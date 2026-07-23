<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$orgName = $current ? (string) $current['name'] : '';
$memberCount = isset($memberCount) ? (int) $memberCount : 0;
$familyCount = isset($familyCount) ? (int) $familyCount : 0;
$schemeCount = isset($schemeCount) ? (int) $schemeCount : 0;
$eventCount = isset($eventCount) ? (int) $eventCount : 0;
$canManageOrg = !empty($canManageOrg);
?>
<div class="dash-page">
  <header class="dash-header<?php echo $canManageOrg ? ' dash-header--admin' : ''; ?>">
    <div class="dash-header__main">
      <h1 class="dash-header-title"><?php echo htmlspecialchars($orgName !== '' ? $orgName : t('nav.home')); ?></h1>
      <p class="dash-header-meta">
        <?php echo $canManageOrg ? t('role.org_admin') : t('role.member'); ?>
      </p>
      <div class="dash-header-tools">
        <?php if (!empty($current['full_member_code'])): ?>
          <p class="dash-header-meta"><?php echo htmlspecialchars(t('common.code')); ?>: <strong><?php echo htmlspecialchars((string) $current['full_member_code']); ?></strong></p>
        <?php endif; ?>
        <?php $committeeLookupPart = 'button'; require __DIR__ . '/partials/committee_lookup.php'; ?>
      </div>
    </div>
    <?php if ($canManageOrg): ?>
      <?php require BASE_PATH . '/app/Views/partials/org_contact_admin_card.php'; ?>
    <?php endif; ?>
  </header>

  <?php if ($flashOk): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($flashOk); ?></div>
  <?php endif; ?>
  <?php if ($flashErr): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($flashErr); ?></div>
  <?php endif; ?>

  <div class="dash-main-row">
    <div class="dash-main-primary">
      <?php if ($canManageOrg): ?>
        <div class="dash-stat-grid">
          <div class="dash-stat-card dash-stat-card--accent">
            <p class="dash-stat-label"><?php echo htmlspecialchars(t('dashboard.members')); ?></p>
            <p class="dash-stat-value"><?php echo $memberCount; ?></p>
          </div>
          <a class="dash-stat-card" href="<?php echo htmlspecialchars($b); ?>/organization/families">
            <p class="dash-stat-label"><?php echo htmlspecialchars(t('nav.family')); ?></p>
            <p class="dash-stat-value"><?php echo $familyCount; ?></p>
          </a>
          <a class="dash-stat-card" href="<?php echo htmlspecialchars($b); ?>/organization/events?event_tab=schemes">
            <p class="dash-stat-label"><?php echo htmlspecialchars(t('nav.schemes')); ?></p>
            <p class="dash-stat-value"><?php echo $schemeCount; ?></p>
          </a>
          <a class="dash-stat-card" href="<?php echo htmlspecialchars($b); ?>/organization/events">
            <p class="dash-stat-label"><?php echo htmlspecialchars(t('nav.events')); ?></p>
            <p class="dash-stat-value"><?php echo $eventCount; ?></p>
          </a>
        </div>
        <div class="dash-actions-grid">
          <a class="dash-action" href="<?php echo htmlspecialchars($b); ?>/organization/receipts">
            <i class="mdi mdi-receipt dash-action-icon"></i>
            <span class="dash-action-label"><?php echo htmlspecialchars(t('nav.receipts')); ?></span>
          </a>
          <a class="dash-action" href="<?php echo htmlspecialchars($b); ?>/organization/events">
            <i class="mdi mdi-calendar-star dash-action-icon"></i>
            <span class="dash-action-label"><?php echo htmlspecialchars(t('nav.events')); ?></span>
          </a>
          <a class="dash-action" href="<?php echo htmlspecialchars($b); ?>/organization/families">
            <i class="mdi mdi-account-group dash-action-icon"></i>
            <span class="dash-action-label"><?php echo htmlspecialchars(t('nav.family')); ?></span>
          </a>
          <a class="dash-action" href="<?php echo htmlspecialchars($b); ?>/organization/notifications">
            <i class="mdi mdi-bell-outline dash-action-icon"></i>
            <span class="dash-action-label"><?php echo htmlspecialchars(t('nav.notifications')); ?></span>
          </a>
        </div>
      <?php else: ?>
        <div class="dash-actions-grid dash-actions-grid--member">
          <a class="dash-action" href="<?php echo htmlspecialchars($b); ?>/organization/my-receipts">
            <i class="mdi mdi-receipt dash-action-icon"></i>
            <span class="dash-action-label"><?php echo htmlspecialchars(t('nav.receipts')); ?></span>
          </a>
          <a class="dash-action" href="<?php echo htmlspecialchars($b); ?>/organization/events">
            <i class="mdi mdi-calendar-star dash-action-icon"></i>
            <span class="dash-action-label"><?php echo htmlspecialchars(t('nav.events')); ?></span>
          </a>
          <a class="dash-action" href="<?php echo htmlspecialchars($b); ?>/organization/my-family">
            <i class="mdi mdi-account-group dash-action-icon"></i>
            <span class="dash-action-label"><?php echo htmlspecialchars(t('dashboard.my_family')); ?></span>
          </a>
          <a class="dash-action" href="<?php echo htmlspecialchars($b); ?>/organization/profile">
            <i class="mdi mdi-account-circle dash-action-icon"></i>
            <span class="dash-action-label"><?php echo htmlspecialchars(t('dashboard.profile')); ?></span>
          </a>
        </div>
      <?php endif; ?>
    </div>

    <div class="dash-main-widgets">
      <div class="dash-main-widget dash-main-widget--calendar">
        <?php require __DIR__ . '/partials/calendar_widget.php'; ?>
      </div>
      <div class="dash-main-widget dash-main-widget--presence">
        <?php require __DIR__ . '/partials/presence_widget.php'; ?>
      </div>
    </div>
  </div>

  <?php require __DIR__ . '/partials/notices_strip.php'; ?>
  <?php $committeeLookupPart = 'modal'; require __DIR__ . '/partials/committee_lookup.php'; ?>
</div>
