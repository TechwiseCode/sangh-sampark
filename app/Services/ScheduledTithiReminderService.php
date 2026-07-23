<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\PlatformPanchangDay;

/**
 * Push-only reminder at 11 PM the day before selected panchang tithis.
 * No in-app notification or campaign rows are stored.
 */
final class ScheduledTithiReminderService
{
    /** @var list<string>|null */
    private ?array $configuredTithis = null;

    /** @return list<string> */
    public function configuredTithis(): array
    {
        if ($this->configuredTithis !== null) {
            return $this->configuredTithis;
        }
        $path = BASE_PATH . '/config/tithi_reminders.php';
        $cfg = is_file($path) ? require $path : ['tithis' => []];
        $rows = $cfg['tithis'] ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }
        $normalized = [];
        foreach ($rows as $row) {
            $key = normalize_tithi_match_key((string) $row);
            if ($key !== '') {
                $normalized[] = $key;
            }
        }

        return $this->configuredTithis = array_values(array_unique($normalized));
    }

    public function isEnabled(): bool
    {
        return scheduled_tithi_reminders_enabled() && $this->configuredTithis() !== [];
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

    public function todayInAppTimezone(): string
    {
        return (new \DateTimeImmutable('now', $this->appTimezone()))->format('Y-m-d');
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
     * @return array{date:string,tithi_key:string,tithi_label:string,row:array<string,mixed>}|null
     */
    public function matchForTomorrow(string $todayYmd): ?array
    {
        $tomorrow = $this->tomorrowFrom($todayYmd);
        if ($tomorrow === null) {
            return null;
        }

        $map = (new PlatformPanchangDay())->mapForDateRange($tomorrow, $tomorrow);
        $row = $map[$tomorrow] ?? null;
        if (!is_array($row)) {
            return null;
        }

        $tithiLabel = trim((string) ($row['tithi'] ?? ''));
        if ($tithiLabel === '') {
            return null;
        }

        $tithiKey = normalize_tithi_match_key($tithiLabel);
        if ($tithiKey === '' || !in_array($tithiKey, $this->configuredTithis(), true)) {
            return null;
        }

        return [
            'date' => $tomorrow,
            'tithi_key' => $tithiKey,
            'tithi_label' => $tithiLabel,
            'row' => $row,
        ];
    }

    /** @return array{title:string,message:string,date:string,locale:string}|null */
    public function buildPayloadForTomorrow(string $todayYmd, string $locale = 'en'): ?array
    {
        $match = $this->matchForTomorrow($todayYmd);
        if ($match === null) {
            return null;
        }

        $locale = user_notification_locale($locale);
        $row = $match['row'];
        $summary = panchang_day_summary($row);
        if ($summary === '') {
            return null;
        }

        $weekday = trim((string) ($row['weekday'] ?? ''));
        $dateLabel = $this->formatDateLabel($match['date'], $weekday, $locale);
        $title = t_for_locale('calendar.tomorrow_tithi', $locale) . ' · ' . $dateLabel;
        $message = t_for_locale('calendar.tomorrow_tithi_message', $locale, ['summary' => $summary]);

        $festival = panchang_festival_notes_for_display($row['festival_notes'] ?? '');
        if ($festival !== null && $festival !== '') {
            $message .= "\n" . $festival;
        }

        return [
            'title' => $title,
            'message' => $message,
            'date' => $match['date'],
            'locale' => $locale,
        ];
    }

    public function alreadySentForOrganization(int $organizationId, string $tomorrowYmd, string $tithiKey): bool
    {
        if ($organizationId < 1 || $tomorrowYmd === '' || $tithiKey === '') {
            return false;
        }

        return is_file($this->markerPath($organizationId, $tomorrowYmd, $tithiKey));
    }

    /**
     * @return array{ok:bool,skipped?:bool,reason?:string,organization_id?:int,date?:string,tithi?:string,recipients?:int,push_sent?:int}
     */
    public function sendForOrganization(int $organizationId, string $todayYmd, bool $dryRun = false): array
    {
        $todayYmd = $this->normalizeDate($todayYmd);
        if ($todayYmd === null || $organizationId < 1) {
            return ['ok' => false, 'reason' => 'invalid_input'];
        }

        $match = $this->matchForTomorrow($todayYmd);
        if ($match === null) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'no_matching_tithi', 'organization_id' => $organizationId];
        }

        $tomorrow = $match['date'];
        $tithiKey = $match['tithi_key'];

        if ($this->alreadySentForOrganization($organizationId, $tomorrow, $tithiKey)) {
            return [
                'ok' => true,
                'skipped' => true,
                'reason' => 'already_sent',
                'organization_id' => $organizationId,
                'date' => $tomorrow,
                'tithi' => $match['tithi_label'],
            ];
        }

        $recipients = (new Organization())->listAllNotificationRecipientRows($organizationId);
        if ($recipients === []) {
            return [
                'ok' => true,
                'skipped' => true,
                'reason' => 'no_recipients',
                'organization_id' => $organizationId,
                'date' => $tomorrow,
                'tithi' => $match['tithi_label'],
            ];
        }

        if ($dryRun) {
            return [
                'ok' => true,
                'skipped' => true,
                'reason' => 'dry_run',
                'organization_id' => $organizationId,
                'date' => $tomorrow,
                'tithi' => $match['tithi_label'],
                'recipients' => count($recipients),
            ];
        }

        $pushSent = 0;
        $dashboardUrl = base_url() . '/organization/dashboard';
        $webPush = new WebPushService();

        foreach ($recipients as $recipient) {
            $uid = (int) ($recipient['id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            $locale = user_notification_locale($recipient['preferred_locale'] ?? null);
            $payload = $this->buildPayloadForTomorrow($todayYmd, $locale);
            if ($payload === null) {
                $payload = $this->buildPayloadForTomorrow($todayYmd, 'en');
            }
            if ($payload === null) {
                continue;
            }
            try {
                $pushSent += $webPush->sendToUser($uid, $payload['title'], $payload['message'], [
                    'type' => 'scheduled_tithi',
                    'url' => $dashboardUrl,
                    'locale' => $locale,
                    'date' => $tomorrow,
                    'tag' => 'scheduled-tithi-' . $tomorrow . '-' . $tithiKey,
                ]);
            } catch (\Throwable $e) {
                // Push-only; nothing persisted.
            }
        }

        $this->markSent($organizationId, $tomorrow, $tithiKey, count($recipients), $pushSent);

        return [
            'ok' => true,
            'organization_id' => $organizationId,
            'date' => $tomorrow,
            'tithi' => $match['tithi_label'],
            'recipients' => count($recipients),
            'push_sent' => $pushSent,
        ];
    }

    /**
     * @return array{today:string,tomorrow?:string,tithi?:string,organizations:int,sent:int,skipped:int,failed:int,results:list<array<string,mixed>>}
     */
    public function sendForAllOrganizations(?string $todayYmd = null, bool $dryRun = false): array
    {
        $todayYmd = $this->normalizeDate($todayYmd ?? $this->todayInAppTimezone()) ?? $this->todayInAppTimezone();
        $match = $this->matchForTomorrow($todayYmd);
        $orgs = (new Organization())->listAll('name', 'asc');
        $summary = [
            'today' => $todayYmd,
            'tomorrow' => $match['date'] ?? null,
            'tithi' => $match['tithi_label'] ?? null,
            'organizations' => count($orgs),
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
            'results' => [],
        ];

        if ($match === null) {
            return $summary;
        }

        foreach ($orgs as $org) {
            $orgId = (int) ($org['id'] ?? 0);
            if ($orgId < 1) {
                continue;
            }
            $result = $this->sendForOrganization($orgId, $todayYmd, $dryRun);
            $result['organization_name'] = (string) ($org['name'] ?? '');
            $summary['results'][] = $result;
            if (!empty($result['skipped'])) {
                $summary['skipped']++;
            } elseif (!empty($result['ok'])) {
                $summary['sent']++;
            } else {
                $summary['failed']++;
            }
        }

        return $summary;
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

    private function formatDateLabel(string $dateYmd, string $weekdayEn, string $locale): string
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd);
        if ($dt === false) {
            return $dateYmd;
        }

        $locale = user_notification_locale($locale);
        $dayKeys = [
            'calendar.day_sun',
            'calendar.day_mon',
            'calendar.day_tue',
            'calendar.day_wed',
            'calendar.day_thu',
            'calendar.day_fri',
            'calendar.day_sat',
        ];
        $dow = (int) $dt->format('w');
        $weekdayLabel = t_for_locale($dayKeys[$dow] ?? 'calendar.day_sun', $locale);
        if ($locale === 'en' && $weekdayEn !== '') {
            $weekdayLabel = $weekdayEn;
        }

        $monthNum = (int) $dt->format('n');
        $monthLabel = t_for_locale('calendar.month_' . $monthNum, $locale);
        $formatted = $dt->format('j') . ' ' . $monthLabel . ' ' . $dt->format('Y');

        return $weekdayLabel . ', ' . $formatted;
    }

    private function stateDir(): string
    {
        $dir = BASE_PATH . '/storage/cache/tithi_reminders';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private function markerPath(int $organizationId, string $tomorrowYmd, string $tithiKey): string
    {
        $slug = preg_replace('/[^a-z0-9_-]+/', '-', strtolower($tithiKey)) ?? 'tithi';

        return $this->stateDir() . '/' . $tomorrowYmd . '_' . $slug . '_org' . $organizationId . '.sent';
    }

    private function markSent(int $organizationId, string $tomorrowYmd, string $tithiKey, int $recipients, int $pushSent): void
    {
        $path = $this->markerPath($organizationId, $tomorrowYmd, $tithiKey);
        $payload = json_encode([
            'organization_id' => $organizationId,
            'tomorrow' => $tomorrowYmd,
            'tithi' => $tithiKey,
            'recipients' => $recipients,
            'push_sent' => $pushSent,
            'sent_at' => (new \DateTimeImmutable('now', $this->appTimezone()))->format(DATE_ATOM),
        ], JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            $payload = '{}';
        }
        file_put_contents($path, $payload);
    }
}
