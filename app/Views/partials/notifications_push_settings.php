<?php
$pushConfigured = !empty($pushConfigured);
$pushSubscriptionCount = (int) ($pushSubscriptionCount ?? 0);
$pushStatusClass = 'is-muted';
if (!$pushConfigured) {
    $pushStatusClass = 'is-warn';
} elseif ($pushSubscriptionCount > 0) {
    $pushStatusClass = 'is-on';
}
?>
<div class="notifications-push-panel">
  <div class="notifications-push-panel__head">
    <span class="notifications-push-panel__icon" aria-hidden="true"><i class="mdi mdi-cellphone-link"></i></span>
    <div>
      <h3 class="notifications-push-panel__title"><?php echo h('notifications.push_settings_title'); ?></h3>
      <p class="notifications-push-panel__desc mb-0"><?php echo h('notifications.push_settings_desc'); ?></p>
    </div>
  </div>
  <div class="notifications-push-status <?php echo htmlspecialchars($pushStatusClass); ?>" id="push_status_wrap">
    <span class="notifications-status-dot" aria-hidden="true"></span>
    <p id="push_status_text" class="notifications-status-text mb-0">
      <?php
      if (!$pushConfigured) {
          echo h('notifications.push_not_configured');
      } elseif ($pushSubscriptionCount > 0) {
          echo h('notifications.push_status_enabled');
      } else {
          echo h('notifications.push_status_disabled');
      }
      ?>
    </p>
  </div>
  <?php if ($pushConfigured): ?>
    <p class="notifications-push-hint mb-0" id="push_enable_hint"><?php echo h('notifications.push_click_enable_hint'); ?></p>
    <p class="notifications-push-hint notifications-push-hint--ios mb-0" id="push_ios_install_hint" style="display:none;"></p>
    <p class="notifications-push-hint notifications-push-hint--ios mb-0" id="push_ios_settings_hint" style="display:none;"></p>
  <?php endif; ?>
  <div class="notifications-push-actions">
    <button type="button" class="btn btn-primary btn-sm" id="push_enable_btn"<?php echo !$pushConfigured ? ' style="display:none;"' : ''; ?>><?php echo h('notifications.push_enable_btn'); ?></button>
    <button type="button" class="btn btn-outline-secondary btn-sm" id="push_disable_btn" style="display:none;"><?php echo h('notifications.push_disable_btn'); ?></button>
  </div>
  <p class="notifications-push-hint mb-0" id="push_server_sync_hint" style="display:none;"></p>
</div>
