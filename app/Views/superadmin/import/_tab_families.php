<?php
$b = base_url();
$importTab = (string) ($importTab ?? 'families');
$includeOrgCode = true;
$importBaseUrl = $b;
require BASE_PATH . '/app/Views/partials/family_import_panel.php';
