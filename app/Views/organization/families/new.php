<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
?>
<div class="row">
  <div class="col-12 border-bottom d-flex justify-content-between align-items-center flex-wrap pb-3 mb-0">
    <h3 class="mb-0"><?php echo htmlspecialchars(t('family.new')); ?></h3>
    <a class="btn btn-light btn-sm" href="<?php echo htmlspecialchars($b); ?>/organization/families"><?php echo htmlspecialchars(t('family.new.back')); ?></a>
  </div>
</div>
<?php if ($flashOk): ?>
  <div class="alert alert-success mt-3 mb-0"><?php echo htmlspecialchars($flashOk); ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
  <div class="alert alert-danger mt-3 mb-0"><?php echo htmlspecialchars($flashErr); ?></div>
<?php endif; ?>
<div class="row mt-4">
  <div class="col-lg-6 col-xl-5">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-4"><?php echo htmlspecialchars(t('family.new.head_title')); ?></h5>
        <p class="text-muted small"><?php echo htmlspecialchars(t('family.new.desc')); ?></p>
        <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/families" id="form-new-family">
          <?php
          $nameFieldRow = [];
          $nameFieldPrefix = 'head_';
          $nameFieldIdPrefix = 'head_';
          require BASE_PATH . '/app/Views/partials/person_name_fields.php';
          ?>
          <div class="form-group">
            <label for="head_email"><?php echo htmlspecialchars(t('family.new.email')); ?></label>
            <input type="email" class="form-control" name="head_email" id="head_email" required autocomplete="email">
            <div id="head_email_warn" class="alert alert-danger mt-2 py-2 mb-0 small d-none" role="alert"><?php echo htmlspecialchars(t('family.new.email_exists')); ?></div>
          </div>
          <div class="form-group">
            <label for="head_phone_visible"><?php echo htmlspecialchars(t('family.new.phone')); ?> *</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">+91</span>
              </div>
              <input type="tel" class="form-control" id="head_phone_visible" maxlength="10" inputmode="numeric" pattern="[0-9]*" required placeholder="<?php echo htmlspecialchars(t('family.new.phone_placeholder')); ?>" autocomplete="tel">
            </div>
            <input type="hidden" name="head_phone" id="head_phone_hidden" value="">
            <div id="head_phone_warn" class="alert alert-danger mt-2 py-2 mb-0 small d-none" role="alert"><?php echo htmlspecialchars(t('family.new.phone_exists')); ?></div>
          </div>
          <button type="submit" class="btn btn-primary" id="btn_new_family_submit"><?php echo htmlspecialchars(t('family.new.submit')); ?></button>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  var base = <?php echo json_encode($b); ?>;
  var form = document.getElementById('form-new-family');
  var emailIn = document.getElementById('head_email');
  var emailWarn = document.getElementById('head_email_warn');
  var phoneVis = document.getElementById('head_phone_visible');
  var phoneHid = document.getElementById('head_phone_hidden');
  var phoneWarn = document.getElementById('head_phone_warn');
  var btn = document.getElementById('btn_new_family_submit');
  var emailTimer = null;
  var phoneTimer = null;
  var lastEmail = '';
  var lastPhone = '';
  var emailDup = false;
  var phoneDup = false;

  function refreshSubmit() {
    btn.disabled = emailDup || phoneDup;
  }

  function setEmailDup(on) {
    emailDup = !!on;
    if (emailDup) {
      emailWarn.classList.remove('d-none');
      emailIn.classList.add('is-invalid');
    } else {
      emailWarn.classList.add('d-none');
      emailIn.classList.remove('is-invalid');
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
    fetch(base + '/organization/check-email?email=' + encodeURIComponent(v), { credentials: 'same-origin' })
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
    fetch(base + '/organization/check-phone?phone=' + encodeURIComponent(full), { credentials: 'same-origin' })
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

  if (form) {
    form.addEventListener('submit', function (e) {
      syncPhone();
      if (emailDup || phoneDup) {
        e.preventDefault();
      }
    });
  }

  syncPhone();
  refreshSubmit();
})();
</script>
