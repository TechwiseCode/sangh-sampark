<?php
$b = base_url();
$err = $formError ?? null;
?>
<div class="row">
  <div class="col-12 border-bottom">
    <h3 class="mb-0"><?php echo h(t('superadmin.organizations.form.title')); ?></h3>
    <p class="text-muted small mb-0"><?php echo h(t('superadmin.organizations.form.subtitle')); ?></p>
  </div>
</div>
<div class="row" style="padding-top: 24px;">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body">
        <?php if (!empty($err)): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
        <?php endif; ?>
        <form method="post" action="<?php echo htmlspecialchars($b); ?>/superadmin/organizations" id="form_new_org" autocomplete="off"
          data-submit-guard-note="Creating organization and admin. Invite email sends separately — please do not click again.">
          <h5 class="mb-3"><?php echo h(t('superadmin.organizations.form.org_section')); ?></h5>
          <div class="form-group">
            <label for="org_name"><?php echo h(t('superadmin.organizations.form.org_name')); ?></label>
            <input type="text" class="form-control" name="name" id="org_name" value="<?php echo htmlspecialchars($nameDraft ?? ''); ?>" required>
          </div>
          <div class="form-group">
            <label for="org_short_code"><?php echo h(t('superadmin.organizations.form.short_code')); ?> *</label>
            <input type="text" class="form-control text-uppercase org-short-code-input" name="short_code" id="org_short_code"
              value="<?php echo htmlspecialchars((string) ($shortCodeDraft ?? '')); ?>" required maxlength="12"
              pattern="[A-Za-z0-9]{2,12}" autocomplete="off" style="text-transform: uppercase;">
            <small class="text-muted d-block"><?php echo h(t('superadmin.organizations.form.short_code_help')); ?></small>
            <div id="short_code_warn" class="alert alert-danger mt-2 py-2 mb-0 small d-none" role="alert"></div>
          </div>
          <div class="form-group">
            <label for="org_member_initials"><?php echo h(t('superadmin.organizations.form.member_initials')); ?></label>
            <input type="text" class="form-control text-uppercase" name="member_initials" id="org_member_initials"
              value="<?php echo htmlspecialchars((string) ($memberInitialsDraft ?? '')); ?>" maxlength="4"
              pattern="[A-Za-z]{2,4}" autocomplete="off" style="text-transform: uppercase;" placeholder="AJ">
            <small class="text-muted d-block"><?php echo h(t('superadmin.organizations.form.member_initials_help')); ?></small>
          </div>
          <div class="form-group">
            <label for="org_nickname"><?php echo h(t('superadmin.organizations.form.nickname')); ?></label>
            <input type="text" class="form-control" name="nickname" id="org_nickname" value="<?php echo htmlspecialchars((string) ($nicknameDraft ?? '')); ?>" maxlength="191">
            <small class="text-muted d-block"><?php echo h(t('superadmin.organizations.form.nickname_help')); ?></small>
          </div>
          <div class="form-group">
            <label for="org_address"><?php echo h(t('superadmin.organizations.form.address')); ?></label>
            <textarea class="form-control" name="address" id="org_address" rows="3"><?php echo htmlspecialchars((string) ($addressDraft ?? '')); ?></textarea>
            <small class="text-muted d-block"><?php echo h(t('superadmin.organizations.form.address_help')); ?></small>
          </div>
          <div class="form-group">
            <label for="org_maps_url"><?php echo h(t('superadmin.organizations.form.maps_url')); ?></label>
            <input type="url" class="form-control" name="maps_url" id="org_maps_url"
              value="<?php echo htmlspecialchars((string) ($mapsUrlDraft ?? '')); ?>" maxlength="512"
              placeholder="https://maps.app.goo.gl/…">
            <small class="text-muted d-block"><?php echo h(t('superadmin.organizations.form.maps_url_help')); ?></small>
          </div>
          <hr>
          <h5 class="mb-3"><?php echo h(t('superadmin.organizations.form.admin_section')); ?></h5>
          <p class="text-muted small"><?php echo h(t('superadmin.organizations.form.admin_help')); ?></p>
          <?php
          $nameFieldRow = isset($adminNamePartsDraft) && is_array($adminNamePartsDraft) ? $adminNamePartsDraft : ['first_name' => '', 'middle_name' => null, 'last_name' => ''];
          $nameFieldPrefix = 'admin_';
          $nameFieldIdPrefix = 'admin_';
          require BASE_PATH . '/app/Views/partials/person_name_fields.php';
          ?>
          <div class="form-group">
            <label for="admin_email"><?php echo h(t('superadmin.organizations.form.admin_email')); ?></label>
            <input type="email" class="form-control" name="admin_email" id="admin_email" value="<?php echo htmlspecialchars($adminEmailDraft ?? ''); ?>" required autocomplete="email">
            <div id="admin_email_warn" class="alert alert-danger mt-2 py-2 mb-0 small d-none" role="alert"><?php echo h(t('superadmin.organizations.form.email_belongs_superadmin')); ?></div>
          </div>
          <div class="form-group">
            <label for="admin_phone_visible"><?php echo h(t('superadmin.organizations.form.admin_phone')); ?> *</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">+91</span>
              </div>
              <input type="tel" class="form-control" id="admin_phone_visible" maxlength="10" inputmode="numeric" pattern="[0-9]*" required placeholder="<?php echo h(t('family.new.phone_placeholder')); ?>" value="<?php echo htmlspecialchars($adminPhoneDraft ?? ''); ?>" autocomplete="tel">
            </div>
            <input type="hidden" name="admin_phone" id="admin_phone_hidden" value="">
            <div id="admin_phone_warn" class="alert alert-danger mt-2 py-2 mb-0 small d-none" role="alert"><?php echo h(t('superadmin.organizations.form.phone_belongs_superadmin')); ?></div>
          </div>
          <button type="submit" class="btn btn-success" id="btn_new_org_submit" data-submit-wait="Creating…"><?php echo h(t('superadmin.organizations.form.submit')); ?></button>
          <a class="btn btn-light ml-2" href="<?php echo htmlspecialchars($b); ?>/superadmin/organizations"><?php echo h(t('superadmin.organizations.form.cancel')); ?></a>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var base = <?php echo json_encode($b); ?>;
  var form = document.getElementById('form_new_org');
  var emailIn = document.getElementById('admin_email');
  var emailWarn = document.getElementById('admin_email_warn');
  var phoneVis = document.getElementById('admin_phone_visible');
  var phoneHid = document.getElementById('admin_phone_hidden');
  var phoneWarn = document.getElementById('admin_phone_warn');
  var btn = document.getElementById('btn_new_org_submit');
  var shortCodeIn = document.getElementById('org_short_code');
  var shortCodeWarn = document.getElementById('short_code_warn');
  var emailTimer = null;
  var phoneTimer = null;
  var shortCodeTimer = null;
  var lastEmail = '';
  var lastPhone = '';
  var lastShortCode = '';
  var emailDup = false;
  var phoneDup = false;
  var shortCodeInvalid = false;

  function refreshSubmit() {
    if (btn) btn.disabled = emailDup || phoneDup || shortCodeInvalid;
  }

  function setEmailDup(on) {
    emailDup = !!on;
    if (!emailWarn || !emailIn) return;
    emailWarn.classList.toggle('d-none', !emailDup);
    emailIn.classList.toggle('is-invalid', emailDup);
    refreshSubmit();
  }

  function setPhoneDup(on) {
    phoneDup = !!on;
    if (!phoneWarn || !phoneVis) return;
    phoneWarn.classList.toggle('d-none', !phoneDup);
    phoneVis.classList.toggle('is-invalid', phoneDup);
    refreshSubmit();
  }

  function setShortCodeInvalid(on, message) {
    shortCodeInvalid = !!on;
    if (!shortCodeWarn || !shortCodeIn) return;
    shortCodeWarn.textContent = message || '';
    shortCodeWarn.classList.toggle('d-none', !shortCodeInvalid);
    shortCodeIn.classList.toggle('is-invalid', shortCodeInvalid);
    refreshSubmit();
  }

  function syncShortCode() {
    if (!shortCodeIn) return;
    shortCodeIn.value = (shortCodeIn.value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 12);
  }

  function checkShortCode() {
    syncShortCode();
    var v = (shortCodeIn.value || '').trim();
    if (v.length < 2) {
      setShortCodeInvalid(false, '');
      return;
    }
    lastShortCode = v;
    fetch(base + '/superadmin/check-org-code?code=' + encodeURIComponent(v), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        syncShortCode();
        if ((shortCodeIn.value || '').trim() !== lastShortCode) return;
        if (!d.checked) {
          setShortCodeInvalid(false, '');
          return;
        }
        if (!d.valid) {
          setShortCodeInvalid(true, d.error || 'Invalid short name.');
          return;
        }
        setShortCodeInvalid(false, '');
      })
      .catch(function () { setShortCodeInvalid(false, ''); });
  }

  function syncPhone() {
    if (!phoneVis || !phoneHid) return;
    var d = (phoneVis.value || '').replace(/\D/g, '').slice(0, 10);
    phoneVis.value = d;
    phoneHid.value = d.length === 10 ? '91' + d : '';
  }

  function checkEmail() {
    var v = (emailIn.value || '').trim();
    if (v === '' || v.indexOf('@') === -1) {
      setEmailDup(false);
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) {
      setEmailDup(false);
      return;
    }
    lastEmail = v;
    fetch(base + '/superadmin/check-email?email=' + encodeURIComponent(v), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if ((emailIn.value || '').trim() !== lastEmail) return;
        setEmailDup(!!d.exists);
      })
      .catch(function () { setEmailDup(false); });
  }

  function checkPhone() {
    syncPhone();
    var full = phoneHid.value;
    if (full.length !== 12) {
      setPhoneDup(false);
      return;
    }
    lastPhone = full;
    fetch(base + '/superadmin/check-phone?phone=' + encodeURIComponent(full), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        syncPhone();
        if (phoneHid.value !== lastPhone) return;
        setPhoneDup(!!d.exists);
      })
      .catch(function () { setPhoneDup(false); });
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
  if (emailIn) {
    emailIn.addEventListener('input', function () {
      clearTimeout(emailTimer);
      emailTimer = setTimeout(checkEmail, 400);
    });
    emailIn.addEventListener('blur', checkEmail);
  }
  if (shortCodeIn) {
    shortCodeIn.addEventListener('input', function () {
      syncShortCode();
      clearTimeout(shortCodeTimer);
      shortCodeTimer = setTimeout(checkShortCode, 400);
    });
    shortCodeIn.addEventListener('blur', checkShortCode);
  }
  if (form) {
    form.addEventListener('submit', function (e) {
      syncPhone();
      syncShortCode();
      if (emailDup || phoneDup || shortCodeInvalid) e.preventDefault();
    });
  }
  syncPhone();
  syncShortCode();
  refreshSubmit();
})();
</script>
