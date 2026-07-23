<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

final class PushSubscription
{
    private bool $ensured = false;

    private function ensureTable(): void
    {
        if ($this->ensured) {
            return;
        }
        Database::pdo()->exec(
            'CREATE TABLE IF NOT EXISTS push_subscriptions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                endpoint VARCHAR(2048) NOT NULL,
                p256dh_key VARCHAR(255) NOT NULL,
                auth_key VARCHAR(255) NOT NULL,
                user_agent VARCHAR(255) NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_push_endpoint (endpoint(512)),
                KEY idx_push_user (user_id),
                CONSTRAINT fk_push_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        try {
            Database::pdo()->exec(
                'ALTER TABLE push_subscriptions MODIFY endpoint VARCHAR(2048) NOT NULL'
            );
        } catch (PDOException $e) {
            // Column may already be wide enough.
        }
        $this->ensured = true;
    }

    public function upsertForUser(
        int $userId,
        string $endpoint,
        string $p256dhKey,
        string $authKey,
        ?string $userAgent = null
    ): void {
        $this->ensureTable();
        $endpoint = trim($endpoint);
        if ($userId <= 0 || $endpoint === '' || $p256dhKey === '' || $authKey === '') {
            return;
        }
        $stmt = Database::pdo()->prepare(
            'INSERT INTO push_subscriptions (user_id, endpoint, p256dh_key, auth_key, user_agent)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                p256dh_key = VALUES(p256dh_key),
                auth_key = VALUES(auth_key),
                user_agent = VALUES(user_agent),
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$userId, $endpoint, $p256dhKey, $authKey, $userAgent]);
    }

    public function deleteForUserEndpoint(int $userId, string $endpoint): void
    {
        $this->ensureTable();
        $stmt = Database::pdo()->prepare(
            'DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint = ?'
        );
        $stmt->execute([$userId, trim($endpoint)]);
    }

    public function deleteByEndpoint(string $endpoint): void
    {
        $this->ensureTable();
        $stmt = Database::pdo()->prepare('DELETE FROM push_subscriptions WHERE endpoint = ?');
        $stmt->execute([trim($endpoint)]);
    }

    /** @return list<array<string,mixed>> */
    public function listForUser(int $userId): array
    {
        $this->ensureTable();
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM push_subscriptions WHERE user_id = ? ORDER BY updated_at DESC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countForUser(int $userId): int
    {
        $this->ensureTable();
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM push_subscriptions WHERE user_id = ?');
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }
}
