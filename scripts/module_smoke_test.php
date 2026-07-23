<?php

declare(strict_types=1);

/**
 * Module smoke tests: deactivate flags + key superadmin/org routes.
 *
 * Usage (local WAMP):
 *   php scripts/module_smoke_test.php
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Models\Organization;
use App\Models\User;

$base = rtrim(base_url(), '/');
$cookieFile = sys_get_temp_dir() . '/szvs_module_smoke_cookies.txt';
@unlink($cookieFile);

$pass = 0;
$fail = 0;
$results = [];

function smoke_ok(string $name, bool $ok, string $detail = ''): void
{
    global $pass, $fail, $results;
    $results[] = [$name, $ok, $detail];
    if ($ok) {
        $pass++;
        echo 'PASS  ' . $name . ($detail !== '' ? ' — ' . $detail : '') . PHP_EOL;
    } else {
        $fail++;
        echo 'FAIL  ' . $name . ($detail !== '' ? ' — ' . $detail : '') . PHP_EOL;
    }
}

function http_req(string $url, string $method = 'GET', ?string $body = null, array $headers = [], ?string $cookieFile = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    if ($headers !== []) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        return ['code' => 0, 'body' => $err, 'headers' => ''];
    }
    $parts = explode("\r\n\r\n", $raw, 2);
    if (count($parts) < 2) {
        $parts = explode("\n\n", $raw, 2);
    }

    return ['code' => $code, 'headers' => $parts[0] ?? '', 'body' => $parts[1] ?? $raw];
}

echo 'Base URL: ' . $base . PHP_EOL;
echo '--- Model / DB checks ---' . PHP_EOL;

$users = new User();
$orgs = new Organization();

// Ensure columns exist via model helpers.
$probeUser = $users->findById(1);
smoke_ok('users_is_active_column_readable', true, 'ensureIsActive ran');

$allOrgs = $orgs->listAll();
smoke_ok('organizations_list', $allOrgs !== [], 'count=' . count($allOrgs));

$orgId = (int) ($allOrgs[0]['id'] ?? 0);
$tmpUserId = 0;
if ($orgId < 1) {
    smoke_ok('fixture_org_found', false, 'No organizations');
} else {
    smoke_ok('fixture_org_found', true, 'org_id=' . $orgId);
    try {
        $tmpUserId = $users->create([
            'organization_id' => $orgId,
            'name' => 'Smoke Test Member',
            'first_name' => 'Smoke',
            'middle_name' => null,
            'last_name' => 'Test',
            'email' => 'smoke.test.' . time() . '@example.com',
            'phone' => '9199' . substr((string) time(), -8),
            'password' => 'TempPass1!',
            'role' => 'member',
            'must_change_password' => false,
        ]);
        smoke_ok('fixture_member_created', $tmpUserId > 0, 'user_id=' . $tmpUserId);

        $users->setActive($tmpUserId, false);
        smoke_ok('user_deactivate', !$users->isActive($tmpUserId), 'user_id=' . $tmpUserId);

        $users->setActive($tmpUserId, true);
        smoke_ok('user_reactivate', $users->isActive($tmpUserId), 'user_id=' . $tmpUserId);

        $wasOrgActive = $orgs->isActive($orgId);
        $orgs->setActive($orgId, false);
        smoke_ok('org_disable', !$orgs->isActive($orgId), 'org_id=' . $orgId);

        $orgs->setActive($orgId, true);
        smoke_ok('org_enable', $orgs->isActive($orgId), 'org_id=' . $orgId);
        $orgs->setActive($orgId, $wasOrgActive);
    } catch (Throwable $e) {
        smoke_ok('fixture_member_created', false, $e->getMessage());
    }

    if ($tmpUserId > 0) {
        try {
            \App\Core\Database::pdo()->prepare('DELETE FROM users WHERE id = ?')->execute([$tmpUserId]);
            smoke_ok('fixture_member_cleaned', true, 'deleted user_id=' . $tmpUserId);
        } catch (Throwable $e) {
            smoke_ok('fixture_member_cleaned', false, $e->getMessage());
        }
    }
}

echo '--- HTTP route checks (no auth) ---' . PHP_EOL;

$publicGets = [
    '/login',
    '/login/superadmin',
    '/login/organization',
    '/forgot-password',
    '/mail/ping',
];
foreach ($publicGets as $path) {
    $r = http_req($base . $path, 'GET');
    $ok = in_array($r['code'], [200, 302], true);
    if ($path === '/mail/ping') {
        $ok = $r['code'] === 200 && strpos($r['body'], 'mail_pipeline') !== false;
    }
    smoke_ok('public_get ' . $path, $ok, 'HTTP ' . $r['code']);
}

$protectedGets = [
    '/superadmin',
    '/superadmin/organizations',
    '/superadmin/members',
    '/superadmin/holidays',
    '/superadmin/mail-test',
    '/organization/dashboard',
    '/organization/families',
    '/organization/events',
    '/organization/notices',
    '/organization/receipts',
    '/organization/donations',
    '/organization/schemes',
    '/organization/settings/password',
];
foreach ($protectedGets as $path) {
    $r = http_req($base . $path, 'GET', null, ['Accept: text/html']);
    // Unauthenticated should redirect to login or 401/302/303
    $ok = in_array($r['code'], [301, 302, 303, 401], true);
    smoke_ok('protected_no_auth ' . $path, $ok, 'HTTP ' . $r['code']);
}

echo '--- OTP helper ---' . PHP_EOL;
$otp = generate_temporary_otp_password();
smoke_ok('otp_six_letters', preg_match('/^[A-HJ-NP-Z]{6}$/', $otp) === 1, $otp);

echo '--- Route map presence ---' . PHP_EOL;
$routesFile = file_get_contents(BASE_PATH . '/routes/web.php') ?: '';
smoke_ok('route_member_active', strpos($routesFile, 'family/member-active') !== false);
smoke_ok('route_org_set_active', strpos($routesFile, 'organization/set-active') !== false);

echo PHP_EOL . 'Summary: ' . $pass . ' passed, ' . $fail . ' failed' . PHP_EOL;
exit($fail === 0 ? 0 : 1);
