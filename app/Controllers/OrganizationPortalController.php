<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\OrganizationPortalTrait;
use App\Core\Database;
use App\Core\Request;
use App\Models\EmailVerificationToken;
use App\Models\Donation;
use App\Models\DonationCategory;
use App\Models\Due;
use App\Models\EventPass;
use App\Models\Family;
use App\Models\FamilyDependent;
use App\Models\FamilyHistory;
use App\Models\FamilyMembershipRequest;
use App\Models\FamilyRelationshipLink;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\OrgCalendarDay;
use App\Models\OrgCommitteeMember;
use App\Models\OrgNotice;
use App\Models\OrgPresence;
use App\Models\PlatformHoliday;
use App\Models\PlatformPanchangDay;
use App\Models\Receipt;
use App\Models\Scheme;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Access;
use App\Services\CalendarDayNotificationService;
use App\Services\FamilyImportService;
use App\Services\UserProvisionService;
use Dompdf\Dompdf;
use Dompdf\Options;

use function base_url;
use function current_organization_id;
use function organization_id;
use function current_user;
use function flash_set;
use function is_married_flag_from_marital_status;
use function normalize_blood_group;
use function is_valid_email;
use function normalize_email;
use function normalize_gender;
use function normalize_member_age_range_filters;
use function normalize_member_profession_filter;
use function normalize_marital_status;
use function json_response;
use function normalize_org_calendar_day_category;
use function normalize_phone;
use function normalize_stored_password_hash;
use function redirect;
use function set_current_user;

final class OrganizationPortalController extends Controller
{
    use OrganizationPortalTrait;

    public function dashboard(Request $request): void
    {
        [$memberships, $current, $orgId] = $this->dashboardOrgContext();
        $this->enforceMemberProfileCompletion($orgId, $current);
        $user = current_user();
        $access = new Access();
        $canManageOrg = $orgId > 0 && $access->canManageOrganization($user, $orgId);
        $uid = (int) $user['id'];
        $memberCount = $orgId > 0 ? (new Organization())->countMembers($orgId) : 0;
        $familyCount = $orgId > 0 ? (new Organization())->countFamilies($orgId) : 0;
        $schemeCount = $orgId > 0 ? (new Organization())->countSchemes($orgId) : 0;
        $eventCount = $orgId > 0 ? (new Due())->countEventsForOrganization($orgId) : 0;
        $month = $this->calendarMonthParam();
        $calendarItems = $orgId > 0
            ? $this->buildCalendarItems($orgId, $uid, $month, $canManageOrg)
            : [];
        $calendarTodayItems = $orgId > 0
            ? $this->buildTodayCalendarItems($orgId, $uid, $canManageOrg)
            : [];
        $calendarPanchang = $this->buildPanchangMapForMonth($month);
        $calendarTodayPanchang = $this->buildPanchangForDate(date('Y-m-d'));
        $presenceModel = new OrgPresence();
        $presenceCurrent = $orgId > 0 ? $presenceModel->getCurrent($orgId) : null;
        $presenceHistory = $orgId > 0 ? $presenceModel->listHistory($orgId) : [];
        $dashNotices = [];
        if ($orgId > 0) {
            try {
                $dashNotices = (new OrgNotice())->listForOrganization($orgId, 20, true);
            } catch (\Throwable $e) {
                $dashNotices = [];
            }
        }
        $committeeMembers = [];
        if ($orgId > 0) {
            try {
                $committeeMembers = (new OrgCommitteeMember())->listForOrganization($orgId);
            } catch (\Throwable $e) {
                $committeeMembers = [];
            }
        }
        $orgContact = $canManageOrg && $orgId > 0
            ? (new Organization())->officialContactForDisplay($orgId)
            : null;
        $this->render('organization', 'dashboard.php', [
            'pageTitle' => page_title((string) ($current['name'] ?? 'Upashray/Sangh')),
            'navActive' => 'dashboard',
        ], [
            'memberships' => $memberships,
            'current' => $current,
            'canManageOrg' => $canManageOrg,
            'memberCount' => $memberCount,
            'familyCount' => $familyCount,
            'schemeCount' => $schemeCount,
            'eventCount' => $eventCount,
            'calendarMonth' => $month,
            'calendarItems' => $calendarItems,
            'calendarTodayItems' => $calendarTodayItems,
            'calendarPanchang' => $calendarPanchang,
            'calendarTodayPanchang' => $calendarTodayPanchang,
            'presenceCurrent' => $presenceCurrent,
            'presenceHistory' => $presenceHistory,
            'dashNotices' => $dashNotices,
            'committeeMembers' => $committeeMembers,
            'orgContact' => $orgContact,
        ]);
    }

    public function presenceIndex(Request $request): void
    {
        redirect(base_url() . '/organization/dashboard#presence');
    }

    public function presenceStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = $bundle['user'];
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', t('flash.presence_forbidden'));
            redirect(base_url() . '/organization/dashboard');
        }
        $names = isset($_POST['names']) && is_array($_POST['names']) ? $_POST['names'] : [];
        $changed = (new OrgPresence())->replaceCurrent($orgId, (int) $user['id'], $names);
        flash_set('ok', $changed ? t('flash.presence_updated') : t('flash.presence_unchanged'));
        redirect(base_url() . '/organization/dashboard');
    }

    public function sadhvisIndex(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $q = trim((string) ($_GET['q'] ?? ''));
        $filterSanghId = (int) ($_GET['sangh_id'] ?? 0);
        $presence = new OrgPresence();
        $sanghOptions = $presence->listOrganizationsWithCurrentPresence();
        $validSanghIds = [];
        foreach ($sanghOptions as $opt) {
            $validSanghIds[(int) ($opt['id'] ?? 0)] = true;
        }
        if ($filterSanghId > 0 && !isset($validSanghIds[$filterSanghId])) {
            $filterSanghId = 0;
        }
        $rows = $presence->listCurrentAcrossOrganizations(
            $q !== '' ? $q : null,
            $filterSanghId > 0 ? $filterSanghId : null
        );
        $selectedSangh = null;
        if ($filterSanghId > 0) {
            foreach ($sanghOptions as $opt) {
                if ((int) ($opt['id'] ?? 0) === $filterSanghId) {
                    $selectedSangh = $opt;
                    break;
                }
            }
        }
        $this->render('organization', 'sadhvis/index.php', [
            'pageTitle' => page_title(t('sadhvis.title')),
            'navActive' => 'sadhvis',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => $orgId,
            'rows' => $rows,
            'filterQ' => $q,
            'filterSanghId' => $filterSanghId,
            'sanghOptions' => $sanghOptions,
            'selectedSangh' => $selectedSangh,
        ]);
    }

    public function sadhvisSearch(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $q = trim((string) ($_GET['q'] ?? ''));
        $filterSanghId = (int) ($_GET['sangh_id'] ?? 0);
        $presence = new OrgPresence();
        $sanghOptions = $presence->listOrganizationsWithCurrentPresence();
        $validSanghIds = [];
        foreach ($sanghOptions as $opt) {
            $validSanghIds[(int) ($opt['id'] ?? 0)] = true;
        }
        if ($filterSanghId > 0 && !isset($validSanghIds[$filterSanghId])) {
            $filterSanghId = 0;
        }
        $rows = $presence->listCurrentAcrossOrganizations(
            $q !== '' ? $q : null,
            $filterSanghId > 0 ? $filterSanghId : null
        );
        $selectedSangh = null;
        if ($filterSanghId > 0) {
            foreach ($sanghOptions as $opt) {
                if ((int) ($opt['id'] ?? 0) === $filterSanghId) {
                    $selectedSangh = $opt;
                    break;
                }
            }
        }
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'display_name' => (string) ($row['display_name'] ?? ''),
                'organization_id' => (int) ($row['organization_id'] ?? 0),
                'organization_name' => (string) ($row['organization_name'] ?? ''),
                'organization_nickname' => (string) ($row['organization_nickname'] ?? ''),
                'organization_address' => (string) ($row['organization_address'] ?? ''),
                'organization_maps_url' => (string) ($row['organization_maps_url'] ?? ''),
            ];
        }
        $sanghPayload = null;
        if ($selectedSangh !== null) {
            $sanghPayload = [
                'id' => (int) ($selectedSangh['id'] ?? 0),
                'name' => (string) ($selectedSangh['name'] ?? ''),
                'nickname' => (string) ($selectedSangh['nickname'] ?? ''),
                'address' => (string) ($selectedSangh['address'] ?? ''),
                'maps_url' => (string) ($selectedSangh['maps_url'] ?? ''),
            ];
        }
        json_response([
            'ok' => true,
            'q' => $q,
            'sangh_id' => $filterSanghId,
            'count' => count($out),
            'rows' => $out,
            'selected_sangh' => $sanghPayload,
            'labels' => [
                'none' => t('sadhvis.none'),
                'none_filtered' => t('sadhvis.none_filtered'),
                'group_eyebrow' => t('sadhvis.group_eyebrow'),
                'group_count' => t('sadhvis.group_count'),
                'stat_present' => t('sadhvis.stat_present'),
                'stat_matching' => t('sadhvis.stat_matching'),
                'navigate' => t('maps.navigate'),
                'navigate_aria' => t('maps.navigate_aria'),
            ],
        ]);
    }

    public function familiesIndex(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $access = new Access();
        $canManageOrg = $access->canManageOrganization($bundle['user'], $orgId);
        if ($canManageOrg) {
            $membersTab = isset($_GET['members_tab']) && (string) $_GET['members_tab'] === 'import' ? 'import' : 'list';
            if ($membersTab === 'import') {
                $this->requireOrgAdminForImport($bundle['user'], $orgId);
                $this->renderMembersImportPage($bundle, null, [], []);

                return;
            }
            $parsed = parse_member_directory_filters_from_input($_GET);
            $members = (new Organization())->listMembersDirectory($orgId, member_directory_filters_for_model($parsed));
            $storage = $parsed['storage'];
            $this->render('organization', 'families/members.php', [
                'pageTitle' => page_title(t('members.title')),
                'navActive' => 'families',
            ], [
                'memberships' => $bundle['memberships'],
                'current' => $bundle['current'],
                'orgId' => $orgId,
                'members' => $members,
                'canManageOrg' => true,
                'membersTab' => 'list',
                'memberFilter' => ($storage['filter'] ?? 'all') === 'heads' ? 'heads' : 'all',
                'genderFilter' => $storage['gender'] ?? 'all',
                'professionFilter' => $storage['profession'] ?? 'all',
                'donationFilter' => $storage['donation'] ?? 'all',
                'ageFilters' => $storage['age'] ?? [],
                'preview' => null,
                'errors' => [],
                'warnings' => [],
            ]);

            return;
        }
        $familyModel = new Family();
        $families = $familyModel->listByOrganizationForMember((int) $bundle['user']['id'], $orgId);
        if (count($families) === 1) {
            $singleFamilyId = (int) ($families[0]['id'] ?? 0);
            if ($singleFamilyId > 0) {
                redirect(base_url() . '/organization/family?id=' . $singleFamilyId);
            }
        }
        $currentUser = $bundle['user'];
        foreach ($families as &$familyRow) {
            $fid = (int) ($familyRow['id'] ?? 0);
            $headUserId = (int) ($familyRow['head_user_id'] ?? 0);
            if ($fid < 1) {
                $familyRow['member_names'] = [];
                $familyRow['members'] = [];
                $familyRow['can_edit_names'] = false;
                $familyRow['head_user_id'] = 0;
                $familyRow['head_email'] = '';
                $familyRow['head_phone'] = '';
                continue;
            }
            $members = $familyModel->membersWithUsers($fid);
            $names = [];
            $details = [];
            foreach ($members as $m) {
                $n = trim((string) (($m['user_name'] ?? '') ?: ($m['name'] ?? '')));
                if ($n !== '') {
                    $names[] = $n;
                }
                $details[] = [
                    'user_id' => (int) ($m['user_id'] ?? 0),
                    'name' => $n,
                    'role' => (string) ($m['role'] ?? ''),
                    'email' => (string) ($m['email'] ?? ''),
                    'phone' => (string) ($m['phone'] ?? ''),
                ];
            }
            $familyRow['member_names'] = $names;
            $familyRow['members'] = $details;
            $familyRow['can_edit_names'] = $access->canManageFamily($currentUser, $fid, $orgId);
            $familyRow['head_user_id'] = $headUserId;
            $headRow = $headUserId > 0 ? (new User())->findById($headUserId) : null;
            $familyRow['head_email'] = (string) ($headRow['email'] ?? '');
            $familyRow['head_phone'] = (string) ($headRow['phone'] ?? '');
        }
        unset($familyRow);
        $this->render('organization', 'families/index.php', [
            'pageTitle' => page_title('Family'),
            'navActive' => 'families',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => $orgId,
            'families' => $families,
            'canManageOrg' => $canManageOrg,
        ]);
    }

    public function membersImportIndex(Request $request): void
    {
        redirect(base_url() . '/organization/families?members_tab=import');
    }

    public function membersImportSample(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $this->requireOrgAdminForImport($bundle['user'], $orgId);
        (new FamilyImportService())->streamSampleCsv(false);
    }

    public function membersImportPreview(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $this->requireOrgAdminForImport($bundle['user'], $orgId);
        $file = $_FILES['import_file'] ?? null;
        if (!is_array($file) || !isset($file['tmp_name']) || (int) ($file['error'] ?? 1) !== 0) {
            $this->renderMembersImportPage($bundle, null, [t('import.families.error_upload')], []);
            return;
        }
        $result = (new FamilyImportService())->previewFromPath((string) $file['tmp_name'], $orgId);
        $this->renderMembersImportPage($bundle, $result['preview'], $result['errors'], $result['warnings']);
    }

    public function membersImportApply(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $this->requireOrgAdminForImport($bundle['user'], $orgId);
        $payload = trim((string) ($_POST['valid_rows_json'] ?? ''));
        if ($payload === '') {
            flash_set('error', t('import.families.error_payload_missing'));
            redirect(base_url() . '/organization/families?members_tab=import');
        }
        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            flash_set('error', t('import.families.error_payload_invalid'));
            redirect(base_url() . '/organization/families?members_tab=import');
        }
        $rows = json_decode($decoded, true);
        if (!is_array($rows) || $rows === []) {
            flash_set('error', t('import.families.error_no_rows'));
            redirect(base_url() . '/organization/families?members_tab=import');
        }
        foreach ($rows as $row) {
            if (!is_array($row) || (int) ($row['organization_id'] ?? 0) !== $orgId) {
                flash_set('error', t('import.families.error_org_mismatch'));
                redirect(base_url() . '/organization/families?members_tab=import');
            }
        }
        $actor = current_user();
        $createdBy = $actor ? (int) $actor['id'] : null;
        $result = (new FamilyImportService())->apply($rows, $createdBy);
        flash_set('ok', $result['summary']);
        redirect(base_url() . '/organization/families?members_tab=import');
    }

    /** @param array<string,mixed>|null $user */
    private function requireOrgAdminForImport(?array $user, int $orgId): void
    {
        if (!(new Access())->canManageOrganization($user, $orgId)) {
            flash_set('error', t('organization.import.admin_only'));
            redirect(base_url() . '/organization/dashboard');
        }
    }

    /** @param array<string,mixed> $bundle */
    private function renderMembersImportPage(array $bundle, ?array $preview, array $errors, array $warnings): void
    {
        $this->render('organization', 'families/members.php', [
            'pageTitle' => page_title(t('members.title')),
            'navActive' => 'families',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => (int) ($bundle['orgId'] ?? 0),
            'members' => [],
            'canManageOrg' => true,
            'membersTab' => 'import',
            'memberFilter' => 'all',
            'genderFilter' => 'all',
            'professionFilter' => 'all',
            'donationFilter' => 'all',
            'ageFilters' => [],
            'preview' => $preview,
            'errors' => $errors,
            'warnings' => $warnings,
        ]);
    }

    public function familyMemberSetActive(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only organization admins can deactivate members.');
            redirect(base_url() . '/organization/families');
        }

        $familyId = (int) ($_POST['family_id'] ?? 0);
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $active = isset($_POST['is_active']) && (string) $_POST['is_active'] === '1';
        $redirectTo = $familyId > 0
            ? base_url() . '/organization/family?id=' . $familyId
            : base_url() . '/organization/families';

        if ($targetUserId < 1) {
            flash_set('error', 'Member is required.');
            redirect($redirectTo);
        }
        if ($targetUserId === (int) ($user['id'] ?? 0)) {
            flash_set('error', 'You cannot deactivate your own account.');
            redirect($redirectTo);
        }

        $users = new User();
        $target = $users->findById($targetUserId);
        if ($target === null || (int) ($target['organization_id'] ?? 0) !== $orgId) {
            flash_set('error', 'Member not found in this organization.');
            redirect($redirectTo);
        }
        if ((string) ($target['role'] ?? '') !== 'member') {
            flash_set('error', 'Only members can be deactivated here. Contact platform admin for org admin accounts.');
            redirect($redirectTo);
        }
        if ($familyId > 0) {
            $this->familyInOrgOrAbort($familyId, $orgId);
            $membership = (new Family())->getHouseholdMembership($familyId, $targetUserId);
            if ($membership === null) {
                flash_set('error', 'Member not found in this family.');
                redirect($redirectTo);
            }
        }

        $users->setActive($targetUserId, $active);
        flash_set('ok', $active ? 'Member reactivated. They can sign in again.' : 'Member deactivated. They can no longer sign in.');
        redirect($redirectTo);
    }

    public function familyMemberUpdateStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        $familyIdRaw = (int) ($_POST['family_id'] ?? 0);
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $redirectAfterUpdate = static function (int $familyId): void {
            $returnUrl = trim((string) ($_POST['return_url'] ?? ''));
            $base = base_url();
            if ($returnUrl !== '' && str_starts_with($returnUrl, $base)) {
                redirect($returnUrl);
            }
            if ($familyId > 0) {
                redirect($base . '/organization/family?id=' . $familyId);
            }
            redirect($base . '/organization/families');
        };
        $nameParsed = parse_person_name_from_post($_POST);
        if (($nameParsed['ok'] ?? false) !== true) {
            flash_set('error', $nameParsed['error'] ?? 'Family, member, and name are required.');
            $redirectAfterUpdate($familyIdRaw);
        }
        $emailRaw = trim((string) ($_POST['email'] ?? ''));
        $phoneRaw = trim((string) ($_POST['phone'] ?? ''));
        if ($familyIdRaw < 1 || $targetUserId < 1) {
            flash_set('error', 'Family and member are required.');
            $redirectAfterUpdate($familyIdRaw);
        }
        $family = $this->familyInOrgOrAbort($familyIdRaw, $orgId);
        $familyId = (int) ($family['id'] ?? 0);
        $access = new Access();
        if (!$access->canManageFamily($user, $familyId, $orgId)) {
            flash_set('error', 'You cannot edit members in this family.');
            $redirectAfterUpdate($familyId);
        }
        $families = new Family();
        $membership = $families->getHouseholdMembership($familyId, $targetUserId);
        if ($membership === null) {
            flash_set('error', 'Member not found in this family.');
            $redirectAfterUpdate($familyId);
        }
        $isHead = strtolower((string) ($membership['role'] ?? '')) === 'head';
        if ($isHead && !$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only org admins can edit head details.');
            $redirectAfterUpdate($familyId);
        }
        $email = normalize_email($emailRaw);
        if ($email !== null && !is_valid_email($email)) {
            flash_set('error', 'Invalid email address.');
            $redirectAfterUpdate($familyId);
        }
        $phoneResult = provisioned_phone_from_post($phoneRaw, true);
        if (($phoneResult['ok'] ?? false) !== true) {
            flash_set('error', $phoneResult['error'] ?? 'Valid phone number is required.');
            $redirectAfterUpdate($familyId);
        }
        $phone = $phoneResult['phone'] ?? null;
        $users = new User();
        if ($email !== null) {
            $existingEmailUser = $users->findByEmail($email, $orgId);
            if ($existingEmailUser !== null && (int) $existingEmailUser['id'] !== $targetUserId) {
                flash_set('error', 'Email is already used by another user.');
                $redirectAfterUpdate($familyId);
            }
        }
        if ($phone !== null) {
            $phoneVariants = [$phone];
            if (strlen($phone) === 12 && strpos($phone, '91') === 0) {
                $phoneVariants[] = substr($phone, 2);
            } elseif (strlen($phone) === 10) {
                $phoneVariants[] = '91' . $phone;
            }
            foreach (array_unique($phoneVariants) as $variant) {
                $existingPhoneUser = $users->findByPhone($variant, $orgId);
                if ($existingPhoneUser !== null && (int) $existingPhoneUser['id'] !== $targetUserId) {
                    flash_set('error', 'Phone number is already used by another user.');
                    $redirectAfterUpdate($familyId);
                }
            }
        }
        if (array_key_exists('role', $_POST)) {
            $role = trim((string) ($_POST['role'] ?? ''));
            $relatedRaw = $_POST['related_to_user_id'] ?? '';
            $relatedTo = ($relatedRaw !== '' && $relatedRaw !== '0') ? (int) $relatedRaw : null;
            if (strtolower($role) === 'head' && !$access->canManageOrganization($user, $orgId)) {
                flash_set('error', 'Only an org admin can set head.');
                $redirectAfterUpdate($familyId);
            }
            $roleErr = $families->validateMemberRoleUpdate($familyId, $targetUserId, $role, $relatedTo);
            if ($roleErr !== null) {
                flash_set('error', $roleErr);
                $redirectAfterUpdate($familyId);
            }
            $relatedForStore = strtolower($role) === 'head' ? null : $relatedTo;
            $headUserId = (int) ($family['head_user_id'] ?? 0);
            if ($headUserId > 0) {
                $families->updateMemberAcrossHousehold($headUserId, $targetUserId, $role, $relatedForStore);
            } else {
                $families->upsertMember($familyId, $targetUserId, $role, $relatedForStore);
            }
        }
        $targetRow = $users->findById($targetUserId);
        $oldEmail = normalize_email($targetRow['email'] ?? null) ?? '';
        $newEmail = normalize_email($email) ?? '';
        $users->updatePersonDetails(
            $targetUserId,
            (string) $nameParsed['first_name'],
            $nameParsed['middle_name'] ?? null,
            (string) $nameParsed['last_name'],
            $email,
            $phone
        );
        $fullName = (string) ($nameParsed['full_name'] ?? user_display_name($targetRow ?: []));
        $emailChanged = $newEmail !== '' && $newEmail !== $oldEmail;
        if ($emailChanged) {
            $temporaryPassword = generate_temporary_otp_password();
            $users->updatePasswordHash($targetUserId, password_hash($temporaryPassword, PASSWORD_BCRYPT));
            $users->setMustChangePassword($targetUserId, true);
            $token = (new EmailVerificationToken())->createForUser($targetUserId);
            $verifyUrl = $token !== '' ? base_url() . '/verify-email?token=' . urlencode($token) : base_url() . '/login';
            $orgRow = (new Organization())->findById($orgId);
            $orgCode = $orgRow !== null ? strtoupper(trim((string) ($orgRow['org_code'] ?? ''))) : null;
            send_invite_email_with_password(
                (string) $email,
                $fullName,
                $temporaryPassword,
                $verifyUrl,
                $orgCode !== '' ? $orgCode : null,
                base_url() . '/login'
            );
            flash_set('ok', 'Member details updated. Temporary password sent to new email.');
            $redirectAfterUpdate($familyId);
        }
        flash_set('ok', 'Member details updated.');
        $redirectAfterUpdate($familyId);
    }

    public function familySplitStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only organization admins can split a family.');
            redirect(base_url() . '/organization/families');
        }
        $familyIdRaw = (int) ($_POST['family_id'] ?? 0);
        $newHeadUserId = (int) ($_POST['new_head_user_id'] ?? 0);
        if ($familyIdRaw < 1 || $newHeadUserId < 1) {
            flash_set('error', 'Family and new head are required.');
            redirect(base_url() . '/organization/families');
        }
        $family = $this->familyInOrgOrAbort($familyIdRaw, $orgId);
        $familyId = (int) ($family['id'] ?? 0);
        $familyModel = new Family();
        if ((int) ($family['head_user_id'] ?? 0) === $newHeadUserId) {
            flash_set('error', 'Selected person is already the head of this family.');
            redirect(base_url() . '/organization/family?id=' . $familyId);
        }
        $directMembers = $familyModel->listDirectMembers($familyId);
        if ($directMembers === []) {
            flash_set('error', 'No members found to split.');
            redirect(base_url() . '/organization/family?id=' . $familyId);
        }
        $memberByUserId = [];
        foreach ($directMembers as $row) {
            $uid = (int) ($row['user_id'] ?? 0);
            if ($uid > 0) {
                $memberByUserId[$uid] = $row;
            }
        }
        if (!isset($memberByUserId[$newHeadUserId])) {
            flash_set('error', 'Selected head is not in this family.');
            redirect(base_url() . '/organization/family?id=' . $familyId);
        }

        $moveUserIds = [$newHeadUserId => true];
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($directMembers as $row) {
                $uid = (int) ($row['user_id'] ?? 0);
                if ($uid < 1 || isset($moveUserIds[$uid])) {
                    continue;
                }
                $relatedTo = isset($row['related_to_user_id']) ? (int) $row['related_to_user_id'] : 0;
                if ($relatedTo > 0 && isset($moveUserIds[$relatedTo])) {
                    $moveUserIds[$uid] = true;
                    $changed = true;
                }
            }
        }
        $moveIds = array_values(array_map('intval', array_keys($moveUserIds)));
        if ($moveIds === []) {
            flash_set('error', 'No related members found to move.');
            redirect(base_url() . '/organization/family?id=' . $familyId);
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $newFamilyId = $familyModel->create($orgId, $newHeadUserId, (int) ($user['id'] ?? 0));
            $history = new FamilyHistory();
            $actorUserId = isset($user['id']) ? (int) $user['id'] : null;

            foreach ($moveIds as $uid) {
                $row = $memberByUserId[$uid] ?? null;
                if ($row === null) {
                    continue;
                }
                $personName = (string) ($row['user_name'] ?? ('User #' . $uid));
                if ($uid === $newHeadUserId) {
                    $familyModel->upsertMember($newFamilyId, $uid, 'head', null);
                    $history->create(
                        $orgId,
                        $newFamilyId,
                        $uid,
                        $actorUserId,
                        'split_in_head',
                        'New family head created',
                        null
                    );
                    $history->create(
                        $orgId,
                        $familyId,
                        $uid,
                        $actorUserId,
                        'split_out_member',
                        'Member moved out',
                        null
                    );
                    continue;
                }
                $role = (string) ($row['role'] ?? 'other');
                $relatedTo = isset($row['related_to_user_id']) ? (int) $row['related_to_user_id'] : 0;
                $relatedToNew = in_array($relatedTo, $moveIds, true) ? $relatedTo : null;
                $familyModel->upsertMember($newFamilyId, $uid, $role, $relatedToNew);
                $history->create(
                    $orgId,
                    $newFamilyId,
                    $uid,
                    $actorUserId,
                    'split_in_member',
                    'Member moved in',
                    null
                );
                $history->create(
                    $orgId,
                    $familyId,
                    $uid,
                    $actorUserId,
                    'split_out_member',
                    'Member moved out',
                    null
                );
            }

            (new FamilyDependent())->moveByRelatedUsers($familyId, $newFamilyId, $moveIds);

            $linksModel = new FamilyRelationshipLink();
            $existingLinks = $linksModel->listByFamilyAndUserIds($familyId, $moveIds);
            foreach ($existingLinks as $link) {
                $uid = (int) ($link['user_id'] ?? 0);
                $role = (string) ($link['relationship_role'] ?? 'other');
                $relatedTo = isset($link['related_to_user_id']) ? (int) $link['related_to_user_id'] : 0;
                $relatedToNew = in_array($relatedTo, $moveIds, true) ? $relatedTo : null;
                if ($uid > 0) {
                    $linksModel->upsert($newFamilyId, $uid, $role, $relatedToNew);
                }
            }
            $linksModel->deleteByFamilyAndUserIds($familyId, $moveIds);

            $familyModel->removeMembersByUserIds($familyId, $moveIds);
            $familyModel->ensureHeadMembershipForDesignatedHead($familyId);
            $familyModel->normalizeHeadRolesForHousehold($familyId);
            $familyModel->ensureHeadMembershipForDesignatedHead($newFamilyId);
            $familyModel->normalizeHeadRolesForHousehold($newFamilyId);

            $pdo->commit();
            flash_set('ok', 'Family split done. New family created successfully.');
            redirect(base_url() . '/organization/family?id=' . $newFamilyId);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            flash_set('error', 'Could not split family. Please try again.');
            redirect(base_url() . '/organization/family?id=' . $familyId);
        }
    }

    public function familyNew(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $access = new Access();
        if (!$access->canManageOrganization($bundle['user'], $orgId)) {
            flash_set('error', 'Only an org admin can add a new family.');
            redirect(base_url() . '/organization/families');
        }
        $this->render('organization', 'families/new.php', [
            'pageTitle' => page_title('New family'),
            'navActive' => 'families',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => $orgId,
        ]);
    }

    public function familyCreateStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Forbidden.');
            redirect(base_url() . '/organization/families');
        }
        $users = new User();
        $nameParsed = parse_person_name_from_post($_POST, 'head_', 'head_name');
        if (($nameParsed['ok'] ?? false) !== true) {
            flash_set('error', $nameParsed['error'] ?? 'Family head name is required.');
            redirect(base_url() . '/organization/families/new');
        }
        $emailRaw = trim((string) ($_POST['head_email'] ?? ''));
        $email = normalize_email($emailRaw);
        $phoneResult = provisioned_phone_from_post(trim((string) ($_POST['head_phone'] ?? '')), true);
        if (($phoneResult['ok'] ?? false) !== true) {
            flash_set('error', $phoneResult['error'] ?? 'Valid phone number is required for the family head.');
            redirect(base_url() . '/organization/families/new');
        }
        $phone = $phoneResult['phone'] ?? null;

        if ($email === null || !is_valid_email($email)) {
            flash_set('error', 'A valid email is required for the family head.');
            redirect(base_url() . '/organization/families/new');
        }

        if ($users->findByEmail($email, $orgId) !== null) {
            flash_set('error', 'This email is already registered in this organization. Open that family to manage them.');
            redirect(base_url() . '/organization/families/new');
        }
        if ($phone !== null && $users->phoneIsRegistered($phone, $orgId)) {
            flash_set('error', 'That phone is already registered in this organization.');
            redirect(base_url() . '/organization/families/new');
        }
        $created = UserProvisionService::createWithInviteEmail($users, [
            'organization_id' => $orgId,
            'name' => (string) $nameParsed['full_name'],
            'first_name' => (string) $nameParsed['first_name'],
            'middle_name' => $nameParsed['middle_name'] ?? null,
            'last_name' => (string) $nameParsed['last_name'],
            'email' => $email,
            'phone' => $phone,
            'role' => 'member',
        ], [
            'duplicate_email' => 'Email already registered in this organization.',
            'duplicate_phone' => 'Phone already registered in this organization.',
        ]);
        if (($created['ok'] ?? false) !== true) {
            flash_set('error', $created['error'] ?? 'Could not create family head.');
            redirect(base_url() . '/organization/families/new');
        }
        $headUserId = (int) $created['id'];
        $inviteEmailSent = !empty($created['email_sent']);

        $families = new Family();
        $familyId = $families->create($orgId, $headUserId, (int) $user['id']);
        $families->addMember($familyId, $headUserId, 'head', null);
        (new Due())->syncMembershipChargesForOrganization($orgId);
        if (!empty($created['email_deferred'])) {
            flash_set('ok', 'Family created. Invite email is being sent — it may take a minute.');
        } elseif ($inviteEmailSent === true) {
            flash_set('ok', 'Family created. Login details were emailed to the head.');
        } elseif ($inviteEmailSent === false) {
            flash_set('ok', 'Family created, but the invite email could not be sent.');
        } else {
            flash_set('ok', 'Family created.');
        }
        redirect(base_url() . '/organization/family?id=' . $familyId);
    }

    public function familyShow(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundlePre = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundlePre['current']);
        $user = current_user();
        $familyId = (int) ($_GET['id'] ?? 0);
        if ($familyId < 1) {
            $resolved = $this->resolvePrimaryFamilyIdForUser((int) $user['id'], $orgId);
            if ($resolved > 0) {
                redirect(base_url() . '/organization/family?id=' . $resolved);
            }
            flash_set('error', 'Invalid family.');
            redirect(base_url() . '/organization/families');
        }
        $families = new Family();
        $family = $families->findById($familyId);
        if (!$family) {
            flash_set('error', 'Family not found.');
            redirect(base_url() . '/organization/families');
        }
        $canonicalId = $families->canonicalFamilyIdForHeadUserId((int) $family['head_user_id']);
        if ($canonicalId !== null && $canonicalId !== $familyId) {
            redirect(base_url() . '/organization/family?id=' . $canonicalId);
        }
        $sessionOrgId = (int) current_organization_id();
        if ($sessionOrgId < 1) {
            flash_set('error', 'No organization selected.');
            redirect(base_url() . '/organization/dashboard');
        }
        $orgModel = new Organization();
        if (!$orgModel->userIsMember((int) $user['id'], $sessionOrgId)) {
            flash_set('error', 'Family not found.');
            redirect(base_url() . '/organization/families');
        }
        if (!$families->familyIsAnchoredInOrganization($familyId, $sessionOrgId)) {
            flash_set('error', 'This household is not available in your current organization.');
            redirect(base_url() . '/organization/families');
        }
        $orgId = $sessionOrgId;
        $access = new Access();
        if (!$access->canViewFamily($user, $familyId, $orgId)) {
            flash_set('error', 'You can’t open this family.');
            redirect(base_url() . '/organization/families');
        }
        $families->ensureHeadMembershipForDesignatedHead($familyId);
        $families->normalizeHeadRolesForHousehold($familyId);
        $bundle = $bundlePre;
        $members = $families->membersWithUsers($familyId);
        $dependents = (new FamilyDependent())->listByFamilyId($familyId);
        $canManageOrg = $access->canManageOrganization($user, $orgId);
        $canManageFamily = $access->canManageFamily($user, $familyId, $orgId);
        $canAddHead = $canManageOrg && !$families->familyAlreadyHasHeadMember($familyId);
        $isHeadViewer = $families->userIsHead((int) $user['id'], $familyId);
        $ownFamilyId = $this->resolvePrimaryFamilyIdForUser((int) $user['id'], $orgId);
        $familyPageTitle = family_page_title($ownFamilyId === $familyId, $family, $members);
        $this->render('organization', 'families/show.php', [
            'pageTitle' => page_title($familyPageTitle),
            'navActive' => 'families',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => $orgId,
            'family' => $family,
            'familyPageTitle' => $familyPageTitle,
            'members' => $members,
            'dependents' => $dependents,
            'canManageOrg' => $canManageOrg,
            'canManageFamily' => $canManageFamily,
            'canAddHead' => $canAddHead,
            'isHeadViewer' => $isHeadViewer,
        ]);
    }

    public function myFamily(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        $familyId = $this->resolvePrimaryFamilyIdForUser((int) $user['id'], $orgId);
        if ($familyId < 1) {
            flash_set('error', 'No family found for your account.');
            redirect(base_url() . '/organization/dashboard');
        }
        redirect(base_url() . '/organization/family?id=' . $familyId);
    }

    public function familyHistory(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        $familyId = (int) ($_GET['id'] ?? 0);
        if ($familyId < 1) {
            flash_set('error', 'Invalid family.');
            redirect(base_url() . '/organization/families');
        }
        $families = new Family();
        $family = $families->findById($familyId);
        if ($family === null) {
            flash_set('error', 'Family not found.');
            redirect(base_url() . '/organization/families');
        }
        $access = new Access();
        if (!$access->canViewFamily($user, $familyId, $orgId)) {
            flash_set('error', 'You can’t open this family history.');
            redirect(base_url() . '/organization/families');
        }
        $events = (new FamilyHistory())->listByFamily($orgId, $familyId);
        $ownFamilyId = $this->resolvePrimaryFamilyIdForUser((int) $user['id'], $orgId);
        $familyPageTitle = family_page_title($ownFamilyId === $familyId, $family);
        $this->render('organization', 'families/history.php', [
            'pageTitle' => page_title($familyPageTitle),
            'navActive' => 'families',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => $orgId,
            'family' => $family,
            'familyPageTitle' => $familyPageTitle,
            'events' => $events,
        ]);
    }

    public function familyAddMemberStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        $familyIdRaw = (int) ($_POST['family_id'] ?? 0);
        $role = trim((string) ($_POST['role'] ?? ''));
        $relatedRaw = $_POST['related_to_user_id'] ?? '';
        $relatedTo = $relatedRaw !== '' ? (int) $relatedRaw : null;

        if ($familyIdRaw < 1 || $role === '') {
            flash_set('error', 'Family and role are required.');
            if ($familyIdRaw > 0) {
                redirect(base_url() . '/organization/family?id=' . $familyIdRaw);
            }
            redirect(base_url() . '/organization/families');
        }

        $families = new Family();
        $family = $this->familyInOrgOrAbort($familyIdRaw, $orgId);
        $familyId = (int) $family['id'];

        $redirectBack = static function () use ($familyId): void {
            if ($familyId > 0) {
                redirect(base_url() . '/organization/family?id=' . $familyId);
            }
            redirect(base_url() . '/organization/families');
        };

        $access = new Access();
        if (!$access->canManageFamily($user, $familyId, $orgId)) {
            flash_set('error', 'You can’t add people here.');
            $redirectBack();
        }

        $users = new User();
        $memberMode = isset($_POST['member_mode']) ? trim((string) $_POST['member_mode']) : 'new';

        if ($memberMode === 'existing') {
            flash_set('error', 'This person must be added as a new member with a new email. If they are already in the organization, open their family instead.');
            $redirectBack();
        }

        if ($memberMode === 'dependent') {
            $name = trim((string) ($_POST['dep_name'] ?? ''));
            $dob = trim((string) ($_POST['dep_dob'] ?? ''));
            $pincode = trim((string) ($_POST['dep_pincode'] ?? ''));
            $city = trim((string) ($_POST['dep_city'] ?? ''));
            $state = trim((string) ($_POST['dep_state'] ?? ''));
            if ($name === '') {
                flash_set('error', 'Dependent name is required.');
                $redirectBack();
            }
            if ($dob === '' || strtotime($dob) === false) {
                flash_set('error', 'Valid dependent birthdate is required.');
                $redirectBack();
            }
            if (!preg_match('/^\d{6}$/', $pincode)) {
                flash_set('error', 'Dependent pincode must be 6 digits.');
                $redirectBack();
            }
            if ($city === '' || $state === '') {
                flash_set('error', 'Dependent city/state is required.');
                $redirectBack();
            }
            $relatedForDependent = strtolower($role) === 'head' ? null : $relatedTo;
            if (strtolower($role) !== 'head' && ($relatedForDependent === null || $relatedForDependent < 1)) {
                flash_set('error', 'Please choose related member for dependent.');
                $redirectBack();
            }
            (new FamilyDependent())->create($familyId, $name, $role, $relatedForDependent, $dob, $pincode, $city, $state);
            flash_set('ok', 'Dependent added.');
            $redirectBack();
        }

        if ($memberMode !== 'new') {
            flash_set('error', 'Invalid member type.');
            $redirectBack();
        }

        $nameParsed = parse_person_name_from_post($_POST, 'new_', 'new_name');
        if (($nameParsed['ok'] ?? false) !== true) {
            flash_set('error', $nameParsed['error'] ?? 'Name is required.');
            $redirectBack();
        }
        $emailRaw = trim((string) ($_POST['new_email'] ?? ''));
        $email = normalize_email($emailRaw);
        $phoneResult = provisioned_phone_from_post(trim((string) ($_POST['new_phone'] ?? '')), true);
        if (($phoneResult['ok'] ?? false) !== true) {
            flash_set('error', $phoneResult['error'] ?? 'Valid phone number is required.');
            $redirectBack();
        }
        $phone = $phoneResult['phone'] ?? null;
        if ($email === null || !is_valid_email($email)) {
            flash_set('error', 'A valid email is required.');
            $redirectBack();
        }
        if ($users->findByEmail($email, $orgId) !== null) {
            flash_set('error', 'This email is already registered in this organization.');
            $redirectBack();
        }
        if ($phone !== null && $users->phoneIsRegistered($phone, $orgId)) {
            flash_set('error', 'This phone is already registered in this organization.');
            $redirectBack();
        }
        $created = UserProvisionService::createWithInviteEmail($users, [
            'organization_id' => $orgId,
            'name' => (string) $nameParsed['full_name'],
            'first_name' => (string) $nameParsed['first_name'],
            'middle_name' => $nameParsed['middle_name'] ?? null,
            'last_name' => (string) $nameParsed['last_name'],
            'email' => $email,
            'phone' => $phone,
            'role' => 'member',
        ]);
        if (($created['ok'] ?? false) !== true) {
            flash_set('error', $created['error'] ?? 'Could not create member.');
            $redirectBack();
        }
        $targetUserId = (int) $created['id'];
        $memberInviteEmailSent = !empty($created['email_sent']);

        if ($families->userBelongsToOrganizationFamily($targetUserId, $orgId)) {
            flash_set('error', 'This person is already in a family in this organization.');
            $redirectBack();
        }

        if (strtolower($role) === 'head' && !$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only an org admin can set head.');
            $redirectBack();
        }

        $relatedForRule = $relatedTo;
        if (strtolower($role) === 'head') {
            $relatedForRule = null;
        }

        $err = $families->validateAddMember($familyId, $targetUserId, $role, $relatedForRule);
        if ($err !== null) {
            flash_set('error', $err);
            $redirectBack();
        }

        try {
            $families->addMember($familyId, $targetUserId, $role, $relatedForRule);
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                flash_set('error', 'Already in this family.');
                $redirectBack();
            }
            throw $e;
        }
        (new Due())->syncMembershipChargesForOrganization($orgId);

        if (!empty($created['email_deferred'])) {
            flash_set('ok', 'Member added to family. Invite email is being sent — it may take a minute.');
        } elseif ($memberInviteEmailSent === true) {
            flash_set('ok', 'Member added to family. Login details were emailed to them.');
        } elseif ($memberInviteEmailSent === false) {
            flash_set('ok', 'Member added, but the invite email could not be sent.');
        } else {
            flash_set('ok', 'Member added to family.');
        }
        redirect(base_url() . '/organization/family?id=' . $familyId);
    }

    public function relationshipRequestForm(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundlePre = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundlePre['current']);
        $user = current_user();
        $familyIdRaw = (int) ($_GET['family_id'] ?? 0);
        $targetUserId = (int) ($_GET['user_id'] ?? 0);
        if ($familyIdRaw < 1 || $targetUserId < 1) {
            flash_set('error', 'Invalid link.');
            redirect(base_url() . '/organization/families');
        }
        $families = new Family();
        $family = $this->familyInOrgOrAbort($familyIdRaw, $orgId);
        $familyId = (int) $family['id'];
        $access = new Access();
        if (!$access->canManageFamily($user, $familyId, $orgId)) {
            flash_set('error', 'You cannot send this request.');
            redirect(base_url() . '/organization/family?id=' . $familyId);
        }
        $membership = $families->getHouseholdMembership($familyId, $targetUserId);
        if ($membership === null) {
            flash_set('error', 'Add them on the family page first.');
            redirect(base_url() . '/organization/family?id=' . $familyId);
        }
        $targetRow = (new User())->findById($targetUserId);
        if (!$targetRow) {
            flash_set('error', 'User not found.');
            redirect(base_url() . '/organization/family?id=' . $familyId);
        }
        $bundle = $bundlePre;
        $canManageOrg = $access->canManageOrganization($user, $orgId);
        $isTargetCurrentHead = strtolower((string) $membership['role']) === 'head';
        $canAddHead = $canManageOrg && (!$families->familyAlreadyHasHeadMember($familyId) || $isTargetCurrentHead);
        $members = $families->membersWithUsers($familyId);
        $this->render('organization', 'families/relationship_request.php', [
            'pageTitle' => page_title('Relationship request'),
            'navActive' => 'families',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => $orgId,
            'familyId' => $familyId,
            'targetUser' => [
                'id' => $targetUserId,
                'name' => (string) $targetRow['name'],
                'email' => $targetRow['email'] ?? null,
                'phone' => $targetRow['phone'] ?? null,
            ],
            'currentMembership' => $membership,
            'members' => $members,
            'canAddHead' => $canAddHead,
        ]);
    }

    public function relationshipRequestStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        $familyIdRaw = (int) ($_POST['family_id'] ?? 0);
        $targetUserId = (int) ($_POST['target_user_id'] ?? 0);
        $role = trim((string) ($_POST['role'] ?? ''));
        $relatedRaw = $_POST['related_to_user_id'] ?? '';
        $relatedTo = $relatedRaw !== '' ? (int) $relatedRaw : null;

        if ($familyIdRaw < 1 || $targetUserId < 1 || $role === '') {
            flash_set('error', 'Invalid request.');
            if ($familyIdRaw > 0) {
                redirect(base_url() . '/organization/family?id=' . $familyIdRaw);
            }
            redirect(base_url() . '/organization/families');
        }

        $families = new Family();
        $family = $this->familyInOrgOrAbort($familyIdRaw, $orgId);
        $familyId = (int) $family['id'];

        $backFamily = static function () use ($familyId): void {
            redirect(base_url() . '/organization/family?id=' . $familyId);
        };

        $access = new Access();
        if (!$access->canManageFamily($user, $familyId, $orgId)) {
            flash_set('error', 'You cannot send this request.');
            $backFamily();
        }
        if (!$families->getHouseholdMembership($familyId, $targetUserId)) {
            flash_set('error', 'That person is not in this family.');
            $backFamily();
        }
        if (strtolower($role) === 'head' && !$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only an org admin can set head.');
            $backFamily();
        }
        $relatedForRule = strtolower($role) === 'head' ? null : $relatedTo;
        $err = $families->validateRelationshipChange($familyId, $targetUserId, $role, $relatedForRule);
        if ($err !== null) {
            flash_set('error', $err);
            redirect(base_url() . '/organization/family/relationship-request?family_id=' . $familyId . '&user_id=' . $targetUserId);
        }

        $reqModel = new FamilyMembershipRequest();
        $requestId = $reqModel->createPending(
            $familyId,
            $targetUserId,
            (int) $user['id'],
            $role,
            $relatedForRule
        );
        $orgName = (string) (($this->orgPageBundle($orgId)['current']['name'] ?? '') ?: 'Upashray/Sangh');
        $title = 'Relationship update in ' . $orgName;
        $msg = (string) $user['name'] . ' wants to update your role in family #' . $familyId . ' to “' . $role . '”. Open Notifications to approve or decline.';
        (new Notification())->createForUser($targetUserId, 'relationship_request', $requestId, $title, $msg);

        flash_set('ok', 'Sent. They need to approve.');
        $backFamily();
    }

    public function notificationsIndex(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $user = current_user();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $access = new Access();
        $canManageOrg = $access->canManageOrganization($user, $orgId);
        $notifModel = new Notification();
        $pending = $canManageOrg ? [] : (new FamilyMembershipRequest())->listPendingForTargetUser((int) $user['id']);
        $pageSize = Notification::PAGE_SIZE;
        $recent = $canManageOrg ? [] : $notifModel->listPagedForUser((int) $user['id'], $pageSize, 0);
        $notificationsTotal = $canManageOrg ? 0 : $notifModel->countInWindowForUser((int) $user['id']);
        $notificationsHasMore = !$canManageOrg && count($recent) < $notificationsTotal;
        $unreadNotifications = [];
        $readNotifications = [];
        foreach ($recent as $n) {
            if (empty($n['read_at'])) {
                $unreadNotifications[] = $n;
            } else {
                $readNotifications[] = $n;
            }
        }
        $unreadInWindow = $canManageOrg ? 0 : $notifModel->countUnreadInWindow((int) $user['id']);
        $campaigns = $canManageOrg ? $notifModel->listRecentCampaignsForOrganization($orgId, 20) : [];
        $queueSummary = $canManageOrg ? $notifModel->listQueueSummaryForOrganization($orgId) : [];
        $orgUsersForWhatsApp = $canManageOrg ? (new Organization())->listMembers($orgId) : [];
        $pushConfigured = web_push_is_configured();
        $pushSubscriptionCount = $canManageOrg ? 0 : (new \App\Models\PushSubscription())->countForUser((int) $user['id']);
        $this->render('organization', 'notifications/index.php', [
            'pageTitle' => page_title('Notifications'),
            'navActive' => 'notifications',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => $orgId,
            'canManageOrg' => $canManageOrg,
            'pendingRequests' => $pending,
            'recentNotifications' => $recent,
            'unreadNotifications' => $unreadNotifications,
            'readNotifications' => $readNotifications,
            'unreadInWindow' => $unreadInWindow,
            'notificationsTotal' => $notificationsTotal,
            'notificationsHasMore' => $notificationsHasMore,
            'notificationsPageSize' => $pageSize,
            'notificationsRecentMonths' => Notification::RECENT_MONTHS,
            'campaigns' => $campaigns,
            'queueSummary' => $queueSummary,
            'orgUsersForWhatsApp' => $orgUsersForWhatsApp,
            'pushConfigured' => $pushConfigured,
            'pushSubscriptionCount' => $pushSubscriptionCount,
        ]);
    }

    public function notificationsPreview(Request $request): void
    {
        $this->notificationsList($request);
    }

    public function notificationsList(Request $request): void
    {
        $accept = (string) ($request->header('Accept') ?? '');
        $wantsJson = request_wants_json($accept, $request->header('Content-Type'));
        $wantsHtml = str_contains($accept, 'text/html');
        if (!$wantsJson && $wantsHtml) {
            redirect(base_url() . '/organization/notifications');
        }
        $this->requireOrgId();
        $user = current_user();
        $uid = (int) $user['id'];
        $model = new Notification();
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : Notification::PAGE_SIZE;
        $offset = (int) ($_GET['offset'] ?? 0);
        $inbox = isset($_GET['inbox']) && (string) $_GET['inbox'] === '1';
        $items = [];
        if ($limit > 0) {
            $rows = $model->listPagedForUser($uid, $limit, $offset);
            foreach ($rows as $row) {
                $items[] = $inbox ? notification_to_inbox_client($row) : notification_to_client($row);
            }
        }
        $loaded = $offset + count($items);
        $total = $model->countInWindowForUser($uid);
        json_response([
            'ok' => true,
            'unreadCount' => $model->countUnreadInWindow($uid),
            'items' => $items,
            'offset' => $offset,
            'limit' => $limit,
            'total' => $total,
            'hasMore' => $loaded < $total,
            'pageSize' => Notification::PAGE_SIZE,
            'recentMonths' => Notification::RECENT_MONTHS,
            'viewAllUrl' => base_url() . '/organization/notifications',
        ]);
    }

    public function notificationsMarkRead(Request $request): void
    {
        $this->requireOrgId();
        $user = current_user();
        $uid = (int) $user['id'];
        $model = new Notification();
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        $wantsJson = str_contains($contentType, 'application/json');
        $payload = [];
        if ($wantsJson) {
            $raw = file_get_contents('php://input');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        } else {
            $payload = $_POST;
        }
        if (!empty($payload['all'])) {
            $marked = $model->markAllReadForUser($uid);
            if ($wantsJson) {
                json_response([
                    'ok' => true,
                    'marked' => $marked,
                    'unreadCount' => 0,
                ]);
            }
            flash_set('ok', t('notifications.mark_all_read_done', ['count' => (string) $marked]));
            redirect(base_url() . '/organization/notifications');
        }
        $id = (int) ($payload['id'] ?? 0);
        if ($id > 0) {
            $model->markReadForUser($id, $uid);
            if ($wantsJson) {
                json_response([
                    'ok' => true,
                    'unreadCount' => $model->countUnread($uid),
                ]);
            }
            redirect(base_url() . '/organization/notifications');
        }
        if ($wantsJson) {
            json_response(['ok' => false, 'error' => 'Invalid request'], 400);
        }
        redirect(base_url() . '/organization/notifications');
    }

    public function notificationsBroadcastStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only organization admins can send broadcasts.');
            redirect(base_url() . '/organization/notifications');
        }
        $title = trim((string) ($_POST['title'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $channels = $_POST['channels'] ?? null;
        $channelList = is_array($channels) ? array_values(array_filter(array_map('strval', $channels))) : ['in_app'];
        $memberFilters = parse_member_directory_filters_from_input($_POST);
        $recipientIdsRaw = $_POST['recipient_ids'] ?? [];
        $recipientUserIds = [];
        if (is_array($recipientIdsRaw)) {
            foreach ($recipientIdsRaw as $rid) {
                $uid = (int) $rid;
                if ($uid > 0) {
                    $recipientUserIds[$uid] = $uid;
                }
            }
            $recipientUserIds = array_values($recipientUserIds);
        }
        if ($recipientUserIds === []) {
            flash_set('error', t('notifications.broadcast_no_recipients_selected'));
            redirect(base_url() . '/organization/notifications');
        }
        $memberFilters['recipient_user_ids'] = $recipientUserIds;

        if ($title === '' || $message === '') {
            flash_set('error', 'Title and message are required.');
            redirect(base_url() . '/organization/notifications');
        }
        if (strlen($title) > 255) {
            flash_set('error', 'Title must be 255 characters or less.');
            redirect(base_url() . '/organization/notifications');
        }
        if (strlen($message) > 5000) {
            flash_set('error', 'Message is too long.');
            redirect(base_url() . '/organization/notifications');
        }

        try {
            $result = (new Notification())->broadcastToOrganization(
                $orgId,
                $title,
                $message,
                $channelList,
                $memberFilters,
                isset($user['id']) ? (int) $user['id'] : null
            );
        } catch (\Throwable $e) {
            $detail = (string) app_config('env', '') === 'development' ? ' ' . $e->getMessage() : '';
            flash_set('error', t('notifications.broadcast_failed') . $detail);
            redirect(base_url() . '/organization/notifications');
        }
        if ($result['total_recipients'] === 0) {
            flash_set('error', t('notifications.broadcast_no_recipients'));
            redirect(base_url() . '/organization/notifications');
        }
        $msg = 'Broadcast sent to ' . $result['in_app_sent_count'] . ' users (in-app).';
        if ($result['push_sent_count'] > 0) {
            $msg .= ' Push delivered: ' . $result['push_sent_count'] . '.';
        }
        if ($result['whatsapp_queued_count'] > 0) {
            $msg .= ' WhatsApp queued: ' . $result['whatsapp_queued_count'] . '.';
        }
        flash_set('ok', $msg);
        redirect(base_url() . '/organization/notifications');
    }

    public function notificationsBroadcastRecipients(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            json_response(['ok' => false, 'error' => 'Forbidden'], 403);
        }
        $parsed = parse_member_directory_filters_from_input($_GET);
        $rows = (new Organization())->listMembersDirectory($orgId, member_directory_filters_for_model($parsed));
        $members = [];
        foreach ($rows as $row) {
            $members[] = format_member_directory_recipient_json($row);
        }
        json_response([
            'ok' => true,
            'members' => $members,
            'total' => count($members),
        ]);
    }

    public function pushVapidPublicKey(Request $request): void
    {
        $this->requireOrgId();
        if (!web_push_is_configured()) {
            $setupError = web_push_setup_error();
            json_response([
                'ok' => false,
                'error' => $setupError ?? 'Web push is not configured on this server.',
            ], 503);
        }
        json_response(['ok' => true, 'publicKey' => web_push_public_key()]);
    }

    public function pushSubscribe(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $user = current_user();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        if (!web_push_is_configured()) {
            $setupError = web_push_setup_error();
            json_response([
                'ok' => false,
                'error' => $setupError ?? 'Web push is not configured on this server.',
            ], 503);
        }
        $raw = file_get_contents('php://input');
        $payload = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($payload)) {
            json_response(['ok' => false, 'error' => 'Invalid JSON body.'], 422);
        }
        $endpoint = trim((string) ($payload['endpoint'] ?? ''));
        $keys = isset($payload['keys']) && is_array($payload['keys']) ? $payload['keys'] : [];
        $p256dh = trim((string) ($keys['p256dh'] ?? ''));
        $auth = trim((string) ($keys['auth'] ?? ''));
        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            json_response(['ok' => false, 'error' => 'Subscription endpoint and keys are required.'], 422);
        }
        $userAgent = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        (new \App\Models\PushSubscription())->upsertForUser(
            (int) $user['id'],
            $endpoint,
            $p256dh,
            $auth,
            $userAgent !== '' ? $userAgent : null
        );
        json_response([
            'ok' => true,
            'subscriptionCount' => (new \App\Models\PushSubscription())->countForUser((int) $user['id']),
        ]);
    }

    public function pushUnsubscribe(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $user = current_user();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $raw = file_get_contents('php://input');
        $payload = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($payload)) {
            json_response(['ok' => false, 'error' => 'Invalid JSON body.'], 422);
        }
        $endpoint = trim((string) ($payload['endpoint'] ?? ''));
        if ($endpoint === '') {
            json_response(['ok' => false, 'error' => 'Endpoint is required.'], 422);
        }
        (new \App\Models\PushSubscription())->deleteForUserEndpoint((int) $user['id'], $endpoint);
        json_response(['ok' => true]);
    }

    public function pushSubscriptionStatus(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $user = current_user();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        json_response([
            'ok' => true,
            'configured' => web_push_is_configured(),
            'subscriptionCount' => (new \App\Models\PushSubscription())->countForUser((int) $user['id']),
        ]);
    }

    public function schemesIndex(Request $request): void
    {
        redirect(base_url() . '/organization/events?event_tab=schemes');
    }

    public function myReceiptsIndex(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        $access = new Access();
        if ($access->canManageOrganization($user, $orgId)) {
            redirect(base_url() . '/organization/receipts');
        }
        $familyModel = new Family();
        $families = $familyModel->listByOrganizationForMember((int) $user['id'], $orgId);
        $receiptModel = new Receipt();
        $receipts = [];
        foreach ($families as $familyRow) {
            $familyId = (int) ($familyRow['id'] ?? 0);
            if ($familyId < 1 || !$access->canViewFamily($user, $familyId, $orgId)) {
                continue;
            }
            foreach ($receiptModel->listByFamily($orgId, $familyId, 50) as $row) {
                $receipts[] = $row;
            }
        }
        usort(
            $receipts,
            static function (array $a, array $b): int {
                $dateCmp = strcmp((string) ($b['receipt_date'] ?? ''), (string) ($a['receipt_date'] ?? ''));
                if ($dateCmp !== 0) {
                    return $dateCmp;
                }

                return (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0);
            }
        );
        $this->render('organization', 'receipts/member.php', [
            'pageTitle' => page_title(t('nav.receipts')),
            'navActive' => 'receipts',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => $orgId,
            'receipts' => $receipts,
        ]);
    }

    public function receiptsIndex(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only organization admins can manage receipts.');
            redirect(base_url() . '/organization/dashboard');
        }
        $fy = trim((string) ($_GET['financial_year'] ?? ''));
        if ($fy === '') {
            $fy = $this->financialYearForDate(date('Y-m-d'));
        }
        $filters = [
            'financial_year' => $fy,
            'recipient_user_id' => (int) ($_GET['recipient_user_id'] ?? 0),
            'list_due_definition_id' => (int) ($_GET['list_due_definition_id'] ?? 0),
            'q' => trim((string) ($_GET['q'] ?? '')),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
        ];
        $receiptModel = new Receipt();
        $receipts = $receiptModel->listByOrganization($orgId, $filters);
        $fys = $receiptModel->listFinancialYears($orgId);
        if (!in_array($fy, $fys, true)) {
            array_unshift($fys, $fy);
        }
        $familyModel = new Family();
        $heads = $familyModel->listByOrganization($orgId);
        foreach ($heads as &$headRow) {
            $headRow['member_count'] = $familyModel->householdMemberCount((int) ($headRow['id'] ?? 0));
        }
        unset($headRow);
        $dueModel = new Due();
        $dueDefinitions = $dueModel->listDefinitions($orgId, $fy);
        $eventOccasionDefinitions = $dueModel->listEventOccasionForOrganization($orgId);
        $selectedDueId = (int) ($_GET['due_definition_id'] ?? 0);
        if ($selectedDueId < 1 && $dueDefinitions !== []) {
            $selectedDueId = (int) ($dueDefinitions[0]['id'] ?? 0);
        }
        $selectedDueDefinition = null;
        $dueTrackerSummary = null;
        $dueStatuses = [];
        $trackerFilter = trim((string) ($_GET['tracker_filter'] ?? ''));
        if (!in_array($trackerFilter, ['all', 'pending', 'paid'], true)) {
            $trackerFilter = '';
        }
        $isEventDue = false;
        if ($selectedDueId > 0) {
            $selectedDueDefinition = $dueModel->findDefinitionByIdInOrganization($selectedDueId, $orgId);
            $isEventDue = $selectedDueDefinition !== null && $dueModel->isEventDefinition($selectedDueDefinition);
            if ($trackerFilter === '' && $selectedDueDefinition !== null) {
                $trackerFilter = $dueModel->isCompulsoryDefinition($selectedDueDefinition) ? 'pending' : 'paid';
            }
            if ($trackerFilter === '') {
                $trackerFilter = 'pending';
            }
            if ($selectedDueDefinition !== null && $dueModel->isCompulsoryDefinition($selectedDueDefinition)) {
                $dueModel->syncCompulsoryChargesForDefinition($orgId, $selectedDueId);
            }
            $dueStatuses = $dueModel->listChargeStatus($orgId, $selectedDueId);
            $dueTrackerSummary = $dueModel->trackerSummary($orgId, $selectedDueId);
            if ($trackerFilter === 'pending') {
                $dueStatuses = array_values(array_filter(
                    $dueStatuses,
                    static fn (array $row): bool => ($row['status'] ?? '') !== 'paid'
                ));
            } elseif ($trackerFilter === 'paid') {
                $dueStatuses = array_values(array_filter(
                    $dueStatuses,
                    static fn (array $row): bool => ($row['status'] ?? '') === 'paid'
                ));
            }
        }
        if ($trackerFilter === '') {
            $trackerFilter = 'pending';
        }
        $defaultDate = date('Y-m-d');
        $isPerMemberDue = $selectedDueDefinition !== null && $dueModel->isPerMemberDefinition($selectedDueDefinition);
        $perMemberRate = $isPerMemberDue && $selectedDueDefinition !== null
            ? (float) ($selectedDueDefinition['amount'] ?? 0)
            : 0.0;
        $this->render('organization', 'receipts/index.php', [
            'pageTitle' => page_title('Receipts'),
            'navActive' => 'receipts',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => $orgId,
            'heads' => $heads,
            'receipts' => $receipts,
            'financialYears' => $fys,
            'selectedFinancialYear' => $fy,
            'defaultReceiptDate' => $defaultDate,
            'receiptFilters' => $filters,
            'eventOccasionDefinitions' => $eventOccasionDefinitions,
            'dueDefinitions' => $dueDefinitions,
            'selectedDueDefinitionId' => $selectedDueId,
            'dueStatuses' => $dueStatuses,
            'selectedDueDefinition' => $selectedDueDefinition,
            'dueTrackerSummary' => $dueTrackerSummary,
            'trackerFilter' => $trackerFilter,
            'isPerMemberDue' => $isPerMemberDue,
            'perMemberRate' => $perMemberRate,
            'isEventDue' => $isEventDue,
        ]);
    }

    public function passesIndex(Request $request): void
    {
        redirect(base_url() . '/organization/events');
    }

    public function calendarIndex(Request $request): void
    {
        $month = $this->calendarMonthParam();
        redirect(base_url() . '/organization/dashboard?month=' . urlencode($month));
    }

    public function calendarFeed(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = $bundle['user'];
        $canManageOrg = (new Access())->canManageOrganization($user, $orgId);
        $month = $this->calendarMonthParam();
        $items = $this->buildCalendarItems($orgId, (int) $user['id'], $month, $canManageOrg);
        $panchang = $this->buildPanchangMapForMonth($month);
        json_response([
            'ok' => true,
            'month' => $month,
            'items' => $items,
            'todayItems' => $this->buildTodayCalendarItems($orgId, (int) $user['id'], $canManageOrg),
            'panchang' => $panchang,
            'todayPanchang' => $this->buildPanchangForDate(date('Y-m-d')),
        ]);
    }

    public function calendarDaysIndex(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $this->requireOrgAdmin($bundle['user'], $orgId);
        $days = (new OrgCalendarDay())->listForOrganization($orgId);
        $editId = (int) ($_GET['edit'] ?? 0);
        $this->renderOrgCalendarDaysPage($bundle, $days, $editId > 0 ? $editId : null, null, null);
    }

    public function calendarDaysNew(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $this->requireOrgAdmin($bundle['user'], $orgId);
        redirect(base_url() . '/organization/calendar-days');
    }

    public function calendarDaysStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $this->requireOrgAdmin($bundle['user'], $orgId);
        $this->storeOrUpdateOrgCalendarDay($orgId, $bundle, null);
    }

    public function calendarDaysEdit(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $this->requireOrgAdmin($bundle['user'], $orgId);
        $id = (int) ($_GET['id'] ?? 0);
        redirect(base_url() . '/organization/calendar-days' . ($id > 0 ? '?edit=' . $id : ''));
    }

    public function calendarDaysUpdate(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $this->requireOrgAdmin($bundle['user'], $orgId);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            flash_set('error', t('calendar_days.org.invalid'));
            redirect(base_url() . '/organization/calendar-days');
        }
        $this->storeOrUpdateOrgCalendarDay($orgId, $bundle, $id);
    }

    public function calendarDaysDelete(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $this->requireOrgAdmin($bundle['user'], $orgId);
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            flash_set('error', t('calendar_days.org.invalid'));
            redirect(base_url() . '/organization/calendar-days');
        }
        $model = new OrgCalendarDay();
        if ($model->findByIdInOrganization($id, $orgId) === null) {
            flash_set('error', t('calendar_days.org.not_found'));
            redirect(base_url() . '/organization/calendar-days');
        }
        $model->delete($id, $orgId);
        flash_set('ok', t('calendar_days.org.deleted'));
        redirect(base_url() . '/organization/calendar-days');
    }

    public function noticesIndex(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $this->requireOrgAdmin($bundle['user'], $orgId);

        $noticeModel = new OrgNotice();
        $this->render('organization', 'notices/index.php', [
            'pageTitle' => t('notices.title'),
            'navActive' => 'notices',
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'user' => $bundle['user'],
        ], [
            'notices' => $noticeModel->listForOrganization($orgId, 200, true),
            'deactivatedNotices' => $noticeModel->listForOrganization($orgId, 200, false),
        ]);
    }

    public function noticesStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $this->requireOrgAdmin($bundle['user'], $orgId);

        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $isPinned = isset($_POST['is_pinned']) && (string) $_POST['is_pinned'] === '1';

        if ($title === '') {
            flash_set('error', t('notices.title_required'));
            redirect(base_url() . '/organization/notices');
        }
        if (strlen($title) > 255) {
            flash_set('error', t('notices.title_too_long'));
            redirect(base_url() . '/organization/notices');
        }
        if (strlen($description) > 2000) {
            flash_set('error', t('notices.description_too_long'));
            redirect(base_url() . '/organization/notices');
        }

        $upload = store_org_notice_upload($orgId, $_FILES['notice_file'] ?? null);
        if (empty($upload['ok'])) {
            flash_set('error', (string) ($upload['error'] ?? t('notices.upload_failed')));
            redirect(base_url() . '/organization/notices');
        }

        $userId = (int) ($bundle['user']['id'] ?? 0);
        $noticeId = (new OrgNotice())->create([
            'organization_id' => $orgId,
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'file_path' => (string) $upload['path'],
            'original_filename' => (string) $upload['original_filename'],
            'mime_type' => (string) $upload['mime_type'],
            'file_size_bytes' => (int) $upload['file_size_bytes'],
            'is_pinned' => $isPinned ? 1 : 0,
            'uploaded_by_user_id' => $userId > 0 ? $userId : null,
        ]);
        $this->notifyMembersOnNoticePublished($orgId, $noticeId, $title, $userId > 0 ? $userId : null);

        flash_set('ok', t('notices.created'));
        redirect(base_url() . '/organization/notices');
    }

    private function notifyMembersOnNoticePublished(
        int $organizationId,
        int $noticeId,
        string $noticeTitle,
        ?int $actorUserId
    ): void {
        try {
            $recipients = (new Organization())->listMemberRecipientRows($organizationId);
            if ($recipients === []) {
                return;
            }
            $notification = new Notification();
            foreach ($recipients as $row) {
                $userId = (int) ($row['id'] ?? 0);
                if ($userId <= 0 || ($actorUserId !== null && $userId === $actorUserId)) {
                    continue;
                }
                $locale = \user_notification_locale((string) ($row['preferred_locale'] ?? ''));
                $title = \t_for_locale('notices.notification_title', $locale);
                $message = \t_for_locale('notices.notification_message', $locale, [
                    'title' => $noticeTitle,
                ]);
                $notification->createForUser(
                    $userId,
                    'org_notice',
                    $noticeId,
                    $title,
                    $message,
                    true,
                    ['url' => base_url() . '/organization/dashboard']
                );
            }
        } catch (\Throwable $e) {
            // Notice creation should not fail if notification delivery fails.
        }
    }

    public function noticesDelete(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $this->requireOrgAdmin($bundle['user'], $orgId);

        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            flash_set('error', t('notices.not_found'));
            redirect(base_url() . '/organization/notices');
        }

        $path = (new OrgNotice())->delete($id, $orgId);
        if ($path === null) {
            flash_set('error', t('notices.not_found'));
            redirect(base_url() . '/organization/notices');
        }

        delete_org_notice_file($path);
        flash_set('ok', t('notices.deleted'));
        redirect(base_url() . '/organization/notices');
    }

    public function noticesPin(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $this->requireOrgAdmin($bundle['user'], $orgId);

        $id = (int) ($_POST['id'] ?? 0);
        $pinned = isset($_POST['is_pinned']) && (string) $_POST['is_pinned'] === '1';
        if ($id < 1) {
            flash_set('error', t('notices.not_found'));
            redirect(base_url() . '/organization/notices');
        }

        $notice = (new OrgNotice())->findByIdInOrganization($id, $orgId);
        if ($notice === null) {
            flash_set('error', t('notices.not_found'));
            redirect(base_url() . '/organization/notices');
        }

        (new OrgNotice())->setPinned($id, $orgId, $pinned);
        flash_set('ok', $pinned ? t('notices.pinned') : t('notices.unpinned'));
        redirect(base_url() . '/organization/notices');
    }

    public function noticesSetActive(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $this->requireOrgAdmin($bundle['user'], $orgId);

        $id = (int) ($_POST['id'] ?? 0);
        $active = isset($_POST['is_active']) && (string) $_POST['is_active'] === '1';
        if ($id < 1) {
            flash_set('error', t('notices.not_found'));
            redirect(base_url() . '/organization/notices');
        }

        $notice = (new OrgNotice())->findByIdInOrganization($id, $orgId);
        if ($notice === null) {
            flash_set('error', t('notices.not_found'));
            redirect(base_url() . '/organization/notices');
        }

        (new OrgNotice())->setActive($id, $orgId, $active);
        flash_set('ok', $active ? t('notices.reactivated') : t('notices.deactivated'));
        redirect(base_url() . '/organization/notices');
    }

    public function noticesFile(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);

        $id = (int) ($_GET['id'] ?? 0);
        if ($id < 1) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $notice = (new OrgNotice())->findByIdInOrganization($id, $orgId);
        if ($notice === null) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $access = new Access();
        $canManageOrg = $access->canManageOrganization($bundle['user'], $orgId);
        if ((int) ($notice['is_active'] ?? 1) !== 1 && !$canManageOrg) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $relative = ltrim(str_replace('\\', '/', (string) ($notice['file_path'] ?? '')), '/');
        if (!preg_match('#^uploads/org-notices/\d+/[a-zA-Z0-9._-]+$#', $relative)) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $full = BASE_PATH . '/public/' . $relative;
        if (!is_file($full)) {
            http_response_code(404);
            echo 'File missing';
            return;
        }

        $mime = (string) ($notice['mime_type'] ?? 'application/octet-stream');
        $filename = (string) ($notice['original_filename'] ?? 'notice');
        $download = isset($_GET['download']) && (string) $_GET['download'] === '1';
        $inline = !$download && $mime === 'application/pdf';

        if (headers_sent()) {
            return;
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($full));
        header('X-Content-Type-Options: nosniff');
        $disposition = $inline ? 'inline' : 'attachment';
        header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $filename) . '"');
        readfile($full);
        exit;
    }

    public function eventsIndex(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $userId = (int) $bundle['user']['id'];
        $canManageOrg = (new Access())->canManageOrganization($bundle['user'], $orgId);
        $dueModel = new Due();
        $passModel = new EventPass();
        $events = [];
        foreach ($dueModel->listEventsForOrganization($orgId) as $event) {
            $row = ['event' => $event];
            if ($canManageOrg) {
                $eventId = (int) ($event['id'] ?? 0);
                $row['stats'] = $passModel->eventPassStats($orgId, $eventId);
            } else {
                $summary = $passModel->householdSummaryForUserEvent($userId, $orgId, $event);
                $row['pass_count'] = $summary !== null ? (int) ($summary['pass_count'] ?? 0) : 0;
                $row['redeemed_count'] = $summary !== null ? (int) ($summary['redeemed_count'] ?? 0) : 0;
                $row['amount_paid'] = $summary !== null ? (float) ($summary['amount_paid'] ?? 0) : 0.0;
            }
            $events[] = $row;
        }
        $schemeModel = new Scheme();
        $schemeRows = $schemeModel->listByOrganization($orgId);
        $eligibleRows = $schemeModel->listEligibleForUser($orgId, $userId);
        $defaultFy = $this->financialYearForDate(date('Y-m-d'));
        $hasOkFlash = isset($_SESSION['_flash']['ok']);
        $showCreateEvent = $canManageOrg && isset($_GET['create_event']) && !$hasOkFlash;
        $activeEventTab = 'events';
        if (isset($_GET['event_tab']) && (string) $_GET['event_tab'] === 'schemes') {
            $activeEventTab = 'schemes';
        }
        if ($showCreateEvent) {
            $activeEventTab = 'events';
        }
        $this->render('organization', 'events/index.php', [
            'pageTitle' => page_title('Events'),
            'navActive' => 'events',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => $orgId,
            'events' => $events,
            'canManageOrg' => $canManageOrg,
            'schemes' => $schemeRows,
            'eligibleSchemes' => $eligibleRows,
            'defaultFinancialYear' => $defaultFy,
            'defaultEventDate' => date('Y-m-d'),
            'showCreateEvent' => $showCreateEvent,
            'activeEventTab' => $activeEventTab,
        ]);
    }

    public function eventsShow(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $canManageOrg = (new Access())->canManageOrganization($bundle['user'], $orgId);
        $eventId = (int) ($_GET['id'] ?? 0);
        if ($eventId < 1) {
            flash_set('error', 'Invalid event.');
            redirect(base_url() . '/organization/events');
        }
        $event = (new Due())->findEventByIdInOrganization($eventId, $orgId);
        if ($event === null) {
            flash_set('error', 'Event not found.');
            redirect(base_url() . '/organization/events');
        }
        $dueModel = new Due();
        $isPerPerson = $dueModel->isPerPersonDefinition($event);
        $passModel = new EventPass();
        $summary = null;
        $passStats = null;
        $eventPasses = [];
        if ($canManageOrg) {
            $passStats = $passModel->eventPassStats($orgId, $eventId);
            $eventPasses = $passModel->listPassesForEvent($orgId, $eventId);
        } else {
            $summary = $passModel->householdSummaryForUserEvent((int) $bundle['user']['id'], $orgId, $event);
        }
        $this->render('organization', 'events/show.php', [
            'pageTitle' => page_title((string) ($event['title'] ?? 'Event')),
            'navActive' => 'events',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => $orgId,
            'event' => $event,
            'summary' => $summary,
            'isPerPerson' => $isPerPerson,
            'canManageOrg' => $canManageOrg,
            'passStats' => $passStats,
            'eventPasses' => $eventPasses,
        ]);
    }

    public function eventsPassSearch(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        if (!(new Access())->canManageOrganization($bundle['user'], $orgId)) {
            json_response(['ok' => false, 'error' => 'Forbidden'], 403);
        }
        $eventId = (int) ($_GET['event_id'] ?? 0);
        $q = trim((string) ($_GET['q'] ?? ''));
        if ($eventId < 1) {
            json_response(['ok' => false, 'error' => 'Invalid event'], 400);
        }
        if ((new Due())->findEventByIdInOrganization($eventId, $orgId) === null) {
            json_response(['ok' => false, 'error' => 'Event not found'], 404);
        }
        $passModel = new EventPass();
        $matches = $passModel->searchPassesInEvent($orgId, $eventId, $q);
        $out = [];
        foreach ($matches as $row) {
            $code = (string) ($row['pass_code'] ?? '');
            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'pass_code' => $code,
                'code_suffix' => (string) ($row['code_suffix'] ?? $passModel->passCodeSuffix($code, 3)),
                'holder_name' => (string) ($row['holder_name'] ?? ''),
                'head_name' => (string) ($row['head_name'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
            ];
        }
        json_response(['ok' => true, 'matches' => $out]);
    }

    public function eventsRedeemStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $user = $bundle['user'];
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only organization admins can redeem passes.');
            redirect(base_url() . '/organization/events');
        }
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $passId = (int) ($_POST['pass_id'] ?? 0);
        $passCode = trim((string) ($_POST['pass_code'] ?? ''));
        if ($eventId < 1) {
            flash_set('error', 'Invalid event.');
            redirect(base_url() . '/organization/events');
        }
        if ($passId < 1 && $passCode === '') {
            flash_set('error', 'Enter at least 3 characters of the pass code.');
            redirect(base_url() . '/organization/event?id=' . $eventId);
        }
        $event = (new Due())->findEventByIdInOrganization($eventId, $orgId);
        if ($event === null) {
            flash_set('error', 'Event not found.');
            redirect(base_url() . '/organization/events');
        }
        $passModel = new EventPass();
        if ($passId > 0) {
            $result = $passModel->redeemById($orgId, $eventId, $passId);
        } else {
            $result = $passModel->redeemByCode($orgId, $eventId, $passCode);
        }
        if (($result['ok'] ?? false) !== true) {
            flash_set('error', $result['error'] ?? 'Could not redeem pass.');
            redirect(base_url() . '/organization/event?id=' . $eventId);
        }
        $pass = $result['pass'] ?? [];
        $holder = (string) ($pass['holder_name'] ?? '');
        $head = (string) ($pass['head_name'] ?? '');
        $suffix = $passModel->passCodeSuffix((string) ($pass['pass_code'] ?? $passCode), 3);
        flash_set(
            'ok',
            'Redeemed ' . $suffix
            . ' — ' . (string) ($pass['pass_code'] ?? '')
            . ($holder !== '' ? ' · ' . $holder : '')
            . ($head !== '' && $head !== $holder ? ' (head: ' . $head . ')' : '') . '.'
        );
        redirect(base_url() . '/organization/event?id=' . $eventId);
    }

    public function eventsUnredeemStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $user = $bundle['user'];
        if (!(new Access())->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only organization admins can undo pass redemption.');
            redirect(base_url() . '/organization/events');
        }
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $passId = (int) ($_POST['pass_id'] ?? 0);
        if ($eventId < 1 || $passId < 1) {
            flash_set('error', 'Invalid pass or event.');
            redirect(base_url() . '/organization/events');
        }
        if ((new Due())->findEventByIdInOrganization($eventId, $orgId) === null) {
            flash_set('error', 'Event not found.');
            redirect(base_url() . '/organization/events');
        }
        $passModel = new EventPass();
        $result = $passModel->unredeemById($orgId, $eventId, $passId);
        if (($result['ok'] ?? false) !== true) {
            flash_set('error', $result['error'] ?? 'Could not restore pass.');
            redirect(base_url() . '/organization/event?id=' . $eventId);
        }
        $pass = $result['pass'] ?? [];
        $holder = (string) ($pass['holder_name'] ?? '');
        $suffix = $passModel->passCodeSuffix((string) ($pass['pass_code'] ?? ''), 3);
        flash_set(
            'ok',
            'Pass ' . $suffix
            . ' marked active again'
            . ($holder !== '' ? ' · ' . $holder : '')
            . '.'
        );
        redirect(base_url() . '/organization/event?id=' . $eventId);
    }

    public function receiptsStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only organization admins can create receipts.');
            redirect(base_url() . '/organization/dashboard');
        }
        $familyId = (int) ($_POST['family_id'] ?? 0);
        $recipientUserId = (int) ($_POST['recipient_user_id'] ?? 0);
        $dueDefinitionId = (int) ($_POST['due_definition_id'] ?? 0);
        $purpose = trim((string) ($_POST['purpose'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $amountRaw = trim((string) ($_POST['amount'] ?? ''));
        $receiptDate = trim((string) ($_POST['receipt_date'] ?? ''));
        if ($familyId < 1 || $recipientUserId < 1 || $amountRaw === '' || $receiptDate === '') {
            flash_set('error', 'Recipient, amount, and date are required.');
            redirect(base_url() . '/organization/receipts');
        }
        if (strtotime($receiptDate) === false) {
            flash_set('error', 'Invalid receipt date.');
            redirect(base_url() . '/organization/receipts');
        }
        if (!is_numeric($amountRaw) || (float) $amountRaw <= 0) {
            flash_set('error', 'Amount must be a positive number.');
            redirect(base_url() . '/organization/receipts');
        }
        $family = (new Family())->findById($familyId);
        if ($family === null || (int) ($family['head_user_id'] ?? 0) !== $recipientUserId) {
            flash_set('error', 'Please choose a valid family head.');
            redirect(base_url() . '/organization/receipts');
        }
        $dueModel = new Due();
        if ($dueDefinitionId > 0) {
            $dueDef = $dueModel->findDefinitionByIdInOrganization($dueDefinitionId, $orgId);
            if ($dueDef === null) {
                flash_set('error', 'Selected due is invalid.');
                redirect(base_url() . '/organization/receipts');
            }
            $purpose = trim((string) ($dueDef['title'] ?? ''));
            if ($dueModel->isEventDefinition($dueDef)) {
                $rate = (float) ($dueDef['amount'] ?? 0);
                if ($rate <= 0) {
                    flash_set('error', 'Event rate is not configured.');
                    redirect(base_url() . '/organization/receipts');
                }
                $passCount = (int) ($_POST['pass_count'] ?? 0);
                if ($passCount < 1) {
                    flash_set('error', 'Number of passes must be at least 1.');
                    redirect(base_url() . '/organization/receipts');
                }
                if (!$dueModel->isPerPersonDefinition($dueDef)) {
                    $passCount = 1;
                }
                $amountRaw = (string) round($rate * $passCount, 2);
            }
        }
        if ($purpose === '') {
            flash_set('error', 'Select due or enter custom purpose.');
            redirect(base_url() . '/organization/receipts');
        }
        $financialYear = $this->financialYearForDate($receiptDate);
        $receiptId = (new Receipt())->create(
            $orgId,
            $familyId,
            $recipientUserId,
            $dueDefinitionId > 0 ? $dueDefinitionId : null,
            $purpose,
            $description !== '' ? $description : null,
            (float) $amountRaw,
            $receiptDate,
            $financialYear,
            isset($user['id']) ? (int) $user['id'] : null
        );
        $passCodes = [];
        if ($dueDefinitionId > 0) {
            $dueModel->applyReceiptPaymentByDefinition(
                $orgId,
                $recipientUserId,
                $dueDefinitionId,
                (float) $amountRaw,
                $receiptId,
                $receiptDate,
                $familyId
            );
            $dueDef = $dueModel->findDefinitionByIdInOrganization($dueDefinitionId, $orgId);
            if ($dueDef !== null && $dueModel->isEventDefinition($dueDef)) {
                $passCodes = (new EventPass())->issueForPaidEventReceipt(
                    $orgId,
                    $dueDef,
                    $familyId,
                    $recipientUserId,
                    $receiptId
                );
            }
        } else {
            $dueModel->applyReceiptPayment(
                $orgId,
                $recipientUserId,
                $purpose,
                (float) $amountRaw,
                $financialYear,
                $receiptId,
                $receiptDate
            );
        }
        $okMsg = 'Receipt created.';
        if ($passCodes !== []) {
            $n = count($passCodes);
            $okMsg .= ' ' . $n . ' event pass' . ($n === 1 ? '' : 'es') . ' active for this family';
            if (isset($dueDef) && $dueDef !== null && (new Due())->isPerPersonDefinition($dueDef)) {
                $rate = (float) ($dueDef['amount'] ?? 0);
                if ($rate > 0) {
                    $okMsg .= ' (' . $n . ' × ' . number_format($rate, 2) . ' per person)';
                }
            }
            $okMsg .= '.';
        }
        flash_set('ok', $okMsg);
        redirect(base_url() . '/organization/receipts?financial_year=' . urlencode($financialYear));
    }

    public function donationsIndex(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only organization admins can manage donations.');
            redirect(base_url() . '/organization/dashboard');
        }
        $fy = trim((string) ($_GET['financial_year'] ?? ''));
        if ($fy === '') {
            $fy = $this->financialYearForDate(date('Y-m-d'));
        }
        $donationModel = new Donation();
        $categoryModel = new DonationCategory();
        $categories = $categoryModel->listActive($orgId);
        $fys = $donationModel->listFinancialYears($orgId);
        if (!in_array($fy, $fys, true)) {
            array_unshift($fys, $fy);
        }
        $filters = [
            'financial_year' => $fy,
            'category_id' => (int) ($_GET['category_id'] ?? 0),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'q' => trim((string) ($_GET['q'] ?? '')),
        ];
        $commitments = $donationModel->listCommitments($orgId, $filters);
        $dashboardRows = $donationModel->dashboardByCategory($orgId, $fy);
        $dashboardTotals = ['pledged' => 0.0, 'collected' => 0.0, 'balance' => 0.0];
        foreach ($dashboardRows as $dr) {
            $dashboardTotals['pledged'] += (float) ($dr['pledged'] ?? 0);
            $dashboardTotals['collected'] += (float) ($dr['collected'] ?? 0);
            $dashboardTotals['balance'] += (float) ($dr['balance'] ?? 0);
        }
        $familyModel = new Family();
        $heads = $familyModel->listByOrganization($orgId);
        $activeTab = 'donation';
        if (isset($_GET['donation_tab'])) {
            $tab = (string) $_GET['donation_tab'];
            if (in_array($tab, ['dashboard', 'categories'], true)) {
                $activeTab = $tab;
            }
        }
        $allCategories = $categoryModel->listForManagement($orgId);
        $this->render('organization', 'donations/index.php', [
            'pageTitle' => page_title('Donations'),
            'navActive' => 'donations',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => $orgId,
            'categories' => $categories,
            'allCategories' => $allCategories,
            'commitments' => $commitments,
            'dashboardRows' => $dashboardRows,
            'dashboardTotals' => $dashboardTotals,
            'financialYears' => $fys,
            'selectedFinancialYear' => $fy,
            'donationFilters' => $filters,
            'heads' => $heads,
            'activeDonationTab' => $activeTab,
            'defaultDate' => date('Y-m-d'),
        ]);
    }

    public function donationsCommitmentStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        if (!(new Access())->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only organization admins can record donations.');
            redirect(base_url() . '/organization/dashboard');
        }
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $donorType = strtolower(trim((string) ($_POST['donor_type'] ?? 'member')));
        $amountRaw = trim((string) ($_POST['committed_amount'] ?? ''));
        $committedDate = trim((string) ($_POST['committed_date'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $directPayment = !empty($_POST['direct_payment']);
        if ($categoryId < 1 || $amountRaw === '' || $committedDate === '') {
            flash_set('error', 'Category, amount, and date are required.');
            redirect(base_url() . '/organization/donations');
        }
        if (strtotime($committedDate) === false) {
            flash_set('error', 'Invalid date.');
            redirect(base_url() . '/organization/donations');
        }
        if (!is_numeric($amountRaw) || (float) $amountRaw <= 0) {
            flash_set('error', 'Amount must be a positive number.');
            redirect(base_url() . '/organization/donations');
        }
        $category = (new DonationCategory())->findByIdInOrganization($categoryId, $orgId);
        if ($category === null) {
            flash_set('error', 'Invalid category.');
            redirect(base_url() . '/organization/donations');
        }
        $donorName = '';
        $donorPhone = null;
        $userId = null;
        $familyId = null;
        if ($donorType === 'guest') {
            $donorName = trim((string) ($_POST['donor_name'] ?? ''));
            $phoneRaw = trim((string) ($_POST['donor_phone'] ?? ''));
            if ($donorName === '') {
                flash_set('error', 'Guest name is required.');
                redirect(base_url() . '/organization/donations');
            }
            if ($phoneRaw !== '') {
                $donorPhone = normalize_phone($phoneRaw);
            }
        } else {
            $donorType = 'member';
            $familyId = (int) ($_POST['family_id'] ?? 0);
            $userId = (int) ($_POST['user_id'] ?? 0);
            if ($familyId < 1 || $userId < 1) {
                flash_set('error', 'Please choose a member.');
                redirect(base_url() . '/organization/donations');
            }
            $family = (new Family())->findById($familyId);
            if ($family === null || (int) ($family['organization_id'] ?? 0) !== $orgId) {
                flash_set('error', 'Invalid family.');
                redirect(base_url() . '/organization/donations');
            }
            $headUser = (new User())->findById($userId);
            $donorName = trim((string) ($headUser['name'] ?? ''));
            if ($donorName === '') {
                flash_set('error', 'Member not found.');
                redirect(base_url() . '/organization/donations');
            }
        }
        $createdBy = isset($user['id']) ? (int) $user['id'] : null;
        $donationModel = new Donation();
        $commitmentId = $donationModel->createCommitment(
            $orgId,
            $categoryId,
            $donorType,
            $userId,
            $familyId,
            $donorName,
            $donorPhone,
            (float) $amountRaw,
            $committedDate,
            $notes !== '' ? $notes : null,
            $createdBy
        );
        if ($directPayment) {
            $paymentDate = trim((string) ($_POST['payment_date'] ?? $committedDate));
            $paymentMode = strtolower(trim((string) ($_POST['payment_mode'] ?? '')));
            if (!Donation::isValidPaymentMode($paymentMode)) {
                flash_set('error', 'Select a valid payment mode for direct payment.');
                redirect(base_url() . '/organization/donations');
            }
            $referenceNo = trim((string) ($_POST['reference_no'] ?? ''));
            $bankName = trim((string) ($_POST['bank_name'] ?? ''));
            $chequeDate = trim((string) ($_POST['cheque_date'] ?? ''));
            $paymentId = $donationModel->recordPayment(
                $orgId,
                $commitmentId,
                (float) $amountRaw,
                $paymentDate,
                $paymentMode,
                $referenceNo !== '' ? $referenceNo : null,
                $bankName !== '' ? $bankName : null,
                $chequeDate !== '' ? $chequeDate : null,
                null,
                $createdBy
            );
            if ($paymentId < 1) {
                flash_set('error', 'Commitment saved but payment could not be recorded.');
                redirect(base_url() . '/organization/donations');
            }
            flash_set('ok', 'Donation recorded with payment.');
        } else {
            flash_set('ok', 'Commitment recorded.');
        }
        $fy = $this->financialYearForDate($committedDate);
        redirect(base_url() . '/organization/donations?financial_year=' . urlencode($fy));
    }

    public function donationsPaymentStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        if (!(new Access())->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only organization admins can record payments.');
            redirect(base_url() . '/organization/dashboard');
        }
        $parentId = (int) ($_POST['parent_id'] ?? 0);
        $amountRaw = trim((string) ($_POST['paid_amount'] ?? ''));
        $paymentDate = trim((string) ($_POST['payment_date'] ?? ''));
        $paymentMode = strtolower(trim((string) ($_POST['payment_mode'] ?? '')));
        if ($parentId < 1 || $amountRaw === '' || $paymentDate === '') {
            flash_set('error', 'Commitment, amount, and payment date are required.');
            redirect(base_url() . '/organization/donations');
        }
        if (!Donation::isValidPaymentMode($paymentMode)) {
            flash_set('error', 'Select a valid payment mode.');
            redirect(base_url() . '/organization/donations');
        }
        if (strtotime($paymentDate) === false) {
            flash_set('error', 'Invalid payment date.');
            redirect(base_url() . '/organization/donations');
        }
        if (!is_numeric($amountRaw) || (float) $amountRaw <= 0) {
            flash_set('error', 'Amount must be a positive number.');
            redirect(base_url() . '/organization/donations');
        }
        $donationModel = new Donation();
        $commitment = $donationModel->findCommitmentById($orgId, $parentId);
        if ($commitment === null) {
            flash_set('error', 'Commitment not found.');
            redirect(base_url() . '/organization/donations');
        }
        $status = (string) ($commitment['status'] ?? '');
        if ($status === 'fulfilled' || $status === 'cancelled') {
            flash_set('error', 'This commitment cannot accept more payments.');
            redirect(base_url() . '/organization/donations');
        }
        $balance = (float) ($commitment['balance'] ?? 0);
        if ((float) $amountRaw > $balance + 0.001) {
            flash_set('error', 'Payment exceeds remaining balance (' . number_format($balance, 2) . ').');
            redirect(base_url() . '/organization/donations');
        }
        $referenceNo = trim((string) ($_POST['reference_no'] ?? ''));
        $bankName = trim((string) ($_POST['bank_name'] ?? ''));
        $chequeDate = trim((string) ($_POST['cheque_date'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $createdBy = isset($user['id']) ? (int) $user['id'] : null;
        $paymentId = $donationModel->recordPayment(
            $orgId,
            $parentId,
            (float) $amountRaw,
            $paymentDate,
            $paymentMode,
            $referenceNo !== '' ? $referenceNo : null,
            $bankName !== '' ? $bankName : null,
            $chequeDate !== '' ? $chequeDate : null,
            $notes !== '' ? $notes : null,
            $createdBy
        );
        if ($paymentId < 1) {
            flash_set('error', 'Could not record payment.');
            redirect(base_url() . '/organization/donations');
        }
        flash_set('ok', 'Payment recorded.');
        $fy = $this->financialYearForDate($paymentDate);
        redirect(base_url() . '/organization/donations?financial_year=' . urlencode($fy));
    }

    public function donationsCategoryStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        if (!(new Access())->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only organization admins can manage donation categories.');
            redirect(base_url() . '/organization/dashboard');
        }
        $nameGu = trim((string) ($_POST['name_gu'] ?? ''));
        if ($nameGu === '') {
            flash_set('error', t('donations.categories_name_required'));
            redirect(base_url() . '/organization/donations?donation_tab=categories');
        }
        try {
            (new DonationCategory())->createCustom($orgId, $nameGu);
            flash_set('ok', t('donations.categories_added'));
        } catch (\InvalidArgumentException $e) {
            $msg = $e->getMessage();
            if ($msg === 'This category already exists.') {
                flash_set('error', t('donations.categories_duplicate'));
            } elseif ($msg === 'Category name is too long.') {
                flash_set('error', t('donations.categories_name_too_long'));
            } else {
                flash_set('error', t('donations.categories_name_required'));
            }
        }
        redirect(base_url() . '/organization/donations?donation_tab=categories');
    }

    public function donationsCategoryToggle(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        if (!(new Access())->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only organization admins can manage donation categories.');
            redirect(base_url() . '/organization/dashboard');
        }
        $id = (int) ($_POST['id'] ?? 0);
        $activate = !empty($_POST['activate']);
        if ($id < 1) {
            flash_set('error', t('donations.categories_invalid'));
            redirect(base_url() . '/organization/donations?donation_tab=categories');
        }
        $model = new DonationCategory();
        $row = $model->findByIdInOrganization($id, $orgId);
        if ($row === null) {
            flash_set('error', t('donations.categories_not_found'));
            redirect(base_url() . '/organization/donations?donation_tab=categories');
        }
        if (!$model->setActive($id, $orgId, $activate)) {
            flash_set('error', t('donations.categories_update_failed'));
            redirect(base_url() . '/organization/donations?donation_tab=categories');
        }
        flash_set('ok', $activate ? t('donations.categories_activated') : t('donations.categories_deactivated'));
        redirect(base_url() . '/organization/donations?donation_tab=categories');
    }

    public function duesCreateStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only organization admins can create dues.');
            redirect(base_url() . '/organization/dashboard');
        }
        $title = trim((string) ($_POST['title'] ?? ''));
        $dueType = trim((string) ($_POST['due_type'] ?? 'other'));
        $amountRaw = trim((string) ($_POST['amount'] ?? ''));
        $financialYear = trim((string) ($_POST['financial_year'] ?? ''));
        $isCompulsory = !empty($_POST['is_compulsory']);
        $chargeBasis = trim((string) ($_POST['charge_basis'] ?? 'per_family'));
        $returnToEvents = trim((string) ($_POST['return_to'] ?? '')) === 'events';
        $eventsListUrl = base_url() . '/organization/events?event_tab=events';
        $eventsCreateUrl = $eventsListUrl . '&create_event=1';
        if ($title === '' || $amountRaw === '' || $financialYear === '') {
            flash_set('error', 'Title, amount, and financial year are required.');
            redirect($returnToEvents ? $eventsCreateUrl : base_url() . '/organization/receipts');
        }
        if (!is_numeric($amountRaw) || (float) $amountRaw <= 0) {
            flash_set('error', 'Amount must be a positive number.');
            redirect($returnToEvents ? $eventsCreateUrl : base_url() . '/organization/receipts');
        }
        $eventDate = trim((string) ($_POST['event_date'] ?? ''));
        if (!in_array($dueType, ['event', 'occasion'], true)) {
            $eventDate = '';
        }
        $definitionId = (new Due())->createDefinitionAndAssignHeads(
            $orgId,
            $title,
            $dueType,
            (float) $amountRaw,
            $financialYear,
            isset($user['id']) ? (int) $user['id'] : null,
            $isCompulsory,
            $chargeBasis,
            $eventDate !== '' ? $eventDate : null
        );
        $perPerson = $chargeBasis === 'per_person' || $dueType === 'membership';
        if ($dueType === 'event') {
            $basisLabel = $perPerson ? 'per person' : 'per family';
            $msg = $isCompulsory
                ? "Event created (compulsory, {$basisLabel}). Passes are issued when payment is complete."
                : "Event created (optional, {$basisLabel}). Passes are issued when a full receipt is recorded.";
        } elseif ($dueType === 'membership' || ($isCompulsory && $perPerson)) {
            $msg = 'Due created — amount is per login member (rate × member count). Open the tracker to see who is still pending.';
        } elseif ($isCompulsory) {
            $msg = 'Compulsory due created (flat per family).';
        } else {
            $msg = 'Optional due created — only families who pay will appear in the tracker.';
        }
        flash_set('ok', $msg);
        if ($returnToEvents) {
            redirect($eventsListUrl);
        }
        redirect(base_url() . '/organization/receipts?financial_year=' . urlencode($financialYear)
            . '&due_definition_id=' . $definitionId . '&tracker_filter=pending&receipt_tab=dues');
    }

    public function receiptPrint(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        $access = new Access();
        $receiptId = (int) ($_GET['id'] ?? 0);
        if ($receiptId < 1) {
            flash_set('error', 'Invalid receipt.');
            redirect(base_url() . '/organization/dashboard');
        }
        $receipt = (new Receipt())->findByIdInOrganization($receiptId, $orgId);
        if ($receipt === null) {
            flash_set('error', 'Receipt not found.');
            redirect(base_url() . '/organization/dashboard');
        }
        if (!$access->canViewReceipt($user, $receipt, $orgId)) {
            flash_set('error', 'You cannot view this receipt.');
            redirect(base_url() . '/organization/dashboard');
        }

        ob_start();
        require BASE_PATH . '/app/Views/organization/receipts/print.php';
        $html = (string) ob_get_clean();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'landscape');
        $dompdf->render();
        $dompdf->stream(receipt_pdf_filename($receipt), ['Attachment' => false]);
        exit;
    }

    public function schemesNew(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $access = new Access();
        if (!$access->canManageOrganization($bundle['user'], $orgId)) {
            flash_set('error', 'Only an org admin can create schemes.');
            redirect(base_url() . '/organization/events');
        }
        $this->render('organization', 'schemes/new.php', [
            'pageTitle' => page_title('New scheme'),
            'navActive' => 'events',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => $orgId,
        ]);
    }

    public function schemesCreateStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only an org admin can create schemes.');
            redirect(base_url() . '/organization/events');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $scope = strtolower(trim((string) ($_POST['benefit_scope'] ?? '')));
        $type = trim((string) ($_POST['benefit_type'] ?? ''));
        $valueRaw = trim((string) ($_POST['benefit_value'] ?? ''));
        $startsAtRaw = trim((string) ($_POST['starts_at'] ?? ''));
        $endsAtRaw = trim((string) ($_POST['ends_at'] ?? ''));
        $benefitValue = $valueRaw !== '' ? $valueRaw : null;
        $startsAt = $startsAtRaw !== '' ? $startsAtRaw : null;
        $endsAt = $endsAtRaw !== '' ? $endsAtRaw : null;

        if ($name === '' || $type === '' || !in_array($scope, ['family', 'member'], true)) {
            flash_set('error', 'Name, benefit type, and a valid scope are required.');
            redirect(base_url() . '/organization/schemes/new');
        }
        if ($startsAt !== null && $endsAt !== null && strtotime($startsAt) !== false && strtotime($endsAt) !== false && strtotime($startsAt) > strtotime($endsAt)) {
            flash_set('error', 'Start date cannot be after end date.');
            redirect(base_url() . '/organization/schemes/new');
        }

        $schemes = new Scheme();
        $schemeId = $schemes->create(
            $orgId,
            $name,
            $description,
            $scope,
            $type,
            $benefitValue,
            $startsAt,
            $endsAt,
            (int) $user['id']
        );
        $assigned = $schemes->assignForScope($schemeId, $orgId, $scope);
        flash_set('ok', 'Scheme created. Eligible ' . ($scope === 'family' ? 'families' : 'members') . ' assigned: ' . $assigned . '.');
        redirect(base_url() . '/organization/events?event_tab=schemes');
    }

    public function schemesEdit(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only an org admin can edit schemes.');
            redirect(base_url() . '/organization/events');
        }
        $schemeId = (int) ($_GET['id'] ?? 0);
        if ($schemeId < 1) {
            flash_set('error', 'Invalid scheme.');
            redirect(base_url() . '/organization/events');
        }
        $scheme = (new Scheme())->findByIdInOrganization($schemeId, $orgId);
        if ($scheme === null) {
            flash_set('error', 'Scheme not found.');
            redirect(base_url() . '/organization/events');
        }
        $this->render('organization', 'schemes/edit.php', [
            'pageTitle' => page_title('Edit scheme'),
            'navActive' => 'events',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => $orgId,
            'scheme' => $scheme,
        ]);
    }

    public function schemesUpdateStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only an org admin can update schemes.');
            redirect(base_url() . '/organization/events');
        }
        $schemeId = (int) ($_POST['scheme_id'] ?? 0);
        if ($schemeId < 1) {
            flash_set('error', 'Invalid scheme.');
            redirect(base_url() . '/organization/events');
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $type = trim((string) ($_POST['benefit_type'] ?? ''));
        $valueRaw = trim((string) ($_POST['benefit_value'] ?? ''));
        $startsAtRaw = trim((string) ($_POST['starts_at'] ?? ''));
        $endsAtRaw = trim((string) ($_POST['ends_at'] ?? ''));
        $isActive = isset($_POST['is_active']) && (string) $_POST['is_active'] === '1' ? 1 : 0;
        $benefitValue = $valueRaw !== '' ? $valueRaw : null;
        $startsAt = $startsAtRaw !== '' ? $startsAtRaw : null;
        $endsAt = $endsAtRaw !== '' ? $endsAtRaw : null;
        if ($name === '' || $type === '') {
            flash_set('error', 'Name and benefit type are required.');
            redirect(base_url() . '/organization/schemes/edit?id=' . $schemeId);
        }
        if ($startsAt !== null && $endsAt !== null && strtotime($startsAt) !== false && strtotime($endsAt) !== false && strtotime($startsAt) > strtotime($endsAt)) {
            flash_set('error', 'Start date cannot be after end date.');
            redirect(base_url() . '/organization/schemes/edit?id=' . $schemeId);
        }
        $schemes = new Scheme();
        if ($schemes->findByIdInOrganization($schemeId, $orgId) === null) {
            flash_set('error', 'Scheme not found.');
            redirect(base_url() . '/organization/events');
        }
        $schemes->updateInOrganization($schemeId, $orgId, $name, $description, $type, $benefitValue, $startsAt, $endsAt, $isActive);
        flash_set('ok', 'Scheme updated.');
        redirect(base_url() . '/organization/events?event_tab=schemes');
    }

    public function schemesDeleteStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->enforceMemberProfileCompletion($orgId, $bundle['current']);
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only an org admin can delete schemes.');
            redirect(base_url() . '/organization/events');
        }
        $schemeId = (int) ($_POST['scheme_id'] ?? 0);
        if ($schemeId < 1) {
            flash_set('error', 'Invalid scheme.');
            redirect(base_url() . '/organization/events');
        }
        $schemes = new Scheme();
        if ($schemes->findByIdInOrganization($schemeId, $orgId) === null) {
            flash_set('error', 'Scheme not found.');
            redirect(base_url() . '/organization/events');
        }
        $schemes->deleteInOrganization($schemeId, $orgId);
        flash_set('ok', 'Scheme deleted.');
        redirect(base_url() . '/organization/events?event_tab=schemes');
    }

    public function profile(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $this->rejectOrgAdminProfileAccess($orgId);
        $bundle = $this->orgPageBundle($orgId);
        $users = new User();
        $freshUser = $users->findById((int) $bundle['user']['id']);
        if ($freshUser !== null) {
            $bundle['user'] = $users->toSessionArray($freshUser);
            set_current_user($bundle['user']);
        }
        $profile = (new UserProfile())->findByUserId((int) $bundle['user']['id']);
        $email = trim((string) ($bundle['user']['email'] ?? ''));
        $emailMemberships = $email !== ''
            ? (new User())->listMembershipsByEmail($email)
            : $bundle['memberships'];
        $this->render('organization', 'profile/show.php', [
            'pageTitle' => page_title('My profile'),
            'navActive' => 'profile',
        ], [
            'memberships' => $bundle['memberships'],
            'emailMemberships' => $emailMemberships,
            'current' => $bundle['current'],
            'orgId' => $orgId,
            'profileUser' => $bundle['user'],
            'profileDetails' => $profile,
            'mustCompleteProfile' => $this->memberNeedsProfileCompletion((int) $bundle['user']['id'], $bundle['current']),
        ]);
    }

    public function switchOrganization(Request $request): void
    {
        $this->requireOrgId();
        $user = current_user();
        if ($user === null) {
            redirect(base_url() . '/login/organization');
        }
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $targetOrgId = (int) ($_POST['organization_id'] ?? 0);
        if ($targetUserId < 1 || $targetOrgId < 1) {
            flash_set('error', t('profile.switch_invalid'));
            $this->redirectAfterOrgSwitch($user);
        }
        $users = new User();
        $current = $users->findById((int) $user['id']);
        $target = $users->findById($targetUserId);
        if ($current === null || $target === null) {
            flash_set('error', t('profile.switch_invalid'));
            $this->redirectAfterOrgSwitch($user);
        }
        $currentEmail = normalize_email(isset($current['email']) ? (string) $current['email'] : null) ?? '';
        $targetEmail = normalize_email(isset($target['email']) ? (string) $target['email'] : null) ?? '';
        if ($currentEmail === '' || $targetEmail === '' || strcasecmp($currentEmail, $targetEmail) !== 0) {
            flash_set('error', t('profile.switch_forbidden'));
            $this->redirectAfterOrgSwitch($user);
        }
        if ((int) ($target['organization_id'] ?? 0) !== $targetOrgId) {
            flash_set('error', t('profile.switch_invalid'));
            $this->redirectAfterOrgSwitch($user);
        }
        $role = strtolower((string) ($target['role'] ?? ''));
        if (!in_array($role, ['admin', 'member'], true)) {
            flash_set('error', t('profile.switch_forbidden'));
            $this->redirectAfterOrgSwitch($user);
        }
        $org = (new Organization())->findById($targetOrgId);
        if ($org === null) {
            flash_set('error', t('profile.switch_invalid'));
            $this->redirectAfterOrgSwitch($user);
        }

        set_current_user($users->toSessionArray($target));
        set_current_organization_id($targetOrgId);
        flash_set('ok', str_replace('{org}', (string) ($org['name'] ?? ''), t('profile.switch_ok')));
        redirect(base_url() . '/organization/dashboard');
    }

    /** @param array<string,mixed> $user */
    private function redirectAfterOrgSwitch(array $user): void
    {
        $orgId = (int) (current_organization_id() ?? 0);
        if ($orgId > 0 && (new Access())->canManageOrganization($user, $orgId)) {
            redirect(base_url() . '/organization/settings/password');
        }
        redirect(base_url() . '/organization/profile');
    }

    public function profilePhotoStore(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $this->rejectOrgAdminProfileAccess($orgId);
        $bundle = $this->orgPageBundle($orgId);
        $uid = (int) $bundle['user']['id'];
        $users = new User();
        $existing = $users->findById($uid);
        $remove = isset($_POST['remove_photo']) && (string) $_POST['remove_photo'] === '1';

        if ($remove) {
            if ($existing !== null) {
                delete_user_profile_photo(isset($existing['photo_path']) ? (string) $existing['photo_path'] : null);
            }
            $users->updatePhotoPath($uid, null);
            $fresh = $users->findById($uid);
            if ($fresh !== null) {
                set_current_user($users->toSessionArray($fresh));
            }
            flash_set('ok', 'Profile photo removed.');
            redirect(base_url() . '/organization/profile');
        }

        $result = save_user_profile_photo($uid, $_FILES['photo'] ?? null);
        if (($result['ok'] ?? false) !== true) {
            flash_set('error', $result['error'] ?? 'Could not upload photo.');
            redirect(base_url() . '/organization/profile');
        }
        if ($existing !== null && !empty($existing['photo_path'])) {
            delete_user_profile_photo((string) $existing['photo_path']);
        }
        $users->updatePhotoPath($uid, (string) ($result['path'] ?? ''));
        $fresh = $users->findById($uid);
        if ($fresh !== null) {
            set_current_user($users->toSessionArray($fresh));
        }
        flash_set('ok', 'Profile photo updated.');
        redirect(base_url() . '/organization/profile');
    }

    public function profileUpdate(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $this->rejectOrgAdminProfileAccess($orgId);
        $bundle = $this->orgPageBundle($orgId);
        $uid = (int) $bundle['user']['id'];
        $dob = trim((string) ($_POST['dob'] ?? ''));
        $houseNumber = trim((string) ($_POST['house_number'] ?? ''));
        $addressLine1 = trim((string) ($_POST['address_line1'] ?? ''));
        $addressLine2 = trim((string) ($_POST['address_line2'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $state = trim((string) ($_POST['state'] ?? ''));
        $pincode = trim((string) ($_POST['pincode'] ?? ''));
        $area = trim((string) ($_POST['area'] ?? ''));
        $gender = normalize_gender((string) ($_POST['gender'] ?? ''));
        $maritalStatus = normalize_marital_status((string) ($_POST['marital_status'] ?? ''));
        $bloodGroup = normalize_blood_group((string) ($_POST['blood_group'] ?? ''));
        $highestEducation = trim((string) ($_POST['highest_education'] ?? ''));
        $professionType = normalize_profession_type((string) ($_POST['profession_type'] ?? ''));
        $jobTitle = trim((string) ($_POST['job_title'] ?? ''));
        $companyName = trim((string) ($_POST['company_name'] ?? ''));
        $industrySector = trim((string) ($_POST['industry_sector'] ?? ''));
        $companyWebsiteRaw = trim((string) ($_POST['company_website'] ?? ''));
        $companyWebsite = $companyWebsiteRaw !== '' ? (normalize_company_website($companyWebsiteRaw) ?? '') : '';
        $nativeSame = isset($_POST['native_same_as_current']) ? trim((string) $_POST['native_same_as_current']) : '0';
        $nativePincode = trim((string) ($_POST['native_pincode'] ?? ''));
        $nativeCity = trim((string) ($_POST['native_city'] ?? ''));
        $nativeState = trim((string) ($_POST['native_state'] ?? ''));
        $phoneRaw = trim((string) ($_POST['phone'] ?? ''));
        $phoneResult = provisioned_phone_from_post($phoneRaw, true);
        if (($phoneResult['ok'] ?? false) !== true) {
            flash_set('error', $phoneResult['error'] ?? 'Valid phone number is required.');
            redirect(base_url() . '/organization/profile');
        }
        $phone = $phoneResult['phone'] ?? null;
        $nameParsed = parse_person_name_from_post($_POST);
        if (($nameParsed['ok'] ?? false) !== true) {
            flash_set('error', $nameParsed['error'] ?? 'First and last name are required.');
            redirect(base_url() . '/organization/profile');
        }

        if ($dob === '' || strtotime($dob) === false) {
            flash_set('error', 'Valid date of birth is required.');
            redirect(base_url() . '/organization/profile');
        }
        if ($addressLine1 === '' || $city === '' || $state === '') {
            flash_set('error', 'All profile fields are required.');
            redirect(base_url() . '/organization/profile');
        }
        if (strlen($houseNumber) > 32) {
            flash_set('error', 'House number is too long (max 32 characters).');
            redirect(base_url() . '/organization/profile');
        }
        if (strlen($addressLine1) > 255) {
            flash_set('error', 'Address line 1 is too long (max 255 characters).');
            redirect(base_url() . '/organization/profile');
        }
        if (!preg_match('/^\d{6}$/', $pincode)) {
            flash_set('error', 'Pincode must be 6 digits.');
            redirect(base_url() . '/organization/profile');
        }
        if ($area === '' || strlen($area) > 50) {
            flash_set('error', 'Area is required (max 50 characters).');
            redirect(base_url() . '/organization/profile');
        }
        if ($gender === null) {
            flash_set('error', 'Please select a valid gender.');
            redirect(base_url() . '/organization/profile');
        }
        if ($maritalStatus === null) {
            flash_set('error', 'Please select a valid marital status.');
            redirect(base_url() . '/organization/profile');
        }
        if ($nativeSame === '1') {
            $nativeCity = $city;
            $nativeState = $state;
            $nativePincode = $pincode;
        } else {
            if (!preg_match('/^\d{6}$/', $nativePincode)) {
                flash_set('error', 'Native pincode must be 6 digits.');
                redirect(base_url() . '/organization/profile');
            }
            if ($nativeCity === '' || $nativeState === '') {
                flash_set('error', 'Please fetch native city/state from native pincode.');
                redirect(base_url() . '/organization/profile');
            }
        }
        if ($phone === null || strlen($phone) < 10) {
            flash_set('error', 'Valid phone number is required.');
            redirect(base_url() . '/organization/profile');
        }
        if ($bloodGroup === null) {
            flash_set('error', 'Please select a valid blood group.');
            redirect(base_url() . '/organization/profile');
        }
        if ($highestEducation === '') {
            flash_set('error', 'Highest education is required.');
            redirect(base_url() . '/organization/profile');
        }
        if (!is_valid_profession_type($professionType)) {
            flash_set('error', 'Please select a valid profession type.');
            redirect(base_url() . '/organization/profile');
        }
        $occupation = occupation_from_profession_type($professionType);
        if ($professionType === 'job') {
            if ($jobTitle === '' || $companyName === '' || $industrySector === '') {
                flash_set('error', 'For job profession, job title, company name, and industry sector are required.');
                redirect(base_url() . '/organization/profile');
            }
            if ($companyWebsiteRaw !== '' && $companyWebsite === '') {
                flash_set('error', 'Please enter a valid company website URL.');
                redirect(base_url() . '/organization/profile');
            }
        } elseif ($professionType === 'business') {
            if ($companyName === '') {
                flash_set('error', 'Company name is required for business profession.');
                redirect(base_url() . '/organization/profile');
            }
            if ($companyWebsiteRaw !== '' && $companyWebsite === '') {
                flash_set('error', 'Please enter a valid company website URL.');
                redirect(base_url() . '/organization/profile');
            }
            $jobTitle = '';
            $industrySector = '';
        } elseif ($professionType === 'professional') {
            if ($jobTitle === '') {
                flash_set('error', 'Profession or role title is required for professional occupation.');
                redirect(base_url() . '/organization/profile');
            }
            if ($companyWebsiteRaw !== '' && $companyWebsite === '') {
                flash_set('error', 'Please enter a valid company website URL.');
                redirect(base_url() . '/organization/profile');
            }
            $industrySector = '';
        } else {
            $jobTitle = '';
            $companyName = '';
            $industrySector = '';
            $companyWebsite = '';
        }
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        (new UserProfile())->upsert($uid, [
            'dob' => $dob,
            'gender' => $gender,
            'marital_status' => $maritalStatus,
            'house_number' => ($houseNumber !== '' ? $houseNumber : null),
            'address_line1' => $addressLine1,
            'address_line2' => ($addressLine2 !== '' ? $addressLine2 : null),
            'city' => $city,
            'state' => $state,
            'pincode' => $pincode,
            'area' => $area,
            'occupation' => $occupation,
            'blood_group' => $bloodGroup,
            'highest_education' => ($highestEducation !== '' ? $highestEducation : null),
            'profession_type' => ($professionType !== '' ? $professionType : null),
            'job_title' => ($jobTitle !== '' ? $jobTitle : null),
            'company_name' => ($companyName !== '' ? $companyName : null),
            'industry_sector' => ($industrySector !== '' ? $industrySector : null),
            'company_website' => ($companyWebsite !== '' ? $companyWebsite : null),
            'is_married' => is_married_flag_from_marital_status($maritalStatus),
            'native_pincode' => $nativePincode,
            'native_city' => $nativeCity,
            'native_state' => $nativeState,
        ]);
        $users = new User();
        $existing = $users->findById($uid);
        $users->updatePersonDetails(
            $uid,
            (string) $nameParsed['first_name'],
            $nameParsed['middle_name'] ?? null,
            (string) $nameParsed['last_name'],
            $existing['email'] ?? null,
            $phone
        );
        $fresh = $users->findById($uid);
        if ($fresh !== null) {
            set_current_user($users->toSessionArray($fresh));
        }
        flash_set('ok', 'Profile updated.');
        redirect(base_url() . '/organization/profile');
    }

    public function pincodeLookup(Request $request): void
    {
        $this->requireOrgId();
        $pin = trim((string) ($_GET['pincode'] ?? ''));
        if (!preg_match('/^\d{6}$/', $pin)) {
            json_response(['ok' => false, 'error' => 'Invalid pincode.'], 422);
        }
        $url = 'https://api.postalpincode.in/pincode/' . urlencode($pin);
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 6,
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false || trim($raw) === '') {
            json_response(['ok' => false, 'error' => 'Could not fetch pincode details.'], 502);
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded[0]) || !is_array($decoded[0])) {
            json_response(['ok' => false, 'error' => 'Unexpected API response.'], 502);
        }
        $first = $decoded[0];
        $offices = isset($first['PostOffice']) && is_array($first['PostOffice']) ? $first['PostOffice'] : [];
        if ($offices === []) {
            json_response(['ok' => false, 'error' => 'No city/state found for this pincode.'], 404);
        }
        $office = $offices[0];
        $city = trim((string) (($office['District'] ?? '') ?: ($office['Block'] ?? '') ?: ($office['Name'] ?? '')));
        $state = trim((string) ($office['State'] ?? ''));
        if ($city === '' || $state === '') {
            json_response(['ok' => false, 'error' => 'No city/state found for this pincode.'], 404);
        }
        json_response([
            'ok' => true,
            'city' => $city,
            'state' => $state,
        ]);
    }

    public function settings(Request $request): void
    {
        redirect(base_url() . '/organization/settings/password');
    }

    public function settingsPassword(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->renderOrgSettingsPage($bundle, 'password');
    }

    public function settingsNotifications(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->renderOrgSettingsPage($bundle, 'notifications');
    }

    public function settingsLanguage(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $bundle = $this->orgPageBundle($orgId);
        $this->renderOrgSettingsPage($bundle, 'language');
    }

    public function settingsChangePassword(Request $request): void
    {
        $this->requireOrgId();
        $user = current_user();
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            flash_set('error', 'All password fields are required.');
            redirect(base_url() . '/organization/settings/password');
        }
        if (strlen($newPassword) < 8) {
            flash_set('error', 'New password must be at least 8 characters.');
            redirect(base_url() . '/organization/settings/password');
        }
        if ($newPassword !== $confirmPassword) {
            flash_set('error', 'New password and confirm password do not match.');
            redirect(base_url() . '/organization/settings/password');
        }
        $users = new User();
        $row = $users->findById((int) $user['id']);
        $storedHash = $row !== null ? normalize_stored_password_hash((string) ($row['password'] ?? '')) : '';
        $currentOk = $row !== null && $storedHash !== '' && password_verify($currentPassword, $storedHash);
        if (!$currentOk && preg_match('/^[A-Za-z]{6}$/', $currentPassword) === 1) {
            $currentOk = $storedHash !== '' && password_verify(strtoupper($currentPassword), $storedHash);
        }
        if (!$currentOk) {
            flash_set('error', 'Current password is incorrect.');
            redirect(base_url() . '/organization/settings/password');
        }
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $users->updatePasswordHash((int) $user['id'], $newHash, true);
        $users->syncPasswordHashToEmailSiblings((int) $user['id'], $newHash, true);
        if (isset($_SESSION['force_password_change_user_id']) && (int) $_SESSION['force_password_change_user_id'] === (int) $user['id']) {
            unset($_SESSION['force_password_change_user_id']);
        }
        $fresh = $users->findById((int) $user['id']);
        if ($fresh !== null) {
            set_current_user($users->toSessionArray($fresh));
        }
        flash_set('ok', 'Password updated.');
        redirect(base_url() . '/organization/dashboard');
    }

    private function renderOrgSettingsPage(array $bundle, string $section): void
    {
        $user = $bundle['user'] ?? current_user();
        $uid = (int) ($user['id'] ?? 0);
        $email = trim((string) ($user['email'] ?? ''));
        $emailMemberships = $email !== ''
            ? (new User())->listMembershipsByEmail($email)
            : [];
        $this->render('organization', 'settings/index.php', [
            'pageTitle' => page_title(t('settings.title')),
            'navActive' => 'settings',
        ], [
            'memberships' => $bundle['memberships'],
            'emailMemberships' => $emailMemberships,
            'profileUser' => is_array($user) ? $user : [],
            'orgId' => (int) ($bundle['orgId'] ?? current_organization_id() ?? 0),
            'current' => $bundle['current'],
            'settingsSection' => $section,
            'pushConfigured' => web_push_is_configured(),
            'pushSubscriptionCount' => $uid > 0 ? (new \App\Models\PushSubscription())->countForUser($uid) : 0,
            'forcePasswordChange' => $uid > 0 && (
                !empty($user['must_change_password'])
                || (isset($_SESSION['force_password_change_user_id']) && (int) $_SESSION['force_password_change_user_id'] === $uid)
                || (new User())->mustChangePassword($uid)
            ),
        ]);
    }

    public function schemeShow(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only an org admin can manage scheme benefits.');
            redirect(base_url() . '/organization/events');
        }
        $schemeId = (int) ($_GET['id'] ?? 0);
        if ($schemeId < 1) {
            flash_set('error', 'Invalid scheme.');
            redirect(base_url() . '/organization/events');
        }
        $schemes = new Scheme();
        $scheme = $schemes->findByIdInOrganization($schemeId, $orgId);
        if ($scheme === null) {
            flash_set('error', 'Scheme not found.');
            redirect(base_url() . '/organization/events');
        }
        $bundle = $this->orgPageBundle($orgId);
        $assignments = $schemes->listAssignmentsForAdmin($orgId, $schemeId);
        $this->render('organization', 'schemes/show.php', [
            'pageTitle' => page_title('Scheme #' . $schemeId),
            'navActive' => 'events',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'orgId' => $orgId,
            'scheme' => $scheme,
            'assignments' => $assignments,
        ]);
    }

    public function schemeMarkDone(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $user = current_user();
        $access = new Access();
        if (!$access->canManageOrganization($user, $orgId)) {
            flash_set('error', 'Only an org admin can mark scheme benefits.');
            redirect(base_url() . '/organization/events');
        }
        $schemeId = (int) ($_POST['scheme_id'] ?? 0);
        $benefitId = (int) ($_POST['benefit_id'] ?? 0);
        if ($schemeId < 1 || $benefitId < 1) {
            flash_set('error', 'Invalid selection.');
            redirect(base_url() . '/organization/events');
        }
        $err = (new Scheme())->markDoneByAdmin($orgId, $schemeId, $benefitId, (int) $user['id']);
        if ($err !== null) {
            flash_set('error', $err);
        } else {
            flash_set('ok', 'Marked as done.');
        }
        redirect(base_url() . '/organization/scheme?id=' . $schemeId);
    }

    public function membershipRequestRespond(Request $request): void
    {
        $this->requireOrgId();
        $user = current_user();
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $action = trim((string) ($_POST['action'] ?? ''));
        if ($requestId < 1 || !in_array($action, ['approve', 'reject'], true)) {
            flash_set('error', 'Invalid action.');
            redirect(base_url() . '/organization/notifications');
        }
        $reqModel = new FamilyMembershipRequest();
        if ($action === 'approve') {
            $err = $reqModel->approve($requestId, (int) $user['id']);
            if ($err !== null) {
                flash_set('error', $err);
            } else {
                flash_set('ok', 'Approved.');
            }
        } else {
            $err = $reqModel->reject($requestId, (int) $user['id']);
            if ($err !== null) {
                flash_set('error', $err);
            } else {
                flash_set('ok', 'Declined.');
            }
        }
        redirect(base_url() . '/organization/notifications');
    }

    public function resolveIdentity(Request $request): void
    {
        $orgId = $this->requireOrgId();
        $user = current_user();
        $access = new Access();
        $allowed = $user !== null && $access->canManageOrganization($user, $orgId);
        $familyId = (int) ($_GET['family_id'] ?? 0);
        if (!$allowed && $user !== null && $familyId > 0) {
            $allowed = $access->canManageFamily($user, $familyId, $orgId);
        }
        if (!$allowed) {
            json_response(['ok' => false, 'checked' => false], 403);
        }
        $raw = trim((string) ($_GET['identity'] ?? ''));
        if ($raw === '') {
            json_response([
                'ok' => true,
                'found' => false,
                'checked' => false,
                'in_current_organization' => false,
            ]);
        }
        $row = (new User())->findByIdentity($raw, $orgId);
        if ($row === null) {
            json_response([
                'ok' => true,
                'found' => false,
                'checked' => true,
                'in_current_organization' => false,
            ]);
        }
        $uid = (int) $row['id'];
        json_response([
            'ok' => true,
            'found' => true,
            'checked' => true,
            'in_current_organization' => (new Organization())->userIsMember($uid, $orgId),
            'name' => (string) $row['name'],
        ]);
    }

    public function checkEmailAvailability(Request $request): void
    {
        $orgId = $this->ensureCanRunSignupAvailabilityCheck();
        $email = trim((string) ($_GET['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['exists' => false, 'checked' => false]);
        }
        json_response([
            'exists' => (new User())->emailIsRegistered($email, $orgId),
            'checked' => true,
        ]);
    }

    public function checkPhoneAvailability(Request $request): void
    {
        $orgId = $this->ensureCanRunSignupAvailabilityCheck();
        $raw = trim((string) ($_GET['phone'] ?? ''));
        if ($raw === '') {
            json_response(['exists' => false, 'checked' => false]);
        }
        $digits = normalize_phone($raw);
        if ($digits === null || strlen($digits) < 10) {
            json_response(['exists' => false, 'checked' => false]);
        }
        json_response([
            'exists' => (new User())->phoneIsRegistered($raw, $orgId),
            'checked' => true,
        ]);
    }

    private function ensureCanRunSignupAvailabilityCheck(): int
    {
        $user = current_user();
        if ($user === null) {
            json_response(['exists' => false, 'checked' => false], 401);
        }
        $orgId = organization_id();
        if ($orgId < 1) {
            json_response(['exists' => false, 'checked' => false], 403);
        }
        $access = new Access();
        $uid = (int) $user['id'];
        $headOrgIds = (new Family())->organizationIdsWhereUserIsFamilyHead($uid);
        if (!$access->canManageOrganization($user, $orgId) && !in_array($orgId, $headOrgIds, true)) {
            json_response(['exists' => false, 'checked' => false], 403);
        }

        return $orgId;
    }

    private function normalizeProvisionedPhone(?string $phone): ?string
    {
        if ($phone !== null && strlen($phone) === 10) {
            return '91' . $phone;
        }

        return $phone;
    }

    private function financialYearForDate(string $date): string
    {
        $dt = new \DateTimeImmutable($date);
        $year = (int) $dt->format('Y');
        $month = (int) $dt->format('n');
        if ($month >= 4) {
            $start = $year;
            $end = $year + 1;
        } else {
            $start = $year - 1;
            $end = $year;
        }

        return (string) $start . '-' . substr((string) $end, -2);
    }

    private function memberNeedsProfileCompletion(int $userId, ?array $currentMembership): bool
    {
        $user = current_user();
        if ($user === null) {
            return false;
        }
        if (strtolower((string) ($user['role'] ?? '')) === 'admin') {
            return false;
        }
        $orgId = organization_id();
        if ($orgId > 0 && (new Access())->canManageOrganization($user, $orgId)) {
            return false;
        }
        $role = strtolower((string) ($currentMembership['membership_role'] ?? ''));
        if ($role !== 'member') {
            return false;
        }

        return !(new UserProfile())->isCompleteForUser($userId);
    }

    private function enforceMemberProfileCompletion(int $orgId, ?array $currentMembership): void
    {
        $user = current_user();
        if ($user === null) {
            return;
        }
        if ($this->isProfileCompletionExemptPath(\App\Core\Request::fromGlobals()->path())) {
            return;
        }
        if ($this->memberNeedsProfileCompletion((int) $user['id'], $currentMembership)) {
            flash_set('error', 'Complete your profile first.');
            redirect(base_url() . '/organization/profile');
        }
    }

    private function isProfileCompletionExemptPath(string $path): bool
    {
        static $exact = [
            '/organization/profile',
            '/organization/my-family',
            '/organization/family',
            '/organization/family/history',
            '/logout',
        ];
        if (in_array($path, $exact, true)) {
            return true;
        }

        return strpos($path, '/organization/family/') === 0;
    }

    private function rejectOrgAdminProfileAccess(int $orgId): void
    {
        $user = current_user();
        if ($user !== null && (new Access())->canManageOrganization($user, $orgId)) {
            redirect(base_url() . '/organization/dashboard');
        }
    }

    private function calendarMonthParam(): string
    {
        $raw = trim((string) ($_GET['month'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}$/', $raw)) {
            $parts = explode('-', $raw);
            $y = (int) $parts[0];
            $m = (int) $parts[1];
            if ($m >= 1 && $m <= 12) {
                return sprintf('%04d-%02d', $y, $m);
            }
        }

        return date('Y-m');
    }

    /** @return array{start:string,end:string} */
    private function calendarMonthBounds(string $month): array
    {
        $parts = explode('-', $month);
        $y = (int) ($parts[0] ?? (int) date('Y'));
        $m = (int) ($parts[1] ?? (int) date('m'));
        $start = sprintf('%04d-%02d-01', $y, $m);
        $end = date('Y-m-t', strtotime($start));

        return ['start' => $start, 'end' => $end];
    }

    /** @return array<string, array<string,mixed>> */
    private function buildPanchangMapForMonth(string $month): array
    {
        $bounds = $this->calendarMonthBounds($month);
        $map = (new PlatformPanchangDay())->mapForDateRange($bounds['start'], $bounds['end']);
        $out = [];
        foreach ($map as $date => $row) {
            $out[$date] = $this->formatPanchangRow($row, $date);
        }

        return $out;
    }

    /** @return array<string,mixed>|null */
    private function buildPanchangForDate(string $date): ?array
    {
        $map = (new PlatformPanchangDay())->mapForDateRange($date, $date);
        $row = $map[$date] ?? null;
        if (!is_array($row)) {
            return null;
        }

        return $this->formatPanchangRow($row, $date);
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function formatPanchangRow(array $row, string $date): array
    {
        return [
            'gregorian_date' => $date,
            'weekday' => (string) ($row['weekday'] ?? ''),
            'gujarati_month' => (string) ($row['gujarati_month'] ?? ''),
            'paksha' => (string) ($row['paksha'] ?? ''),
            'tithi' => (string) ($row['tithi'] ?? ''),
            'festival_notes' => (string) (panchang_festival_notes_for_display($row['festival_notes'] ?? '') ?? ''),
            'summary' => panchang_day_summary($row),
            'short_label' => panchang_day_short_label($row),
        ];
    }

    /**
     * @return list<array{id:string,type:string,title:string,start:string,end:string,url:string,allDay:bool,meta?:string}>
     */
    private function buildCalendarItems(int $orgId, int $userId, string $month, bool $canManageOrg): array
    {
        $bounds = $this->calendarMonthBounds($month);
        $parts = explode('-', $month);
        $calendarYear = (int) ($parts[0] ?? (int) date('Y'));
        $calendarMonth = (int) ($parts[1] ?? (int) date('m'));
        $base = base_url();
        $items = [];

        foreach ((new Due())->listScheduledForCalendar($orgId, $bounds['start'], $bounds['end']) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $dueType = (string) ($row['due_type'] ?? 'event');
            $day = (string) ($row['event_date'] ?? '');
            if ($day === '' && !empty($row['created_at'])) {
                $day = date('Y-m-d', strtotime((string) $row['created_at']));
            }
            if ($day === '') {
                continue;
            }
            $items[] = [
                'id' => $dueType . '-' . $id,
                'type' => $dueType === 'occasion' ? 'occasion' : 'event',
                'title' => (string) ($row['title'] ?? ''),
                'start' => $day,
                'end' => $day,
                'url' => $dueType === 'event'
                    ? $base . '/organization/event?id=' . $id
                    : $base . '/organization/receipts?receipt_tab=dues',
                'allDay' => true,
            ];
        }

        $schemeModel = new Scheme();
        $schemes = $schemeModel->listDatedForCalendar($orgId);
        if (!$canManageOrg) {
            $eligibleIds = [];
            foreach ($schemeModel->listEligibleForUser($orgId, $userId) as $er) {
                $eligibleIds[(int) ($er['scheme_id'] ?? 0)] = true;
            }
            $schemes = array_values(array_filter(
                $schemes,
                static fn (array $s): bool => isset($eligibleIds[(int) ($s['id'] ?? 0)])
            ));
        }

        foreach ($schemes as $scheme) {
            $schemeId = (int) ($scheme['id'] ?? 0);
            if ($schemeId < 1) {
                continue;
            }
            $startRaw = trim((string) ($scheme['starts_at'] ?? ''));
            $endRaw = trim((string) ($scheme['ends_at'] ?? ''));
            $rangeStart = $startRaw !== '' ? $startRaw : $endRaw;
            $rangeEnd = $endRaw !== '' ? $endRaw : $startRaw;
            if ($rangeStart === '') {
                continue;
            }
            if ($rangeEnd === '') {
                $rangeEnd = $rangeStart;
            }
            if ($rangeEnd < $bounds['start'] || $rangeStart > $bounds['end']) {
                continue;
            }
            $clipStart = $rangeStart < $bounds['start'] ? $bounds['start'] : $rangeStart;
            $clipEnd = $rangeEnd > $bounds['end'] ? $bounds['end'] : $rangeEnd;
            $items[] = [
                'id' => 'scheme-' . $schemeId,
                'type' => 'scheme',
                'title' => (string) ($scheme['name'] ?? ''),
                'start' => $clipStart,
                'end' => $clipEnd,
                'url' => $base . '/organization/scheme?id=' . $schemeId,
                'allDay' => true,
            ];
        }

        foreach ((new PlatformHoliday())->listForCalendarRange($bounds['start'], $bounds['end']) as $holiday) {
            $holidayId = (int) ($holiday['id'] ?? 0);
            if ($holidayId < 1) {
                continue;
            }
            $startRaw = trim((string) ($holiday['start_date'] ?? ''));
            $endRaw = trim((string) ($holiday['end_date'] ?? ''));
            if ($startRaw === '') {
                continue;
            }
            if ($endRaw === '') {
                $endRaw = $startRaw;
            }
            if ($endRaw < $bounds['start'] || $startRaw > $bounds['end']) {
                continue;
            }
            $clipStart = $startRaw < $bounds['start'] ? $bounds['start'] : $startRaw;
            $clipEnd = $endRaw > $bounds['end'] ? $bounds['end'] : $endRaw;
            $category = normalize_platform_holiday_category((string) ($holiday['category'] ?? 'religious'));
            $title = platform_holiday_display_title($holiday);
            if ($title === '') {
                continue;
            }
            $item = [
                'id' => 'holiday-' . $holidayId,
                'type' => 'holiday',
                'category' => $category,
                'title' => $title,
                'start' => $clipStart,
                'end' => $clipEnd,
                'url' => $base . '/organization/dashboard',
                'allDay' => true,
            ];
            $notes = trim((string) ($holiday['notes'] ?? ''));
            if ($notes !== '') {
                $item['meta'] = $notes;
            }
            $items[] = $item;
        }

        foreach ((new PlatformPanchangDay())->listFestivalsForDateRange($bounds['start'], $bounds['end']) as $festivalRow) {
            $date = (string) ($festivalRow['gregorian_date'] ?? '');
            $title = panchang_festival_notes_for_display($festivalRow['festival_notes'] ?? '');
            if ($date === '' || $title === null || $title === '') {
                continue;
            }
            $item = [
                'id' => 'festival-' . $date,
                'type' => 'festival',
                'title' => $title,
                'start' => $date,
                'end' => $date,
                'allDay' => true,
            ];
            $summary = panchang_day_summary($festivalRow);
            if ($summary !== '') {
                $item['meta'] = $summary;
            }
            $items[] = $item;
        }

        foreach ((new OrgCalendarDay())->listForCalendarRange($orgId, $bounds['start'], $bounds['end']) as $orgDay) {
            $dayId = (int) ($orgDay['id'] ?? 0);
            if ($dayId < 1) {
                continue;
            }
            $startRaw = trim((string) ($orgDay['start_date'] ?? ''));
            $endRaw = trim((string) ($orgDay['end_date'] ?? ''));
            if ($startRaw === '') {
                continue;
            }
            if ($endRaw === '') {
                $endRaw = $startRaw;
            }
            if ($endRaw < $bounds['start'] || $startRaw > $bounds['end']) {
                continue;
            }
            $clipStart = $startRaw < $bounds['start'] ? $bounds['start'] : $startRaw;
            $clipEnd = $endRaw > $bounds['end'] ? $bounds['end'] : $endRaw;
            $category = normalize_org_calendar_day_category((string) ($orgDay['category'] ?? 'other'));
            $title = platform_holiday_display_title($orgDay);
            if ($title === '') {
                continue;
            }
            $item = [
                'id' => 'org-day-' . $dayId,
                'type' => 'org_day',
                'category' => $category,
                'title' => $title,
                'start' => $clipStart,
                'end' => $clipEnd,
                'url' => $canManageOrg
                    ? $base . '/organization/calendar-days'
                    : $base . '/organization/dashboard',
                'allDay' => true,
            ];
            $notes = trim((string) ($orgDay['notes'] ?? ''));
            $eventTime = format_org_calendar_event_time(isset($orgDay['event_time']) ? (string) $orgDay['event_time'] : null);
            if (org_calendar_day_shows_event_time($category) && $eventTime !== '') {
                $item['meta'] = $notes !== ''
                    ? $eventTime . ' · ' . $notes
                    : $eventTime;
            } elseif ($notes !== '') {
                $item['meta'] = $notes;
            }
            if (org_calendar_day_shows_event_time($category) && $eventTime !== '') {
                $item['time'] = $eventTime;
            }
            $items[] = $item;
        }

        if ($canManageOrg) {
            foreach ((new Organization())->listBirthdaysForCalendarMonth($orgId, $calendarMonth) as $birthday) {
                $name = trim((string) ($birthday['name'] ?? ''));
                $dob = trim((string) ($birthday['dob'] ?? ''));
                $kind = (string) ($birthday['kind'] ?? 'member');
                $entityId = (int) ($birthday['id'] ?? 0);
                if ($name === '' || $dob === '' || $entityId < 1) {
                    continue;
                }
                $day = $this->birthdayDateInCalendarMonth($dob, $calendarYear, $calendarMonth);
                if ($day === null || $day < $bounds['start'] || $day > $bounds['end']) {
                    continue;
                }
                $familyId = (int) ($birthday['family_id'] ?? 0);
                $url = $familyId > 0
                    ? $base . '/organization/family?id=' . $familyId
                    : $base . '/organization/families';
                $birthYear = (int) date('Y', strtotime($dob));
                $age = $calendarYear - $birthYear;
                $item = [
                    'id' => 'birthday-' . $kind . '-' . $entityId,
                    'type' => 'birthday',
                    'title' => str_replace('{name}', $name, t('calendar.birthday_title')),
                    'start' => $day,
                    'end' => $day,
                    'url' => $url,
                    'allDay' => true,
                ];
                if ($age > 0) {
                    $item['meta'] = str_replace('{age}', (string) $age, t('calendar.birthday_turns'));
                }
                $items[] = $item;
            }
        }

        usort($items, static function (array $a, array $b): int {
            $cmp = strcmp($a['start'], $b['start']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp($a['title'], $b['title']);
        });

        return $items;
    }

    /** @return list<array<string,mixed>> */
    private function buildTodayCalendarItems(int $orgId, int $userId, bool $canManageOrg): array
    {
        $today = date('Y-m-d');

        return $this->filterCalendarItemsForDate(
            $this->buildCalendarItems($orgId, $userId, date('Y-m'), $canManageOrg),
            $today
        );
    }

    /**
     * @param list<array<string,mixed>> $items
     * @return list<array<string,mixed>>
     */
    private function filterCalendarItemsForDate(array $items, string $date): array
    {
        return array_values(array_filter(
            $items,
            static function (array $item) use ($date): bool {
                $start = (string) ($item['start'] ?? '');
                $end = (string) ($item['end'] ?? $start);
                if ($start === '') {
                    return false;
                }

                return $start <= $date && $end >= $date;
            }
        ));
    }

    private function birthdayDateInCalendarMonth(string $dob, int $calendarYear, int $calendarMonth): ?string
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dob);
        if ($dt === false) {
            return null;
        }
        $birthMonth = (int) $dt->format('n');
        $birthDay = (int) $dt->format('j');
        if ($birthMonth !== $calendarMonth) {
            return null;
        }
        $daysInMonth = (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $calendarYear, $calendarMonth)))->format('t');
        if ($birthDay > $daysInMonth) {
            $birthDay = $daysInMonth;
        }

        return sprintf('%04d-%02d-%02d', $calendarYear, $calendarMonth, $birthDay);
    }

    /** @param array<string,mixed> $user */
    private function requireOrgAdmin(array $user, int $orgId): void
    {
        if (!(new Access())->canManageOrganization($user, $orgId)) {
            flash_set('error', t('calendar_days.org.forbidden'));
            redirect(base_url() . '/organization/dashboard');
        }
    }

    /** @param array{memberships:list<array<string,mixed>>,current:?array<string,mixed>,orgId:int,user:array<string,mixed>} $bundle */
    /** @param array<string,mixed>|null $drafts */
    private function renderOrgCalendarDaysPage(array $bundle, array $days, ?int $dayId, ?string $formError, ?array $drafts): void
    {
        $orgId = (int) $bundle['orgId'];
        $resolvedDayId = $dayId !== null && $dayId > 0 ? $dayId : 0;
        $day = null;
        if ($resolvedDayId > 0) {
            $day = (new OrgCalendarDay())->findByIdInOrganization($resolvedDayId, $orgId);
            if ($day === null) {
                flash_set('error', t('calendar_days.org.not_found'));
                redirect(base_url() . '/organization/calendar-days');
            }
        }

        if ($drafts !== null) {
            $titleDraft = (string) ($drafts['title'] ?? '');
            $titleGuDraft = (string) ($drafts['title_gu'] ?? '');
            $categoryDraft = normalize_org_calendar_day_category((string) ($drafts['category'] ?? 'other'));
            $startDateDraft = (string) ($drafts['start_date'] ?? '');
            $endDateDraft = (string) ($drafts['end_date'] ?? '');
            $notesDraft = (string) ($drafts['notes'] ?? '');
            $eventTimeDraft = org_calendar_event_time_input_value((string) ($drafts['event_time'] ?? ''));
        } elseif ($day !== null) {
            $titleDraft = (string) ($day['title'] ?? '');
            $titleGuDraft = (string) ($day['title_gu'] ?? '');
            $categoryDraft = normalize_org_calendar_day_category(isset($day['category']) ? (string) $day['category'] : 'other');
            $startDateDraft = (string) ($day['start_date'] ?? '');
            $endDateDraft = (string) ($day['end_date'] ?? '');
            $notesDraft = (string) ($day['notes'] ?? '');
            $eventTimeDraft = org_calendar_event_time_input_value(isset($day['event_time']) ? (string) $day['event_time'] : null);
        } else {
            $titleDraft = '';
            $titleGuDraft = '';
            $categoryDraft = 'other';
            $startDateDraft = '';
            $endDateDraft = '';
            $notesDraft = '';
            $eventTimeDraft = '';
        }

        $this->render('organization', 'calendar_days/index.php', [
            'pageTitle' => page_title(t('calendar_days.org.title')),
            'navActive' => 'calendar_days',
        ], [
            'memberships' => $bundle['memberships'],
            'current' => $bundle['current'],
            'days' => $days,
            'dayId' => $resolvedDayId,
            'formError' => $formError,
            'titleDraft' => $titleDraft,
            'titleGuDraft' => $titleGuDraft,
            'categoryDraft' => $categoryDraft,
            'startDateDraft' => $startDateDraft,
            'endDateDraft' => $endDateDraft,
            'notesDraft' => $notesDraft,
            'eventTimeDraft' => $eventTimeDraft,
        ]);
    }

    /** @param array{memberships:list<array<string,mixed>>,current:?array<string,mixed>,orgId:int,user:array<string,mixed>} $bundle */
    private function storeOrUpdateOrgCalendarDay(int $orgId, array $bundle, ?int $id): void
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $titleGu = trim((string) ($_POST['title_gu'] ?? ''));
        $category = normalize_org_calendar_day_category((string) ($_POST['category'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate = trim((string) ($_POST['end_date'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $eventTimeRaw = trim((string) ($_POST['event_time'] ?? ''));

        $renderError = function (string $message) use ($orgId, $bundle, $id, $title, $titleGu, $category, $startDate, $endDate, $notes, $eventTimeRaw): void {
            $days = (new OrgCalendarDay())->listForOrganization($orgId);
            $this->renderOrgCalendarDaysPage($bundle, $days, $id, $message, [
                'title' => $title,
                'title_gu' => $titleGu,
                'category' => $category,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'notes' => $notes,
                'event_time' => $eventTimeRaw,
            ]);
        };

        if ($title === '') {
            $renderError(t('calendar_days.org.error_title'));
            return;
        }
        if ($startDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $renderError(t('calendar_days.org.error_start'));
            return;
        }
        if ($endDate === '') {
            $endDate = $startDate;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $renderError(t('calendar_days.org.error_end'));
            return;
        }
        if ($endDate < $startDate) {
            $renderError(t('calendar_days.org.error_range'));
            return;
        }

        $eventTime = null;
        if (org_calendar_day_shows_event_time($category)) {
            if ($eventTimeRaw !== '') {
                $eventTime = normalize_org_calendar_event_time($eventTimeRaw);
                if ($eventTime === null) {
                    $renderError(t('calendar_days.org.error_time'));
                    return;
                }
            }
        }

        $payload = [
            'title' => $title,
            'title_gu' => $titleGu !== '' ? $titleGu : null,
            'category' => $category,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'event_time' => $eventTime,
            'notes' => $notes !== '' ? $notes : null,
        ];

        $model = new OrgCalendarDay();
        if ($id !== null && $id > 0) {
            if ($model->findByIdInOrganization($id, $orgId) === null) {
                flash_set('error', t('calendar_days.org.not_found'));
                redirect(base_url() . '/organization/calendar-days');
            }
            $model->update($id, $orgId, $payload);
            flash_set('ok', t('calendar_days.org.updated'));
            redirect(base_url() . '/organization/calendar-days?edit=' . $id);
        } else {
            $user = $bundle['user'];
            $createdBy = (int) ($user['id'] ?? 0);
            $newId = $model->create([
                'organization_id' => $orgId,
                'title' => $payload['title'],
                'title_gu' => $payload['title_gu'],
                'category' => $payload['category'],
                'start_date' => $payload['start_date'],
                'end_date' => $payload['end_date'],
                'event_time' => $payload['event_time'],
                'notes' => $payload['notes'],
                'created_by' => $createdBy > 0 ? $createdBy : null,
            ]);
            try {
                (new CalendarDayNotificationService())->notifyCreated($orgId, [
                    'id' => $newId,
                    'title' => $payload['title'],
                    'title_gu' => $payload['title_gu'],
                    'category' => $payload['category'],
                    'start_date' => $payload['start_date'],
                    'end_date' => $payload['end_date'],
                    'event_time' => $payload['event_time'],
                    'notes' => $payload['notes'],
                ]);
            } catch (\Throwable $e) {
                // Notification failures never block calendar-day creation.
            }
            flash_set('ok', t('calendar_days.org.created'));
        }

        redirect(base_url() . '/organization/calendar-days');
    }
}
