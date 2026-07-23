<?php

declare(strict_types=1);

namespace App\Controllers\Concerns;

use App\Models\Family;
use App\Models\Organization;

use function base_url;
use function current_user;
use function flash_set;
use function organization_id;
use function redirect;

trait OrganizationPortalTrait
{
    private function requireOrgId(): int
    {
        $id = organization_id();
        if ($id < 1) {
            flash_set('error', 'Upashray/Sangh not configured.');
            redirect(base_url() . '/organization/dashboard');
        }
        $user = current_user();
        if (!$user || !(new Organization())->userIsMember((int) $user['id'], $id)) {
            flash_set('error', 'Access denied.');
            redirect(base_url() . ($user !== null ? '/organization/dashboard' : '/login'));
        }
        return $id;
    }

    /** @param list<array<string,mixed>> $memberships */
    private function pickCurrentMembership(array $memberships, int $orgId): ?array
    {
        foreach ($memberships as $o) {
            if ((int) $o['id'] === $orgId) {
                return $o;
            }
        }
        return $memberships[0] ?? null;
    }

    /** @return array{0: list<array<string,mixed>>, 1: ?array<string,mixed>, 2: int} */
    private function dashboardOrgContext(): array
    {
        $user = current_user();
        $orgModel = new Organization();
        $memberships = $orgModel->listForUser((int) $user['id']);
        $orgId = organization_id();
        $current = $this->pickCurrentMembership($memberships, $orgId);
        if ($current === null && $memberships !== []) {
            $current = $memberships[0];
            $orgId = (int) $current['id'];
        }

        return [$memberships, $current, $orgId];
    }

    /** @return array{memberships: list<array<string,mixed>>, current: ?array<string,mixed>, orgId: int, user: array<string,mixed>} */
    private function orgPageBundle(int $orgId): array
    {
        $user = current_user();
        $orgModel = new Organization();
        $memberships = $orgModel->listForUser((int) $user['id']);
        $current = $this->pickCurrentMembership($memberships, $orgId);

        return [
            'memberships' => $memberships,
            'current' => $current,
            'orgId' => $orgId,
            'user' => $user,
        ];
    }

    private function familyInOrgOrAbort(int $familyId, int $orgId): array
    {
        $families = new Family();
        $family = $families->findById($familyId);
        if (!$family) {
            flash_set('error', 'Family not found.');
            redirect(base_url() . '/organization/families');
        }
        $canonicalId = $families->canonicalFamilyIdForHeadUserId((int) $family['head_user_id']);
        if ($canonicalId !== null && $canonicalId !== (int) $family['id']) {
            $family = $families->findById($canonicalId);
        }
        if (!$family || !$families->familyIsAnchoredInOrganization((int) $family['id'], $orgId)) {
            flash_set('error', 'Family not found.');
            redirect(base_url() . '/organization/families');
        }

        return $family;
    }

    private function resolvePrimaryFamilyIdForUser(int $userId, int $orgId): int
    {
        if ($userId < 1 || $orgId < 1) {
            return 0;
        }
        $families = new Family();
        $list = $families->listByOrganizationForMember($userId, $orgId);
        if ($list === []) {
            return 0;
        }
        if (count($list) === 1) {
            return (int) ($list[0]['id'] ?? 0);
        }
        foreach ($list as $row) {
            $familyId = (int) ($row['id'] ?? 0);
            if ($familyId > 0 && ($families->userIsHead($userId, $familyId) || $families->isDesignatedHead($userId, $familyId))) {
                return $familyId;
            }
        }

        return (int) ($list[0]['id'] ?? 0);
    }
}
