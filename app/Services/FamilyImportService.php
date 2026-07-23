<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyDependent;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserProfile;

use function build_person_full_name;
use function is_married_flag_from_marital_status;
use function is_valid_profession_type;
use function normalize_blood_group;
use function normalize_gender;
use function normalize_marital_status_import;
use function normalize_phone;
use function normalize_profession_type;
use function occupation_from_profession_type;
use function person_name_parts_from_row;

final class FamilyImportService
{
    /** @return list<string> */
    public static function csvHeaders(bool $includeOrgCode): array
    {
        $headers = [];
        if ($includeOrgCode) {
            $headers[] = 'organization_code';
        }

        return array_merge($headers, [
            'family_ref',
            'person_type',
            'first_name',
            'middle_name',
            'last_name',
            'name',
            'phone',
            'email',
            'dob',
            'gender',
            'marital_status',
            'is_married',
            'blood_group',
            'highest_education',
            'profession_type',
            'job_title',
            'company_name',
            'industry_sector',
            'company_website',
            'house_number',
            'pincode',
            'area',
            'city',
            'state',
            'address_line1',
            'address_line2',
            'native_pincode',
            'native_city',
            'native_state',
            'role_in_family',
            'head_phone',
            'related_to_phone',
        ]);
    }

    public function streamSampleCsv(bool $includeOrgCode): void
    {
        $orgCol = $includeOrgCode ? ['001'] : [];
        $rows = [
            self::csvHeaders($includeOrgCode),
            array_merge($orgCol, [
                'FAM-1001', 'head', 'Arun', 'Kumar', '', 'Arun Kumar', '919900000001', 'arun.kumar@test.com',
                '1984-06-12', 'Male', 'Married', '1', 'B+', 'Graduate', 'business', '', 'Arun Traders', '', 'https://aruntraders.example',
                '12A', '560001', 'MG Road', 'Bengaluru', 'Karnataka', 'MG Road', '', '560001', 'Bengaluru', 'Karnataka',
                'head', '919900000001', '',
            ]),
            array_merge($orgCol, [
                'FAM-1001', 'member', 'Lakshmi', '', 'Arun', 'Lakshmi Arun', '919900000002', 'lakshmi.arun@test.com',
                '1988-04-22', 'Female', 'Married', '1', 'O+', 'Postgraduate', 'job', 'HR Manager', 'PeopleWorks', 'HR', 'https://peopleworks.example',
                '12A', '560001', 'MG Road', 'Bengaluru', 'Karnataka', 'MG Road', '', '560001', 'Bengaluru', 'Karnataka',
                'spouse', '919900000001', '919900000001',
            ]),
            array_merge($orgCol, [
                'FAM-1001', 'dependent', 'Baby', '', 'Arun', 'Baby Arun', '', '', '2020-09-01', '', '', '0', '',
                '', '', '', '', '', '',
                '', '560001', '', 'Bengaluru', 'Karnataka', 'MG Road', '', '560001', 'Bengaluru', 'Karnataka',
                'daughter', '919900000001', '919900000001',
            ]),
        ];
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="family_import_sample.csv"');
        $out = fopen('php://output', 'wb');
        if ($out === false) {
            echo 'Could not create output.';

            return;
        }
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
    }

    /**
     * @return array{preview: ?array<string,mixed>, errors: list<string>, warnings: list<string>}
     */
    public function previewFromPath(string $path, ?int $fixedOrganizationId = null): array
    {
        $fp = fopen($path, 'rb');
        if ($fp === false) {
            return [
                'preview' => null,
                'errors' => [t('import.families.error_read')],
                'warnings' => [],
            ];
        }

        $header = fgetcsv($fp);
        if (!is_array($header) || $header === []) {
            fclose($fp);

            return [
                'preview' => null,
                'errors' => [t('import.families.error_empty')],
                'warnings' => [],
            ];
        }
        $header = array_map(static fn ($h): string => strtolower(trim((string) $h)), $header);
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]) ?? $header[0];
        }

        $includeOrgCode = $fixedOrganizationId === null;
        $required = ['family_ref', 'person_type'];
        if ($includeOrgCode) {
            $required[] = 'organization_code';
        }
        $missing = [];
        foreach ($required as $req) {
            if (!in_array($req, $header, true)) {
                $missing[] = $req;
            }
        }
        if ($missing !== []) {
            fclose($fp);

            return [
                'preview' => null,
                'errors' => [t('import.families.error_missing_columns', ['columns' => implode(', ', $missing)])],
                'warnings' => [],
            ];
        }

        $orgMap = [];
        $fixedOrgCode = null;
        if ($fixedOrganizationId !== null && $fixedOrganizationId > 0) {
            $org = (new Organization())->findById($fixedOrganizationId);
            if ($org === null) {
                fclose($fp);

                return [
                    'preview' => null,
                    'errors' => [t('import.families.error_org_not_found')],
                    'warnings' => [],
                ];
            }
            $fixedOrgCode = strtoupper(trim((string) ($org['org_code'] ?? '')));
        } else {
            $normalizeOrgCode = static function (string $raw): string {
                $v = strtoupper(trim($raw));
                if ($v === '') {
                    return '';
                }
                if (ctype_digit($v)) {
                    return ltrim($v, '0') === '' ? '0' : ltrim($v, '0');
                }

                return $v;
            };
            foreach ((new Organization())->listAll() as $org) {
                $code = strtoupper(trim((string) ($org['org_code'] ?? '')));
                if ($code !== '') {
                    $orgMap[$code] = (int) ($org['id'] ?? 0);
                    $orgMap[$normalizeOrgCode($code)] = (int) ($org['id'] ?? 0);
                }
            }
        }

        $rows = [];
        $errors = [];
        $warnings = [];
        $lineNo = 1;
        while (($csvRow = fgetcsv($fp)) !== false) {
            $lineNo++;
            if ($csvRow === [null] || $csvRow === false) {
                continue;
            }
            $row = [];
            foreach ($header as $idx => $key) {
                $row[$key] = trim((string) ($csvRow[$idx] ?? ''));
            }
            if ($row === []) {
                continue;
            }

            $personType = strtolower((string) ($row['person_type'] ?? ''));
            $nameParts = person_name_parts_from_row($row);
            $displayName = build_person_full_name(
                $nameParts['first_name'],
                $nameParts['middle_name'],
                $nameParts['last_name']
            );
            if ($displayName === '') {
                $displayName = trim((string) ($row['name'] ?? ''));
            }

            $organizationId = $fixedOrganizationId ?? 0;
            $orgCodeRaw = $fixedOrgCode ?? '';
            if ($includeOrgCode) {
                $orgCodeRaw = strtoupper((string) ($row['organization_code'] ?? ''));
                $orgCode = $this->normalizeOrgCode($orgCodeRaw);
                if ($orgCode === '' || !isset($orgMap[$orgCode])) {
                    $errors[] = t('import.families.error_line_org', ['line' => (string) $lineNo]);
                    continue;
                }
                $organizationId = $orgMap[$orgCode];
            }

            $familyRef = (string) ($row['family_ref'] ?? '');
            if ($familyRef === '') {
                $errors[] = t('import.families.error_line_family_ref', ['line' => (string) $lineNo]);
                continue;
            }
            if (!in_array($personType, ['head', 'member', 'dependent'], true)) {
                $errors[] = t('import.families.error_line_person_type', ['line' => (string) $lineNo]);
                continue;
            }
            if ($displayName === '') {
                $errors[] = t('import.families.error_line_name', ['line' => (string) $lineNo]);
                continue;
            }

            $phone = normalize_phone((string) ($row['phone'] ?? ''));
            if (in_array($personType, ['head', 'member'], true) && ($phone === null || strlen($phone) < 10)) {
                $errors[] = t('import.families.error_line_phone', ['line' => (string) $lineNo]);
                continue;
            }

            $emailRaw = trim((string) ($row['email'] ?? ''));
            if ($emailRaw !== '' && filter_var($emailRaw, FILTER_VALIDATE_EMAIL) === false) {
                $errors[] = t('import.families.error_line_email', ['line' => (string) $lineNo]);
                continue;
            }

            $parsed = [
                'line' => $lineNo,
                'organization_code' => $orgCodeRaw,
                'organization_id' => $organizationId,
                'family_ref' => $familyRef,
                'person_type' => $personType,
                'first_name' => $nameParts['first_name'],
                'middle_name' => $nameParts['middle_name'],
                'last_name' => $nameParts['last_name'],
                'name' => $displayName,
                'phone' => $phone,
                'email' => $emailRaw,
                'dob' => trim((string) ($row['dob'] ?? '')),
                'gender' => trim((string) ($row['gender'] ?? '')),
                'marital_status' => trim((string) ($row['marital_status'] ?? '')),
                'is_married' => trim((string) ($row['is_married'] ?? '')),
                'blood_group' => trim((string) ($row['blood_group'] ?? '')),
                'highest_education' => trim((string) ($row['highest_education'] ?? '')),
                'profession_type' => trim((string) ($row['profession_type'] ?? '')),
                'job_title' => trim((string) ($row['job_title'] ?? '')),
                'company_name' => trim((string) ($row['company_name'] ?? '')),
                'industry_sector' => trim((string) ($row['industry_sector'] ?? '')),
                'company_website' => trim((string) ($row['company_website'] ?? '')),
                'house_number' => trim((string) ($row['house_number'] ?? '')),
                'pincode' => trim((string) ($row['pincode'] ?? '')),
                'area' => trim((string) ($row['area'] ?? '')),
                'city' => trim((string) ($row['city'] ?? '')),
                'state' => trim((string) ($row['state'] ?? '')),
                'address_line1' => trim((string) ($row['address_line1'] ?? '')),
                'address_line2' => trim((string) ($row['address_line2'] ?? '')),
                'native_pincode' => trim((string) ($row['native_pincode'] ?? '')),
                'native_city' => trim((string) ($row['native_city'] ?? '')),
                'native_state' => trim((string) ($row['native_state'] ?? '')),
                'role_in_family' => trim((string) ($row['role_in_family'] ?? '')),
                'head_phone' => normalize_phone((string) ($row['head_phone'] ?? '')),
                'related_to_phone' => normalize_phone((string) ($row['related_to_phone'] ?? '')),
            ];

            $rowWarnings = $this->profileWarningsForRow($parsed);
            if ($rowWarnings !== []) {
                $warnings[] = t('import.families.warning_line_profile', [
                    'line' => (string) $lineNo,
                    'fields' => implode(', ', $rowWarnings),
                ]);
            }

            $rows[] = $parsed;
        }
        fclose($fp);

        $groupStats = [];
        foreach ($rows as $row) {
            $gk = $row['organization_code'] . '|' . $row['family_ref'];
            if (!isset($groupStats[$gk])) {
                $groupStats[$gk] = [
                    'organization_code' => $row['organization_code'],
                    'family_ref' => $row['family_ref'],
                    'total' => 0,
                    'heads' => 0,
                ];
            }
            $groupStats[$gk]['total']++;
            if ($row['person_type'] === 'head') {
                $groupStats[$gk]['heads']++;
            }
        }
        $validGroups = 0;
        $invalidGroups = 0;
        foreach ($groupStats as $g) {
            if ((int) $g['heads'] !== 1) {
                $invalidGroups++;
                $errors[] = t('import.families.error_group_heads', [
                    'org' => (string) $g['organization_code'],
                    'family' => (string) $g['family_ref'],
                    'count' => (string) $g['heads'],
                ]);
            } else {
                $validGroups++;
            }
        }

        $validRows = [];
        foreach ($rows as $row) {
            $gk = $row['organization_code'] . '|' . $row['family_ref'];
            if ((int) ($groupStats[$gk]['heads'] ?? 0) === 1) {
                $validRows[] = $row;
            }
        }

        return [
            'preview' => [
                'total_rows' => count($rows),
                'group_count' => count($groupStats),
                'valid_groups' => $validGroups,
                'invalid_groups' => $invalidGroups,
                'warning_count' => count($warnings),
                'groups' => array_values($groupStats),
                'valid_rows_json' => base64_encode((string) json_encode($validRows)),
            ],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @return array{summary: string, familiesCreated: int, membersAdded: int, dependentsCreated: int, usersCreated: int, usersReused: int, skippedGroups: int, profilesSaved: int, profilesSkipped: int}
     */
    public function apply(array $rows, ?int $actorUserId): array
    {
        $byGroup = [];
        foreach ($rows as $row) {
            $orgCode = (string) ($row['organization_code'] ?? '');
            $familyRef = (string) ($row['family_ref'] ?? '');
            if ($orgCode === '' || $familyRef === '') {
                continue;
            }
            $gk = $orgCode . '|' . $familyRef;
            if (!isset($byGroup[$gk])) {
                $byGroup[$gk] = [];
            }
            $byGroup[$gk][] = $row;
        }

        $orgs = new Organization();
        $users = new User();
        $profiles = new UserProfile();
        $families = new Family();
        $dependents = new FamilyDependent();

        $familiesCreated = 0;
        $usersCreated = 0;
        $usersReused = 0;
        $dependentsCreated = 0;
        $membersAdded = 0;
        $skippedGroups = 0;
        $profilesSaved = 0;
        $profilesSkipped = 0;
        $notes = [];

        foreach ($byGroup as $gk => $groupRows) {
            $heads = array_values(array_filter($groupRows, static fn (array $r): bool => strtolower((string) ($r['person_type'] ?? '')) === 'head'));
            if (count($heads) !== 1) {
                $skippedGroups++;
                $notes[] = $gk . ': skipped (head count != 1).';
                continue;
            }
            $headRow = $heads[0];
            $organizationId = (int) ($headRow['organization_id'] ?? 0);
            if ($organizationId < 1 || $orgs->findById($organizationId) === null) {
                $skippedGroups++;
                $notes[] = $gk . ': skipped (organization not found).';
                continue;
            }

            [$headUserId, $headCreated] = $this->resolveOrCreateImportUser($users, $headRow, $organizationId);
            if ($headUserId < 1) {
                $skippedGroups++;
                $notes[] = $gk . ': skipped (head user could not be resolved).';
                continue;
            }
            if ($headCreated) {
                $usersCreated++;
            } else {
                $usersReused++;
            }
            $orgs->addUser($organizationId, $headUserId, 'member');

            $existingFamilyId = $families->findIdByOrganizationAndHead($organizationId, $headUserId);
            if ($existingFamilyId !== null) {
                $familyId = $existingFamilyId;
            } else {
                $familyId = $families->create($organizationId, $headUserId, $actorUserId);
                $familiesCreated++;
            }
            $families->upsertMember($familyId, $headUserId, 'head', null);
            if ($this->upsertProfileFromImportRow($profiles, $headUserId, $headRow)) {
                $profilesSaved++;
            } else {
                $profilesSkipped++;
            }

            $phoneToUser = [];
            $headPhone = $this->canonicalImportPhone(isset($headRow['phone']) ? (string) $headRow['phone'] : null);
            if ($headPhone !== null && $headPhone !== '') {
                $phoneToUser[$headPhone] = $headUserId;
            }

            foreach ($groupRows as $row) {
                $personType = strtolower((string) ($row['person_type'] ?? ''));
                if ($personType === 'head') {
                    continue;
                }
                if ($personType === 'member') {
                    [$memberUserId, $memberCreated] = $this->resolveOrCreateImportUser($users, $row, $organizationId);
                    if ($memberUserId < 1) {
                        $notes[] = $gk . ': line ' . (int) ($row['line'] ?? 0) . ' skipped (member user unresolved).';
                        continue;
                    }
                    if ($memberCreated) {
                        $usersCreated++;
                    } else {
                        $usersReused++;
                    }
                    $orgs->addUser($organizationId, $memberUserId, 'member');
                    $relatedTo = $this->resolveRelatedUserId($row, $phoneToUser, $headUserId);
                    $role = strtolower(trim((string) ($row['role_in_family'] ?? '')));
                    if ($role === '' || $role === 'head') {
                        $role = 'other';
                    }
                    $families->upsertMember($familyId, $memberUserId, $role, $relatedTo);
                    if ($this->upsertProfileFromImportRow($profiles, $memberUserId, $row)) {
                        $profilesSaved++;
                    } else {
                        $profilesSkipped++;
                    }
                    $membersAdded++;
                    $memberPhone = $this->canonicalImportPhone(isset($row['phone']) ? (string) $row['phone'] : null);
                    if ($memberPhone !== null && $memberPhone !== '') {
                        $phoneToUser[$memberPhone] = $memberUserId;
                    }
                    continue;
                }
                if ($personType === 'dependent') {
                    $name = trim((string) ($row['name'] ?? ''));
                    $dob = trim((string) ($row['dob'] ?? ''));
                    $pincode = trim((string) ($row['pincode'] ?? ''));
                    $city = trim((string) ($row['city'] ?? ''));
                    $state = trim((string) ($row['state'] ?? ''));
                    if ($name === '' || $dob === '' || $pincode === '' || $city === '' || $state === '') {
                        $notes[] = $gk . ': line ' . (int) ($row['line'] ?? 0) . ' skipped (dependent missing required fields).';
                        continue;
                    }
                    $role = strtolower(trim((string) ($row['role_in_family'] ?? '')));
                    if ($role === '') {
                        $role = 'other';
                    }
                    $relatedTo = $this->resolveRelatedUserId($row, $phoneToUser, $headUserId);
                    $dependents->create($familyId, $name, $role, $relatedTo, $dob, $pincode, $city, $state);
                    $dependentsCreated++;
                }
            }
        }

        $summary = t('import.families.applied_summary', [
            'families' => (string) $familiesCreated,
            'members' => (string) $membersAdded,
            'dependents' => (string) $dependentsCreated,
            'users_created' => (string) $usersCreated,
            'users_reused' => (string) $usersReused,
            'profiles_saved' => (string) $profilesSaved,
            'profiles_skipped' => (string) $profilesSkipped,
            'skipped_groups' => (string) $skippedGroups,
        ]);
        if ($notes !== []) {
            $summary .= ' ' . t('import.families.applied_notes', ['notes' => implode(' | ', array_slice($notes, 0, 6))]);
        }

        return [
            'summary' => $summary,
            'familiesCreated' => $familiesCreated,
            'membersAdded' => $membersAdded,
            'dependentsCreated' => $dependentsCreated,
            'usersCreated' => $usersCreated,
            'usersReused' => $usersReused,
            'skippedGroups' => $skippedGroups,
            'profilesSaved' => $profilesSaved,
            'profilesSkipped' => $profilesSkipped,
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return list<string>
     */
    public function profileWarningsForRow(array $row): array
    {
        $personType = strtolower((string) ($row['person_type'] ?? ''));
        if ($personType === 'dependent') {
            $missing = [];
            foreach (['dob', 'pincode', 'city', 'state'] as $field) {
                if (trim((string) ($row[$field] ?? '')) === '') {
                    $missing[] = $field;
                }
            }

            return $missing;
        }
        if (!in_array($personType, ['head', 'member'], true)) {
            return [];
        }

        $missing = [];
        if (trim((string) ($row['dob'] ?? '')) === '' || strtotime((string) $row['dob']) === false) {
            $missing[] = 'dob';
        }
        if (!preg_match('/^\d{6}$/', trim((string) ($row['pincode'] ?? '')))) {
            $missing[] = 'pincode';
        }
        if (trim((string) ($row['city'] ?? '')) === '') {
            $missing[] = 'city';
        }
        if (trim((string) ($row['state'] ?? '')) === '') {
            $missing[] = 'state';
        }
        if (trim((string) ($row['address_line1'] ?? '')) === '' && trim((string) ($row['house_number'] ?? '')) === '') {
            $missing[] = 'address_line1';
        }
        if (trim((string) ($row['area'] ?? '')) === '') {
            $missing[] = 'area';
        }
        if (normalize_gender((string) ($row['gender'] ?? '')) === null) {
            $missing[] = 'gender';
        }
        if (normalize_marital_status_import(
            isset($row['marital_status']) ? (string) $row['marital_status'] : null,
            isset($row['is_married']) ? (string) $row['is_married'] : null
        ) === null) {
            $missing[] = 'marital_status';
        }
        if (normalize_blood_group((string) ($row['blood_group'] ?? '')) === null) {
            $missing[] = 'blood_group';
        }
        if (trim((string) ($row['highest_education'] ?? '')) === '') {
            $missing[] = 'highest_education';
        }
        if (!is_valid_profession_type((string) ($row['profession_type'] ?? ''))) {
            $missing[] = 'profession_type';
        }

        $professionType = normalize_profession_type((string) ($row['profession_type'] ?? ''));
        if ($professionType === 'job') {
            if (trim((string) ($row['job_title'] ?? '')) === '') {
                $missing[] = 'job_title';
            }
            if (trim((string) ($row['company_name'] ?? '')) === '') {
                $missing[] = 'company_name';
            }
            if (trim((string) ($row['industry_sector'] ?? '')) === '') {
                $missing[] = 'industry_sector';
            }
        } elseif ($professionType === 'business') {
            if (trim((string) ($row['company_name'] ?? '')) === '') {
                $missing[] = 'company_name';
            }
        } elseif ($professionType === 'professional') {
            if (trim((string) ($row['job_title'] ?? '')) === '') {
                $missing[] = 'job_title';
            }
        }

        $nativePincode = trim((string) ($row['native_pincode'] ?? ''));
        $nativeCity = trim((string) ($row['native_city'] ?? ''));
        $nativeState = trim((string) ($row['native_state'] ?? ''));
        if ($nativePincode === '' && trim((string) ($row['pincode'] ?? '')) === '') {
            $missing[] = 'native_pincode';
        } elseif ($nativePincode !== '' && !preg_match('/^\d{6}$/', $nativePincode)) {
            $missing[] = 'native_pincode';
        }
        if ($nativeCity === '' && trim((string) ($row['city'] ?? '')) === '') {
            $missing[] = 'native_city';
        }
        if ($nativeState === '' && trim((string) ($row['state'] ?? '')) === '') {
            $missing[] = 'native_state';
        }

        return $missing;
    }

    /** @return array{0:int,1:bool} */
    private function resolveOrCreateImportUser(User $users, array $row, int $organizationId): array
    {
        $phone = normalize_phone((string) ($row['phone'] ?? ''));
        $emailRaw = trim((string) ($row['email'] ?? ''));
        $email = $emailRaw !== '' && filter_var($emailRaw, FILTER_VALIDATE_EMAIL) ? $emailRaw : null;
        $nameParts = person_name_parts_from_row($row);
        $fullName = build_person_full_name(
            $nameParts['first_name'],
            $nameParts['middle_name'],
            $nameParts['last_name']
        );
        if ($fullName === '') {
            $fullName = trim((string) ($row['name'] ?? ''));
        }
        if ($phone !== null) {
            $existing = $users->findByIdentity($phone);
            if ($existing !== null) {
                return [(int) $existing['id'], false];
            }
            if (strlen($phone) === 10) {
                $existing = $users->findByIdentity('91' . $phone);
                if ($existing !== null) {
                    return [(int) $existing['id'], false];
                }
            }
            if (strlen($phone) === 12 && strpos($phone, '91') === 0) {
                $existing = $users->findByIdentity(substr($phone, 2));
                if ($existing !== null) {
                    return [(int) $existing['id'], false];
                }
            }
        }
        if ($email !== null) {
            $existing = $users->findByEmail($email);
            if ($existing !== null) {
                return [(int) $existing['id'], false];
            }
        }
        if ($fullName === '' || $phone === null || $organizationId < 1) {
            return [0, false];
        }
        $normalizedPhone = strlen($phone) === 10 ? ('91' . $phone) : $phone;
        $newId = $users->create([
            'name' => $fullName,
            'first_name' => $nameParts['first_name'],
            'middle_name' => $nameParts['middle_name'],
            'last_name' => $nameParts['last_name'],
            'email' => $email,
            'phone' => $normalizedPhone,
            'password' => '12345678',
            'role' => 'member',
            'organization_id' => $organizationId,
        ]);

        return [$newId, true];
    }

    private function canonicalImportPhone(?string $phone): ?string
    {
        $phone = normalize_phone($phone);
        if ($phone === null || $phone === '') {
            return null;
        }
        if (strlen($phone) === 10) {
            return '91' . $phone;
        }

        return $phone;
    }

    private function resolveRelatedUserId(array $row, array $phoneToUser, int $headUserId): ?int
    {
        $relatedPhone = $this->canonicalImportPhone((string) ($row['related_to_phone'] ?? ''));
        if ($relatedPhone !== null && isset($phoneToUser[$relatedPhone])) {
            return (int) $phoneToUser[$relatedPhone];
        }
        $headPhone = $this->canonicalImportPhone((string) ($row['head_phone'] ?? ''));
        if ($headPhone !== null && isset($phoneToUser[$headPhone])) {
            return (int) $phoneToUser[$headPhone];
        }

        return $headUserId > 0 ? $headUserId : null;
    }

    /** @return bool True when profile row was saved */
    private function upsertProfileFromImportRow(UserProfile $profiles, int $userId, array $row): bool
    {
        if ($this->profileWarningsForRow($row) !== []) {
            return false;
        }

        $dob = trim((string) ($row['dob'] ?? ''));
        $pincode = trim((string) ($row['pincode'] ?? ''));
        $city = trim((string) ($row['city'] ?? ''));
        $state = trim((string) ($row['state'] ?? ''));
        $houseNumber = trim((string) ($row['house_number'] ?? ''));
        $address1 = trim((string) ($row['address_line1'] ?? ''));
        $gender = normalize_gender((string) ($row['gender'] ?? ''));
        $maritalStatus = normalize_marital_status_import(
            isset($row['marital_status']) ? (string) $row['marital_status'] : null,
            isset($row['is_married']) ? (string) $row['is_married'] : null
        );
        $area = trim((string) ($row['area'] ?? ''));
        $professionType = normalize_profession_type((string) ($row['profession_type'] ?? ''));
        if (
            $gender === null || $maritalStatus === null || $professionType === null
        ) {
            return false;
        }
        $occupation = occupation_from_profession_type($professionType);
        $nativePincode = trim((string) ($row['native_pincode'] ?? '')) !== '' ? trim((string) $row['native_pincode']) : $pincode;
        $nativeCity = trim((string) ($row['native_city'] ?? '')) !== '' ? trim((string) $row['native_city']) : $city;
        $nativeState = trim((string) ($row['native_state'] ?? '')) !== '' ? trim((string) $row['native_state']) : $state;

        $profiles->upsert($userId, [
            'dob' => $dob,
            'gender' => $gender,
            'marital_status' => $maritalStatus,
            'house_number' => $houseNumber !== '' ? $houseNumber : null,
            'address_line1' => $address1 !== '' ? $address1 : ($houseNumber !== '' ? $houseNumber : ''),
            'address_line2' => (string) ($row['address_line2'] ?? '') !== '' ? (string) $row['address_line2'] : null,
            'city' => $city,
            'state' => $state,
            'pincode' => $pincode,
            'area' => $area,
            'occupation' => $occupation,
            'blood_group' => normalize_blood_group((string) ($row['blood_group'] ?? '')),
            'highest_education' => (string) ($row['highest_education'] ?? ''),
            'profession_type' => $professionType,
            'job_title' => (string) ($row['job_title'] ?? '') !== '' ? (string) $row['job_title'] : null,
            'company_name' => (string) ($row['company_name'] ?? '') !== '' ? (string) $row['company_name'] : null,
            'industry_sector' => (string) ($row['industry_sector'] ?? '') !== '' ? (string) $row['industry_sector'] : null,
            'company_website' => (string) ($row['company_website'] ?? '') !== '' ? (string) $row['company_website'] : null,
            'is_married' => is_married_flag_from_marital_status($maritalStatus),
            'native_pincode' => $nativePincode,
            'native_city' => $nativeCity,
            'native_state' => $nativeState,
        ]);

        return true;
    }

    private function normalizeOrgCode(string $raw): string
    {
        $v = strtoupper(trim($raw));
        if ($v === '') {
            return '';
        }
        if (ctype_digit($v)) {
            return ltrim($v, '0') === '' ? '0' : ltrim($v, '0');
        }

        return $v;
    }
}
