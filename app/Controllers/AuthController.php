<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\EmailVerificationToken;
use App\Models\Organization;
use App\Models\User;

use function base_url;
use function flash_get;
use function flash_set;
use function is_valid_email;
use function user_is_superadmin;
use function redirect;
use function current_user;
use function normalize_identity;
use function queue_deferred_email;
use function release_session_lock;
use function resume_session_for_flash;
use function set_current_user;
use function set_current_organization_id;
use function set_locale;
use function send_html_cache_headers;
use function client_ip;
use function rate_limit_too_many;
use function json_response;
use function pwa_status_payload;

final class AuthController
{
    public function home(Request $request): void
    {
        $u = current_user();
        if ($u === null) {
            redirect(base_url() . '/login');
        }
        if (user_is_superadmin($u)) {
            redirect(base_url() . '/superadmin');
        }
        $orgs = (new Organization())->listForUser((int) $u['id']);
        if ($orgs === []) {
            redirect(base_url() . '/login');
        }
        redirect(base_url() . '/organization/dashboard');
    }

    public function showLogin(Request $request): void
    {
        $signedOut = isset($_GET['signed_out']) && (string) $_GET['signed_out'] === '1';
        if ($signedOut) {
            $this->forceLocalSignOut();
        }
        $u = current_user();
        if ($u !== null) {
            if (user_is_superadmin($u)) {
                redirect(base_url() . '/superadmin');
            }
            $orgs = (new Organization())->listForUser((int) $u['id']);
            if ($orgs === []) {
                set_current_user(null);
                set_current_organization_id(null);
            } else {
                redirect(base_url() . '/organization/dashboard');
            }
        }
        $no_access_message = null;
        $flashOk = flash_get('ok');
        $flashErr = flash_get('error');
        send_html_cache_headers();
        require BASE_PATH . '/app/Views/auth/login_organization.php';
    }

    public function showSuperadminLogin(Request $request): void
    {
        $signedOut = isset($_GET['signed_out']) && (string) $_GET['signed_out'] === '1';
        if ($signedOut) {
            $this->forceLocalSignOut();
        }
        $u = current_user();
        if ($u !== null) {
            if (user_is_superadmin($u)) {
                redirect(base_url() . '/superadmin');
            }
            $orgs = (new Organization())->listForUser((int) $u['id']);
            if ($orgs === []) {
                set_current_user(null);
                set_current_organization_id(null);
            } else {
                redirect(base_url() . '/organization/dashboard');
            }
        }
        send_html_cache_headers();
        require BASE_PATH . '/app/Views/auth/login.php';
    }

    public function showOrganizationLogin(Request $request): void
    {
        redirect(base_url() . '/login');
    }

    public function showForgotPassword(Request $request): void
    {
        if (current_user() !== null) {
            redirect(base_url() . '/organization/dashboard');
        }
        $flashOk = flash_get('ok');
        $flashErr = flash_get('error');
        require BASE_PATH . '/app/Views/auth/forgot_password.php';
    }

    public function forgotPasswordStore(Request $request): void
    {
        $ip = client_ip();
        if (rate_limit_too_many('forgot:' . $ip, 5, 900)) {
            flash_set('error', 'Too many reset attempts. Please wait and try again.');
            redirect(base_url() . '/forgot-password');
        }

        $orgCode = strtoupper(trim((string) ($_POST['org_code'] ?? '')));
        $identity = normalize_identity((string) ($_POST['identity'] ?? ''));
        $genericOk = 'If the account exists in this organization, a temporary password has been sent to the registered email.';

        if ($orgCode === '' || $identity === '') {
            flash_set('error', 'Please enter your organization code and email or phone.');
            redirect(base_url() . '/forgot-password');
        }
        $org = (new Organization())->findByOrgCode($orgCode);
        if ($org === null) {
            flash_set('ok', $genericOk);
            redirect(base_url() . '/login');
        }
        $orgId = (int) $org['id'];
        $userModel = new User();
        $row = $userModel->findByIdentity($identity, $orgId);
        if ($row === null || (string) ($row['role'] ?? '') === 'superadmin') {
            flash_set('ok', $genericOk);
            redirect(base_url() . '/login');
        }
        $email = trim((string) ($row['email'] ?? ''));
        if ($email === '' || !is_valid_email($email)) {
            flash_set('ok', $genericOk);
            redirect(base_url() . '/login');
        }
        $temporaryPassword = generate_temporary_otp_password();
        $loginUrl = base_url() . '/login';
        $displayName = (string) ($row['name'] ?? 'User');
        $mailPayload = [
            'email' => $email,
            'name' => $displayName,
            'password' => $temporaryPassword,
            'org_code' => $orgCode,
            'login_url' => $loginUrl,
        ];
        release_session_lock();
        $mailQueued = queue_deferred_email('forgot_password', $mailPayload);
        if (empty($mailQueued['queued'])) {
            resume_session_for_flash();
            flash_set('error', 'Could not send the reset email right now. Please contact your administrator.');
            redirect(base_url() . '/forgot-password');
        }
        $userId = (int) $row['id'];
        $newHash = password_hash($temporaryPassword, PASSWORD_BCRYPT);
        $userModel->updatePasswordHash($userId, $newHash);
        $userModel->syncPasswordHashToEmailSiblings($userId, $newHash);
        $userModel->setMustChangePassword($userId, true);
        $updatedUser = $userModel->findById($userId);
        $updatedHash = normalize_stored_password_hash((string) ($updatedUser['password'] ?? ''));
        if ($updatedUser === null || $updatedHash === '' || !password_verify($temporaryPassword, $updatedHash)) {
            error_log('Forgot-password hash verification failed for user_id=' . $userId);
            resume_session_for_flash();
            flash_set('error', 'Password reset could not be completed. Please try again.');
            redirect(base_url() . '/forgot-password');
        }
        resume_session_for_flash();
        $_SESSION['force_password_change_user_id'] = (int) $row['id'];
        $mailMessage = !empty($mailQueued['sent'])
            ? 'Temporary password was sent to your email. Change it under Settings after sign-in.'
            : 'Temporary password is being sent to your email. Change it under Settings after sign-in.';
        flash_set('ok', $mailMessage);
        redirect(base_url() . '/login');
    }

    public function logout(Request $request): void
    {
        // Always destroy the local session. A failed GET CSRF used to redirect to
        // /login while still authenticated (bounce back to dashboard). Clear-Site-Data
        // was also removed — it hangs Chromium/PWA navigations on logout.
        $wasSuperadmin = user_is_superadmin(current_user());
        $this->forceLocalSignOut();
        send_html_cache_headers();
        if ($wasSuperadmin) {
            redirect(base_url() . '/login/superadmin?signed_out=1');
        }
        redirect(base_url() . '/login?signed_out=1');
    }

    /** Clear auth session + cookies without navigating. */
    private function forceLocalSignOut(): void
    {
        set_current_user(null);
        set_current_organization_id(null);
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy_all_cookies();
            session_destroy();
        }
    }

    public function verifyEmail(Request $request): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        if ($token === '') {
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(400);
            echo 'Invalid verification link.';

            return;
        }
        $uid = (new EmailVerificationToken())->consumeToken($token);
        if ($uid === null) {
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(400);
            echo 'Verification link is invalid or expired.';

            return;
        }
        (new User())->markEmailVerified($uid);
        flash_set('ok', 'Email verified. You can sign in now.');
        redirect(base_url() . '/login');
    }

    public function setLocale(Request $request): void
    {
        $locale = trim((string) ($_POST['locale'] ?? $_GET['locale'] ?? ''));
        set_locale($locale);

        $back = trim((string) ($_POST['back'] ?? $_GET['back'] ?? ''));
        if ($back === '' || strpos($back, '/') !== 0 || strpos($back, '//') === 0 || strpos($back, '..') !== false) {
            $back = '/organization/dashboard';
        }
        if ($back === '/locale') {
            $back = '/organization/dashboard';
        }

        redirect(base_url() . $back);
    }

    public function manifest(Request $request): void
    {
        pwa_manifest_response();
    }

    public function pwaStatus(Request $request): void
    {
        // Auth required via middleware; payload already strips disk paths for non-debug.
        json_response(pwa_status_payload());
    }
}
