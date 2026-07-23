<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\WebPushService;
use PDO;

final class Notification
{
    public const RECENT_MONTHS = 2;

    public const PAGE_SIZE = 7;

    private static bool $ensured = false;

    private function ensureTables(): void
    {
        if (self::$ensured) {
            return;
        }
        $pdo = Database::pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS notification_campaigns (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                organization_id INT UNSIGNED NOT NULL,
                audience VARCHAR(32) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NULL,
                channels VARCHAR(64) NOT NULL DEFAULT \'in_app\',
                total_recipients INT UNSIGNED NOT NULL DEFAULT 0,
                in_app_sent_count INT UNSIGNED NOT NULL DEFAULT 0,
                whatsapp_queued_count INT UNSIGNED NOT NULL DEFAULT 0,
                push_sent_count INT UNSIGNED NOT NULL DEFAULT 0,
                created_by_user_id INT UNSIGNED NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_notif_campaign_org (organization_id, created_at),
                CONSTRAINT fk_notif_campaign_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
                CONSTRAINT fk_notif_campaign_user FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS whatsapp_notification_queue (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                campaign_id INT UNSIGNED NULL DEFAULT NULL,
                organization_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                phone VARCHAR(32) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NULL,
                status ENUM(\'pending\',\'sent\',\'failed\') NOT NULL DEFAULT \'pending\',
                attempts INT UNSIGNED NOT NULL DEFAULT 0,
                last_error VARCHAR(255) NULL DEFAULT NULL,
                sent_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_wa_queue_org_status (organization_id, status),
                KEY idx_wa_queue_campaign (campaign_id),
                CONSTRAINT fk_wa_queue_campaign FOREIGN KEY (campaign_id) REFERENCES notification_campaigns (id) ON DELETE SET NULL,
                CONSTRAINT fk_wa_queue_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
                CONSTRAINT fk_wa_queue_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $this->ensureCampaignPushColumn($pdo);
        $this->ensureCampaignRecipientFiltersColumn($pdo);
        self::$ensured = true;
    }

    private function ensureCampaignRecipientFiltersColumn(\PDO $pdo): void
    {
        try {
            if ($this->campaignHasRecipientFiltersColumn($pdo)) {
                return;
            }
            $pdo->exec(
                'ALTER TABLE notification_campaigns ADD COLUMN recipient_filters VARCHAR(255) NULL DEFAULT NULL AFTER audience'
            );
        } catch (\Throwable $e) {
            // Table may not exist yet on fresh installs before schema migration.
        }
    }

    private function campaignHasRecipientFiltersColumn(\PDO $pdo): bool
    {
        try {
            $rows = $pdo->query('SHOW COLUMNS FROM notification_campaigns LIKE \'recipient_filters\'')->fetchAll(PDO::FETCH_ASSOC);

            return $rows !== [];
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return int campaign id
     */
    private function insertCampaignRow(
        \PDO $pdo,
        int $organizationId,
        string $audienceLabel,
        ?string $filtersJson,
        string $title,
        string $message,
        string $channelsCsv,
        ?int $createdByUserId
    ): int {
        $this->ensureCampaignRecipientFiltersColumn($pdo);
        if ($this->campaignHasRecipientFiltersColumn($pdo)) {
            $campaignStmt = $pdo->prepare(
                'INSERT INTO notification_campaigns
                (organization_id, audience, recipient_filters, title, message, channels, total_recipients, in_app_sent_count, whatsapp_queued_count, push_sent_count, created_by_user_id)
                VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, 0, ?)'
            );
            $campaignStmt->execute([
                $organizationId,
                $audienceLabel,
                $filtersJson,
                $title,
                $message,
                $channelsCsv,
                $createdByUserId,
            ]);
        } else {
            $campaignStmt = $pdo->prepare(
                'INSERT INTO notification_campaigns
                (organization_id, audience, title, message, channels, total_recipients, in_app_sent_count, whatsapp_queued_count, push_sent_count, created_by_user_id)
                VALUES (?, ?, ?, ?, ?, 0, 0, 0, 0, ?)'
            );
            $campaignStmt->execute([
                $organizationId,
                $audienceLabel,
                $title,
                $message,
                $channelsCsv,
                $createdByUserId,
            ]);
        }

        return (int) $pdo->lastInsertId();
    }

    private function ensureCampaignPushColumn(\PDO $pdo): void
    {
        try {
            $rows = $pdo->query('SHOW COLUMNS FROM notification_campaigns LIKE \'push_sent_count\'')->fetchAll(PDO::FETCH_ASSOC);
            if ($rows === []) {
                $pdo->exec(
                    'ALTER TABLE notification_campaigns ADD COLUMN push_sent_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER whatsapp_queued_count'
                );
            }
        } catch (\Throwable $e) {
            // Table may not exist yet on fresh installs before schema migration.
        }
    }

    public function createForUser(
        int $userId,
        string $type,
        ?int $referenceId,
        string $title,
        string $message,
        bool $dispatchPush = true,
        array $pushExtra = []
    ): int {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'INSERT INTO notifications (user_id, type, reference_id, title, message) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $type, $referenceId, $title, $message]);
        $notificationId = (int) Database::pdo()->lastInsertId();
        if ($dispatchPush) {
            $this->dispatchWebPush($userId, $title, $message, $type, $referenceId, $notificationId, $pushExtra);
        }

        return $notificationId;
    }

    /** @param array<string,mixed> $extra */
    private function dispatchWebPush(
        int $userId,
        string $title,
        string $message,
        string $type,
        ?int $referenceId,
        int $notificationId,
        array $extra = []
    ): int {
        try {
            return (new WebPushService())->sendToUser($userId, $title, $message, array_merge([
                'type' => $type,
                'referenceId' => $referenceId,
                'notificationId' => $notificationId,
                'url' => base_url() . '/organization/notifications',
            ], $extra));
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function markReadByReferenceForUser(int $referenceId, string $type, int $userId): void
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE reference_id = ? AND type = ? AND user_id = ? AND read_at IS NULL'
        );
        $stmt->execute([$referenceId, $type, $userId]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRecentForUser(int $userId, int $limit = 50): array
    {
        return $this->listPagedForUser($userId, $limit, 0);
    }

    public function recentCutoff(): string
    {
        return (new \DateTimeImmutable('today'))
            ->modify('-' . self::RECENT_MONTHS . ' months')
            ->format('Y-m-d 00:00:00');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPagedForUser(int $userId, int $limit, int $offset = 0): array
    {
        $this->ensureTables();
        $lim = max(1, min(50, $limit));
        $off = max(0, $offset);
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM notifications
             WHERE user_id = ? AND created_at >= ?
             ORDER BY (read_at IS NULL) DESC, created_at DESC
             LIMIT {$lim} OFFSET {$off}"
        );
        $stmt->execute([$userId, $this->recentCutoff()]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countInWindowForUser(int $userId): int
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND created_at >= ?'
        );
        $stmt->execute([$userId, $this->recentCutoff()]);

        return (int) $stmt->fetchColumn();
    }

    public function countUnread(int $userId): int
    {
        return $this->countUnreadInWindow($userId);
    }

    public function countUnreadInWindow(int $userId): int
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL AND created_at >= ?'
        );
        $stmt->execute([$userId, $this->recentCutoff()]);

        return (int) $stmt->fetchColumn();
    }

    /** @return list<array<string, mixed>> */
    public function listPreviewForUser(int $userId, int $limit = 3): array
    {
        return $this->listPagedForUser($userId, $limit, 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRecentForUserUnfiltered(int $userId, int $limit = 50): array
    {
        $this->ensureTables();
        $lim = max(1, min(100, $limit));
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT {$lim}"
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markReadForUser(int $notificationId, int $userId): bool
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ? AND read_at IS NULL'
        );
        $stmt->execute([$notificationId, $userId]);

        return $stmt->rowCount() > 0;
    }

    public function markAllReadForUser(int $userId): int
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND read_at IS NULL'
        );
        $stmt->execute([$userId]);

        return $stmt->rowCount();
    }

    /**
     * @param list<string> $channels
     * @param array{heads_only?:bool,gender?:?string,profession?:?string,age_ranges?:list<string>,storage?:array<string,mixed>,recipient_user_ids?:list<int>} $memberFilters
     * @return array{campaign_id:int,total_recipients:int,in_app_sent_count:int,whatsapp_queued_count:int,push_sent_count:int}
     */
    public function broadcastToOrganization(
        int $organizationId,
        string $title,
        string $message,
        array $channels,
        array $memberFilters,
        ?int $createdByUserId
    ): array {
        $this->ensureTables();
        $sendInApp = in_array('in_app', $channels, true);
        $sendPush = in_array('web_push', $channels, true);
        $queueWhatsApp = in_array('whatsapp', $channels, true);
        if (!$sendInApp && !$sendPush && !$queueWhatsApp) {
            $sendInApp = true;
            $sendPush = true;
        }
        $directoryFilters = [
            'heads_only' => !empty($memberFilters['heads_only']),
            'age_ranges' => isset($memberFilters['age_ranges']) && is_array($memberFilters['age_ranges'])
                ? $memberFilters['age_ranges']
                : [],
        ];
        if (isset($memberFilters['gender']) && $memberFilters['gender'] !== null) {
            $directoryFilters['gender'] = $memberFilters['gender'];
        }
        if (isset($memberFilters['profession']) && $memberFilters['profession'] !== null) {
            $directoryFilters['profession'] = $memberFilters['profession'];
        }
        if (isset($memberFilters['donation']) && $memberFilters['donation'] !== null) {
            $directoryFilters['donation'] = $memberFilters['donation'];
        }
        $storage = isset($memberFilters['storage']) && is_array($memberFilters['storage'])
            ? $memberFilters['storage']
            : [];
        $audienceLabel = !empty($memberFilters['heads_only']) ? 'family_heads' : 'all_members';
        $recipientRows = (new Organization())->listMemberRecipientRows($organizationId, $directoryFilters);
        $filteredCount = count($recipientRows);
        $recipientUserIds = isset($memberFilters['recipient_user_ids']) && is_array($memberFilters['recipient_user_ids'])
            ? array_values(array_unique(array_filter(array_map('intval', $memberFilters['recipient_user_ids']), static function (int $id): bool {
                return $id > 0;
            })))
            : [];
        if ($recipientUserIds !== []) {
            $allowed = array_flip($recipientUserIds);
            $recipientRows = array_values(array_filter(
                $recipientRows,
                static function (array $row) use ($allowed): bool {
                    return isset($allowed[(int) ($row['id'] ?? 0)]);
                }
            ));
        }
        if ($recipientUserIds !== [] && count($recipientRows) < $filteredCount) {
            $storage['selected_count'] = count($recipientRows);
            $storage['filtered_count'] = $filteredCount;
        }
        $filtersJson = $storage !== [] ? json_encode($storage, JSON_UNESCAPED_UNICODE) : null;
        if ($filtersJson === false) {
            $filtersJson = null;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $campaignId = $this->insertCampaignRow(
                $pdo,
                $organizationId,
                $audienceLabel,
                $filtersJson,
                $title,
                $message,
                implode(',', array_values(array_unique($channels))),
                $createdByUserId
            );
            $inAppCount = 0;
            $waCount = 0;
            $pushCount = 0;
            $waStmt = $pdo->prepare(
                'INSERT INTO whatsapp_notification_queue
                (campaign_id, organization_id, user_id, phone, title, message, status)
                VALUES (?, ?, ?, ?, ?, ?, \'pending\')'
            );
            $pushRecipients = [];
            foreach ($recipientRows as $row) {
                $uid = (int) ($row['id'] ?? 0);
                if ($uid <= 0) {
                    continue;
                }
                if ($sendInApp) {
                    $stmt = $pdo->prepare(
                        'INSERT INTO notifications (user_id, type, reference_id, title, message) VALUES (?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([$uid, 'broadcast', $campaignId, $title, $message]);
                    $inAppCount++;
                }
                if ($sendPush) {
                    $pushRecipients[] = $uid;
                }
                if ($queueWhatsApp) {
                    $phone = trim((string) ($row['phone'] ?? ''));
                    if ($phone !== '') {
                        $waStmt->execute([$campaignId, $organizationId, $uid, $phone, $title, $message]);
                        $waCount++;
                    }
                }
            }
            $updateStmt = $pdo->prepare(
                'UPDATE notification_campaigns
                SET total_recipients = ?, in_app_sent_count = ?, whatsapp_queued_count = ?, push_sent_count = ?
                WHERE id = ?'
            );
            $updateStmt->execute([count($recipientRows), $inAppCount, $waCount, 0, $campaignId]);
            $pdo->commit();

            if ($sendPush && $pushRecipients !== []) {
                try {
                    $webPush = new WebPushService();
                    foreach ($pushRecipients as $uid) {
                        $pushCount += $webPush->sendToUser($uid, $title, $message, [
                            'type' => 'broadcast',
                            'referenceId' => $campaignId,
                            'url' => base_url() . '/organization/notifications',
                        ]);
                    }
                    $pdo->prepare('UPDATE notification_campaigns SET push_sent_count = ? WHERE id = ?')
                        ->execute([$pushCount, $campaignId]);
                } catch (\Throwable $e) {
                    // In-app broadcast already committed; do not fail the whole request if push fails.
                }
            }

            return [
                'campaign_id' => $campaignId,
                'total_recipients' => count($recipientRows),
                'in_app_sent_count' => $inAppCount,
                'whatsapp_queued_count' => $waCount,
                'push_sent_count' => $pushCount,
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return list<array<string,mixed>> */
    public function listRecentCampaignsForOrganization(int $organizationId, int $limit = 20): array
    {
        $this->ensureTables();
        $lim = max(1, min(100, $limit));
        $stmt = Database::pdo()->prepare(
            "SELECT nc.*, u.name AS created_by_name
            FROM notification_campaigns nc
            LEFT JOIN users u ON u.id = nc.created_by_user_id
            WHERE nc.organization_id = ?
            ORDER BY nc.created_at DESC
            LIMIT {$lim}"
        );
        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string,mixed>> */
    public function listQueueSummaryForOrganization(int $organizationId): array
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'SELECT status, COUNT(*) AS cnt
            FROM whatsapp_notification_queue
            WHERE organization_id = ?
            GROUP BY status'
        );
        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
