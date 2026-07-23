<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\PlatformPanchangDay;

/**
 * Sends platform panchang (today's tithi) by direct web push.
 * No in-app notification, campaign, or log rows are stored.
 * Title uses EN or GU based on each member's preferred_locale.
 * Intended to run from CLI / Task Scheduler once daily (default 07:00 app timezone).
 */
final class DailyTithiNotificationService
{
    /** @return array{title:string,message:string,date:string,locale:string}|null */
    public function buildPayloadForDate(string $dateYmd, string $locale = 'en'): ?array
    {
        $dateYmd = $this->normalizeDate($dateYmd);
        if ($dateYmd === null) {
            return null;
        }

        $locale = user_notification_locale($locale);

        $map = (new PlatformPanchangDay())->mapForDateRange($dateYmd, $dateYmd);
        $row = $map[$dateYmd] ?? null;
        if (!is_array($row)) {
            return null;
        }

        $summary = panchang_day_summary($row);
        if ($summary === '') {
            return null;
        }

        $festival = panchang_festival_notes_for_display($row['festival_notes'] ?? '');
        $weekday = trim((string) ($row['weekday'] ?? ''));
        $dateLabel = $this->formatDateLabel($dateYmd, $weekday, $locale);

        $title = t_for_locale('calendar.today_tithi', $locale) . ' · ' . $dateLabel;
        $message = $summary;
        if ($festival !== null && $festival !== '') {
            $message .= "\n" . $festival;
        }

        return [
            'title' => $title,
            'message' => $message,
            'date' => $dateYmd,
            'locale' => $locale,
        ];
    }

    public function alreadySentForOrganization(int $organizationId, string $dateYmd): bool
    {
        $dateYmd = $this->normalizeDate($dateYmd);
        if ($dateYmd === null || $organizationId < 1) {
            return false;
        }

        return is_file($this->markerPath($organizationId, $dateYmd));
    }

    /**
     * @return array{ok:bool,skipped?:bool,reason?:string,organization_id?:int,date?:string,recipients?:int,push_sent?:int,locale_counts?:array<string,int>,samples?:array<string,array{title:string,message:string}>}
     */
    public function sendForOrganization(int $organizationId, string $dateYmd, bool $dryRun = false): array
    {
        $dateYmd = $this->normalizeDate($dateYmd);
        if ($dateYmd === null || $organizationId < 1) {
            return ['ok' => false, 'reason' => 'invalid_input'];
        }

        if ($this->alreadySentForOrganization($organizationId, $dateYmd)) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'already_sent', 'organization_id' => $organizationId, 'date' => $dateYmd];
        }

        if ($this->buildPayloadForDate($dateYmd, 'en') === null && $this->buildPayloadForDate($dateYmd, 'gu') === null) {
            return ['ok' => false, 'reason' => 'no_panchang', 'organization_id' => $organizationId, 'date' => $dateYmd];
        }

        $members = (new Organization())->listMemberRecipientRows($organizationId, []);
        if ($members === []) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'no_members', 'organization_id' => $organizationId, 'date' => $dateYmd];
        }

        $localeCounts = ['en' => 0, 'gu' => 0];
        foreach ($members as $member) {
            $loc = user_notification_locale($member['preferred_locale'] ?? null);
            $localeCounts[$loc] = ($localeCounts[$loc] ?? 0) + 1;
        }

        $samples = [
            'en' => $this->buildPayloadForDate($dateYmd, 'en'),
            'gu' => $this->buildPayloadForDate($dateYmd, 'gu'),
        ];

        if ($dryRun) {
            return [
                'ok' => true,
                'skipped' => true,
                'reason' => 'dry_run',
                'organization_id' => $organizationId,
                'date' => $dateYmd,
                'recipients' => count($members),
                'locale_counts' => $localeCounts,
                'samples' => array_filter($samples),
            ];
        }

        $pushSent = 0;
        $dashboardUrl = base_url() . '/organization/dashboard';
        $webPush = new WebPushService();
        foreach ($members as $member) {
            $uid = (int) ($member['id'] ?? 0);
            if ($uid < 1) {
                continue;
            }
            $locale = user_notification_locale($member['preferred_locale'] ?? null);
            $payload = $this->buildPayloadForDate($dateYmd, $locale)
                ?? $this->buildPayloadForDate($dateYmd, 'en');
            if ($payload === null) {
                continue;
            }
            try {
                $pushSent += $webPush->sendToUser($uid, $payload['title'], $payload['message'], [
                    'type' => 'daily_tithi',
                    'url' => $dashboardUrl,
                    'locale' => $locale,
                    'date' => $dateYmd,
                    'tag' => 'daily-tithi-' . $dateYmd,
                ]);
            } catch (\Throwable $e) {
                // Push-only; nothing is persisted.
            }
        }

        $this->markSent($organizationId, $dateYmd, count($members), $pushSent);

        return [
            'ok' => true,
            'organization_id' => $organizationId,
            'date' => $dateYmd,
            'recipients' => count($members),
            'push_sent' => $pushSent,
            'locale_counts' => $localeCounts,
        ];
    }

    /**
     * @return array{date:string,organizations:int,sent:int,skipped:int,failed:int,results:list<array<string,mixed>>}
     */
    public function sendForAllOrganizations(?string $dateYmd = null, bool $dryRun = false): array
    {
        $dateYmd = $this->normalizeDate($dateYmd ?? $this->todayInAppTimezone()) ?? $this->todayInAppTimezone();
        $orgs = (new Organization())->listAll('name', 'asc');
        $summary = [
            'date' => $dateYmd,
            'organizations' => count($orgs),
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
            'results' => [],
        ];

        foreach ($orgs as $org) {
            $orgId = (int) ($org['id'] ?? 0);
            if ($orgId < 1) {
                continue;
            }
            $result = $this->sendForOrganization($orgId, $dateYmd, $dryRun);
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

    public function todayInAppTimezone(): string
    {
        return (new \DateTimeImmutable('now', $this->appTimezone()))->format('Y-m-d');
    }

    public function isWithinSendWindow(?\DateTimeImmutable $now = null): bool
    {
        $now = $now ?? new \DateTimeImmutable('now', $this->appTimezone());
        $hour = (int) app_config('daily_tithi_hour', 7);
        $minute = (int) app_config('daily_tithi_minute', 0);
        $windowMinutes = (int) app_config('daily_tithi_window_minutes', 30);

        $target = $now->setTime(max(0, min(23, $hour)), max(0, min(59, $minute)), 0);
        $diff = abs($now->getTimestamp() - $target->getTimestamp());

        return $diff <= max(1, $windowMinutes) * 60;
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
        $dir = BASE_PATH . '/storage/cache/daily_tithi_notifications';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    private function markerPath(int $organizationId, string $dateYmd): string
    {
        return $this->stateDir() . '/' . $dateYmd . '_org' . $organizationId . '.sent';
    }

    private function markSent(int $organizationId, string $dateYmd, int $recipients, int $pushSent): void
    {
        $payload = json_encode([
            'organization_id' => $organizationId,
            'date' => $dateYmd,
            'recipients' => $recipients,
            'push_sent' => $pushSent,
            'sent_at' => (new \DateTimeImmutable('now', $this->appTimezone()))->format(DATE_ATOM),
        ], JSON_UNESCAPED_UNICODE);
        file_put_contents($this->markerPath($organizationId, $dateYmd), $payload !== false ? $payload : '{}');
    }
}
