<?php

declare(strict_types=1);

/**
 * PDO (MySQL) — one database per organization deploy.
 * See docs/ORG-DEPLOYMENT.md and .env (DATABASE_*).
 */
$local = __DIR__ . '/database.local.php';
if (is_file($local)) {
    return require $local;
}

return [
    'driver' => 'mysql',
    'host' => getenv('DATABASE_HOST') ?: (getenv('SAAS_DB_HOST') ?: '127.0.0.1'),
    'port' => (int) (getenv('DATABASE_PORT') ?: (getenv('SAAS_DB_PORT') ?: 3306)),
    'database' => getenv('DATABASE_NAME') ?: (getenv('SAAS_DB_NAME') ?: 'szvs'),
    'username' => getenv('DATABASE_USER') ?: (getenv('SAAS_DB_USER') ?: 'root'),
    'password' => getenv('DATABASE_PASSWORD') !== false
        ? (string) getenv('DATABASE_PASSWORD')
        : (getenv('SAAS_DB_PASS') !== false ? (string) getenv('SAAS_DB_PASS') : ''),
    'charset' => 'utf8mb4',
];
