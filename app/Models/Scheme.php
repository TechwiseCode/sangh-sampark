<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Scheme
{
    /** @return int new scheme id */
    public function create(
        int $organizationId,
        string $name,
        string $description,
        string $benefitScope,
        string $benefitType,
        ?string $benefitValue,
        ?string $startsAt,
        ?string $endsAt,
        int $createdBy
    ): int {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO schemes
            (organization_id, name, description, benefit_scope, benefit_type, benefit_value, starts_at, ends_at, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)'
        );
        $stmt->execute([
            $organizationId,
            $name,
            $description,
            $benefitScope,
            $benefitType,
            $benefitValue,
            $startsAt,
            $endsAt,
            $createdBy,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function listByOrganization(int $organizationId): array
    {
        $sql = 'SELECT s.*,
            uc.name AS created_by_name,
            COUNT(sb.id) AS assignment_count
            FROM schemes s
            LEFT JOIN users uc ON uc.id = s.created_by
            LEFT JOIN scheme_benefits sb ON sb.scheme_id = s.id
            WHERE s.organization_id = ?
            GROUP BY s.id, uc.name
            ORDER BY s.created_at DESC, s.id DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Active schemes that have at least one calendar date. */
    public function listDatedForCalendar(int $organizationId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, name, starts_at, ends_at, is_active
             FROM schemes
             WHERE organization_id = ?
               AND is_active = 1
               AND (starts_at IS NOT NULL OR ends_at IS NOT NULL)
             ORDER BY COALESCE(starts_at, ends_at) ASC, id ASC'
        );
        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByIdInOrganization(int $schemeId, int $organizationId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM schemes WHERE id = ? AND organization_id = ? LIMIT 1');
        $stmt->execute([$schemeId, $organizationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function updateInOrganization(
        int $schemeId,
        int $organizationId,
        string $name,
        string $description,
        string $benefitType,
        ?string $benefitValue,
        ?string $startsAt,
        ?string $endsAt,
        int $isActive
    ): void {
        $stmt = Database::pdo()->prepare(
            'UPDATE schemes
            SET name = ?, description = ?, benefit_type = ?, benefit_value = ?, starts_at = ?, ends_at = ?, is_active = ?
            WHERE id = ? AND organization_id = ?'
        );
        $stmt->execute([
            $name,
            $description,
            $benefitType,
            $benefitValue,
            $startsAt,
            $endsAt,
            $isActive,
            $schemeId,
            $organizationId,
        ]);
    }

    public function deleteInOrganization(int $schemeId, int $organizationId): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM schemes WHERE id = ? AND organization_id = ?');
        $stmt->execute([$schemeId, $organizationId]);
    }

    /** @return int inserted assignment rows */
    public function assignForScope(int $schemeId, int $organizationId, string $benefitScope): int
    {
        if ($benefitScope === 'family') {
            return $this->assignFamilyScheme($schemeId, $organizationId);
        }

        return $this->assignMemberScheme($schemeId, $organizationId);
    }

    /** @return int */
    private function assignFamilyScheme(int $schemeId, int $organizationId): int
    {
        $families = (new Family())->listByOrganization($organizationId);
        $rows = 0;
        $stmt = Database::pdo()->prepare(
            'INSERT INTO scheme_benefits
            (scheme_id, organization_id, family_id, beneficiary_user_id, status)
            VALUES (?, ?, ?, ?, \'eligible\')
            ON DUPLICATE KEY UPDATE beneficiary_user_id = VALUES(beneficiary_user_id)'
        );
        foreach ($families as $f) {
            $fid = (int) $f['id'];
            $hid = (int) $f['head_user_id'];
            $stmt->execute([$schemeId, $organizationId, $fid, $hid]);
            $rows++;
        }

        return $rows;
    }

    /** @return int */
    private function assignMemberScheme(int $schemeId, int $organizationId): int
    {
        $stmtMembers = Database::pdo()->prepare(
            "SELECT u.id AS user_id
            FROM users u
            WHERE u.role = 'member'"
        );
        $stmtMembers->execute();
        $members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);
        $rows = 0;
        $stmt = Database::pdo()->prepare(
            'INSERT INTO scheme_benefits
            (scheme_id, organization_id, user_id, beneficiary_user_id, status)
            VALUES (?, ?, ?, ?, \'eligible\')
            ON DUPLICATE KEY UPDATE beneficiary_user_id = VALUES(beneficiary_user_id)'
        );
        foreach ($members as $m) {
            $uid = (int) $m['user_id'];
            $stmt->execute([$schemeId, $organizationId, $uid, $uid]);
            $rows++;
        }

        return $rows;
    }

    /** @return list<array<string,mixed>> */
    public function listEligibleForUser(int $organizationId, int $userId): array
    {
        $sql = 'SELECT s.id AS scheme_id, s.name, s.description, s.benefit_scope, s.benefit_type, s.benefit_value,
            sb.status, sb.claimed_at,
            CASE WHEN s.benefit_scope = \'family\' THEN f.id ELSE NULL END AS family_id,
            hu.name AS family_head_name
            FROM scheme_benefits sb
            INNER JOIN schemes s ON s.id = sb.scheme_id
            LEFT JOIN families f ON f.id = sb.family_id
            LEFT JOIN users hu ON hu.id = f.head_user_id
            LEFT JOIN users oum ON oum.id = sb.user_id
            WHERE sb.organization_id = ?
            AND s.is_active = 1
            AND (
                (s.benefit_scope = \'member\' AND sb.user_id = ?)
                OR
                (s.benefit_scope = \'family\' AND f.head_user_id = ?)
            )
            AND (s.benefit_scope <> \'member\' OR (oum.role = \'member\'))
            ORDER BY
                CASE WHEN sb.status = \'claimed\' THEN 1 ELSE 0 END ASC,
                s.created_at DESC,
                s.id DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$organizationId, $userId, $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string,mixed>> */
    public function listAssignmentsForAdmin(int $organizationId, int $schemeId): array
    {
        $sql = 'SELECT sb.id AS benefit_id, sb.status, sb.claimed_at,
            sb.family_id, sb.user_id, sb.beneficiary_user_id,
            beneficiary.name AS beneficiary_name,
            claimant.name AS claimed_by_name,
            sb.claimed_by_user_id,
            s.benefit_scope,
            f.head_user_id,
            hu.name AS head_name,
            u.name AS member_name,
            oub.member_code AS beneficiary_member_code,
            CASE
              WHEN oub.member_code IS NOT NULL AND org.org_code IS NOT NULL THEN CONCAT(org.org_code, \'-\', oub.member_code)
              ELSE NULL
            END AS beneficiary_full_member_code
            FROM scheme_benefits sb
            INNER JOIN schemes s ON s.id = sb.scheme_id
            INNER JOIN organizations org ON org.id = sb.organization_id
            LEFT JOIN families f ON f.id = sb.family_id
            LEFT JOIN users hu ON hu.id = f.head_user_id
            LEFT JOIN users u ON u.id = sb.user_id
            LEFT JOIN users beneficiary ON beneficiary.id = sb.beneficiary_user_id
            LEFT JOIN users claimant ON claimant.id = sb.claimed_by_user_id
            LEFT JOIN users oum ON oum.id = sb.user_id
            LEFT JOIN users oub ON oub.id = sb.beneficiary_user_id
            WHERE sb.organization_id = ? AND sb.scheme_id = ?
            AND (
                s.benefit_scope = \'family\'
                OR
                (s.benefit_scope = \'member\' AND oum.role = \'member\')
            )
            ORDER BY CASE sb.status WHEN \'claimed\' THEN 0 ELSE 1 END, sb.id ASC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$organizationId, $schemeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return string|null error */
    public function markDoneByAdmin(int $organizationId, int $schemeId, int $benefitId, int $adminUserId): ?string
    {
        $stmt = Database::pdo()->prepare(
            'SELECT sb.id, sb.status
            FROM scheme_benefits sb
            INNER JOIN schemes s ON s.id = sb.scheme_id
            WHERE sb.id = ? AND sb.scheme_id = ? AND sb.organization_id = ? AND s.organization_id = ?
            LIMIT 1'
        );
        $stmt->execute([$benefitId, $schemeId, $organizationId, $organizationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return 'Assignment not found.';
        }
        if ((string) $row['status'] === 'claimed') {
            return 'This benefit is already marked done.';
        }

        $up = Database::pdo()->prepare(
            'UPDATE scheme_benefits
            SET status = \'claimed\', claimed_at = CURRENT_TIMESTAMP, claimed_by_user_id = ?
            WHERE id = ?'
        );
        $up->execute([$adminUserId, $benefitId]);

        return null;
    }
}

