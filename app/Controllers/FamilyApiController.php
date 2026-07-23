<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\Family;
use App\Models\FamilyMembershipRequest;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\User;
use App\Services\Access;

use function current_user;

final class FamilyApiController extends ApiController
{
    public function create(Request $request): void
    {
        $user = current_user();
        $access = new Access();
        $data = $this->body($request);
        $organizationId = (int) ($data['organization_id'] ?? 0);
        $headUserId = (int) ($data['head_user_id'] ?? 0);
        if ($organizationId < 1 || $headUserId < 1) {
            $this->json(['ok' => false, 'error' => 'organization_id and head_user_id required'], 422);
        }
        if (!$access->canManageOrganization($user, $organizationId)) {
            $this->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }
        $orgs = new Organization();
        if (!$orgs->userIsMember($headUserId, $organizationId)) {
            $this->json(['ok' => false, 'error' => 'Head user must belong to the organization'], 422);
        }

        $families = new Family();
        $familyId = $families->create($organizationId, $headUserId, $user ? (int) $user['id'] : null);
        $families->addMember($familyId, $headUserId, 'head', null);

        $this->json([
            'ok' => true,
            'family' => $families->findById($familyId),
        ], 201);
    }

    public function addMember(Request $request): void
    {
        $user = current_user();
        $access = new Access();
        $data = $this->body($request);
        $familyId = (int) ($data['family_id'] ?? 0);
        $role = trim((string) ($data['role'] ?? 'member'));
        $relatedTo = isset($data['related_to_user_id']) && $data['related_to_user_id'] !== ''
            ? (int) $data['related_to_user_id'] : null;

        $targetUserId = isset($data['user_id']) ? (int) $data['user_id'] : 0;
        if ($targetUserId < 1 && isset($data['identity'])) {
            $u = new User();
            $row = $u->findByIdentity(trim((string) $data['identity']));
            $targetUserId = $row ? (int) $row['id'] : 0;
        }
        if ($familyId < 1 || $targetUserId < 1 || $role === '') {
            $this->json(['ok' => false, 'error' => 'family_id, user (user_id or identity), and role required'], 422);
        }

        $families = new Family();
        $family = $families->findById($familyId);
        if (!$family) {
            $this->json(['ok' => false, 'error' => 'Family not found'], 404);
        }
        $organizationId = (int) ($data['organization_id'] ?? 0);
        if ($organizationId < 1) {
            $organizationId = (int) $family['organization_id'];
        }
        if (!$families->familyIsAnchoredInOrganization($familyId, $organizationId)) {
            $this->json(['ok' => false, 'error' => 'organization_id must be an organization where this family\'s head is a member'], 422);
        }
        if (!$access->canManageFamily($user, $familyId, $organizationId)) {
            $this->json(['ok' => false, 'error' => 'Only an organization admin or the family head may add members'], 403);
        }
        $roleLower = strtolower(trim($role));
        if ($roleLower === 'head' && !$access->canManageOrganization($user, $organizationId)) {
            $this->json(['ok' => false, 'error' => 'Only an organization admin can assign the head role'], 403);
        }
        if ($families->userIsFamilyMember($familyId, $targetUserId)) {
            $this->json(['ok' => false, 'error' => 'User is already in this family; use the relationship-change flow'], 409);
        }

        $ruleErr = $families->validateAddMember($familyId, $targetUserId, $role, $relatedTo);
        if ($ruleErr !== null) {
            $this->json(['ok' => false, 'error' => $ruleErr], 422);
        }

        $reqModel = new FamilyMembershipRequest();
        $requestId = $reqModel->createPending(
            $familyId,
            $targetUserId,
            (int) $user['id'],
            $role,
            $relatedTo
        );
        $orgRow = (new Organization())->findById($organizationId);
        $orgName = (string) (($orgRow['name'] ?? '') ?: 'Upashray/Sangh');
        $title = 'Invitation — ' . $orgName;
        $msg = (string) $user['name'] . ' invited you to join this organization and family #' . $familyId
            . ' as “' . $role . '”. Open Notifications to accept or decline.';
        (new Notification())->createForUser($targetUserId, 'relationship_request', $requestId, $title, $msg);

        $this->json([
            'ok' => true,
            'pending' => true,
            'request_id' => $requestId,
            'family_id' => $familyId,
            'user_id' => $targetUserId,
            'role' => $role,
        ], 202);
    }

    public function list(Request $request): void
    {
        $user = current_user();
        $access = new Access();
        $organizationId = (int) ($_GET['organization_id'] ?? 0);
        if ($organizationId < 1) {
            $this->json(['ok' => false, 'error' => 'organization_id query required'], 422);
        }
        if (!$access->canAccessOrganization($user, $organizationId)) {
            $this->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }
        $families = new Family();
        if ($access->canManageOrganization($user, $organizationId)) {
            $list = $families->listByOrganization($organizationId);
        } else {
            $list = $families->listByOrganizationForMember((int) $user['id'], $organizationId);
        }
        $this->json(['ok' => true, 'families' => $list]);
    }

    public function details(Request $request): void
    {
        $user = current_user();
        $access = new Access();
        $id = (int) ($_GET['id'] ?? 0);
        if ($id < 1) {
            $this->json(['ok' => false, 'error' => 'id query required'], 422);
        }
        if (!$access->canViewFamily($user, $id)) {
            $this->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }
        $families = new Family();
        $family = $families->findById($id);
        if (!$family) {
            $this->json(['ok' => false, 'error' => 'Not found'], 404);
        }
        $this->json([
            'ok' => true,
            'family' => $family,
            'members' => $families->membersWithUsers($id),
        ]);
    }
}
