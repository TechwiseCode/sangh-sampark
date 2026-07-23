<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class PlatformHoliday
{
    /**
     * @return list<array<string,mixed>>
     */
    public function listAll(string $sort = 'dates', string $dir = 'desc'): array
    {
        $columns = [
            'title' => 'h.title',
            'category' => 'h.category',
            'dates' => 'h.start_date',
            'notes' => 'h.notes',
        ];
        $orderCol = $columns[$sort] ?? $columns['dates'];
        $orderDir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';
        $sql = "SELECT h.*, u.name AS created_by_name
            FROM platform_holidays h
            LEFT JOIN users u ON u.id = h.created_by
            ORDER BY {$orderCol} {$orderDir}, h.title ASC";

        return Database::pdo()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string,mixed>> */
    public function listForCalendarRange(string $rangeStart, string $rangeEnd): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM platform_holidays
             WHERE end_date >= ? AND start_date <= ?
             ORDER BY start_date ASC, title ASC'
        );
        $stmt->execute([$rangeStart, $rangeEnd]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM platform_holidays WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /** @param array{title:string,title_gu:?string,category:string,start_date:string,end_date:string,notes:?string,created_by:?int} $data */
    public function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO platform_holidays (title, title_gu, category, start_date, end_date, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['title'],
            $data['title_gu'],
            $data['category'],
            $data['start_date'],
            $data['end_date'],
            $data['notes'],
            $data['created_by'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** @param array{title:string,title_gu:?string,category:string,start_date:string,end_date:string,notes:?string} $data */
    public function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE platform_holidays
             SET title = ?, title_gu = ?, category = ?, start_date = ?, end_date = ?, notes = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['title'],
            $data['title_gu'],
            $data['category'],
            $data['start_date'],
            $data['end_date'],
            $data['notes'],
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM platform_holidays WHERE id = ?');
        $stmt->execute([$id]);
    }
}
