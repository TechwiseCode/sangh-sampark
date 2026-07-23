<?php

declare(strict_types=1);

/**
 * Process all queued outbound emails (cron backup for online servers).
 *
 * Cron (every minute):
 *   * * * * * cd /path/to/szvs-tenant && php scripts/process_deferred_emails.php
 */

require dirname(__DIR__) . '/app/bootstrap.php';

$sent = process_pending_deferred_emails(20);
$dir = BASE_PATH . '/storage/deferred_emails';
$remaining = 0;
if (is_dir($dir)) {
    $remaining = count(glob($dir . '/*.json') ?: []);
}

echo 'Sent: ' . $sent . ', remaining in queue: ' . $remaining . PHP_EOL;
exit($sent > 0 || $remaining === 0 ? 0 : 1);
