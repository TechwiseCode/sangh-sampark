<?php
$b = base_url();
$settingsSection = (string) ($settingsSection ?? 'password');
$items = [
    'password' => ['icon' => 'mdi-lock-outline', 'label' => t('settings.nav_password')],
    'notifications' => ['icon' => 'mdi-bell-ring-outline', 'label' => t('settings.nav_notifications')],
    'language' => ['icon' => 'mdi-translate', 'label' => t('settings.nav_language')],
];
?>
<ul class="nav org-settings-sidebar__nav">
  <li class="nav-item">
    <a class="nav-link org-settings-sidebar__back" href="<?php echo htmlspecialchars($b); ?>/organization/dashboard">
      <i class="mdi mdi-arrow-left menu-icon"></i>
      <span class="menu-title"><?php echo htmlspecialchars(t('settings.nav_back')); ?></span>
    </a>
  </li>
  <li class="nav-item org-settings-sidebar__label" aria-hidden="true">
    <span class="org-settings-sidebar__label-text"><?php echo htmlspecialchars(t('settings.title')); ?></span>
  </li>
  <?php foreach ($items as $key => $item): ?>
    <li class="nav-item">
      <a class="nav-link<?php echo $settingsSection === $key ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($b); ?>/organization/settings/<?php echo htmlspecialchars($key); ?>">
        <i class="mdi <?php echo htmlspecialchars($item['icon']); ?> menu-icon"></i>
        <span class="menu-title"><?php echo htmlspecialchars($item['label']); ?></span>
      </a>
    </li>
  <?php endforeach; ?>
  <?php if (!$isOrgAdmin): ?>
  <li class="nav-item org-settings-sidebar__spacer" aria-hidden="true"></li>
  <li class="nav-item">
    <a class="nav-link" href="<?php echo htmlspecialchars($b); ?>/organization/profile">
      <i class="mdi mdi-account-outline menu-icon"></i>
      <span class="menu-title"><?php echo htmlspecialchars(t('settings.nav_profile')); ?></span>
    </a>
  </li>
  <?php endif; ?>
</ul>
