<?php

declare(strict_types=1);

/**
 * One-off: add Mahavir Trust sanghs missing from organizations.
 * Run: php scripts/import_missing_mahavir_sanghs.php
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Models\Organization;

$toAdd = [
    [
        'name' => 'શ્રી સૌરાષ્ટ્ર સ્થાનકવાસી જૈન સંઘ',
        'nickname' => 'સૌરાષ્ટ્ર સંઘ',
        'address' => 'ઘીકાંટા, નગરશેઠનો વંડો, અમદાવાદ - 380001',
        'org_code' => 'C41',
        'member_initials' => 'SA',
    ],
    [
        'name' => 'શ્રી શાહપુર દરિયાપુરી આઠકોટી સ્થાનકવાસી જૈન સંઘ',
        'nickname' => 'શાહપુર આઠકોટી',
        'address' => 'ચુનારાનો ખાંચો, શાહપુર, અમદાવાદ - 380001',
        'org_code' => 'C42',
        'member_initials' => 'SP',
    ],
    [
        'name' => 'શ્રી અમદાવાદ સ્થાનકવાસી જૈન છ કોટી સંઘ',
        'nickname' => 'અમદાવાદ છ કોટી',
        'address' => 'સારંગપુર, દોલતખાના, અમદાવાદ - 380001',
        'org_code' => 'C43',
        'member_initials' => 'AD',
    ],
    [
        'name' => 'શ્રી સરસપુર શ્વેતાંબર દરિયાપુરી આઠ કોટી સ્થાનકવાસી જૈન સંઘ',
        'nickname' => 'સરસપુર આઠ કોટી',
        'address' => 'નિકોલ દરવાજા બહાર, સરસપુર, અમદાવાદ - 380018',
        'org_code' => 'C44',
        'member_initials' => 'SR',
    ],
    [
        'name' => 'શ્રી સારંગપુર દરિયાપુરી આઠકોટી સ્થાનકવાસી જૈન સંઘ',
        'nickname' => 'સારંગપુર તળીયાની પોળ',
        'address' => 'નાણાવટીનો ખાંચો, તળીયાની પોળ, સારંગપુર, અમદાવાદ - 380001',
        'org_code' => 'C45',
        'member_initials' => 'SG',
    ],
    [
        'name' => 'શ્રી વર્ધમાન સ્થાનકવાસી જૈન સંઘ (લાંભા)',
        'nickname' => 'લાંભા વર્ધમાન સંઘ',
        'address' => 'હેમચંદ્રાચાર્યનગર, લાંભા, અમદાવાદ - 382405',
        'org_code' => 'C46',
        'member_initials' => 'LB',
    ],
    [
        'name' => 'શ્રી ગોતા સ્થાનકવાસી જૈન સંઘ',
        'nickname' => 'ગોતા સંઘ',
        'address' => 'ગાંધી વસાહત, ગોતા, અમદાવાદ - 382481',
        'org_code' => 'C47',
        'member_initials' => 'GT',
    ],
    [
        'name' => 'શ્રી ગાંધીનગર વર્ધમાન સ્થાનકવાસી જૈન સંઘ (સેક્ટર 6)',
        'nickname' => 'ગાંધીનગર સેક્ટર-6',
        'address' => 'પ્લોટ નં. 446/2, સેક્ટર-6A, ગાંધીનગર - 382006',
        'org_code' => 'C48',
        'member_initials' => 'GS',
    ],
    [
        'name' => 'શ્રી ગાંધીનગર સ્થાનકવાસી જૈન સંઘ (સેક્ટર 22)',
        'nickname' => 'ગાંધીનગર સેક્ટર-22',
        'address' => 'પ્લોટ નં. 533, સેક્ટર-22, ગાંધીનગર - 382022',
        'org_code' => 'C49',
        'member_initials' => 'GF',
    ],
    [
        'name' => 'શ્રી સુધર્મા સ્થાનકવાસી જૈન સંઘ (મણિનગર)',
        'nickname' => 'સુધર્મા મણિનગર',
        'address' => 'રાજ ચેમ્બર્સ, ગોરનો કૂવો, મણિનગર (પૂર્વ), અમદાવાદ',
        'org_code' => 'C50',
        'member_initials' => 'SD',
    ],
    [
        'name' => 'શ્રી વર્ધમાન સ્થાનકવાસી જૈન ટ્રસ્ટ - સંઘ (નવા નરોડા)',
        'nickname' => 'નવા નરોડા વર્ધમાન',
        'address' => '11, વેદાંત બંગ્લોઝ, હરિદર્શન ચાર રસ્તા, નવા નરોડા, અમદાવાદ - 382330',
        'org_code' => 'C51',
        'member_initials' => 'ND',
    ],
];

$orgs = new Organization();
$added = 0;
$skipped = 0;

foreach ($toAdd as $row) {
    if ($orgs->orgCodeIsTaken($row['org_code'])) {
        echo "SKIP code taken {$row['org_code']}: {$row['name']}\n";
        $skipped++;
        continue;
    }

    // Skip if same Gujarati name already exists (safety).
    $all = $orgs->listAll();
    $exists = false;
    foreach ($all as $existing) {
        if (trim((string) ($existing['name'] ?? '')) === $row['name']) {
            $exists = true;
            break;
        }
    }
    if ($exists) {
        echo "SKIP name exists: {$row['name']}\n";
        $skipped++;
        continue;
    }

    $id = $orgs->create(
        $row['name'],
        null,
        $row['org_code'],
        $row['nickname'],
        $row['address'],
        $row['member_initials'],
        null
    );
    echo "ADDED id={$id} {$row['org_code']} {$row['member_initials']} {$row['name']}\n";
    $added++;
}

echo "\nDone. added={$added} skipped={$skipped}\n";
