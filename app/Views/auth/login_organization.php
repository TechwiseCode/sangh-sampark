<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="<?php echo htmlspecialchars(current_locale()); ?>">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?php echo htmlspecialchars(page_title(t('auth.org_title'))); ?></title>
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
              <h4><?php echo h('auth.org_welcome'); ?></h4>
              <?php $orgSubtitle = trim(t('auth.org_subtitle')); ?>
              <?php if ($orgSubtitle !== ''): ?>
              <h6 class="fw-light text-muted"><?php echo htmlspecialchars($orgSubtitle); ?></h6>
              <?php endif; ?>
              <?php $orgHint = trim(t('auth.org_hint')); ?>
              <?php if ($orgHint !== ''): ?>
              <p class="text-muted small mb-0"><?php echo htmlspecialchars($orgHint); ?></p>
              <?php endif; ?>
              <?php if (!empty($flashOk)): ?>
                <div class="alert alert-success mt-3"><?php echo htmlspecialchars((string) $flashOk); ?></div>
              <?php endif; ?>
              <?php if (!empty($flashErr)): ?>
                <div class="alert alert-danger mt-3"><?php echo htmlspecialchars((string) $flashErr); ?></div>
              <?php endif; ?>
              <?php if (!empty($no_access_message)): ?>
                <div class="alert alert-warning mt-3"><?php echo htmlspecialchars((string) flash_tr($no_access_message)); ?></div>
                <p class="mt-3">
                  <form method="post" action="<?php echo htmlspecialchars(base_url()); ?>/logout" class="d-inline">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-outline-secondary btn-sm"><?php echo h('auth.sign_out'); ?></button>
                  </form>
                </p>
              <?php else: ?>
              <div id="login-alert" class="alert alert-danger mt-3" style="display:none;"></div>
              <form class="pt-3" id="login-form-org" action="<?php echo htmlspecialchars(base_url()); ?>/login" method="post" autocomplete="on">
                <div class="form-group">
                  <input type="text" class="form-control form-control-lg text-uppercase" name="org_code" id="org-code-org" placeholder="<?php echo h('auth.org_code_placeholder'); ?>" required maxlength="12" autocomplete="organization">
                </div>
                <div class="form-group">
                  <input type="text" class="form-control form-control-lg" name="identity" id="identity-org" placeholder="<?php echo h('auth.identity_placeholder'); ?>" required autocomplete="username" inputmode="email">
                </div>
                <div class="form-group">
                  <div class="password-field">
                    <input type="password" class="form-control form-control-lg password-field__input" name="password" id="password-org" placeholder="<?php echo h('auth.password_placeholder'); ?>" required autocomplete="current-password">
                    <button type="button" class="password-field__toggle js-toggle-password" data-target="password-org" aria-label="Show password">
                      <i class="mdi mdi-eye-outline"></i>
                    </button>
                  </div>
                </div>
                <div class="auth-remember-row">
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember-me-org" name="remember_me" value="1">
                    <label class="form-check-label" for="remember-me-org"><?php echo h('auth.remember_me'); ?></label>
                  </div>
                </div>
                <div class="mt-3">
                  <button type="submit" class="btn btn-lg btn-primary btn-block" id="login-btn-org"><?php echo h('auth.org_signin_btn'); ?></button>
                </div>
                <div class="my-2 text-center auth-form-links">
                  <a class="auth-link text-black small d-block mb-1" href="<?php echo htmlspecialchars(base_url()); ?>/forgot-password"><?php echo h('auth.forgot_link'); ?></a>
                  <a class="auth-link text-black small d-block" href="<?php echo htmlspecialchars(base_url()); ?>/login/superadmin"><?php echo h('auth.superadmin_link'); ?></a>
                </div>
              </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="<?php echo htmlspecialchars(asset_url('themes/vendors/js/vendor.bundle.base.js')); ?>"></script>
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/email-lowercase.js')); ?>"></script>
  <script src="<?php echo htmlspecialchars(asset_url('themes/js/login-remember.js')); ?>"></script>
  <script>
  window.SanghSamparkLoginRemember = {
    signedOut: <?php echo json_encode(!empty($signedOut)); ?>
  };
  </script>
  <?php if (empty($no_access_message)): ?>
  <script>
  (function () {
    var base = <?php echo json_encode(base_url()); ?>;
    var form = document.getElementById('login-form-org');
    var alertEl = document.getElementById('login-alert');
    var btn = document.getElementById('login-btn-org');
    var rememberMeEl = document.getElementById('remember-me-org');
    var store = window.SanghSamparkLoginStore ? window.SanghSamparkLoginStore.org : null;
    var rememberApi = window.SanghSamparkLoginStore || {};
    var rememberCfg = window.SanghSamparkLoginRemember || {};

    function rememberEnabled() {
      return rememberMeEl && rememberMeEl.checked;
    }

    function saveLoginDetails(orgCode, identity, password) {
      if (!rememberEnabled()) {
        if (store) {
          store.clear();
        }
        return Promise.resolve();
      }
      if (store) {
        store.write({
          remember: true,
          org_code: orgCode,
          identity: identity,
          password: password
        });
      }
      return rememberApi.saveBrowserCredential
        ? rememberApi.saveBrowserCredential({ org_code: orgCode, identity: identity, password: password })
        : Promise.resolve();
    }

    function applySavedFields(saved) {
      if (!saved) {
        return;
      }
      var orgInput = document.getElementById('org-code-org');
      var identityInput = document.getElementById('identity-org');
      var passwordInput = document.getElementById('password-org');
      if (saved.org_code && orgInput) {
        orgInput.value = String(saved.org_code).toUpperCase();
      }
      if (saved.identity && identityInput) {
        identityInput.value = String(saved.identity);
      }
      if (rememberMeEl && saved.remember) {
        rememberMeEl.checked = true;
      }
    }

    function restoreLoginDetails() {
      if (!store) {
        return;
      }
      if (rememberCfg.signedOut) {
        store.clear();
        return;
      }
      // Prefill only — never auto-submit (that caused reload + password-picker loops).
      var saved = store.read();
      if (saved && saved.remember) {
        applySavedFields(saved);
      }
    }

    function submitLogin() {
      alertEl.style.display = 'none';
      btn.disabled = true;
      var orgCode = document.getElementById('org-code-org').value.trim().toUpperCase();
      var identity = document.getElementById('identity-org').value.trim();
      if (identity.indexOf('@') !== -1) {
        identity = identity.toLowerCase();
      }
      var password = document.getElementById('password-org').value;
      var csrfMeta = document.querySelector('meta[name="csrf-token"]');
      var csrf = csrfMeta ? (csrfMeta.getAttribute('content') || '') : '';
      var headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      };
      if (csrf) {
        headers['X-CSRF-Token'] = csrf;
      }
      fetch(base + '/login', {
        method: 'POST',
        credentials: 'same-origin',
        headers: headers,
        body: JSON.stringify({
          org_code: orgCode,
          identity: identity,
          password: password,
          login_as: 'organization',
          remember_me: rememberEnabled(),
          _csrf: csrf
        })
      })
        .then(function (r) {
          return r.text().then(function (text) {
            var body;
            try {
              body = text ? JSON.parse(text) : {};
            } catch (e) {
              var err = new Error('bad_json');
              err.status = r.status;
              throw err;
            }
            return { status: r.status, body: body };
          });
        })
        .then(function (res) {
          btn.disabled = false;
          if (res.status === 403) {
            if (store) {
              store.clear();
            }
            window.location.reload();
            return;
          }
          if (res.body.ok) {
            // Never wait on PasswordCredential / localStorage — it can hang on the
            // browser "Save password?" UI and leave the user stuck on /login.
            try {
              saveLoginDetails(orgCode, identity, password);
            } catch (e) {}
            if (res.body.force_change_password) {
              window.location.href = base + '/organization/settings/password';
              return;
            }
            var intended = res.body.intended_url || '';
            var intendedIsApi = false;
            try {
              var intendedPath = intended ? (new URL(intended, window.location.origin)).pathname : '';
              intendedIsApi = /\/organization\/notifications\/(list|preview)\b/.test(intendedPath)
                || /\/organization\/(check-email|check-phone|pincode-lookup|resolve-identity|calendar\/feed|event\/pass-search)\b/.test(intendedPath);
            } catch (e) {}
            if (intended && intended.indexOf('/login') === -1 && !intendedIsApi) {
              window.location.href = intended;
              return;
            }
            if (res.body.must_complete_profile) {
              window.location.href = base + '/organization/my-family';
            } else {
              window.location.href = base + '/organization/dashboard';
            }
            return;
          }
          if (res.status === 401 && store) {
            store.clear();
          }
          if (res.status === 404) {
            alertEl.textContent = <?php echo json_encode(t('auth.js_endpoint_404_org')); ?>.replace('{url}', base + '/login');
          } else {
            var baseMsg = res.body.error || <?php echo json_encode(t('auth.js_signin_failed_generic')); ?>;
            var statusPart = (res.status && res.status !== 200) ? ' (HTTP ' + res.status + ')' : '';
            alertEl.textContent = baseMsg + statusPart;
          }
          alertEl.style.display = 'block';
        })
        .catch(function (err) {
          btn.disabled = false;
          if (err && err.message === 'bad_json') {
            var st = err.status || '';
            alertEl.textContent = st === 404
              ? <?php echo json_encode(t('auth.js_failed_404')); ?>
              : <?php echo json_encode(t('auth.js_failed_non_json')); ?>.replace('{status}', String(st || ''));
          } else {
            alertEl.textContent = <?php echo json_encode(t('auth.js_failed_network')); ?>;
          }
          alertEl.style.display = 'block';
        });
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      submitLogin();
    });

    if (rememberMeEl) {
      rememberMeEl.addEventListener('change', function () {
        if (!rememberEnabled() && store) {
          store.clear();
        }
      });
    }

    restoreLoginDetails();
    window.addEventListener('pageshow', function (event) {
      if (event.persisted) {
        window.location.reload();
      }
    });
    document.querySelectorAll('.js-toggle-password').forEach(function (btnEye) {
      btnEye.addEventListener('click', function () {
        var targetId = btnEye.getAttribute('data-target') || '';
        var input = document.getElementById(targetId);
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        var icon = btnEye.querySelector('i');
        if (icon) {
          icon.className = show ? 'mdi mdi-eye-off-outline' : 'mdi mdi-eye-outline';
        }
      });
    });
  })();
  </script>
  <?php endif; ?>
  <?php require BASE_PATH . '/app/Views/partials/pwa_install_prompt.php'; ?>
  <?php require BASE_PATH . '/app/Views/partials/pwa_install.php'; ?>
  <style>
  .password-field { position: relative; }
  .password-field__input { padding-right: 42px; }
  .password-field__toggle {
    position: absolute;
    top: 50%;
    right: 10px;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: #6c757d;
    padding: 0;
    line-height: 1;
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
  .auth-form-light .auth-remember-row {
    text-align: left;
    margin: 0.35rem 0 0.85rem;
  }
  .auth-form-light .auth-remember-row .form-check {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    min-height: 1.35rem;
    padding-left: 0;
    margin-bottom: 0;
  }
  .auth-form-light .auth-remember-row .form-check-input {
    position: static;
    margin: 0;
    width: 1.05rem;
    height: 1.05rem;
    flex-shrink: 0;
    cursor: pointer;
    opacity: 1;
    visibility: visible;
  }
  .auth-form-light .auth-remember-row .form-check-label {
    position: static;
    margin: 0;
    padding: 0;
    font-size: 0.92rem;
    font-weight: 500;
    color: #343a40;
    cursor: pointer;
    line-height: 1.3;
  }
  </style>
</body>
</html>
