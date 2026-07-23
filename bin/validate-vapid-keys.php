<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

echo "VAPID validation\n";
echo "================\n\n";

if (!class_exists(\Minishlink\WebPush\VAPID::class)) {
    fwrite(STDERR, "Missing minishlink/web-push. From project root run:\n  composer install --no-dev\nOr upload the vendor/ folder from your dev machine.\n");
    exit(1);
}

$public = trim((string) app_config('vapid_public_key', ''));
$private = trim((string) app_config('vapid_private_key', ''));
$subject = trim((string) app_config('vapid_subject', ''));

$checks = [
    'VAPID_PUBLIC_KEY set' => $public !== '',
    'VAPID_PRIVATE_KEY set' => $private !== '',
    'VAPID_SUBJECT set' => $subject !== '',
    'Subject is mailto: or https:' => $subject !== '' && (str_starts_with($subject, 'mailto:') || str_starts_with($subject, 'https://')),
];

foreach ($checks as $label => $ok) {
    echo ($ok ? '[OK] ' : '[FAIL] ') . $label . "\n";
}

if (in_array(false, $checks, true)) {
    echo "\nFix .env and run again.\n";
    exit(1);
}

try {
    \Minishlink\WebPush\VAPID::validate([
        'subject' => $subject,
        'publicKey' => $public,
        'privateKey' => $private,
    ]);
    echo "[OK] Key pair format is valid (public + private match expected lengths)\n";
} catch (\Throwable $e) {
    echo '[FAIL] ' . $e->getMessage() . "\n";
    echo "\nRegenerate keys: php bin/generate-vapid-keys.php\n";
    exit(1);
}

echo "\nweb_push_is_configured(): " . (web_push_is_configured() ? 'true' : 'false') . "\n";
if (!web_push_package_available()) {
    echo "\n[FAIL] minishlink/web-push package not loaded.\n";
    echo "Fix: upload vendor/ from your PC, or SSH to server and run:\n";
    echo "  cd /path/to/sanghsampark && composer install --no-dev\n";
    exit(1);
}
echo "Public key (first 20 chars): " . substr($public, 0, 20) . "...\n";
echo "\nNext: sign in to the app → Settings → Notifications.\n";
echo "  - Should NOT say \"Push is not configured on the server\"\n";
echo "  - Tap \"Enable push notifications\" (from Home Screen app on iPhone)\n";
echo "\nAPI check (while logged in): GET /organization/notifications/push/vapid-public-key\n";
echo "  Should return JSON: {\"ok\":true,\"publicKey\":\"...\"}\n";
