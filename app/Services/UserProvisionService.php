<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmailVerificationToken;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserProfile;

use function base_url;
use function build_person_full_name;
use function copy_user_profile_photo_for_user;
use function is_valid_email;
use function normalize_email;
use function normalize_phone;
use function normalize_stored_password_hash;
use function parse_person_name_from_post;
use function provisioned_phone_from_post;
use function queue_deferred_email;
use function send_invite_email_with_password;
use function send_multi_org_membership_email;
use function generate_temporary_otp_password;

final class UserProvisionService
{
    public static function generateInvitePassword(): string
    {
        return generate_temporary_otp_password();
    }

    public static function parseNewPersonFields(array $post, string $keyPrefix = 'new_', array $messages = [], bool $requirePassword = true): array
    {
        $msg = array_merge([
            'need_name_pass' => 'Enter name and password (8+ characters).',
            'need_phone' => 'Valid phone number is required.',
            'bad_email' => 'Check the email.',
        ], $messages);

        $nameParsed = parse_person_name_from_post($post, $keyPrefix === '' ? '' : $keyPrefix);
        if (($nameParsed['ok'] ?? false) !== true) {
            return ['ok' => false, 'error' => $nameParsed['error'] ?? ($requirePassword ? $msg['need_name_pass'] : 'Name is required.')];
        }

        if ($keyPrefix === '') {
            $emailRaw = trim((string) ($post['email'] ?? ''));
            $email = normalize_email($emailRaw);
            $phoneRaw = trim((string) ($post['phone'] ?? ''));
            $password = (string) ($post['password'] ?? '');
        } else {
            $emailRaw = trim((string) ($post[$keyPrefix . 'email'] ?? ''));
            $email = normalize_email($emailRaw);
            $phoneRaw = trim((string) ($post[$keyPrefix . 'phone'] ?? ''));
            $password = (string) ($post[$keyPrefix . 'password'] ?? '');
        }

        if ($requirePassword && strlen($password) < 8) {
            return ['ok' => false, 'error' => $msg['need_name_pass']];
        }
        $phoneResult = provisioned_phone_from_post($phoneRaw, true);
        if (($phoneResult['ok'] ?? false) !== true) {
            return ['ok' => false, 'error' => $phoneResult['error'] ?? $msg['need_phone']];
        }
        $phone = $phoneResult['phone'] ?? null;
        if ($email !== null && !is_valid_email($email)) {
            return ['ok' => false, 'error' => $msg['bad_email']];
        }

        return [
            'ok' => true,
            'name' => (string) ($nameParsed['full_name'] ?? ''),
            'first_name' => (string) ($nameParsed['first_name'] ?? ''),
            'middle_name' => $nameParsed['middle_name'] ?? null,
            'last_name' => (string) ($nameParsed['last_name'] ?? ''),
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
        ];
    }

    public static function createUniqueUser(User $users, array $fields, array $dupMessages = []): array
    {
        $dup = array_merge([
            'duplicate_email' => 'Email already in use.',
            'duplicate_phone' => 'Phone already in use.',
        ], $dupMessages);

        $organizationId = isset($fields['organization_id']) ? (int) $fields['organization_id'] : null;
        $role = (string) ($fields['role'] ?? 'member');
        if ($role !== 'superadmin' && ($organizationId === null || $organizationId < 1)) {
            return ['ok' => false, 'error' => 'Organization is required.'];
        }
        if ($fields['phone'] === null || $fields['phone'] === '') {
            return ['ok' => false, 'error' => 'Valid phone number is required.'];
        }
        if ($fields['email'] !== null && $users->findByEmail($fields['email'], $organizationId)) {
            return ['ok' => false, 'error' => $dup['duplicate_email']];
        }
        if ($fields['phone'] !== null && $users->phoneIsRegistered($fields['phone'], $organizationId)) {
            return ['ok' => false, 'error' => $dup['duplicate_phone']];
        }

        $id = $users->create([
            'organization_id' => $organizationId,
            'name' => $fields['name'],
            'first_name' => $fields['first_name'] ?? null,
            'middle_name' => $fields['middle_name'] ?? null,
            'last_name' => $fields['last_name'] ?? null,
            'email' => $fields['email'],
            'phone' => $fields['phone'],
            'password' => $fields['password'],
            'role' => $role,
        ]);

        return ['ok' => true, 'id' => $id];
    }

    /**
     * Creates a user with a generated password and emails credentials (email required).
     * If the same email already exists in another org, copies that password + profile and sends a multi-org notice.
     *
     * @return array{ok:bool, id?:int, email_sent?:bool|null, email_deferred?:bool, password_copied?:bool, profile_copied?:bool, error?:string}
     */
    public static function createWithInviteEmail(User $users, array $fields, array $dupMessages = [], bool $deferEmail = true): array
    {
        $email = $fields['email'] ?? null;
        if ($email === null || $email === '') {
            return ['ok' => false, 'error' => 'Email is required so login credentials can be sent.'];
        }
        if (!isset($fields['phone']) || $fields['phone'] === null || $fields['phone'] === '') {
            return ['ok' => false, 'error' => 'Valid phone number is required.'];
        }
        $organizationId = isset($fields['organization_id']) ? (int) $fields['organization_id'] : 0;
        $sibling = self::resolveSiblingForReuse($users, (string) $email, $organizationId);
        $siblingHash = '';
        if ($sibling !== null) {
            $siblingHash = normalize_stored_password_hash((string) ($sibling['password'] ?? ''));
        }
        $reuseExistingPassword = $siblingHash !== '';

        $plainPassword = self::generateInvitePassword();
        $role = (string) ($fields['role'] ?? 'member');
        if ($role !== 'admin') {
            $role = 'member';
        }
        $created = self::createUniqueUser($users, [
            'organization_id' => $fields['organization_id'] ?? null,
            'name' => $fields['name'],
            'first_name' => $fields['first_name'] ?? null,
            'middle_name' => $fields['middle_name'] ?? null,
            'last_name' => $fields['last_name'] ?? null,
            'email' => $email,
            'phone' => $fields['phone'],
            'password' => $plainPassword,
            'role' => $role,
            'must_change_password' => !$reuseExistingPassword,
        ], $dupMessages);
        if (($created['ok'] ?? false) !== true) {
            return $created;
        }
        $userId = (int) $created['id'];
        $profileCopied = false;
        if ($reuseExistingPassword && $sibling !== null) {
            $users->updatePasswordHash($userId, $siblingHash);
            $profileCopied = (new UserProfile())->copyFromUser((int) $sibling['id'], $userId);
            $locale = trim((string) ($sibling['preferred_locale'] ?? ''));
            if ($locale !== '') {
                $users->updatePreferredLocale($userId, $locale);
            }
            $photoPath = copy_user_profile_photo_for_user(
                (int) $sibling['id'],
                $userId,
                isset($sibling['photo_path']) ? (string) $sibling['photo_path'] : null
            );
            if ($photoPath !== null) {
                $users->updatePhotoPath($userId, $photoPath);
            }
        }

        $displayName = build_person_full_name(
            (string) ($fields['first_name'] ?? ''),
            $fields['middle_name'] ?? null,
            (string) ($fields['last_name'] ?? ($fields['name'] ?? ''))
        ) ?: (string) $fields['name'];

        $orgCode = null;
        $organizationName = 'Upashray/Sangh';
        if ($organizationId > 0) {
            $org = (new Organization())->findById($organizationId);
            if ($org !== null) {
                $orgCode = strtoupper(trim((string) ($org['org_code'] ?? '')));
                $organizationName = trim((string) ($org['name'] ?? '')) !== ''
                    ? (string) $org['name']
                    : $organizationName;
            }
        }
        $loginUrl = base_url() . '/login';
        $profileUrl = base_url() . '/organization/profile';

        if ($reuseExistingPassword) {
            $emailResult = self::dispatchInviteEmail(
                $deferEmail,
                'multi_org_membership',
                [
                    'email' => (string) $email,
                    'name' => $displayName,
                    'organization_name' => $organizationName,
                    'org_code' => $orgCode !== '' ? $orgCode : null,
                    'login_url' => $loginUrl,
                    'profile_url' => $profileUrl,
                ],
                static function () use ($email, $displayName, $organizationName, $orgCode, $loginUrl, $profileUrl): bool {
                    return send_multi_org_membership_email(
                        (string) $email,
                        $displayName,
                        $organizationName,
                        $orgCode !== '' ? $orgCode : null,
                        $loginUrl,
                        $profileUrl
                    );
                }
            );

            return [
                'ok' => true,
                'id' => $userId,
                'email_sent' => $emailResult['email_sent'],
                'email_deferred' => $emailResult['email_deferred'],
                'password_copied' => true,
                'profile_copied' => $profileCopied,
            ];
        }

        $token = (new EmailVerificationToken())->createForUser($userId);
        $verifyUrl = $token !== ''
            ? base_url() . '/verify-email?token=' . urlencode($token)
            : base_url() . '/login';
        $emailResult = self::dispatchInviteEmail(
            $deferEmail,
            'invite_with_password',
            [
                'email' => (string) $email,
                'name' => $displayName,
                'password' => $plainPassword,
                'verify_url' => $verifyUrl,
                'org_code' => $orgCode !== '' ? $orgCode : null,
                'login_url' => $loginUrl,
            ],
            static function () use ($email, $displayName, $plainPassword, $verifyUrl, $orgCode, $loginUrl): bool {
                return send_invite_email_with_password(
                    (string) $email,
                    $displayName,
                    $plainPassword,
                    $verifyUrl,
                    $orgCode !== '' ? $orgCode : null,
                    $loginUrl
                );
            }
        );

        return [
            'ok' => true,
            'id' => $userId,
            'email_sent' => $emailResult['email_sent'],
            'email_deferred' => $emailResult['email_deferred'],
            'password_copied' => false,
            'profile_copied' => false,
        ];
    }

    /**
     * @param array<string,mixed> $queueData
     * @return array{email_sent:bool|null, email_deferred:bool}
     */
    private static function dispatchInviteEmail(bool $deferEmail, string $queueType, array $queueData, callable $sendNow): array
    {
        if ($deferEmail) {
            $queued = queue_deferred_email($queueType, $queueData);
            if (!empty($queued['queued']) && !empty($queued['sent'])) {
                return ['email_sent' => true, 'email_deferred' => false];
            }
            if (!empty($queued['queued'])) {
                return ['email_sent' => null, 'email_deferred' => true];
            }
            error_log('Could not queue invite email for ' . (string) ($queueData['email'] ?? ''));

            return ['email_sent' => null, 'email_deferred' => false];
        }

        $sent = (bool) $sendNow();

        return ['email_sent' => $sent, 'email_deferred' => false];
    }

    /**
     * Prefer sibling with a complete profile; otherwise any same-email account with a password.
     *
     * @return array<string,mixed>|null
     */
    private static function resolveSiblingForReuse(User $users, string $email, int $excludeOrganizationId): ?array
    {
        $memberships = $users->listMembershipsByEmail($email);
        $candidates = [];
        foreach ($memberships as $m) {
            $oid = (int) ($m['organization_id'] ?? 0);
            if ($excludeOrganizationId > 0 && $oid === $excludeOrganizationId) {
                continue;
            }
            $uid = (int) ($m['user_id'] ?? 0);
            if ($uid < 1) {
                continue;
            }
            $row = $users->findById($uid);
            if ($row !== null) {
                $candidates[] = $row;
            }
        }
        if ($candidates === []) {
            return $users->findSiblingByEmail($email, $excludeOrganizationId > 0 ? $excludeOrganizationId : null);
        }
        $profiles = new UserProfile();
        foreach ($candidates as $row) {
            if ($profiles->isCompleteForUser((int) $row['id'])) {
                return $row;
            }
        }
        foreach ($candidates as $row) {
            if (normalize_stored_password_hash((string) ($row['password'] ?? '')) !== '') {
                return $row;
            }
        }

        return $candidates[0];
    }
}
