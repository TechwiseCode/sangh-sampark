<?php
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$stats = isset($stats) && is_array($stats) ? $stats : [];
$b = base_url();
?>
<div class="dash-page">
  <header class="dash-header">
    <h1 class="dash-header-title"><?php echo h(t('superadmin.home.title')); ?></h1>
    <p class="dash-header-meta mb-0"><?php echo h(t('superadmin.home.subtitle')); ?></p>
  </header>

  <?php if ($flashOk): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($flashOk); ?></div>
  <?php endif; ?>
  <?php if ($flashErr): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($flashErr); ?></div>
  <?php endif; ?>

  <div class="dash-stat-grid">
    <div class="dash-stat-card dash-stat-card--accent">
      <p class="dash-stat-label"><?php echo h(t('superadmin.home.users')); ?></p>
      <p class="dash-stat-value"><?php echo (int) ($stats['users'] ?? 0); ?></p>
    </div>
    <a class="dash-stat-card" href="<?php echo htmlspecialchars($b); ?>/superadmin/organizations">
      <p class="dash-stat-label"><?php echo h(t('superadmin.home.organizations')); ?></p>
      <p class="dash-stat-value"><?php echo (int) ($stats['organizations'] ?? 0); ?></p>
    </a>
    <div class="dash-stat-card">
      <p class="dash-stat-label"><?php echo h(t('superadmin.home.families')); ?></p>
      <p class="dash-stat-value"><?php echo (int) ($stats['families'] ?? 0); ?></p>
    </div>
  </div>

  <div class="dash-actions-grid mb-4">
    <a class="dash-action" href="<?php echo htmlspecialchars($b); ?>/superadmin/organizations">
      <i class="mdi mdi-domain dash-action-icon"></i>
      <span class="dash-action-label"><?php echo h(t('superadmin.home.nav_organizations')); ?></span>
    </a>
    <a class="dash-action" href="<?php echo htmlspecialchars($b); ?>/superadmin/members">
      <i class="mdi mdi-account-multiple dash-action-icon"></i>
      <span class="dash-action-label"><?php echo h(t('superadmin.home.nav_users')); ?></span>
    </a>
    <a class="dash-action" href="<?php echo htmlspecialchars($b); ?>/superadmin/import">
      <i class="mdi mdi-upload dash-action-icon"></i>
      <span class="dash-action-label"><?php echo h(t('superadmin.home.nav_import')); ?></span>
    </a>
  </div>

  <div class="row">
    <div class="col-md-6 mb-3">
      <div class="card h-100">
        <div class="card-body">
          <h4 class="dash-section-title"><?php echo h(t('superadmin.home.recent_growth')); ?></h4>
          <div class="dash-metric-list">
            <div class="dash-metric-row">
              <span><?php echo h(t('superadmin.home.users_7d')); ?></span>
              <strong><?php echo (int) ($stats['users_7d'] ?? 0); ?></strong>
            </div>
            <div class="dash-metric-row">
              <span><?php echo h(t('superadmin.home.users_30d')); ?></span>
              <strong><?php echo (int) ($stats['users_30d'] ?? 0); ?></strong>
            </div>
            <div class="dash-metric-row">
              <span><?php echo h(t('superadmin.home.orgs_30d')); ?></span>
              <strong><?php echo (int) ($stats['organizations_30d'] ?? 0); ?></strong>
            </div>
            <div class="dash-metric-row">
              <span><?php echo h(t('superadmin.home.families_30d')); ?></span>
              <strong><?php echo (int) ($stats['families_30d'] ?? 0); ?></strong>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 mb-3">
      <div class="card h-100">
        <div class="card-body">
          <h4 class="dash-section-title"><?php echo h(t('superadmin.home.data_health')); ?></h4>
          <div class="dash-metric-list">
            <div class="dash-metric-row">
              <span><?php echo h(t('superadmin.home.orgs_with_admin')); ?></span>
              <strong><?php echo (int) ($stats['organizations_with_admin'] ?? 0); ?></strong>
            </div>
            <div class="dash-metric-row">
              <span><?php echo h(t('superadmin.home.orgs_without_admin')); ?></span>
              <strong class="text-danger"><?php echo (int) ($stats['organizations_without_admin'] ?? 0); ?></strong>
            </div>
            <div class="dash-metric-row">
              <span><?php echo h(t('superadmin.home.families_without_head')); ?></span>
              <strong class="text-warning"><?php echo (int) ($stats['families_without_head_membership'] ?? 0); ?></strong>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
