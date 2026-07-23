<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

final class DonationCategory
{
    private static bool $tableEnsured = false;

    /** @return list<string> */
    public static function defaultNamesGu(): array
    {
        return [
            'શ્રી આયંબીલ કાયમી તિથી ફંડ',
            'શ્રી જૈન-શાળા જમણ કાયમી તિથી ફંડ',
            'શ્રી જૈન-શાળા પ્રભાવના કાયમી તિથી ફંડ',
            'શ્રી સાધુ-સાધ્વીજી વૈયા-વચ્ચ ફંડ',
            '(કૉપર્સ - ઉપજ ફંડ)',
            'શ્રી ધર્મકરણી ખાતે',
            'શ્રી સાધારણ / શુભ ખાતે',
            'શ્રી સ્વામી વાતસલ્ય ખાતે',
            'શ્રી સાધર્મિક ભક્તિ (મહેમાન રસોડા) ખાતે',
            'શ્રી જીવદયા ખાતે',
            'શ્રી માનવ રાહત ખાતે',
            'શ્રી શિબિર આયોજન ખાતે',
            'શ્રી શિક્ષણ / મેડીકલ સહાય ફંડ ખાતે',
            'શ્રી પ્રભાવના ખાતે',
            'શ્રી નિભાવ ફંડ ખાતે',
            'શ્રી અન્ય',
        ];
    }

    public function ensureTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }
        $pdo = Database::pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS donation_categories (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                organization_id INT UNSIGNED NOT NULL,
                name_gu VARCHAR(255) NOT NULL,
                sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                is_default TINYINT(1) NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_donation_cat_org (organization_id, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        try {
            $pdo->exec(
                'ALTER TABLE donation_categories ADD COLUMN is_default TINYINT(1) NOT NULL DEFAULT 0 AFTER sort_order'
            );
        } catch (PDOException $e) {
            // Column already exists.
        }
        $placeholders = implode(',', array_fill(0, count(self::defaultNamesGu()), '?'));
        $stmt = $pdo->prepare(
            'UPDATE donation_categories SET is_default = 1 WHERE name_gu IN (' . $placeholders . ') AND is_default = 0'
        );
        $stmt->execute(self::defaultNamesGu());
        self::$tableEnsured = true;
    }

    public function seedForOrganization(int $organizationId): void
    {
        $this->ensureTable();
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM donation_categories WHERE organization_id = ?'
        );
        $stmt->execute([$organizationId]);
        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }
        $insert = Database::pdo()->prepare(
            'INSERT INTO donation_categories (organization_id, name_gu, sort_order, is_default) VALUES (?, ?, ?, 1)'
        );
        $order = 1;
        foreach (self::defaultNamesGu() as $name) {
            $insert->execute([$organizationId, $name, $order]);
            $order++;
        }
    }

    /** @return list<array<string,mixed>> */
    public function listActive(int $organizationId): array
    {
        $this->seedForOrganization($organizationId);
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM donation_categories
            WHERE organization_id = ? AND is_active = 1
            ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string,mixed>> */
    public function listForManagement(int $organizationId): array
    {
        $this->seedForOrganization($organizationId);
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM donation_categories
            WHERE organization_id = ?
            ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByIdInOrganization(int $id, int $organizationId): ?array
    {
        $this->ensureTable();
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM donation_categories WHERE id = ? AND organization_id = ? LIMIT 1'
        );
        $stmt->execute([$id, $organizationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function nameExistsInOrganization(int $organizationId, string $nameGu, ?int $excludeId = null): bool
    {
        $this->ensureTable();
        $nameGu = trim($nameGu);
        if ($excludeId !== null && $excludeId > 0) {
            $stmt = Database::pdo()->prepare(
                'SELECT 1 FROM donation_categories WHERE organization_id = ? AND name_gu = ? AND id <> ? LIMIT 1'
            );
            $stmt->execute([$organizationId, $nameGu, $excludeId]);
        } else {
            $stmt = Database::pdo()->prepare(
                'SELECT 1 FROM donation_categories WHERE organization_id = ? AND name_gu = ? LIMIT 1'
            );
            $stmt->execute([$organizationId, $nameGu]);
        }

        return $stmt->fetchColumn() !== false;
    }

    public function createCustom(int $organizationId, string $nameGu): int
    {
        $this->seedForOrganization($organizationId);
        $nameGu = trim($nameGu);
        if ($nameGu === '') {
            throw new \InvalidArgumentException('Category name is required.');
        }
        if (mb_strlen($nameGu) > 255) {
            throw new \InvalidArgumentException('Category name is too long.');
        }
        if ($this->nameExistsInOrganization($organizationId, $nameGu)) {
            throw new \InvalidArgumentException('This category already exists.');
        }
        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) FROM donation_categories WHERE organization_id = ?'
        );
        $stmt->execute([$organizationId]);
        $sortOrder = (int) $stmt->fetchColumn() + 1;
        $insert = Database::pdo()->prepare(
            'INSERT INTO donation_categories (organization_id, name_gu, sort_order, is_default, is_active)
            VALUES (?, ?, ?, 0, 1)'
        );
        $insert->execute([$organizationId, $nameGu, $sortOrder]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function setActive(int $id, int $organizationId, bool $active): bool
    {
        $row = $this->findByIdInOrganization($id, $organizationId);
        if ($row === null) {
            return false;
        }
        $stmt = Database::pdo()->prepare(
            'UPDATE donation_categories SET is_active = ? WHERE id = ? AND organization_id = ?'
        );
        $stmt->execute([$active ? 1 : 0, $id, $organizationId]);

        return $stmt->rowCount() > 0;
    }

    public static function isDefaultRow(array $row): bool
    {
        return (int) ($row['is_default'] ?? 0) === 1;
    }
}
