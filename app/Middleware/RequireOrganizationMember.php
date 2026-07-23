<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Models\Family;
use App\Models\Organization;
use App\Services\RequestCache;

use function base_url;
use function current_organization_id;
use function current_user;
use function flash_set;
use function json_response;
use function redirect;
use function set_current_organization_id;
use function set_current_user;
use function user_is_superadmin;

final class RequireOrganizationMember implements MiddlewareInterface
{
    public function handle(Request $request): void
    {
        $user = current_user();
        if ($user === null) {
            if ($this->wantsJson($request)) {
                json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
            }
            redirect(base_url() . '/login/organization');
        }
        if (user_is_superadmin($user)) {
            if ($this->wantsJson($request)) {
                json_response(['ok' => false, 'error' => 'Superadmin must use superadmin routes'], 403);
            }
            redirect(base_url() . '/superadmin');
        }
        $orgModel = new Organization();
        $list = $orgModel->listForUser((int) $user['id']);
        if ($list === []) {
            if ($this->wantsJson($request)) {
                json_response(['ok' => false, 'error' => 'No organization membership'], 403);
            }
            set_current_user(null);
            set_current_organization_id(null);
            flash_set('error', 'You are not in any organization yet.');
            redirect(base_url() . '/login/organization');
        }
        $cid = current_organization_id();
        $found = false;
        foreach ($list as $o) {
            if ((int) $o['id'] === $cid) {
                $found = true;
                break;
            }
        }
        $uid = (int) $user['id'];
        $families = new Family();
        if (!$found) {
            $headMemberships = $orgModel->listOrganizationsWhereUserIsFamilyHead($uid);
            if ($headMemberships !== []) {
                set_current_organization_id((int) $headMemberships[0]['id']);
            } else {
                set_current_organization_id((int) $list[0]['id']);
            }
        }
        $isFamilyHead = RequestCache::remember('family_head_any:' . $uid, static function () use ($families, $uid): bool {
            return $families->userIsFamilyHeadInAnyOrganization($uid);
        });
        if (!$isFamilyHead) {
            $pinned = $orgModel->pinnedOrganizationIdForNonHead($uid, $list);
            if ($pinned !== current_organization_id()) {
                set_current_organization_id($pinned);
            }
        }

        $this->enforceForcedPasswordChange($request, $user);
        $this->enforceActiveMembership($request, $user);
    }

    /**
     * @param array<string,mixed> $user
     */
    private function enforceActiveMembership(Request $request, array $user): void
    {
        $uid = (int) ($user['id'] ?? 0);
        if ($uid < 1) {
            return;
        }

        $users = new \App\Models\User();
        $fresh = $users->findById($uid);
        if ($fresh === null) {
            set_current_user(null);
            set_current_organization_id(null);
            if ($this->wantsJson($request)) {
                json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
            }
            redirect(base_url() . '/login/organization');
        }

        if (isset($fresh['is_active']) && (int) $fresh['is_active'] === 0) {
            set_current_user(null);
            set_current_organization_id(null);
            flash_set('error', 'This account has been deactivated. Contact your organization administrator.');
            if ($this->wantsJson($request)) {
                json_response(['ok' => false, 'error' => 'Account deactivated'], 403);
            }
            redirect(base_url() . '/login/organization');
        }

        $orgId = current_organization_id() ?? (int) ($fresh['organization_id'] ?? 0);
        if ($orgId > 0 && !(new Organization())->isActive($orgId)) {
            set_current_user(null);
            set_current_organization_id(null);
            flash_set('error', 'This Upashray/Sangh is currently disabled. Contact the platform administrator.');
            if ($this->wantsJson($request)) {
                json_response(['ok' => false, 'error' => 'Organization disabled'], 403);
            }
            redirect(base_url() . '/login/organization');
        }
    }

    /**
     * @param array<string,mixed> $user
     */
    private function enforceForcedPasswordChange(Request $request, array $user): void
    {
        $uid = (int) ($user['id'] ?? 0);
        if ($uid < 1) {
            return;
        }

        $needsChange = !empty($user['must_change_password']);
        if (!$needsChange) {
            $sessionForceId = isset($_SESSION['force_password_change_user_id'])
                ? (int) $_SESSION['force_password_change_user_id']
                : 0;
            $needsChange = $sessionForceId > 0 && $sessionForceId === $uid;
        }
        if (!$needsChange && (new \App\Models\User())->mustChangePassword($uid)) {
            $needsChange = true;
            $fresh = (new \App\Models\User())->findById($uid);
            if ($fresh !== null) {
                set_current_user((new \App\Models\User())->toSessionArray($fresh));
            }
        }
        if (!$needsChange) {
            return;
        }

        $path = $request->path();
        $allowedExact = [
            '/organization/settings/password',
            '/organization/settings/change-password',
            '/logout',
            '/locale',
        ];
        if (in_array($path, $allowedExact, true)) {
            return;
        }
        // Block settings subpages other than password.
        if (strpos($path, '/organization/settings') === 0) {
            flash_set('ok', 'For security, please change your temporary login code now.');
            redirect(base_url() . '/organization/settings/password');
        }

        if ($this->wantsJson($request)) {
            json_response([
                'ok' => false,
                'error' => 'Password change required',
                'force_change_password' => true,
                'redirect' => base_url() . '/organization/settings/password',
            ], 403);
        }

        flash_set('ok', 'For security, please change your temporary login code now.');
        redirect(base_url() . '/organization/settings/password');
    }

    private function wantsJson(Request $request): bool
    {
        return request_wants_json(
            $request->header('Accept'),
            $request->header('Content-Type')
        );
    }
}
