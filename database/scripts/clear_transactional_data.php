<?php

declare(strict_types=1);

/**
 * Remove events, payments, receipts, dues, passes, schemes, notifications, audit history.
 * Keeps organizations, users (incl. admins), profiles, families, and family structure.
 *
 * Usage:
 *   php database/scripts/clear_transactional_data.php          # dry-run (counts only)
 *   php database/scripts/clear_transactional_data.php --apply  # actually delete
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Core\Database;

$tablesToClear = [
    'event_passes' => 'Event food passes',
    'due_payments' => 'Due payment links',
    'due_charges' => 'Due charges (membership/events)',
    'receipts' => 'Receipts / payments',
    'due_definitions' => 'Dues & events definitions',
    'scheme_benefits' => 'Scheme benefit assignments',
    'schemes' => 'Schemes',
    'whatsapp_notification_queue' => 'WhatsApp queue',
    'notification_campaigns' => 'Notification broadcasts',
    'notifications' => 'In-app notifications',
    'family_member_history' => 'Family change history',
];

$tablesKept = [
    'organizations',
    'users',
    'user_profiles',
    'families',
    'family_members',
    'family_dependents',
    'family_relationship_links',
    'family_membership_requests',
    'email_verification_tokens',
];

$apply = in_array('--apply', $argv ?? [], true);

$pdo = Database::pdo();

echo "=== Clear transactional data ===\n\n";
echo "WILL DELETE from:\n";
foreach ($tablesToClear as $table => $label) {
    echo "  - {$table} ({$label})\n";
}
echo "\nKEEPS:\n";
foreach ($tablesKept as $table) {
    $n = tableCount($pdo, $table);
    echo "  - {$table} ({$n} rows)\n";
}
echo "\n";

echo "Rows to remove:\n";
$total = 0;
foreach ($tablesToClear as $table => $label) {
    $n = tableCount($pdo, $table);
    $total += $n;
    echo sprintf("  %-28s %6d\n", $table, $n);
}
echo sprintf("  %-28s %6d\n", 'TOTAL', $total);
echo "\n";

if (!$apply) {
    echo "Dry run only. To delete, run:\n";
    echo "  php database/scripts/clear_transactional_data.php --apply\n";
    exit(0);
}

echo "Applying in 3 seconds… (Ctrl+C to cancel)\n";
sleep(3);

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
try {
    foreach (array_keys($tablesToClear) as $table) {
        $pdo->exec("TRUNCATE TABLE `{$table}`");
        echo "Truncated {$table}\n";
    }
} finally {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

echo "\nDone. Organizations, users, families, and admins are unchanged.\n";

function tableCount(PDO $pdo, string $table): int
{
    static $exists = [];
    if (!isset($exists[$table])) {
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
        $exists[$table] = $stmt !== false && $stmt->fetch() !== false;
    }
    if (!$exists[$table]) {
        return 0;
    }
    return (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
}
