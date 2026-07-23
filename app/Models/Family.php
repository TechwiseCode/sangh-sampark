<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Family
{
    /**
     * INNER JOIN (after `FROM families f`) so each head appears once — lowest family id is the canonical household.
     */
    public static function sqlJoinCanonicalHousehold(): string
    {
        return ' INNER JOIN (
            SELECT head_user_id, MIN(id) AS canonical_family_id
            FROM families
            GROUP BY head_user_id
        ) household ON household.canonical_family_id = f.id ';
    }

    public function countAll(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM families')->fetchColumn();
    }

    public function countCreatedSinceDays(int $days): int
    {
        $days = max(1, $days);
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM families WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'
        );
        $stmt->execute([$days]);

        return (int) $stmt->fetchColumn();
    }

    public function countHeadWithoutMembership(): int
    {
        $sql = "SELECT COUNT(*)
            FROM families f
            LEFT JOIN users u ON u.id = f.head_user_id AND u.role IN ('admin', 'member')
            WHERE u.id IS NULL";
        $stmt = Database::pdo()->query($sql);

        return (int) $stmt->fetchColumn();
    }

    public function create(int $organizationId, int $headUserId, ?int $createdBy): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO families (organization_id, head_user_id, created_by) VALUES (?, ?, ?)'
        );
        $stmt->execute([$organizationId, $headUserId, $createdBy]);
        return (int) Database::pdo()->lastInsertId();
    }

    public function findIdByOrganizationAndHead(int $organizationId, int $headUserId): ?int
    {
        if ($organizationId < 1 || $headUserId < 1) {
            return null;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT MIN(id) FROM families WHERE organization_id = ? AND head_user_id = ?'
        );
        $stmt->execute([$organizationId, $headUserId]);
        $v = $stmt->fetchColumn();

        return $v !== false ? (int) $v : null;
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM families WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Lowest families.id for this head (canonical household row). */
    public function canonicalFamilyIdForHeadUserId(int $headUserId): ?int
    {
        if ($headUserId < 1) {
            return null;
        }
        $stmt = Database::pdo()->prepare('SELECT MIN(id) FROM families WHERE head_user_id = ?');
        $stmt->execute([$headUserId]);
        $v = $stmt->fetchColumn();

        return $v !== false ? (int) $v : null;
    }

    /**
     * True when this family's designated head is a member of the organization
     * (the same household is visible in every org the head belongs to).
     */
    public function familyIsAnchoredInOrganization(int $familyId, int $organizationId): bool
    {
        if ($familyId < 1 || $organizationId < 1) {
            return false;
        }
        $family = $this->findById($familyId);
        if ($family === null) {
            return false;
        }
        $headId = (int) $family['head_user_id'];
        if ($headId < 1) {
            return false;
        }

        return (int) ($family['organization_id'] ?? 0) === $organizationId
            && (new Organization())->userIsMember($headId, $organizationId);
    }

    /** @return list<array<string,mixed>> */
    public function listByOrganization(int $organizationId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT f.*, u.name AS head_name FROM families f
            ' . self::sqlJoinCanonicalHousehold() . '
            LEFT JOIN users u ON u.id = f.head_user_id
            INNER JOIN users hu_mem ON hu_mem.id = f.head_user_id AND hu_mem.role IN (\'admin\', \'member\')
            WHERE f.organization_id = ?
            ORDER BY f.id DESC'
        );
        $stmt->execute([$organizationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string,mixed>> */
    public function listFamiliesForUserInOrganization(int $userId, int $organizationId): array
    {
        $this->ensureHeadMembershipsForDesignatedHeadInOrganization($userId, $organizationId);
        $sql = 'SELECT f.id AS family_id, f.head_user_id, hu.name AS head_name,
            COALESCE(MAX(fm.role), IF(f.head_user_id = ?, \'head\', NULL)) AS my_role
            FROM families f
            ' . self::sqlJoinCanonicalHousehold() . '
            LEFT JOIN users hu ON hu.id = f.head_user_id
            LEFT JOIN family_members fm ON fm.user_id = ?
            AND EXISTS (
                SELECT 1 FROM families fh WHERE fh.id = fm.family_id AND fh.head_user_id = f.head_user_id
            )
            WHERE f.organization_id = ?
            AND (fm.id IS NOT NULL OR f.head_user_id = ?)
            GROUP BY f.id, f.head_user_id, hu.name
            ORDER BY f.id DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$userId, $userId, $organizationId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string,mixed>> */
    public function listByOrganizationForMember(int $userId, int $organizationId): array
    {
        $this->ensureHeadMembershipsForDesignatedHeadInOrganization($userId, $organizationId);
        $sql = 'SELECT f.id, f.organization_id, f.head_user_id, f.created_by, f.created_at, hu.name AS head_name,
            COALESCE(MAX(fm.role), IF(f.head_user_id = ?, \'head\', NULL)) AS my_role
            FROM families f
            ' . self::sqlJoinCanonicalHousehold() . '
            LEFT JOIN users hu ON hu.id = f.head_user_id
            LEFT JOIN family_members fm ON fm.user_id = ?
            AND EXISTS (
                SELECT 1 FROM families fh WHERE fh.id = fm.family_id AND fh.head_user_id = f.head_user_id
            )
            LEFT JOIN family_relationship_links frl ON frl.family_id = f.id AND frl.user_id = ?
            WHERE f.organization_id = ?
            AND (fm.id IS NOT NULL OR f.head_user_id = ? OR frl.id IS NOT NULL)
            GROUP BY f.id, f.organization_id, f.head_user_id, f.created_by, f.created_at, hu.name
            ORDER BY f.id DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$userId, $userId, $userId, $organizationId, $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $ownHeadRows = array_values(
            array_filter(
                $rows,
                static function (array $row) use ($userId): bool {
                    return (int) ($row['head_user_id'] ?? 0) === $userId;
                }
            )
        );
        if ($ownHeadRows !== []) {
            return $ownHeadRows;
        }

        return $rows;
    }

    /** @return list<array<string,mixed>> */
    public function membersWithUsers(int $familyId): array
    {
        $family = $this->findById($familyId);
        if ($family === null) {
            return [];
        }
        $hid = (int) $family['head_user_id'];
        $sql = 'SELECT fm1.*, u.name AS user_name, u.first_name, u.middle_name, u.last_name,
            u.email, u.phone, u.role AS user_role, u.is_active AS user_is_active, up.dob, up.blood_group,
            ru.name AS related_user_name
            FROM family_members fm1
            INNER JOIN (
                SELECT fm.user_id, MIN(fm.id) AS pick_id
                FROM family_members fm
                INNER JOIN families f ON f.id = fm.family_id
                WHERE f.head_user_id = ?
                GROUP BY fm.user_id
            ) pick ON pick.pick_id = fm1.id
            INNER JOIN users u ON u.id = fm1.user_id
            LEFT JOIN user_profiles up ON up.user_id = u.id
            LEFT JOIN users ru ON ru.id = fm1.related_to_user_id
            ORDER BY fm1.id ASC';
        $stmt = Database::pdo()->prepare($sql);
        try {
            $stmt->execute([$hid]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // Fallback if is_active column not yet present.
            $sqlFallback = 'SELECT fm1.*, u.name AS user_name, u.first_name, u.middle_name, u.last_name,
                u.email, u.phone, u.role AS user_role, 1 AS user_is_active, up.dob, up.blood_group,
                ru.name AS related_user_name
                FROM family_members fm1
                INNER JOIN (
                    SELECT fm.user_id, MIN(fm.id) AS pick_id
                    FROM family_members fm
                    INNER JOIN families f ON f.id = fm.family_id
                    WHERE f.head_user_id = ?
                    GROUP BY fm.user_id
                ) pick ON pick.pick_id = fm1.id
                INNER JOIN users u ON u.id = fm1.user_id
                LEFT JOIN user_profiles up ON up.user_id = u.id
                LEFT JOIN users ru ON ru.id = fm1.related_to_user_id
                ORDER BY fm1.id ASC';
            $stmt = Database::pdo()->prepare($sqlFallback);
            $stmt->execute([$hid]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $links = (new FamilyRelationshipLink())->listByFamilyId($familyId);
        $seen = [];
        foreach ($rows as $r) {
            $uid = (int) ($r['user_id'] ?? 0);
            if ($uid > 0) {
                $seen[$uid] = true;
            }
        }
        foreach ($links as $l) {
            $uid = (int) ($l['user_id'] ?? 0);
            if ($uid < 1 || isset($seen[$uid])) {
                continue;
            }
            $rows[] = [
                'user_id' => $uid,
                'user_name' => (string) ($l['user_name'] ?? ''),
                'email' => $l['email'] ?? null,
                'phone' => $l['phone'] ?? null,
                'role' => (string) ($l['relationship_role'] ?? ''),
                'related_to_user_id' => $l['related_to_user_id'] ?? null,
                'related_user_name' => (string) ($l['related_user_name'] ?? ''),
                'dob' => (string) ($l['dob'] ?? ''),
                'is_linked' => 1,
            ];
            $seen[$uid] = true;
        }

        return $rows;
    }

    /** Login members in this household (head + family members). Dependents without accounts are excluded. */
    public function householdMemberCount(int $familyId): int
    {
        $members = $this->membersWithUsers($familyId);
        $count = count($members);

        return max(1, $count);
    }

    public function addMember(int $familyId, int $userId, string $role, ?int $relatedToUserId): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO family_members (family_id, user_id, role, related_to_user_id) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$familyId, $userId, $role, $relatedToUserId]);
        $this->syncHeadUserIdFromMembers($familyId);
    }

    /** @return list<array<string,mixed>> */
    public function listDirectMembers(int $familyId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT fm.*, u.name AS user_name, u.email, u.phone
            FROM family_members fm
            INNER JOIN users u ON u.id = fm.user_id
            WHERE fm.family_id = ?
            ORDER BY fm.id ASC'
        );
        $stmt->execute([$familyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function upsertMember(int $familyId, int $userId, string $role, ?int $relatedToUserId): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO family_members (family_id, user_id, role, related_to_user_id)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE role = VALUES(role), related_to_user_id = VALUES(related_to_user_id)'
        );
        $stmt->execute([$familyId, $userId, $role, $relatedToUserId]);
        $this->syncHeadUserIdFromMembers($familyId);
    }

    public function syncHeadUserIdFromMembers(int $familyId): void
    {
        $hid = $this->headMembershipUserId($familyId);
        if ($hid === null) {
            return;
        }
        $stmt = Database::pdo()->prepare('UPDATE families SET head_user_id = ? WHERE id = ?');
        $stmt->execute([$hid, $familyId]);
    }

    public function ensureHeadMembershipForDesignatedHead(int $familyId): void
    {
        $family = $this->findById($familyId);
        if ($family === null) {
            return;
        }
        $hid = (int) $family['head_user_id'];
        if ($hid < 1 || $this->userIsFamilyMember($familyId, $hid)) {
            return;
        }
        $stmt = Database::pdo()->prepare(
            'INSERT INTO family_members (family_id, user_id, role, related_to_user_id) VALUES (?, ?, ?, NULL)'
        );
        $stmt->execute([$familyId, $hid, 'head']);
        $this->syncHeadUserIdFromMembers($familyId);
    }

    public function normalizeHeadRolesForHousehold(int $familyId): void
    {
        $family = $this->findById($familyId);
        if ($family === null) {
            return;
        }
        $designatedHeadUserId = (int) ($family['head_user_id'] ?? 0);
        if ($designatedHeadUserId < 1) {
            return;
        }

        // Keep only designated head as role=head across this household.
        $stmt = Database::pdo()->prepare(
            'UPDATE family_members fm
            INNER JOIN families f ON f.id = fm.family_id
            SET fm.role = ?, fm.related_to_user_id = NULL
            WHERE f.head_user_id = ?
            AND LOWER(fm.role) = ?
            AND fm.user_id <> ?'
        );
        $stmt->execute(['other', $designatedHeadUserId, 'head', $designatedHeadUserId]);

        $this->ensureHeadMembershipForDesignatedHead($familyId);
    }

    public function ensureHeadMembershipsForDesignatedHeadInOrganization(int $userId, int $organizationId): void
    {
        if ($userId < 1 || $organizationId < 1) {
            return;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT f.id FROM families f
            ' . self::sqlJoinCanonicalHousehold() . '
            WHERE f.organization_id = ? AND f.head_user_id = ?'
        );
        $stmt->execute([$organizationId, $userId]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $fid) {
            $this->ensureHeadMembershipForDesignatedHead((int) $fid);
        }
    }

    public function userIsHead(int $userId, int $familyId): bool
    {
        $f = $this->findById($familyId);
        if ($f === null) {
            return false;
        }
        $hid = (int) $f['head_user_id'];
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM family_members fm
            INNER JOIN families fam ON fam.id = fm.family_id
            WHERE fam.head_user_id = ? AND fm.user_id = ? AND LOWER(fm.role) = ?
            LIMIT 1'
        );
        $stmt->execute([$hid, $userId, 'head']);
        return (bool) $stmt->fetchColumn();
    }

    public function headUserId(int $familyId): ?int
    {
        $f = $this->findById($familyId);
        return $f ? (int) $f['head_user_id'] : null;
    }

    public function isDesignatedHead(int $userId, int $familyId): bool
    {
        $f = $this->findById($familyId);
        return $f !== null && (int) $f['head_user_id'] === $userId;
    }

    public function userIsFamilyHeadInAnyOrganization(int $userId): bool
    {
        return $this->organizationIdsWhereUserIsFamilyHead($userId) !== [];
    }

    /** @return list<int> */
    public function organizationIdsWhereUserIsFamilyHead(int $userId): array
    {
        if ($userId < 1) {
            return [];
        }
        $sql = 'SELECT DISTINCT f.organization_id
            FROM families f
            WHERE f.head_user_id = ?
            UNION
            SELECT DISTINCT f.organization_id
            FROM family_members fm
            INNER JOIN families f ON f.id = fm.family_id
            WHERE fm.user_id = ? AND LOWER(fm.role) = ?';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$userId, $userId, 'head']);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }

    public function userIsFamilyMember(int $familyId, int $userId): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM family_members WHERE family_id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$familyId, $userId]);
        return (bool) $stmt->fetchColumn();
    }

    /** Membership row for this user on any family row with the same designated head (canonical family id). */
    public function getHouseholdMembership(int $canonicalFamilyId, int $userId): ?array
    {
        $family = $this->findById($canonicalFamilyId);
        if ($family === null) {
            return null;
        }
        $hid = (int) $family['head_user_id'];
        $stmt = Database::pdo()->prepare(
            'SELECT fm.* FROM family_members fm
            INNER JOIN families f ON f.id = fm.family_id
            WHERE f.head_user_id = ? AND fm.user_id = ?
            ORDER BY (CASE WHEN fm.family_id = ? THEN 0 ELSE 1 END), fm.id ASC
            LIMIT 1'
        );
        $stmt->execute([$hid, $userId, $canonicalFamilyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function userIsInHousehold(int $canonicalFamilyId, int $userId): bool
    {
        $family = $this->findById($canonicalFamilyId);
        if ($family === null) {
            return false;
        }
        $hid = (int) $family['head_user_id'];
        if ($userId === $hid) {
            return true;
        }

        return $this->getHouseholdMembership($canonicalFamilyId, $userId) !== null;
    }

    public function userBelongsToOrganizationFamily(int $userId, int $organizationId): bool
    {
        if ($userId < 1 || $organizationId < 1) {
            return false;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM family_members fm
             INNER JOIN families f ON f.id = fm.family_id
             WHERE f.organization_id = ? AND fm.user_id = ?
             LIMIT 1'
        );
        $stmt->execute([$organizationId, $userId]);
        if ($stmt->fetchColumn() !== false) {
            return true;
        }
        $headStmt = Database::pdo()->prepare(
            'SELECT 1 FROM families WHERE organization_id = ? AND head_user_id = ? LIMIT 1'
        );
        $headStmt->execute([$organizationId, $userId]);

        return $headStmt->fetchColumn() !== false;
    }

    public function updateMemberAcrossHousehold(int $headUserId, int $userId, string $role, ?int $relatedToUserId): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE family_members fm
            INNER JOIN families f ON f.id = fm.family_id
            SET fm.role = ?, fm.related_to_user_id = ?
            WHERE f.head_user_id = ? AND fm.user_id = ?'
        );
        $stmt->execute([$role, $relatedToUserId, $headUserId, $userId]);
        $ids = Database::pdo()->prepare('SELECT id FROM families WHERE head_user_id = ?');
        $ids->execute([$headUserId]);
        while ($fid = $ids->fetchColumn()) {
            $this->syncHeadUserIdFromMembers((int) $fid);
        }
    }

    public function headMembershipUserIdForHousehold(int $canonicalFamilyId): ?int
    {
        $f = $this->findById($canonicalFamilyId);
        if ($f === null) {
            return null;
        }
        $hid = (int) $f['head_user_id'];
        $stmt = Database::pdo()->prepare(
            'SELECT fm.user_id FROM family_members fm
            INNER JOIN families fam ON fam.id = fm.family_id
            WHERE fam.head_user_id = ? AND LOWER(fm.role) = ?
            ORDER BY fm.id ASC LIMIT 1'
        );
        $stmt->execute([$hid, 'head']);
        $v = $stmt->fetchColumn();

        return $v !== false ? (int) $v : null;
    }

    public function householdHasHeadRoleMember(int $canonicalFamilyId): bool
    {
        $f = $this->findById($canonicalFamilyId);
        if ($f === null) {
            return false;
        }
        $hid = (int) $f['head_user_id'];
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM family_members fm
            INNER JOIN families fam ON fam.id = fm.family_id
            WHERE fam.head_user_id = ? AND LOWER(fm.role) = ? LIMIT 1'
        );
        $stmt->execute([$hid, 'head']);

        return (bool) $stmt->fetchColumn();
    }

    /** @param list<int> $userIds */
    public function removeMembersByUserIds(int $familyId, array $userIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $userIds)));
        if ($ids === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$familyId], $ids);
        $stmt = Database::pdo()->prepare(
            'DELETE FROM family_members
            WHERE family_id = ?
            AND user_id IN (' . $placeholders . ')'
        );
        $stmt->execute($params);
    }

    public function headMembershipUserId(int $familyId): ?int
    {
        $family = $this->findById($familyId);
        if ($family === null) {
            return null;
        }
        $designated = (int) $family['head_user_id'];
        if ($designated > 0) {
            $stmt = Database::pdo()->prepare(
                'SELECT 1 FROM family_members WHERE family_id = ? AND user_id = ? AND LOWER(role) = ? LIMIT 1'
            );
            $stmt->execute([$familyId, $designated, 'head']);
            if ($stmt->fetchColumn()) {
                return $designated;
            }
        }
        $stmt = Database::pdo()->prepare(
            'SELECT user_id FROM family_members WHERE family_id = ? AND LOWER(role) = ? ORDER BY id ASC LIMIT 1'
        );
        $stmt->execute([$familyId, 'head']);
        $v = $stmt->fetchColumn();
        return $v !== false ? (int) $v : null;
    }

    /** @return string|null */
    public function validateRelationshipChange(int $familyId, int $targetUserId, string $role, ?int $relatedToUserId): ?string
    {
        $existing = $this->getHouseholdMembership($familyId, $targetUserId);
        if ($existing === null) {
            return 'Not in this family.';
        }
        $existingRole = strtolower((string) $existing['role']);
        $roleLower = strtolower(trim($role));
        if ($roleLower === '') {
            return 'Role is required.';
        }
        if ($existingRole === 'head' && $roleLower !== 'head') {
            return 'Ask an org admin to change the head.';
        }
        if ($roleLower === 'head') {
            $designatedHeadUserId = (int) ($this->headUserId($familyId) ?? 0);
            if ($designatedHeadUserId > 0 && $designatedHeadUserId !== $targetUserId) {
                return 'This family already has a designated head.';
            }
            if ($relatedToUserId !== null && $relatedToUserId > 0) {
                return 'Head: leave “related to” empty.';
            }
            $currentHeadUser = $this->headMembershipUserIdForHousehold($familyId);
            if ($currentHeadUser !== null && $currentHeadUser !== $targetUserId) {
                return 'This family already has a head.';
            }
            return null;
        }
        if ($relatedToUserId === null || $relatedToUserId < 1) {
            return 'Pick who they are related to.';
        }
        if ($relatedToUserId === $targetUserId) {
            return 'A member cannot be related to themselves.';
        }
        if (!$this->userIsInHousehold($familyId, $relatedToUserId)) {
            return 'The related person must already belong to this family.';
        }
        return null;
    }

    public function familyAlreadyHasHeadMember(int $familyId): bool
    {
        return $this->householdHasHeadRoleMember($familyId);
    }

    /** @return string|null */
    public function validateMemberRoleUpdate(int $familyId, int $targetUserId, string $role, ?int $relatedToUserId): ?string
    {
        $roleLower = strtolower(trim($role));
        if ($roleLower === '') {
            return 'Role is required.';
        }
        if ($roleLower === 'head') {
            if ($relatedToUserId !== null && $relatedToUserId > 0) {
                return 'Head: leave “related to” empty.';
            }

            return null;
        }
        if ($relatedToUserId === null || $relatedToUserId < 1) {
            return 'Pick who they are related to.';
        }
        if ($relatedToUserId === $targetUserId) {
            return 'A member cannot be related to themselves.';
        }
        if (!$this->userIsFamilyMember($familyId, $relatedToUserId)) {
            return 'The related person must already belong to this family.';
        }

        return null;
    }

    /** @return string|null */
    public function validateAddMember(int $familyId, int $targetUserId, string $role, ?int $relatedToUserId): ?string
    {
        if ($this->userIsInHousehold($familyId, $targetUserId)) {
            return 'This person is already in this family.';
        }
        $roleLower = strtolower(trim($role));
        if ($roleLower === '') {
            return 'Role is required.';
        }
        if ($roleLower === 'head') {
            $designatedHeadUserId = (int) ($this->headUserId($familyId) ?? 0);
            if ($designatedHeadUserId > 0 && $designatedHeadUserId !== $targetUserId) {
                return 'This family already has a designated head.';
            }
            if ($relatedToUserId !== null && $relatedToUserId > 0) {
                return 'Head: leave “related to” empty.';
            }
            if ($this->familyAlreadyHasHeadMember($familyId)) {
                return 'This family already has a head.';
            }
            return null;
        }
        if ($relatedToUserId === null || $relatedToUserId < 1) {
            return 'Pick who they are related to.';
        }
        if ($relatedToUserId === $targetUserId) {
            return 'A member cannot be related to themselves.';
        }
        if (!$this->userIsFamilyMember($familyId, $relatedToUserId)) {
            return 'The related person must already belong to this family.';
        }
        return null;
    }
}
