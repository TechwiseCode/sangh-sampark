<?php
$b = base_url();
$formError = $formError ?? null;
?>
<div class="row">
  <div class="col-lg-8">
    <h3 class="mb-3"><?php echo h(t('superadmin.admins.form.title')); ?></h3>
    <p class="text-muted small"><?php echo h(t('superadmin.admins.form.subtitle')); ?></p>
    <?php if ($formError): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars((string) $formError); ?></div>
    <?php endif; ?>
    <form method="post" action="<?php echo htmlspecialchars($b); ?>/superadmin/admins" class="card" id="form_new_admin" autocomplete="off" data-no-submit-guard="1">
      <?php echo csrf_field(); ?>
      <div class="card-body">
        <div class="form-group mb-3">
          <label for="organization_id"><?php echo h(t('superadmin.admins.form.organization')); ?></label>
          <select class="form-control" id="organization_id" name="organization_id" required data-no-search-dropdown>
            <option value=""><?php echo h(t('superadmin.admins.form.select_placeholder')); ?></option>
            <?php foreach (($organizations ?? []) as $org): ?>
              <option value="<?php echo (int) $org['id']; ?>"<?php echo (int) ($organizationIdDraft ?? 0) === (int) $org['id'] ? ' selected' : ''; ?>>
                <?php echo htmlspecialchars((string) $org['name']); ?> (<?php echo htmlspecialchars((string) $org['org_code']); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group mb-3">
          <?php
          $nameFieldRow = isset($namePartsDraft) && is_array($namePartsDraft)
            ? $namePartsDraft
            : split_person_full_name((string) ($nameDraft ?? ''));
          $nameFieldPrefix = '';
          require BASE_PATH . '/app/Views/partials/person_name_fields.php';
          ?>
        </div>
        <div class="form-group mb-3">
          <label for="email"><?php echo h(t('superadmin.admins.form.email_required')); ?> <span class="text-danger">*</span></label>
          <input type="email" class="form-control" id="email" name="email" required
            value="<?php echo htmlspecialchars((string) ($emailDraft ?? '')); ?>" autocomplete="email">
          <div id="admin_email_warn" class="alert alert-danger mt-2 py-2 mb-0 small d-none" role="alert"><?php echo h(t('superadmin.admins.form.email_duplicate')); ?></div>
        </div>
        <div class="form-group mb-3">
          <label for="phone_visible"><?php echo h(t('superadmin.admins.form.phone_required')); ?> <span class="text-danger">*</span></label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text">+91</span>
            </div>
            <input type="tel" class="form-control" id="phone_visible" maxlength="10" inputmode="numeric" pattern="[0-9]*" required
              value="<?php echo htmlspecialchars(preg_replace('/^91/', '', preg_replace('/\D+/', '', (string) ($phoneDraft ?? '')))); ?>">
          </div>
          <input type="hidden" name="phone" id="phone_hidden" value="<?php echo htmlspecialchars((string) ($phoneDraft ?? '')); ?>">
          <div id="admin_phone_warn" class="alert alert-danger mt-2 py-2 mb-0 small d-none" role="alert"><?php echo h(t('superadmin.admins.form.phone_duplicate')); ?></div>
        </div>
        <p class="text-muted small mb-1" id="new_admin_status" aria-live="polite"></p>
        <button type="submit" class="btn btn-primary" id="btn_new_admin_submit"><?php echo h(t('superadmin.admins.form.submit')); ?></button>
        <a class="btn btn-light ms-2" href="<?php echo htmlspecialchars($b); ?>/superadmin/members"><?php echo h(t('superadmin.admins.form.cancel')); ?></a>
      </div>
    </form>
  </div>
</div>
<script>
(function () {
  var base = <?php echo json_encode($b); ?>;
  var form = document.getElementById('form_new_admin');
  var orgSelect = document.getElementById('organization_id');
  var emailIn = document.getElementById('email');
  var emailWarn = document.getElementById('admin_email_warn');
  var phoneVis = document.getElementById('phone_visible');
  var phoneHid = document.getElementById('phone_hidden');
  var phoneWarn = document.getElementById('admin_phone_warn');
  var btn = document.getElementById('btn_new_admin_submit');
  var statusEl = document.getElementById('new_admin_status');
  var emailTimer = null;
  var phoneTimer = null;
  var lastEmail = '';
  var lastPhone = '';
  var emailDup = false;
  var phoneDup = false;

  function selectedOrgId() {
    return parseInt(orgSelect && orgSelect.value ? orgSelect.value : '0', 10) || 0;
  }

  function refreshSubmit() {
    if (!btn) return;
    btn.disabled = emailDup || phoneDup || selectedOrgId() < 1;
  }

  function setEmailDup(on) {
    emailDup = !!on;
    if (emailWarn) emailWarn.classList.toggle('d-none', !emailDup);
    if (emailIn) emailIn.classList.toggle('is-invalid', emailDup);
    refreshSubmit();
  }

  function setPhoneDup(on) {
    phoneDup = !!on;
    if (phoneWarn) phoneWarn.classList.toggle('d-none', !phoneDup);
    if (phoneVis) phoneVis.classList.toggle('is-invalid', phoneDup);
    refreshSubmit();
  }

  function syncPhone() {
    if (!phoneVis || !phoneHid) return;
    var d = (phoneVis.value || '').replace(/\D/g, '').slice(0, 10);
    phoneVis.value = d;
    phoneHid.value = d.length === 10 ? '91' + d : '';
  }

  function checkEmail() {
    var orgId = selectedOrgId();
    var v = (emailIn.value || '').trim();
    if (orgId < 1 || v === '' || v.indexOf('@') === -1) {
      setEmailDup(false);
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) {
      setEmailDup(false);
      return;
    }
    lastEmail = v;
    fetch(base + '/superadmin/check-email?organization_id=' + encodeURIComponent(String(orgId)) + '&email=' + encodeURIComponent(v), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if ((emailIn.value || '').trim() !== lastEmail || selectedOrgId() !== orgId) return;
        setEmailDup(!!d.exists);
      })
      .catch(function () { setEmailDup(false); });
  }

  function checkPhone() {
    syncPhone();
    var orgId = selectedOrgId();
    var full = phoneHid.value;
    if (orgId < 1 || full.length !== 12) {
      setPhoneDup(false);
      return;
    }
    lastPhone = full;
    fetch(base + '/superadmin/check-phone?organization_id=' + encodeURIComponent(String(orgId)) + '&phone=' + encodeURIComponent(full), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        syncPhone();
        if (phoneHid.value !== lastPhone || selectedOrgId() !== orgId) return;
        setPhoneDup(!!d.exists);
      })
      .catch(function () { setPhoneDup(false); });
  }

  if (orgSelect) {
    orgSelect.addEventListener('change', function () {
      checkEmail();
      checkPhone();
      refreshSubmit();
    });
  }
  if (emailIn) {
    emailIn.addEventListener('input', function () {
      clearTimeout(emailTimer);
      emailTimer = setTimeout(checkEmail, 400);
    });
    emailIn.addEventListener('blur', checkEmail);
  }
  if (phoneVis) {
    phoneVis.addEventListener('input', function () {
      syncPhone();
      clearTimeout(phoneTimer);
      phoneTimer = setTimeout(checkPhone, 400);
    });
    phoneVis.addEventListener('blur', function () {
      syncPhone();
      checkPhone();
    });
  }
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      syncPhone();
      if (btn.disabled || form.getAttribute('data-submitting') === '1') {
        return;
      }
      if (emailDup || phoneDup || selectedOrgId() < 1) {
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
          if (timeoutId) window.clearTimeout(timeoutId);
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
          if (statusEl) statusEl.textContent = msg;
          alert(msg);
          form.removeAttribute('data-submitting');
          btn.removeAttribute('aria-busy');
          var original = btn.getAttribute('data-original-label');
          if (original) {
            btn.innerHTML = original;
          }
          refreshSubmit();
        })
        .catch(function () {
          if (timeoutId) window.clearTimeout(timeoutId);
          var msg = 'Request timed out or failed. Refresh the page — the admin may already have been created.';
          if (statusEl) statusEl.textContent = msg;
          alert(msg);
          window.location.reload();
        });
    });
  }
  syncPhone();
  refreshSubmit();
})();
</script>
