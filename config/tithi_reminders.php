<?php

declare(strict_types=1);

/**
 * Pre-fixed tithi names for evening reminders (11 PM day before).
 * Values must match the `tithi` column in platform panchang CSV (base name is enough).
 *
 * Override via .env:
 *   TITHI_REMINDER_TITHIS=Pancham,Atham,Poonam,Amas,Chaudas
 */
$fromEnv = trim((string) (getenv('TITHI_REMINDER_TITHIS') ?: ''));
$tithis = $fromEnv !== ''
    ? array_values(array_filter(array_map('trim', explode(',', $fromEnv)), static fn (string $v): bool => $v !== ''))
    : [
        'Pancham',
        'Atham',
        'Chaudas',
        'Poonam',
        'Amas',
    ];

return [
    'tithis' => $tithis,
];
