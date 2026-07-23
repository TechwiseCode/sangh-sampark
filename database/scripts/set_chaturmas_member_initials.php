<?php

declare(strict_types=1);

/**
 * Set memorable place-based member_initials for Chaturmas C01–C40 orgs.
 * Usage: php database/scripts/set_chaturmas_member_initials.php
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\MembershipCodeService;

$map = [
    'C01' => 'MN', // Maninagar
    'C02' => 'RJ', // Rajasthan SS
    'C03' => 'VV', // Vardhaman Vasna
    'C04' => 'EB', // Ellisbridge
    'C05' => 'NV', // Naval-Prakash
    'C06' => 'CG', // Champakguru
    'C07' => 'LV', // Lavanya
    'C08' => 'JV', // Jivraj Park
    'C09' => 'AW', // Ambawadi
    'C10' => 'ST', // Satellite
    'C11' => 'PR', // Prerana / Jodhpur
    'C12' => 'DL', // Dharmalay
    'C13' => 'AN', // Anandnagar
    'C14' => 'BP', // Bopal
    'C15' => 'SB', // South Bopal
    'C16' => 'KW', // Kathiawad
    'C17' => 'VP', // Vastrapur
    'C18' => 'TT', // Thaltej
    'C19' => 'MM', // Memnagar
    'C20' => 'NR', // Navrangpura
    'C21' => 'SL', // Sola
    'C22' => 'GL', // Ghatlodia
    'C23' => 'NN', // Nirnaynagar
    'C24' => 'KT', // Kalpataru
    'C25' => 'SH', // Shahibaug
    'C26' => 'GG', // Girdharnagar
    'C27' => 'RS', // Rajasthan Shahibaug
    'C28' => 'BN', // Bapunagar
    'C29' => 'KN', // Krishnanagar
    'C30' => 'CP', // Chipapol
    'C31' => 'SM', // Sabarmati
    'C32' => 'VN', // Vijaynagar
    'C33' => 'AK', // Ankur Ajaramar
    'C34' => 'NA', // Naranpura
    'C35' => 'TA', // Tarabai
    'C36' => 'BT', // Botad
    'C37' => 'NW', // Navavadaj
    'C38' => 'DB', // Devbhumi
    'C39' => 'CK', // Chanakyapuri
    'C40' => 'GN', // Ghanshyamnagar
];

$pdo = Database::pdo();
$pdo->exec('UPDATE organizations SET member_initials = NULL');
$upd = $pdo->prepare('UPDATE organizations SET member_initials = ? WHERE org_code = ?');
foreach ($map as $code => $ini) {
    $upd->execute([$ini, $code]);
    echo "{$code} => {$ini}\n";
}

$svc = new MembershipCodeService();
$members = $pdo->query(
    "SELECT id, organization_id, member_code FROM users
     WHERE member_code IS NOT NULL AND member_code <> ''
     ORDER BY id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$u = $pdo->prepare('UPDATE users SET member_code = ? WHERE id = ?');
foreach ($members as $row) {
    $u->execute([null, (int) $row['id']]);
    $new = $svc->generateMemberCode((int) $row['organization_id']);
    $u->execute([$new, (int) $row['id']]);
    echo 'user #' . $row['id'] . ' ' . $row['member_code'] . ' -> ' . $new . PHP_EOL;
}

echo 'Done.' . PHP_EOL;
