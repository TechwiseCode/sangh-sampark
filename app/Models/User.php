<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class User
{
    private const SELECT_COLUMNS = 'id, organization_id, name, first_name, middle_name, last_name, email, phone, password, must_change_password, is_active, role, member_code, photo_path, preferred_locale, created_at';

    private function selectColumns(): string
    {
        $this->ensurePhotoPathColumn();
        $this->ensureNamePartColumns();
        $this->ensurePreferredLocaleColumn();
        $this->ensureMustChangePasswordColumn();
        $this->ensureIsActiveColumn();

        return self::SELECT_COLUMNS;
    }

    public function countAll(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function countAdmins(): int
    {
        return (int) Database::pdo()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    }

    public function countMembers(): int
    {
        return (int) Database::pdo()->query("SELECT COUNT(*) FROM users WHERE role = 'member'")->fetchColumn();
    }

    /** @return list<array<string,mixed>> */
    public function listOrgAdmins(): array
    {
        $stmt = Database::pdo()->query(
            "SELECT id, organization_id, name, email, phone, role, created_at
             FROM users WHERE role = 'admin' ORDER BY name ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function normalizeRole(string $role): string
    {
        if ($role === 'superadmin' || $role === 'admin') {
            return $role;
        }

        return 'member';
    }

    public function countCreatedSinceDays(int $days): int
    {
        $days = max(1, $days);
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'
        );
        $stmt->execute([$days]);

        return (int) $stmt->fetchColumn();
    }

    public function countMembersCreatedSinceDays(int $days): int
    {
        $days = max(1, $days);
        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM users WHERE role = 'member' AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $stmt->execute([$days]);

        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        return \App\Services\RequestCache::remember('user:' . $id, function () use ($id): ?array {
            $this->ensurePhotoPathColumn();
            $stmt = Database::pdo()->prepare(
                'SELECT ' . $this->selectColumns() . ' FROM users WHERE id = ? LIMIT 1'
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        });
    }

    public function findByEmail(string $email, ?int $organizationId = null): ?array
    {
        $this->ensurePhotoPathColumn();
        $email = normalize_email($email) ?? '';
        if ($email === '') {
            return null;
        }
        if ($organizationId !== null) {
            $stmt = Database::pdo()->prepare(
                'SELECT ' . $this->selectColumns() . '
                 FROM users
                 WHERE organization_id = ? AND email IS NOT NULL AND LOWER(TRIM(email)) = LOWER(?)
                 LIMIT 1'
            );
            $stmt->execute([$organizationId, $email]);
        } else {
            $stmt = Database::pdo()->prepare(
                'SELECT ' . $this->selectColumns() . '
                 FROM users
                 WHERE organization_id IS NULL AND role = \'superadmin\'
                   AND email IS NOT NULL AND LOWER(TRIM(email)) = LOWER(?)
                 LIMIT 1'
            );
            $stmt->execute([$email]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Another org member/admin account with the same email (for password reuse / Switch).
     *
     * @return array<string,mixed>|null
     */
    public function findSiblingByEmail(string $email, ?int $excludeOrganizationId = null): ?array
    {
        $this->ensurePhotoPathColumn();
        $email = normalize_email($email) ?? '';
        if ($email === '') {
            return null;
        }
        $sql = 'SELECT ' . $this->selectColumns() . '
            FROM users
            WHERE organization_id IS NOT NULL
              AND role IN (\'admin\', \'member\')
              AND email IS NOT NULL AND LOWER(TRIM(email)) = LOWER(?)';
        $params = [$email];
        if ($excludeOrganizationId !== null && $excludeOrganizationId > 0) {
            $sql .= ' AND organization_id <> ?';
            $params[] = $excludeOrganizationId;
        }
        $sql .= ' ORDER BY id ASC LIMIT 1';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * All org memberships (separate user rows) that share this email.
     *
     * @return list<array<string,mixed>>
     */
    public function listMembershipsByEmail(string $email): array
    {
        $email = normalize_email($email) ?? '';
        if ($email === '') {
            return [];
        }
        $sql = "SELECT u.id AS user_id, u.organization_id, u.role AS membership_role, u.member_code,
                o.id, o.name, o.nickname, o.org_code, o.address,
                CASE
                  WHEN u.member_code IS NOT NULL AND o.org_code IS NOT NULL THEN CONCAT(o.org_code, '-', u.member_code)
                  ELSE NULL
                END AS full_member_code
            FROM users u
            INNER JOIN organizations o ON o.id = u.organization_id
            WHERE u.role IN ('admin', 'member')
              AND u.email IS NOT NULL AND LOWER(TRIM(u.email)) = LOWER(?)
            ORDER BY o.name ASC, u.id ASC";
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$email]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function emailIsRegistered(string $email, ?int $organizationId = null): bool
    {
        return $this->findByEmail($email, $organizationId) !== null;
    }

    public function findByPhone(string $phone, ?int $organizationId = null): ?array
    {
        $this->ensurePhotoPathColumn();
        if ($organizationId !== null) {
            $stmt = Database::pdo()->prepare(
                'SELECT ' . $this->selectColumns() . ' FROM users WHERE organization_id = ? AND phone = ? LIMIT 1'
            );
            $stmt->execute([$organizationId, $phone]);
        } else {
            $stmt = Database::pdo()->prepare(
                'SELECT ' . $this->selectColumns() . '
                 FROM users WHERE organization_id IS NULL AND role = \'superadmin\' AND phone = ? LIMIT 1'
            );
            $stmt->execute([$phone]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function phoneIsRegistered(string $raw, ?int $organizationId = null): bool
    {
        $digits = \normalize_phone($raw);
        if ($digits === null) {
            return false;
        }
        $variants = [$digits];
        if (strlen($digits) === 10) {
            $variants[] = '91' . $digits;
        }
        if (strlen($digits) === 12 && strpos($digits, '91') === 0) {
            $variants[] = substr($digits, 2);
        }
        foreach (array_unique($variants) as $v) {
            if ($this->findByPhone($v, $organizationId)) {
                return true;
            }
        }

        return false;
    }

    public function findByIdentity(string $identity, ?int $organizationId = null): ?array
    {
        $identity = normalize_identity($identity);
        if ($identity === '') {
            return null;
        }
        if (strpos($identity, '@') !== false) {
            return $this->findByEmail($identity, $organizationId);
        }
        $phone = \normalize_phone($identity);
        if ($phone !== null) {
            $row = $this->findByPhone($phone, $organizationId);
            if ($row !== null) {
                return $row;
            }
            if (strlen($phone) === 10) {
                $row = $this->findByPhone('91' . $phone, $organizationId);
                if ($row !== null) {
                    return $row;
                }
            }
            if (strlen($phone) === 12 && strpos($phone, '91') === 0) {
                $row = $this->findByPhone(substr($phone, 2), $organizationId);
                if ($row !== null) {
                    return $row;
                }
            }
        }

        return null;
    }

    public function create(array $data): int
    {
        $role = self::normalizeRole((string) ($data['role'] ?? 'member'));
        $organizationId = isset($data['organization_id']) ? (int) $data['organization_id'] : null;
        if ($role === 'superadmin') {
            $organizationId = null;
        } elseif ($organizationId === null || $organizationId < 1) {
            throw new \InvalidArgumentException('organization_id is required for org users');
        }
        $memberCode = $data['member_code'] ?? null;
        if ($role === 'member' && ($memberCode === null || $memberCode === '') && $organizationId !== null) {
            $memberCode = (new Organization())->nextMemberCode($organizationId);
        }
        if ($role !== 'member') {
            $memberCode = $role === 'admin' ? ($data['member_code'] ?? null) : null;
        }
        $this->ensureNamePartColumns();
        $this->ensureMustChangePasswordColumn();
        $nameParts = person_name_parts_from_fields($data);
        $fullName = build_person_full_name(
            $nameParts['first_name'],
            $nameParts['middle_name'],
            $nameParts['last_name']
        );
        if ($fullName === '') {
            $fullName = trim((string) ($data['name'] ?? ''));
        }
        $mustChangePassword = !empty($data['must_change_password']) ? 1 : 0;
        $stmt = Database::pdo()->prepare(
            'INSERT INTO users (organization_id, name, first_name, middle_name, last_name, email, phone, password, must_change_password, role, member_code)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $organizationId,
            $fullName,
            $nameParts['first_name'] !== '' ? $nameParts['first_name'] : null,
            $nameParts['middle_name'],
            $nameParts['last_name'] !== '' ? $nameParts['last_name'] : null,
            normalize_email(isset($data['email']) ? (string) $data['email'] : null),
            $data['phone'] ?? null,
            password_hash($data['password'], PASSWORD_BCRYPT),
            $mustChangePassword,
            $role,
            $memberCode,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function listMembersForDirectory(
        ?int $organizationId = null,
        ?string $roleFilter = null,
        string $sort = 'name',
        string $dir = 'asc'
    ): array {
        $roleClause = "u.role IN ('admin', 'member')";
        if ($roleFilter === 'admin') {
            $roleClause = "u.role = 'admin'";
        } elseif ($roleFilter === 'member') {
            $roleClause = "u.role = 'member'";
        }

        $columns = [
            'id' => 'u.id',
            'name' => 'u.name',
            'email' => 'u.email',
            'phone' => 'u.phone',
            'orgs' => 'o.name',
            'type' => "CASE WHEN u.role = 'admin' THEN 0 ELSE 1 END",
            'since' => 'u.created_at',
        ];
        $orderCol = $columns[$sort] ?? $columns['name'];
        $orderDir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';
        $orderSql = "ORDER BY {$orderCol} {$orderDir}, u.name ASC, u.id ASC";

        if ($organizationId !== null && $organizationId > 0) {
            $stmt = Database::pdo()->prepare(
                "SELECT u.id, u.organization_id, u.name, u.email, u.phone, u.role, u.member_code, u.created_at,
                    o.name AS organization_name
                 FROM users u
                 INNER JOIN organizations o ON o.id = u.organization_id
                 WHERE u.organization_id = ? AND {$roleClause}
                 {$orderSql}"
            );
            $stmt->execute([$organizationId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $sql = "SELECT u.id, u.organization_id, u.name, u.email, u.phone, u.role, u.member_code, u.created_at,
            o.name AS organization_name
            FROM users u
            INNER JOIN organizations o ON o.id = u.organization_id
            WHERE {$roleClause}
            {$orderSql}";

        return Database::pdo()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function toSessionArray(array $row): array
    {
        $displayName = user_display_name($row);

        return [
            'id' => (int) $row['id'],
            'organization_id' => isset($row['organization_id']) ? (int) $row['organization_id'] : null,
            'name' => $displayName,
            'first_name' => $row['first_name'] ?? null,
            'middle_name' => $row['middle_name'] ?? null,
            'last_name' => $row['last_name'] ?? null,
            'email' => $row['email'],
            'phone' => $row['phone'],
            'role' => (string) ($row['role'] ?? 'member'),
            'member_code' => $row['member_code'] ?? null,
            'photo_path' => isset($row['photo_path']) && $row['photo_path'] !== ''
                ? (string) $row['photo_path']
                : null,
            'must_change_password' => !empty($row['must_change_password']),
            'is_active' => !isset($row['is_active']) || (int) $row['is_active'] === 1,
        ];
    }

    public function updatePhotoPath(int $userId, ?string $photoPath): void
    {
        $this->ensurePhotoPathColumn();
        $stmt = Database::pdo()->prepare('UPDATE users SET photo_path = ? WHERE id = ?');
        $stmt->execute([$photoPath, $userId]);
    }

    public function updatePreferredLocale(int $userId, string $locale): void
    {
        $this->ensurePreferredLocaleColumn();
        $locale = strtolower(trim($locale));
        if (!isset(supported_locales()[$locale])) {
            $locale = 'en';
        }
        $stmt = Database::pdo()->prepare('UPDATE users SET preferred_locale = ? WHERE id = ?');
        $stmt->execute([$locale, $userId]);
    }

    private function ensurePreferredLocaleColumn(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;
        try {
            $stmt = Database::pdo()->prepare("SHOW COLUMNS FROM users LIKE 'preferred_locale'");
            $stmt->execute();
            if ($stmt->fetch(PDO::FETCH_ASSOC) !== false) {
                return;
            }
            Database::pdo()->exec(
                'ALTER TABLE users ADD COLUMN preferred_locale VARCHAR(5) NULL DEFAULT NULL AFTER photo_path'
            );
        } catch (\PDOException $e) {
            // Column may already exist or DB user lacks ALTER; reads still work if migration applied.
        }
    }

    private function ensurePhotoPathColumn(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;
        try {
            $stmt = Database::pdo()->prepare("SHOW COLUMNS FROM users LIKE 'photo_path'");
            $stmt->execute();
            if ($stmt->fetch(PDO::FETCH_ASSOC) !== false) {
                return;
            }
            Database::pdo()->exec(
                'ALTER TABLE users ADD COLUMN photo_path VARCHAR(255) NULL DEFAULT NULL AFTER member_code'
            );
        } catch (\PDOException $e) {
            // Column may already exist or DB user lacks ALTER; reads still work if migration applied.
        }
    }

    private function ensureMustChangePasswordColumn(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;
        try {
            $stmt = Database::pdo()->prepare("SHOW COLUMNS FROM users LIKE 'must_change_password'");
            $stmt->execute();
            if ($stmt->fetch(PDO::FETCH_ASSOC) !== false) {
                return;
            }
            Database::pdo()->exec(
                'ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password'
            );
        } catch (\PDOException $e) {
            // Column may already exist or DB user lacks ALTER; reads still work if migration applied.
        }
    }

    private function ensureIsActiveColumn(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;
        try {
            $stmt = Database::pdo()->prepare("SHOW COLUMNS FROM users LIKE 'is_active'");
            $stmt->execute();
            if ($stmt->fetch(PDO::FETCH_ASSOC) !== false) {
                return;
            }
            Database::pdo()->exec(
                'ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER must_change_password'
            );
        } catch (\PDOException $e) {
            // Column may already exist or DB user lacks ALTER.
        }
    }

    public function setActive(int $userId, bool $active): void
    {
        $this->ensureIsActiveColumn();
        $stmt = Database::pdo()->prepare('UPDATE users SET is_active = ? WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $userId]);
        \App\Services\RequestCache::forget('user:' . $userId);
    }

    public function isActive(int $userId): bool
    {
        $row = $this->findById($userId);

        return $row !== null && (!isset($row['is_active']) || (int) $row['is_active'] === 1);
    }

    public function updatePasswordHash(int $userId, string $passwordHash, bool $clearMustChangePassword = false): void
    {
        $this->ensureMustChangePasswordColumn();
        if ($clearMustChangePassword) {
            $stmt = Database::pdo()->prepare(
                'UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?'
            );
        } else {
            $stmt = Database::pdo()->prepare('UPDATE users SET password = ? WHERE id = ?');
        }
        $stmt->execute([$passwordHash, $userId]);
        \App\Services\RequestCache::forget('user:' . $userId);
    }

    public function setMustChangePassword(int $userId, bool $required): void
    {
        $this->ensureMustChangePasswordColumn();
        $stmt = Database::pdo()->prepare('UPDATE users SET must_change_password = ? WHERE id = ?');
        $stmt->execute([$required ? 1 : 0, $userId]);
        \App\Services\RequestCache::forget('user:' . $userId);
    }

    public function mustChangePassword(int $userId): bool
    {
        $row = $this->findById($userId);

        return $row !== null && !empty($row['must_change_password']);
    }

    /**
     * Copy password hash to every other admin/member row with the same email.
     */
    public function syncPasswordHashToEmailSiblings(int $sourceUserId, string $passwordHash, bool $clearMustChangePassword = false): int
    {
        $this->ensureMustChangePasswordColumn();
        $source = $this->findById($sourceUserId);
        if ($source === null) {
            return 0;
        }
        $email = normalize_email(isset($source['email']) ? (string) $source['email'] : null) ?? '';
        if ($email === '') {
            return 0;
        }
        if ($clearMustChangePassword) {
            $stmt = Database::pdo()->prepare(
                "UPDATE users
                 SET password = ?, must_change_password = 0
                 WHERE id <> ?
                   AND organization_id IS NOT NULL
                   AND role IN ('admin', 'member')
                   AND email IS NOT NULL AND LOWER(TRIM(email)) = LOWER(?)"
            );
        } else {
            $stmt = Database::pdo()->prepare(
                "UPDATE users
                 SET password = ?
                 WHERE id <> ?
                   AND organization_id IS NOT NULL
                   AND role IN ('admin', 'member')
                   AND email IS NOT NULL AND LOWER(TRIM(email)) = LOWER(?)"
            );
        }
        $stmt->execute([$passwordHash, $sourceUserId, $email]);

        return $stmt->rowCount();
    }

    public function updatePhone(int $userId, ?string $phone): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET phone = ? WHERE id = ?');
        $stmt->execute([$phone, $userId]);
    }

    public function updateBasicDetails(int $userId, string $name, ?string $email, ?string $phone): void
    {
        $split = split_person_full_name($name);
        $this->updatePersonDetails(
            $userId,
            $split['first_name'],
            $split['middle_name'],
            $split['last_name'],
            $email,
            $phone
        );
    }

    public function updatePersonDetails(
        int $userId,
        string $firstName,
        ?string $middleName,
        string $lastName,
        ?string $email,
        ?string $phone
    ): void {
        $this->ensureNamePartColumns();
        $fullName = build_person_full_name($firstName, $middleName, $lastName);
        $stmt = Database::pdo()->prepare(
            'UPDATE users SET name = ?, first_name = ?, middle_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?'
        );
        $stmt->execute([
            $fullName,
            $firstName,
            $middleName,
            $lastName,
            normalize_email($email),
            $phone,
            $userId,
        ]);
    }

    private function ensureNamePartColumns(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;
        try {
            $stmt = Database::pdo()->query("SHOW COLUMNS FROM users LIKE 'first_name'");
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                return;
            }
            Database::pdo()->exec(
                'ALTER TABLE users
                 ADD COLUMN first_name VARCHAR(100) NULL DEFAULT NULL AFTER name,
                 ADD COLUMN middle_name VARCHAR(100) NULL DEFAULT NULL AFTER first_name,
                 ADD COLUMN last_name VARCHAR(100) NULL DEFAULT NULL AFTER middle_name'
            );
        } catch (\PDOException $e) {
            // Column may already exist or DB user lacks ALTER; reads still work if migration applied.
        }
    }

    public function markEmailVerified(int $userId): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = ?');
        $stmt->execute([$userId]);
    }
}
