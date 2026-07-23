<?php

declare(strict_types=1);

/**
 * Test outbound email (forgot-password style).
 *
 * Usage:
 *   php scripts/test_mail.php you@example.com
 */

require dirname(__DIR__) . '/app/bootstrap.php';

$to = trim((string) ($argv[1] ?? ''));
if ($to === '') {
    fwrite(STDERR, "Usage: php scripts/test_mail.php recipient@example.com\n");
    exit(1);
}

$ok = system_send_email(
    $to,
    'Test email from ' . app_name(),
    "Hello,\n\nIf you received this, SMTP is configured correctly.\n\n— " . app_name()
);

if ($ok) {
    echo "Sent to {$to}\n";
    echo 'Mode: ' . (smtp_mail_enabled() ? 'SMTP (' . (mail_config()['smtp_host'] ?? '') . ')' : 'PHP mail()') . "\n";
    exit(0);
}

echo "Failed to send. Check .env SMTP_* settings and PHP error log.\n";
echo 'SMTP enabled: ' . (smtp_mail_enabled() ? 'yes' : 'no') . "\n";
exit(1);
