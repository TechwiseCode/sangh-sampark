<?php

declare(strict_types=1);

/**
 * One-time: create family_membership_requests + notifications tables.
 * Run: php database/apply_family_requests_tables.php
 */

$base = dirname(__DIR__);
$c = require $base . '/config/database.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $c['host'],
    $c['port'],
    $c['database'],
    $c['charset']
);

$sqlFile = __DIR__ . '/family_membership_requests.sql';
if (!is_readable($sqlFile)) {
    fwrite(STDERR, "Missing {$sqlFile}\n");
    exit(1);
}

$raw = file_get_contents($sqlFile);
if ($raw === false) {
    fwrite(STDERR, "Could not read SQL file.\n");
    exit(1);
}

// Strip full-line comments; split on semicolons.
$lines = preg_split('/\R/', $raw);
$buf = '';
foreach ($lines as $line) {
    $t = trim($line);
    if ($t === '' || strpos($t, '--') === 0) {
        continue;
    }
    $buf .= ' ' . $line;
}
$parts = array_filter(array_map('trim', explode(';', $buf)));

$pdo = new PDO($dsn, $c['username'], $c['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

foreach ($parts as $stmt) {
    if ($stmt === '') {
        continue;
    }
    $pdo->exec($stmt);
}

echo "OK: tables family_membership_requests and notifications are ready on database {$c['database']}.\n";
