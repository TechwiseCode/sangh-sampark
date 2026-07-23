<?php

declare(strict_types=1);

/**
 * Full reset: delete all orgs, members, families, receipts, etc.
 * Keeps exactly one superadmin (by --email= or lowest id).
 *
 * Usage:
 *   php database/scripts/reset_keep_superadmin.php
 *   php database/scripts/reset_keep_superadmin.php --apply
 *   php database/scripts/reset_keep_superadmin.php --apply --email=admin@example.com
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Core\Database;

$apply = in_array('--apply', $argv ?? [], true);
$keepEmail = null;
foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--email=') === 0) {
        $keepEmail = trim(substr($arg, 8));
    }
}

$tablesToClear = [
    'org_presence_members',
    'org_presence_lists',
    'family_member_history',
    'whatsapp_notification_queue',
    'notification_campaigns',
    'notifications',
    'event_passes',
    'due_payments',
    'due_charges',
    'receipts',
    'due_definitions',
    'scheme_benefits',
    'schemes',
    'email_verification_tokens',
    'user_profiles',
    'family_membership_requests',
    'family_dependents',
    'family_relationship_links',
    'family_members',
    'families',
    'organizations',
];

$pdo = Database::pdo();

$sql = 'SELECT id, name, email, role FROM users WHERE role = ?';
$params = ['superadmin'];
if ($keepEmail !== null && $keepEmail !== '') {
    $sql .= ' AND email = ?';
    $params[] = $keepEmail;
}
$sql .= ' ORDER BY id ASC LIMIT 1';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$keeper = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

echo "=== Full database reset (keep one superadmin) ===\n\n";
if ($keeper !== null) {
    echo "Will KEEP superadmin:\n";
    echo "  id:    {$keeper['id']}\n";
    echo "  name:  {$keeper['name']}\n";
    echo "  email: {$keeper['email']}\n\n";
} else {
    echo "No matching superadmin found.";
    if ($keepEmail !== null && $keepEmail !== '') {
        echo " (email: {$keepEmail})";
    }
    echo "\nAfter reset, default superadmin will be created:\n";
    echo "  email: super@local.test\n";
    echo "  password: Super@123\n\n";
}

$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$orgCount = (int) $pdo->query('SELECT COUNT(*) FROM organizations')->fetchColumn();
echo "Current: {$userCount} users, {$orgCount} organizations\n\n";

echo "Will DELETE all rows from:\n";
foreach ($tablesToClear as $table) {
    $n = tableCount($pdo, $table);
    echo sprintf("  %-32s %6d rows\n", $table, $n);
}
$deleteUsers = $keeper !== null ? max(0, $userCount - 1) : max(0, $userCount);
echo sprintf("\nWill DELETE %d user(s) (non-kept accounts).\n\n", $deleteUsers);

if (!$apply) {
    echo "Dry run only. To reset, run:\n";
    echo "  php database/scripts/reset_keep_superadmin.php --apply\n";
    exit(0);
}

echo "Applying in 5 seconds… (Ctrl+C to cancel)\n";
sleep(5);

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
try {
    foreach ($tablesToClear as $table) {
        if (tableExists($pdo, $table)) {
            $pdo->exec("DELETE FROM `{$table}`");
            echo "Cleared {$table}\n";
        }
    }

    if ($keeper !== null) {
        $keepId = (int) $keeper['id'];
        $del = $pdo->prepare('DELETE FROM users WHERE id <> ?');
        $del->execute([$keepId]);
        echo "Deleted users except id {$keepId}\n";
    } else {
        $pdo->exec('DELETE FROM users');
        echo "Deleted all users\n";
        $hash = password_hash('Super@123', PASSWORD_BCRYPT);
        $ins = $pdo->prepare(
            'INSERT INTO users (organization_id, name, email, phone, password, role, member_code)
             VALUES (NULL, ?, ?, NULL, ?, ?, NULL)'
        );
        $ins->execute(['Platform Superadmin', 'super@local.test', $hash, 'superadmin']);
        echo "Created default superadmin super@local.test / Super@123\n";
    }

    $pdo->exec("UPDATE users SET organization_id = NULL, role = 'superadmin' WHERE role = 'superadmin'");
} finally {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

$photoDir = dirname(__DIR__, 2) . '/public/uploads/profile-photos';
if (is_dir($photoDir)) {
    $removed = 0;
    foreach (glob($photoDir . '/*') ?: [] as $path) {
        if (is_file($path) && @unlink($path)) {
            $removed++;
        }
    }
    if ($removed > 0) {
        echo "Removed {$removed} profile photo file(s).\n";
    }
}

echo "\nDone. Sign in at /superadmin with your superadmin account.\n";

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));

    return $stmt !== false && $stmt->fetch() !== false;
}

function tableCount(PDO $pdo, string $table): int
{
    if (!tableExists($pdo, $table)) {
        return 0;
    }

    return (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
}
