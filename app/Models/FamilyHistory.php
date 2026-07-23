<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class FamilyHistory
{
    public function ensureTable(): void
    {
        Database::pdo()->exec(
            'CREATE TABLE IF NOT EXISTS family_member_history (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                organization_id INT UNSIGNED NOT NULL,
                family_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NULL DEFAULT NULL,
                actor_user_id INT UNSIGNED NULL DEFAULT NULL,
                event_type VARCHAR(40) NOT NULL,
                event_label VARCHAR(120) NOT NULL,
                details TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_fmh_org_family (organization_id, family_id),
                KEY idx_fmh_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function create(
        int $organizationId,
        int $familyId,
        ?int $userId,
        ?int $actorUserId,
        string $eventType,
        string $eventLabel,
        ?string $details
    ): void {
        $this->ensureTable();
        $stmt = Database::pdo()->prepare(
            'INSERT INTO family_member_history
            (organization_id, family_id, user_id, actor_user_id, event_type, event_label, details)
            VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $organizationId,
            $familyId,
            $userId,
            $actorUserId,
            $eventType,
            $eventLabel,
            $details,
        ]);
    }

    /** @return list<array<string,mixed>> */
    public function listByFamily(int $organizationId, int $familyId): array
    {
        $this->ensureTable();
        $stmt = Database::pdo()->prepare(
            'SELECT h.*, u.name AS user_name, a.name AS actor_name
            FROM family_member_history h
            LEFT JOIN users u ON u.id = h.user_id
            LEFT JOIN users a ON a.id = h.actor_user_id
            WHERE h.organization_id = ? AND h.family_id = ?
            ORDER BY h.id DESC'
        );
        $stmt->execute([$organizationId, $familyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

