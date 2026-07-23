<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class OrgCalendarDay
{
    private static bool $schemaEnsured = false;

    private function ensureSchema(): void
    {
        if (self::$schemaEnsured) {
            return;
        }
        $pdo = Database::pdo();
        try {
            $pdo->exec(
                "ALTER TABLE organization_calendar_days
                 MODIFY COLUMN category ENUM('holiday', 'paryushan', 'religious', 'other', 'vyakhyan', 'pratikraman') NOT NULL DEFAULT 'other'"
            );
        } catch (\PDOException $e) {
            // Column may already allow vyakhyan.
        }
        try {
            $pdo->exec(
                'ALTER TABLE organization_calendar_days ADD COLUMN event_time TIME NULL DEFAULT NULL AFTER end_date'
            );
        } catch (\PDOException $e) {
            // Column already exists.
        }
        self::$schemaEnsured = true;
    }

    /** @return list<array<string,mixed>> */
    public function listForOrganization(int $organizationId): array
    {
        $this->ensureSchema();
        $stmt = Database::pdo()->prepare(
            'SELECT d.*, u.name AS created_by_name
             FROM organization_calendar_days d
             LEFT JOIN users u ON u.id = d.created_by
             WHERE d.organization_id = ?
             ORDER BY d.start_date DESC, d.title ASC'
        );
        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string,mixed>> */
    public function listForCalendarRange(int $organizationId, string $rangeStart, string $rangeEnd): array
    {
        $this->ensureSchema();
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM organization_calendar_days
             WHERE organization_id = ? AND end_date >= ? AND start_date <= ?
             ORDER BY start_date ASC, title ASC'
        );
        $stmt->execute([$organizationId, $rangeStart, $rangeEnd]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return list<array<string,mixed>> */
    public function listStartingOn(int $organizationId, string $dateYmd): array
    {
        $this->ensureSchema();
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM organization_calendar_days
             WHERE organization_id = ? AND start_date = ?
             ORDER BY title ASC'
        );
        $stmt->execute([$organizationId, $dateYmd]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByIdInOrganization(int $id, int $organizationId): ?array
    {
        $this->ensureSchema();
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM organization_calendar_days WHERE id = ? AND organization_id = ? LIMIT 1'
        );
        $stmt->execute([$id, $organizationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /** @param array{organization_id:int,title:string,title_gu:?string,category:string,start_date:string,end_date:string,event_time:?string,notes:?string,created_by:?int} $data */
    public function create(array $data): int
    {
        $this->ensureSchema();
        $stmt = Database::pdo()->prepare(
            'INSERT INTO organization_calendar_days
             (organization_id, title, title_gu, category, start_date, end_date, event_time, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['organization_id'],
            $data['title'],
            $data['title_gu'],
            $data['category'],
            $data['start_date'],
            $data['end_date'],
            $data['event_time'],
            $data['notes'],
            $data['created_by'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** @param array{title:string,title_gu:?string,category:string,start_date:string,end_date:string,event_time:?string,notes:?string} $data */
    public function update(int $id, int $organizationId, array $data): void
    {
        $this->ensureSchema();
        $stmt = Database::pdo()->prepare(
            'UPDATE organization_calendar_days
             SET title = ?, title_gu = ?, category = ?, start_date = ?, end_date = ?, event_time = ?, notes = ?
             WHERE id = ? AND organization_id = ?'
        );
        $stmt->execute([
            $data['title'],
            $data['title_gu'],
            $data['category'],
            $data['start_date'],
            $data['end_date'],
            $data['event_time'],
            $data['notes'],
            $id,
            $organizationId,
        ]);
    }

    public function delete(int $id, int $organizationId): void
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM organization_calendar_days WHERE id = ? AND organization_id = ?'
        );
        $stmt->execute([$id, $organizationId]);
    }
}
