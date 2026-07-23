<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class FamilyDependent
{
    /** @return int */
    public function create(
        int $familyId,
        string $name,
        string $role,
        ?int $relatedToUserId,
        string $dob,
        string $pincode,
        string $city,
        string $state
    ): int {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO family_dependents
            (family_id, name, role, related_to_user_id, dob, pincode, city, state)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$familyId, $name, $role, $relatedToUserId, $dob, $pincode, $city, $state]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function listByFamilyId(int $familyId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT fd.*, ru.name AS related_user_name
            FROM family_dependents fd
            LEFT JOIN users ru ON ru.id = fd.related_to_user_id
            WHERE fd.family_id = ?
            ORDER BY fd.id ASC'
        );
        $stmt->execute([$familyId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param list<int> $relatedUserIds */
    public function moveByRelatedUsers(int $fromFamilyId, int $toFamilyId, array $relatedUserIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $relatedUserIds)));
        if ($ids === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$toFamilyId, $fromFamilyId], $ids);
        $stmt = Database::pdo()->prepare(
            'UPDATE family_dependents
            SET family_id = ?
            WHERE family_id = ?
            AND related_to_user_id IN (' . $placeholders . ')'
        );
        $stmt->execute($params);
    }
}

