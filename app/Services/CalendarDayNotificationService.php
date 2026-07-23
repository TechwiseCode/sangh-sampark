<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\OrgCalendarDay;

/**
 * Push-only notifications for organization calendar days.
 * Fires immediately when a day is created and again at 11 PM the day before.
 * Nothing is stored in the database (no in-app rows / campaigns).
 */
final class CalendarDayNotificationService
{
    public function isEnabled(): bool
    {
        return calendar_day_notifications_enabled();
    }

    /**
     * Immediate push when an admin adds a calendar day.
     *
     * @param array<string,mixed> $day
     */
    public function notifyCreated(int $organizationId, array $day): int
    {
        if (!$this->isEnabled() || $organizationId < 1) {
            return 0;
        }

        return $this->broadcast($organizationId, $day, 'created');
    }

    public function todayInAppTimezone(): string
    {
        return (new \DateTimeImmutable('now', $this->appTimezone()))->format('Y-m-d');
    }

    public function isWithinSendWindow(?\DateTimeImmutable $now = null): bool
    {
        $now = $now ?? new \DateTimeImmutable('now', $this->appTimezone());
        $hour = (int) app_config('scheduled_tithi_hour', 23);
        $minute = (int) app_config('scheduled_tithi_minute', 0);
        $windowMinutes = (int) app_config('scheduled_tithi_window_minutes', 30);

        $target = $now->setTime(max(0, min(23, $hour)), max(0, min(59, $minute)), 0);
        $diff = abs($now->getTimestamp() - $target->getTimestamp());

        return $diff <= max(1, $windowMinutes) * 60;
    }

    public function tomorrowFrom(string $dateYmd): ?string
    {
        $dateYmd = $this->normalizeDate($dateYmd);
        if ($dateYmd === null) {
            return null;
        }
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd, $this->appTimezone());
        if ($dt === false) {
            return null;
        }

        return $dt->modify('+1 day')->format('Y-m-d');
    }

    /**
     * Day-before reminders for every organization for days starting tomorrow.
     *
     * @return array{today:string,tomorrow?:string,organizations:int,days:int,push_sent:int,results:list<array<string,mixed>>}
     */
    public function sendDayBeforeForAllOrganizations(?string $todayYmd = null, bool $dryRun = false): array
    {
        $todayYmd = $this->normalizeDate($todayYmd ?? $this->todayInAppTimezone()) ?? $this->todayInAppTimezone();
        $tomorrow = $this->tomorrowFrom($todayYmd);
        $orgs = (new Organization())->listAll('name', 'asc');
        $summary = [
            'today' => $todayYmd,
            'tomorrow' => $tomorrow,
            'organizations' => count($orgs),
            'days' => 0,
            'push_sent' => 0,
            'results' => [],
        ];

        if ($tomorrow === null || !$this->isEnabled()) {
            return $summary;
        }

        $dayModel = new OrgCalendarDay();
        foreach ($orgs as $org) {
            $orgId = (int) ($org['id'] ?? 0);
            if ($orgId < 1) {
                continue;
            }
            $days = $dayModel->listStartingOn($orgId, $tomorrow);
            foreach ($days as $day) {
                $dayId = (int) ($day['id'] ?? 0);
                if ($dayId < 1) {
                    continue;
                }
                $summary['days']++;
                if ($this->alreadySent($orgId, $tomorrow, $dayId)) {
                    $summary['results'][] = [
                        'organization_id' => $orgId,
                        'organization_name' => (string) ($org['name'] ?? ''),
                        'day_id' => $dayId,
                        'title' => (string) ($day['title'] ?? ''),
                        'skipped' => true,
                        'reason' => 'already_sent',
                    ];
                    continue;
                }
                if ($dryRun) {
                    $summary['results'][] = [
                        'organization_id' => $orgId,
                        'organization_name' => (string) ($org['name'] ?? ''),
                        'day_id' => $dayId,
                        'title' => (string) ($day['title'] ?? ''),
                        'skipped' => true,
                        'reason' => 'dry_run',
                    ];
                    continue;
                }
                $pushSent = $this->broadcast($orgId, $day, 'tomorrow');
                $this->markSent($orgId, $tomorrow, $dayId, $pushSent);
                $summary['push_sent'] += $pushSent;
                $summary['results'][] = [
                    'organization_id' => $orgId,
                    'organization_name' => (string) ($org['name'] ?? ''),
                    'day_id' => $dayId,
                    'title' => (string) ($day['title'] ?? ''),
                    'push_sent' => $pushSent,
                ];
            }
        }

        return $summary;
    }

    /**
     * @param array<string,mixed> $day
     * @param 'created'|'tomorrow' $mode
     */
    private function broadcast(int $organizationId, array $day, string $mode): int
    {
        $recipients = (new Organization())->listAllNotificationRecipientRows($organizationId);
        if ($recipients === []) {
            return 0;
        }

        $pushSent = 0;
        $dashboardUrl = base_url() . '/organization/dashboard';
        $webPush = new WebPushService();
        $dayId = (int) ($day['id'] ?? 0);

        foreach ($recipients as $recipient) {
            $uid = (int) ($recipient['id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            $locale = user_notification_locale($recipient['preferred_locale'] ?? null);
            $payload = $this->buildPayload($day, $locale, $mode);
            if ($payload === null) {
                continue;
            }
            try {
                $pushSent += $webPush->sendToUser($uid, $payload['title'], $payload['message'], [
                    'type' => 'calendar_day',
                    'url' => $dashboardUrl,
                    'locale' => $locale,
                    'tag' => 'calendar-day-' . $mode . '-' . $dayId,
                ]);
            } catch (\Throwable $e) {
                // Push-only; nothing persisted.
            }
        }

        return $pushSent;
    }

    /**
     * @param array<string,mixed> $day
     * @param 'created'|'tomorrow' $mode
     * @return array{title:string,message:string}|null
     */
    public function buildPayload(array $day, string $locale, string $mode): ?array
    {
        $locale = user_notification_locale($locale);
        $title = trim((string) ($day['title'] ?? ''));
        if ($locale === 'gu') {
            $titleGu = trim((string) ($day['title_gu'] ?? ''));
            if ($titleGu !== '') {
                $title = $titleGu;
            }
        }
        if ($title === '') {
            return null;
        }

        $category = normalize_org_calendar_day_category((string) ($day['category'] ?? 'other'));
        $categoryLabel = org_calendar_day_category_label_for_locale($category, $locale);
        $startDate = trim((string) ($day['start_date'] ?? ''));
        $endDate = trim((string) ($day['end_date'] ?? ''));
        $dateLabel = $this->formatDateRange($startDate, $endDate, $locale);

        $eventTime = trim((string) ($day['event_time'] ?? ''));
        if ($eventTime !== '' && org_calendar_day_shows_event_time($category)) {
            $dateLabel = trim($dateLabel . ' · ' . $this->formatTime($eventTime));
        }

        $pushTitleKey = $mode === 'created'
            ? 'calendar_days.notify.created_title'
            : 'calendar_days.notify.tomorrow_title';
        $pushTitle = t_for_locale($pushTitleKey, $locale, ['title' => $title]);

        $messageParts = [];
        if ($categoryLabel !== '') {
            $messageParts[] = $categoryLabel;
        }
        if ($dateLabel !== '') {
            $messageParts[] = $dateLabel;
        }
        $message = implode(' · ', $messageParts);

        $notes = trim((string) ($day['notes'] ?? ''));
        if ($notes !== '') {
            $message .= "\n" . $notes;
        }
        if ($message === '') {
            $message = $title;
        }

        return [
            'title' => $pushTitle,
            'message' => $message,
        ];
    }

    private function appTimezone(): \DateTimeZone
    {
        $tz = trim((string) app_config('timezone', 'Asia/Kolkata'));
        try {
            return new \DateTimeZone($tz !== '' ? $tz : 'Asia/Kolkata');
        } catch (\Throwable $e) {
            return new \DateTimeZone('Asia/Kolkata');
        }
    }

    private function normalizeDate(?string $dateYmd): ?string
    {
        $dateYmd = trim((string) $dateYmd);
        if ($dateYmd === '') {
            return null;
        }
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd);
        if ($dt === false || $dt->format('Y-m-d') !== $dateYmd) {
            return null;
        }

        return $dateYmd;
    }

    private function formatDateRange(string $startYmd, string $endYmd, string $locale): string
    {
        $start = $this->formatDate($startYmd, $locale);
        if ($start === '') {
            return '';
        }
        if ($endYmd !== '' && $endYmd !== $startYmd) {
            $end = $this->formatDate($endYmd, $locale);
            if ($end !== '') {
                return $start . ' – ' . $end;
            }
        }

        return $start;
    }

    private function formatDate(string $dateYmd, string $locale): string
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd);
        if ($dt === false) {
            return '';
        }
        $monthNum = (int) $dt->format('n');
        $monthLabel = t_for_locale('calendar.month_' . $monthNum, $locale);

        return $dt->format('j') . ' ' . $monthLabel . ' ' . $dt->format('Y');
    }

    private function formatTime(string $time): string
    {
        $dt = \DateTimeImmutable::createFromFormat('H:i:s', $time)
            ?: \DateTimeImmutable::createFromFormat('H:i', $time);
        if ($dt === false) {
            return $time;
        }

        return $dt->format('g:i A');
    }

    private function stateDir(): string
    {
        $dir = BASE_PATH . '/storage/cache/calendar_day_reminders';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private function markerPath(int $organizationId, string $dateYmd, int $dayId): string
    {
        return $this->stateDir() . '/' . $dateYmd . '_day' . $dayId . '_org' . $organizationId . '.sent';
    }

    private function alreadySent(int $organizationId, string $dateYmd, int $dayId): bool
    {
        return is_file($this->markerPath($organizationId, $dateYmd, $dayId));
    }

    private function markSent(int $organizationId, string $dateYmd, int $dayId, int $pushSent): void
    {
        $path = $this->markerPath($organizationId, $dateYmd, $dayId);
        $payload = json_encode([
            'organization_id' => $organizationId,
            'date' => $dateYmd,
            'day_id' => $dayId,
            'push_sent' => $pushSent,
            'sent_at' => (new \DateTimeImmutable('now', $this->appTimezone()))->format(DATE_ATOM),
        ], JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            $payload = '{}';
        }
        file_put_contents($path, $payload);
    }
}
