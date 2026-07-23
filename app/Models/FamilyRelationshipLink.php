<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class FamilyRelationshipLink
{
    public function upsert(int $familyId, int $userId, string $relationshipRole, ?int $relatedToUserId): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO family_relationship_links (family_id, user_id, relationship_role, related_to_user_id)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE relationship_role = VALUES(relationship_role), related_to_user_id = VALUES(related_to_user_id)'
        );
        $stmt->execute([$familyId, $userId, $relationshipRole, $relatedToUserId]);
    }

    /** @return list<array<string,mixed>> */
    public function listByFamilyId(int $familyId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT l.*, u.name AS user_name, u.email, u.phone, up.dob,
                ru.name AS related_user_name
            FROM family_relationship_links l
            INNER JOIN users u ON u.id = l.user_id
            LEFT JOIN user_profiles up ON up.user_id = u.id
            LEFT JOIN users ru ON ru.id = l.related_to_user_id
            WHERE l.family_id = ?
            ORDER BY l.id ASC'
        );
        $stmt->execute([$familyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param list<int> $userIds
     *  @return list<array<string,mixed>> */
    public function listByFamilyAndUserIds(int $familyId, array $userIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $userIds)));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$familyId], $ids);
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM family_relationship_links
            WHERE family_id = ?
            AND user_id IN (' . $placeholders . ')'
        );
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param list<int> $userIds */
    public function deleteByFamilyAndUserIds(int $familyId, array $userIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $userIds)));
        if ($ids === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$familyId], $ids);
        $stmt = Database::pdo()->prepare(
            'DELETE FROM family_relationship_links
            WHERE family_id = ?
            AND user_id IN (' . $placeholders . ')'
        );
        $stmt->execute($params);
    }
}

