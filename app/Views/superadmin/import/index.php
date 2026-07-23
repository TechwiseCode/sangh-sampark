<?php
$b = base_url();
$importTab = (string) ($importTab ?? 'families');
if (!in_array($importTab, ['families', 'panchang'], true)) {
    $importTab = 'families';
}
$familiesTabUrl = $b . '/superadmin/import?tab=families';
$panchangTabUrl = $b . '/superadmin/import?tab=panchang';
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
?>
<div class="row">
  <div class="col-12 border-bottom">
    <h3 class="mb-0"><?php echo h(t('superadmin.import.title')); ?></h3>
    <p class="text-muted small mb-0"><?php echo h(t('superadmin.import.subtitle')); ?></p>
  </div>
</div>

<?php if ($flashOk): ?>
  <div class="alert alert-success mt-3"><?php echo htmlspecialchars($flashOk); ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
  <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($flashErr); ?></div>
<?php endif; ?>

<div class="row mt-3">
  <div class="col-12">
    <ul class="nav nav-tabs mb-3" id="superadminImportTabs">
      <li class="nav-item">
        <a class="nav-link<?php echo $importTab === 'families' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($familiesTabUrl); ?>">
          <?php echo h(t('superadmin.import.tab_families')); ?>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link<?php echo $importTab === 'panchang' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($panchangTabUrl); ?>">
          <?php echo h(t('superadmin.import.tab_panchang')); ?>
        </a>
      </li>
    </ul>

    <?php if ($importTab === 'families'): ?>
      <?php require __DIR__ . '/_tab_families.php'; ?>
    <?php else: ?>
      <?php require __DIR__ . '/_tab_panchang.php'; ?>
    <?php endif; ?>
  </div>
</div>
