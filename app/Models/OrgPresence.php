<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

final class OrgPresence
{
    private static bool $ensured = false;

    private function ensureTables(): void
    {
        if (self::$ensured) {
            return;
        }
        $pdo = Database::pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS org_presence_lists (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                organization_id INT UNSIGNED NOT NULL,
                effective_from DATETIME NOT NULL,
                effective_until DATETIME NULL DEFAULT NULL,
                created_by_user_id INT UNSIGNED NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_presence_org_current (organization_id, effective_until),
                KEY idx_presence_org_from (organization_id, effective_from),
                CONSTRAINT fk_presence_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE,
                CONSTRAINT fk_presence_user FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS org_presence_members (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                presence_list_id INT UNSIGNED NOT NULL,
                display_name VARCHAR(191) NOT NULL,
                sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                KEY idx_presence_members_list (presence_list_id, sort_order),
                CONSTRAINT fk_presence_members_list FOREIGN KEY (presence_list_id) REFERENCES org_presence_lists (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        self::$ensured = true;
    }

    /** @return list<string> */
    private static function normalizeNames(array $names): array
    {
        $out = [];
        foreach ($names as $name) {
            $n = trim((string) $name);
            if ($n !== '') {
                $out[] = $n;
            }
        }

        return $out;
    }

    /** @return array<string,mixed>|null */
    public function getCurrent(int $organizationId): ?array
    {
        $this->ensureTables();
        $stmt = Database::pdo()->prepare(
            'SELECT id, organization_id, effective_from, effective_until, created_by_user_id, created_at
             FROM org_presence_lists
             WHERE organization_id = ? AND effective_until IS NULL
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([$organizationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->attachMembers($row);
    }

    /**
     * Organizations that currently have presence members (for directory filter).
     *
     * @return list<array{id:int,name:string,nickname:?string,address:?string,org_code:?string,member_count:int}>
     */
    public function listOrganizationsWithCurrentPresence(): array
    {
        $this->ensureTables();
        $sql = 'SELECT o.id, o.name, o.nickname, o.address, o.maps_url, o.org_code,
                COUNT(m.id) AS member_count
            FROM organizations o
            INNER JOIN org_presence_lists l ON l.organization_id = o.id AND l.effective_until IS NULL
            INNER JOIN org_presence_members m ON m.presence_list_id = l.id
            GROUP BY o.id, o.name, o.nickname, o.address, o.maps_url, o.org_code
            HAVING member_count > 0
            ORDER BY o.name ASC';

        return Database::pdo()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Current presence names across organizations (optional name + sangh filter).
     *
     * @return list<array{
     *   display_name:string,
     *   organization_id:int,
     *   organization_name:string,
     *   organization_nickname:?string,
     *   organization_address:?string,
     *   org_code:?string
     * }>
     */
    public function listCurrentAcrossOrganizations(?string $search = null, ?int $organizationId = null): array
    {
        $this->ensureTables();
        $search = trim((string) $search);
        $organizationId = $organizationId !== null && $organizationId > 0 ? $organizationId : null;
        $sql = 'SELECT m.display_name, m.sort_order,
                o.id AS organization_id, o.name AS organization_name, o.nickname AS organization_nickname,
                o.address AS organization_address, o.maps_url AS organization_maps_url, o.org_code
            FROM org_presence_members m
            INNER JOIN org_presence_lists l ON l.id = m.presence_list_id
            INNER JOIN organizations o ON o.id = l.organization_id
            WHERE l.effective_until IS NULL';
        $params = [];
        if ($organizationId !== null) {
            $sql .= ' AND o.id = ?';
            $params[] = $organizationId;
        }
        if ($search !== '') {
            $sql .= ' AND m.display_name LIKE ?';
            $params[] = '%' . $search . '%';
        }
        if ($organizationId !== null) {
            $sql .= ' ORDER BY m.sort_order ASC, m.display_name ASC, m.id ASC';
        } else {
            $sql .= ' ORDER BY m.display_name ASC, o.name ASC, m.sort_order ASC, m.id ASC';
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listHistory(int $organizationId, int $limit = 12): array
    {
        $this->ensureTables();
        $limit = max(1, min(50, $limit));
        $stmt = Database::pdo()->prepare(
            'SELECT id, organization_id, effective_from, effective_until, created_by_user_id, created_at
             FROM org_presence_lists
             WHERE organization_id = ? AND effective_until IS NOT NULL
             ORDER BY effective_until DESC, id DESC
             LIMIT ' . $limit
        );
        $stmt->execute([$organizationId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows === []) {
            return [];
        }
        $listIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $rows);
        $membersByList = $this->loadMembersForLists($listIds);
        $out = [];
        foreach ($rows as $row) {
            $listId = (int) ($row['id'] ?? 0);
            $row['members'] = $membersByList[$listId] ?? [];
            $out[] = $row;
        }

        return $out;
    }

    /**
     * @param list<string> $names
     * @return bool true when list changed
     */
    public function replaceCurrent(int $organizationId, int $actorUserId, array $names): bool
    {
        $this->ensureTables();
        $names = self::normalizeNames($names);
        $current = $this->getCurrent($organizationId);
        $currentNames = [];
        if ($current !== null) {
            foreach ($current['members'] as $m) {
                $currentNames[] = (string) ($m['display_name'] ?? '');
            }
        }
        if ($currentNames === $names) {
            return false;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            if ($current !== null) {
                $close = $pdo->prepare(
                    'UPDATE org_presence_lists SET effective_until = NOW() WHERE id = ? AND effective_until IS NULL'
                );
                $close->execute([(int) $current['id']]);
            }
            if ($names !== []) {
                $ins = $pdo->prepare(
                    'INSERT INTO org_presence_lists (organization_id, effective_from, created_by_user_id)
                     VALUES (?, NOW(), ?)'
                );
                $ins->execute([$organizationId, $actorUserId > 0 ? $actorUserId : null]);
                $listId = (int) $pdo->lastInsertId();
                $memberStmt = $pdo->prepare(
                    'INSERT INTO org_presence_members (presence_list_id, display_name, sort_order) VALUES (?, ?, ?)'
                );
                foreach ($names as $i => $name) {
                    $memberStmt->execute([$listId, $name, $i]);
                }
            }
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }

        return true;
    }

    /** @param array<string,mixed> $row */
    private function attachMembers(array $row): array
    {
        $listId = (int) ($row['id'] ?? 0);
        $row['members'] = $this->loadMembersForLists([$listId])[$listId] ?? [];

        return $row;
    }

    /**
     * @param list<int> $listIds
     * @return array<int, list<array<string,mixed>>>
     */
    private function loadMembersForLists(array $listIds): array
    {
        $listIds = array_values(array_filter(array_map('intval', $listIds), static fn (int $id): bool => $id > 0));
        if ($listIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($listIds), '?'));
        $stmt = Database::pdo()->prepare(
            'SELECT presence_list_id, display_name, sort_order FROM org_presence_members
             WHERE presence_list_id IN (' . $placeholders . ')
             ORDER BY presence_list_id ASC, sort_order ASC, id ASC'
        );
        $stmt->execute($listIds);
        $membersByList = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $memberRow) {
            $listId = (int) ($memberRow['presence_list_id'] ?? 0);
            if ($listId < 1) {
                continue;
            }
            unset($memberRow['presence_list_id']);
            $membersByList[$listId][] = $memberRow;
        }

        return $membersByList;
    }
}
