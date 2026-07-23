<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

final class UserProfile
{
    private ?bool $hasAddressLine2 = null;
    private ?bool $hasState = null;
    private ?bool $hasNativePincode = null;
    /** @var list<string>|null */
    private ?array $columnList = null;

    public function findByUserId(int $userId): ?array
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT * FROM user_profiles WHERE user_id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if ($this->isMissingTable($e)) {
                return null;
            }
            throw $e;
        }

        return $row ?: null;
    }

    /** @param array<string,mixed> $data */
    public function upsert(int $userId, array $data): void
    {
        try {
            $this->ensureStep2Columns();
            $this->ensureStep3Columns();
        $this->ensureGenderEnumIncludesOther();
        $this->ensureMaritalStatusEnum();
            $available = $this->availableColumns();
            $payload = ['user_id' => $userId];
            foreach ($data as $key => $value) {
                if (in_array($key, $available, true)) {
                    $payload[$key] = $value;
                }
            }
            if (!isset($payload['user_id']) || count($payload) < 2) {
                return;
            }
            $columns = array_keys($payload);
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $updates = [];
            foreach ($columns as $col) {
                if ($col === 'user_id') {
                    continue;
                }
                $updates[] = $col . ' = VALUES(' . $col . ')';
            }
            $sql = 'INSERT INTO user_profiles (' . implode(',', $columns) . ') VALUES (' . $placeholders . ')';
            if ($updates !== []) {
                $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
            }
            $stmt = Database::pdo()->prepare($sql);
            $stmt->execute(array_values($payload));
        } catch (PDOException $e) {
            if ($this->isMissingTable($e)) {
                return;
            }
            throw $e;
        }
    }

    /**
     * Copy profile row from one user to another (multi-org same-email reuse).
     * Does not overwrite an existing destination profile.
     */
    public function copyFromUser(int $fromUserId, int $toUserId): bool
    {
        if ($fromUserId < 1 || $toUserId < 1 || $fromUserId === $toUserId) {
            return false;
        }
        if ($this->findByUserId($toUserId) !== null) {
            return true;
        }
        $source = $this->findByUserId($fromUserId);
        if ($source === null) {
            return false;
        }
        $skip = ['user_id' => true, 'created_at' => true, 'updated_at' => true];
        $data = [];
        foreach ($source as $key => $value) {
            if (isset($skip[$key])) {
                continue;
            }
            $data[$key] = $value;
        }
        if ($data === []) {
            return false;
        }
        $this->upsert($toUserId, $data);

        return $this->findByUserId($toUserId) !== null;
    }

    public function isCompleteForUser(int $userId): bool
    {
        $u = (new User())->findById($userId);
        if ($u === null) {
            return false;
        }
        $phone = normalize_phone(isset($u['phone']) ? (string) $u['phone'] : null);
        if ($phone === null || strlen($phone) < 10) {
            return false;
        }
        $p = $this->findByUserId($userId);
        if ($p === null) {
            return false;
        }

        return trim((string) ($p['dob'] ?? '')) !== ''
            && trim((string) ($p['address_line1'] ?? '')) !== ''
            && trim((string) ($p['city'] ?? '')) !== ''
            && trim((string) ($p['state'] ?? '')) !== ''
            && trim((string) ($p['pincode'] ?? '')) !== ''
            && trim((string) ($p['occupation'] ?? '')) !== ''
            && \is_valid_blood_group(isset($p['blood_group']) ? (string) $p['blood_group'] : null)
            && \is_valid_gender(isset($p['gender']) ? (string) $p['gender'] : null)
            && \is_valid_marital_status(\profile_marital_status_from_row($p))
            && trim((string) ($p['area'] ?? '')) !== ''
            && trim((string) (($p['native_pincode'] ?? '') ?: '')) !== ''
            && trim((string) ($p['native_city'] ?? '')) !== ''
            && trim((string) ($p['native_state'] ?? '')) !== '';
    }

    private function isMissingTable(PDOException $e): bool
    {
        $msg = (string) $e->getMessage();

        return strpos($msg, 'Base table or view not found') !== false
            || strpos($msg, '1146') !== false
            || strpos($msg, "user_profiles") !== false;
    }

    /** @return list<string> */
    private function availableColumns(): array
    {
        if ($this->columnList !== null) {
            return $this->columnList;
        }
        try {
            $rows = Database::pdo()->query('SHOW COLUMNS FROM user_profiles')->fetchAll(PDO::FETCH_ASSOC);
            $list = [];
            foreach ($rows as $row) {
                $field = isset($row['Field']) ? (string) $row['Field'] : '';
                if ($field !== '') {
                    $list[] = $field;
                }
            }
            $this->columnList = $list;
        } catch (PDOException $e) {
            $this->columnList = [];
        }

        return $this->columnList;
    }

    private function ensureStep2Columns(): void
    {
        $toEnsure = [
            'blood_group' => 'VARCHAR(8) NULL DEFAULT NULL',
            'highest_education' => 'VARCHAR(191) NULL DEFAULT NULL',
            'profession_type' => "ENUM('job','business','homemaker','professional','student','retired') NULL DEFAULT NULL",
            'job_title' => 'VARCHAR(191) NULL DEFAULT NULL',
            'company_name' => 'VARCHAR(191) NULL DEFAULT NULL',
            'industry_sector' => 'VARCHAR(191) NULL DEFAULT NULL',
            'company_website' => 'VARCHAR(255) NULL DEFAULT NULL',
            'house_number' => 'VARCHAR(32) NULL DEFAULT NULL',
        ];
        foreach ($toEnsure as $column => $definition) {
            if ($this->hasColumn($column)) {
                continue;
            }
            try {
                Database::pdo()->exec('ALTER TABLE user_profiles ADD COLUMN ' . $column . ' ' . $definition);
                $this->columnList = null;
            } catch (PDOException $e) {
                $msg = (string) $e->getMessage();
                if (strpos($msg, 'Duplicate column name') === false && strpos($msg, '1060') === false) {
                    throw $e;
                }
            }
        }
    }

    private function ensureStep3Columns(): void
    {
        $toEnsure = [
            'gender' => "ENUM('Male','Female','Other') NULL DEFAULT NULL",
            'marital_status' => "ENUM('Single','Married','Widowed','Divorced') NULL DEFAULT NULL",
            'area' => 'VARCHAR(50) NULL DEFAULT NULL',
            'house_number' => 'VARCHAR(32) NULL DEFAULT NULL',
        ];
        foreach ($toEnsure as $column => $definition) {
            if ($this->hasColumn($column)) {
                continue;
            }
            try {
                Database::pdo()->exec('ALTER TABLE user_profiles ADD COLUMN ' . $column . ' ' . $definition);
                $this->columnList = null;
            } catch (PDOException $e) {
                $msg = (string) $e->getMessage();
                if (strpos($msg, 'Duplicate column name') === false && strpos($msg, '1060') === false) {
                    throw $e;
                }
            }
        }
        if ($this->hasColumn('marital_status') && $this->hasColumn('is_married')) {
            try {
                Database::pdo()->exec(
                    "UPDATE user_profiles SET marital_status = 'Married'
                     WHERE is_married = 1 AND (marital_status IS NULL OR marital_status = '')"
                );
                Database::pdo()->exec(
                    "UPDATE user_profiles SET marital_status = 'Single'
                     WHERE is_married = 0 AND (marital_status IS NULL OR marital_status = '')"
                );
            } catch (PDOException $e) {
                // Best-effort backfill for legacy rows.
            }
        }
    }

    private function ensureMaritalStatusEnum(): void
    {
        if (!$this->hasColumn('marital_status')) {
            return;
        }
        try {
            Database::pdo()->exec(
                "UPDATE user_profiles SET marital_status = 'Divorced' WHERE marital_status = 'Separated'"
            );
            Database::pdo()->exec(
                "ALTER TABLE user_profiles MODIFY COLUMN marital_status ENUM('Single','Married','Widowed','Divorced') NULL DEFAULT NULL"
            );
        } catch (PDOException $e) {
            // Best-effort schema alignment.
        }
    }

    private function ensureGenderEnumIncludesOther(): void
    {
        if (!$this->hasColumn('gender')) {
            return;
        }
        try {
            Database::pdo()->exec(
                "ALTER TABLE user_profiles MODIFY COLUMN gender ENUM('Male','Female','Other') NULL DEFAULT NULL"
            );
        } catch (PDOException $e) {
            // Best-effort: column may already allow Other.
        }
    }

    private function hasColumn(string $column): bool
    {
        try {
            $stmt = Database::pdo()->prepare("SHOW COLUMNS FROM user_profiles LIKE ?");
            $stmt->execute([$column]);

            return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    private function hasAddressLine2Column(): bool
    {
        if ($this->hasAddressLine2 !== null) {
            return $this->hasAddressLine2;
        }
        try {
            $stmt = Database::pdo()->query("SHOW COLUMNS FROM user_profiles LIKE 'address_line2'");
            $this->hasAddressLine2 = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->hasAddressLine2 = false;
        }

        return $this->hasAddressLine2;
    }

    private function hasStateColumn(): bool
    {
        if ($this->hasState !== null) {
            return $this->hasState;
        }
        try {
            $stmt = Database::pdo()->query("SHOW COLUMNS FROM user_profiles LIKE 'state'");
            $this->hasState = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->hasState = false;
        }

        return $this->hasState;
    }

    private function hasNativePincodeColumn(): bool
    {
        if ($this->hasNativePincode !== null) {
            return $this->hasNativePincode;
        }
        try {
            $stmt = Database::pdo()->query("SHOW COLUMNS FROM user_profiles LIKE 'native_pincode'");
            $this->hasNativePincode = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->hasNativePincode = false;
        }

        return $this->hasNativePincode;
    }
}

