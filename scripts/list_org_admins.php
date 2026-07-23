<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$users = new App\Models\User();
$orgs = new App\Models\Organization();
foreach ($orgs->listAll() as $o) {
    $oid = (int) $o['id'];
    $code = strtoupper((string) ($o['org_code'] ?? ''));
    $rows = $users->listByOrganization($oid, 'admin');
    if ($rows === []) {
        continue;
    }
    echo $code . ' | ' . ($o['name'] ?? '') . PHP_EOL;
    foreach ($rows as $a) {
        echo '  - ' . ($a['email'] ?? '(no email)') . ' | ' . ($a['phone'] ?? '') . ' | id=' . ($a['id'] ?? '') . PHP_EOL;
    }
}
