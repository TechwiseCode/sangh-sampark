<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

final class WebPushService
{
    /** @param array<string,mixed> $data */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): int
    {
        return $this->sendToUserDetailed($userId, $title, $body, $data)['sent'];
    }

    /**
     * @param array<string,mixed> $data
     * @return array{sent:int,failed:int,errors:list<string>}
     */
    public function sendToUserDetailed(int $userId, string $title, string $body, array $data = []): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'errors' => []];
        try {
            if (!web_push_is_configured()) {
                $result['errors'][] = 'VAPID keys are not configured on the server.';

                return $result;
            }
            $rows = (new PushSubscription())->listForUser($userId);
            if ($rows === []) {
                $result['errors'][] = 'No push subscription saved for this user. Enable push on this device first.';

                return $result;
            }

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ], JSON_UNESCAPED_UNICODE);
            if ($payload === false) {
                $result['errors'][] = 'Could not encode push payload.';

                return $result;
            }

            $auth = web_push_vapid_auth();
            if ($auth === null) {
                $result['errors'][] = 'VAPID auth is invalid.';

                return $result;
            }

            $webPush = new WebPush(['VAPID' => $auth]);
            $pushModel = new PushSubscription();
            foreach ($rows as $row) {
                $subscription = Subscription::create([
                    'endpoint' => (string) ($row['endpoint'] ?? ''),
                    'keys' => [
                        'p256dh' => (string) ($row['p256dh_key'] ?? ''),
                        'auth' => (string) ($row['auth_key'] ?? ''),
                    ],
                ]);
                $webPush->sendOneNotification($subscription, $payload);
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $result['sent']++;
                    continue;
                }
                $result['failed']++;
                $reason = $report->getReason();
                $endpoint = $report->getEndpoint();
                $response = $report->getResponse();
                $status = $response !== null ? (int) $response->getStatusCode() : 0;
                $msg = trim($reason . ($status > 0 ? ' (HTTP ' . $status . ')' : ''));
                if ($msg === '') {
                    $msg = 'Push delivery failed.';
                }
                $result['errors'][] = $msg;
                error_log('WebPush failed for user ' . $userId . ': ' . $msg . ' endpoint=' . substr($endpoint, 0, 120));
                if ($endpoint !== '' && in_array($status, [404, 410], true)) {
                    $pushModel->deleteByEndpoint($endpoint);
                }
            }

            return $result;
        } catch (\Throwable $e) {
            error_log('WebPush exception for user ' . $userId . ': ' . $e->getMessage());
            $result['errors'][] = $e->getMessage();

            return $result;
        }
    }
}
