<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class MembershipCodeService
{
    private const SERIAL_START = 101;

    /** Min/max Latin letters in org member_initials (e.g. AJ). */
    private const INITIALS_MIN = 2;
    private const INITIALS_MAX = 4;

    public function generateOrganizationCode(string $organizationName): string
    {
        $prefix = $this->prefixFromOrganizationName($organizationName);
        for ($attempt = 0; $attempt < 60; $attempt++) {
            $code = $prefix . (string) random_int(1000, 9999);
            if (!$this->organizationCodeExists($code)) {
                return $code;
            }
        }

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $code = $prefix . (string) random_int(10000, 99999);
            if (!$this->organizationCodeExists($code)) {
                return $code;
            }
        }

        throw new \RuntimeException('Could not generate a unique organization code.');
    }

    /**
     * Next member code for an org: {INITIALS}{serial} e.g. AJ101, AJ102.
     * Full display code is org_code-member_code (e.g. C12-AJ101).
     */
    public function generateMemberCode(int $organizationId): string
    {
        $initials = $this->ensureOrganizationMemberInitials($organizationId);
        $nextSerial = $this->nextSerialForInitials($organizationId, $initials);

        for ($guard = 0; $guard < 500; $guard++) {
            $code = $initials . (string) $nextSerial;
            if (!$this->memberCodeExistsInOrganization($organizationId, $code)) {
                return $code;
            }
            $nextSerial++;
        }

        throw new \RuntimeException('Could not generate a unique member code.');
    }

    /**
     * Ensure the organization has Latin member_initials; persist if newly derived.
     */
    public function ensureOrganizationMemberInitials(int $organizationId): string
    {
        $stmt = Database::pdo()->prepare(
            'SELECT name, nickname, member_initials FROM organizations WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$organizationId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            throw new \RuntimeException('Organization not found for member code.');
        }

        $existing = $this->normalizeMemberInitials((string) ($row['member_initials'] ?? ''));
        if ($existing !== null) {
            return $existing;
        }

        $suggested = $this->suggestMemberInitials(
            (string) ($row['name'] ?? ''),
            (string) ($row['nickname'] ?? '')
        );
        if ($suggested === null || $this->memberInitialsTaken($suggested, $organizationId)) {
            $suggested = $this->allocateUniqueMemberInitials($organizationId);
        }

        $upd = Database::pdo()->prepare('UPDATE organizations SET member_initials = ? WHERE id = ?');
        $upd->execute([$suggested, $organizationId]);

        return $suggested;
    }

    public function prefixFromOrganizationName(string $name): string
    {
        $letters = preg_replace('/[^A-Za-z]/', '', trim($name)) ?? '';
        $letters = strtoupper($letters);
        if (strlen($letters) >= 3) {
            return substr($letters, 0, 3);
        }

        $initials = '';
        foreach (preg_split('/\s+/u', trim($name)) ?: [] as $part) {
            if ($part === '') {
                continue;
            }
            $ch = mb_substr($part, 0, 1);
            if (preg_match('/[A-Za-z]/', $ch) === 1) {
                $initials .= mb_strtoupper($ch);
            }
        }
        $initials = preg_replace('/[^A-Z]/', '', $initials) ?? '';
        if (strlen($initials) >= 3) {
            return substr($initials, 0, 3);
        }

        $seed = strtoupper(preg_replace('/[^A-Z]/', '', $letters . $initials) ?? '');
        if ($seed === '') {
            $seed = 'ORG';
        }

        return str_pad(substr($seed, 0, 3), 3, 'X');
    }

    /** @return string|null Uppercase 2–4 letters, or null if empty/invalid */
    public function normalizeMemberInitials(?string $value): ?string
    {
        $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', trim((string) $value)) ?? '');
        $len = strlen($letters);
        if ($len < self::INITIALS_MIN || $len > self::INITIALS_MAX) {
            return null;
        }

        return $letters;
    }

    /**
     * Suggest initials from Latin letters in name/nickname (word initials preferred).
     */
    public function suggestMemberInitials(string $name, string $nickname = ''): ?string
    {
        foreach ([$nickname, $name] as $source) {
            $source = trim($source);
            if ($source === '') {
                continue;
            }
            $wordInitials = '';
            foreach (preg_split('/\s+/u', $source) ?: [] as $part) {
                if ($part === '') {
                    continue;
                }
                $ch = mb_substr($part, 0, 1);
                if (preg_match('/[A-Za-z]/', $ch) === 1) {
                    $wordInitials .= mb_strtoupper($ch);
                }
            }
            $wordInitials = preg_replace('/[^A-Z]/', '', $wordInitials) ?? '';
            if (strlen($wordInitials) >= self::INITIALS_MIN) {
                return substr($wordInitials, 0, self::INITIALS_MAX);
            }

            $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', $source) ?? '');
            if (strlen($letters) >= self::INITIALS_MIN) {
                return substr($letters, 0, self::INITIALS_MAX);
            }
        }

        return null;
    }

    public function memberInitialsTaken(string $initials, ?int $excludeOrganizationId = null): bool
    {
        $initials = $this->normalizeMemberInitials($initials);
        if ($initials === null) {
            return false;
        }
        if ($excludeOrganizationId !== null && $excludeOrganizationId > 0) {
            $stmt = Database::pdo()->prepare(
                'SELECT 1 FROM organizations WHERE member_initials = ? AND id <> ? LIMIT 1'
            );
            $stmt->execute([$initials, $excludeOrganizationId]);
        } else {
            $stmt = Database::pdo()->prepare(
                'SELECT 1 FROM organizations WHERE member_initials = ? LIMIT 1'
            );
            $stmt->execute([$initials]);
        }

        return $stmt->fetchColumn() !== false;
    }

    private function allocateUniqueMemberInitials(int $organizationId): string
    {
        // AA … ZZ then AAA …
        for ($len = self::INITIALS_MIN; $len <= self::INITIALS_MAX; $len++) {
            $max = (int) (26 ** $len);
            for ($n = 0; $n < $max; $n++) {
                $code = $this->indexToLetters($n, $len);
                if (!$this->memberInitialsTaken($code, $organizationId)) {
                    return $code;
                }
            }
        }

        throw new \RuntimeException('Could not allocate unique member initials.');
    }

    private function indexToLetters(int $index, int $length): string
    {
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out = chr(65 + ($index % 26)) . $out;
            $index = intdiv($index, 26);
        }

        return $out;
    }

    private function nextSerialForInitials(int $organizationId, string $initials): int
    {
        $prefixLen = strlen($initials);
        $stmt = Database::pdo()->prepare(
            'SELECT member_code FROM users
             WHERE organization_id = ?
             AND member_code IS NOT NULL
             AND member_code LIKE ?'
        );
        $stmt->execute([$organizationId, $initials . '%']);
        $max = self::SERIAL_START - 1;
        while (($code = $stmt->fetchColumn()) !== false) {
            $code = strtoupper((string) $code);
            if (strlen($code) <= $prefixLen) {
                continue;
            }
            if (substr($code, 0, $prefixLen) !== $initials) {
                continue;
            }
            $rest = substr($code, $prefixLen);
            if ($rest === '' || !ctype_digit($rest)) {
                continue;
            }
            $n = (int) $rest;
            if ($n > $max) {
                $max = $n;
            }
        }

        return max(self::SERIAL_START, $max + 1);
    }

    private function organizationCodeExists(string $code): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM organizations WHERE org_code = ? LIMIT 1');
        $stmt->execute([strtoupper($code)]);

        return $stmt->fetchColumn() !== false;
    }

    private function memberCodeExistsInOrganization(int $organizationId, string $code): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM users WHERE organization_id = ? AND member_code = ? LIMIT 1'
        );
        $stmt->execute([$organizationId, strtoupper($code)]);

        return $stmt->fetchColumn() !== false;
    }
}
