<?php

declare(strict_types=1);

/**
 * Per-org deploy config. See docs/ORG-DEPLOYMENT.md and .env.example.
 */
return [
    'env' => getenv('APP_ENV') ?: 'development',
    'app_name' => getenv('APP_NAME') ?: 'SanghSampark',
    'app_mode' => getenv('APP_MODE') ?: 'control_plane',
    'base_url' => getenv('APP_URL') ?: (getenv('SAAS_BASE_URL') ?: ''),
    'asset_base_url' => getenv('ASSET_URL') ?: (getenv('SAAS_ASSET_BASE_URL') ?: ''),
    'org_code' => getenv('ORG_CODE') ?: '',
    'org_name' => getenv('ORG_NAME') ?: '',
    'organization_id' => (int) (getenv('ORGANIZATION_ID') ?: 1),
    'session_name' => getenv('SESSION_NAME') ?: 'SANGHSAMPARK_SESS',
    'mail_from' => getenv('MAIL_FROM') ?: 'no-reply@sanghsampark.com',
    'mail' => require __DIR__ . '/mail.php',
    'subtle_accent' => filter_var(getenv('SUBTLE_ACCENT') !== false ? getenv('SUBTLE_ACCENT') : 'true', FILTER_VALIDATE_BOOLEAN),
    'vapid_public_key' => getenv('VAPID_PUBLIC_KEY') ?: '',
    'vapid_private_key' => getenv('VAPID_PRIVATE_KEY') ?: '',
    'vapid_subject' => getenv('VAPID_SUBJECT') ?: '',
    // Member → admin session chat (temp off; set MEMBER_ADMIN_CHAT=true to re-enable)
    'member_admin_chat' => filter_var(getenv('MEMBER_ADMIN_CHAT') !== false ? getenv('MEMBER_ADMIN_CHAT') : 'false', FILTER_VALIDATE_BOOLEAN),
    'timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Kolkata',
    'daily_tithi_notifications' => filter_var(getenv('DAILY_TITHI_NOTIFICATIONS') !== false ? getenv('DAILY_TITHI_NOTIFICATIONS') : 'true', FILTER_VALIDATE_BOOLEAN),
    'daily_tithi_hour' => (int) (getenv('DAILY_TITHI_HOUR') ?: 7),
    'daily_tithi_minute' => (int) (getenv('DAILY_TITHI_MINUTE') ?: 0),
    'daily_tithi_window_minutes' => (int) (getenv('DAILY_TITHI_WINDOW_MINUTES') ?: 30),
    'scheduled_tithi_reminders' => filter_var(getenv('SCHEDULED_TITHI_REMINDERS') !== false ? getenv('SCHEDULED_TITHI_REMINDERS') : 'true', FILTER_VALIDATE_BOOLEAN),
    'scheduled_tithi_hour' => (int) (getenv('SCHEDULED_TITHI_HOUR') ?: 23),
    'scheduled_tithi_minute' => (int) (getenv('SCHEDULED_TITHI_MINUTE') ?: 0),
    'scheduled_tithi_window_minutes' => (int) (getenv('SCHEDULED_TITHI_WINDOW_MINUTES') ?: 30),
    'calendar_day_notifications' => filter_var(getenv('CALENDAR_DAY_NOTIFICATIONS') !== false ? getenv('CALENDAR_DAY_NOTIFICATIONS') : 'true', FILTER_VALIDATE_BOOLEAN),
];
