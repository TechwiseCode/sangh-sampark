<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="<?php echo htmlspecialchars(current_locale()); ?>">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?php echo htmlspecialchars($pageTitle ?? t('common.organization')); ?></title>
  <link rel="manifest" href="<?php echo htmlspecialchars(pwa_web_base_url()); ?>/manifest.json">
  <meta name="theme-color" content="#34B1AA">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
  <?php require BASE_PATH . '/app/Views/partials/csrf_head.php'; ?>
  <?php require BASE_PATH . '/app/Views/partials/pwa_head.php'; ?>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/feather/feather.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/mdi/css/materialdesignicons.min.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/ti-icons/css/themify-icons.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/typicons/typicons.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/simple-line-icons/css/simple-line-icons.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/css/vendor.bundle.base.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/css/vertical-layout-light/style.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/css/app.css')); ?>?v=<?php echo is_file(BASE_PATH . '/themes/css/app.css') ? (string) filemtime(BASE_PATH . '/themes/css/app.css') : '1'; ?>">
  <?php if (member_admin_chat_enabled()): ?>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/css/member-admin-chat.css')); ?>">
  <?php endif; ?>
  <?php require BASE_PATH . '/app/Views/partials/subtle_accent.php'; ?>
  <?php if (current_locale() === 'gu'): ?>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Gujarati:wght@400;600;700&display=swap">
  <style>body { font-family: "Noto Sans Gujarati", sans-serif; }</style>
  <?php endif; ?>
  <?php require BASE_PATH . '/app/Views/partials/favicon.php'; ?>
  <?php
    $navPhotoUrl = user_photo_url(isset($user['photo_path']) ? (string) $user['photo_path'] : null);
    if ($navPhotoUrl !== null):
  ?>
  <link rel="preload" as="image" href="<?php echo htmlspecialchars($navPhotoUrl); ?>">
  <?php endif; ?>
</head>
<?php $isSettingsMode = ($navActive ?? '') === 'settings'; ?>
<?php
$forcePasswordLock = false;
$sessionUserForLock = current_user();
if (is_array($sessionUserForLock)) {
    $lockUid = (int) ($sessionUserForLock['id'] ?? 0);
    $forcePasswordLock = !empty($sessionUserForLock['must_change_password'])
        || ($lockUid > 0 && isset($_SESSION['force_password_change_user_id']) && (int) $_SESSION['force_password_change_user_id'] === $lockUid);
}
?>
<body class="<?php echo trim(subtle_accent_body_class() . ($isSettingsMode ? ' saas-org-settings-mode' : '') . ($forcePasswordLock ? ' force-password-lock' : '')); ?>">
  <?php
  $navUser = isset($user) && is_array($user) ? $user : [];
  $navDisplayName = user_nav_display_name($navUser);
  $navFullName = user_display_name($navUser);
  $navEmail = trim((string) ($navUser['email'] ?? ''));
  $navInitials = user_photo_initials($navDisplayName);
  $notifUnreadCount = 0;
  $isOrgAdmin = isset($current['membership_role']) && strtolower((string) $current['membership_role']) === 'admin';
  $memberChatEnabled = member_admin_chat_enabled();
  $memberChatOpenCount = 0;
  if ($memberChatEnabled && $isOrgAdmin && isset($user['id']) && !$forcePasswordLock) {
    $footerOrgIdForChat = (int) ($current['id'] ?? 0);
    if ($footerOrgIdForChat < 1 && function_exists('organization_id')) {
      $footerOrgIdForChat = organization_id();
    }
    if ($footerOrgIdForChat > 0) {
      try {
        $memberChatOpenCount = (new \App\Models\MemberAdminChat())->countOpenThreadsForOrganization($footerOrgIdForChat);
      } catch (\Throwable $e) {
        $memberChatOpenCount = 0;
      }
    }
  }
  $orgFooterContact = null;
  if (!$isOrgAdmin) {
    $footerOrgId = (int) ($current['id'] ?? 0);
    if ($footerOrgId < 1 && function_exists('organization_id')) {
      $footerOrgId = organization_id();
    }
    if ($footerOrgId > 0) {
      $orgFooterContact = (new \App\Models\Organization())->officialContactForDisplay($footerOrgId);
    }
  }
  ?>
  <div class="container-scroller saas-org-shell<?php echo $orgFooterContact !== null ? ' has-org-footer' : ''; ?>">
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row saas-app-navbar">
      <div class="navbar-brand-wrapper d-flex align-items-center justify-content-start saas-navbar-brand">
        <?php if (!$forcePasswordLock): ?>
        <button class="navbar-toggler saas-nav-menu-btn d-lg-none" type="button" data-bs-toggle="offcanvas" aria-label="Toggle menu">
          <span class="mdi mdi-menu" aria-hidden="true"></span>
        </button>
        <?php endif; ?>
        <a class="navbar-brand brand-logo" href="<?php echo htmlspecialchars(base_url()); ?>/organization/<?php echo $forcePasswordLock ? 'settings/password' : 'dashboard'; ?>">
          <img src="<?php echo htmlspecialchars(asset_url('themes/images/logo.png')); ?>" alt="<?php echo htmlspecialchars(app_name()); ?>" class="brand-logo-img">
        </a>
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end saas-navbar-menu">
        <ul class="navbar-nav navbar-nav-right saas-nav-actions">
          <?php if (!$forcePasswordLock): ?>
          <li class="nav-item saas-nav-quick-item" id="notifBellRoot">
            <a class="nav-link saas-nav-icon-btn notif-bell-link<?php echo ($navActive ?? '') === 'notifications' ? ' is-active' : ''; ?>" href="#" id="notifBellToggle" role="button" aria-haspopup="true" aria-expanded="false" title="<?php echo htmlspecialchars(t('nav.notifications')); ?>" aria-label="<?php echo htmlspecialchars(t('nav.notifications')); ?>">
              <span class="notif-bell-wrap">
                <i class="mdi mdi-bell-outline notif-bell-icon" aria-hidden="true"></i>
                <span class="notif-badge" id="notif_badge"<?php echo $notifUnreadCount < 1 ? ' hidden' : ''; ?>><?php echo (int) min($notifUnreadCount, 99); ?><?php echo $notifUnreadCount > 99 ? '+' : ''; ?></span>
              </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right notif-bell-dropdown" id="notifBellMenu" aria-labelledby="notifBellToggle">
              <div class="notif-bell-dropdown__head">
                <strong class="notif-bell-dropdown__title"><?php echo htmlspecialchars(t('notifications.bell_title')); ?></strong>
                <span class="notif-bell-dropdown__summary" id="notif_bell_summary"></span>
              </div>
              <div class="notif-bell-dropdown__list" id="notif_bell_list">
                <p class="notif-bell-dropdown__loading text-muted small mb-0"><?php echo htmlspecialchars(t('notifications.bell_loading')); ?></p>
              </div>
              <div class="notif-bell-dropdown__foot">
                <button type="button" class="btn btn-link btn-sm p-0 notif-bell-mark-all" id="notif_mark_all_btn" hidden><?php echo htmlspecialchars(t('notifications.mark_all_read')); ?></button>
                <a class="notif-bell-view-all" href="<?php echo htmlspecialchars(base_url()); ?>/organization/notifications"><?php echo htmlspecialchars(t('notifications.bell_view_all')); ?></a>
              </div>
            </div>
          </li>
          <li class="nav-item saas-nav-quick-item saas-nav-settings-item">
            <a class="nav-link saas-nav-icon-btn saas-nav-quick-btn<?php echo $isSettingsMode ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/organization/settings" title="<?php echo htmlspecialchars(t('nav.settings')); ?>" aria-label="<?php echo htmlspecialchars(t('nav.settings')); ?>">
              <i class="mdi mdi-settings" aria-hidden="true"></i>
            </a>
          </li>
          <?php if (!$isOrgAdmin): ?>
          <li class="nav-item saas-nav-quick-item d-lg-none">
            <a class="nav-link saas-nav-icon-btn saas-nav-quick-btn<?php echo ($navActive ?? '') === 'profile' ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/organization/profile" title="<?php echo htmlspecialchars(t('nav.profile')); ?>" aria-label="<?php echo htmlspecialchars(t('nav.profile')); ?>">
              <i class="mdi mdi-account-outline" aria-hidden="true"></i>
            </a>
          </li>
          <?php endif; ?>
          <?php endif; ?>
          <li class="nav-item dropdown saas-nav-quick-item" id="userMenuRoot">
            <a class="nav-link saas-nav-user-btn d-flex align-items-center" href="#" id="userMenuDropdown" role="button" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo htmlspecialchars($navFullName); ?>">
              <span class="nav-user-avatar<?php echo $navPhotoUrl !== null ? ' has-photo' : ''; ?>" aria-hidden="true"<?php if ($navPhotoUrl !== null): ?> style="background-image: url('<?php echo htmlspecialchars($navPhotoUrl, ENT_QUOTES, 'UTF-8'); ?>')"<?php endif; ?>>
                <?php if ($navPhotoUrl === null): ?>
                  <span class="nav-user-avatar-ph"><?php echo htmlspecialchars($navInitials); ?></span>
                <?php endif; ?>
              </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right saas-user-dropdown" id="userMenuDropdownMenu" aria-labelledby="userMenuDropdown">
              <div class="saas-user-dropdown-header">
                <div class="saas-user-dropdown-name"><?php echo htmlspecialchars($navFullName); ?></div>
                <?php if ($navEmail !== ''): ?>
                <div class="saas-user-dropdown-email"><?php echo htmlspecialchars($navEmail); ?></div>
                <?php endif; ?>
              </div>
              <div class="dropdown-divider"></div>
              <?php if (!$forcePasswordLock): ?>
              <?php if (!$isOrgAdmin): ?>
              <a class="dropdown-item" href="<?php echo htmlspecialchars(base_url()); ?>/organization/profile">
                <span class="saas-user-dropdown-item-label"><i class="mdi mdi-account-outline" aria-hidden="true"></i> <?php echo htmlspecialchars(t('nav.profile')); ?></span>
              </a>
              <?php endif; ?>
              <a class="dropdown-item saas-user-dropdown-item-with-badge" href="<?php echo htmlspecialchars(base_url()); ?>/organization/notifications">
                <span class="saas-user-dropdown-item-label"><i class="mdi mdi-bell-outline" aria-hidden="true"></i> <?php echo htmlspecialchars(t('nav.notifications')); ?></span>
                <span class="sidebar-notif-badge"<?php echo $notifUnreadCount < 1 ? ' style="display:none"' : ''; ?>><?php echo (int) min($notifUnreadCount, 99); ?><?php echo $notifUnreadCount > 99 ? '+' : ''; ?></span>
              </a>
              <a class="dropdown-item" href="<?php echo htmlspecialchars(base_url()); ?>/organization/events">
                <span class="saas-user-dropdown-item-label"><i class="mdi mdi-calendar-star" aria-hidden="true"></i> <?php echo htmlspecialchars(t('nav.events')); ?></span>
              </a>
              <?php if (!$isOrgAdmin): ?>
              <a class="dropdown-item" href="<?php echo htmlspecialchars(base_url()); ?>/organization/my-receipts">
                <span class="saas-user-dropdown-item-label"><i class="mdi mdi-receipt" aria-hidden="true"></i> <?php echo htmlspecialchars(t('nav.receipts')); ?></span>
              </a>
              <?php endif; ?>
              <a class="dropdown-item" href="<?php echo htmlspecialchars(base_url()); ?>/organization/settings">
                <span class="saas-user-dropdown-item-label"><i class="mdi mdi-settings" aria-hidden="true"></i> <?php echo htmlspecialchars(t('nav.settings')); ?></span>
              </a>
              <button type="button" class="dropdown-item js-pwa-install" id="pwaInstallMenuBtn">
                <span class="saas-user-dropdown-item-label"><i class="mdi mdi-cellphone-arrow-down" aria-hidden="true"></i> <?php echo htmlspecialchars(t('pwa.install_title')); ?></span>
              </button>
              <div class="dropdown-divider"></div>
              <?php $localeSwitcherVariant = 'dropdown'; require BASE_PATH . '/app/Views/partials/locale_switcher.php'; ?>
              <div class="dropdown-divider"></div>
              <?php else: ?>
              <a class="dropdown-item" href="<?php echo htmlspecialchars(base_url()); ?>/organization/settings/password">
                <span class="saas-user-dropdown-item-label"><i class="mdi mdi-lock-outline" aria-hidden="true"></i> <?php echo htmlspecialchars(t('settings.change_password')); ?></span>
              </a>
              <div class="dropdown-divider"></div>
              <?php endif; ?>
              <form method="post" action="<?php echo htmlspecialchars(base_url()); ?>/logout" class="saas-logout-form">
                <?php echo csrf_field(); ?>
                <button type="submit" class="dropdown-item">
                  <span class="saas-user-dropdown-item-label"><i class="mdi mdi-logout" aria-hidden="true"></i> <?php echo htmlspecialchars(t('nav.logout')); ?></span>
                </button>
              </form>
            </div>
          </li>
        </ul>
      </div>
    </nav>
    <div class="container-fluid page-body-wrapper">
      <div class="popup-overlay" id="popupOverlay" aria-hidden="true"></div>
      <nav class="sidebar sidebar-offcanvas<?php echo $isSettingsMode ? ' org-settings-sidebar' : ''; ?>" id="sidebar"<?php echo $forcePasswordLock ? ' aria-hidden="true"' : ''; ?>>
        <?php if ($forcePasswordLock): ?>
          <ul class="nav org-settings-sidebar__nav">
            <li class="nav-item">
              <a class="nav-link active" href="<?php echo htmlspecialchars(base_url()); ?>/organization/settings/password">
                <i class="mdi mdi-lock-outline menu-icon"></i>
                <span class="menu-title"><?php echo htmlspecialchars(t('settings.change_password')); ?></span>
              </a>
            </li>
          </ul>
        <?php elseif ($isSettingsMode): ?>
          <?php require BASE_PATH . '/app/Views/partials/org_settings_sidebar.php'; ?>
        <?php else: ?>
        <?php /* $isOrgAdmin set above for footer + sidebar */ ?>
        <ul class="nav">
          <li class="nav-item">
            <a class="nav-link<?php echo ($navActive ?? '') === 'dashboard' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/organization/dashboard">
              <i class="mdi mdi-view-dashboard menu-icon"></i>
              <span class="menu-title"><?php echo htmlspecialchars(t('nav.home')); ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link<?php echo ($navActive ?? '') === 'families' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($isOrgAdmin ? base_url() . '/organization/families' : base_url() . '/organization/my-family'); ?>">
              <i class="mdi mdi-account-group menu-icon"></i>
              <span class="menu-title"><?php echo htmlspecialchars(t($isOrgAdmin ? 'nav.members' : 'nav.family')); ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link<?php echo ($navActive ?? '') === 'events' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/organization/events">
              <i class="mdi mdi-calendar-star menu-icon"></i>
              <span class="menu-title"><?php echo htmlspecialchars(t('nav.events')); ?></span>
            </a>
          </li>
          <?php if ($isOrgAdmin): ?>
          <li class="nav-item">
            <a class="nav-link<?php echo ($navActive ?? '') === 'notices' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/organization/notices">
              <i class="mdi mdi-bulletin-board menu-icon"></i>
              <span class="menu-title"><?php echo htmlspecialchars(t('nav.notices')); ?></span>
            </a>
          </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="nav-link<?php echo ($navActive ?? '') === 'sadhvis' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/organization/sadhvis">
              <i class="mdi mdi-account-star menu-icon"></i>
              <span class="menu-title"><?php echo htmlspecialchars(t('nav.sadhvis')); ?></span>
            </a>
          </li>
          <?php
            $receiptsNavActive = in_array((string) ($navActive ?? ''), ['receipts', 'donations'], true);
          ?>
          <?php if ($isOrgAdmin): ?>
          <li class="nav-item<?php echo $receiptsNavActive ? ' active' : ''; ?>">
            <a class="nav-link<?php echo $receiptsNavActive ? '' : ' collapsed'; ?>" data-bs-toggle="collapse" href="#org-receipts-submenu" aria-expanded="<?php echo $receiptsNavActive ? 'true' : 'false'; ?>" aria-controls="org-receipts-submenu">
              <i class="mdi mdi-receipt menu-icon"></i>
              <span class="menu-title"><?php echo htmlspecialchars(t('nav.receipts_menu')); ?></span>
              <i class="menu-arrow"></i>
            </a>
            <div class="collapse<?php echo $receiptsNavActive ? ' show' : ''; ?>" id="org-receipts-submenu">
              <ul class="nav flex-column sub-menu">
                <li class="nav-item">
                  <a class="nav-link<?php echo ($navActive ?? '') === 'receipts' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/organization/receipts"><?php echo htmlspecialchars(t('nav.event_receipts')); ?></a>
                </li>
                <li class="nav-item">
                  <a class="nav-link<?php echo ($navActive ?? '') === 'donations' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/organization/donations"><?php echo htmlspecialchars(t('nav.donation_receipts')); ?></a>
                </li>
              </ul>
            </div>
          </li>
          <?php else: ?>
          <li class="nav-item">
            <a class="nav-link<?php echo ($navActive ?? '') === 'receipts' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/organization/my-receipts">
              <i class="mdi mdi-receipt menu-icon"></i>
              <span class="menu-title"><?php echo htmlspecialchars(t('nav.event_receipts')); ?></span>
            </a>
          </li>
          <?php endif; ?>
          <?php if ($isOrgAdmin): ?>
          <li class="nav-item">
            <a class="nav-link<?php echo ($navActive ?? '') === 'calendar_days' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/organization/calendar-days">
              <i class="mdi mdi-calendar-plus menu-icon"></i>
              <span class="menu-title"><?php echo htmlspecialchars(t('nav.calendar_days')); ?></span>
            </a>
          </li>
          <?php endif; ?>
          <?php if ($memberChatEnabled && $isOrgAdmin): ?>
          <li class="nav-item">
            <a class="nav-link<?php echo ($navActive ?? '') === 'member_messages' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/organization/member-messages">
              <i class="mdi mdi-message-text-outline menu-icon"></i>
              <span class="menu-title"><?php echo htmlspecialchars(t('member_chat.nav_admin')); ?></span>
              <?php if ($memberChatOpenCount > 0): ?>
                <span class="sidebar-member-chat-badge"><?php echo (int) min($memberChatOpenCount, 99); ?><?php echo $memberChatOpenCount > 99 ? '+' : ''; ?></span>
              <?php endif; ?>
            </a>
          </li>
          <?php endif; ?>
        </ul>
        <?php endif; ?>
      </nav>
      <div class="main-panel">
        <div class="content-wrapper">
          <?php require $slot; ?>
        </div>
        <?php if ($orgFooterContact !== null): ?>
          <?php $orgContact = $orgFooterContact; require BASE_PATH . '/app/Views/partials/org_official_contact.php'; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <script src="<?php echo htmlspecialchars(asset_url('themes/vendors/js/vendor.bundle.base.js')); ?>"></script>
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/off-canvas.js')); ?>"></script>
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/hoverable-collapse.js')); ?>"></script>
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/template.js')); ?>"></script>
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/settings.js')); ?>"></script>
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/todolist.js')); ?>"></script>
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/email-lowercase.js')); ?>"></script>
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/mail-queue-drain.js')); ?>?v=<?php echo is_file(BASE_PATH . '/themes/js/mail-queue-drain.js') ? (string) filemtime(BASE_PATH . '/themes/js/mail-queue-drain.js') : '1'; ?>"></script>
  <script>
  <?php
    $pushAutoPrompt = web_push_is_configured() && (
        (($navActive ?? '') === 'notifications')
        || (($settingsSection ?? '') === 'notifications')
        || member_admin_chat_enabled()
    );
  ?>
  window.SanghSamparkPush = {
    baseUrl: <?php echo json_encode(base_url()); ?>,
    configured: <?php echo web_push_is_configured() ? 'true' : 'false'; ?>,
    autoPrompt: <?php echo $pushAutoPrompt ? 'true' : 'false'; ?>,
    notConfiguredMessage: <?php echo json_encode(t('notifications.push_not_configured')); ?>,
    enabledMessage: <?php echo json_encode(t('notifications.push_status_enabled')); ?>,
    disabledMessage: <?php echo json_encode(t('notifications.push_status_disabled')); ?>,
    deniedMessage: <?php echo json_encode(t('notifications.push_status_denied')); ?>,
    unsupportedMessage: <?php echo json_encode(t('notifications.push_unsupported')); ?>,
    httpsRequiredMessage: <?php echo json_encode(t('notifications.push_https_required')); ?>,
    swFailedMessage: <?php echo json_encode(t('notifications.push_sw_failed')); ?>,
    permissionDeclinedMessage: <?php echo json_encode(t('notifications.push_permission_declined')); ?>,
    iosInstallHint: <?php echo json_encode(t('notifications.push_ios_install_hint')); ?>,
    iosSettingsHint: <?php echo json_encode(t('notifications.push_ios_settings_hint')); ?>,
    serverSyncWarning: <?php echo json_encode(t('notifications.push_server_sync_warning')); ?>
  };
  window.SanghSamparkNotifications = {
    baseUrl: <?php echo json_encode(base_url()); ?>,
    initialUnread: <?php echo (int) $notifUnreadCount; ?>,
    unreadLabel: <?php echo json_encode(t('notifications.unread_label')); ?>,
    readLabel: <?php echo json_encode(t('notifications.read_label')); ?>,
    emptyLabel: <?php echo json_encode(t('notifications.bell_empty')); ?>,
    loadingLabel: <?php echo json_encode(t('notifications.bell_loading')); ?>,
    unreadSummary: <?php echo json_encode(t('notifications.bell_unread_summary')); ?>,
    markAllRead: <?php echo json_encode(t('notifications.mark_all_read')); ?>,
    viewAll: <?php echo json_encode(t('notifications.bell_view_all')); ?>,
    pageSize: <?php echo (int) \App\Models\Notification::PAGE_SIZE; ?>,
    loadingMoreLabel: <?php echo json_encode(t('notifications.loading_more')); ?>
  };
  </script>
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/popup-overlay.js')); ?>"></script>
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/push-notifications.js')); ?>"></script>
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/notifications-bell.js')); ?>"></script>
  <?php require BASE_PATH . '/app/Views/partials/pwa_install_prompt.php'; ?>
  <?php require BASE_PATH . '/app/Views/partials/pwa_install.php'; ?>
  <?php if ($memberChatEnabled && !$isOrgAdmin): ?>
    <?php require BASE_PATH . '/app/Views/partials/member_admin_chat_widget.php'; ?>
  <?php endif; ?>
  <script>
  (function () {
    var userMenuToggle = document.getElementById('userMenuDropdown');
    var userMenu = document.getElementById('userMenuDropdownMenu');
    var userMenuRoot = document.getElementById('userMenuRoot');
    if (userMenuToggle && userMenu && userMenuRoot) {
      function positionUserMenu() {
        var rect = userMenuToggle.getBoundingClientRect();
        var gap = 8;
        var minInset = 16;
        userMenu.style.position = 'fixed';
        userMenu.style.top = Math.round(rect.bottom + gap) + 'px';
        userMenu.style.right = Math.round(Math.max(minInset, window.innerWidth - rect.right)) + 'px';
        userMenu.style.left = 'auto';
        userMenu.style.bottom = 'auto';
        userMenu.style.transform = 'none';
      }

      function closeUserMenu() {
        userMenu.classList.remove('show');
        userMenuRoot.classList.remove('show');
        userMenuToggle.setAttribute('aria-expanded', 'false');
        if (window.SanghSamparkPopupOverlay) {
          window.SanghSamparkPopupOverlay.hide(closeUserMenu);
        }
      }

      window.SanghSamparkUserMenu = { close: closeUserMenu };

      userMenuToggle.addEventListener('click', function (e) {
        e.preventDefault();
        var isOpen = userMenu.classList.contains('show');
        if (isOpen) {
          closeUserMenu();
          return;
        }
        if (window.SanghSamparkNotificationsApi && window.SanghSamparkNotificationsApi.close) {
          window.SanghSamparkNotificationsApi.close();
        }
        positionUserMenu();
        userMenu.classList.add('show');
        userMenuRoot.classList.add('show');
        userMenuToggle.setAttribute('aria-expanded', 'true');
        if (window.SanghSamparkPopupOverlay) {
          window.SanghSamparkPopupOverlay.show(closeUserMenu);
        }
      });
      document.addEventListener('click', function (e) {
        if (!userMenuRoot.contains(e.target)) {
          closeUserMenu();
        }
      });
      window.addEventListener('resize', function () {
        if (userMenu.classList.contains('show')) {
          positionUserMenu();
        }
      });
    }

    function normalizeSearch(v) {
      return (v || '').toLowerCase().replace(/[^a-z0-9]/g, '');
    }

    function enhanceSelect(select) {
      if (!select || select.dataset.searchDropdownEnhanced === '1') return;
      if (select.multiple || select.hasAttribute('data-no-search-dropdown')) return;
      select.dataset.searchDropdownEnhanced = '1';

      var wrapper = document.createElement('div');
      wrapper.className = 'search-select-wrap';
      select.parentNode.insertBefore(wrapper, select);
      wrapper.appendChild(select);

      var toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'form-control text-left search-select-toggle';
      wrapper.appendChild(toggle);

      var menu = document.createElement('div');
      menu.className = 'search-select-menu d-none';
      wrapper.appendChild(menu);

      var input = document.createElement('input');
      input.type = 'text';
      input.className = 'form-control form-control-sm mb-2';
      input.placeholder = <?php echo json_encode(t('common.search')); ?>;
      menu.appendChild(input);

      var list = document.createElement('div');
      list.className = 'search-select-list';
      menu.appendChild(list);

      function selectedText() {
        var opt = select.options[select.selectedIndex];
        return opt ? (opt.text || '').trim() : <?php echo json_encode(t('common.choose')); ?>;
      }

      function syncToggle() {
        toggle.textContent = selectedText() || <?php echo json_encode(t('common.choose')); ?>;
      }

      function renderOptions() {
        list.innerHTML = '';
        for (var i = 0; i < select.options.length; i++) {
          var opt = select.options[i];
          if (!opt.value) continue;
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'search-select-option';
          btn.textContent = opt.text || '';
          btn.setAttribute('data-value', opt.value);
          btn.setAttribute('data-text', opt.text || '');
          btn.addEventListener('click', function () {
            var val = this.getAttribute('data-value') || '';
            select.value = val;
            if (typeof Event === 'function') {
              select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            syncToggle();
            menu.classList.add('d-none');
          });
          list.appendChild(btn);
        }
      }

      function filterOptions() {
        var term = normalizeSearch(input.value || '');
        var options = list.querySelectorAll('.search-select-option');
        options.forEach(function (btn) {
          var txt = normalizeSearch(btn.getAttribute('data-text') || '');
          btn.style.display = (term === '' || txt.indexOf(term) !== -1) ? 'block' : 'none';
        });
      }

      select.style.display = 'none';
      renderOptions();
      syncToggle();

      toggle.addEventListener('click', function () {
        menu.classList.toggle('d-none');
        if (!menu.classList.contains('d-none')) {
          input.focus();
          input.select();
        }
      });
      input.addEventListener('input', filterOptions);
      select.addEventListener('change', syncToggle);
      document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) {
          menu.classList.add('d-none');
        }
      });
    }

    document.querySelectorAll('select.form-control').forEach(enhanceSelect);
  })();
  </script>
  <style>
  .search-select-wrap {
    position: relative;
    min-width: 220px;
  }
  .search-select-toggle {
    background: #fff;
  }
  .search-select-menu {
    position: absolute;
    z-index: 60;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #ced4da;
    border-radius: 6px;
    padding: 8px;
    box-shadow: 0 8px 18px rgba(0,0,0,0.12);
  }
  .search-select-list {
    max-height: 220px;
    overflow-y: auto;
  }
  .search-select-option {
    width: 100%;
    text-align: left;
    border: 0;
    background: transparent;
    padding: 6px 8px;
    border-radius: 4px;
  }
  .search-select-option:hover {
    background: #f1f3f5;
  }
  .form-check {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    padding-left: 0;
  }
  .form-check .form-check-input {
    position: static;
    margin: 0;
    flex: 0 0 auto;
  }
  .form-check .form-check-label {
    margin-bottom: 0;
  }
  .locale-switcher {
    display: inline-flex;
    border: 1px solid #ced4da;
    border-radius: 6px;
    overflow: hidden;
    background: #fff;
  }
  .locale-switcher-btn {
    display: inline-block;
    padding: 0.2rem 0.55rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: #6c757d;
    text-decoration: none;
    line-height: 1.6;
  }
  .locale-switcher-btn:hover {
    color: #34B1AA;
    text-decoration: none;
  }
  .locale-switcher-btn.is-active {
    background: #34B1AA;
    color: #fff;
  }
  </style>
</body>
</html>
