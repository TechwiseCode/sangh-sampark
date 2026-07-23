<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/load-env.php';
load_dotenv(BASE_PATH . '/.env');

$config = require BASE_PATH . '/config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionLifetime = (int) (getenv('SESSION_LIFETIME') ?: 2592000);
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $cookiePath = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if ($cookiePath === '' || $cookiePath === '.') {
        $cookiePath = '/';
    } else {
        $publicSuffix = '/public';
        if (substr($cookiePath, -strlen($publicSuffix)) === $publicSuffix) {
            $parent = substr($cookiePath, 0, -strlen($publicSuffix));
            $cookiePath = $parent !== '' ? $parent : '/';
        }
        if ($cookiePath !== '/') {
            $cookiePath .= '/';
        }
    }
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_SSL']) === 'on');
    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path' => $cookiePath,
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
    session_name($config['session_name']);
    session_start();
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

require_once BASE_PATH . '/app/helpers.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    session_clear_stale_cookies();
}

send_security_headers();

// Drop stale sessions when the user was deleted (avoids login↔dashboard redirect loops).
// Skip on POST so long-running form submits are not blocked by session locks from other tabs.
if (
    !empty($_SESSION['user']['id'])
    && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST'
) {
    try {
        $sessionUserId = (int) $_SESSION['user']['id'];
        $users = new \App\Models\User();
        $freshUser = $users->findById($sessionUserId);
        if ($freshUser === null) {
            unset($_SESSION['user'], $_SESSION['current_organization_id'], $_SESSION['intended_url']);
        } else {
            $_SESSION['user'] = $users->toSessionArray($freshUser);
        }
    } catch (\Throwable $e) {
        // Leave session as-is if DB is unavailable during bootstrap.
    }
}
