<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\OrganizationPortalTrait;
use App\Core\Request;
use App\Models\MemberAdminChat;
use App\Models\Notification;
use App\Models\Organization;
use App\Services\Access;

use function base_url;
use function current_user;
use function flash_set;
use function json_response;
use function page_title;
use function redirect;
use function t;
use function user_display_name;

final class MemberAdminChatController extends Controller
{
    use OrganizationPortalTrait;

    /** @var array<string,mixed>|null */
    private ?array $jsonPayload = null;

    public function memberMessages(Request $request): void
    {
        $this->assertEnabled();
        $orgId = $this->requireOrgId();
        $user = current_user();
        $access = new Access();
        if ($access->canManageOrganization($user, $orgId)) {
            json_response(['ok' => false, 'error' => 'admin_use_inbox'], 403);
        }

        $token = $this->sessionTokenFromRequest();
        if ($token === '') {
            json_response(['ok' => true, 'messages' => []]);
        }

        $model = new MemberAdminChat();
        $uid = (int) $user['id'];
        $rows = $model->listMessagesForMemberSession($orgId, $uid, $token);

        json_response([
            'ok' => true,
            'messages' => $this->messagesToClient($rows),
        ]);
    }

    public function memberSend(Request $request): void
    {
        $this->assertEnabled();
        $orgId = $this->requireOrgId();
        $user = current_user();
        $access = new Access();
        if ($access->canManageOrganization($user, $orgId)) {
            json_response(['ok' => false, 'error' => 'forbidden'], 403);
        }

        $token = $this->sessionTokenFromRequest();
        if ($token === '') {
            json_response(['ok' => false, 'error' => 'invalid_session'], 422);
        }

        $body = $this->readMessageBody();
        $model = new MemberAdminChat();
        $uid = (int) $user['id'];

        try {
            $result = $model->sendMemberMessage($orgId, $uid, $token, $body);
        } catch (\InvalidArgumentException $e) {
            json_response(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        $memberName = user_display_name($user);
        $preview = mb_strlen($body) > 200 ? mb_substr($body, 0, 197) . '...' : $body;
        $title = apply_text_placeholders(t('member_chat.notify_admin_title'), ['name' => $memberName]);
        $msg = $preview;
        $notif = new Notification();
        $inboxUrl = base_url() . '/organization/member-messages?thread=' . $result['thread_id'];
        foreach ((new Organization())->listAdminUsers($orgId) as $adminRow) {
            $adminId = (int) ($adminRow['user_id'] ?? 0);
            if ($adminId < 1 || $adminId === $uid) {
                continue;
            }
            $notif->createForUser(
                $adminId,
                'member_admin_chat',
                $result['thread_id'],
                $title,
                $msg,
                true,
                [
                    'url' => $inboxUrl,
                    'tag' => 'member-chat-thread-' . $result['thread_id'],
                ]
            );
        }

        $rows = $model->listMessagesForMemberSession($orgId, $uid, $token);
        json_response([
            'ok' => true,
            'threadId' => $result['thread_id'],
            'messages' => $this->messagesToClient($rows),
            'adminInboxUrl' => $inboxUrl,
        ]);
    }

    public function adminIndex(Request $request): void
    {
        if (!member_admin_chat_enabled()) {
            flash_set('error', t('member_chat.disabled'));
            redirect(base_url() . '/organization/dashboard');
        }
        $orgId = $this->requireOrgId();
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', t('member_chat.admin_only'));
            redirect(base_url() . '/organization/dashboard');
        }

        $bundle = $this->orgPageBundle($orgId);
        $model = new MemberAdminChat();
        $threads = $model->listOpenThreadsForOrganization($orgId);
        $activeThreadId = max(0, (int) ($_GET['thread'] ?? 0));
        $activeThread = null;
        $activeMessages = [];
        if ($activeThreadId > 0) {
            $activeThread = $model->findThreadForOrganization($activeThreadId, $orgId);
            if ($activeThread !== null) {
                $activeMessages = $model->listMessagesForThread($activeThreadId);
                $inList = false;
                foreach ($threads as $threadRow) {
                    if ((int) ($threadRow['id'] ?? 0) === $activeThreadId) {
                        $inList = true;
                        break;
                    }
                }
                if (!$inList) {
                    $last = $activeMessages !== [] ? $activeMessages[count($activeMessages) - 1] : null;
                    array_unshift($threads, array_merge($activeThread, [
                        'last_body' => is_array($last) ? (string) ($last['body'] ?? '') : '',
                        'last_message_at' => is_array($last) ? (string) ($last['created_at'] ?? '') : (string) ($activeThread['updated_at'] ?? ''),
                    ]));
                }
            }
        }

        $this->render('organization', 'member_admin_chat/index.php', [
            'pageTitle' => page_title(t('member_chat.admin_page_title')),
            'navActive' => 'member_messages',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => $orgId,
            'threads' => $threads,
            'activeThread' => $activeThread,
            'activeMessages' => $activeMessages,
            'activeThreadId' => $activeThreadId,
        ]);
    }

    public function adminReply(Request $request): void
    {
        if (!member_admin_chat_enabled()) {
            flash_set('error', t('member_chat.disabled'));
            redirect(base_url() . '/organization/dashboard');
        }
        $orgId = $this->requireOrgId();
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            json_response(['ok' => false, 'error' => 'forbidden'], 403);
        }

        $threadId = (int) ($_POST['thread_id'] ?? 0);
        $body = $this->readMessageBody();
        $model = new MemberAdminChat();

        try {
            $model->sendAdminReply($orgId, $threadId, (int) $user['id'], $body);
        } catch (\InvalidArgumentException $e) {
            if ($this->wantsJson()) {
                json_response(['ok' => false, 'error' => $e->getMessage()], 422);
            }
            flash_set('error', t('member_chat.error_empty'));
            redirect(base_url() . '/organization/member-messages?thread=' . $threadId);
        } catch (\RuntimeException $e) {
            if ($this->wantsJson()) {
                json_response(['ok' => false, 'error' => $e->getMessage()], 404);
            }
            flash_set('error', t('member_chat.error_thread'));
            redirect(base_url() . '/organization/member-messages');
        }

        $thread = $model->findThreadForOrganization($threadId, $orgId);
        if ($thread !== null) {
            $memberId = (int) ($thread['member_user_id'] ?? 0);
            if ($memberId > 0) {
                $title = t('member_chat.notify_member_title');
                $preview = mb_strlen($body) > 500 ? mb_substr($body, 0, 497) . '...' : $body;
                (new Notification())->createForUser(
                    $memberId,
                    'member_admin_chat_reply',
                    $threadId,
                    $title,
                    $preview,
                    true,
                    [
                        'url' => base_url() . '/organization/notifications',
                        'tag' => 'member-chat-reply-' . $threadId,
                    ]
                );
            }
        }

        if ($this->wantsJson()) {
            $messages = $model->listMessagesForThread($threadId);
            json_response([
                'ok' => true,
                'messages' => $this->messagesToClient($messages),
            ]);
        }

        flash_set('ok', t('member_chat.reply_sent'));
        redirect(base_url() . '/organization/member-messages?thread=' . $threadId);
    }

    private function assertEnabled(): void
    {
        if (!member_admin_chat_enabled()) {
            json_response(['ok' => false, 'error' => 'disabled'], 404);
        }
    }

    private function readMessageBody(): string
    {
        $payload = $this->jsonPayload();
        if ($payload !== null) {
            return trim((string) ($payload['body'] ?? $payload['message'] ?? ''));
        }

        return trim((string) ($_POST['body'] ?? $_POST['message'] ?? ''));
    }

    private function sessionTokenFromRequest(): string
    {
        $header = trim((string) ($_SERVER['HTTP_X_CHAT_SESSION'] ?? ''));
        if ($header !== '' && preg_match('/^[a-f0-9]{32}$/', $header) === 1) {
            return $header;
        }
        $payload = $this->jsonPayload();
        if ($payload !== null) {
            $fromJson = trim((string) ($payload['session_token'] ?? ''));
            if ($fromJson !== '' && preg_match('/^[a-f0-9]{32}$/', $fromJson) === 1) {
                return $fromJson;
            }
        }
        $fromPost = trim((string) ($_POST['session_token'] ?? ''));
        if ($fromPost !== '' && preg_match('/^[a-f0-9]{32}$/', $fromPost) === 1) {
            return $fromPost;
        }

        return '';
    }

    /** @return array<string,mixed>|null */
    private function jsonPayload(): ?array
    {
        if ($this->jsonPayload !== null) {
            return $this->jsonPayload;
        }
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (!str_contains($contentType, 'application/json')) {
            $this->jsonPayload = [];

            return null;
        }
        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        $this->jsonPayload = is_array($decoded) ? $decoded : [];

        return is_array($decoded) ? $decoded : null;
    }

    private function wantsJson(): bool
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        return str_contains($accept, 'application/json') || str_contains($contentType, 'application/json');
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    private function messagesToClient(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'role' => (string) ($row['sender_role'] ?? ''),
                'body' => (string) ($row['body'] ?? ''),
                'createdAt' => (string) ($row['created_at'] ?? ''),
            ];
        }

        return $out;
    }
}
