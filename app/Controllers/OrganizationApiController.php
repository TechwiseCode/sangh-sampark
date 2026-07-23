<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\Organization;
use App\Models\User;
use App\Services\Access;

use function current_user;

final class OrganizationApiController extends ApiController
{
    public function create(Request $request): void
    {
        $user = current_user();
        $access = new Access();
        if (!$access->isSuperadmin($user)) {
            $this->json(['ok' => false, 'error' => 'Only platform superadmin can create organizations'], 403);
        }
        $data = $this->body($request);
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $this->json(['ok' => false, 'error' => 'Upashray/Sangh name required'], 422);
        }

        $orgs = new Organization();
        $users = new User();
        $candidateId = $user !== null ? (int) ($user['id'] ?? 0) : 0;
        $creatorId = $candidateId > 0 && $users->findById($candidateId) !== null ? $candidateId : null;
        if ($creatorId === null) {
            $this->json(['ok' => false, 'error' => 'Session user not found in database. Sign in again.'], 409);
        }
        $orgId = $orgs->create($name, $creatorId);
        $orgs->addUser($orgId, $creatorId, 'admin');

        $this->json([
            'ok' => true,
            'organization' => $orgs->findById($orgId),
        ], 201);
    }

    public function addAdmin(Request $request): void
    {
        $user = current_user();
        $access = new Access();
        $data = $this->body($request);
        $organizationId = (int) ($data['organization_id'] ?? 0);
        $identity = trim((string) ($data['identity'] ?? $data['email'] ?? ''));
        $role = trim((string) ($data['role'] ?? 'admin'));
        if (!in_array($role, ['admin', 'member'], true)) {
            $this->json(['ok' => false, 'error' => 'role must be admin or member'], 422);
        }
        if ($organizationId < 1 || $identity === '') {
            $this->json(['ok' => false, 'error' => 'organization_id and identity required'], 422);
        }
        if ($role === 'admin' && !$access->isSuperadmin($user)) {
            $this->json(['ok' => false, 'error' => 'Only platform superadmin can assign organization admins'], 403);
        }
        if ($role === 'member' && !$access->canManageOrganization($user, $organizationId)) {
            $this->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $users = new User();
        $target = $users->findByIdentity($identity);
        if (!$target) {
            $this->json(['ok' => false, 'error' => 'User not found'], 404);
        }

        $orgs = new Organization();
        $orgs->addUser($organizationId, (int) $target['id'], $role);

        $this->json(['ok' => true, 'user_id' => (int) $target['id'], 'role' => $role]);
    }
}
