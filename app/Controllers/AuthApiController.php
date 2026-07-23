<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Access;

use function normalize_phone;
use function normalize_identity;
use function normalize_stored_password_hash;
use function flash_set;
use function set_current_organization_id;
use function set_current_user;
use function sync_user_locale_on_login;
use function client_ip;
use function rate_limit_too_many;
use function rate_limit_clear;

final class AuthApiController extends ApiController
{
    public function register(Request $request): void
    {
        $this->json([
            'ok' => false,
            'error' => 'Self-registration is not available. Your organization admin adds members through a family.',
        ], 403);
    }

    public function login(Request $request): void
    {
        try {
            $data = $this->body($request);
            $identity = normalize_identity(trim((string) ($data['identity'] ?? $data['email'] ?? '')));
            $password = (string) ($data['password'] ?? '');
            $ip = client_ip();
            $rateKey = 'login:' . $ip . ':' . substr(hash('sha256', strtolower($identity)), 0, 16);

            if (rate_limit_too_many($rateKey, 8, 600)) {
                $this->json(['ok' => false, 'error' => 'Too many sign-in attempts. Please wait and try again.'], 429);
            }

            if ($identity === '' || $password === '') {
                $this->json(['ok' => false, 'error' => 'Identity and password required'], 422);
            }

            $users = new User();
            $orgId = null;
            $loginAs = isset($data['login_as']) ? trim((string) $data['login_as']) : '';
            if ($loginAs === 'organization') {
                $orgCode = strtoupper(trim((string) ($data['org_code'] ?? '')));
                if ($orgCode === '') {
                    $this->json(['ok' => false, 'error' => 'Upashray/Sangh code is required.'], 422);
                }
                $org = (new Organization())->findByOrgCode($orgCode);
                if ($org === null) {
                    $this->json(['ok' => false, 'error' => 'Invalid credentials'], 401);
                }
                $orgId = (int) $org['id'];
            }
            $row = $loginAs === 'superadmin'
                ? $users->findByIdentity($identity, null)
                : $users->findByIdentity($identity, $orgId);
            if ($row === null) {
                $this->json(['ok' => false, 'error' => 'Invalid credentials'], 401);
            }

            $storedHash = normalize_stored_password_hash((string) ($row['password'] ?? ''));
            $passwordOk = $storedHash !== '' && password_verify($password, $storedHash);
            // 6-letter OTP codes are stored uppercase; accept lowercase typing.
            if (!$passwordOk && preg_match('/^[A-Za-z]{6}$/', $password) === 1) {
                $passwordOk = $storedHash !== '' && password_verify(strtoupper($password), $storedHash);
            }
            if (!$passwordOk) {
                $this->json(['ok' => false, 'error' => 'Invalid credentials'], 401);
            }

            if ($loginAs !== 'superadmin') {
                if (isset($row['is_active']) && (int) $row['is_active'] === 0) {
                    $this->json(['ok' => false, 'error' => 'This account has been deactivated. Contact your organization administrator.'], 403);
                }
                $checkOrgId = $orgId ?? (int) ($row['organization_id'] ?? 0);
                if ($checkOrgId > 0 && !(new Organization())->isActive($checkOrgId)) {
                    $this->json(['ok' => false, 'error' => 'This Upashray/Sangh is currently disabled. Contact the platform administrator.'], 403);
                }
            }

            $role = (string) ($row['role'] ?? 'member');
            if ($loginAs === 'superadmin') {
                if ($role !== 'superadmin') {
                    $this->json(['ok' => false, 'error' => 'This sign-in is for platform superadmin accounts only.'], 403);
                }
            } elseif ($loginAs === 'organization') {
                if ($role === 'superadmin') {
                    $this->json(['ok' => false, 'error' => 'Use the platform administrator sign-in page.'], 403);
                }
                if (!in_array($role, ['admin', 'member'], true)) {
                    $this->json(['ok' => false, 'error' => 'This account is not active in this organization.'], 403);
                }
                $memberships = (new Organization())->listForUser((int) $row['id']);
                if ($memberships === []) {
                    $this->json([
                        'ok' => false,
                        'error' => 'This account is not active in this organization. Please contact your administrator.',
                    ], 403);
                }
            } else {
                if ($role === 'superadmin') {
                    $this->json(['ok' => false, 'error' => 'Use the platform administrator sign-in page.'], 403);
                }
                $memberships = (new Organization())->listForUser((int) $row['id']);
                if ($memberships === []) {
                    $this->json([
                        'ok' => false,
                        'error' => 'This account is not active in this organization. Please contact your administrator.',
                    ], 403);
                }
            }

            $forceChangePassword = !empty($row['must_change_password']);
            $forceChangeUserId = isset($_SESSION['force_password_change_user_id']) ? (int) $_SESSION['force_password_change_user_id'] : 0;
            if ($forceChangeUserId > 0 && $forceChangeUserId === (int) $row['id']) {
                $forceChangePassword = true;
            }
            if ($forceChangePassword) {
                flash_set('ok', 'For security, please change your temporary password now.');
            }

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            rate_limit_clear($rateKey);

            set_current_user($users->toSessionArray($row));
            sync_user_locale_on_login($row);
            if ($loginAs !== 'superadmin' && $orgId !== null && $orgId > 0) {
                set_current_organization_id($orgId);
            }

            $mustCompleteProfile = false;
            if ($loginAs === 'organization') {
                $sessionUser = $users->toSessionArray($row);
                $checkOrgId = $orgId ?? (int) ($row['organization_id'] ?? 0);
                $isOrgAdmin = $checkOrgId > 0 && (new Access())->canManageOrganization($sessionUser, $checkOrgId);
                if (!$isOrgAdmin && $role === 'member') {
                    $mustCompleteProfile = !(new UserProfile())->isCompleteForUser((int) $row['id']);
                }
            }

            $intendedUrl = isset($_SESSION['intended_url']) ? (string) $_SESSION['intended_url'] : '';
            unset($_SESSION['intended_url']);
            $intendedUrl = sanitize_post_login_intended_url($intendedUrl) ?? '';

            $this->json([
                'ok' => true,
                'user' => $users->toSessionArray($row),
                'force_change_password' => $forceChangePassword,
                'must_complete_profile' => $mustCompleteProfile,
                'intended_url' => $intendedUrl !== '' ? $intendedUrl : null,
            ]);
        } catch (\Throwable $e) {
            error_log('AuthApiController::login ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $env = (string) \app_config('env', 'production');
            $msg = $env === 'development'
                ? ('Server error: ' . $e->getMessage())
                : 'Server error while signing in. Check database config and the PHP error log.';
            $this->json(['ok' => false, 'error' => $msg], 500);
        }
    }
}
