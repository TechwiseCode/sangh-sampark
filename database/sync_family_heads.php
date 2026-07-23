<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

use App\Core\Database;
use App\Models\Family;

$ids = Database::pdo()->query('SELECT id FROM families')->fetchAll(PDO::FETCH_COLUMN);
$fam = new Family();
foreach ($ids as $fid) {
    $fid = (int) $fid;
    $fam->ensureHeadMembershipForDesignatedHead($fid);
    $fam->syncHeadUserIdFromMembers($fid);
}

echo 'Done. ' . count($ids) . " families.\n";
