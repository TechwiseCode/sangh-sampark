<?php

declare(strict_types=1);

/**
 * Import 46 Maninagar-directory sanghs + presence (sadhvis) from extracted JSON.
 *
 * Usage:
 *   php database/scripts/import_maninagar_directory.php --apply
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Core\Database;
use App\Models\Organization;
use App\Models\OrgPresence;

$apply = in_array('--apply', $argv ?? [], true);
$jsonPath = BASE_PATH . '/storage/tmp_pdf_pages/extracted.json';

if (!is_file($jsonPath)) {
    fwrite(STDERR, "Missing {$jsonPath}. Build extracted.json first.\n");
    exit(1);
}

$data = json_decode((string) file_get_contents($jsonPath), true);
if (!is_array($data) || !isset($data['organizations']) || !is_array($data['organizations'])) {
    fwrite(STDERR, "Invalid extracted.json\n");
    exit(1);
}

$pdo = Database::pdo();
$superId = (int) $pdo->query("SELECT id FROM users WHERE role = 'superadmin' ORDER BY id ASC LIMIT 1")->fetchColumn();
if ($superId < 1) {
    fwrite(STDERR, "No superadmin found.\n");
    exit(1);
}

$orgs = new Organization();
$presence = new OrgPresence();

echo 'Organizations in file: ' . count($data['organizations']) . PHP_EOL;
echo 'Superadmin id: ' . $superId . PHP_EOL;

if (!$apply) {
    echo "Dry run. Pass --apply to import.\n";
    exit(0);
}

$usedInitials = [];
foreach ($pdo->query('SELECT member_initials FROM organizations WHERE member_initials IS NOT NULL') as $row) {
    $usedInitials[strtoupper((string) $row['member_initials'])] = true;
}

$codeNum = 1;
$imported = 0;
foreach ($data['organizations'] as $row) {
    $name = trim((string) ($row['name'] ?? ''));
    if ($name === '') {
        continue;
    }
    $nickname = trim((string) ($row['nickname'] ?? ''));
    if ($nickname === '') {
        $nickname = $name;
    }
    $address = trim((string) ($row['address'] ?? ''));
    $address = $address !== '' ? $address : null;
    $sadhvis = [];
    if (isset($row['sadhvis']) && is_array($row['sadhvis'])) {
        foreach ($row['sadhvis'] as $s) {
            $s = trim((string) $s);
            if ($s !== '') {
                $sadhvis[] = $s;
            }
        }
    }

    $orgCode = sprintf('C%02d', $codeNum);
    while ($orgs->orgCodeIsTaken($orgCode)) {
        $codeNum++;
        $orgCode = sprintf('C%02d', $codeNum);
    }
    $initials = suggestInitials($nickname !== '' ? $nickname : $name, $usedInitials);
    $usedInitials[$initials] = true;

    $orgId = $orgs->create($name, $superId, $orgCode, $nickname, $address, $initials, null);
    if ($sadhvis !== []) {
        $presence->replaceCurrent($orgId, $superId, $sadhvis);
    }
    echo sprintf(
        "OK %s %s | sadhvis=%d | %s\n",
        $orgCode,
        $initials,
        count($sadhvis),
        $nickname
    );
    $imported++;
    $codeNum++;
}

echo "\nImported {$imported} organizations.\n";

/**
 * @param array<string,bool> $used
 */
function suggestInitials(string $label, array &$used): string
{
    $map = [
        'મણિનગર' => 'MN', 'રાજસ્થાન એસ' => 'RJ', 'વાસણા' => 'VV', 'એલિસબ્રીજ' => 'EB',
        'નવલપ્રકાશ' => 'NV', 'ચંપકગુરુ' => 'CG', 'લાવણ્ય' => 'LV', 'જીવરાજ' => 'JV',
        'આંબાવાડી' => 'AW', 'સેટેલાઇટ' => 'ST', 'જોધપુર' => 'PR', 'ધર્માલય' => 'DL',
        'આનંદનગર' => 'AN', 'બોપલ' => 'BP', 'સાઉથ બોપલ' => 'SB', 'કાઠીયાવાડી' => 'KW',
        'વસ્ત્રાપુર' => 'VP', 'થલતેજ' => 'TT', 'મેમનગર' => 'MM', 'નવરંગપુરા' => 'NR',
        'સોલા' => 'SL', 'ઘાટલોડીયા' => 'GL', 'નિર્ણયનગર' => 'NN', 'કલ્પતરૂ' => 'KT',
        'વિજયનગર' => 'VN', 'અજરામર સ્થા. જૈન સંઘ' => 'AJ', 'નારણપુરા' => 'NA',
        'તારાબાઈ' => 'TA', 'બોતાદ' => 'BT', 'નવાવાડજ' => 'NW', 'દેવભૂમિ' => 'DB',
        'ચાણક્યપુરી' => 'CK', 'ઘનશ્યામનગર' => 'GN', 'શાહીબાગ' => 'SH', 'ગીરધરનગર' => 'GG',
        'હઠીસિંગ' => 'RS', 'બાપુનગર' => 'BN', 'કૃષ્ણનગર' => 'KN', 'છીપાપોળ' => 'CP',
        'સાબરમતી' => 'SM', 'રામનગર' => 'RM', 'સે. ૬' => 'GS', 'સે. ૨૨' => 'GF',
        'ગોતા' => 'GT', 'નવા નરોડા' => 'ND', 'સુધર્મા' => 'SD', 'વિરતીધર' => 'VD',
    ];
    foreach ($map as $needle => $code) {
        if (mb_strpos($label, $needle) !== false && !isset($used[$code])) {
            return $code;
        }
    }
    for ($len = 2; $len <= 3; $len++) {
        $max = (int) (26 ** $len);
        for ($n = 0; $n < $max; $n++) {
            $code = '';
            $x = $n;
            for ($i = 0; $i < $len; $i++) {
                $code = chr(65 + ($x % 26)) . $code;
                $x = intdiv($x, 26);
            }
            if (!isset($used[$code])) {
                return $code;
            }
        }
    }

    return 'XX';
}
