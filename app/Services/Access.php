<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Family;
use App\Models\Organization;

use function organization_id;

final class Access
{
    private Organization $organizations;
    private Family $families;

    public function __construct(?Organization $organizations = null, ?Family $families = null)
    {
        $this->organizations = $organizations ?? new Organization();
        $this->families = $families ?? new Family();
    }

    public function canManageOrganization(?array $user, int $organizationId): bool
    {
        if ($user === null) {
            return false;
        }
        return $this->organizations->userHasRole((int) $user['id'], $organizationId, 'admin');
    }

    public function canAccessOrganization(?array $user, int $organizationId): bool
    {
        if ($user === null) {
            return false;
        }
        return $this->organizations->userIsMember((int) $user['id'], $organizationId);
    }

    public function canManageFamily(?array $user, int $familyId, ?int $forOrganizationId = null): bool
    {
        if ($user === null) {
            return false;
        }
        if (!$this->families->findById($familyId)) {
            return false;
        }
        $uid = (int) $user['id'];
        $orgId = $forOrganizationId !== null && $forOrganizationId > 0
            ? $forOrganizationId
            : organization_id();
        if (!$this->families->familyIsAnchoredInOrganization($familyId, $orgId)) {
            return false;
        }
        if ($this->organizations->userHasRole($uid, $orgId, 'admin')) {
            return true;
        }
        if (
            ($this->families->userIsHead($uid, $familyId) || $this->families->isDesignatedHead($uid, $familyId))
            && $this->organizations->userIsMember($uid, $orgId)
        ) {
            return true;
        }

        return false;
    }

    public function canViewFamily(?array $user, int $familyId, ?int $forOrganizationId = null): bool
    {
        if ($user === null) {
            return false;
        }
        if (!$this->families->findById($familyId)) {
            return false;
        }
        $uid = (int) $user['id'];
        $orgId = $forOrganizationId !== null && $forOrganizationId > 0
            ? $forOrganizationId
            : organization_id();
        if (!$this->organizations->userIsMember($uid, $orgId)) {
            return false;
        }
        if (!$this->families->familyIsAnchoredInOrganization($familyId, $orgId)) {
            return false;
        }
        if ($this->canManageOrganization($user, $orgId)) {
            return true;
        }
        if ($this->families->userIsFamilyMember($familyId, $uid) || $this->families->isDesignatedHead($uid, $familyId)) {
            return true;
        }

        return false;
    }

    public function canViewReceipt(?array $user, array $receipt, ?int $forOrganizationId = null): bool
    {
        if ($user === null || $receipt === []) {
            return false;
        }
        $orgId = $forOrganizationId !== null && $forOrganizationId > 0
            ? $forOrganizationId
            : organization_id();
        if ($orgId < 1 || (int) ($receipt['organization_id'] ?? 0) !== $orgId) {
            return false;
        }
        if ($this->canManageOrganization($user, $orgId)) {
            return true;
        }
        $familyId = (int) ($receipt['family_id'] ?? 0);

        return $familyId > 0 && $this->canViewFamily($user, $familyId, $orgId);
    }
}
