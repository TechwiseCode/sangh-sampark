<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\MembershipCodeService;
use PDO;

final class Organization
{
    public function updateName(int $organizationId, string $name): void
    {
        $stmt = Database::pdo()->prepare('UPDATE organizations SET name = ? WHERE id = ?');
        $stmt->execute([$name, $organizationId]);
    }

    public function updateDetails(
        int $organizationId,
        string $name,
        ?string $nickname,
        ?string $address,
        string $orgCode,
        ?string $memberInitials = null,
        ?string $mapsUrl = null
    ): void {
        $stmt = Database::pdo()->prepare(
            'UPDATE organizations SET name = ?, nickname = ?, address = ?, maps_url = ?, org_code = ?, member_initials = ? WHERE id = ?'
        );
        $stmt->execute([$name, $nickname, $address, $mapsUrl, $orgCode, $memberInitials, $organizationId]);
    }

    public function orgCodeIsTaken(string $orgCode, ?int $excludeOrganizationId = null): bool
    {
        $orgCode = strtoupper(trim($orgCode));
        if ($orgCode === '') {
            return false;
        }
        if ($excludeOrganizationId !== null && $excludeOrganizationId > 0) {
            $stmt = Database::pdo()->prepare(
                'SELECT 1 FROM organizations WHERE org_code = ? AND id <> ? LIMIT 1'
            );
            $stmt->execute([$orgCode, $excludeOrganizationId]);
        } else {
            $stmt = Database::pdo()->prepare('SELECT 1 FROM organizations WHERE org_code = ? LIMIT 1');
            $stmt->execute([$orgCode]);
        }

        return $stmt->fetchColumn() !== false;
    }

    public function create(
        string $name,
        ?int $createdBy,
        ?string $orgCode = null,
        ?string $nickname = null,
        ?string $address = null,
        ?string $memberInitials = null,
        ?string $mapsUrl = null
    ): int {
        $codes = new MembershipCodeService();
        if ($orgCode === null || $orgCode === '') {
            $orgCode = $codes->generateOrganizationCode($name);
        } else {
            $orgCode = strtoupper(trim($orgCode));
        }
        if ($memberInitials === null || $memberInitials === '') {
            $memberInitials = $codes->suggestMemberInitials($name, (string) ($nickname ?? ''));
        } else {
            $memberInitials = $codes->normalizeMemberInitials($memberInitials);
        }
        $createdById = null;
        if ($createdBy !== null && $createdBy > 0) {
            $chk = Database::pdo()->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
            $chk->execute([$createdBy]);
            if ($chk->fetchColumn() !== false) {
                $createdById = $createdBy;
            }
        }
        $stmt = Database::pdo()->prepare(
            'INSERT INTO organizations (name, nickname, address, maps_url, org_code, member_initials, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $nickname, $address, $mapsUrl, $orgCode, $memberInitials, $createdById]);
        $orgId = (int) Database::pdo()->lastInsertId();
        if ($memberInitials === null) {
            $codes->ensureOrganizationMemberInitials($orgId);
        }

        return $orgId;
    }

    public function countAll(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM organizations')->fetchColumn();
    }

    public function countCreatedSinceDays(int $days): int
    {
        $days = max(1, $days);
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM organizations WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'
        );
        $stmt->execute([$days]);

        return (int) $stmt->fetchColumn();
    }

    public function countWithoutAdmins(): int
    {
        $stmt = Database::pdo()->query(
            "SELECT COUNT(*) FROM organizations o
             WHERE NOT EXISTS (
               SELECT 1 FROM users u WHERE u.organization_id = o.id AND u.role = 'admin'
             )"
        );

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listAll(string $sort = 'name', string $dir = 'asc'): array
    {
        $this->ensureIsActiveColumn();
        $columns = [
            'id' => 'o.id',
            'code' => 'o.org_code',
            'name' => 'o.name',
            'nickname' => 'o.nickname',
            'created_by' => 'uc.name',
            'created_at' => 'o.created_at',
        ];
        $orderCol = $columns[$sort] ?? $columns['name'];
        $orderDir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';
        $sql = "SELECT o.*, uc.name AS created_by_name
            FROM organizations o
            LEFT JOIN users uc ON uc.id = o.created_by
            ORDER BY {$orderCol} {$orderDir}, o.id ASC";

        return Database::pdo()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string,mixed>> */
    public function listMembers(int $organizationId): array
    {
        $sql = 'SELECT u.id AS membership_id, u.organization_id, u.id AS user_id, u.role, u.member_code,
            CASE
              WHEN u.member_code IS NOT NULL AND o.org_code IS NOT NULL THEN CONCAT(o.org_code, \'-\', u.member_code)
              ELSE NULL
            END AS full_member_code,
            u.created_at AS member_since,
            u.name, u.email, u.phone
            FROM users u
            INNER JOIN organizations o ON o.id = u.organization_id
            WHERE u.organization_id = ? AND u.role = \'member\'
            ORDER BY u.name ASC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$organizationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Org members for admin directory with optional filters.
     *
     * @param array{heads_only?:bool,gender?:?string,profession?:?string,donation?:?string,age_ranges?:list<string>} $filters
     * @return list<array<string,mixed>>
     */
    public function listMembersDirectory(int $organizationId, array $filters = []): array
    {
        $headsOnly = !empty($filters['heads_only']);
        $gender = isset($filters['gender']) ? normalize_gender((string) $filters['gender']) : null;
        $profession = isset($filters['profession']) ? normalize_member_profession_filter((string) $filters['profession']) : null;
        $donation = isset($filters['donation']) ? normalize_member_donation_filter((string) $filters['donation']) : null;
        $ageRangeKeys = isset($filters['age_ranges']) && is_array($filters['age_ranges'])
            ? normalize_member_age_range_filters($filters['age_ranges'])
            : [];

        $donorExistsSql = 'EXISTS (
            SELECT 1 FROM donations d
            WHERE d.organization_id = ?
                AND d.donor_type = \'member\'
                AND d.user_id = u.id
                AND d.paid_amount IS NOT NULL
                AND d.paid_amount > 0
        )';

        $join = Family::sqlJoinCanonicalHousehold();
        $sql = "SELECT u.id AS user_id, u.name, u.first_name, u.middle_name, u.last_name, u.email, u.phone, u.role, u.member_code,
            CASE
              WHEN u.member_code IS NOT NULL AND o.org_code IS NOT NULL THEN CONCAT(o.org_code, '-', u.member_code)
              ELSE NULL
            END AS full_member_code,
            u.created_at AS member_since,
            u.preferred_locale,
            CASE WHEN head_info.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_family_head,
            COALESCE(head_info.family_id, mem.family_id) AS family_id,
            mem.family_role,
            up.gender AS profile_gender,
            up.dob AS profile_dob,
            up.profession_type AS profile_profession_type,
            up.occupation AS profile_occupation
            FROM users u
            INNER JOIN organizations o ON o.id = u.organization_id
            LEFT JOIN user_profiles up ON up.user_id = u.id
            LEFT JOIN (
                SELECT user_id, MIN(family_id) AS family_id FROM (
                    SELECT f.head_user_id AS user_id, f.id AS family_id
                    FROM families f
                    {$join}
                    WHERE f.organization_id = ?
                    UNION
                    SELECT fm.user_id, f.id AS family_id
                    FROM family_members fm
                    INNER JOIN families f ON f.id = fm.family_id
                    {$join}
                    WHERE f.organization_id = ? AND LOWER(fm.role) = 'head'
                ) heads GROUP BY user_id
            ) head_info ON head_info.user_id = u.id
            LEFT JOIN (
                SELECT fm.user_id, MIN(fm.family_id) AS family_id, MIN(fm.role) AS family_role
                FROM family_members fm
                INNER JOIN families f ON f.id = fm.family_id
                {$join}
                WHERE f.organization_id = ?
                GROUP BY fm.user_id
            ) mem ON mem.user_id = u.id
            WHERE u.organization_id = ? AND u.role = 'member'
            " . ($headsOnly ? 'AND head_info.user_id IS NOT NULL ' : '');
        $params = [$organizationId, $organizationId, $organizationId, $organizationId];
        if ($gender !== null) {
            $sql .= 'AND up.gender = ? ';
            $params[] = $gender;
        }
        if ($profession !== null) {
            $occupationLabel = occupation_from_profession_type($profession);
            $sql .= 'AND (up.profession_type = ? OR (up.profession_type IS NULL AND up.occupation = ?)) ';
            $params[] = $profession;
            $params[] = $occupationLabel;
        }
        if ($ageRangeKeys !== []) {
            $ageParts = [];
            foreach ($ageRangeKeys as $ageRangeKey) {
                $ageBounds = member_age_range_bounds($ageRangeKey);
                if ($ageBounds === null) {
                    continue;
                }
                $ageParts[] = '(TIMESTAMPDIFF(YEAR, up.dob, CURDATE()) >= ? AND TIMESTAMPDIFF(YEAR, up.dob, CURDATE()) <= ?)';
                $params[] = $ageBounds[0];
                $params[] = $ageBounds[1];
            }
            if ($ageParts !== []) {
                $sql .= 'AND up.dob IS NOT NULL AND (' . implode(' OR ', $ageParts) . ') ';
            }
        }
        if ($donation === 'donors') {
            $sql .= 'AND ' . $donorExistsSql . ' ';
            $params[] = $organizationId;
        } elseif ($donation === 'non_donors') {
            $sql .= 'AND NOT ' . $donorExistsSql . ' ';
            $params[] = $organizationId;
        }
        $sql .= 'ORDER BY is_family_head DESC, u.name ASC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Member recipients (id + phone) for broadcasts using directory filters.
     *
     * @param array{heads_only?:bool,gender?:?string,profession?:?string,donation?:?string,age_ranges?:list<string>} $filters
     * @return list<array{id:int,phone:string}>
     */
    public function listMemberRecipientRows(int $organizationId, array $filters = []): array
    {
        $rows = $this->listMembersDirectory($organizationId, $filters);
        $recipients = [];
        foreach ($rows as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $recipients[] = [
                'id' => $userId,
                'phone' => (string) ($row['phone'] ?? ''),
                'preferred_locale' => strtolower(trim((string) ($row['preferred_locale'] ?? ''))),
            ];
        }

        return $recipients;
    }

    /**
     * All org users who should receive system broadcasts (admins + members).
     *
     * @return list<array{id:int,phone:string,preferred_locale:string}>
     */
    public function listAllNotificationRecipientRows(int $organizationId): array
    {
        if ($organizationId < 1) {
            return [];
        }
        $stmt = Database::pdo()->prepare(
            "SELECT u.id, u.phone, u.preferred_locale
             FROM users u
             WHERE u.organization_id = ? AND u.role IN ('admin', 'member')
             ORDER BY u.name ASC"
        );
        $stmt->execute([$organizationId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $recipients = [];
        foreach ($rows as $row) {
            $userId = (int) ($row['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $recipients[] = [
                'id' => $userId,
                'phone' => (string) ($row['phone'] ?? ''),
                'preferred_locale' => strtolower(trim((string) ($row['preferred_locale'] ?? ''))),
            ];
        }

        return $recipients;
    }

    /**
     * Org members eligible for the committee list (members only — not admins).
     *
     * @return list<array{user_id:int,name:string,phone:string,email:string,member_code:?string,full_member_code:?string,photo_path:?string}>
     */
    public function listUsersForCommitteePicker(int $organizationId): array
    {
        if ($organizationId < 1) {
            return [];
        }
        $stmt = Database::pdo()->prepare(
            "SELECT u.id AS user_id, u.name, u.phone, u.email, u.member_code,
                CASE
                  WHEN u.member_code IS NOT NULL AND o.org_code IS NOT NULL THEN CONCAT(o.org_code, '-', u.member_code)
                  ELSE NULL
                END AS full_member_code,
                u.photo_path
             FROM users u
             INNER JOIN organizations o ON o.id = u.organization_id
             WHERE u.organization_id = ? AND u.role = 'member'
             ORDER BY u.name ASC"
        );
        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findOrgUserForCommittee(int $organizationId, int $userId): ?array
    {
        if ($organizationId < 1 || $userId < 1) {
            return null;
        }
        $stmt = Database::pdo()->prepare(
            "SELECT u.id AS user_id, u.name, u.phone, u.email, u.member_code, u.photo_path
             FROM users u
             WHERE u.id = ? AND u.organization_id = ? AND u.role = 'member'
             LIMIT 1"
        );
        $stmt->execute([$userId, $organizationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /** @return list<array<string,mixed>> */
    public function listAdminUsers(int $organizationId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT u.id AS user_id, u.name, u.email, u.phone
             FROM users u
             WHERE u.organization_id = ? AND u.role = 'admin'
             ORDER BY u.name ASC"
        );
        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string,mixed>> */
    public function listAdminsAndFamilyHeads(int $organizationId): array
    {
        $sql = 'SELECT u.id AS user_id, u.name, u.first_name, u.middle_name, u.last_name, u.email, u.phone,
            MAX(CASE WHEN u.role = \'admin\' THEN 1 ELSE 0 END) AS is_org_admin,
            MAX(CASE WHEN head_ids.user_id IS NOT NULL THEN 1 ELSE 0 END) AS is_family_head,
            MAX(u.created_at) AS admin_since,
            MIN(head_fam.family_id) AS head_family_id
            FROM users u
            LEFT JOIN (
                SELECT DISTINCT f.head_user_id AS user_id FROM families f
                ' . Family::sqlJoinCanonicalHousehold() . '
                WHERE f.organization_id = ?
                UNION
                SELECT DISTINCT fm.user_id FROM family_members fm
                INNER JOIN families f ON f.id = fm.family_id
                ' . Family::sqlJoinCanonicalHousehold() . '
                WHERE f.organization_id = ? AND LOWER(fm.role) = \'head\'
            ) AS head_ids ON head_ids.user_id = u.id
            LEFT JOIN (
                SELECT f.id AS family_id, f.head_user_id AS user_id FROM families f
                ' . Family::sqlJoinCanonicalHousehold() . '
                WHERE f.organization_id = ?
            ) AS head_fam ON head_fam.user_id = u.id
            WHERE u.organization_id = ?
            AND (u.role = \'admin\' OR head_ids.user_id IS NOT NULL)
            GROUP BY u.id, u.name, u.first_name, u.middle_name, u.last_name, u.email, u.phone
            ORDER BY u.name ASC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$organizationId, $organizationId, $organizationId, $organizationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countFamilies(int $organizationId): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM families f
            ' . Family::sqlJoinCanonicalHousehold() . '
            WHERE f.organization_id = ?'
        );
        $stmt->execute([$organizationId]);
        return (int) $stmt->fetchColumn();
    }

    public function countMembers(int $organizationId): int
    {
        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM users
             WHERE organization_id = ? AND role = 'member'"
        );
        $stmt->execute([$organizationId]);

        return (int) $stmt->fetchColumn();
    }

    public function countSchemes(int $organizationId): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM schemes WHERE organization_id = ?'
        );
        $stmt->execute([$organizationId]);

        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        return \App\Services\RequestCache::remember('org:' . $id, function () use ($id): ?array {
            $this->ensureIsActiveColumn();
            $stmt = Database::pdo()->prepare('SELECT * FROM organizations WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        });
    }

    public function setActive(int $organizationId, bool $active): void
    {
        $this->ensureIsActiveColumn();
        $stmt = Database::pdo()->prepare('UPDATE organizations SET is_active = ? WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $organizationId]);
        \App\Services\RequestCache::forget('org:' . $organizationId);
        \App\Services\RequestCache::forget('org_contact:' . $organizationId);
    }

    public function isActive(int $organizationId): bool
    {
        $row = $this->findById($organizationId);

        return $row !== null && (!isset($row['is_active']) || (int) $row['is_active'] === 1);
    }

    private function ensureIsActiveColumn(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;
        try {
            $stmt = Database::pdo()->prepare("SHOW COLUMNS FROM organizations LIKE 'is_active'");
            $stmt->execute();
            if ($stmt->fetch(PDO::FETCH_ASSOC) !== false) {
                return;
            }
            Database::pdo()->exec(
                'ALTER TABLE organizations ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER org_code'
            );
        } catch (\PDOException $e) {
            // Column may already exist or DB user lacks ALTER.
        }
    }

    /**
     * Organization name and primary admin contact for member-facing display.
     *
     * @return array{
     *   name: string,
     *   nickname: ?string,
     *   address: ?string,
     *   admin_name: ?string,
     *   email: ?string,
     *   phone: ?string,
     *   phone_display: ?string
     * }|null
     */
    public function officialContactForDisplay(int $organizationId): ?array
    {
        return \App\Services\RequestCache::remember('org_contact:' . $organizationId, function () use ($organizationId): ?array {
            return $this->loadOfficialContactForDisplay($organizationId);
        });
    }

    /**
     * @return array{
     *   name: string,
     *   nickname: ?string,
     *   address: ?string,
     *   admin_name: ?string,
     *   email: ?string,
     *   phone: ?string,
     *   phone_display: ?string
     * }|null
     */
    private function loadOfficialContactForDisplay(int $organizationId): ?array
    {
        $org = $this->findById($organizationId);
        if ($org === null) {
            return null;
        }

        $admin = null;
        $createdBy = (int) ($org['created_by'] ?? 0);
        if ($createdBy > 0) {
            $createdByUser = (new User())->findById($createdBy);
            if (
                is_array($createdByUser)
                && (int) ($createdByUser['organization_id'] ?? 0) === $organizationId
                && ($createdByUser['role'] ?? '') === 'admin'
            ) {
                $admin = $createdByUser;
            }
        }
        if ($admin === null) {
            $admins = $this->listAdminUsers($organizationId);
            $admin = $admins[0] ?? null;
        }

        $email = $admin !== null ? normalize_email((string) ($admin['email'] ?? '')) : null;
        $phone = $admin !== null ? trim((string) ($admin['phone'] ?? '')) : '';
        $phoneDisplay = null;
        if ($phone !== '') {
            $formatted = format_india_phone($phone);
            $phoneDisplay = $formatted !== '—' ? $formatted : null;
        }

        return [
            'name' => trim((string) ($org['name'] ?? '')),
            'nickname' => trim((string) ($org['nickname'] ?? '')) ?: null,
            'address' => trim((string) ($org['address'] ?? '')) ?: null,
            'maps_url' => normalize_maps_url((string) ($org['maps_url'] ?? '')),
            'admin_name' => $admin !== null ? user_display_name($admin) : null,
            'email' => $email !== null && $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'phone_display' => $phoneDisplay,
        ];
    }

    public function findByOrgCode(string $orgCode): ?array
    {
        $orgCode = strtoupper(trim($orgCode));
        if ($orgCode === '') {
            return null;
        }
        $stmt = Database::pdo()->prepare('SELECT * FROM organizations WHERE org_code = ? LIMIT 1');
        $stmt->execute([$orgCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return list<array<string,mixed>> */
    public function listForUser(int $userId): array
    {
        return \App\Services\RequestCache::remember('org_list:' . $userId, function () use ($userId): array {
            $u = (new User())->findById($userId);
            if ($u === null || ($u['role'] ?? '') === 'superadmin') {
                return [];
            }
            $orgId = (int) ($u['organization_id'] ?? 0);
            if ($orgId < 1) {
                return [];
            }
            $org = $this->findById($orgId);
            if ($org === null) {
                return [];
            }
            $memberCode = $u['member_code'] ?? null;
            $orgCode = (string) ($org['org_code'] ?? '');
            $full = ($memberCode !== null && $memberCode !== '' && $orgCode !== '')
                ? $orgCode . '-' . $memberCode
                : null;

            return [array_merge($org, [
                'membership_role' => (string) ($u['role'] ?? 'member'),
                'member_code' => $memberCode,
                'full_member_code' => $full,
            ])];
        });
    }

    public function userHasRole(int $userId, int $organizationId, string $role): bool
    {
        $cacheKey = 'org_role:' . $userId . ':' . $organizationId . ':' . $role;

        return (bool) \App\Services\RequestCache::remember($cacheKey, function () use ($userId, $organizationId, $role): bool {
            $stmt = Database::pdo()->prepare(
                'SELECT 1 FROM users WHERE id = ? AND organization_id = ? AND role = ? LIMIT 1'
            );
            $stmt->execute([$userId, $organizationId, $role]);

            return (bool) $stmt->fetchColumn();
        });
    }

    public function userIsMember(int $userId, int $organizationId): bool
    {
        $cacheKey = 'org_member:' . $userId . ':' . $organizationId;

        return (bool) \App\Services\RequestCache::remember($cacheKey, function () use ($userId, $organizationId): bool {
            $stmt = Database::pdo()->prepare(
                "SELECT 1 FROM users WHERE id = ? AND organization_id = ?
                 AND role IN ('admin', 'member') LIMIT 1"
            );
            $stmt->execute([$userId, $organizationId]);

            return (bool) $stmt->fetchColumn();
        });
    }

    /** @return list<array<string,mixed>> */
    public function listOrganizationsWhereUserIsFamilyHead(int $userId): array
    {
        $ids = (new Family())->organizationIdsWhereUserIsFamilyHead($userId);
        if ($ids === []) {
            return [];
        }
        $allow = array_fill_keys($ids, true);
        $out = [];
        foreach ($this->listForUser($userId) as $o) {
            if (isset($allow[(int) $o['id']])) {
                $out[] = $o;
            }
        }

        return $out;
    }

    public function pinnedOrganizationIdForNonHead(int $userId, array $memberships): int
    {
        $allowed = [];
        foreach ($memberships as $o) {
            $allowed[(int) $o['id']] = true;
        }
        $u = (new User())->findById($userId);
        $homeId = $u !== null ? (int) ($u['organization_id'] ?? 0) : 0;
        if ($homeId > 0 && isset($allowed[$homeId])) {
            return $homeId;
        }

        return (int) $memberships[0]['id'];
    }

    public function addUser(int $organizationId, int $userId, string $role): void
    {
        $u = (new User())->findById($userId);
        if ($u === null) {
            return;
        }
        $existingOrg = (int) ($u['organization_id'] ?? 0);
        if ($existingOrg > 0 && $existingOrg !== $organizationId) {
            return;
        }
        $memberCode = $u['member_code'] ?? null;
        if ($role === 'member' && ($memberCode === null || $memberCode === '')) {
            $memberCode = $this->nextMemberCode($organizationId);
        }
        if ($role === 'admin') {
            $memberCode = $u['member_code'] ?? null;
        }
        $stmt = Database::pdo()->prepare(
            'UPDATE users SET organization_id = ?, role = ?, member_code = ? WHERE id = ?'
        );
        $stmt->execute([$organizationId, $role, $memberCode, $userId]);
    }

    public function nextMemberCode(int $organizationId): string
    {
        return (new MembershipCodeService())->generateMemberCode($organizationId);
    }

    public function findUserByIdentityInOrg(int $organizationId, string $identity): ?array
    {
        return (new User())->findByIdentity($identity, $organizationId);
    }

    /**
     * Members and family dependents with a birthday in the given calendar month.
     *
     * @return list<array{kind:string,id:int,name:string,dob:string,family_id:?int}>
     */
    public function listBirthdaysForCalendarMonth(int $organizationId, int $calendarMonth): array
    {
        if ($calendarMonth < 1 || $calendarMonth > 12) {
            return [];
        }

        $pdo = Database::pdo();
        $rows = [];

        $memberSql = "SELECT u.id AS id, u.name, up.dob,
            (SELECT MIN(f.id) FROM families f
             LEFT JOIN family_members fm ON fm.family_id = f.id AND fm.user_id = u.id
             WHERE f.organization_id = u.organization_id
             AND (f.head_user_id = u.id OR fm.id IS NOT NULL)
            ) AS family_id
            FROM users u
            INNER JOIN user_profiles up ON up.user_id = u.id
            WHERE u.organization_id = ?
            AND u.role IN ('admin', 'member')
            AND up.dob IS NOT NULL
            AND MONTH(up.dob) = ?
            ORDER BY DAY(up.dob), u.name ASC";
        $stmt = $pdo->prepare($memberSql);
        $stmt->execute([$organizationId, $calendarMonth]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                'kind' => 'member',
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'dob' => (string) ($row['dob'] ?? ''),
                'family_id' => isset($row['family_id']) && $row['family_id'] !== null
                    ? (int) $row['family_id']
                    : null,
            ];
        }

        $dependentSql = 'SELECT fd.id AS id, fd.name, fd.dob, f.id AS family_id
            FROM family_dependents fd
            INNER JOIN families f ON f.id = fd.family_id
            WHERE f.organization_id = ?
            AND fd.dob IS NOT NULL
            AND MONTH(fd.dob) = ?
            ORDER BY DAY(fd.dob), fd.name ASC';
        $stmt = $pdo->prepare($dependentSql);
        $stmt->execute([$organizationId, $calendarMonth]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                'kind' => 'dependent',
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'dob' => (string) ($row['dob'] ?? ''),
                'family_id' => (int) ($row['family_id'] ?? 0),
            ];
        }

        return $rows;
    }
}
