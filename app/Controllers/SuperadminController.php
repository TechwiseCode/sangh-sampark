<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\EmailVerificationToken;
use App\Models\Family;
use App\Models\Organization;
use App\Models\PlatformHoliday;
use App\Models\PlatformPanchangDay;
use App\Models\User;
use App\Services\FamilyImportService;
use App\Services\UserProvisionService;

use function base_url;
use function current_user;
use function flash_set;
use function is_control_plane;
use function is_valid_email;
use function normalize_gender;
use function normalize_org_address;
use function normalize_org_nickname;
use function normalize_org_short_code;
use function normalize_maps_url;
use function normalize_phone;
use function validate_member_initials;
use function validate_org_short_code;
use function redirect;
use function request_wants_json;
use function json_response;
use function release_session_lock;
use function resume_session_for_flash;
use function mail_config_summary;
use function rate_limit_too_many;
use function system_send_email_smtp;

final class SuperadminController extends Controller
{
    public function dashboard(Request $request): void
    {
        $users = new User();
        $orgs = new Organization();
        $families = new Family();
        $organizations = $orgs->countAll();
        $organizationsWithoutAdmin = $orgs->countWithoutAdmins();
        $totalFamilies = $families->countAll();
        $stats = [
            'users' => $users->countMembers(),
            'organizations' => $organizations,
            'families' => $totalFamilies,
            'users_7d' => $users->countMembersCreatedSinceDays(7),
            'users_30d' => $users->countMembersCreatedSinceDays(30),
            'organizations_30d' => $orgs->countCreatedSinceDays(30),
            'families_30d' => $families->countCreatedSinceDays(30),
            'organizations_without_admin' => $organizationsWithoutAdmin,
            'families_without_head_membership' => $families->countHeadWithoutMembership(),
            'families_per_org' => $organizations > 0 ? round($totalFamilies / $organizations, 2) : 0.0,
            'organizations_with_admin' => max(0, $organizations - $organizationsWithoutAdmin),
        ];
        $this->render('superadmin', 'dashboard_home.php', [
            'pageTitle' => page_title('Superadmin'),
            'navActive' => 'dashboard',
        ], [
            'stats' => $stats,
        ]);
    }

    public function adminsIndex(Request $request): void
    {
        redirect(base_url() . '/superadmin/members');
    }

    public function adminsNew(Request $request): void
    {
        $this->render('superadmin', 'admins/form.php', [
            'pageTitle' => page_title('Add Upashray/Sangh admin'),
            'navActive' => 'members',
        ], [
            'formError' => null,
            'nameDraft' => '',
            'emailDraft' => '',
            'phoneDraft' => '',
            'organizationIdDraft' => (int) ($_GET['organization_id'] ?? 0),
            'organizations' => (new Organization())->listAll(),
        ]);
    }

    public function adminsStore(Request $request): void
    {
        $nameParsed = parse_person_name_from_post($_POST);
        $emailRaw = trim((string) ($_POST['email'] ?? ''));
        $email = $emailRaw !== '' ? $emailRaw : null;
        $phoneResult = provisioned_phone_from_post(trim((string) ($_POST['phone'] ?? '')), true);
        $phoneRaw = (string) ($_POST['phone'] ?? '');
        $organizationId = (int) ($_POST['organization_id'] ?? 0);
        $name = (string) ($nameParsed['full_name'] ?? '');

        $fail = function (string $message) use ($name, $emailRaw, $phoneRaw, $organizationId): void {
            if (request_wants_json()) {
                json_response(['ok' => false, 'error' => $message], 422);
            }
            $this->renderAdminForm($message, $name, $emailRaw, $phoneRaw, $organizationId);
        };

        if (($nameParsed['ok'] ?? false) !== true) {
            $fail($nameParsed['error'] ?? 'Name is required.');
        }
        if ($email === null || !is_valid_email($email)) {
            $fail('A valid email is required so login credentials can be sent.');
        }
        if (($phoneResult['ok'] ?? false) !== true) {
            $fail($phoneResult['error'] ?? 'Valid phone number is required.');
        }
        $phone = $phoneResult['phone'] ?? null;

        $users = new User();
        if ($organizationId < 1 || (new Organization())->findById($organizationId) === null) {
            $fail('Select a valid Upashray/Sangh.');
        }
        if ($users->findByEmail($email, $organizationId)) {
            $fail('Email already in use in that Upashray/Sangh.');
        }
        if ($phone !== null && $users->phoneIsRegistered($phone, $organizationId)) {
            $fail('Phone already in use in that Upashray/Sangh.');
        }

        release_session_lock();
        $created = UserProvisionService::createWithInviteEmail($users, [
            'organization_id' => $organizationId,
            'name' => $name,
            'first_name' => (string) ($nameParsed['first_name'] ?? ''),
            'middle_name' => $nameParsed['middle_name'] ?? null,
            'last_name' => (string) ($nameParsed['last_name'] ?? ''),
            'email' => $email,
            'phone' => $phone,
            'role' => 'admin',
        ]);
        resume_session_for_flash();

        if (($created['ok'] ?? false) !== true) {
            $fail($created['error'] ?? 'Could not create admin.');
        }

        $message = !empty($created['email_deferred'])
            ? 'Upashray/Sangh admin created. Invite email is being sent — it may take a minute.'
            : (!empty($created['email_sent'])
                ? 'Upashray/Sangh admin created. Invite email with temporary password was sent.'
                : 'Admin created, but email could not be sent. Ask them to use Forgot password on the login page.');
        $redirect = base_url() . '/superadmin/members';
        if (request_wants_json()) {
            json_response(['ok' => true, 'message' => $message, 'redirect' => $redirect]);
        }
        flash_set('ok', $message);
        redirect($redirect);
    }

    private function renderAdminForm(string $error, string $name, string $email, string $phone, int $organizationId = 0): void
    {
        $nameRow = $name !== '' ? split_person_full_name($name) : ['first_name' => '', 'middle_name' => null, 'last_name' => ''];
        $this->render('superadmin', 'admins/form.php', [
            'pageTitle' => page_title('Add Upashray/Sangh admin'),
            'navActive' => 'members',
        ], [
            'formError' => $error,
            'nameDraft' => $name,
            'namePartsDraft' => $nameRow,
            'emailDraft' => $email,
            'phoneDraft' => $phone,
            'organizationIdDraft' => $organizationId,
            'organizations' => (new Organization())->listAll(),
        ]);
        exit;
    }

    public function organizationsIndex(Request $request): void
    {
        [$sort, $dir] = parse_table_sort(
            ['id', 'code', 'name', 'nickname', 'created_by', 'created_at'],
            'name',
            'asc'
        );
        $organizations = (new Organization())->listAll($sort, $dir);
        $this->render('superadmin', 'organizations/index.php', [
            'pageTitle' => page_title('Upashray/Sangh'),
            'navActive' => 'organizations',
        ], [
            'organizations' => $organizations,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function membersIndex(Request $request): void
    {
        $orgId = (int) ($_GET['organization_id'] ?? 0);
        $orgFilterId = $orgId > 0 ? $orgId : null;
        $roleFilter = (string) ($_GET['role'] ?? 'all');
        if (!in_array($roleFilter, ['all', 'admin', 'member'], true)) {
            $roleFilter = 'all';
        }
        $roleQuery = $roleFilter === 'all' ? null : $roleFilter;
        [$sort, $dir] = parse_table_sort(
            ['id', 'name', 'email', 'phone', 'orgs', 'type', 'since'],
            'name',
            'asc'
        );
        $orgs = new Organization();
        $organizations = $orgs->listAll();
        $users = (new User())->listMembersForDirectory($orgFilterId, $roleQuery, $sort, $dir);
        $this->render('superadmin', 'members/index.php', [
            'pageTitle' => page_title('Upashray/Sangh users'),
            'navActive' => 'members',
        ], [
            'members' => $users,
            'organizations' => $organizations,
            'selectedOrganizationId' => $orgId,
            'selectedRoleFilter' => $roleFilter,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function organizationsNew(Request $request): void
    {
        $this->render('superadmin', 'organizations/form.php', [
            'pageTitle' => page_title('Add Upashray/Sangh'),
            'navActive' => 'organizations',
        ], [
            'formError' => null,
            'nameDraft' => '',
            'shortCodeDraft' => '',
            'memberInitialsDraft' => '',
            'nicknameDraft' => '',
            'addressDraft' => '',
            'mapsUrlDraft' => '',
            'adminNamePartsDraft' => ['first_name' => '', 'middle_name' => null, 'last_name' => ''],
            'adminEmailDraft' => '',
            'adminPhoneDraft' => '',
        ]);
    }

    public function organizationsStore(Request $request): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $shortCodeRaw = (string) ($_POST['short_code'] ?? '');
        $memberInitialsRaw = (string) ($_POST['member_initials'] ?? '');
        $nickname = normalize_org_nickname($_POST['nickname'] ?? null);
        $address = normalize_org_address($_POST['address'] ?? null);
        $mapsUrlRaw = trim((string) ($_POST['maps_url'] ?? ''));
        $mapsUrl = $mapsUrlRaw !== '' ? normalize_maps_url($mapsUrlRaw) : null;
        $adminNameParsed = parse_person_name_from_post($_POST, 'admin_', 'admin_name');
        $adminName = (string) ($adminNameParsed['full_name'] ?? '');
        $adminEmailRaw = trim((string) ($_POST['admin_email'] ?? ''));
        $adminEmail = $adminEmailRaw !== '' ? $adminEmailRaw : null;
        $adminPhoneResult = provisioned_phone_from_post(trim((string) ($_POST['admin_phone'] ?? '')), true);
        $adminPhone = ($adminPhoneResult['ok'] ?? false) === true ? ($adminPhoneResult['phone'] ?? null) : null;

        $renderForm = function (?string $error) use (
            $name,
            $shortCodeRaw,
            $memberInitialsRaw,
            $nickname,
            $address,
            $mapsUrlRaw,
            $adminNameParsed,
            $adminEmailRaw,
            $adminPhone
        ): void {
            $phoneDraft = '';
            if ($adminPhone !== null && strlen($adminPhone) === 12 && strpos($adminPhone, '91') === 0) {
                $phoneDraft = substr($adminPhone, 2);
            }
            $this->render('superadmin', 'organizations/form.php', [
                'pageTitle' => page_title('Add Upashray/Sangh'),
                'navActive' => 'organizations',
            ], [
                'formError' => $error,
                'nameDraft' => $name,
                'shortCodeDraft' => normalize_org_short_code($shortCodeRaw),
                'memberInitialsDraft' => strtoupper(preg_replace('/[^A-Za-z]/', '', $memberInitialsRaw) ?? ''),
                'nicknameDraft' => $nickname ?? '',
                'addressDraft' => $address ?? '',
                'mapsUrlDraft' => $mapsUrlRaw,
                'adminNamePartsDraft' => ($adminNameParsed['ok'] ?? false) === true
                    ? [
                        'first_name' => (string) ($adminNameParsed['first_name'] ?? ''),
                        'middle_name' => $adminNameParsed['middle_name'] ?? null,
                        'last_name' => (string) ($adminNameParsed['last_name'] ?? ''),
                    ]
                    : ['first_name' => '', 'middle_name' => null, 'last_name' => ''],
                'adminEmailDraft' => $adminEmailRaw,
                'adminPhoneDraft' => $phoneDraft,
            ]);
        };

        if ($name === '') {
            $renderForm('Upashray/Sangh name is required.');
            return;
        }
        if ($mapsUrlRaw !== '' && $mapsUrl === null) {
            $renderForm(t('superadmin.organizations.error_maps_url'));
            return;
        }
        $shortCodeResult = validate_org_short_code($shortCodeRaw);
        if (($shortCodeResult['ok'] ?? false) !== true) {
            $renderForm((string) ($shortCodeResult['error'] ?? 'Invalid short name.'));
            return;
        }
        $orgCode = (string) ($shortCodeResult['code'] ?? '');
        $initialsResult = validate_member_initials($memberInitialsRaw, null, false);
        if (($initialsResult['ok'] ?? false) !== true) {
            $renderForm((string) ($initialsResult['error'] ?? 'Invalid member initials.'));
            return;
        }
        $memberInitials = $initialsResult['initials'] ?? null;
        if (($adminNameParsed['ok'] ?? false) !== true) {
            $renderForm($adminNameParsed['error'] ?? 'Upashray/Sangh admin name is required.');
            return;
        }
        if ($adminEmail === null || !is_valid_email($adminEmail)) {
            $renderForm('A valid admin email is required so login details can be sent.');
            return;
        }
        if (($adminPhoneResult['ok'] ?? false) !== true) {
            $renderForm($adminPhoneResult['error'] ?? 'Valid admin phone number is required.');
            return;
        }

        $users = new User();
        if ($users->findByEmail($adminEmail, null) !== null) {
            $renderForm('That email belongs to a platform superadmin account.');
            return;
        }
        if ($adminPhone !== null && $users->phoneIsRegistered($adminPhone, null)) {
            $renderForm('That phone belongs to a platform superadmin account.');
            return;
        }

        $user = current_user();
        $candidateId = $user !== null ? (int) ($user['id'] ?? 0) : 0;
        $uid = $candidateId > 0 && $users->findById($candidateId) !== null ? $candidateId : null;
        if ($uid === null) {
            flash_set('error', 'Your session could not be matched to a database user. Sign out, sign in again, then retry.');
            redirect(base_url() . '/superadmin/organizations/new');
        }

        $orgs = new Organization();
        $orgId = $orgs->create($name, $uid, $orgCode, $nickname, $address, $memberInitials, $mapsUrl);

        if ($users->findByEmail($adminEmail, $orgId) !== null) {
            flash_set('error', 'Upashray/Sangh created, but that admin email is already used. Add the admin from the Upashray/Sangh page.');
            redirect(base_url() . '/superadmin/organization?id=' . $orgId);
        }
        if ($adminPhone !== null && $users->phoneIsRegistered($adminPhone, $orgId)) {
            flash_set('error', 'Upashray/Sangh created, but that admin phone is already used. Add the admin from the Upashray/Sangh page.');
            redirect(base_url() . '/superadmin/organization?id=' . $orgId);
        }

        $created = UserProvisionService::createWithInviteEmail($users, [
            'organization_id' => $orgId,
            'name' => $adminName,
            'first_name' => (string) ($adminNameParsed['first_name'] ?? ''),
            'middle_name' => $adminNameParsed['middle_name'] ?? null,
            'last_name' => (string) ($adminNameParsed['last_name'] ?? ''),
            'email' => $adminEmail,
            'phone' => $adminPhone,
            'role' => 'admin',
        ]);
        if (($created['ok'] ?? false) !== true) {
            flash_set('error', 'Upashray/Sangh created, but admin account failed: ' . ($created['error'] ?? 'unknown error') . '. Add the admin from the Upashray/Sangh page.');
            redirect(base_url() . '/superadmin/organization?id=' . $orgId);
        }

        if (!empty($created['email_deferred'])) {
            flash_set('ok', 'Upashray/Sangh and admin created. Invite email is being sent — it may take a minute.');
        } elseif (!empty($created['email_sent'])) {
            flash_set('ok', 'Upashray/Sangh and admin created. Login details were emailed to the admin.');
        } else {
            flash_set('ok', 'Upashray/Sangh and admin created, but the invite email could not be sent. The admin can use Forgot password on the login page.');
        }
        redirect(base_url() . '/superadmin/organization?id=' . $orgId);
    }

    public function checkEmailAvailability(Request $request): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $email = trim((string) ($_GET['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['exists' => false, 'checked' => false]);

            return;
        }
        $orgRaw = trim((string) ($_GET['organization_id'] ?? ''));
        $organizationId = $orgRaw !== '' ? (int) $orgRaw : null;
        if ($organizationId !== null && $organizationId < 1) {
            $organizationId = null;
        }
        $exists = (new User())->emailIsRegistered($email, $organizationId);
        echo json_encode(['exists' => $exists, 'checked' => true]);
    }

    public function checkPhoneAvailability(Request $request): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $raw = trim((string) ($_GET['phone'] ?? ''));
        if ($raw === '') {
            echo json_encode(['exists' => false, 'checked' => false]);

            return;
        }
        $digits = normalize_phone($raw);
        if ($digits === null || strlen($digits) < 10) {
            echo json_encode(['exists' => false, 'checked' => false]);

            return;
        }
        $orgRaw = trim((string) ($_GET['organization_id'] ?? ''));
        $organizationId = $orgRaw !== '' ? (int) $orgRaw : null;
        if ($organizationId !== null && $organizationId < 1) {
            $organizationId = null;
        }
        $exists = (new User())->phoneIsRegistered($raw, $organizationId);
        echo json_encode(['exists' => $exists, 'checked' => true]);
    }

    public function checkOrgCodeAvailability(Request $request): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $raw = (string) ($_GET['code'] ?? '');
        $orgRaw = trim((string) ($_GET['organization_id'] ?? ''));
        $organizationId = $orgRaw !== '' ? (int) $orgRaw : null;
        if ($organizationId !== null && $organizationId < 1) {
            $organizationId = null;
        }
        $result = validate_org_short_code($raw, $organizationId);
        if (($result['ok'] ?? false) !== true) {
            $code = normalize_org_short_code($raw);
            $taken = $code !== '' && (new Organization())->orgCodeIsTaken($code, $organizationId);
            echo json_encode([
                'checked' => true,
                'valid' => false,
                'exists' => $taken,
                'error' => (string) ($result['error'] ?? ''),
                'code' => $code,
            ]);

            return;
        }
        echo json_encode([
            'checked' => true,
            'valid' => true,
            'exists' => false,
            'error' => '',
            'code' => (string) ($result['code'] ?? ''),
        ]);
    }

    public function organizationShow(Request $request): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id < 1) {
            flash_set('error', 'Invalid organization.');
            redirect(base_url() . '/superadmin/organizations');
        }
        $orgs = new Organization();
        $organization = $orgs->findById($id);
        if (!$organization) {
            flash_set('error', 'Upashray/Sangh not found.');
            redirect(base_url() . '/superadmin/organizations');
        }
        $members = $orgs->listAdminsAndFamilyHeads($id);
        $adminUsers = $orgs->listAdminUsers($id);
        $familyCount = $orgs->countFamilies($id);
        $this->render('superadmin', 'organizations/show.php', [
            'pageTitle' => page_title((string) $organization['name']),
            'navActive' => 'organizations',
        ], [
            'organization' => $organization,
            'members' => $members,
            'adminUsers' => $adminUsers,
            'familyCount' => $familyCount,
        ]);
    }

    public function organizationUpdate(Request $request): void
    {
        $organizationId = (int) ($_POST['organization_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $shortCodeRaw = (string) ($_POST['short_code'] ?? '');
        $memberInitialsRaw = (string) ($_POST['member_initials'] ?? '');
        $nickname = normalize_org_nickname($_POST['nickname'] ?? null);
        $address = normalize_org_address($_POST['address'] ?? null);
        $mapsUrlRaw = trim((string) ($_POST['maps_url'] ?? ''));
        $mapsUrl = $mapsUrlRaw !== '' ? normalize_maps_url($mapsUrlRaw) : null;
        if ($organizationId < 1 || $name === '') {
            flash_set('error', 'Upashray/Sangh and name are required.');
            redirect(base_url() . '/superadmin/organizations');
        }
        $orgs = new Organization();
        $org = $orgs->findById($organizationId);
        if ($org === null) {
            flash_set('error', 'Upashray/Sangh not found.');
            redirect(base_url() . '/superadmin/organizations');
        }
        if ($mapsUrlRaw !== '' && $mapsUrl === null) {
            flash_set('error', t('superadmin.organizations.error_maps_url'));
            redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
        }
        $shortCodeResult = validate_org_short_code($shortCodeRaw, $organizationId);
        if (($shortCodeResult['ok'] ?? false) !== true) {
            flash_set('error', (string) ($shortCodeResult['error'] ?? 'Invalid short name.'));
            redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
        }
        $orgCode = (string) ($shortCodeResult['code'] ?? '');
        $initialsResult = validate_member_initials($memberInitialsRaw, $organizationId, true);
        if (($initialsResult['ok'] ?? false) !== true) {
            flash_set('error', (string) ($initialsResult['error'] ?? 'Invalid member initials.'));
            redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
        }
        $memberInitials = $initialsResult['initials'] ?? null;
        $orgs->updateDetails($organizationId, $name, $nickname, $address, $orgCode, $memberInitials, $mapsUrl);
        flash_set('ok', t('superadmin.organizations.show.updated'));
        redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
    }

    public function organizationSetActive(Request $request): void
    {
        $organizationId = (int) ($_POST['organization_id'] ?? 0);
        $active = isset($_POST['is_active']) && (string) $_POST['is_active'] === '1';
        if ($organizationId < 1) {
            flash_set('error', 'Invalid organization.');
            redirect(base_url() . '/superadmin/organizations');
        }
        $orgs = new Organization();
        if ($orgs->findById($organizationId) === null) {
            flash_set('error', 'Upashray/Sangh not found.');
            redirect(base_url() . '/superadmin/organizations');
        }
        $orgs->setActive($organizationId, $active);
        flash_set(
            'ok',
            $active
                ? 'Upashray/Sangh enabled. Admins and members can sign in again.'
                : 'Upashray/Sangh disabled. Admins and members cannot sign in until it is enabled again.'
        );
        redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
    }

    public function organizationUpdateAdmin(Request $request): void
    {
        $organizationId = (int) ($_POST['organization_id'] ?? 0);
        $adminUserId = (int) ($_POST['admin_user_id'] ?? 0);
        $nameParsed = parse_person_name_from_post($_POST);
        $emailRaw = trim((string) ($_POST['email'] ?? ''));
        $email = $emailRaw !== '' ? $emailRaw : null;
        $phoneResult = provisioned_phone_from_post(trim((string) ($_POST['phone'] ?? '')), true);
        if ($organizationId < 1 || $adminUserId < 1) {
            flash_set('error', 'Admin user is required.');
            redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
        }
        if (($nameParsed['ok'] ?? false) !== true) {
            flash_set('error', $nameParsed['error'] ?? 'Name is required.');
            redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
        }
        if (($phoneResult['ok'] ?? false) !== true) {
            flash_set('error', $phoneResult['error'] ?? 'Valid phone number is required.');
            redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
        }
        $phone = $phoneResult['phone'] ?? null;
        $orgs = new Organization();
        if (!$orgs->findById($organizationId)) {
            flash_set('error', 'Upashray/Sangh not found.');
            redirect(base_url() . '/superadmin/organizations');
        }
        if (!$orgs->userHasRole($adminUserId, $organizationId, 'admin')) {
            flash_set('error', 'Selected user is not an organization admin.');
            redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
        }
        $users = new User();
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'Invalid email address.');
            redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
        }
        if ($email !== null) {
            $existingEmail = $users->findByEmail($email, $organizationId);
            if ($existingEmail !== null && (int) $existingEmail['id'] !== $adminUserId) {
                flash_set('error', 'Email already used in this organization.');
                redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
            }
        }
        if ($phone !== null) {
            $existingPhone = $users->findByPhone($phone, $organizationId);
            if ($existingPhone !== null && (int) $existingPhone['id'] !== $adminUserId) {
                flash_set('error', 'Phone already used in this organization.');
                redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
            }
        }
        $users->updatePersonDetails(
            $adminUserId,
            (string) $nameParsed['first_name'],
            $nameParsed['middle_name'] ?? null,
            (string) $nameParsed['last_name'],
            $email,
            $phone
        );
        flash_set('ok', 'Admin details updated.');
        redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
    }

    public function organizationAddUser(Request $request): void
    {
        $organizationId = (int) ($_POST['organization_id'] ?? 0);
        $identity = trim((string) ($_POST['identity'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? 'admin'));
        if ($organizationId < 1 || $identity === '') {
            flash_set('error', 'Upashray/Sangh and identity are required.');
            redirect(base_url() . '/superadmin/organizations');
        }
        if (!in_array($role, ['admin'], true)) {
            flash_set('error', 'Invalid role.');
            redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
        }
        $orgs = new Organization();
        if (!$orgs->findById($organizationId)) {
            flash_set('error', 'Upashray/Sangh not found.');
            redirect(base_url() . '/superadmin/organizations');
        }
        $users = new User();
        $target = $users->findByIdentity($identity, $organizationId);
        if (!$target) {
            flash_set('error', 'No account in this organization for that email or phone. Use Create user below.');
            redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
        }
        if ((int) ($target['organization_id'] ?? 0) !== $organizationId) {
            flash_set('error', 'That account belongs to another organization. Create a new user for this org.');
            redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
        }
        $orgs->addUser($organizationId, (int) $target['id'], $role);
        flash_set('ok', 'User updated in organization.');
        redirect(base_url() . '/superadmin/organization?id=' . $organizationId);
    }

    public function organizationCreateUser(Request $request): void
    {
        $organizationId = (int) ($_POST['organization_id'] ?? 0);
        $role = 'admin';

        $respond = static function (string $type, string $message) use ($organizationId): void {
            $redirect = $organizationId > 0
                ? base_url() . '/superadmin/organization?id=' . $organizationId
                : base_url() . '/superadmin/organizations';
            if (request_wants_json()) {
                json_response([
                    'ok' => $type === 'ok',
                    'message' => $message,
                    'error' => $type === 'error' ? $message : null,
                    'redirect' => $redirect,
                ], $type === 'ok' ? 200 : 422);
            }
            flash_set($type, $message);
            redirect($redirect);
        };

        if ($organizationId < 1) {
            flash_set('error', 'Invalid organization.');
            redirect(base_url() . '/superadmin/organizations');
        }
        if (!in_array($role, ['admin'], true)) {
            $respond('error', 'Invalid role.');
        }
        $orgs = new Organization();
        if (!$orgs->findById($organizationId)) {
            flash_set('error', 'Upashray/Sangh not found.');
            redirect(base_url() . '/superadmin/organizations');
        }

        $nameParsed = parse_person_name_from_post($_POST);
        $emailRaw = trim((string) ($_POST['email'] ?? ''));
        $email = $emailRaw !== '' ? $emailRaw : null;
        $phoneResult = provisioned_phone_from_post(trim((string) ($_POST['phone'] ?? '')), true);
        if (($nameParsed['ok'] ?? false) !== true) {
            $respond('error', $nameParsed['error'] ?? 'Name is required.');
        }
        if ($email === null) {
            $respond('error', 'Email is required so login credentials can be sent.');
        }
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $respond('error', 'Invalid email address.');
        }
        if (($phoneResult['ok'] ?? false) !== true) {
            $respond('error', $phoneResult['error'] ?? 'Valid phone number is required.');
        }
        $phone = $phoneResult['phone'] ?? null;
        $name = (string) ($nameParsed['full_name'] ?? '');

        $users = new User();
        if ($email !== null && $users->findByEmail($email, $organizationId)) {
            $respond('error', 'Email already used in this organization.');
        }
        if ($phone !== null && $users->phoneIsRegistered($phone, $organizationId)) {
            $respond('error', 'Phone already used in this organization.');
        }

        release_session_lock();
        $created = UserProvisionService::createWithInviteEmail($users, [
            'organization_id' => $organizationId,
            'name' => $name,
            'first_name' => (string) ($nameParsed['first_name'] ?? ''),
            'middle_name' => $nameParsed['middle_name'] ?? null,
            'last_name' => (string) ($nameParsed['last_name'] ?? ''),
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
        ]);
        resume_session_for_flash();

        if (($created['ok'] ?? false) !== true) {
            $respond('error', $created['error'] ?? 'Could not create user.');
        }

        if (!empty($created['email_deferred'])) {
            $respond('ok', 'Upashray/Sangh admin created. Invite email is being sent — it may take a minute.');
        } elseif (!empty($created['email_sent'])) {
            $respond('ok', 'Upashray/Sangh admin created and invite email sent.');
        } else {
            $respond('ok', 'Upashray/Sangh admin created, but the invite email could not be sent. Ask them to use Forgot password on the login page.');
        }
    }

    public function organizationFamilyShow(Request $request): void
    {
        $familyId = (int) ($_GET['id'] ?? 0);
        $fromOrgId = (int) ($_GET['organization_id'] ?? 0);
        if ($familyId < 1) {
            flash_set('error', 'Invalid family.');
            redirect(base_url() . '/superadmin/organizations');
        }
        $families = new Family();
        $family = $families->findById($familyId);
        if (!$family) {
            flash_set('error', 'Family not found.');
            redirect($fromOrgId > 0 ? base_url() . '/superadmin/organization?id=' . $fromOrgId : base_url() . '/superadmin/organizations');
        }
        $canonicalId = $families->canonicalFamilyIdForHeadUserId((int) $family['head_user_id']);
        if ($canonicalId !== null && $canonicalId !== $familyId) {
            $q = 'id=' . $canonicalId;
            if ($fromOrgId > 0) {
                $q .= '&organization_id=' . $fromOrgId;
            }
            redirect(base_url() . '/superadmin/organization/family?' . $q);
        }
        if ($fromOrgId > 0 && !$families->familyIsAnchoredInOrganization($familyId, $fromOrgId)) {
            flash_set('error', 'This household is not linked to that organization (the family head is not a member there).');
            redirect(base_url() . '/superadmin/organization?id=' . $fromOrgId);
        }
        $orgs = new Organization();
        $viewOrgId = $fromOrgId > 0 ? $fromOrgId : (int) $family['organization_id'];
        $organization = $orgs->findById($viewOrgId);
        if (!$organization) {
            flash_set('error', 'Upashray/Sangh not found.');
            redirect(base_url() . '/superadmin/organizations');
        }
        $members = $families->membersWithUsers($familyId);
        $this->render('superadmin', 'organizations/family_show.php', [
            'pageTitle' => page_title('Family #' . $familyId),
            'navActive' => 'organizations',
        ], [
            'organization' => $organization,
            'family' => $family,
            'members' => $members,
            'fromOrganizationId' => $fromOrgId > 0 ? $fromOrgId : $viewOrgId,
        ]);
    }

    public function importIndex(Request $request): void
    {
        $tab = trim((string) ($_GET['tab'] ?? 'families'));
        if (!in_array($tab, ['families', 'panchang'], true)) {
            $tab = 'families';
        }
        $this->renderImportPage($tab);
    }

    public function familiesImport(Request $request): void
    {
        redirect(base_url() . '/superadmin/import?tab=families');
    }

    public function familiesImportSample(Request $request): void
    {
        (new FamilyImportService())->streamSampleCsv(true);
    }

    public function familiesImportPreview(Request $request): void
    {
        $file = $_FILES['import_file'] ?? null;
        if (!is_array($file) || !isset($file['tmp_name']) || (int) ($file['error'] ?? 1) !== 0) {
            $this->renderImportPage('families', [
                'preview' => null,
                'errors' => [t('import.families.error_upload')],
                'warnings' => [],
            ]);
            return;
        }
        $result = (new FamilyImportService())->previewFromPath((string) $file['tmp_name'], null);
        $this->renderImportPage('families', [
            'preview' => $result['preview'],
            'errors' => $result['errors'],
            'warnings' => $result['warnings'],
        ]);
    }

    public function familiesImportApply(Request $request): void
    {
        $payload = trim((string) ($_POST['valid_rows_json'] ?? ''));
        if ($payload === '') {
            flash_set('error', t('import.families.error_payload_missing'));
            redirect(base_url() . '/superadmin/import?tab=families');
        }
        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            flash_set('error', t('import.families.error_payload_invalid'));
            redirect(base_url() . '/superadmin/import?tab=families');
        }
        $rows = json_decode($decoded, true);
        if (!is_array($rows) || $rows === []) {
            flash_set('error', t('import.families.error_no_rows'));
            redirect(base_url() . '/superadmin/import?tab=families');
        }
        $actor = current_user();
        $createdBy = $actor ? (int) $actor['id'] : null;
        $result = (new FamilyImportService())->apply($rows, $createdBy);
        flash_set('ok', $result['summary']);
        redirect(base_url() . '/superadmin/import?tab=families');
    }

    public function holidaysIndex(Request $request): void
    {
        [$sort, $dir] = parse_table_sort(
            ['title', 'category', 'dates', 'notes'],
            'dates',
            'desc'
        );
        $this->render('superadmin', 'holidays/index.php', [
            'pageTitle' => page_title(t('holidays.superadmin.title')),
            'navActive' => 'holidays',
        ], [
            'holidays' => (new PlatformHoliday())->listAll($sort, $dir),
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function holidaysNew(Request $request): void
    {
        $this->renderHolidayForm(null, null);
    }

    public function holidaysStore(Request $request): void
    {
        $this->storeOrUpdateHoliday(null);
    }

    public function holidaysEdit(Request $request): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id < 1) {
            flash_set('error', t('holidays.superadmin.invalid'));
            redirect(base_url() . '/superadmin/holidays');
        }
        $holiday = (new PlatformHoliday())->findById($id);
        if ($holiday === null) {
            flash_set('error', t('holidays.superadmin.not_found'));
            redirect(base_url() . '/superadmin/holidays');
        }
        $this->renderHolidayForm($id, $holiday);
    }

    public function holidaysUpdate(Request $request): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            flash_set('error', t('holidays.superadmin.invalid'));
            redirect(base_url() . '/superadmin/holidays');
        }
        $this->storeOrUpdateHoliday($id);
    }

    public function holidaysDelete(Request $request): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            flash_set('error', t('holidays.superadmin.invalid'));
            redirect(base_url() . '/superadmin/holidays');
        }
        $model = new PlatformHoliday();
        if ($model->findById($id) === null) {
            flash_set('error', t('holidays.superadmin.not_found'));
            redirect(base_url() . '/superadmin/holidays');
        }
        $model->delete($id);
        flash_set('ok', t('holidays.superadmin.deleted'));
        redirect(base_url() . '/superadmin/holidays');
    }

    /** @param array<string,mixed>|null $holiday */
    private function renderHolidayForm(?int $id, ?array $holiday): void
    {
        $isEdit = $id !== null && $id > 0;
        $this->render('superadmin', 'holidays/form.php', [
            'pageTitle' => page_title($isEdit ? t('holidays.superadmin.edit') : t('holidays.superadmin.add')),
            'navActive' => 'holidays',
        ], [
            'formError' => null,
            'holidayId' => $id,
            'titleDraft' => (string) ($holiday['title'] ?? ''),
            'titleGuDraft' => (string) ($holiday['title_gu'] ?? ''),
            'categoryDraft' => normalize_platform_holiday_category(isset($holiday['category']) ? (string) $holiday['category'] : 'religious'),
            'startDateDraft' => (string) ($holiday['start_date'] ?? ''),
            'endDateDraft' => (string) ($holiday['end_date'] ?? ''),
            'notesDraft' => (string) ($holiday['notes'] ?? ''),
        ]);
    }

    private function storeOrUpdateHoliday(?int $id): void
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $titleGu = trim((string) ($_POST['title_gu'] ?? ''));
        $category = normalize_platform_holiday_category((string) ($_POST['category'] ?? ''));
        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        $endDate = trim((string) ($_POST['end_date'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        $renderError = function (string $message) use ($id, $title, $titleGu, $category, $startDate, $endDate, $notes): void {
            $isEdit = $id !== null && $id > 0;
            $this->render('superadmin', 'holidays/form.php', [
                'pageTitle' => page_title($isEdit ? t('holidays.superadmin.edit') : t('holidays.superadmin.add')),
                'navActive' => 'holidays',
            ], [
                'formError' => $message,
                'holidayId' => $id,
                'titleDraft' => $title,
                'titleGuDraft' => $titleGu,
                'categoryDraft' => $category,
                'startDateDraft' => $startDate,
                'endDateDraft' => $endDate,
                'notesDraft' => $notes,
            ]);
        };

        if ($title === '') {
            $renderError(t('holidays.superadmin.error_title'));
            return;
        }
        if ($startDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $renderError(t('holidays.superadmin.error_start'));
            return;
        }
        if ($endDate === '') {
            $endDate = $startDate;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $renderError(t('holidays.superadmin.error_end'));
            return;
        }
        if ($endDate < $startDate) {
            $renderError(t('holidays.superadmin.error_range'));
            return;
        }

        $payload = [
            'title' => $title,
            'title_gu' => $titleGu !== '' ? $titleGu : null,
            'category' => $category,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $notes !== '' ? $notes : null,
        ];

        $model = new PlatformHoliday();
        if ($id !== null && $id > 0) {
            if ($model->findById($id) === null) {
                flash_set('error', t('holidays.superadmin.not_found'));
                redirect(base_url() . '/superadmin/holidays');
            }
            $model->update($id, $payload);
            flash_set('ok', t('holidays.superadmin.updated'));
        } else {
            $user = current_user();
            $payload['created_by'] = $user ? (int) ($user['id'] ?? 0) : null;
            if ($payload['created_by'] !== null && $payload['created_by'] < 1) {
                $payload['created_by'] = null;
            }
            $model->create($payload);
            flash_set('ok', t('holidays.superadmin.created'));
        }

        redirect(base_url() . '/superadmin/holidays');
    }

    public function panchangImport(Request $request): void
    {
        redirect(base_url() . '/superadmin/import?tab=panchang');
    }

    public function panchangImportPreview(Request $request): void
    {
        $existingCount = (new PlatformPanchangDay())->countAll();
        $file = $_FILES['import_file'] ?? null;
        if (!is_array($file) || !isset($file['tmp_name']) || (int) ($file['error'] ?? 1) !== 0) {
            $this->renderImportPage('panchang', [
                'preview' => null,
                'errors' => [t('superadmin.import.panchang.error_upload')],
                'existingCount' => $existingCount,
            ]);

            return;
        }
        $parsed = $this->parsePanchangCsvFile((string) $file['tmp_name']);
        $this->renderImportPage('panchang', [
            'preview' => [
                'total_rows' => $parsed['total'],
                'valid_rows' => count($parsed['rows']),
                'sample' => array_slice($parsed['preview_rows'], 0, 12),
                'valid_rows_json' => json_encode($parsed['rows'], JSON_UNESCAPED_UNICODE),
            ],
            'errors' => $parsed['errors'],
            'existingCount' => $existingCount,
        ]);
    }

    public function panchangImportApply(Request $request): void
    {
        $json = trim((string) ($_POST['valid_rows_json'] ?? ''));
        if ($json === '') {
            flash_set('error', t('superadmin.import.panchang.error_no_rows'));
            redirect(base_url() . '/superadmin/import?tab=panchang');
        }
        $rows = json_decode($json, true);
        if (!is_array($rows) || $rows === []) {
            flash_set('error', t('superadmin.import.panchang.error_no_rows'));
            redirect(base_url() . '/superadmin/import?tab=panchang');
        }
        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $date = trim((string) ($row['gregorian_date'] ?? ''));
            $tithi = trim((string) ($row['tithi'] ?? ''));
            if ($date === '' || $tithi === '') {
                continue;
            }
            $festival = panchang_festival_notes_for_display(trim((string) ($row['festival_notes'] ?? '')));
            $normalized[] = [
                'gregorian_date' => $date,
                'weekday' => trim((string) ($row['weekday'] ?? '')) ?: null,
                'gujarati_month' => trim((string) ($row['gujarati_month'] ?? '')) ?: null,
                'paksha' => trim((string) ($row['paksha'] ?? '')) ?: null,
                'tithi' => $tithi,
                'festival_notes' => $festival,
            ];
        }
        if ($normalized === []) {
            flash_set('error', t('superadmin.import.panchang.error_no_rows'));
            redirect(base_url() . '/superadmin/import?tab=panchang');
        }
        $imported = (new PlatformPanchangDay())->upsertMany($normalized);
        flash_set('ok', t('superadmin.import.panchang.applied', ['count' => (string) $imported]));
        redirect(base_url() . '/superadmin/import?tab=panchang');
    }

    /** @param array<string,mixed> $data */
    private function renderImportPage(string $tab, array $data = []): void
    {
        if (!in_array($tab, ['families', 'panchang'], true)) {
            $tab = 'families';
        }
        $defaults = [
            'importTab' => $tab,
            'preview' => null,
            'errors' => [],
            'warnings' => [],
            'existingCount' => (new PlatformPanchangDay())->countAll(),
        ];
        $this->render('superadmin', 'import/index.php', [
            'pageTitle' => page_title(t('superadmin.import.title')),
            'navActive' => 'import',
        ], array_merge($defaults, $data));
    }

    /** @return array{errors:list<string>,rows:list<array<string,mixed>>,preview_rows:list<array<string,mixed>>,total:int} */
    private function parsePanchangCsvFile(string $path): array
    {
        $errors = [];
        $rows = [];
        $previewRows = [];
        $total = 0;
        $fp = fopen($path, 'rb');
        if ($fp === false) {
            return [
                'errors' => [t('superadmin.import.panchang.error_read')],
                'rows' => [],
                'preview_rows' => [],
                'total' => 0,
            ];
        }
        $header = fgetcsv($fp);
        if (!is_array($header) || $header === []) {
            fclose($fp);

            return [
                'errors' => [t('superadmin.import.panchang.error_empty')],
                'rows' => [],
                'preview_rows' => [],
                'total' => 0,
            ];
        }
        $columns = [];
        foreach ($header as $idx => $col) {
            $columns[normalize_panchang_csv_header((string) $col)] = $idx;
        }
        $dateIdx = $columns['english_date'] ?? null;
        $tithiIdx = $columns['tithi'] ?? null;
        if ($dateIdx === null || $tithiIdx === null) {
            fclose($fp);

            return [
                'errors' => [t('superadmin.import.panchang.error_columns')],
                'rows' => [],
                'preview_rows' => [],
                'total' => 0,
            ];
        }
        $dayIdx = $columns['day'] ?? null;
        $monthIdx = $columns['gujarati_month'] ?? null;
        $pakshaIdx = $columns['paksha'] ?? null;
        $festivalIdx = $columns['festival_notes'] ?? ($columns['festival_/_notes'] ?? null);

        $line = 1;
        while (($csvRow = fgetcsv($fp)) !== false) {
            $line++;
            if ($csvRow === [null] || $csvRow === false) {
                continue;
            }
            $total++;
            $englishDate = trim((string) ($csvRow[$dateIdx] ?? ''));
            $tithi = trim((string) ($csvRow[$tithiIdx] ?? ''));
            if ($englishDate === '' && $tithi === '') {
                continue;
            }
            $offset = panchang_csv_row_offset($csvRow, $dateIdx);
            $dIdx = $dateIdx + $offset;
            $tIdx = $tithiIdx + $offset;
            $englishDate = trim((string) ($csvRow[$dIdx] ?? ''));
            $tithi = trim((string) ($csvRow[$tIdx] ?? ''));
            $gregorianDate = parse_panchang_gregorian_date($englishDate);
            if ($gregorianDate === null) {
                $errors[] = t('superadmin.import.panchang.error_date', ['line' => (string) $line, 'value' => $englishDate]);
                continue;
            }
            if ($tithi === '') {
                $errors[] = t('superadmin.import.panchang.error_tithi', ['line' => (string) $line]);
                continue;
            }
            $row = [
                'gregorian_date' => $gregorianDate,
                'weekday' => $dayIdx !== null ? trim((string) ($csvRow[$dayIdx + $offset] ?? '')) : '',
                'gujarati_month' => $monthIdx !== null ? trim((string) ($csvRow[$monthIdx + $offset] ?? '')) : '',
                'paksha' => $pakshaIdx !== null ? trim((string) ($csvRow[$pakshaIdx + $offset] ?? '')) : '',
                'tithi' => $tithi,
                'festival_notes' => panchang_festival_notes_for_display(
                    $festivalIdx !== null ? trim((string) ($csvRow[$festivalIdx + $offset] ?? '')) : ''
                ) ?? '',
            ];
            $rows[] = $row;
            $previewRows[] = array_merge($row, [
                'english_date' => $englishDate,
                'summary' => panchang_day_summary($row),
            ]);
        }
        fclose($fp);

        return [
            'errors' => $errors,
            'rows' => $rows,
            'preview_rows' => $previewRows,
            'total' => $total,
        ];
    }

    public function mailTestShow(Request $request): void
    {
        $user = current_user();
        $this->render('superadmin', 'mail_test.php', [
            'pageTitle' => page_title(t('superadmin.mail_test.title')),
            'navActive' => 'mail_test',
        ], [
            'mailStatus' => mail_config_summary(),
            'defaultRecipient' => trim((string) ($user['email'] ?? '')),
            'formError' => null,
            'formOk' => null,
            'lastAttempt' => null,
        ]);
    }

    public function mailTestSend(Request $request): void
    {
        $user = current_user();
        $userId = (int) ($user['id'] ?? 0);
        $to = trim((string) ($_POST['to'] ?? ''));
        $profile = trim((string) ($_POST['smtp_profile'] ?? 'env'));

        $render = function (?string $error, ?string $ok, ?array $lastAttempt = null) use ($user, $to, $profile): void {
            $this->render('superadmin', 'mail_test.php', [
                'pageTitle' => page_title(t('superadmin.mail_test.title')),
                'navActive' => 'mail_test',
            ], [
                'mailStatus' => mail_config_summary(),
                'defaultRecipient' => $to !== '' ? $to : trim((string) ($user['email'] ?? '')),
                'formError' => $error,
                'formOk' => $ok,
                'lastAttempt' => $lastAttempt,
                'smtpProfileDraft' => $profile,
            ]);
        };

        if ($userId < 1) {
            redirect(base_url() . '/login/superadmin');
        }
        if (rate_limit_too_many('mail_test:' . $userId, 8, 3600)) {
            $render(t('superadmin.mail_test.rate_limit'), null);

            return;
        }
        if ($to === '' || !is_valid_email($to)) {
            $render(t('superadmin.mail_test.invalid_email'), null);

            return;
        }

        $summary = mail_config_summary();
        if (empty($summary['smtp_enabled'])) {
            $render(t('superadmin.mail_test.smtp_missing'), null);

            return;
        }
        if (empty($summary['pass_set'])) {
            $render(t('superadmin.mail_test.pass_missing'), null);

            return;
        }

        $overrides = [];
        if ($profile === '587_tls') {
            $overrides = ['smtp_port' => 587, 'smtp_secure' => 'tls'];
        } elseif ($profile === '465_ssl') {
            $overrides = ['smtp_port' => 465, 'smtp_secure' => 'ssl'];
        }

        $attemptPort = (int) ($overrides['smtp_port'] ?? $summary['port']);
        $attemptSecure = (string) ($overrides['smtp_secure'] ?? $summary['secure']);
        $subject = 'Test email from ' . app_name();
        $body = "Hello,\n\nIf you received this, SMTP is working on this server.\n\n"
            . "Host: " . (string) ($summary['host'] ?? '') . "\n"
            . "Port: {$attemptPort}\n"
            . "Security: {$attemptSecure}\n\n"
            . '— ' . app_name();

        release_session_lock();
        $smtpError = null;
        $sent = system_send_email_smtp($to, $subject, $body, $overrides, $smtpError);
        if (!$sent && $smtpError === null) {
            $sent = system_send_email($to, $subject, $body);
        }
        resume_session_for_flash();

        $lastAttempt = [
            'to' => $to,
            'port' => $attemptPort,
            'secure' => $attemptSecure,
            'profile' => $profile,
        ];

        if ($sent) {
            $render(null, t('superadmin.mail_test.sent', ['email' => $to]), $lastAttempt);

            return;
        }

        $message = t('superadmin.mail_test.failed');
        if ($smtpError !== null && $smtpError !== '') {
            $message .= ' ' . $smtpError;
        }
        $render($message, null, $lastAttempt);
    }
}
