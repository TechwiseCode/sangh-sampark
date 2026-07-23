<?php

declare(strict_types=1);

/**
 * Daily tithi direct web push for all org members.
 * No in-app notification, campaign, or delivery log is stored.
 *
 * Schedule (Windows Task Scheduler / cron) at 07:00 in APP_TIMEZONE:
 *   php scripts/send_daily_tithi_notifications.php
 *
 * Options:
 *   --force       Send even outside the configured hour window
 *   --dry-run     Show what would be sent without sending
 *   --date=YYYY-MM-DD  Use a specific date (testing)
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/bootstrap.php';

use App\Services\DailyTithiNotificationService;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$force = in_array('--force', $argv, true);
$dryRun = in_array('--dry-run', $argv, true);
$dateArg = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--date=') === 0) {
        $dateArg = substr($arg, 7);
    }
}

if (!daily_tithi_notifications_enabled()) {
    fwrite(STDOUT, "Daily tithi notifications are disabled (set DAILY_TITHI_NOTIFICATIONS=true in .env).\n");
    exit(0);
}

$tzName = trim((string) app_config('timezone', 'Asia/Kolkata'));
try {
    date_default_timezone_set($tzName !== '' ? $tzName : 'Asia/Kolkata');
} catch (\Throwable $e) {
    date_default_timezone_set('Asia/Kolkata');
}

$service = new DailyTithiNotificationService();

if (!$force && !$dryRun && !$service->isWithinSendWindow()) {
    $now = new DateTimeImmutable('now');
    fwrite(STDOUT, sprintf(
        "Outside send window (now %s %s; target %02d:%02d). Use --force to override.\n",
        $now->format('Y-m-d H:i'),
        $tzName,
        (int) app_config('daily_tithi_hour', 7),
        (int) app_config('daily_tithi_minute', 0)
    ));
    exit(0);
}

$date = $dateArg ?? $service->todayInAppTimezone();
if ($service->buildPayloadForDate($date, 'en') === null && $service->buildPayloadForDate($date, 'gu') === null) {
    fwrite(STDERR, "No panchang data for {$date}. Import panchang CSV first.\n");
    exit(1);
}

fwrite(STDOUT, ($dryRun ? '[DRY RUN] ' : '') . "Daily tithi for {$date}\n");
$payloadEn = $service->buildPayloadForDate($date, 'en');
$payloadGu = $service->buildPayloadForDate($date, 'gu');
if ($payloadEn !== null) {
    fwrite(STDOUT, "EN title: " . $payloadEn['title'] . "\n");
    fwrite(STDOUT, "EN message: " . str_replace("\n", ' / ', $payloadEn['message']) . "\n");
}
if ($payloadGu !== null) {
    fwrite(STDOUT, "GU title: " . $payloadGu['title'] . "\n");
    fwrite(STDOUT, "GU message: " . str_replace("\n", ' / ', $payloadGu['message']) . "\n");
}
fwrite(STDOUT, "\n");

$summary = $service->sendForAllOrganizations($date, $dryRun);

foreach ($summary['results'] as $row) {
    $name = (string) ($row['organization_name'] ?? ('org#' . ($row['organization_id'] ?? '?')));
    if (!empty($row['skipped'])) {
        $reason = (string) ($row['reason'] ?? 'skipped');
        $loc = isset($row['locale_counts']) && is_array($row['locale_counts'])
            ? ' [en:' . (int) ($row['locale_counts']['en'] ?? 0) . ' gu:' . (int) ($row['locale_counts']['gu'] ?? 0) . ']'
            : '';
        fwrite(STDOUT, "  - {$name}: skipped ({$reason}){$loc}\n");
        continue;
    }
    if (empty($row['ok'])) {
        $reason = (string) ($row['reason'] ?? 'failed');
        fwrite(STDERR, "  - {$name}: FAILED ({$reason})\n");
        continue;
    }
    $loc = isset($row['locale_counts']) && is_array($row['locale_counts'])
        ? ' [en:' . (int) ($row['locale_counts']['en'] ?? 0) . ' gu:' . (int) ($row['locale_counts']['gu'] ?? 0) . ']'
        : '';
    fwrite(STDOUT, sprintf(
        "  - %s: sent to %d member(s), push %d%s\n",
        $name,
        (int) ($row['recipients'] ?? 0),
        (int) ($row['push_sent'] ?? 0),
        $loc
    ));
}

fwrite(STDOUT, sprintf(
    "\nDone: %d org(s), %d sent, %d skipped, %d failed.\n",
    (int) $summary['organizations'],
    (int) $summary['sent'],
    (int) $summary['skipped'],
    (int) $summary['failed']
));

exit($summary['failed'] > 0 ? 1 : 0);
