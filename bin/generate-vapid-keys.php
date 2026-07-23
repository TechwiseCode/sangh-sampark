<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

if (!class_exists(\Minishlink\WebPush\VAPID::class)) {
    fwrite(STDERR, "Run: composer require minishlink/web-push\n");
    exit(1);
}

$keys = \Minishlink\WebPush\VAPID::createVapidKeys();
echo "Add these to your .env file:\n\n";
echo 'VAPID_PUBLIC_KEY=' . $keys['publicKey'] . "\n";
echo 'VAPID_PRIVATE_KEY=' . $keys['privateKey'] . "\n";
echo "VAPID_SUBJECT=mailto:admin@example.com\n";
