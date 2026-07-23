<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

use function organization_id;

final class FamilyMembershipRequest
{
    public function voidPendingForPair(int $familyId, int $targetUserId): void
    {
        $pdo = Database::pdo();
        $sel = $pdo->prepare(
            'SELECT id FROM family_membership_requests WHERE family_id = ? AND target_user_id = ? AND status = ?'
        );
        $sel->execute([$familyId, $targetUserId, 'pending']);
        $notif = new Notification();
        while ($rid = $sel->fetchColumn()) {
            $notif->markReadByReferenceForUser((int) $rid, 'relationship_request', $targetUserId);
        }
        $del = $pdo->prepare(
            'DELETE FROM family_membership_requests WHERE family_id = ? AND target_user_id = ? AND status = ?'
        );
        $del->execute([$familyId, $targetUserId, 'pending']);
    }

    /**
     * @return int new request id
     */
    public function createPending(
        int $familyId,
        int $targetUserId,
        int $requestedByUserId,
        string $requestedRole,
        ?int $relatedToUserId
    ): int {
        $this->voidPendingForPair($familyId, $targetUserId);
        $familyRow = (new Family())->findById($familyId);
        $organizationId = $familyRow !== null ? (int) $familyRow['organization_id'] : organization_id();
        $stmt = Database::pdo()->prepare(
            'INSERT INTO family_membership_requests
            (organization_id, family_id, target_user_id, requested_by_user_id, requested_role, related_to_user_id, status)
            VALUES (?, ?, ?, ?, ?, ?, \'pending\')'
        );
        $stmt->execute([
            $organizationId,
            $familyId,
            $targetUserId,
            $requestedByUserId,
            $requestedRole,
            $relatedToUserId,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM family_membership_requests WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Pending requests where this user must respond (they are the target).
     *
     * @return list<array<string, mixed>>
     */
    public function listPendingForTargetUser(int $userId): array
    {
        $sql = 'SELECT r.*, COALESCE(r.organization_id, f.organization_id) AS organization_id,
            o.name AS organization_name,
            u.name AS requester_name, fam.name AS target_name,
            ru.name AS related_user_name,
            CASE WHEN r.family_id IS NULL THEN 0
                WHEN fm.id IS NULL THEN 0 ELSE 1 END AS is_existing_family_member
            FROM family_membership_requests r
            LEFT JOIN families f ON f.id = r.family_id
            INNER JOIN organizations o ON o.id = COALESCE(r.organization_id, f.organization_id)
            INNER JOIN users u ON u.id = r.requested_by_user_id
            INNER JOIN users fam ON fam.id = r.target_user_id
            LEFT JOIN users ru ON ru.id = r.related_to_user_id
            LEFT JOIN family_members fm ON fm.family_id = r.family_id AND fm.user_id = r.target_user_id
            WHERE r.target_user_id = ? AND r.status = ?
            ORDER BY r.created_at DESC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$userId, 'pending']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markApproved(int $id, int $responderUserId): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE family_membership_requests SET status = ?, responded_at = CURRENT_TIMESTAMP, responded_by_user_id = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND status = ?'
        );
        $stmt->execute(['approved', $responderUserId, $id, 'pending']);
    }

    public function markRejected(int $id, int $responderUserId): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE family_membership_requests SET status = ?, responded_at = CURRENT_TIMESTAMP, responded_by_user_id = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND status = ?'
        );
        $stmt->execute(['rejected', $responderUserId, $id, 'pending']);
    }

    /**
     * @return string|null error message or null on success
     */
    public function approve(int $requestId, int $responderUserId): ?string
    {
        $req = $this->findById($requestId);
        if (!$req || ($req['status'] ?? '') !== 'pending') {
            return 'Not pending.';
        }
        if ((int) $req['target_user_id'] !== $responderUserId) {
            return 'Not for you.';
        }
        $targetUserId = (int) $req['target_user_id'];
        $role = (string) $req['requested_role'];
        $related = isset($req['related_to_user_id']) && $req['related_to_user_id'] !== null && $req['related_to_user_id'] !== ''
            ? (int) $req['related_to_user_id'] : null;

        $familyIdRaw = $req['family_id'] ?? null;
        if ($familyIdRaw === null || $familyIdRaw === '' || (int) $familyIdRaw < 1) {
            return $this->approveNewFamilyHeadInvite($req, $requestId, $responderUserId);
        }

        $familyId = (int) $familyIdRaw;
        $families = new Family();
        $pdo = Database::pdo();

        $familyRowProbe = $families->findById($familyId);
        if ($familyRowProbe === null) {
            return 'Family not found.';
        }
        $canon = $families->canonicalFamilyIdForHeadUserId((int) $familyRowProbe['head_user_id']);
        if ($canon !== null) {
            $familyId = $canon;
        }

        if ($families->userIsInHousehold($familyId, $targetUserId)) {
            $err = $families->validateRelationshipChange($familyId, $targetUserId, $role, $related);
            if ($err !== null) {
                return $err;
            }
            $headUserId = (int) ($families->findById($familyId)['head_user_id'] ?? 0);
            if ($headUserId < 1) {
                return 'Family not found.';
            }
            $pdo->beginTransaction();
            try {
                $families->updateMemberAcrossHousehold($headUserId, $targetUserId, $role, $related);
                $this->markApproved($requestId, $responderUserId);
                (new Notification())->markReadByReferenceForUser($requestId, 'relationship_request', $responderUserId);
                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }

            return null;
        }

        $familyRow = $families->findById($familyId);
        if ($familyRow === null) {
            return 'Family not found.';
        }
        $requesterHeadUserId = (int) ($familyRow['head_user_id'] ?? 0);
        $targetOwnFamilyId = $families->canonicalFamilyIdForHeadUserId($targetUserId);
        $crossHousehold = $targetOwnFamilyId !== null && $targetOwnFamilyId > 0 && $targetOwnFamilyId !== $familyId;
        if (!$crossHousehold) {
            $err = $families->validateAddMember($familyId, $targetUserId, $role, $related);
            if ($err !== null) {
                return $err;
            }
        }
        $orgId = (int) ($req['organization_id'] ?? 0);
        if ($orgId < 1) {
            $orgId = (int) $familyRow['organization_id'];
        }
        $pdo->beginTransaction();
        try {
            if ($crossHousehold) {
                // Cross-household relation: keep both households intact, only add link rows.
                (new FamilyRelationshipLink())->upsert($familyId, $targetUserId, $role, $related);
            } else {
                // Same household relation update/add.
                $families->addMember($familyId, $targetUserId, $role, $related);
            }

            // If target has own household and requester has a head household, mirror as relationship link.
            if ($requesterHeadUserId > 0 && $targetOwnFamilyId !== null && $targetOwnFamilyId > 0 && $targetOwnFamilyId !== $familyId) {
                $families->ensureHeadMembershipForDesignatedHead($targetOwnFamilyId);
                $mirrorRole = $this->inverseFamilyRole($role);
                (new FamilyRelationshipLink())->upsert($targetOwnFamilyId, $requesterHeadUserId, $mirrorRole, $targetUserId);
            }
            $this->markApproved($requestId, $responderUserId);
            (new Notification())->markReadByReferenceForUser($requestId, 'relationship_request', $responderUserId);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return null;
    }

    private function inverseFamilyRole(string $requestedRole): string
    {
        $r = strtolower(trim($requestedRole));
        $map = [
            'father' => 'son',
            'mother' => 'son',
            'son' => 'father',
            'daughter' => 'father',
            'wife' => 'husband',
            'husband' => 'wife',
            'brother' => 'brother',
            'sister' => 'sister',
            'daughter-in-law' => 'father',
            'son-in-law' => 'father',
            'other' => 'other',
        ];

        return $map[$r] ?? 'other';
    }

    /**
     * Accept invitation to become head of a new family (family_id was NULL on the request).
     *
     * @param array<string, mixed> $req
     */
    private function approveNewFamilyHeadInvite(array $req, int $requestId, int $responderUserId): ?string
    {
        $orgId = (int) ($req['organization_id'] ?? 0);
        if ($orgId < 1) {
            return 'Invalid invitation.';
        }
        if (strtolower(trim((string) $req['requested_role'])) !== 'head') {
            return 'Invalid invitation.';
        }
        $targetUserId = (int) $req['target_user_id'];

        $families = new Family();
        $orgs = new Organization();
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $canonicalFamilyId = $families->canonicalFamilyIdForHeadUserId($targetUserId);
            if ($canonicalFamilyId !== null && $canonicalFamilyId > 0) {
                $this->addEntireHouseholdToOrganization($targetUserId, $orgId);
            } else {
                if (!$orgs->userIsMember($targetUserId, $orgId)) {
                    $orgs->addUser($orgId, $targetUserId, 'member');
                }
                $requesterId = (int) $req['requested_by_user_id'];
                $familyIdNew = $families->create($orgId, $targetUserId, $requesterId);
                $families->addMember($familyIdNew, $targetUserId, 'head', null);
            }
            $this->markApproved($requestId, $responderUserId);
            (new Notification())->markReadByReferenceForUser($requestId, 'relationship_request', $responderUserId);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return null;
    }

    private function addEntireHouseholdToOrganization(int $headUserId, int $organizationId): void
    {
        $orgs = new Organization();
        $stmt = Database::pdo()->prepare(
            'SELECT DISTINCT fm.user_id
            FROM family_members fm
            INNER JOIN families f ON f.id = fm.family_id
            WHERE f.head_user_id = ?
            UNION
            SELECT ?'
        );
        $stmt->execute([$headUserId, $headUserId]);
        $userIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        foreach ($userIds as $uid) {
            if ($uid < 1) {
                continue;
            }
            if ($orgs->userHasRole($uid, $organizationId, 'admin')) {
                continue;
            }
            if (!$orgs->userIsMember($uid, $organizationId)) {
                $orgs->addUser($organizationId, $uid, 'member');
            }
        }
    }

    /**
     * @return string|null error or null
     */
    public function reject(int $requestId, int $responderUserId): ?string
    {
        $req = $this->findById($requestId);
        if (!$req || ($req['status'] ?? '') !== 'pending') {
            return 'Not pending.';
        }
        if ((int) $req['target_user_id'] !== $responderUserId) {
            return 'Not for you.';
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $this->markRejected($requestId, $responderUserId);
            (new Notification())->markReadByReferenceForUser($requestId, 'relationship_request', $responderUserId);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return null;
    }
}
