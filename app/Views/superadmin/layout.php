<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="<?php echo htmlspecialchars(current_locale()); ?>">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?php echo htmlspecialchars($pageTitle ?? t('superadmin.layout.title')); ?></title>
  <link rel="manifest" href="<?php echo htmlspecialchars(pwa_web_base_url()); ?>/manifest.json">
  <meta name="theme-color" content="#34B1AA">
  <?php require BASE_PATH . '/app/Views/partials/csrf_head.php'; ?>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/feather/feather.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/mdi/css/materialdesignicons.min.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/ti-icons/css/themify-icons.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/typicons/typicons.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/simple-line-icons/css/simple-line-icons.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/css/vendor.bundle.base.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/css/vertical-layout-light/style.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/css/app.css')); ?>?v=<?php echo is_file(BASE_PATH . '/themes/css/app.css') ? (string) filemtime(BASE_PATH . '/themes/css/app.css') : '1'; ?>">
  <?php require BASE_PATH . '/app/Views/partials/subtle_accent.php'; ?>
  <?php require BASE_PATH . '/app/Views/partials/favicon.php'; ?>
</head>
<body class="<?php echo trim(subtle_accent_body_class()); ?>">
  <?php
  $navUser = isset($user) && is_array($user) ? $user : [];
  if (isset($navUser['id'])) {
      $userModel = new \App\Models\User();
      $freshNavUser = $userModel->findById((int) $navUser['id']);
      if (is_array($freshNavUser)) {
          $navUser = array_merge($navUser, $userModel->toSessionArray($freshNavUser));
      }
  }
  $navDisplayName = user_nav_display_name($navUser);
  $navFullName = user_display_name($navUser);
  $navEmail = trim((string) ($navUser['email'] ?? ''));
  $navInitials = user_photo_initials($navDisplayName);
  $navPhotoUrl = user_photo_url(isset($navUser['photo_path']) ? (string) $navUser['photo_path'] : null);
  ?>
  <div class="container-scroller saas-superadmin-shell">
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row saas-app-navbar">
      <div class="navbar-brand-wrapper d-flex align-items-center justify-content-start saas-navbar-brand">
        <button class="navbar-toggler saas-nav-menu-btn d-lg-none" type="button" data-bs-toggle="offcanvas" aria-label="Toggle menu">
          <span class="mdi mdi-menu" aria-hidden="true"></span>
        </button>
        <a class="navbar-brand brand-logo" href="<?php echo htmlspecialchars(base_url()); ?>/superadmin">
          <img src="<?php echo htmlspecialchars(asset_url('themes/images/logo.png')); ?>" alt="<?php echo htmlspecialchars(app_name()); ?>" class="brand-logo-img">
        </a>
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end saas-navbar-menu">
        <ul class="navbar-nav navbar-nav-right saas-nav-actions">
          <li class="nav-item dropdown" id="userMenuRoot">
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
              <?php $localeSwitcherVariant = 'dropdown'; require BASE_PATH . '/app/Views/partials/locale_switcher.php'; ?>
              <div class="dropdown-divider"></div>
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
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">
          <li class="nav-item<?php echo ($navActive ?? '') === 'dashboard' ? ' active' : ''; ?>">
            <a class="nav-link<?php echo ($navActive ?? '') === 'dashboard' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/superadmin">
              <i class="mdi mdi-view-dashboard menu-icon"></i>
              <span class="menu-title"><?php echo htmlspecialchars(t('nav.home')); ?></span>
            </a>
          </li>
          <li class="nav-item<?php echo ($navActive ?? '') === 'organizations' ? ' active' : ''; ?>">
            <a class="nav-link<?php echo ($navActive ?? '') === 'organizations' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/superadmin/organizations">
              <i class="mdi mdi-office-building menu-icon"></i>
              <span class="menu-title"><?php echo htmlspecialchars(t('nav.organizations')); ?></span>
            </a>
          </li>
          <li class="nav-item<?php echo ($navActive ?? '') === 'members' ? ' active' : ''; ?>">
            <a class="nav-link<?php echo ($navActive ?? '') === 'members' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/superadmin/members">
              <i class="mdi mdi-account-multiple menu-icon"></i>
              <span class="menu-title"><?php echo htmlspecialchars(t('nav.superadmin_users')); ?></span>
            </a>
          </li>
          <li class="nav-item<?php echo ($navActive ?? '') === 'import' ? ' active' : ''; ?>">
            <a class="nav-link<?php echo ($navActive ?? '') === 'import' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/superadmin/import">
              <i class="mdi mdi-file-import menu-icon"></i>
              <span class="menu-title"><?php echo htmlspecialchars(t('nav.import')); ?></span>
            </a>
          </li>
          <li class="nav-item<?php echo ($navActive ?? '') === 'holidays' ? ' active' : ''; ?>">
            <a class="nav-link<?php echo ($navActive ?? '') === 'holidays' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/superadmin/holidays">
              <i class="mdi mdi-calendar-star menu-icon"></i>
              <span class="menu-title"><?php echo htmlspecialchars(t('nav.holidays')); ?></span>
            </a>
          </li>
          <li class="nav-item<?php echo ($navActive ?? '') === 'mail_test' ? ' active' : ''; ?>">
            <a class="nav-link<?php echo ($navActive ?? '') === 'mail_test' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars(base_url()); ?>/superadmin/mail-test">
              <i class="mdi mdi-email-send menu-icon"></i>
              <span class="menu-title"><?php echo htmlspecialchars(t('nav.mail_test')); ?></span>
            </a>
          </li>
        </ul>
      </nav>
      <div class="main-panel">
        <div class="content-wrapper">
          <?php require $slot; ?>
        </div>
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
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/form-submit-guard.js')); ?>?v=<?php echo is_file(BASE_PATH . '/themes/js/form-submit-guard.js') ? (string) filemtime(BASE_PATH . '/themes/js/form-submit-guard.js') : '1'; ?>"></script>
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/mail-queue-drain.js')); ?>?v=<?php echo is_file(BASE_PATH . '/themes/js/mail-queue-drain.js') ? (string) filemtime(BASE_PATH . '/themes/js/mail-queue-drain.js') : '1'; ?>"></script>
  <script>
  (function () {
    var base = <?php echo json_encode(base_url()); ?>;
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register(base + '/service-worker.js').catch(function () {});
    }

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

      userMenuToggle.addEventListener('click', function (e) {
        e.preventDefault();
        var isOpen = userMenu.classList.contains('show');
        if (!isOpen) {
          positionUserMenu();
        }
        userMenu.classList.toggle('show', !isOpen);
        userMenuRoot.classList.toggle('show', !isOpen);
        userMenuToggle.setAttribute('aria-expanded', (!isOpen).toString());
      });
      document.addEventListener('click', function (e) {
        if (!userMenuRoot.contains(e.target)) {
          userMenu.classList.remove('show');
          userMenuRoot.classList.remove('show');
          userMenuToggle.setAttribute('aria-expanded', 'false');
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
      input.placeholder = 'Type to search...';
      menu.appendChild(input);

      var list = document.createElement('div');
      list.className = 'search-select-list';
      menu.appendChild(list);

      function selectedText() {
        var opt = select.options[select.selectedIndex];
        return opt ? (opt.text || '').trim() : 'Choose...';
      }

      function syncToggle() {
        toggle.textContent = selectedText() || 'Choose...';
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
