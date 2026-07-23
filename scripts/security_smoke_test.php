<?php

declare(strict_types=1);

$base = 'http://localhost/szvs-tenant/public';
$cookieFile = sys_get_temp_dir() . '/szvs_csrf_cookies.txt';
@unlink($cookieFile);

function http_req(string $url, string $method = 'GET', ?string $body = null, array $headers = [], ?string $cookieFile = null): array
{
    $ch = curl_init($url);
    $hdrs = $headers;
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    if ($hdrs !== []) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
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

$results = [];

$r = http_req($base . '/organization/sadhvis/search?q=a', 'GET', null, ['Accept: application/json']);
$results[] = ['sadhvis_no_auth', $r['code'], $r['code'] === 401];

$r = http_req($base . '/organization/does-not-exist', 'GET', null, ['Accept: application/json']);
$results[] = ['unknown_org_path_no_auth', $r['code'], $r['code'] === 401];

$r = http_req($base . '/pwa/status', 'GET', null, ['Accept: application/json']);
$results[] = ['pwa_status_no_auth', $r['code'], $r['code'] === 401];

$r = http_req($base . '/login', 'POST', '{"identity":"x","password":"y"}', [
    'Accept: application/json',
    'Content-Type: application/json',
]);
$results[] = ['login_no_csrf', $r['code'], $r['code'] === 403 && strpos($r['body'], 'CSRF') !== false];

$loginPage = http_req($base . '/login', 'GET', null, [], $cookieFile);
preg_match('/name="csrf-token" content="([^"]+)"/', $loginPage['body'], $m);
$token = $m[1] ?? '';
$results[] = ['login_page_csrf_meta', $loginPage['code'], $token !== ''];

$r = http_req($base . '/login', 'POST', '{"identity":"x","password":"bad","login_as":"organization","org_code":"ZZZ"}', [
    'Accept: application/json',
    'Content-Type: application/json',
    'X-CSRF-Token: ' . $token,
], $cookieFile);
$results[] = ['login_with_csrf_bad_creds', $r['code'], in_array($r['code'], [401, 422], true)];

$sqli = http_req($base . '/organization/sadhvis/search?q=' . rawurlencode("1' OR '1'='1"), 'GET', null, ['Accept: application/json']);
$results[] = ['sqli_probe_no_auth', $sqli['code'], $sqli['code'] === 401];

$pass = 0;
$fail = 0;
foreach ($results as [$name, $code, $ok]) {
    $mark = $ok ? 'PASS' : 'FAIL';
    if ($ok) {
        $pass++;
    } else {
        $fail++;
    }
    echo sprintf("[%s] %s (HTTP %s)\n", $mark, $name, $code);
}
echo "\n{$pass} passed, {$fail} failed\n";
@unlink($cookieFile);
exit($fail > 0 ? 1 : 0);
