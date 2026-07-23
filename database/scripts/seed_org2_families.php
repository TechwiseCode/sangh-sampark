<?php

declare(strict_types=1);

/**
 * Seed 4 test families for organization 2 (2–4 login members each).
 * Usage: php database/scripts/seed_org2_families.php
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Core\Database;
use App\Models\Due;
use App\Models\Family;
use App\Models\User;

const ORG_ID = 2;
const TEST_PASSWORD = 'Test@12345';

$familySpecs = [
    ['surname' => 'Sharma', 'size' => 2],
    ['surname' => 'Patel', 'size' => 3],
    ['surname' => 'Reddy', 'size' => 4],
    ['surname' => 'Iyer', 'size' => 3],
];

$memberRoles = ['spouse', 'son', 'daughter', 'son'];

$pdo = Database::pdo();
$org = $pdo->prepare('SELECT id, name, org_code FROM organizations WHERE id = ?');
$org->execute([ORG_ID]);
$orgRow = $org->fetch(PDO::FETCH_ASSOC);
if ($orgRow === false) {
    fwrite(STDERR, "Organization " . ORG_ID . " not found.\n");
    exit(1);
}

$users = new User();
$families = new Family();
$dueModel = new Due();
$createdFamilies = [];

foreach ($familySpecs as $i => $spec) {
    $size = (int) $spec['size'];
    $surname = (string) $spec['surname'];
    $slug = strtolower($surname);
    $familyNum = $i + 1;

    $headEmail = "org2.f{$familyNum}.head.{$slug}@example.test";
    if ($users->findByEmail($headEmail, ORG_ID) !== null) {
        echo "Skip family {$surname}: head {$headEmail} already exists.\n";
        continue;
    }

    $headId = $users->create([
        'organization_id' => ORG_ID,
        'name' => "Ravi {$surname}",
        'email' => $headEmail,
        'phone' => sprintf('+9198765%04d', 2000 + $familyNum),
        'password' => TEST_PASSWORD,
        'role' => 'member',
    ]);

    $familyId = $families->create(ORG_ID, $headId, null);
    $families->addMember($familyId, $headId, 'head', null);

    $memberIds = [$headId];
    $firstNames = ['Priya', 'Arjun', 'Meera', 'Kiran'];

    for ($m = 1; $m < $size; $m++) {
        $role = $memberRoles[$m - 1] ?? 'son';
        $fname = $firstNames[($m - 1) % count($firstNames)];
        $email = "org2.f{$familyNum}.m{$m}.{$slug}@example.test";
        $memberId = $users->create([
            'organization_id' => ORG_ID,
            'name' => "{$fname} {$surname}",
            'email' => $email,
            'phone' => sprintf('+9198765%04d', 2100 + ($familyNum * 10) + $m),
            'password' => TEST_PASSWORD,
            'role' => 'member',
        ]);
        $relatedTo = $role === 'spouse' ? $headId : $headId;
        $families->addMember($familyId, $memberId, $role, $relatedTo);
        $memberIds[] = $memberId;
    }

    $count = $families->householdMemberCount($familyId);
    $createdFamilies[] = [
        'family_id' => $familyId,
        'surname' => $surname,
        'members' => $count,
        'head_email' => $headEmail,
    ];
    echo "Created family #{$familyId} ({$surname}): {$count} members — head {$headEmail}\n";
}

if ($createdFamilies !== []) {
    $dueModel->syncMembershipChargesForOrganization(ORG_ID);
    echo "\nSynced membership dues for org " . ORG_ID . " ({$orgRow['name']}, code {$orgRow['org_code']}).\n";
    echo "Password for all test members: " . TEST_PASSWORD . "\n";
    echo "Created " . count($createdFamilies) . " families.\n";
} else {
    echo "No new families created (may already exist).\n";
}
