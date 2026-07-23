<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class MemberAdminChat
{
    private static bool $ensured = false;

    public function isEnabled(): bool
    {
        return member_admin_chat_enabled();
    }

    private function ensureTables(): void
    {
        if (self::$ensured) {
            return;
        }
        $pdo = Database::pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS org_member_admin_chat_threads (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                organization_id INT UNSIGNED NOT NULL,
                member_user_id INT UNSIGNED NOT NULL,
                session_token VARCHAR(64) NOT NULL,
                status ENUM(\'open\',\'replied\') NOT NULL DEFAULT \'open\',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_mac_thread_org_status (organization_id, status, updated_at),
                KEY idx_mac_thread_member_session (member_user_id, session_token),
                CONSTRAINT fk_mac_thread_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
                CONSTRAINT fk_mac_thread_member FOREIGN KEY (member_user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS org_member_admin_chat_messages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                thread_id INT UNSIGNED NOT NULL,
                sender_role ENUM(\'member\',\'admin\') NOT NULL,
                sender_user_id INT UNSIGNED NOT NULL,
                body TEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_mac_msg_thread (thread_id, created_at),
                CONSTRAINT fk_mac_msg_thread FOREIGN KEY (thread_id) REFERENCES org_member_admin_chat_threads (id) ON DELETE CASCADE,
                CONSTRAINT fk_mac_msg_sender FOREIGN KEY (sender_user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        self::$ensured = true;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listMessagesForMemberSession(int $organizationId, int $memberUserId, string $sessionToken): array
    {
        $this->ensureTables();
        $thread = $this->findThreadBySession($organizationId, $memberUserId, $sessionToken);
        if ($thread === null) {
            return [];
        }

        return $this->listMessagesForThread((int) $thread['id']);
    }

    /**
     * @return array{thread_id:int,message_id:int}
     */
    public function sendMemberMessage(int $organizationId, int $memberUserId, string $sessionToken, string $body): array
    {
        $this->ensureTables();
        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException('empty_body');
        }
        if (strlen($body) > 2000) {
            throw new \InvalidArgumentException('body_too_long');
        }

        $pdo = Database::pdo();
        $thread = $this->findThreadBySession($organizationId, $memberUserId, $sessionToken);
        if ($thread === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO org_member_admin_chat_threads (organization_id, member_user_id, session_token, status)
                 VALUES (?, ?, ?, \'open\')'
            );
            $stmt->execute([$organizationId, $memberUserId, $sessionToken]);
            $threadId = (int) $pdo->lastInsertId();
        } else {
            $threadId = (int) $thread['id'];
            $pdo->prepare(
                'UPDATE org_member_admin_chat_threads SET status = \'open\', updated_at = CURRENT_TIMESTAMP WHERE id = ?'
            )->execute([$threadId]);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO org_member_admin_chat_messages (thread_id, sender_role, sender_user_id, body) VALUES (?, \'member\', ?, ?)'
        );
        $stmt->execute([$threadId, $memberUserId, $body]);
        $messageId = (int) $pdo->lastInsertId();

        return ['thread_id' => $threadId, 'message_id' => $messageId];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listOpenThreadsForOrganization(int $organizationId, int $limit = 50): array
    {
        $this->ensureTables();
        $lim = max(1, min(100, $limit));
        $stmt = Database::pdo()->prepare(
            "SELECT t.id, t.member_user_id, t.status, t.created_at, t.updated_at,
                    u.name, u.first_name, u.middle_name, u.last_name, u.email, u.phone,
                    (
                      SELECT m.body FROM org_member_admin_chat_messages m
                      WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1
                    ) AS last_body,
                    (
                      SELECT m.sender_role FROM org_member_admin_chat_messages m
                      WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1
                    ) AS last_sender_role,
                    (
                      SELECT m.created_at FROM org_member_admin_chat_messages m
                      WHERE m.thread_id = t.id ORDER BY m.id DESC LIMIT 1
                    ) AS last_message_at
             FROM org_member_admin_chat_threads t
             INNER JOIN users u ON u.id = t.member_user_id
             WHERE t.organization_id = ?
               AND t.status = 'open'
             ORDER BY t.updated_at DESC
             LIMIT {$lim}"
        );
        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countOpenThreadsForOrganization(int $organizationId): int
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM org_member_admin_chat_threads
             WHERE organization_id = ? AND status = 'open'"
        );
        $stmt->execute([$organizationId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array{name:string,preview:string}|null
     */
    public function notificationContextForThread(int $threadId): ?array
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'SELECT u.name, u.first_name, u.middle_name, u.last_name,
                    (SELECT m.body FROM org_member_admin_chat_messages m
                     WHERE m.thread_id = t.id AND m.sender_role = \'member\'
                     ORDER BY m.id ASC LIMIT 1) AS member_preview
             FROM org_member_admin_chat_threads t
             INNER JOIN users u ON u.id = t.member_user_id
             WHERE t.id = ?
             LIMIT 1'
        );
        $stmt->execute([$threadId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        $preview = trim((string) ($row['member_preview'] ?? ''));
        if (mb_strlen($preview) > 200) {
            $preview = mb_substr($preview, 0, 197) . '...';
        }

        return [
            'name' => user_display_name($row),
            'preview' => $preview,
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findThreadForOrganization(int $threadId, int $organizationId): ?array
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'SELECT t.*, u.name, u.first_name, u.middle_name, u.last_name, u.email, u.phone
             FROM org_member_admin_chat_threads t
             INNER JOIN users u ON u.id = t.member_user_id
             WHERE t.id = ? AND t.organization_id = ?
             LIMIT 1'
        );
        $stmt->execute([$threadId, $organizationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listMessagesForThread(int $threadId): array
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'SELECT id, sender_role, sender_user_id, body, created_at
             FROM org_member_admin_chat_messages
             WHERE thread_id = ?
             ORDER BY id ASC'
        );
        $stmt->execute([$threadId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sendAdminReply(int $organizationId, int $threadId, int $adminUserId, string $body): int
    {
        $this->ensureTables();
        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException('empty_body');
        }
        if (strlen($body) > 2000) {
            throw new \InvalidArgumentException('body_too_long');
        }

        $thread = $this->findThreadForOrganization($threadId, $organizationId);
        if ($thread === null) {
            throw new \RuntimeException('thread_not_found');
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO org_member_admin_chat_messages (thread_id, sender_role, sender_user_id, body) VALUES (?, \'admin\', ?, ?)'
        );
        $stmt->execute([$threadId, $adminUserId, $body]);
        $messageId = (int) $pdo->lastInsertId();

        $pdo->prepare(
            'UPDATE org_member_admin_chat_threads SET status = \'replied\', updated_at = CURRENT_TIMESTAMP WHERE id = ?'
        )->execute([$threadId]);

        return $messageId;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function findThreadBySession(int $organizationId, int $memberUserId, string $sessionToken): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM org_member_admin_chat_threads
             WHERE organization_id = ? AND member_user_id = ? AND session_token = ?
             LIMIT 1'
        );
        $stmt->execute([$organizationId, $memberUserId, $sessionToken]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }
}
