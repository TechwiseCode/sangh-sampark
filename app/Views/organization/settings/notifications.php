<?php
$pushConfigured = !empty($pushConfigured);
$pushSubscriptionCount = (int) ($pushSubscriptionCount ?? 0);
?>
<div class="org-settings-panel">
  <div class="org-settings-panel__head">
    <span class="org-settings-panel__icon" aria-hidden="true"><i class="mdi mdi-bell-ring-outline"></i></span>
    <div>
      <h2 class="org-settings-panel__title"><?php echo h(t('settings.notifications_title')); ?></h2>
      <p class="org-settings-panel__desc mb-0"><?php echo h(t('settings.notifications_desc')); ?></p>
    </div>
  </div>
  <?php require BASE_PATH . '/app/Views/partials/notifications_push_settings.php'; ?>
  <p class="org-settings-panel__note mb-0">
    <i class="mdi mdi-information-outline" aria-hidden="true"></i>
    <?php echo h(t('settings.notifications_inbox_hint')); ?>
    <a href="<?php echo htmlspecialchars(base_url()); ?>/organization/notifications"><?php echo h(t('settings.notifications_inbox_link')); ?></a>
  </p>
</div>
