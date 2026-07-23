<?php

declare(strict_types=1);

/**
 * Evening reminders (push only, no DB storage) sent the day before.
 * Covers both pre-fixed tithis and organization calendar days.
 *
 * Schedule at 23:00 in APP_TIMEZONE:
 *   php scripts/send_scheduled_tithi_reminders.php
 *
 * Options:
 *   --force       Send even outside the configured hour window
 *   --dry-run     Show what would be sent without sending
 *   --date=YYYY-MM-DD  Treat this as "today" (testing)
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/bootstrap.php';

use App\Services\CalendarDayNotificationService;
use App\Services\ScheduledTithiReminderService;

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

$tithiService = new ScheduledTithiReminderService();
$calendarService = new CalendarDayNotificationService();

if (!$tithiService->isEnabled() && !$calendarService->isEnabled()) {
    fwrite(STDOUT, "Evening reminders are disabled (tithi and calendar day).\n");
    exit(0);
}

$tzName = trim((string) app_config('timezone', 'Asia/Kolkata'));
try {
    date_default_timezone_set($tzName !== '' ? $tzName : 'Asia/Kolkata');
} catch (\Throwable $e) {
    date_default_timezone_set('Asia/Kolkata');
}

if (!$force && !$dryRun && !$tithiService->isWithinSendWindow()) {
    $now = new DateTimeImmutable('now');
    fwrite(STDOUT, sprintf(
        "Outside send window (now %s %s; target %02d:%02d). Use --force to override.\n",
        $now->format('Y-m-d H:i'),
        $tzName,
        (int) app_config('scheduled_tithi_hour', 23),
        (int) app_config('scheduled_tithi_minute', 0)
    ));
    exit(0);
}

$today = $dateArg ?? $tithiService->todayInAppTimezone();
$failed = 0;

// --- 1. Pre-fixed tithi reminders --------------------------------------------
if ($tithiService->isEnabled()) {
    $match = $tithiService->matchForTomorrow($today);
    if ($match === null) {
        fwrite(STDOUT, sprintf(
            "Tithi: none configured tomorrow (%s). Watching: %s\n",
            (string) ($tithiService->tomorrowFrom($today) ?? '?'),
            implode(', ', $tithiService->configuredTithis())
        ));
    } else {
        fwrite(STDOUT, ($dryRun ? '[DRY RUN] ' : '') . sprintf(
            "Tithi: tomorrow %s is %s\n",
            $match['date'],
            $match['tithi_label']
        ));
        $summary = $tithiService->sendForAllOrganizations($today, $dryRun);
        foreach ($summary['results'] as $row) {
            $name = (string) ($row['organization_name'] ?? ('org#' . ($row['organization_id'] ?? '?')));
            if (!empty($row['skipped'])) {
                fwrite(STDOUT, "  - {$name}: skipped (" . (string) ($row['reason'] ?? 'skipped') . ")\n");
                continue;
            }
            if (empty($row['ok'])) {
                $failed++;
                fwrite(STDERR, "  - {$name}: FAILED (" . (string) ($row['reason'] ?? 'failed') . ")\n");
                continue;
            }
            fwrite(STDOUT, sprintf(
                "  - %s: push to %d user(s), delivered %d\n",
                $name,
                (int) ($row['recipients'] ?? 0),
                (int) ($row['push_sent'] ?? 0)
            ));
        }
    }
} else {
    fwrite(STDOUT, "Tithi reminders disabled.\n");
}

fwrite(STDOUT, "\n");

// --- 2. Organization calendar-day reminders ----------------------------------
if ($calendarService->isEnabled()) {
    $calSummary = $calendarService->sendDayBeforeForAllOrganizations($today, $dryRun);
    fwrite(STDOUT, ($dryRun ? '[DRY RUN] ' : '') . sprintf(
        "Calendar days starting tomorrow (%s): %d\n",
        (string) ($calSummary['tomorrow'] ?? '?'),
        (int) $calSummary['days']
    ));
    foreach ($calSummary['results'] as $row) {
        $name = (string) ($row['organization_name'] ?? ('org#' . ($row['organization_id'] ?? '?')));
        $title = (string) ($row['title'] ?? '');
        if (!empty($row['skipped'])) {
            fwrite(STDOUT, "  - {$name} / {$title}: skipped (" . (string) ($row['reason'] ?? 'skipped') . ")\n");
            continue;
        }
        fwrite(STDOUT, sprintf(
            "  - %s / %s: push delivered %d\n",
            $name,
            $title,
            (int) ($row['push_sent'] ?? 0)
        ));
    }
    fwrite(STDOUT, sprintf(
        "Calendar day totals: %d org(s), %d day(s), push delivered %d.\n",
        (int) $calSummary['organizations'],
        (int) $calSummary['days'],
        (int) $calSummary['push_sent']
    ));
} else {
    fwrite(STDOUT, "Calendar-day reminders disabled.\n");
}

exit($failed > 0 ? 1 : 0);
