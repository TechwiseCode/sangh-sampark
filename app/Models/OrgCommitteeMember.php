<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class OrgCommitteeMember
{
    private static bool $schemaEnsured = false;

    private function ensureSchema(): void
    {
        if (self::$schemaEnsured) {
            return;
        }
        Database::pdo()->exec(
            'CREATE TABLE IF NOT EXISTS organization_committee_members (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                organization_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NULL DEFAULT NULL,
                person_name VARCHAR(191) NOT NULL,
                designation_key VARCHAR(64) NOT NULL,
                created_by INT UNSIGNED NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_org_committee_org (organization_id, id),
                KEY idx_org_committee_designation (organization_id, designation_key),
                KEY idx_org_committee_user (user_id),
                CONSTRAINT fk_org_committee_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
                CONSTRAINT fk_org_committee_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
                CONSTRAINT fk_org_committee_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        self::$schemaEnsured = true;
    }

    /** @return list<array<string,mixed>> */
    public function listForOrganization(int $organizationId): array
    {
        $this->ensureSchema();
        if ($organizationId < 1) {
            return [];
        }
        $stmt = Database::pdo()->prepare(
            'SELECT c.*,
                u.name AS user_name,
                u.phone AS user_phone,
                u.email AS user_email,
                u.member_code,
                u.photo_path
             FROM organization_committee_members c
             LEFT JOIN users u ON u.id = c.user_id
             WHERE c.organization_id = ?
             ORDER BY CASE c.designation_key
                WHEN \'president\' THEN 1
                WHEN \'vice_president\' THEN 2
                WHEN \'secretary\' THEN 3
                WHEN \'joint_secretary\' THEN 4
                WHEN \'treasurer\' THEN 5
                WHEN \'committee_member\' THEN 6
                ELSE 99
             END ASC, c.id ASC'
        );
        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByIdInOrganization(int $id, int $organizationId): ?array
    {
        $this->ensureSchema();
        if ($id < 1 || $organizationId < 1) {
            return null;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT c.*,
                u.name AS user_name,
                u.phone AS user_phone,
                u.photo_path
             FROM organization_committee_members c
             LEFT JOIN users u ON u.id = c.user_id
             WHERE c.id = ? AND c.organization_id = ?
             LIMIT 1'
        );
        $stmt->execute([$id, $organizationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @param array{organization_id:int,user_id:?int,person_name:string,designation_key:string,created_by:?int} $data
     */
    public function create(array $data): int
    {
        $this->ensureSchema();
        $stmt = Database::pdo()->prepare(
            'INSERT INTO organization_committee_members
             (organization_id, user_id, person_name, designation_key, created_by)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['organization_id'],
            $data['user_id'],
            $data['person_name'],
            $data['designation_key'],
            $data['created_by'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * @param array{user_id:?int,person_name:string,designation_key:string} $data
     */
    public function update(int $id, int $organizationId, array $data): void
    {
        $this->ensureSchema();
        $stmt = Database::pdo()->prepare(
            'UPDATE organization_committee_members
             SET user_id = ?, person_name = ?, designation_key = ?
             WHERE id = ? AND organization_id = ?'
        );
        $stmt->execute([
            $data['user_id'],
            $data['person_name'],
            $data['designation_key'],
            $id,
            $organizationId,
        ]);
    }

    public function delete(int $id, int $organizationId): void
    {
        $this->ensureSchema();
        $stmt = Database::pdo()->prepare(
            'DELETE FROM organization_committee_members WHERE id = ? AND organization_id = ?'
        );
        $stmt->execute([$id, $organizationId]);
    }
}
