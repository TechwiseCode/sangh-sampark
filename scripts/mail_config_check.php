<?php

declare(strict_types=1);

/**
 * Check mail configuration (no email sent).
 * Run on server: php scripts/mail_config_check.php
 */

require dirname(__DIR__) . '/app/bootstrap.php';

$cfg = mail_config();
echo 'SMTP enabled: ' . (smtp_mail_enabled() ? 'yes' : 'NO — forgot password will fail') . PHP_EOL;
echo 'MAIL_FROM: ' . system_mail_from() . PHP_EOL;
echo 'SMTP_HOST: ' . ($cfg['smtp_host'] ?? '(empty)') . PHP_EOL;
echo 'SMTP_PORT: ' . ($cfg['smtp_port'] ?? '') . PHP_EOL;
echo 'SMTP_USER: ' . ($cfg['smtp_user'] ?? '(empty)') . PHP_EOL;
echo 'SMTP_PASS set: ' . (($cfg['smtp_pass'] ?? '') !== '' ? 'yes' : 'NO') . PHP_EOL;
echo 'PHPMailer: ' . (class_exists(PHPMailer\PHPMailer\PHPMailer::class) ? 'yes' : 'missing') . PHP_EOL;
echo 'PHP CLI (background mail): ' . php_cli_binary() . PHP_EOL;
echo 'APP_URL: ' . base_url() . PHP_EOL;

if (!smtp_mail_enabled()) {
    echo PHP_EOL . 'Fix: add SMTP_HOST, SMTP_USER, SMTP_PASS to .env on this server.' . PHP_EOL;
    exit(1);
}

echo PHP_EOL . 'Mail config looks OK. Test send: php scripts/test_mail.php you@example.com' . PHP_EOL;
exit(0);
