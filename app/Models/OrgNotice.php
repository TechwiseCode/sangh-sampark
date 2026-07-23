<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

final class OrgNotice
{
    /** @return list<array<string,mixed>> */
    public function listForOrganization(int $organizationId, int $limit = 200, ?bool $activeOnly = null): array
    {
        try {
            return $this->fetchListForOrganization($organizationId, $limit, $activeOnly);
        } catch (PDOException $e) {
            if ($activeOnly !== null && $this->isMissingActiveColumn($e)) {
                return $this->fetchListForOrganization($organizationId, $limit, null);
            }
            if ($this->isMissingTable($e)) {
                return [];
            }
            throw $e;
        }
    }

    /** @return list<array<string,mixed>> */
    private function fetchListForOrganization(int $organizationId, int $limit, ?bool $activeOnly): array
    {
        $limit = max(1, min(500, $limit));
        $activeSql = '';
        if ($activeOnly === true) {
            $activeSql = ' AND n.is_active = 1';
        } elseif ($activeOnly === false) {
            $activeSql = ' AND n.is_active = 0';
        }
        $stmt = Database::pdo()->prepare(
            'SELECT n.*, u.name AS uploaded_by_name
             FROM org_notices n
             LEFT JOIN users u ON u.id = n.uploaded_by_user_id
             WHERE n.organization_id = ?' . $activeSql . '
             ORDER BY n.is_pinned DESC, n.created_at DESC, n.id DESC
             LIMIT ' . (int) $limit
        );
        $stmt->execute([$organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByIdInOrganization(int $id, int $organizationId): ?array
    {
        try {
            $stmt = Database::pdo()->prepare(
                'SELECT n.*, u.name AS uploaded_by_name
                 FROM org_notices n
                 LEFT JOIN users u ON u.id = n.uploaded_by_user_id
                 WHERE n.id = ? AND n.organization_id = ?
                 LIMIT 1'
            );
            $stmt->execute([$id, $organizationId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            if ($this->isMissingTable($e)) {
                return null;
            }
            throw $e;
        }
    }

    /** @param array{organization_id:int,title:string,description:?string,file_path:string,original_filename:string,mime_type:string,file_size_bytes:int,is_pinned:int,uploaded_by_user_id:?int} $data */
    public function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO org_notices
             (organization_id, title, description, file_path, original_filename, mime_type, file_size_bytes, is_pinned, uploaded_by_user_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['organization_id'],
            $data['title'],
            $data['description'],
            $data['file_path'],
            $data['original_filename'],
            $data['mime_type'],
            $data['file_size_bytes'],
            $data['is_pinned'],
            $data['uploaded_by_user_id'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function setPinned(int $id, int $organizationId, bool $pinned): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE org_notices SET is_pinned = ? WHERE id = ? AND organization_id = ?'
        );
        $stmt->execute([$pinned ? 1 : 0, $id, $organizationId]);
    }

    public function setActive(int $id, int $organizationId, bool $active): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE org_notices SET is_active = ? WHERE id = ? AND organization_id = ?'
        );
        $stmt->execute([$active ? 1 : 0, $id, $organizationId]);
    }

    public function delete(int $id, int $organizationId): ?string
    {
        $row = $this->findByIdInOrganization($id, $organizationId);
        if ($row === null) {
            return null;
        }
        $path = (string) ($row['file_path'] ?? '');
        $stmt = Database::pdo()->prepare('DELETE FROM org_notices WHERE id = ? AND organization_id = ?');
        $stmt->execute([$id, $organizationId]);

        return $path !== '' ? $path : null;
    }

    private function isMissingTable(PDOException $e): bool
    {
        $msg = (string) $e->getMessage();

        return str_contains($msg, 'org_notices')
            || str_contains($msg, 'Base table or view not found')
            || str_contains($msg, '1146');
    }

    private function isMissingActiveColumn(PDOException $e): bool
    {
        $msg = (string) $e->getMessage();

        return str_contains($msg, 'is_active')
            || str_contains($msg, '1054')
            || str_contains($msg, 'Unknown column');
    }
}
