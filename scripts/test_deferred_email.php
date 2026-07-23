<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$data = [
    'email' => $argv[1] ?? 'sanghsamparkadmin@gmail.com',
    'name' => 'Deferred test',
    'password' => 'SzTest12!',
    'verify_url' => base_url() . '/login',
    'org_code' => 'TEST',
    'login_url' => base_url() . '/login',
];

$queued = queue_deferred_email('invite_with_password', $data);
echo 'Deferred queued: ' . ($queued ? 'yes' : 'no (sync fallback)') . PHP_EOL;
echo 'PHP CLI: ' . php_cli_binary() . PHP_EOL;
