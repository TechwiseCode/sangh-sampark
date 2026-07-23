<?php

declare(strict_types=1);

/**
 * Full wipe of application data, then create one superadmin.
 *
 * Usage:
 *   php database/scripts/flush_and_seed_superadmin.php --apply
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Core\Database;

$apply = in_array('--apply', $argv ?? [], true);

$email = 'dygandhi27@gmail.com';
$password = 'Mmn123*678';
$name = 'Platform Superadmin';

$pdo = Database::pdo();

$keepTables = [
    // none — wipe all data tables; schema stays
];

// Tables that must never be truncated (none for MySQL system). We wipe all base tables.
$tables = [];
foreach ($pdo->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'') as $row) {
    $tables[] = (string) array_values($row)[0];
}
sort($tables);

$userCount = tableExists($pdo, 'users') ? (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() : 0;
$orgCount = tableExists($pdo, 'organizations') ? (int) $pdo->query('SELECT COUNT(*) FROM organizations')->fetchColumn() : 0;

echo "=== Flush database + seed superadmin ===\n\n";
echo "Current: {$userCount} users, {$orgCount} organizations\n";
echo "Tables to clear: " . count($tables) . "\n";
echo "New superadmin: {$email}\n\n";

if (!$apply) {
    echo "Dry run only. To apply:\n";
    echo "  php database/scripts/flush_and_seed_superadmin.php --apply\n";
    exit(0);
}

echo "Applying in 3 seconds… (Ctrl+C to cancel)\n";
sleep(3);

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
try {
    foreach ($tables as $table) {
        $pdo->exec("DELETE FROM `{$table}`");
        echo "Cleared {$table}\n";
    }

    // Reset auto-increments where possible
    foreach ($tables as $table) {
        try {
            $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
        } catch (Throwable $e) {
            // ignore views / no AI
        }
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $cols = userColumns($pdo);
    $fields = ['organization_id', 'name', 'email', 'phone', 'password', 'role', 'member_code'];
    $values = [null, $name, $email, null, $hash, 'superadmin', null];

    if (in_array('first_name', $cols, true)) {
        $fields[] = 'first_name';
        $values[] = 'Platform';
    }
    if (in_array('last_name', $cols, true)) {
        $fields[] = 'last_name';
        $values[] = 'Superadmin';
    }
    if (in_array('email_verified_at', $cols, true)) {
        $fields[] = 'email_verified_at';
        $values[] = date('Y-m-d H:i:s');
    }

    $placeholders = implode(', ', array_fill(0, count($fields), '?'));
    $sql = 'INSERT INTO users (' . implode(', ', $fields) . ') VALUES (' . $placeholders . ')';
    $pdo->prepare($sql)->execute($values);
    $id = (int) $pdo->lastInsertId();
    echo "\nCreated superadmin id={$id} email={$email}\n";
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

$noticeDir = dirname(__DIR__, 2) . '/storage/notices';
if (is_dir($noticeDir)) {
    $removed = 0;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($noticeDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        if ($file->isFile() && @unlink($file->getPathname())) {
            $removed++;
        }
    }
    if ($removed > 0) {
        echo "Removed {$removed} notice file(s).\n";
    }
}

echo "\nDone. Sign in at /login/superadmin\n";
echo "  email: {$email}\n";
echo "  password: (as provided)\n";

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));

    return $stmt !== false && $stmt->fetch() !== false;
}

/** @return list<string> */
function userColumns(PDO $pdo): array
{
    $cols = [];
    foreach ($pdo->query('SHOW COLUMNS FROM users') as $row) {
        $cols[] = (string) $row['Field'];
    }

    return $cols;
}
