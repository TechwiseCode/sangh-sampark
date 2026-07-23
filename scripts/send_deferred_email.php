<?php

declare(strict_types=1);

/**
 * Send a queued invite email (spawned in the background after admin/member create).
 *
 * Usage: php scripts/send_deferred_email.php <job_id>
 */

require dirname(__DIR__) . '/app/bootstrap.php';

$jobId = trim((string) ($argv[1] ?? ''));
if ($jobId === '' || !preg_match('/^[a-f0-9]{32}$/', $jobId)) {
    fwrite(STDERR, "Usage: php scripts/send_deferred_email.php <32-char-job-id>\n");
    exit(1);
}

$path = BASE_PATH . '/storage/deferred_emails/' . $jobId . '.json';
if (!is_file($path)) {
    fwrite(STDERR, "Job not found: {$jobId}\n");
    exit(1);
}

if (!deliver_deferred_email_file($path)) {
    fwrite(STDERR, "Deferred email send failed: {$jobId}\n");
    exit(1);
}

exit(0);
