<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class PlatformPanchangDay
{
    private static bool $tableEnsured = false;

    public function ensureTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }
        Database::pdo()->exec(
            'CREATE TABLE IF NOT EXISTS platform_panchang_days (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                gregorian_date DATE NOT NULL,
                weekday VARCHAR(16) NULL DEFAULT NULL,
                gujarati_month VARCHAR(32) NULL DEFAULT NULL,
                paksha VARCHAR(16) NULL DEFAULT NULL,
                tithi VARCHAR(64) NOT NULL,
                festival_notes TEXT NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_panchang_gregorian_date (gregorian_date),
                KEY idx_panchang_gregorian_date (gregorian_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        self::$tableEnsured = true;
    }

    public function countAll(): int
    {
        $this->ensureTable();

        return (int) Database::pdo()->query('SELECT COUNT(*) FROM platform_panchang_days')->fetchColumn();
    }

    /** @return array<string, array<string,mixed>> keyed by Y-m-d */
    public function mapForDateRange(string $rangeStart, string $rangeEnd): array
    {
        $this->ensureTable();
        $stmt = Database::pdo()->prepare(
            'SELECT gregorian_date, weekday, gujarati_month, paksha, tithi, festival_notes
             FROM platform_panchang_days
             WHERE gregorian_date >= ? AND gregorian_date <= ?
             ORDER BY gregorian_date ASC'
        );
        $stmt->execute([$rangeStart, $rangeEnd]);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $date = (string) ($row['gregorian_date'] ?? '');
            if ($date !== '') {
                $map[$date] = $row;
            }
        }

        return $map;
    }

    /** @return list<array<string,mixed>> */
    public function listFestivalsForDateRange(string $rangeStart, string $rangeEnd): array
    {
        $this->ensureTable();
        $stmt = Database::pdo()->prepare(
            'SELECT gregorian_date, weekday, gujarati_month, paksha, tithi, festival_notes
             FROM platform_panchang_days
             WHERE gregorian_date >= ? AND gregorian_date <= ?
               AND festival_notes IS NOT NULL AND TRIM(festival_notes) <> \'\'
             ORDER BY gregorian_date ASC'
        );
        $stmt->execute([$rangeStart, $rangeEnd]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param list<array{gregorian_date:string,weekday:?string,gujarati_month:?string,paksha:?string,tithi:string,festival_notes:?string}> $rows
     */
    public function upsertMany(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }
        $this->ensureTable();
        $stmt = Database::pdo()->prepare(
            'INSERT INTO platform_panchang_days (gregorian_date, weekday, gujarati_month, paksha, tithi, festival_notes)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                weekday = VALUES(weekday),
                gujarati_month = VALUES(gujarati_month),
                paksha = VALUES(paksha),
                tithi = VALUES(tithi),
                festival_notes = VALUES(festival_notes)'
        );
        $count = 0;
        foreach ($rows as $row) {
            $stmt->execute([
                $row['gregorian_date'],
                $row['weekday'],
                $row['gujarati_month'],
                $row['paksha'],
                $row['tithi'],
                $row['festival_notes'],
            ]);
            $count++;
        }

        return $count;
    }
}
