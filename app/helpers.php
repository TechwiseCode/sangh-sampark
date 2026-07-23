<?php

declare(strict_types=1);

use App\Core\Request;

/**
 * @param mixed $default
 * @return mixed
 */
function app_config(string $key, $default = null)
{
    static $cfg;
    if ($cfg === null) {
        $cfg = require BASE_PATH . '/config/app.php';
    }
    return $cfg[$key] ?? $default;
}

function daily_tithi_notifications_enabled(): bool
{
    return (bool) app_config('daily_tithi_notifications', true);
}

function scheduled_tithi_reminders_enabled(): bool
{
    return (bool) app_config('scheduled_tithi_reminders', true);
}

function calendar_day_notifications_enabled(): bool
{
    return (bool) app_config('calendar_day_notifications', true);
}

/** @return list<string> */
function committee_designation_keys(): array
{
    static $keys = null;
    if ($keys !== null) {
        return $keys;
    }
    $path = BASE_PATH . '/config/committee_designations.php';
    $rows = is_file($path) ? require $path : [];
    if (!is_array($rows)) {
        $rows = [];
    }
    $keys = [];
    foreach ($rows as $row) {
        $key = strtolower(trim((string) $row));
        if ($key !== '' && preg_match('/^[a-z0-9_]+$/', $key)) {
            $keys[] = $key;
        }
    }

    return $keys;
}

function normalize_committee_designation_key(?string $value): ?string
{
    $key = strtolower(trim((string) $value));
    if ($key === '' || !in_array($key, committee_designation_keys(), true)) {
        return null;
    }

    return $key;
}

function committee_designation_label(string $key, ?string $locale = null): string
{
    $normalized = normalize_committee_designation_key($key) ?? strtolower(trim($key));
    $langKey = 'committee.designation.' . $normalized;
    if ($locale !== null) {
        return t_for_locale($langKey, $locale);
    }

    return t($langKey);
}

/** @return array<string,string> key => label */
function committee_designation_options(?string $locale = null): array
{
    $options = [];
    foreach (committee_designation_keys() as $key) {
        $options[$key] = committee_designation_label($key, $locale);
    }

    return $options;
}

function normalize_tithi_match_key(string $tithi): string
{
    $tithi = trim($tithi);
    if ($tithi === '') {
        return '';
    }
    $tithi = preg_replace('/\s*\([^)]*\)\s*/', '', $tithi) ?? $tithi;
    $tithi = preg_replace('/\s+/', ' ', $tithi) ?? $tithi;

    return strtolower($tithi);
}

function app_name(): string
{
    return (string) app_config('app_name', 'SanghSampark');
}

function web_push_is_configured(): bool
{
    return web_push_package_available() && web_push_vapid_auth() !== null;
}

function web_push_package_available(): bool
{
    return class_exists(\Minishlink\WebPush\WebPush::class);
}

/** @return array{subject:string,publicKey:string,privateKey:string}|null */
function web_push_vapid_auth(): ?array
{
    $publicKey = trim((string) app_config('vapid_public_key', ''));
    $privateKey = trim((string) app_config('vapid_private_key', ''));
    $subject = trim((string) app_config('vapid_subject', ''));
    if ($publicKey === '' || $privateKey === '' || $subject === '') {
        return null;
    }

    return [
        'subject' => $subject,
        'publicKey' => $publicKey,
        'privateKey' => $privateKey,
    ];
}

function web_push_public_key(): string
{
    return trim((string) app_config('vapid_public_key', ''));
}

function web_push_setup_error(): ?string
{
    if (!is_file(BASE_PATH . '/vendor/autoload.php')) {
        return 'Composer vendor folder is missing on the server. Upload vendor/ or run: composer install --no-dev';
    }
    if (!web_push_package_available()) {
        return 'PHP package minishlink/web-push is not installed. Run composer install on the server (see composer.json).';
    }
    if (web_push_vapid_auth() === null) {
        return 'VAPID keys are missing in .env (VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY, VAPID_SUBJECT).';
    }

    return null;
}

function system_mail_from(): string
{
    return (string) app_config('mail_from', 'no-reply@sanghsampark.com');
}

/** @return array<string,mixed> */
function mail_config(): array
{
    $cfg = app_config('mail');
    if (!is_array($cfg)) {
        return [];
    }

    return $cfg;
}

function smtp_mail_enabled(): bool
{
    $cfg = mail_config();

    return ($cfg['smtp_host'] ?? '') !== '' && ($cfg['smtp_user'] ?? '') !== '';
}

/**
 * Send a plain-text email. Uses Gmail SMTP when SMTP_HOST is set; otherwise PHP mail().
 */
function system_send_email(string $toEmail, string $subject, string $body): bool
{
    $toEmail = normalize_email($toEmail) ?? '';
    if ($toEmail === '' || !is_valid_email($toEmail)) {
        return false;
    }

    if (smtp_mail_enabled()) {
        if (system_send_email_smtp($toEmail, $subject, $body)) {
            return true;
        }

        return system_send_email_smtp($toEmail, $subject, $body);
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . system_mail_from(),
    ];

    return @mail($toEmail, $subject, $body, implode("\r\n", $headers));
}

function system_send_email_smtp(string $toEmail, string $subject, string $body, array $cfgOverrides = [], ?string &$lastError = null): bool
{
    $cfg = array_merge(mail_config(), $cfgOverrides);
    $host = (string) ($cfg['smtp_host'] ?? '');
    $user = (string) ($cfg['smtp_user'] ?? '');
    $pass = (string) ($cfg['smtp_pass'] ?? '');
    if ($host === '' || $user === '') {
        $lastError = 'SMTP host or user is not configured in .env';

        return false;
    }

    $port = (int) ($cfg['smtp_port'] ?? 587);
    $secure = strtolower(trim((string) ($cfg['smtp_secure'] ?? '')));
    if ($secure === '') {
        $secure = $port === 465 ? 'ssl' : 'tls';
    }

    $fromEmail = system_mail_from();
    if ($fromEmail === '' || !is_valid_email($fromEmail)) {
        $fromEmail = $user;
    }
    $fromName = trim((string) ($cfg['from_name'] ?? app_name()));
    if ($fromName === '') {
        $fromName = app_name();
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->Port = $port;
        $mail->Timeout = max(5, (int) ($cfg['smtp_timeout'] ?? 15));
        if ($secure === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($secure === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = false;
        }

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->isHTML(false);

        return $mail->send();
    } catch (Throwable $e) {
        $lastError = $e->getMessage();
        error_log('SMTP send failed to ' . $toEmail . ': ' . $lastError);

        return false;
    }
}

/** @return array<string,mixed> */
function mail_config_summary(): array
{
    $cfg = mail_config();
    $port = (int) ($cfg['smtp_port'] ?? 587);
    $secure = strtolower(trim((string) ($cfg['smtp_secure'] ?? '')));
    if ($secure === '') {
        $secure = $port === 465 ? 'ssl' : 'tls';
    }

    return [
        'smtp_enabled' => smtp_mail_enabled(),
        'from' => system_mail_from(),
        'from_name' => trim((string) ($cfg['from_name'] ?? app_name())),
        'host' => (string) ($cfg['smtp_host'] ?? ''),
        'port' => $port,
        'secure' => $secure,
        'user' => (string) ($cfg['smtp_user'] ?? ''),
        'pass_set' => trim((string) ($cfg['smtp_pass'] ?? '')) !== '',
    ];
}

/** Toggle subtle teal accents app-wide. Revert via SUBTLE_ACCENT=false in .env */
function subtle_accent_enabled(): bool
{
    return (bool) app_config('subtle_accent', true);
}

function subtle_accent_body_class(): string
{
    return subtle_accent_enabled() ? ' subtle-accent' : '';
}

/** Member → admin session chat module. Disable via MEMBER_ADMIN_CHAT=false in .env */
function member_admin_chat_enabled(): bool
{
    return (bool) app_config('member_admin_chat', true);
}

function page_title(string $section): string
{
    $section = trim($section);

    return $section === '' ? app_name() : app_name() . ' | ' . $section;
}

/**
 * Whether the incoming request is served over HTTPS (direct or behind a reverse proxy).
 */
function request_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    $proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($proto === 'https') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
        return true;
    }
    return false;
}

function cookie_path(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if ($dir === '' || $dir === '.') {
        return '/';
    }

    return $dir . '/';
}

/** Web path to the public front controller directory, e.g. /sanghsampark/public */
function pwa_public_path(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $dir = dirname($script);
    if ($dir === '/' || $dir === '\\' || $dir === '.') {
        return '';
    }

    return rtrim($dir, '/');
}

/** PWA base URL from the live request (host + public path). Prefer over APP_URL for install scope. */
function pwa_web_base_url(): string
{
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return rtrim(base_url(), '/');
    }
    $scheme = request_is_https() ? 'https' : 'http';
    $path = pwa_public_path();

    return $scheme . '://' . $host . ($path === '' ? '' : $path);
}

function pwa_service_worker_path(): string
{
    $path = pwa_public_path();

    return ($path === '' ? '' : $path) . '/service-worker.js';
}

function pwa_service_worker_scope(): string
{
    $path = pwa_public_path();

    return ($path === '' ? '/' : $path . '/');
}

/**
 * Detect base URL for the SaaS public entry (no trailing slash).
 */
function base_url(): string
{
    $configured = (string) app_config('base_url', '');
    if ($configured !== '') {
        return rtrim($configured, '/');
    }
    $scheme = request_is_https() ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    return $scheme . '://' . $host . ($dir === '' || $dir === '.' ? '' : $dir);
}

/**
 * Parent web path for shared theme assets (/themes).
 */
function asset_base_url(): string
{
    $configured = (string) app_config('asset_base_url', '');
    if ($configured !== '') {
        return rtrim($configured, '/');
    }
    $base = base_url();
    if (substr($base, -strlen('/public')) === '/public') {
        return substr($base, 0, -strlen('/public'));
    }
    return dirname($base);
}

function asset_url(string $path): string
{
    $path = ltrim($path, '/');
    return asset_base_url() . '/' . $path;
}

function redirect(string $to): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    header('Location: ' . $to, true, 302);
    exit;
}

/** Release session lock before slow work (DB, mail). Call resume_session_for_flash() before flash_set(). */
function release_session_lock(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

function resume_session_for_flash(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/**
 * Whether a request looks like an XHR/fetch expecting JSON (not a full page navigation).
 */
function request_wants_json(?string $accept = null, ?string $contentType = null): bool
{
    $accept = $accept ?? (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    $contentType = $contentType ?? (string) ($_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? ''));
    $xhr = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));

    return strpos($accept, 'application/json') !== false
        || strpos($contentType, 'application/json') !== false
        || $xhr === 'xmlhttprequest';
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf']) || strlen($_SESSION['_csrf']) < 32) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_token_valid(?string $token): bool
{
    if ($token === null || $token === '') {
        return false;
    }
    $expected = csrf_token();

    return hash_equals($expected, $token);
}

/** Hidden input for HTML forms. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function client_ip(): string
{
    $candidates = [
        (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''),
        (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''),
        (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
    ];
    foreach ($candidates as $raw) {
        $raw = trim($raw);
        if ($raw === '') {
            continue;
        }
        if (strpos($raw, ',') !== false) {
            $raw = trim(explode(',', $raw, 2)[0]);
        }
        if (filter_var($raw, FILTER_VALIDATE_IP)) {
            return $raw;
        }
    }

    return '0.0.0.0';
}

/**
 * Simple file-backed rate limiter. Returns true when the caller should be blocked.
 */
function rate_limit_too_many(string $bucket, int $maxAttempts, int $windowSeconds): bool
{
    $maxAttempts = max(1, $maxAttempts);
    $windowSeconds = max(1, $windowSeconds);
    $dir = BASE_PATH . '/storage/rate_limits';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $bucket) ?: 'bucket';
    $file = $dir . '/' . $safe . '.json';
    $now = time();
    $hits = [];
    if (is_file($file)) {
        $decoded = json_decode((string) @file_get_contents($file), true);
        if (is_array($decoded)) {
            foreach ($decoded as $ts) {
                $t = (int) $ts;
                if ($t > ($now - $windowSeconds)) {
                    $hits[] = $t;
                }
            }
        }
    }
    if (count($hits) >= $maxAttempts) {
        return true;
    }
    $hits[] = $now;
    @file_put_contents($file, json_encode($hits), LOCK_EX);

    return false;
}

function rate_limit_clear(string $bucket): void
{
    $dir = BASE_PATH . '/storage/rate_limits';
    $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $bucket) ?: 'bucket';
    $file = $dir . '/' . $safe . '.json';
    if (is_file($file)) {
        @unlink($file);
    }
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: frame-ancestors 'self'");
}

/** Prevent browsers from serving stale HTML with an old CSRF token. */
function send_html_cache_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

/** @return list<string> */
function session_deploy_cookie_paths(): array
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $publicDir = rtrim(dirname($script), '/');
    $paths = ['/'];
    if ($publicDir !== '' && $publicDir !== '.' && $publicDir !== '/') {
        $paths[] = $publicDir . '/';
        if (preg_match('#/public$#', $publicDir)) {
            $parent = substr($publicDir, 0, -strlen('/public'));
            if ($parent !== '' && $parent !== '/') {
                $paths[] = $parent . '/';
            }
        }
    }

    return array_values(array_unique($paths));
}

function session_active_cookie_path(): string
{
    $params = session_get_cookie_params();
    $path = (string) ($params['path'] ?? '/');
    if ($path === '') {
        return '/';
    }
    if ($path !== '/' && substr($path, -1) !== '/') {
        $path .= '/';
    }

    return $path;
}

/** Drop duplicate session cookies left from older deploy paths. */
function session_clear_stale_cookies(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $active = session_active_cookie_path();
    $name = session_name();
    $params = session_get_cookie_params();
    $domain = (string) ($params['domain'] ?? '');
    $secure = (bool) ($params['secure'] ?? false);
    $httponly = (bool) ($params['httponly'] ?? true);
    foreach (session_deploy_cookie_paths() as $path) {
        if ($path === $active) {
            continue;
        }
        setcookie($name, '', time() - 42000, $path, $domain, $secure, $httponly);
    }
}

/** Expire session cookie on every path this app may have used. */
function session_destroy_all_cookies(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $name = session_name();
    $params = session_get_cookie_params();
    $domain = (string) ($params['domain'] ?? '');
    $secure = (bool) ($params['secure'] ?? false);
    $httponly = (bool) ($params['httponly'] ?? true);
    foreach (session_deploy_cookie_paths() as $path) {
        setcookie($name, '', time() - 42000, $path, $domain, $secure, $httponly);
    }
}

/**
 * Paths that return JSON / are not safe post-login page destinations.
 */
function is_post_login_api_path(string $path): bool
{
    $path = strtolower((string) (parse_url($path, PHP_URL_PATH) ?: $path));
    $path = rtrim($path, '/');
    // Strip app base path segments until /organization or /superadmin.
    if (preg_match('#(/organization|/superadmin|/family)(/.*)?$#', $path, $m)) {
        $path = $m[1] . ($m[2] ?? '');
    }

    $apiExact = [
        '/organization/notifications/list',
        '/organization/notifications/preview',
        '/organization/notifications/push/vapid-public-key',
        '/organization/notifications/push/status',
        '/organization/notifications/broadcast/recipients',
        '/organization/check-email',
        '/organization/check-phone',
        '/organization/pincode-lookup',
        '/organization/resolve-identity',
        '/organization/event/pass-search',
        '/organization/sadhvis/search',
        '/organization/calendar/feed',
        '/organization/member-chat/messages',
        '/organization/member-chat/send',
        '/family/list',
        '/family/details',
    ];
    if (in_array($path, $apiExact, true)) {
        return true;
    }

    return (bool) preg_match(
        '#^/organization/(notifications/(mark-read|broadcast|push/)|membership-request/|member-chat/)#',
        $path
    );
}

/**
 * Relative path (+ optional query) safe to send users after login.
 */
function sanitize_post_login_intended_url(?string $url): ?string
{
    if ($url === null) {
        return null;
    }
    $url = trim($url);
    if ($url === '' || strpos($url, '//') === 0 || preg_match('#^https?:#i', $url)) {
        return null;
    }
    if ($url[0] !== '/') {
        return null;
    }
    $path = parse_url($url, PHP_URL_PATH);
    if (!is_string($path) || $path === '' || strpos($path, '/login') !== false) {
        return null;
    }
    if (is_post_login_api_path($path)) {
        return null;
    }

    return $url;
}

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    echo $json !== false ? $json : '{"ok":false,"error":"encoding"}';
    exit;
}

/** @return array<string,mixed> */
function pwa_manifest(): array
{
    $base = pwa_web_base_url();
    $root = $base . '/';

    return [
        'name' => app_name(),
        'short_name' => app_name(),
        'description' => 'Jain Upashray/Sangh and community management',
        'id' => $root,
        'start_url' => $base . '/login/organization?source=homescreen',
        'scope' => $root,
        'display' => 'standalone',
        'display_override' => ['standalone', 'minimal-ui'],
        'orientation' => 'portrait-primary',
        'prefer_related_applications' => false,
        'background_color' => '#ffffff',
        'theme_color' => '#34B1AA',
        'lang' => current_locale() === 'gu' ? 'gu' : 'en',
        'dir' => 'ltr',
        'categories' => ['productivity', 'utilities'],
        'icons' => [
            [
                'src' => $base . '/icons/icon-192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => $base . '/icons/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
        ],
    ];
}

/** @return array<string,mixed> */
function pwa_icon_checks(): array
{
    $base = pwa_web_base_url();
    $checks = [];
    foreach (['icon-192.png' => 192, 'icon-512.png' => 512] as $file => $expected) {
        $diskPath = BASE_PATH . '/public/icons/' . $file;
        $url = $base . '/icons/' . $file;
        $entry = [
            'url' => $url,
            'disk_path' => $diskPath,
            'exists_on_disk' => is_file($diskPath),
        ];
        if ($entry['exists_on_disk'] && function_exists('getimagesize')) {
            $info = @getimagesize($diskPath);
            $w = (int) ($info[0] ?? 0);
            $h = (int) ($info[1] ?? 0);
            $entry['width'] = $w;
            $entry['height'] = $h;
            $entry['valid_size'] = $w === $expected && $h === $expected;
        } else {
            $entry['valid_size'] = false;
        }
        $checks[$file] = $entry;
    }

    return $checks;
}

/** @return array<string,mixed> */
function pwa_status_payload(): array
{
    $pwaBase = pwa_web_base_url();
    $configured = rtrim(base_url(), '/');
    $icons = pwa_icon_checks();
    $iconsOk = true;
    foreach ($icons as $icon) {
        if (empty($icon['exists_on_disk']) || empty($icon['valid_size'])) {
            $iconsOk = false;
            break;
        }
    }

    $payload = [
        'ok' => true,
        'installable' => $iconsOk,
        'icons_ok' => $iconsOk,
        'secure' => request_is_https(),
        'bases_match' => $configured === rtrim($pwaBase, '/'),
        'manifest_url' => $pwaBase . '/manifest.json',
        'service_worker_url' => $pwaBase . '/service-worker.js',
        'service_worker_scope' => pwa_service_worker_scope(),
    ];
    // Disk paths only for authenticated superadmin / debug — never public.
    if (user_is_superadmin(current_user()) || (string) app_config('env', 'production') === 'development') {
        $payload['icons'] = $icons;
        $payload['pwa_base'] = $pwaBase;
        $payload['configured_base'] = $configured;
        $payload['public_path'] = pwa_public_path();
        $payload['service_worker_path'] = pwa_service_worker_path();
    }

    return $payload;
}

function pwa_manifest_response(): void
{
    header('Content-Type: application/manifest+json; charset=utf-8');
    header('Cache-Control: public, max-age=300');
    header('Access-Control-Allow-Origin: *');
    $json = json_encode(pwa_manifest(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo $json !== false ? $json : '{}';
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function set_current_user(?array $user): void
{
    if ($user === null) {
        unset($_SESSION['user']);
        return;
    }
    $_SESSION['user'] = $user;
}

function current_organization_id(): ?int
{
    if (!isset($_SESSION['current_organization_id'])) {
        return null;
    }
    $id = (int) $_SESSION['current_organization_id'];

    return $id > 0 ? $id : null;
}

function set_current_organization_id(?int $id): void
{
    if ($id === null || $id < 1) {
        unset($_SESSION['current_organization_id']);

        return;
    }
    $_SESSION['current_organization_id'] = $id;
}

/** Active organization for org portal (session, then logged-in user's home org). */
function organization_id(): int
{
    $sessionOrg = current_organization_id();
    if ($sessionOrg !== null && $sessionOrg > 0) {
        return $sessionOrg;
    }
    $user = current_user();
    if ($user !== null) {
        $home = (int) ($user['organization_id'] ?? 0);
        if ($home > 0) {
            set_current_organization_id($home);

            return $home;
        }
    }
    $fromConfig = (int) app_config('organization_id', 0);
    if ($fromConfig > 0) {
        return $fromConfig;
    }

    return 0;
}

function is_control_plane(): bool
{
    return (string) app_config('app_mode', 'control_plane') === 'control_plane';
}

/** Platform superadmin for this deploy (manages org admins). */
function user_is_superadmin(?array $user): bool
{
    return $user !== null && ($user['role'] ?? '') === 'superadmin';
}

function require_login_web(): void
{
    if (current_user() === null) {
        redirect(base_url() . '/login');
    }
}

function normalize_phone(?string $phone): ?string
{
    if ($phone === null || trim($phone) === '') {
        return null;
    }
    $digits = preg_replace('/\D+/', '', $phone);
    return $digits !== '' ? $digits : null;
}

function normalize_email(?string $email): ?string
{
    if ($email === null) {
        return null;
    }
    $email = trim($email);
    if ($email === '') {
        return null;
    }

    return strtolower($email);
}

function normalize_identity(string $identity): string
{
    $identity = trim($identity);
    if ($identity === '') {
        return '';
    }
    if (strpos($identity, '@') !== false) {
        return strtolower($identity);
    }

    return $identity;
}

/** @return list<string> */
function blood_group_options(): array
{
    return ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'];
}

function normalize_blood_group(?string $value): ?string
{
    $v = trim((string) $value);
    if ($v === '') {
        return null;
    }
    if (strcasecmp($v, 'Unknown') === 0) {
        return 'Unknown';
    }
    $upper = strtoupper($v);

    return in_array($upper, ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true) ? $upper : null;
}

function is_valid_blood_group(?string $value): bool
{
    return normalize_blood_group($value) !== null;
}

/** @return list<string> */
function gender_options(): array
{
    return ['Male', 'Female', 'Other'];
}

function normalize_gender(?string $value): ?string
{
    $v = trim((string) $value);
    if ($v === '') {
        return null;
    }
    foreach (gender_options() as $opt) {
        if (strcasecmp($v, $opt) === 0) {
            return $opt;
        }
    }

    return null;
}

function is_valid_gender(?string $value): bool
{
    return normalize_gender($value) !== null;
}

function normalize_member_profession_filter(?string $value): ?string
{
    return normalize_profession_type($value);
}

/** @return list<string> */
function profession_type_options(): array
{
    return ['job', 'business', 'homemaker', 'professional', 'student', 'retired'];
}

function normalize_profession_type(?string $value): ?string
{
    $v = strtolower(trim((string) $value));

    return in_array($v, profession_type_options(), true) ? $v : null;
}

/**
 * Prefixed company website for storage (https://…). Empty input → null.
 */
function normalize_company_website(?string $value): ?string
{
    $raw = trim((string) $value);
    if ($raw === '') {
        return null;
    }
    $raw = preg_replace('#^https?://#i', '', $raw) ?? $raw;
    $raw = preg_replace('#^//#', '', $raw) ?? $raw;
    $raw = trim($raw);
    if ($raw === '' || strpos($raw, ' ') !== false) {
        return null;
    }
    $url = 'https://' . $raw;
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return null;
    }

    return $url;
}

function is_valid_profession_type(?string $value): bool
{
    return normalize_profession_type($value) !== null;
}

function profession_type_lang_key(string $type): string
{
    switch (normalize_profession_type($type) ?? '') {
        case 'job':
            return 'profile.profession_job';
        case 'business':
            return 'profile.profession_business';
        case 'homemaker':
            return 'profile.profession_homemaker';
        case 'professional':
            return 'profile.profession_professional';
        case 'student':
            return 'profile.profession_student';
        case 'retired':
            return 'profile.profession_retired';
        default:
            return 'profile.profession_type';
    }
}

function profession_type_label(string $type): string
{
    $normalized = normalize_profession_type($type);

    return $normalized !== null ? t(profession_type_lang_key($normalized)) : '';
}

function occupation_from_profession_type(string $professionType): string
{
    switch (normalize_profession_type($professionType) ?? '') {
        case 'job':
            return 'Job';
        case 'business':
            return 'Business';
        case 'homemaker':
            return 'Homemaker';
        case 'professional':
            return 'Professional';
        case 'student':
            return 'Student';
        case 'retired':
            return 'Retired';
        default:
            return 'Other';
    }
}

/** @param array<string,mixed> $row */
function profession_type_label_from_row(array $row): ?string
{
    $type = normalize_profession_type((string) ($row['profile_profession_type'] ?? $row['profession_type'] ?? ''));
    if ($type !== null) {
        return profession_type_label($type);
    }
    $occupation = trim((string) ($row['profile_occupation'] ?? $row['occupation'] ?? ''));
    if ($occupation === '') {
        return null;
    }
    $legacyMap = [
        'Job' => 'job',
        'Business' => 'business',
        'Homemaker' => 'homemaker',
        'Professional' => 'professional',
        'Student' => 'student',
        'Retired' => 'retired',
    ];
    if (isset($legacyMap[$occupation])) {
        return profession_type_label($legacyMap[$occupation]);
    }

    return $occupation;
}

/** @return list<string> */
function member_age_range_filter_keys(): array
{
    return ['0-18', '19-25', '26-35', '36-45', '46-55', '56-65', '66+'];
}

function normalize_member_age_range_filter(?string $value): ?string
{
    $v = trim((string) $value);
    if ($v === '' || $v === 'all') {
        return null;
    }

    return in_array($v, member_age_range_filter_keys(), true) ? $v : null;
}

/**
 * @param mixed $value
 * @return list<string>
 */
function normalize_member_age_range_filters($value): array
{
    $raw = [];
    if (is_array($value)) {
        $raw = $value;
    } elseif (is_string($value) && $value !== '' && $value !== 'all') {
        $raw = [$value];
    }
    $selected = [];
    foreach ($raw as $item) {
        $normalized = normalize_member_age_range_filter((string) $item);
        if ($normalized !== null && !in_array($normalized, $selected, true)) {
            $selected[] = $normalized;
        }
    }

    return $selected;
}

/** @return list<string> */
function member_donation_filter_keys(): array
{
    return ['all', 'donors', 'non_donors'];
}

function normalize_member_donation_filter(?string $value): ?string
{
    $v = strtolower(trim((string) $value));
    if ($v === '' || $v === 'all') {
        return null;
    }

    return in_array($v, ['donors', 'non_donors'], true) ? $v : null;
}

function member_donation_filter_lang_key(string $key): string
{
    if ($key === 'donors') {
        return 'members.filter_donation_donors';
    }
    if ($key === 'non_donors') {
        return 'members.filter_donation_non_donors';
    }

    return 'members.filter_all_short';
}

/** @return array{0:int,1:int}|null */
function member_age_range_bounds(string $key): ?array
{
    switch ($key) {
        case '0-18':
            return [0, 18];
        case '19-25':
            return [19, 25];
        case '26-35':
            return [26, 35];
        case '36-45':
            return [36, 45];
        case '46-55':
            return [46, 55];
        case '56-65':
            return [56, 65];
        case '66+':
            return [66, 150];
        default:
            return null;
    }
}

function member_age_range_lang_key(string $key): string
{
    switch ($key) {
        case '0-18':
            return 'members.age_range_0_18';
        case '19-25':
            return 'members.age_range_19_25';
        case '26-35':
            return 'members.age_range_26_35';
        case '36-45':
            return 'members.age_range_36_45';
        case '46-55':
            return 'members.age_range_46_55';
        case '56-65':
            return 'members.age_range_56_65';
        case '66+':
            return 'members.age_range_66_plus';
        default:
            return 'members.filter_age';
    }
}

function age_years_from_dob(?string $dob): ?int
{
    $dob = trim((string) $dob);
    if ($dob === '' || strtotime($dob) === false) {
        return null;
    }

    return (new DateTimeImmutable($dob))->diff(new DateTimeImmutable('today'))->y;
}

/**
 * @param array<string,mixed> $input
 * @return array{
 *   heads_only:bool,
 *   gender:?string,
 *   profession:?string,
 *   age_ranges:list<string>,
 *   donation:?string,
 *   storage:array{filter:string,gender:string,profession:string,age:list<string>,donation:string>}
 * }
 */
function parse_member_directory_filters_from_input(array $input): array
{
    $filter = (string) ($input['filter'] ?? 'all');
    $headsOnly = $filter === 'heads';
    $genderRaw = (string) ($input['gender'] ?? 'all');
    $professionRaw = (string) ($input['profession'] ?? 'all');
    $donationRaw = (string) ($input['donation'] ?? 'all');
    $ageRaw = $input['age'] ?? [];
    if (!is_array($ageRaw)) {
        $ageRaw = $ageRaw === '' || $ageRaw === 'all' ? [] : [(string) $ageRaw];
    }
    $gender = normalize_gender($genderRaw === 'all' ? '' : $genderRaw);
    $profession = normalize_member_profession_filter($professionRaw === 'all' ? '' : $professionRaw);
    $donation = normalize_member_donation_filter($donationRaw === 'all' ? '' : $donationRaw);
    $ageRanges = normalize_member_age_range_filters($ageRaw);
    $storage = [
        'filter' => $headsOnly ? 'heads' : 'all',
        'gender' => $gender ?? 'all',
        'profession' => $profession ?? 'all',
        'donation' => $donation ?? 'all',
        'age' => $ageRanges,
    ];

    return [
        'heads_only' => $headsOnly,
        'gender' => $gender,
        'profession' => $profession,
        'donation' => $donation,
        'age_ranges' => $ageRanges,
        'storage' => $storage,
    ];
}

/**
 * @param array{heads_only?:bool,gender?:?string,profession?:?string,donation?:?string,age_ranges?:list<string>} $parsed
 * @return array{heads_only:bool,gender?:?string,profession?:?string,donation?:?string,age_ranges?:list<string>}
 */
function member_directory_filters_for_model(array $parsed): array
{
    $directoryFilters = ['heads_only' => !empty($parsed['heads_only'])];
    if (isset($parsed['gender']) && $parsed['gender'] !== null) {
        $directoryFilters['gender'] = $parsed['gender'];
    }
    if (isset($parsed['profession']) && $parsed['profession'] !== null) {
        $directoryFilters['profession'] = $parsed['profession'];
    }
    if (isset($parsed['donation']) && $parsed['donation'] !== null) {
        $directoryFilters['donation'] = $parsed['donation'];
    }
    if (isset($parsed['age_ranges']) && is_array($parsed['age_ranges']) && $parsed['age_ranges'] !== []) {
        $directoryFilters['age_ranges'] = $parsed['age_ranges'];
    }

    return $directoryFilters;
}

/** @param array{filter?:string,gender?:string,profession?:string,donation?:string,age?:list<string>} $storage */
function member_directory_filters_summary(array $storage): string
{
    $parts = [];
    $parts[] = (($storage['filter'] ?? 'all') === 'heads') ? t('members.filter_heads') : t('members.filter_all');
    $gender = (string) ($storage['gender'] ?? 'all');
    if ($gender !== 'all' && $gender !== '') {
        if ($gender === 'Male') {
            $parts[] = t('profile.gender_male');
        } elseif ($gender === 'Female') {
            $parts[] = t('profile.gender_female');
        } elseif ($gender === 'Other') {
            $parts[] = t('profile.gender_other');
        }
    }
    $profession = (string) ($storage['profession'] ?? 'all');
    if ($profession !== 'all' && $profession !== '') {
        $parts[] = profession_type_label($profession);
    }
    $donation = (string) ($storage['donation'] ?? 'all');
    if ($donation === 'donors') {
        $parts[] = t('members.filter_donation_donors');
    } elseif ($donation === 'non_donors') {
        $parts[] = t('members.filter_donation_non_donors');
    }
    $ageKeys = isset($storage['age']) && is_array($storage['age']) ? $storage['age'] : [];
    if ($ageKeys !== []) {
        $ageLabels = [];
        foreach ($ageKeys as $ageKey) {
            $ageLabels[] = t(member_age_range_lang_key((string) $ageKey));
        }
        $parts[] = t('members.filter_age') . ': ' . implode(', ', $ageLabels);
    }
    $selectedCount = isset($storage['selected_count']) ? (int) $storage['selected_count'] : 0;
    $filteredCount = isset($storage['filtered_count']) ? (int) $storage['filtered_count'] : 0;
    if ($selectedCount > 0 && $filteredCount > 0 && $selectedCount < $filteredCount) {
        $parts[] = t('notifications.broadcast_selected_of_filtered', [
            'selected' => $selectedCount,
            'total' => $filteredCount,
        ]);
    }

    return implode(' · ', $parts);
}

function member_directory_filters_summary_from_json(?string $json): string
{
    $json = trim((string) $json);
    if ($json === '') {
        return t('members.filter_all');
    }
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return $json;
    }

    return member_directory_filters_summary($data);
}

function member_directory_age_filter_summary(array $ageFilters): string
{
    if ($ageFilters === []) {
        return t('members.filter_all_short');
    }
    if (count($ageFilters) === 1) {
        return t(member_age_range_lang_key($ageFilters[0]));
    }

    return t('members.filter_age_n_selected', ['count' => count($ageFilters)]);
}

/** @return array{id:int,name:string,code:string,age:?int,gender:?string,profession:?string,is_head:bool} */
function format_member_directory_recipient_json(array $row): array
{
    $gender = trim((string) ($row['profile_gender'] ?? ''));
    $genderLabel = null;
    if ($gender === 'Male') {
        $genderLabel = t('profile.gender_male');
    } elseif ($gender === 'Female') {
        $genderLabel = t('profile.gender_female');
    } elseif ($gender === 'Other') {
        $genderLabel = t('profile.gender_other');
    }

    return [
        'id' => (int) ($row['user_id'] ?? 0),
        'name' => user_display_name($row),
        'code' => (string) (($row['full_member_code'] ?? '') ?: ''),
        'age' => age_years_from_dob((string) ($row['profile_dob'] ?? '')),
        'gender' => $genderLabel,
        'profession' => profession_type_label_from_row($row),
        'is_head' => !empty($row['is_family_head']),
    ];
}

/** @return list<string> */
function marital_status_options(): array
{
    return ['Single', 'Married', 'Widowed', 'Divorced'];
}

function normalize_marital_status(?string $value): ?string
{
    return normalize_marital_status_import($value, null);
}

function is_valid_marital_status(?string $value): bool
{
    return normalize_marital_status($value) !== null;
}

/** @param array<string,mixed> $profileRow */
function profile_marital_status_from_row(array $profileRow): string
{
    $status = trim((string) ($profileRow['marital_status'] ?? ''));
    if ($status !== '' && is_valid_marital_status($status)) {
        return $status;
    }
    if (array_key_exists('is_married', $profileRow) && $profileRow['is_married'] !== null && $profileRow['is_married'] !== '') {
        return (int) $profileRow['is_married'] === 1 ? 'Married' : 'Single';
    }

    return '';
}

function is_married_flag_from_marital_status(string $maritalStatus): int
{
    return $maritalStatus === 'Married' ? 1 : 0;
}

/**
 * Combine house number + street line for display / print (not for DB storage).
 */
function profile_address_street_display(?string $houseNumber, ?string $addressLine1): string
{
    $house = trim((string) $houseNumber);
    $line1 = trim((string) $addressLine1);
    if ($house === '') {
        return $line1;
    }
    if ($line1 === '') {
        return $house;
    }

    return $house . ', ' . $line1;
}

/** @deprecated Use profile_address_street_display(); fields are stored separately. */
function profile_address_line1_for_storage(?string $houseNumber, ?string $addressLine1): string
{
    return profile_address_street_display($houseNumber, $addressLine1);
}

function normalize_marital_status_import(?string $raw, ?string $legacyIsMarried = null): ?string
{
    $raw = trim((string) $raw);
    if ($raw !== '') {
        foreach (marital_status_options() as $option) {
            if (strcasecmp($raw, $option) === 0) {
                return $option;
            }
        }
        $aliases = [
            'unmarried' => 'Single',
            'single' => 'Single',
            'married' => 'Married',
            'widowed' => 'Widowed',
            'divorced' => 'Divorced',
            'separated' => 'Divorced',
        ];
        $key = strtolower($raw);
        if (isset($aliases[$key])) {
            return $aliases[$key];
        }
    }
    $legacy = trim((string) $legacyIsMarried);
    if ($legacy === '1') {
        return 'Married';
    }
    if ($legacy === '0') {
        return 'Single';
    }

    return null;
}

/** @return array<string,string> */
function platform_holiday_category_options(): array
{
    return [
        'holiday' => t('holidays.category_holiday'),
        'paryushan' => t('holidays.category_paryushan'),
        'religious' => t('holidays.category_religious'),
    ];
}

function normalize_platform_holiday_category(?string $value): string
{
    $key = strtolower(trim((string) $value));
    if (isset(platform_holiday_category_options()[$key])) {
        return $key;
    }

    return 'religious';
}

function platform_holiday_display_title(array $row): string
{
    if (current_locale() === 'gu') {
        $gu = trim((string) ($row['title_gu'] ?? ''));
        if ($gu !== '') {
            return $gu;
        }
    }

    return trim((string) ($row['title'] ?? ''));
}

function platform_holiday_category_label(string $category): string
{
    $options = platform_holiday_category_options();

    return $options[$category] ?? $options['religious'];
}

/** @return array<string,string> */
function org_calendar_day_category_options(): array
{
    return array_merge(platform_holiday_category_options(), [
        'vyakhyan' => t('calendar_days.org.category_vyakhyan'),
        'pratikraman' => t('calendar_days.org.category_pratikraman'),
        'other' => t('calendar_days.org.category_other'),
    ]);
}

function org_calendar_day_shows_event_time(string $category): bool
{
    return in_array($category, ['vyakhyan', 'pratikraman'], true);
}

function normalize_org_calendar_event_time(?string $raw): ?string
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return null;
    }
    if (preg_match('/^\d{2}:\d{2}$/', $raw)) {
        return $raw . ':00';
    }
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $raw)) {
        return $raw;
    }

    return null;
}

function format_org_calendar_event_time(?string $time): string
{
    if ($time === null || trim($time) === '') {
        return '';
    }
    $raw = trim($time);
    try {
        return (new \DateTimeImmutable($raw))->format('g:i A');
    } catch (\Exception $e) {
        if (preg_match('/^(\d{2}):(\d{2})/', $raw, $m)) {
            return (new \DateTimeImmutable('1970-01-01 ' . $m[1] . ':' . $m[2] . ':00'))->format('g:i A');
        }

        return $raw;
    }
}

function org_calendar_event_time_input_value(?string $time): string
{
    if ($time === null || trim($time) === '') {
        return '';
    }
    if (preg_match('/^(\d{2}:\d{2})/', trim($time), $m)) {
        return $m[1];
    }

    return '';
}

function normalize_org_calendar_day_category(?string $value): string
{
    $key = strtolower(trim((string) $value));
    if (isset(org_calendar_day_category_options()[$key])) {
        return $key;
    }

    return 'other';
}

function org_calendar_day_category_label(string $category): string
{
    $options = org_calendar_day_category_options();

    return $options[$category] ?? $options['other'];
}

function org_calendar_day_category_label_for_locale(string $category, string $locale): string
{
    $keys = [
        'holiday' => 'holidays.category_holiday',
        'paryushan' => 'holidays.category_paryushan',
        'religious' => 'holidays.category_religious',
        'vyakhyan' => 'calendar_days.org.category_vyakhyan',
        'pratikraman' => 'calendar_days.org.category_pratikraman',
        'other' => 'calendar_days.org.category_other',
    ];
    $key = $keys[$category] ?? $keys['other'];

    return t_for_locale($key, $locale);
}

function normalize_panchang_csv_header(string $header): string
{
    $key = strtolower(trim($header));
    $key = str_replace(['/', ' '], '_', $key);
    $key = preg_replace('/_+/', '_', $key) ?? $key;

    return trim($key, '_');
}

function parse_panchang_gregorian_date(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '' || preg_match('/^\d{3,4}$/', $raw)) {
        return null;
    }
    foreach (['d-M-Y', 'd-m-Y', 'Y-m-d', 'd/m/Y', 'm/d/Y'] as $format) {
        $dt = \DateTime::createFromFormat($format, $raw);
        if ($dt instanceof \DateTime) {
            $errors = \DateTime::getLastErrors();
            if (is_array($errors) && ($errors['warning_count'] ?? 0) === 0 && ($errors['error_count'] ?? 0) === 0) {
                return $dt->format('Y-m-d');
            }
        }
    }
    $ts = strtotime($raw);

    return $ts !== false ? date('Y-m-d', $ts) : null;
}

/** @param list<string|null> $csvRow */
function panchang_csv_row_offset(array $csvRow, int $dateIdx): int
{
    $raw = trim((string) ($csvRow[$dateIdx] ?? ''));
    if ($raw !== '' && parse_panchang_gregorian_date($raw) !== null) {
        return 0;
    }
    $alt = trim((string) ($csvRow[$dateIdx + 1] ?? ''));
    if ($alt !== '' && parse_panchang_gregorian_date($alt) !== null) {
        return 1;
    }

    return 0;
}

/** @param array<string,mixed> $row */
function panchang_day_summary(array $row): string
{
    $parts = [];
    $month = trim((string) ($row['gujarati_month'] ?? ''));
    $paksha = trim((string) ($row['paksha'] ?? ''));
    $tithi = trim((string) ($row['tithi'] ?? ''));
    if ($month !== '') {
        $parts[] = $month;
    }
    if ($paksha !== '') {
        $parts[] = $paksha;
    }
    if ($tithi !== '') {
        $parts[] = $tithi;
    }

    return implode(' · ', $parts);
}

/** @param array<string,mixed> $row */
function panchang_day_short_label(array $row): string
{
    $month = trim((string) ($row['gujarati_month'] ?? ''));
    $paksha = trim((string) ($row['paksha'] ?? ''));
    $tithi = trim((string) ($row['tithi'] ?? ''));
    if ($tithi === '') {
        return '';
    }
    $shortTithi = preg_replace('/\s*\([^)]*\)\s*/', '', $tithi) ?? $tithi;
    $parts = [];
    if ($month !== '') {
        $parts[] = $month;
    }
    if ($paksha !== '') {
        $parts[] = $paksha;
    }
    $parts[] = $shortTithi;

    return implode(' ', $parts);
}

function panchang_festival_notes_for_display(?string $notes): ?string
{
    $notes = trim((string) $notes);
    if ($notes === '') {
        return null;
    }
    if (preg_match('/tithi\s+skipping\s+adjustment/i', $notes)) {
        return null;
    }

    return $notes;
}

function normalize_org_short_code(?string $value): string
{
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim((string) $value)) ?? '');
}

/** @return array{ok:bool, code?:string, error?:string} */
function validate_org_short_code(string $raw, ?int $excludeOrganizationId = null): array
{
    $code = normalize_org_short_code($raw);
    if ($code === '') {
        return ['ok' => false, 'error' => t('superadmin.organizations.error_short_code_required')];
    }
    $len = strlen($code);
    if ($len < 2 || $len > 12) {
        return ['ok' => false, 'error' => t('superadmin.organizations.error_short_code_length')];
    }
    if (!preg_match('/[A-Z]/', $code)) {
        return ['ok' => false, 'error' => t('superadmin.organizations.error_short_code_letters')];
    }
    if ((new \App\Models\Organization())->orgCodeIsTaken($code, $excludeOrganizationId)) {
        return ['ok' => false, 'error' => t('superadmin.organizations.error_short_code_taken')];
    }

    return ['ok' => true, 'code' => $code];
}

/**
 * Latin letters used in membership numbers (e.g. AJ → C12-AJ101).
 *
 * @return array{ok:bool, initials?:?string, error?:string}
 */
function validate_member_initials(string $raw, ?int $excludeOrganizationId = null, bool $required = false): array
{
    $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', trim($raw)) ?? '');
    if ($letters === '') {
        if ($required) {
            return ['ok' => false, 'error' => t('superadmin.organizations.error_member_initials_required')];
        }

        return ['ok' => true, 'initials' => null];
    }
    $len = strlen($letters);
    if ($len < 2 || $len > 4) {
        return ['ok' => false, 'error' => t('superadmin.organizations.error_member_initials_length')];
    }
    $svc = new \App\Services\MembershipCodeService();
    if ($svc->memberInitialsTaken($letters, $excludeOrganizationId)) {
        return ['ok' => false, 'error' => t('superadmin.organizations.error_member_initials_taken')];
    }

    return ['ok' => true, 'initials' => $letters];
}

function normalize_org_nickname(?string $value): ?string
{
    $value = trim((string) $value);

    return $value !== '' ? $value : null;
}

function normalize_org_address(?string $value): ?string
{
    $value = trim((string) $value);

    return $value !== '' ? $value : null;
}

/**
 * Normalize a pasted Google Maps (or other https) navigation link.
 * Empty → null. Adds https:// if scheme is missing.
 */
function normalize_maps_url(?string $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    if (!preg_match('#^https?://#i', $value)) {
        $value = 'https://' . ltrim($value, '/');
    }
    if (filter_var($value, FILTER_VALIDATE_URL) === false) {
        return null;
    }
    $scheme = strtolower((string) (parse_url($value, PHP_URL_SCHEME) ?? ''));
    if ($scheme !== 'http' && $scheme !== 'https') {
        return null;
    }
    if (strlen($value) > 512) {
        $value = substr($value, 0, 512);
    }

    return $value;
}

/**
 * “Navigate” control — only when a manual maps_url is set (paste from Google Maps).
 *
 * @param array{class?:string, label_key?:string, compact?:bool} $options
 */
function maps_navigate_button(?string $mapsUrl, array $options = []): string
{
    $url = normalize_maps_url($mapsUrl);
    if ($url === null) {
        return '';
    }
    $class = trim((string) ($options['class'] ?? 'maps-nav-btn'));
    $labelKey = (string) ($options['label_key'] ?? 'maps.navigate');
    $compact = !empty($options['compact']);
    $label = t($labelKey);
    $aria = t('maps.navigate_aria');

    $html = '<a class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"';
    $html .= ' href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"';
    $html .= ' target="_blank" rel="noopener noreferrer"';
    $html .= ' aria-label="' . htmlspecialchars($aria, ENT_QUOTES, 'UTF-8') . '"';
    $html .= ' title="' . htmlspecialchars($aria, ENT_QUOTES, 'UTF-8') . '">';
    $html .= '<i class="mdi mdi-navigation" aria-hidden="true"></i>';
    if (!$compact) {
        $html .= '<span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
    }
    $html .= '</a>';

    return $html;
}

/**
 * PHP bcrypt strings are 60 chars. Values sometimes include trailing junk from SQL paste.
 */
function normalize_stored_password_hash(string $stored): string
{
    $stored = trim($stored);
    if ($stored === '') {
        return '';
    }
    if (preg_match('/^\$2[ayb]\$\d{2}\$/', $stored) && strlen($stored) > 60) {
        return substr($stored, 0, 60);
    }

    return $stored;
}

function format_india_phone(?string $stored): string
{
    if ($stored === null || trim((string) $stored) === '') {
        return '—';
    }
    $d = preg_replace('/\D+/', '', (string) $stored);
    if (strlen($d) === 12 && strpos($d, '91') === 0) {
        return '+91 ' . substr($d, 2);
    }
    if (strlen($d) === 10) {
        return '+91 ' . $d;
    }

    return (string) $stored;
}

/**
 * @param array<string, scalar|null> $replacements
 */
function apply_text_placeholders(string $text, array $replacements): string
{
    foreach ($replacements as $name => $value) {
        $token = (string) $name;
        $replacement = (string) $value;
        $text = str_replace(
            [':' . $token, '{' . $token . '}', '[' . $token . ']'],
            [$replacement, $replacement, $replacement],
            $text
        );
    }

    return $text;
}

function notification_text_has_placeholders(string $text): bool
{
    return (bool) preg_match('/(?::|\{|\[)(name|preview|count)(?:\}|\]|\b)/', $text);
}

/**
 * @param array<string,mixed> $row
 * @return array<string,mixed>
 */
function notification_hydrate_row(array $row): array
{
    $type = (string) ($row['type'] ?? '');
    $referenceId = (int) ($row['reference_id'] ?? 0);
    if ($referenceId < 1 || $type !== 'member_admin_chat') {
        return $row;
    }

    $title = (string) ($row['title'] ?? '');
    $message = (string) ($row['message'] ?? '');
    if (!notification_text_has_placeholders($title) && !notification_text_has_placeholders($message)) {
        return $row;
    }

    $context = (new \App\Models\MemberAdminChat())->notificationContextForThread($referenceId);
    if ($context === null) {
        return $row;
    }

    $replacements = [
        'name' => (string) ($context['name'] ?? ''),
        'preview' => (string) ($context['preview'] ?? ''),
    ];
    $row['title'] = apply_text_placeholders($title, $replacements);
    $row['message'] = apply_text_placeholders($message, $replacements);
    if (trim($row['message']) === '' && $replacements['preview'] !== '') {
        $row['message'] = $replacements['preview'];
    }

    return $row;
}

/**
 * @param array<string,mixed> $row
 * @return array{id:int,title:string,message:string,type:string,isUnread:bool,createdAt:string,createdLabel:string}
 */
function notification_to_client(array $row): array
{
    $row = notification_hydrate_row($row);
    $createdAt = (string) ($row['created_at'] ?? '');
    $message = (string) ($row['message'] ?? '');
    if (strlen($message) > 140) {
        $message = substr($message, 0, 137) . '...';
    }

    return [
        'id' => (int) ($row['id'] ?? 0),
        'title' => (string) ($row['title'] ?? ''),
        'message' => $message,
        'type' => (string) ($row['type'] ?? ''),
        'isUnread' => empty($row['read_at']),
        'createdAt' => $createdAt,
        'createdLabel' => format_pretty_date($createdAt !== '' ? $createdAt : null),
    ];
}

/**
 * @param array<string,mixed> $row
 * @return array{id:int,title:string,message:string,type:string,referenceId:int,isUnread:bool,createdAt:string,createdLabel:string}
 */
function notification_to_inbox_client(array $row): array
{
    $row = notification_hydrate_row($row);
    $createdAt = (string) ($row['created_at'] ?? '');

    return [
        'id' => (int) ($row['id'] ?? 0),
        'title' => (string) ($row['title'] ?? ''),
        'message' => (string) ($row['message'] ?? ''),
        'type' => (string) ($row['type'] ?? ''),
        'referenceId' => (int) ($row['reference_id'] ?? 0),
        'isUnread' => empty($row['read_at']),
        'createdAt' => $createdAt,
        'createdLabel' => format_pretty_date($createdAt !== '' ? $createdAt : null),
    ];
}

/**
 * Display date as "19th May, 2026" (no time). Accepts Y-m-d or datetime strings from the DB.
 */
function format_pretty_date(?string $raw): string
{
    if ($raw === null) {
        return '—';
    }
    $raw = trim($raw);
    if ($raw === '' || strpos($raw, '0000-00-00') === 0) {
        return '—';
    }
    try {
        return (new \DateTimeImmutable($raw))->format('jS F, Y');
    } catch (\Exception $e) {
        $ts = strtotime($raw);
        if ($ts === false) {
            return $raw;
        }

        return date('jS F, Y', $ts);
    }
}

/**
 * @param array<string,mixed> $receipt
 */
function receipt_number_label(array $receipt): string
{
    return sprintf('%s/%04d', (string) ($receipt['financial_year'] ?? ''), (int) ($receipt['receipt_no'] ?? 0));
}

/**
 * @param array<string,mixed> $receipt
 */
function receipt_pdf_filename(array $receipt): string
{
    $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', receipt_number_label($receipt));
    $safe = trim((string) $safe, '-');

    return ($safe !== '' ? $safe : 'receipt') . '.pdf';
}

/** WhatsApp share link (opens chat with pre-filled message). */
function whatsapp_share_phone_digits(?string $phone): ?string
{
    $digits = normalize_phone($phone);
    if ($digits === null) {
        return null;
    }
    if (strlen($digits) === 10) {
        return '91' . $digits;
    }
    if (strlen($digits) >= 11) {
        return $digits;
    }

    return null;
}

function whatsapp_share_url(string $text, ?string $phone = null): string
{
    $digits = whatsapp_share_phone_digits($phone);
    if ($digits !== null) {
        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
    }

    return 'https://wa.me/?text=' . rawurlencode($text);
}

/**
 * @param array<string,mixed> $row
 * @param list<string> $keys
 */
function whatsapp_share_phone_from_row(array $row, array $keys): ?string
{
    foreach ($keys as $key) {
        $phone = trim((string) ($row[$key] ?? ''));
        if ($phone !== '') {
            $digits = whatsapp_share_phone_digits($phone);
            if ($digits !== null) {
                return $phone;
            }
        }
    }

    return null;
}

function whatsapp_share_org_name(?string $fallback = null): string
{
    $name = trim((string) ($fallback ?? ''));
    if ($name !== '') {
        return $name;
    }
    $orgId = current_organization_id();
    if ($orgId !== null && $orgId > 0) {
        $org = (new \App\Models\Organization())->findById($orgId);
        if ($org !== null) {
            $name = trim((string) ($org['name'] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }
    }

    return t('common.organization');
}

/**
 * @param array<string,mixed> $receipt
 */
function receipt_whatsapp_share_message(array $receipt): string
{
    return t('whatsapp.share_receipt', [
        'org' => whatsapp_share_org_name((string) ($receipt['organization_name'] ?? '')),
        'no' => receipt_number_label($receipt),
        'recipient' => (string) ($receipt['recipient_name'] ?? ''),
        'purpose' => (string) ($receipt['purpose'] ?? ''),
        'amount' => number_format((float) ($receipt['amount'] ?? 0), 2),
        'date' => format_pretty_date(isset($receipt['receipt_date']) ? (string) $receipt['receipt_date'] : null),
    ]);
}

/**
 * @param array<string,mixed> $event
 */
function event_whatsapp_share_message(array $event): string
{
    $eventDate = trim((string) ($event['event_date'] ?? ''));
    if ($eventDate === '' && !empty($event['created_at'])) {
        $eventDate = date('Y-m-d', strtotime((string) $event['created_at']));
    }
    $basis = strtolower((string) ($event['charge_basis'] ?? 'per_family'));
    if ($basis === '' && strtolower((string) ($event['due_type'] ?? '')) === 'event') {
        $basis = 'per_person';
    }
    $rateLabel = $basis === 'per_person' ? t('events.per_person') : t('events.per_family');

    return t('whatsapp.share_event', [
        'org' => whatsapp_share_org_name(),
        'title' => (string) ($event['title'] ?? ''),
        'date' => $eventDate !== '' ? format_pretty_date($eventDate) : '—',
        'fy' => (string) ($event['financial_year'] ?? ''),
        'amount' => number_format((float) ($event['amount'] ?? 0), 2),
        'rate' => $rateLabel,
    ]);
}

/**
 * @param array<string,mixed> $event
 * @param array<string,mixed> $pass
 */
function event_pass_whatsapp_share_message(array $event, array $pass): string
{
    $code = (string) ($pass['pass_code'] ?? '');
    $suffix = strlen($code) <= 3 ? $code : substr($code, -3);

    return t('whatsapp.share_pass', [
        'org' => whatsapp_share_org_name(),
        'event' => (string) ($event['title'] ?? ''),
        'holder' => (string) ($pass['holder_name'] ?? ''),
        'code' => $suffix,
        'status' => (string) ($pass['status'] ?? '') === 'redeemed' ? t('common.redeemed') : t('common.active'),
    ]);
}

/**
 * @param array<string,mixed> $commitment
 */
function donation_whatsapp_share_message(array $commitment): string
{
    return t('whatsapp.share_donation', [
        'org' => whatsapp_share_org_name(),
        'donor' => (string) ($commitment['donor_name'] ?? ''),
        'category' => (string) ($commitment['category_name'] ?? ''),
        'committed' => number_format((float) ($commitment['committed_amount'] ?? 0), 2),
        'paid' => number_format((float) ($commitment['paid_total'] ?? 0), 2),
        'balance' => number_format((float) ($commitment['balance'] ?? 0), 2),
        'date' => format_pretty_date((string) ($commitment['committed_date'] ?? '')),
    ]);
}

function scheme_benefit_summary(array $row): string
{
    $type = trim((string) ($row['benefit_type'] ?? ''));
    $value = trim((string) ($row['benefit_value'] ?? ''));
    if ($type === '') {
        return '—';
    }

    return $value !== '' ? $type . ' — ' . $value : $type;
}

/**
 * @param array<string,mixed> $scheme
 */
function scheme_whatsapp_share_message(array $scheme): string
{
    $active = (int) ($scheme['is_active'] ?? 0) === 1 ? t('common.active') : t('schemes.inactive');

    return t('whatsapp.share_scheme', [
        'org' => whatsapp_share_org_name(),
        'name' => (string) ($scheme['name'] ?? ''),
        'scope' => (string) ($scheme['benefit_scope'] ?? ''),
        'benefit' => scheme_benefit_summary($scheme),
        'assigned' => (string) (int) ($scheme['assignment_count'] ?? 0),
        'status' => $active,
    ]);
}

/**
 * @param array<string,mixed> $row
 */
function scheme_eligible_whatsapp_share_message(array $row): string
{
    $status = (string) ($row['status'] ?? '') === 'claimed'
        ? t('schemes.benefitted')
        : t('schemes.not_yet');

    return t('whatsapp.share_scheme_benefit', [
        'org' => whatsapp_share_org_name(),
        'name' => (string) ($row['name'] ?? ''),
        'scope' => (string) ($row['benefit_scope'] ?? ''),
        'benefit' => scheme_benefit_summary($row),
        'status' => $status,
    ]);
}

/**
 * Display datetime as "19th May, 2026, 2:15 PM". Accepts Y-m-d or datetime strings from the DB.
 */
function format_pretty_datetime(?string $raw): string
{
    if ($raw === null) {
        return '—';
    }
    $raw = trim($raw);
    if ($raw === '' || strpos($raw, '0000-00-00') === 0) {
        return '—';
    }
    try {
        return (new \DateTimeImmutable($raw))->format('jS F, Y, g:i A');
    } catch (\Exception $e) {
        $ts = strtotime($raw);
        if ($ts === false) {
            return $raw;
        }

        return date('jS F, Y, g:i A', $ts);
    }
}

/** Y-m-d for HTML date inputs (from DB date or datetime). */
function format_date_input(?string $raw): string
{
    if ($raw === null) {
        return '';
    }
    $raw = trim($raw);
    if ($raw === '' || strpos($raw, '0000-00-00') === 0) {
        return '';
    }
    try {
        return (new \DateTimeImmutable($raw))->format('Y-m-d');
    } catch (\Exception $e) {
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $m)) {
            return $m[1];
        }

        return '';
    }
}

function is_valid_email(?string $email): bool
{
    $email = normalize_email($email);
    if ($email === null) {
        return false;
    }
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function request_raw_body(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $cached = file_get_contents('php://input') ?: '';

    return $cached;
}

function read_json_body(): array
{
    $raw = request_raw_body();
    if (trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function flash_set(string $key, string $message): void
{
    if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
        $_SESSION['_flash'] = [];
    }
    $_SESSION['_flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }
    $msg = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    if (!is_string($msg)) {
        return null;
    }

    return flash_tr($msg);
}

/** @return array<string,string> */
function supported_locales(): array
{
    return [
        'en' => 'English',
        'gu' => 'ગુજરાતી',
    ];
}

function current_locale(): string
{
    $session = strtolower(trim((string) ($_SESSION['locale'] ?? '')));
    if ($session !== '' && isset(supported_locales()[$session])) {
        return $session;
    }

    $cookie = strtolower(trim((string) ($_COOKIE['locale'] ?? '')));
    if ($cookie !== '' && isset(supported_locales()[$cookie])) {
        $_SESSION['locale'] = $cookie;

        return $cookie;
    }

    return 'en';
}

function set_locale(string $locale): void
{
    $locale = strtolower(trim($locale));
    if (!isset(supported_locales()[$locale])) {
        $locale = 'en';
    }
    $_SESSION['locale'] = $locale;
    setcookie('locale', $locale, [
        'expires' => time() + (86400 * 365),
        'path' => cookie_path(),
        'secure' => request_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $user = current_user();
    if ($user !== null && isset($user['id'])) {
        (new \App\Models\User())->updatePreferredLocale((int) $user['id'], $locale);
    }
}

/** Apply saved language when a user signs in (DB preference, then cookie/session). */
function sync_user_locale_on_login(array $userRow): void
{
    $preferred = strtolower(trim((string) ($userRow['preferred_locale'] ?? '')));
    if ($preferred !== '' && isset(supported_locales()[$preferred])) {
        set_locale($preferred);

        return;
    }

    $current = current_locale();
    if (!isset($userRow['id'])) {
        return;
    }

    (new \App\Models\User())->updatePreferredLocale((int) $userRow['id'], $current);
}

/** @return array<string, array<string,string>> */
function translation_map(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $map = [];
    foreach (supported_locales() as $code => $_label) {
        $path = BASE_PATH . '/lang/' . $code . '.php';
        if (is_file($path)) {
            $loaded = require $path;
            $map[$code] = is_array($loaded) ? $loaded : [];
        } else {
            $map[$code] = [];
        }
    }

    return $map;
}

/**
 * @param string|array<string, scalar|null>|null $fallbackOrReplacements
 */
function t(string $key, $fallbackOrReplacements = null): string
{
    $fallback = null;
    $replacements = [];
    if (is_array($fallbackOrReplacements)) {
        $replacements = $fallbackOrReplacements;
    } elseif ($fallbackOrReplacements !== null) {
        $fallback = (string) $fallbackOrReplacements;
    }

    $locale = current_locale();
    $map = translation_map();
    if (isset($map[$locale][$key])) {
        $text = $map[$locale][$key];
    } elseif (isset($map['en'][$key])) {
        $text = $map['en'][$key];
    } else {
        $text = $fallback ?? $key;
    }

    if ($replacements !== []) {
        $text = apply_text_placeholders($text, $replacements);
    }

    return $text;
}

/**
 * Translate for a specific locale (ignores session/cookie).
 *
 * @param array<string, scalar|null> $replacements
 */
function t_for_locale(string $key, string $locale, array $replacements = []): string
{
    $locale = strtolower(trim($locale));
    if (!isset(supported_locales()[$locale])) {
        $locale = 'en';
    }
    $map = translation_map();
    if (isset($map[$locale][$key])) {
        $text = $map[$locale][$key];
    } elseif (isset($map['en'][$key])) {
        $text = $map['en'][$key];
    } else {
        $text = $key;
    }

    if ($replacements !== []) {
        $text = apply_text_placeholders($text, $replacements);
    }

    return $text;
}

/** Notification locale from user preference (gu or en). */
function user_notification_locale(?string $preferredLocale): string
{
    $preferred = strtolower(trim((string) $preferredLocale));
    if ($preferred === 'gu' && isset(supported_locales()['gu'])) {
        return 'gu';
    }

    return 'en';
}

/**
 * Shorthand: translate + escape for HTML output.
 *
 * @param string|array<string, scalar|null>|null $fallbackOrReplacements
 */
function h(string $key, $fallbackOrReplacements = null): string
{
    return htmlspecialchars(t($key, $fallbackOrReplacements), ENT_QUOTES, 'UTF-8');
}

/** Translate flash/controller messages stored as English text. */
function flash_tr(?string $message): ?string
{
    if ($message === null || trim($message) === '') {
        return $message;
    }
    if (current_locale() === 'en') {
        return $message;
    }
    static $guMap = null;
    if ($guMap === null) {
        $path = BASE_PATH . '/lang/flash_gu.php';
        $guMap = is_file($path) ? require $path : [];
        if (!is_array($guMap)) {
            $guMap = [];
        }
    }
    if (isset($guMap[$message])) {
        return (string) $guMap[$message];
    }

    if (preg_match('/^Scheme created\. Eligible (families|members) assigned: (\d+)\.$/', $message, $m)) {
        $type = $m[1] === 'families' ? t('schemes.families_word') : t('schemes.members_word');

        return str_replace(['{type}', '{count}'], [$type, $m[2]], t('flash.scheme_created_assigned'));
    }
    if (preg_match('/^Redeemed (.+)$/', $message, $m)) {
        return t('flash.redeemed_prefix') . ' ' . $m[1];
    }
    if (preg_match('/^Pass (.+) marked active again(.*)$/', $message, $m)) {
        return t('flash.pass_prefix') . ' ' . $m[1] . ' ' . t('flash.pass_active_again') . $m[2];
    }
    if (preg_match('/^Receipt created\./', $message)) {
        $out = t('flash.receipt_created');
        if (preg_match('/(\d+) event pass(es)? active for this family/', $message, $m)) {
            $n = (int) $m[1];
            $suffix = $n === 1 ? t('flash.event_pass_singular') : t('flash.event_pass_plural');
            $out .= ' ' . $n . ' ' . $suffix;
            if (preg_match('/\((\d+) × ([\d.]+) per person\)/', $message, $m2)) {
                $out .= ' (' . $m2[1] . ' × ' . $m2[2] . ' ' . t('flash.per_person') . ')';
            }
            $out .= '.';
        }

        return $out;
    }
    if (preg_match('/^Broadcast sent to (\d+) users \(in-app\)\./', $message, $m)) {
        $out = str_replace('{count}', $m[1], t('flash.broadcast_sent'));
        if (preg_match('/WhatsApp queued: (\d+)\./', $message, $m2)) {
            $out .= ' ' . str_replace('{count}', $m2[1], t('flash.whatsapp_queued'));
        }

        return $out;
    }
    if (preg_match('/^Event created \(compulsory, (per person|per family)\)\. Passes are issued when payment is complete\.$/', $message, $m)) {
        $basis = $m[1] === 'per person' ? t('flash.per_person') : t('flash.per_family');

        return str_replace('{basis}', $basis, t('flash.event_compulsory_created'));
    }
    if (preg_match('/^Event created \(optional, (per person|per family)\)\. Passes are issued when a full receipt is recorded\.$/', $message, $m)) {
        $basis = $m[1] === 'per person' ? t('flash.per_person') : t('flash.per_family');

        return str_replace('{basis}', $basis, t('flash.event_optional_created'));
    }
    if ($message === 'Due created — amount is per login member (rate × member count). Open the tracker to see who is still pending.') {
        return t('flash.due_per_member_created');
    }
    if ($message === 'Compulsory due created (flat per family).') {
        return t('flash.due_compulsory_flat');
    }
    if ($message === 'Optional due created — only families who pay will appear in the tracker.') {
        return t('flash.due_optional_created');
    }
    if (preg_match('/^Organization created, but admin account failed: (.+)\. Add the admin from the organization page\.$/', $message, $m)) {
        return str_replace('{error}', $m[1], t('flash.org_admin_failed'));
    }
    if (preg_match('/^Import complete\. Families: (\d+), members added: (\d+), dependents: (\d+), users created: (\d+), users reused: (\d+), groups skipped: (\d+)\.$/', $message, $m)) {
        $out = str_replace(
            ['{families}', '{members}', '{dependents}', '{users}', '{reused}', '{skipped}'],
            [$m[1], $m[2], $m[3], $m[4], $m[5], $m[6]],
            t('flash.import_complete')
        );
        if (preg_match('/\. Notes: (.+)$/', $message, $n)) {
            $out .= str_replace('{notes}', $n[1], t('flash.import_notes'));
        }

        return $out;
    }
    if (str_starts_with($message, 'Already redeemed')) {
        return t('flash.already_redeemed') . substr($message, 16);
    }

    return $message;
}

/** App-relative path for the current request (matches router paths). */
function request_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $rawPath = parse_url($uri, PHP_URL_PATH);
    $path = is_string($rawPath) && $rawPath !== '' ? str_replace('\\', '/', $rawPath) : '/';

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = dirname($scriptName);
    if ($scriptDir === '\\' || $scriptDir === '.') {
        $scriptDir = '';
    }
    if ($scriptDir !== '' && $scriptDir !== '/' && strpos($path, $scriptDir) === 0) {
        $path = substr($path, strlen($scriptDir)) ?: '/';
    }
    $path = '/' . ltrim($path, '/');

    if (strpos($path, '/index.php/') === 0) {
        $path = substr($path, strlen('/index.php')) ?: '/';
        $path = '/' . ltrim($path, '/');
    } elseif ($path === '/index.php') {
        $path = '/';
    }

    if ($path !== '/' && substr($path, -1) === '/') {
        $path = rtrim($path, '/');
    }

    return $path;
}

function locale_url(string $locale): string
{
    $back = request_path();
    if ($back === '/locale') {
        $back = '/organization/dashboard';
    }

    return base_url() . '/locale?locale=' . urlencode($locale) . '&back=' . urlencode($back);
}

function request_data(Request $request): array
{
    if (strtoupper($request->method()) === 'GET') {
        return $_GET;
    }
    $contentType = $request->header('Content-Type') ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        return read_json_body();
    }
    if (!empty($_POST)) {
        return $_POST;
    }
    // Empty $_POST but JSON body (some stacks omit/limit Content-Type detection).
    $json = read_json_body();
    if ($json !== []) {
        return $json;
    }
    return $_POST;
}

/**
 * Resolve the PHP CLI binary for background jobs. Under Apache on WAMP, PHP_BINARY is httpd.exe.
 */
function php_cli_binary(): string
{
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }

    $candidates = [];

    $envPath = trim((string) (getenv('PHP_CLI_PATH') ?: ''));
    if ($envPath !== '') {
        $candidates[] = $envPath;
    }

    if (PHP_SAPI === 'cli' && PHP_BINARY !== '' && is_php_cli_binary(PHP_BINARY)) {
        $candidates[] = PHP_BINARY;
    }

    if (defined('PHP_BINDIR')) {
        $candidates[] = PHP_BINDIR . DIRECTORY_SEPARATOR . 'php.exe';
        $candidates[] = PHP_BINDIR . DIRECTORY_SEPARATOR . 'php';
    }

    $wampRoots = array_filter([
        getenv('WAMP_ROOT') ?: '',
        'C:\\wamp64',
        'C:\\wamp',
    ]);
    foreach ($wampRoots as $root) {
        $root = rtrim((string) $root, '\\/');
        if ($root === '' || !is_dir($root . '\\bin\\php')) {
            continue;
        }
        $matches = glob($root . '\\bin\\php\\php*\\php.exe') ?: [];
        rsort($matches);
        foreach ($matches as $match) {
            $candidates[] = $match;
        }
    }

    if (function_exists('shell_exec')) {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $where = trim((string) @shell_exec('where php 2>NUL'));
        } else {
            $where = trim((string) @shell_exec('command -v php 2>/dev/null'));
        }
        if ($where !== '') {
            foreach (preg_split('/\R+/', $where) ?: [] as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $candidates[] = $line;
                }
            }
        }
    }

    foreach (['/usr/bin/php', '/usr/local/bin/php'] as $linuxPhp) {
        $candidates[] = $linuxPhp;
    }

    foreach ($candidates as $candidate) {
        if (is_php_cli_binary($candidate) && is_file($candidate)) {
            return $resolved = $candidate;
        }
    }

    return $resolved = PHP_BINARY;
}

function is_php_cli_binary(string $path): bool
{
    $base = strtolower(basename(str_replace('\\', '/', $path)));

    return $base === 'php' || $base === 'php.exe' || preg_match('/^php[\d\.\-]*\.exe$/', $base) === 1;
}

/**
 * @param array<string,mixed> $data
 */
function send_deferred_email_payload(string $type, array $data): bool
{
    switch ($type) {
        case 'invite_with_password':
            return send_invite_email_with_password(
                (string) ($data['email'] ?? ''),
                (string) ($data['name'] ?? ''),
                (string) ($data['password'] ?? ''),
                (string) ($data['verify_url'] ?? ''),
                isset($data['org_code']) ? (string) $data['org_code'] : null,
                isset($data['login_url']) ? (string) $data['login_url'] : null
            );
        case 'multi_org_membership':
            return send_multi_org_membership_email(
                (string) ($data['email'] ?? ''),
                (string) ($data['name'] ?? ''),
                (string) ($data['organization_name'] ?? ''),
                isset($data['org_code']) ? (string) $data['org_code'] : null,
                isset($data['login_url']) ? (string) $data['login_url'] : null,
                isset($data['profile_url']) ? (string) $data['profile_url'] : null
            );
        case 'forgot_password':
            return send_forgot_password_email(
                (string) ($data['email'] ?? ''),
                (string) ($data['name'] ?? ''),
                (string) ($data['password'] ?? ''),
                isset($data['org_code']) ? (string) $data['org_code'] : null,
                isset($data['login_url']) ? (string) $data['login_url'] : null
            );
        default:
            error_log('Unknown deferred email type: ' . $type);

            return false;
    }
}

/**
 * Run a PHP script in the background (fire-and-forget). Used for deferred email on WAMP/mod_php.
 *
 * @param list<string|int> $argv
 */
function spawn_background_php_script(string $scriptBasename, array $argv = []): bool
{
    $php = php_cli_binary();
    if (!is_php_cli_binary($php) || !is_file($php)) {
        error_log('Background PHP binary not found (got ' . $php . '). Set PHP_CLI_PATH in .env.');

        return false;
    }

    $script = BASE_PATH . '/scripts/' . ltrim($scriptBasename, '/');
    if (!is_file($script)) {
        error_log('Background script not found: ' . $script);

        return false;
    }

    $cmd = escapeshellarg($php) . ' ' . escapeshellarg($script);
    foreach ($argv as $arg) {
        $cmd .= ' ' . escapeshellarg((string) $arg);
    }

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $handle = @popen('cmd /c start "" /B ' . $cmd . ' 2>NUL', 'r');
        if ($handle === false) {
            error_log('Failed to spawn background PHP process on Windows.');

            return false;
        }
        pclose($handle);

        return true;
    }

    @exec('nohup ' . $cmd . ' > /dev/null 2>&1 &');

    return true;
}

/**
 * Send one queued email job file. Keeps the file when delivery fails.
 */
function deliver_deferred_email_file(string $path): bool
{
    if (!is_file($path)) {
        return false;
    }

    $basename = basename($path);
    if (preg_match('/^[a-f0-9]{32}\.json$/', $basename) !== 1) {
        return false;
    }

    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return false;
    }

    try {
        /** @var array{type?:string,data?:array<string,mixed>} $job */
        $job = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        $type = (string) ($job['type'] ?? '');
        /** @var array<string,mixed> $data */
        $data = is_array($job['data'] ?? null) ? $job['data'] : [];
        if (!send_deferred_email_payload($type, $data)) {
            error_log('Deferred email delivery failed (' . $type . ') to ' . (string) ($data['email'] ?? ''));

            return false;
        }

        @unlink($path);

        return true;
    } catch (Throwable $e) {
        error_log('Deferred email parse failed: ' . $e->getMessage());

        return false;
    }
}

/**
 * Process queued emails (run via cron on servers: php scripts/process_deferred_emails.php).
 */
function process_pending_deferred_emails(int $limit = 10): int
{
    $dir = BASE_PATH . '/storage/deferred_emails';
    if (!is_dir($dir)) {
        return 0;
    }

    $files = glob($dir . '/*.json');
    if ($files === false || $files === []) {
        return 0;
    }

    usort($files, static function (string $a, string $b): int {
        return filemtime($a) <=> filemtime($b);
    });

    $sent = 0;
    foreach (array_slice($files, 0, $limit) as $path) {
        if (deliver_deferred_email_file($path)) {
            $sent++;
        }
    }

    return $sent;
}

/**
 * Queue email and try to send it before the HTTP response finishes.
 *
 * @param array<string,mixed> $data
 * @return array{queued:bool,sent:bool}
 */
function queue_deferred_email(string $type, array $data): array
{
    $dir = BASE_PATH . '/storage/deferred_emails';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        error_log('Deferred email directory not writable: ' . $dir);

        return ['queued' => false, 'sent' => false];
    }

    try {
        $id = bin2hex(random_bytes(16));
        $payload = json_encode(['type' => $type, 'data' => $data], JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        error_log('Deferred email queue failed: ' . $e->getMessage());

        return ['queued' => false, 'sent' => false];
    }

    $path = $dir . '/' . $id . '.json';
    if (file_put_contents($path, $payload, LOCK_EX) === false) {
        error_log('Deferred email queue write failed: ' . $path);

        return ['queued' => false, 'sent' => false];
    }

    if (deliver_deferred_email_file($path)) {
        return ['queued' => true, 'sent' => true];
    }

    spawn_background_php_script('send_deferred_email.php', [$id]);

    return ['queued' => true, 'sent' => false];
}

/**
 * 6-letter temporary login code (easy to type from email).
 * Uses A–Z except I and O to avoid looking like 1/0.
 */
function generate_temporary_otp_password(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $max = strlen($alphabet) - 1;
    $otp = '';
    for ($i = 0; $i < 6; $i++) {
        $otp .= $alphabet[random_int(0, $max)];
    }

    return $otp;
}

function send_invite_email_with_password(
    string $toEmail,
    string $name,
    string $plainPassword,
    string $verifyUrl,
    ?string $orgCode = null,
    ?string $loginUrl = null
): bool {
    $subject = 'Your ' . app_name() . ' account';
    $body = "Hello {$name},\n\n"
        . "Your account is ready.\n"
        . "Temporary login code: {$plainPassword}\n"
        . "(6 letters — enter this as your password when signing in)\n\n";
    if ($orgCode !== null && $orgCode !== '') {
        $body .= "Organization code: {$orgCode}\n";
    }
    if ($loginUrl !== null && $loginUrl !== '') {
        $body .= "Sign in: {$loginUrl}\n\n";
    }
    $body .= "Verify your email:\n{$verifyUrl}\n\n"
        . "After sign-in you must set a new password before using the app.\n";

    return system_send_email($toEmail, $subject, $body);
}

/**
 * Email when adding someone who already has this email in another Upashray/Sangh.
 */
function send_multi_org_membership_email(
    string $toEmail,
    string $name,
    string $organizationName,
    ?string $orgCode = null,
    ?string $loginUrl = null,
    ?string $profileUrl = null
): bool {
    $subject = app_name() . ' — added to ' . $organizationName;
    $body = "Hello {$name},\n\n"
        . "{$organizationName} has added you as a member.\n\n"
        . "You can sign in with your existing password";
    if ($orgCode !== null && $orgCode !== '') {
        $body .= " and organization code {$orgCode}";
    }
    $body .= ".\n";
    if ($loginUrl !== null && $loginUrl !== '') {
        $body .= "Sign in: {$loginUrl}\n";
    }
    $body .= "\nOr open the app, go to My profile, and switch Upashray/Sangh"
        . " from your memberships list — no need to log out.\n"
        . "Your profile details were carried over; you can update them anytime.\n";
    if ($profileUrl !== null && $profileUrl !== '') {
        $body .= "Profile: {$profileUrl}\n";
    }

    return system_send_email($toEmail, $subject, $body);
}

function send_forgot_password_email(
    string $toEmail,
    string $name,
    string $temporaryPassword,
    ?string $orgCode = null,
    ?string $loginUrl = null
): bool {
    $subject = 'Temporary login code - ' . app_name();
    $body = "Hello {$name},\n\n"
        . "Your temporary login code is: {$temporaryPassword}\n"
        . "(6 letters — enter this as your password when signing in)\n\n";
    if ($orgCode !== null && $orgCode !== '') {
        $body .= "Organization code: {$orgCode}\n";
    }
    if ($loginUrl !== null && $loginUrl !== '') {
        $body .= "Sign in: {$loginUrl}\n\n";
    }
    $body .= "You must set a new password after sign-in before using other pages.\n"
        . "If you did not request this, contact your organization administrator.\n";

    return system_send_email($toEmail, $subject, $body);
}

/** Directory under public/ for member profile photos. */
function user_photo_storage_dir(): string
{
    $dir = BASE_PATH . '/public/uploads/profile-photos';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    return $dir;
}

/** Public URL for a stored photo path, or null when none. */
function build_person_full_name(string $firstName, ?string $middleName, string $lastName): string
{
    $parts = [];
    $firstName = trim($firstName);
    $middleName = $middleName !== null ? trim($middleName) : '';
    $lastName = trim($lastName);
    if ($firstName !== '' && $lastName !== '' && strcasecmp($firstName, $lastName) === 0) {
        $lastName = '';
    }
    if ($firstName !== '') {
        $parts[] = $firstName;
    }
    if ($middleName !== '') {
        $parts[] = $middleName;
    }
    if ($lastName !== '') {
        $parts[] = $lastName;
    }

    return implode(' ', $parts);
}

/**
 * @return array{first_name:string,middle_name:?string,last_name:string}
 */
function split_person_full_name(string $fullName): array
{
    $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? '');
    if ($fullName === '') {
        return ['first_name' => '', 'middle_name' => null, 'last_name' => ''];
    }
    $parts = explode(' ', $fullName);
    if (count($parts) === 1) {
        return ['first_name' => $parts[0], 'middle_name' => null, 'last_name' => ''];
    }
    if (count($parts) === 2) {
        return ['first_name' => $parts[0], 'middle_name' => null, 'last_name' => $parts[1]];
    }
    $first = array_shift($parts);
    $last = array_pop($parts);
    $middle = implode(' ', $parts);

    return [
        'first_name' => (string) $first,
        'middle_name' => $middle !== '' ? $middle : null,
        'last_name' => (string) $last,
    ];
}

/**
 * @param array<string,mixed> $row
 * @return array{first_name:string,middle_name:?string,last_name:string}
 */
function person_name_parts_from_row(array $row): array
{
    $first = trim((string) ($row['first_name'] ?? ''));
    $middle = trim((string) ($row['middle_name'] ?? ''));
    $last = trim((string) ($row['last_name'] ?? ''));
    if ($first !== '' || $last !== '') {
        return [
            'first_name' => $first,
            'middle_name' => $middle !== '' ? $middle : null,
            'last_name' => $last,
        ];
    }

    return split_person_full_name((string) ($row['name'] ?? ''));
}

/** Header label: first and last name (no duplicate email / repeated tokens). */
function user_nav_display_name(array $row): string
{
    $parts = person_name_parts_from_row($row);
    $first = trim($parts['first_name']);
    $last = trim($parts['last_name']);
    if ($first !== '' && $last !== '' && strcasecmp($first, $last) !== 0) {
        return $first . ' ' . $last;
    }
    if ($first !== '') {
        if (strpos($first, '@') !== false) {
            $local = strstr($first, '@', true);

            return $local !== false && $local !== '' ? $local : t('common.user');
        }

        return $first;
    }
    if ($last !== '') {
        return $last;
    }
    $legacy = trim((string) ($row['name'] ?? ''));
    if ($legacy !== '' && strpos($legacy, '@') === false) {
        return $legacy;
    }
    if ($legacy !== '' && strpos($legacy, '@') !== false) {
        $local = strstr($legacy, '@', true);

        return $local !== false && $local !== '' ? $local : t('common.user');
    }

    return t('common.user');
}

/** @param array<string,mixed> $row */
function user_display_name(array $row): string
{
    $parts = person_name_parts_from_row($row);
    $full = build_person_full_name($parts['first_name'], $parts['middle_name'], $parts['last_name']);
    if ($full !== '') {
        return $full;
    }

    return trim((string) ($row['name'] ?? ''));
}

/**
 * Head member row for a household (from $members when possible).
 *
 * @param array<string,mixed> $family
 * @param list<array<string,mixed>> $members
 * @return array<string,mixed>
 */
function family_head_row(array $family, array $members = []): array
{
    $headUserId = (int) ($family['head_user_id'] ?? 0);
    $headRow = null;
    foreach ($members as $m) {
        if (strtolower(trim((string) ($m['role'] ?? ''))) === 'head') {
            $headRow = $m;
            break;
        }
    }
    if ($headRow === null && $headUserId > 0) {
        foreach ($members as $m) {
            if ((int) ($m['user_id'] ?? 0) === $headUserId) {
                $headRow = $m;
                break;
            }
        }
    }
    if ($headRow === null && $headUserId > 0) {
        $headRow = (new \App\Models\User())->findById($headUserId) ?? [];
    }
    $row = is_array($headRow) ? $headRow : [];
    if (!isset($row['name']) && isset($row['user_name'])) {
        $row['name'] = $row['user_name'];
    }

    return $row;
}

/** Surname (last name) of the household head, if known. */
function family_head_surname(array $family, array $members = []): string
{
    return trim(person_name_parts_from_row(family_head_row($family, $members))['last_name']);
}

/**
 * Display title for a household, e.g. "Patel Family".
 *
 * @param array<string,mixed> $family
 * @param list<array<string,mixed>> $members
 */
function family_household_title(array $family, array $members = []): string
{
    $rowForParts = family_head_row($family, $members);
    $parts = person_name_parts_from_row($rowForParts);
    $label = trim($parts['last_name']);
    if ($label === '') {
        $label = trim($parts['first_name']);
    }
    if ($label === '') {
        $label = user_display_name($rowForParts);
    }
    if ($label === '' || $label === t('common.user')) {
        return t('family.title');
    }

    return $label . ' ' . t('family.household_suffix');
}

/**
 * Page heading: "My Family" for the viewer's household, otherwise "{Surname} Family".
 *
 * @param array<string,mixed> $family
 * @param list<array<string,mixed>> $members
 */
function family_page_title(bool $isOwnFamily, array $family, array $members = []): string
{
    if ($isOwnFamily) {
        return t('dashboard.my_family');
    }

    return family_household_title($family, $members);
}

/**
 * @param array<string,mixed> $data
 * @return array{first_name:string,middle_name:?string,last_name:string}
 */
function person_name_parts_from_fields(array $data): array
{
    $first = trim((string) ($data['first_name'] ?? ''));
    $middle = trim((string) ($data['middle_name'] ?? ''));
    $last = trim((string) ($data['last_name'] ?? ''));
    if ($first !== '' || $last !== '') {
        return [
            'first_name' => $first,
            'middle_name' => $middle !== '' ? $middle : null,
            'last_name' => $last,
        ];
    }

    return split_person_full_name((string) ($data['name'] ?? ''));
}

/**
 * @param array<string,mixed> $post
 * @return array{ok:bool,error?:string,first_name?:string,middle_name?:?string,last_name?:string,full_name?:string}
 */
function parse_person_name_from_post(array $post, string $prefix = '', ?string $legacySingleKey = null): array
{
    $legacyKey = $legacySingleKey ?? ($prefix === '' ? 'name' : $prefix . 'name');
    $first = trim((string) ($post[$prefix . 'first_name'] ?? ''));
    $middleRaw = trim((string) ($post[$prefix . 'middle_name'] ?? ''));
    $last = trim((string) ($post[$prefix . 'last_name'] ?? ''));
    if ($first === '' && $last === '' && isset($post[$legacyKey])) {
        $split = split_person_full_name((string) $post[$legacyKey]);
        $first = $split['first_name'];
        $middleRaw = (string) ($split['middle_name'] ?? '');
        $last = $split['last_name'];
    }
    $middle = $middleRaw !== '' ? $middleRaw : null;
    if ($first === '' || $last === '') {
        return ['ok' => false, 'error' => t('validation.name_parts_required')];
    }

    return [
        'ok' => true,
        'first_name' => $first,
        'middle_name' => $middle,
        'last_name' => $last,
        'full_name' => build_person_full_name($first, $middle, $last),
    ];
}

/**
 * @return array{ok:bool,error?:string,phone?:?string}
 */
function provisioned_phone_from_post(string $raw, bool $required = true): array
{
    $phone = normalize_phone($raw !== '' ? $raw : null);
    if ($phone === null || strlen($phone) < 10) {
        if ($required) {
            return ['ok' => false, 'error' => t('validation.phone_required')];
        }

        return ['ok' => true, 'phone' => null];
    }
    if (strlen($phone) === 10) {
        $phone = '91' . $phone;
    }

    return ['ok' => true, 'phone' => $phone];
}

function user_photo_url(?string $photoPath): ?string
{
    if ($photoPath === null || trim($photoPath) === '') {
        return null;
    }
    $relative = ltrim(str_replace('\\', '/', $photoPath), '/');

    return base_url() . '/' . $relative;
}

/** Two-letter initials for avatar placeholder. */
function user_photo_initials(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '?';
    }
    $parts = preg_split('/\s+/', $name) ?: [];
    if (count($parts) >= 2) {
        return strtoupper(substr((string) $parts[0], 0, 1) . substr((string) $parts[count($parts) - 1], 0, 1));
    }

    return strtoupper(substr($name, 0, 2));
}

/**
 * Validate and store an uploaded profile photo for a user.
 *
 * @param array<string,mixed>|null $file $_FILES entry
 * @return array{ok:bool, error?:string, path?:string}
 */
function save_user_profile_photo(int $userId, ?array $file): array
{
    if ($userId < 1) {
        return ['ok' => false, 'error' => 'Invalid user.'];
    }
    if (!is_array($file) || !isset($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'Please choose a photo to upload.'];
    }
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Please choose a photo to upload.'];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Photo upload failed. Try a smaller image.'];
    }
    $tmp = (string) $file['tmp_name'];
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Invalid upload.'];
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size < 1 || $size > 2 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Photo must be 2 MB or smaller.'];
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mime = (string) finfo_file($finfo, $tmp);
            finfo_close($finfo);
        }
    }
    if ($mime === '') {
        $mime = (string) ($file['type'] ?? '');
    }
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Use a JPG, PNG, or WebP image.'];
    }
    $imageInfo = @getimagesize($tmp);
    if ($imageInfo === false) {
        return ['ok' => false, 'error' => 'File is not a valid image.'];
    }

    $ext = $allowed[$mime];
    $dir = user_photo_storage_dir();
    foreach (glob($dir . DIRECTORY_SEPARATOR . $userId . '.*') ?: [] as $old) {
        if (is_file($old)) {
            @unlink($old);
        }
    }
    $filename = $userId . '.' . $ext;
    $dest = $dir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($tmp, $dest)) {
        return ['ok' => false, 'error' => 'Could not save photo. Check folder permissions.'];
    }

    return ['ok' => true, 'path' => 'uploads/profile-photos/' . $filename];
}

function delete_user_profile_photo(?string $photoPath): void
{
    if ($photoPath === null || trim($photoPath) === '') {
        return;
    }
    $relative = ltrim(str_replace('\\', '/', $photoPath), '/');
    if (strpos($relative, 'uploads/profile-photos/') !== 0) {
        return;
    }
    $full = BASE_PATH . '/public/' . $relative;
    if (is_file($full)) {
        @unlink($full);
    }
}

/**
 * Duplicate a profile photo file for another user id. Returns relative path or null.
 */
function copy_user_profile_photo_for_user(int $fromUserId, int $toUserId, ?string $sourcePhotoPath): ?string
{
    if ($fromUserId < 1 || $toUserId < 1 || $fromUserId === $toUserId) {
        return null;
    }
    $relative = ltrim(str_replace('\\', '/', (string) $sourcePhotoPath), '/');
    if ($relative === '' || strpos($relative, 'uploads/profile-photos/') !== 0) {
        return null;
    }
    $src = BASE_PATH . '/public/' . $relative;
    if (!is_file($src)) {
        return null;
    }
    $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return null;
    }
    $dir = user_photo_storage_dir();
    foreach (glob($dir . DIRECTORY_SEPARATOR . $toUserId . '.*') ?: [] as $old) {
        if (is_file($old)) {
            @unlink($old);
        }
    }
    $filename = $toUserId . '.' . $ext;
    $dest = $dir . DIRECTORY_SEPARATOR . $filename;
    if (!@copy($src, $dest)) {
        return null;
    }

    return 'uploads/profile-photos/' . $filename;
}

/**
 * Parse ?sort= / ?dir= for list tables.
 *
 * @param list<string> $allowedColumns
 * @return array{0: string, 1: 'asc'|'desc'}
 */
function parse_table_sort(array $allowedColumns, string $defaultColumn, string $defaultDir = 'asc'): array
{
    $sort = strtolower(trim((string) ($_GET['sort'] ?? '')));
    $dir = strtolower(trim((string) ($_GET['dir'] ?? '')));
    if (!in_array($sort, $allowedColumns, true)) {
        $sort = $defaultColumn;
    }
    if ($dir !== 'asc' && $dir !== 'desc') {
        $dir = $defaultDir === 'desc' ? 'desc' : 'asc';
    }

    return [$sort, $dir];
}

/**
 * @param array<string, scalar|null> $preserve Query params to keep (e.g. filters)
 */
function table_sort_url(string $path, string $column, string $currentSort, string $currentDir, array $preserve = [], string $newColumnDefaultDir = 'asc'): string
{
    $nextDir = $newColumnDefaultDir === 'desc' ? 'desc' : 'asc';
    if ($currentSort === $column) {
        $nextDir = $currentDir === 'asc' ? 'desc' : 'asc';
    }
    $query = $preserve;
    $query['sort'] = $column;
    $query['dir'] = $nextDir;
    $qs = http_build_query($query);

    return base_url() . $path . ($qs !== '' ? '?' . $qs : '');
}

/**
 * Clickable sortable column header HTML.
 *
 * @param array<string, scalar|null> $preserve
 */
function sortable_th(
    string $label,
    string $column,
    string $currentSort,
    string $currentDir,
    string $path,
    array $preserve = [],
    string $newColumnDefaultDir = 'asc'
): string {
    $url = table_sort_url($path, $column, $currentSort, $currentDir, $preserve, $newColumnDefaultDir);
    $active = $currentSort === $column;
    $icon = 'mdi-swap-vertical';
    if ($active) {
        $icon = $currentDir === 'desc' ? 'mdi-arrow-down' : 'mdi-arrow-up';
    }
    $aria = $active
        ? ($currentDir === 'desc' ? t('common.sort_desc') : t('common.sort_asc'))
        : t('common.sort_by');

    return '<th scope="col" class="sa-sortable-th' . ($active ? ' is-sorted' : '') . '">'
        . '<a class="sa-sortable-link" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($aria, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        . ' <i class="mdi ' . $icon . ' sa-sort-icon" aria-hidden="true"></i>'
        . '</a></th>';
}

function org_notice_storage_dir(int $organizationId): string
{
    $orgId = max(1, $organizationId);
    $dir = BASE_PATH . '/public/uploads/org-notices/' . $orgId;
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    return $dir;
}

/** @return array{ok:true,path:string,original_filename:string,mime_type:string,file_size_bytes:int}|array{ok:false,error:string} */
function store_org_notice_upload(int $organizationId, ?array $file): array
{
    if ($organizationId < 1) {
        return ['ok' => false, 'error' => t('notices.upload_invalid')];
    }
    if ($file === null || !isset($file['error'])) {
        return ['ok' => false, 'error' => t('notices.upload_required')];
    }
    $error = (int) $file['error'];
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => t('notices.upload_required')];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => t('notices.upload_failed')];
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => t('notices.upload_invalid')];
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size < 1 || $size > 10 * 1024 * 1024) {
        return ['ok' => false, 'error' => t('notices.upload_too_large')];
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mime = (string) finfo_file($finfo, $tmp);
            finfo_close($finfo);
        }
    }
    if ($mime === '') {
        $mime = (string) ($file['type'] ?? '');
    }
    $allowed = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => t('notices.upload_type')];
    }

    $original = trim((string) ($file['name'] ?? 'notice.' . $allowed[$mime]));
    if ($original === '') {
        $original = 'notice.' . $allowed[$mime];
    }
    $original = preg_replace('/[^\w.\- ()]+/u', '_', $original) ?? 'notice.' . $allowed[$mime];
    if (strlen($original) > 200) {
        $original = substr($original, -200);
    }

    $ext = $allowed[$mime];
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = org_notice_storage_dir($organizationId) . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($tmp, $dest)) {
        return ['ok' => false, 'error' => t('notices.upload_save_failed')];
    }

    return [
        'ok' => true,
        'path' => 'uploads/org-notices/' . $organizationId . '/' . $filename,
        'original_filename' => $original,
        'mime_type' => $mime,
        'file_size_bytes' => $size,
    ];
}

function delete_org_notice_file(?string $relativePath): void
{
    if ($relativePath === null || trim($relativePath) === '') {
        return;
    }
    $relative = ltrim(str_replace('\\', '/', $relativePath), '/');
    if (!preg_match('#^uploads/org-notices/\d+/[a-zA-Z0-9._-]+$#', $relative)) {
        return;
    }
    $full = BASE_PATH . '/public/' . $relative;
    if (is_file($full)) {
        @unlink($full);
    }
}

function org_notice_file_type_label(string $mimeType): string
{
    if ($mimeType === 'application/pdf') {
        return 'PDF';
    }
    if ($mimeType === 'application/msword') {
        return 'DOC';
    }
    if ($mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
        return 'DOCX';
    }

    return 'FILE';
}

function format_file_size(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }

    return round($bytes / (1024 * 1024), 1) . ' MB';
}
