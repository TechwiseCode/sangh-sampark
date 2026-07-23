<?php

declare(strict_types=1);

/**
 * Backfill organizations.member_initials and convert old random member_code
 * values to initials+serial (e.g. AJ101).
 *
 * Usage: php database/scripts/backfill_member_initials_codes.php
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\MembershipCodeService;

$pdo = Database::pdo();
$svc = new MembershipCodeService();

$orgs = $pdo->query('SELECT id, org_code, name FROM organizations ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
echo 'Organizations: ' . count($orgs) . PHP_EOL;

foreach ($orgs as $org) {
    $id = (int) $org['id'];
    $initials = $svc->ensureOrganizationMemberInitials($id);
    echo sprintf("  %s (#%d) initials=%s\n", $org['org_code'], $id, $initials);
}

$members = $pdo->query(
    "SELECT id, organization_id, member_code
     FROM users
     WHERE organization_id IS NOT NULL
       AND member_code IS NOT NULL
       AND member_code <> ''
     ORDER BY organization_id ASC, id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$upd = $pdo->prepare('UPDATE users SET member_code = ? WHERE id = ?');
$converted = 0;

foreach ($members as $row) {
    $code = strtoupper(trim((string) $row['member_code']));
    // Already sequential: AJ101
    if (preg_match('/^[A-Z]{2,4}\d{3,}$/', $code) === 1) {
        continue;
    }
    $orgId = (int) $row['organization_id'];
    $newCode = $svc->generateMemberCode($orgId);
    $upd->execute([$newCode, (int) $row['id']]);
    echo sprintf("  user #%d %s -> %s\n", (int) $row['id'], $code, $newCode);
    $converted++;
}

echo "Converted member codes: {$converted}" . PHP_EOL;
echo 'Done.' . PHP_EOL;
