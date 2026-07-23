<?php

declare(strict_types=1);

/**
 * Outbound email (Gmail SMTP via PHPMailer when SMTP_HOST is set).
 *
 * Gmail setup:
 * 1. Google Account → Security → turn on 2-Step Verification
 * 2. App passwords → create one for "Mail" / "Other"
 * 3. Put your Gmail address in SMTP_USER and the 16-char app password in SMTP_PASS
 */
return [
    'from' => getenv('MAIL_FROM') ?: 'no-reply@sanghsampark.com',
    'from_name' => getenv('MAIL_FROM_NAME') ?: (getenv('APP_NAME') ?: 'SanghSampark'),
    'smtp_host' => trim((string) (getenv('SMTP_HOST') ?: '')),
    'smtp_port' => (int) (getenv('SMTP_PORT') ?: 587),
    'smtp_user' => trim((string) (getenv('SMTP_USER') ?: '')),
    'smtp_pass' => (string) (getenv('SMTP_PASS') ?: ''),
    // tls (STARTTLS on 587) or ssl (465). Empty = auto from port.
    'smtp_secure' => strtolower(trim((string) (getenv('SMTP_SECURE') ?: ''))),
    // Seconds before SMTP connect/send gives up (avoids hung form submits).
    'smtp_timeout' => (int) (getenv('SMTP_TIMEOUT') ?: 15),
];
