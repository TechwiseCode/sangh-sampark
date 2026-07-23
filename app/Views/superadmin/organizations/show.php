<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$oid = (int) $organization['id'];
$orgCode = strtoupper(trim((string) ($organization['org_code'] ?? '')));
$orgMemberInitials = strtoupper(trim((string) ($organization['member_initials'] ?? '')));
$orgNickname = trim((string) ($organization['nickname'] ?? ''));
$orgAddress = trim((string) ($organization['address'] ?? ''));
$orgMapsUrl = trim((string) ($organization['maps_url'] ?? ''));
$memberCodeExample = ($orgCode !== '' && $orgMemberInitials !== '')
    ? $orgCode . '-' . $orgMemberInitials . '101'
    : '';
?>
<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center border-bottom flex-wrap">
    <div>
      <h3 class="mb-0"><?php echo htmlspecialchars((string) $organization['name']); ?></h3>
      <?php if ($orgNickname !== ''): ?>
        <p class="text-muted mb-0 small"><?php echo h(t('superadmin.organizations.show.nickname_label')); ?> <?php echo htmlspecialchars($orgNickname); ?></p>
      <?php endif; ?>
      <p class="text-muted mb-0">
        <?php echo h(t('superadmin.organizations.show.org_id')); ?> <?php echo $oid; ?>
        <?php if ($orgCode !== ''): ?>
          · <?php echo h(t('superadmin.organizations.show.login_code')); ?> <strong class="text-dark"><?php echo htmlspecialchars($orgCode); ?></strong>
        <?php endif; ?>
        <?php if ($orgMemberInitials !== ''): ?>
          · <?php echo h(t('superadmin.organizations.show.member_initials_label')); ?> <strong class="text-dark"><?php echo htmlspecialchars($orgMemberInitials); ?></strong>
          <?php if ($memberCodeExample !== ''): ?>
            <span class="text-muted">(<?php echo htmlspecialchars($memberCodeExample); ?>…)</span>
          <?php endif; ?>
        <?php endif; ?>
        · <?php echo (int) $familyCount; ?> <?php echo (int) $familyCount === 1 ? h(t('superadmin.organizations.show.family_singular')) : h(t('superadmin.organizations.show.family_plural')); ?>
      </p>
      <p class="text-muted small mb-0"><?php echo h('superadmin.organizations.show.share_login', ['login_url' => $b . '/login']); ?></p>
      <?php
        $orgIsActive = !isset($organization['is_active']) || (int) $organization['is_active'] === 1;
      ?>
      <?php if (!$orgIsActive): ?>
        <p class="mb-0 mt-1"><span class="badge badge-danger"><?php echo h(t('superadmin.organizations.disabled_badge')); ?></span></p>
      <?php endif; ?>
    </div>
    <div class="d-flex flex-wrap mb-2" style="gap:0.5rem;">
      <form method="post" action="<?php echo htmlspecialchars($b); ?>/superadmin/organization/set-active" onsubmit="return confirm(<?php echo json_encode($orgIsActive ? t('superadmin.organizations.disable_confirm') : t('superadmin.organizations.enable_confirm')); ?>);">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="organization_id" value="<?php echo $oid; ?>">
        <input type="hidden" name="is_active" value="<?php echo $orgIsActive ? '0' : '1'; ?>">
        <button type="submit" class="btn <?php echo $orgIsActive ? 'btn-warning' : 'btn-success'; ?>">
          <?php echo h($orgIsActive ? t('superadmin.organizations.disable') : t('superadmin.organizations.enable')); ?>
        </button>
      </form>
      <a class="btn btn-light" href="<?php echo htmlspecialchars($b); ?>/superadmin/organizations"><?php echo h(t('superadmin.organizations.show.back')); ?></a>
    </div>
  </div>
</div>
<?php if ($flashOk): ?>
  <div class="alert alert-success mt-3"><?php echo htmlspecialchars($flashOk); ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
  <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($flashErr); ?></div>
<?php endif; ?>
<div class="row" style="padding-top: 16px;">
  <div class="col-lg-5">
    <div class="card mb-4">
      <div class="card-body">
        <h4 class="card-title"><?php echo h(t('superadmin.organizations.show.edit_title')); ?></h4>
        <form method="post" action="<?php echo htmlspecialchars($b); ?>/superadmin/organization/update">
          <input type="hidden" name="organization_id" value="<?php echo $oid; ?>">
          <div class="form-group">
            <label for="org_name_edit"><?php echo h(t('superadmin.organizations.show.name_label')); ?></label>
            <input type="text" class="form-control" id="org_name_edit" name="name" required value="<?php echo htmlspecialchars((string) ($organization['name'] ?? '')); ?>">
          </div>
          <div class="form-group">
            <label for="org_short_code_edit"><?php echo h(t('superadmin.organizations.form.short_code')); ?> *</label>
            <input type="text" class="form-control text-uppercase" id="org_short_code_edit" name="short_code" required maxlength="12"
              value="<?php echo htmlspecialchars($orgCode); ?>" pattern="[A-Za-z0-9]{2,12}" autocomplete="off" style="text-transform: uppercase;">
            <small class="text-muted d-block"><?php echo h(t('superadmin.organizations.show.login_code_help')); ?></small>
            <div id="short_code_edit_warn" class="alert alert-danger mt-2 py-2 mb-0 small d-none" role="alert"></div>
          </div>
          <div class="form-group">
            <label for="org_member_initials_edit"><?php echo h(t('superadmin.organizations.form.member_initials')); ?> *</label>
            <input type="text" class="form-control text-uppercase" id="org_member_initials_edit" name="member_initials" required maxlength="4"
              value="<?php echo htmlspecialchars($orgMemberInitials); ?>" pattern="[A-Za-z]{2,4}" autocomplete="off" style="text-transform: uppercase;" placeholder="AJ">
            <small class="text-muted d-block"><?php echo h(t('superadmin.organizations.form.member_initials_help')); ?></small>
          </div>
          <div class="form-group">
            <label for="org_nickname_edit"><?php echo h(t('superadmin.organizations.form.nickname')); ?></label>
            <input type="text" class="form-control" id="org_nickname_edit" name="nickname" maxlength="191" value="<?php echo htmlspecialchars($orgNickname); ?>">
          </div>
          <div class="form-group">
            <label for="org_address_edit"><?php echo h(t('superadmin.organizations.form.address')); ?></label>
            <textarea class="form-control" id="org_address_edit" name="address" rows="3"><?php echo htmlspecialchars($orgAddress); ?></textarea>
            <small class="text-muted d-block"><?php echo h(t('superadmin.organizations.form.address_help')); ?></small>
          </div>
          <div class="form-group">
            <label for="org_maps_url_edit"><?php echo h(t('superadmin.organizations.form.maps_url')); ?></label>
            <input type="url" class="form-control" id="org_maps_url_edit" name="maps_url" maxlength="512"
              value="<?php echo htmlspecialchars($orgMapsUrl); ?>" placeholder="https://maps.app.goo.gl/…">
            <small class="text-muted d-block"><?php echo h(t('superadmin.organizations.form.maps_url_help')); ?></small>
            <?php if ($orgMapsUrl !== ''): ?>
              <div class="mt-2"><?php echo maps_navigate_button($orgMapsUrl, ['class' => 'maps-nav-btn maps-nav-btn--soft']); ?></div>
            <?php endif; ?>
          </div>
          <button type="submit" class="btn btn-outline-primary"><?php echo h(t('superadmin.organizations.show.save_org')); ?></button>
        </form>
      </div>
    </div>
    <div class="card mb-4">
      <div class="card-body">
        <h4 class="card-title"><?php echo h(t('superadmin.organizations.show.admin_title')); ?></h4>
        <p class="text-muted small"><?php echo h(t('superadmin.organizations.show.admin_help')); ?></p>
        <form method="post" action="<?php echo htmlspecialchars($b); ?>/superadmin/organization/create-user" id="form_create_user" autocomplete="off" data-no-submit-guard="1" data-ajax-admin-create="1">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="organization_id" value="<?php echo $oid; ?>">
          <input type="hidden" name="role" value="admin">
          <?php
          $nameFieldRow = [];
          $nameFieldPrefix = '';
          $nameFieldIdPrefix = 'new_';
          require BASE_PATH . '/app/Views/partials/person_name_fields.php';
          ?>
          <div class="form-group">
            <label for="new_email"><?php echo h(t('superadmin.organizations.show.admin_email')); ?></label>
            <input type="email" class="form-control" name="email" id="new_email" required placeholder="Login details will be emailed here" autocomplete="email">
            <div id="new_email_duplicate_warn" class="alert alert-danger mt-2 py-2 mb-0 small d-none" role="alert"><?php echo h(t('superadmin.organizations.show.email_duplicate')); ?></div>
          </div>
          <div class="form-group">
            <label for="new_phone_visible"><?php echo h(t('superadmin.organizations.show.admin_phone')); ?> *</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">+91</span>
              </div>
              <input type="tel" class="form-control" id="new_phone_visible" maxlength="10" inputmode="numeric" pattern="[0-9]*" required placeholder="10-digit mobile" autocomplete="off">
            </div>
            <input type="hidden" name="phone" id="new_phone_hidden" value="">
            <div id="new_phone_duplicate_warn" class="alert alert-danger mt-2 py-2 mb-0 small d-none" role="alert"><?php echo h(t('superadmin.organizations.show.phone_duplicate')); ?></div>
          </div>
          <p class="text-muted small"><?php echo h(t('superadmin.organizations.show.admin_notice')); ?></p>
          <p class="text-muted small mb-1" id="create_admin_status" aria-live="polite"></p>
          <button type="submit" class="btn btn-success" id="btn_create_user_submit"><?php echo h(t('superadmin.organizations.show.create_admin')); ?></button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title"><?php echo h(t('superadmin.organizations.show.admins_heads_title')); ?></h4>
        <p class="text-muted small"><?php echo h(t('superadmin.organizations.show.admins_heads_help')); ?></p>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th><?php echo h(t('superadmin.organizations.show.col_name')); ?></th>
                <th><?php echo h(t('superadmin.organizations.show.col_email')); ?></th>
                <th><?php echo h(t('superadmin.organizations.show.col_phone')); ?></th>
                <th><?php echo h(t('superadmin.organizations.show.col_role')); ?></th>
                <th><?php echo h(t('superadmin.organizations.show.col_admin_since')); ?></th>
                <th><?php echo h(t('superadmin.organizations.show.col_actions')); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($members)): ?>
                <tr><td colspan="6" class="text-muted"><?php echo h(t('superadmin.organizations.show.none_admins_heads')); ?></td></tr>
              <?php else: ?>
                <?php foreach ($members as $m): ?>
                  <tr>
                    <td>
                      <?php if (!empty($m['is_family_head']) && !empty($m['head_family_id'])): ?>
                        <a href="<?php echo htmlspecialchars($b); ?>/superadmin/organization/family?id=<?php echo (int) $m['head_family_id']; ?>&amp;organization_id=<?php echo $oid; ?>">
                          <?php echo htmlspecialchars((string) $m['name']); ?>
                        </a>
                      <?php else: ?>
                        <?php echo htmlspecialchars((string) $m['name']); ?>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars((string) ($m['email'] ?? '—')); ?></td>
                    <td><?php echo htmlspecialchars(\format_india_phone($m['phone'] ?? null)); ?></td>
                    <td>
                      <?php if (!empty($m['is_org_admin'])): ?>
                        <span class="badge badge-primary mr-1"><?php echo h(t('superadmin.organizations.show.badge_org_admin')); ?></span>
                      <?php endif; ?>
                      <?php if (!empty($m['is_family_head'])): ?>
                        <span class="badge badge-info"><?php echo h(t('superadmin.organizations.show.badge_family_head')); ?></span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars(format_pretty_date(isset($m['admin_since']) ? (string) $m['admin_since'] : null)); ?></td>
                    <td>
                      <?php if (!empty($m['is_org_admin'])): ?>
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-primary js-admin-inline-toggle"
                          data-user-id="<?php echo (int) $m['user_id']; ?>"
                        >
                          <?php echo h(t('common.edit')); ?>
                        </button>
                      <?php else: ?>
                        —
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php if (!empty($m['is_org_admin'])): ?>
                  <tr id="admin-inline-row-<?php echo (int) $m['user_id']; ?>" style="display:none; background:#fafafa;">
                    <td colspan="6">
                      <form method="post" action="<?php echo htmlspecialchars($b); ?>/superadmin/organization/update-admin" class="mb-0 py-2">
                        <input type="hidden" name="organization_id" value="<?php echo $oid; ?>">
                        <input type="hidden" name="admin_user_id" value="<?php echo (int) $m['user_id']; ?>">
                        <?php
                        $nameFieldRow = $m;
                        $nameFieldPrefix = '';
                        $nameFieldIdPrefix = 'inline_' . (int) $m['user_id'] . '_';
                        require BASE_PATH . '/app/Views/partials/person_name_fields.php';
                        ?>
                        <div class="form-row">
                          <div class="form-group col-md-5 mb-0">
                            <label class="small mb-1"><?php echo h(t('superadmin.organizations.show.inline_email')); ?></label>
                            <input type="email" class="form-control form-control-sm" name="email" value="<?php echo htmlspecialchars((string) ($m['email'] ?? '')); ?>">
                          </div>
                          <div class="form-group col-md-3 mb-0">
                            <label class="small mb-1"><?php echo h(t('superadmin.organizations.show.inline_phone')); ?> *</label>
                            <input type="text" class="form-control form-control-sm" name="phone" required maxlength="10" pattern="\d{10}" value="<?php echo htmlspecialchars(preg_replace('/^91/', '', preg_replace('/\D+/', '', (string) ($m['phone'] ?? '')))); ?>">
                          </div>
                          <div class="form-group col-md-2 mb-0 d-flex align-items-end">
                            <button type="submit" class="btn btn-sm btn-primary"><?php echo h(t('common.save')); ?></button>
                          </div>
                        </div>
                      </form>
                    </td>
                  </tr>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var base = <?php echo json_encode($b); ?>;
  var orgId = <?php echo (int) $oid; ?>;
  var form = document.querySelector('form[action$="/superadmin/organization/update"]');
  var input = document.getElementById('org_short_code_edit');
  var warn = document.getElementById('short_code_edit_warn');
  var btn = form ? form.querySelector('button[type="submit"]') : null;
  if (!form || !input) return;
  var timer = null;
  var invalid = false;
  var last = '';

  function sync() {
    input.value = (input.value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 12);
  }

  function setInvalid(on, message) {
    invalid = !!on;
    if (warn) {
      warn.textContent = message || '';
      warn.classList.toggle('d-none', !invalid);
    }
    input.classList.toggle('is-invalid', invalid);
    if (btn) btn.disabled = invalid;
  }

  function check() {
    sync();
    var v = (input.value || '').trim();
    if (v.length < 2) {
      setInvalid(false, '');
      return;
    }
    last = v;
    fetch(base + '/superadmin/check-org-code?code=' + encodeURIComponent(v) + '&organization_id=' + encodeURIComponent(String(orgId)), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        sync();
        if ((input.value || '').trim() !== last) return;
        if (!d.checked || d.valid) {
          setInvalid(false, '');
          return;
        }
        setInvalid(true, d.error || 'Invalid short name.');
      })
      .catch(function () { setInvalid(false, ''); });
  }

  input.addEventListener('input', function () {
    sync();
    clearTimeout(timer);
    timer = setTimeout(check, 400);
  });
  input.addEventListener('blur', check);
  form.addEventListener('submit', function (e) {
    sync();
    if (invalid) e.preventDefault();
  });
  sync();
})();
</script>
<script>
(function () {
  var base = <?php echo json_encode($b); ?>;
  var orgId = <?php echo (int) $oid; ?>;
  var form = document.getElementById('form_create_user');
  var emailInput = document.getElementById('new_email');
  var emailWarn = document.getElementById('new_email_duplicate_warn');
  var phoneVis = document.getElementById('new_phone_visible');
  var phoneHid = document.getElementById('new_phone_hidden');
  var phoneWarn = document.getElementById('new_phone_duplicate_warn');
  var btn = document.getElementById('btn_create_user_submit');
  if (!form || !emailInput || !emailWarn || !phoneVis || !phoneHid || !phoneWarn || !btn) return;

  var emailTimer = null;
  var phoneTimer = null;
  var lastEmailChecked = '';
  var lastPhoneChecked = '';
  var emailDup = false;
  var phoneDup = false;

  function refreshSubmit() {
    btn.disabled = emailDup || phoneDup;
  }

  function setEmailDup(on) {
    emailDup = !!on;
    if (emailDup) {
      emailWarn.classList.remove('d-none');
      emailInput.classList.add('is-invalid');
    } else {
      emailWarn.classList.add('d-none');
      emailInput.classList.remove('is-invalid');
    }
    refreshSubmit();
  }

  function setPhoneDup(on) {
    phoneDup = !!on;
    if (phoneDup) {
      phoneWarn.classList.remove('d-none');
      phoneVis.classList.add('is-invalid');
    } else {
      phoneWarn.classList.add('d-none');
      phoneVis.classList.remove('is-invalid');
    }
    refreshSubmit();
  }

  function syncPhoneHidden() {
    var d = (phoneVis.value || '').replace(/\D/g, '').slice(0, 10);
    phoneVis.value = d;
    phoneHid.value = d.length === 10 ? '91' + d : '';
  }

  function runEmailCheck() {
    var v = (emailInput.value || '').trim();
    if (v === '' || v.indexOf('@') === -1) {
      lastEmailChecked = '';
      setEmailDup(false);
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) {
      setEmailDup(false);
      return;
    }
    lastEmailChecked = v;
    fetch(base + '/superadmin/check-email?organization_id=' + encodeURIComponent(String(orgId)) + '&email=' + encodeURIComponent(v), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if ((emailInput.value || '').trim() !== lastEmailChecked) return;
        setEmailDup(!!data.exists);
      })
      .catch(function () { setEmailDup(false); });
  }

  function runPhoneCheck() {
    syncPhoneHidden();
    var full = phoneHid.value;
    if (full.length !== 12) {
      lastPhoneChecked = '';
      setPhoneDup(false);
      return;
    }
    lastPhoneChecked = full;
    fetch(base + '/superadmin/check-phone?organization_id=' + encodeURIComponent(String(orgId)) + '&phone=' + encodeURIComponent(full), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        syncPhoneHidden();
        if (phoneHid.value !== lastPhoneChecked) return;
        setPhoneDup(!!data.exists);
      })
      .catch(function () { setPhoneDup(false); });
  }

  emailInput.addEventListener('input', function () {
    clearTimeout(emailTimer);
    emailTimer = setTimeout(runEmailCheck, 400);
  });
  emailInput.addEventListener('blur', runEmailCheck);

  phoneVis.addEventListener('input', function () {
    syncPhoneHidden();
    clearTimeout(phoneTimer);
    phoneTimer = setTimeout(runPhoneCheck, 400);
  });
  phoneVis.addEventListener('blur', function () {
    syncPhoneHidden();
    runPhoneCheck();
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    syncPhoneHidden();
    if (btn.disabled || form.getAttribute('data-submitting') === '1') {
      return;
    }
    if (emailDup || phoneDup) {
      return;
    }
    if (window.SZVS_CSRF && window.SZVS_CSRF.ensureFormToken) {
      window.SZVS_CSRF.ensureFormToken(form);
    }
    form.setAttribute('data-submitting', '1');
    btn.disabled = true;
    btn.setAttribute('aria-busy', 'true');
    if (!btn.getAttribute('data-original-label')) {
      btn.setAttribute('data-original-label', btn.innerHTML);
    }
    btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>Creating…';
    var statusEl = document.getElementById('create_admin_status');
    if (statusEl) {
      statusEl.textContent = 'Creating admin account…';
    }
    var fd = new FormData(form);
    var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
    var timeoutId = controller ? window.setTimeout(function () { controller.abort(); }, 45000) : null;
    fetch(form.action, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      signal: controller ? controller.signal : undefined,
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(function (r) {
        return r.json().then(function (body) {
          return { ok: r.ok, body: body };
        }).catch(function () {
          return { ok: false, body: { error: 'Invalid server response (HTTP ' + r.status + ').' } };
        });
      })
      .then(function (res) {
        if (timeoutId) {
          window.clearTimeout(timeoutId);
        }
        if (res.body && res.body.redirect) {
          var redirectUrl = res.body.redirect;
          var drain = window.SZVS_MAIL && window.SZVS_MAIL.drain
            ? window.SZVS_MAIL.drain(30000)
            : Promise.resolve();
          if (statusEl) {
            statusEl.textContent = 'Sending invite email…';
          }
          drain.finally(function () {
            window.location.href = redirectUrl;
          });
          return;
        }
        var msg = (res.body && (res.body.error || res.body.message)) || 'Could not create admin.';
        if (statusEl) {
          statusEl.textContent = msg;
        }
        alert(msg);
        form.removeAttribute('data-submitting');
        btn.removeAttribute('aria-busy');
        var original = btn.getAttribute('data-original-label');
        if (original) {
          btn.innerHTML = original;
        }
        btn.disabled = emailDup || phoneDup;
      })
      .catch(function () {
        if (timeoutId) {
          window.clearTimeout(timeoutId);
        }
        var msg = 'Request timed out or failed. Refresh the page — the admin may already have been created.';
        if (statusEl) {
          statusEl.textContent = msg;
        }
        alert(msg);
        window.location.reload();
      });
  });

  var inlineToggles = document.querySelectorAll('.js-admin-inline-toggle');
  inlineToggles.forEach(function (btnEdit) {
    btnEdit.addEventListener('click', function () {
      var uid = btnEdit.getAttribute('data-user-id');
      if (!uid) return;
      var row = document.getElementById('admin-inline-row-' + uid);
      if (!row) return;
      var open = row.style.display !== 'none';
      row.style.display = open ? 'none' : '';
      btnEdit.textContent = open ? 'Edit' : 'Close';
    });
  });
})();
</script>
