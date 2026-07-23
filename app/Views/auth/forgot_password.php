<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="<?php echo htmlspecialchars(current_locale()); ?>">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?php echo htmlspecialchars(page_title(t('auth.forgot_title'))); ?></title>
  <link rel="manifest" href="<?php echo htmlspecialchars(base_url()); ?>/manifest.json">
  <meta name="theme-color" content="#34B1AA">
  <?php require BASE_PATH . '/app/Views/partials/csrf_head.php'; ?>
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/feather/feather.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/mdi/css/materialdesignicons.min.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/ti-icons/css/themify-icons.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/typicons/typicons.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/simple-line-icons/css/simple-line-icons.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/vendors/css/vendor.bundle.base.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/css/vertical-layout-light/style.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('themes/css/app.css')); ?>">
  <?php require BASE_PATH . '/app/Views/partials/subtle_accent.php'; ?>
  <?php if (current_locale() === 'gu'): ?>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Gujarati:wght@400;600;700&display=swap">
  <style>body { font-family: "Noto Sans Gujarati", sans-serif; }</style>
  <?php endif; ?>
  <?php require BASE_PATH . '/app/Views/partials/favicon.php'; ?>
</head>
<body class="<?php echo trim(subtle_accent_body_class()); ?>">
  <div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
      <div class="content-wrapper d-flex align-items-center auth px-0">
        <div class="row w-100 mx-0">
          <div class="brand-logo text-center mb-4">
            <img src="<?php echo htmlspecialchars(asset_url('themes/images/logo.png')); ?>" alt="logo" class="brand-logo-img">
          </div>
          <div class="col-lg-4 mx-auto">
            <div class="auth-form-light text-left py-5 px-4 px-sm-5 text-center">
              <div class="mb-3">
                <?php $activeLocale = current_locale(); ?>
                <div class="locale-switcher" role="group" aria-label="<?php echo h('auth.language_label'); ?>">
                  <a href="<?php echo htmlspecialchars(locale_url('en')); ?>" class="locale-switcher-btn<?php echo $activeLocale === 'en' ? ' is-active' : ''; ?>">EN</a>
                  <a href="<?php echo htmlspecialchars(locale_url('gu')); ?>" class="locale-switcher-btn<?php echo $activeLocale === 'gu' ? ' is-active' : ''; ?>">GU</a>
                </div>
              </div>
              <h4><?php echo h('auth.forgot_heading'); ?></h4>
              <h6 class="fw-light text-muted"><?php echo h('auth.forgot_subtitle'); ?></h6>
              <p class="text-muted small mb-0"><?php echo h('auth.forgot_hint'); ?></p>
              <?php if (!empty($flashOk)): ?>
                <div class="alert alert-success mt-3"><?php echo htmlspecialchars((string) $flashOk); ?></div>
              <?php endif; ?>
              <?php if (!empty($flashErr)): ?>
                <div class="alert alert-danger mt-3"><?php echo htmlspecialchars((string) $flashErr); ?></div>
              <?php endif; ?>
              <form class="pt-3" method="post" action="<?php echo htmlspecialchars(base_url()); ?>/forgot-password" id="form_forgot_password"
                data-submit-guard-note="Sending reset email. Please wait — do not click again.">
                <div class="form-group">
                  <input type="text" class="form-control form-control-lg text-uppercase" name="org_code" placeholder="<?php echo h('auth.org_code_placeholder'); ?>" required maxlength="12" autocomplete="organization">
                </div>
                <div class="form-group">
                  <input type="text" class="form-control form-control-lg" name="identity" placeholder="<?php echo h('auth.identity_placeholder'); ?>" required>
                </div>
                <div class="mt-3">
                  <button type="submit" class="btn btn-lg btn-primary btn-block" data-submit-wait="Sending…"><?php echo h('auth.forgot_submit_btn'); ?></button>
                </div>
                <div class="my-2 text-center">
                  <a class="auth-link text-black small" href="<?php echo htmlspecialchars(base_url()); ?>/login"><?php echo h('auth.back_to_login'); ?></a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="<?php echo htmlspecialchars(asset_url('themes/vendors/js/vendor.bundle.base.js')); ?>"></script>
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/email-lowercase.js')); ?>"></script>
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/form-submit-guard.js')); ?>?v=<?php echo is_file(BASE_PATH . '/themes/js/form-submit-guard.js') ? (string) filemtime(BASE_PATH . '/themes/js/form-submit-guard.js') : '1'; ?>"></script>
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/mail-queue-drain.js')); ?>?v=<?php echo is_file(BASE_PATH . '/themes/js/mail-queue-drain.js') ? (string) filemtime(BASE_PATH . '/themes/js/mail-queue-drain.js') : '1'; ?>"></script>
</body>
</html>
