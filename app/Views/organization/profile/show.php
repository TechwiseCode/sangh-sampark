<?php
$u = $profileUser ?? [];
$memberships = $memberships ?? [];
$p = $profileDetails ?? [];
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$mustCompleteProfile = !empty($mustCompleteProfile);
$phoneRaw = preg_replace('/\D+/', '', (string) ($u['phone'] ?? ''));
$phoneDisplay = $phoneRaw;
if (strlen($phoneRaw) === 12 && strpos($phoneRaw, '91') === 0) {
  $phoneDisplay = substr($phoneRaw, 2);
}
$nativeSameDefault = ((string) ($p['native_city'] ?? '') === '' && (string) ($p['native_state'] ?? '') === '')
  || (
    strtolower(trim((string) ($p['native_city'] ?? ''))) === strtolower(trim((string) ($p['city'] ?? '')))
    && strtolower(trim((string) ($p['native_state'] ?? ''))) === strtolower(trim((string) ($p['state'] ?? '')))
  );
$bloodGroup = normalize_blood_group($p['blood_group'] ?? '') ?? '';
$gender = trim((string) ($p['gender'] ?? ''));
$maritalStatus = profile_marital_status_from_row($p);
$area = trim((string) ($p['area'] ?? ''));
$professionType = strtolower(trim((string) ($p['profession_type'] ?? '')));
$photoUrl = user_photo_url(isset($u['photo_path']) ? (string) $u['photo_path'] : null);
$photoInitials = user_photo_initials((string) ($u['name'] ?? ''));
$hasPhoto = $photoUrl !== null;
?>
<div class="row">
  <div class="col-12 border-bottom">
    <h3 class="mb-0"><?php echo htmlspecialchars(t('profile.title')); ?></h3>
  </div>
</div>
<?php if ($flashOk): ?>
  <div class="alert alert-success mt-3"><?php echo htmlspecialchars($flashOk); ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
  <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($flashErr); ?></div>
<?php endif; ?>
<?php if ($mustCompleteProfile): ?>
  <div class="alert alert-warning mt-3"><?php echo htmlspecialchars(t('profile.complete_required')); ?></div>
<?php endif; ?>
<div class="row" style="padding-top: 16px;">
  <div class="col-lg-10">
    <div class="card mb-3 profile-photo-card">
      <div class="card-body">
        <h4 class="card-title mb-3"><?php echo htmlspecialchars(t('profile.photo')); ?></h4>
        <div class="profile-photo-row">
          <div class="profile-photo-preview" aria-hidden="true">
            <?php if ($hasPhoto): ?>
              <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="" class="profile-photo-img">
            <?php else: ?>
              <span class="profile-photo-placeholder"><?php echo htmlspecialchars($photoInitials); ?></span>
            <?php endif; ?>
          </div>
          <div class="profile-photo-actions">
            <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/profile/photo" enctype="multipart/form-data" class="mb-2">
              <label for="profile_photo" class="small text-muted d-block mb-1"><?php echo htmlspecialchars(t('profile.upload_hint')); ?></label>
              <div class="d-flex flex-wrap align-items-center" style="gap:0.5rem;">
                <input type="file" class="form-control-file" id="profile_photo" name="photo" accept="image/jpeg,image/png,image/webp" required>
                <button type="submit" class="btn btn-primary btn-sm"><?php echo htmlspecialchars(t('profile.upload_btn')); ?></button>
              </div>
            </form>
            <?php if ($hasPhoto): ?>
              <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/profile/photo" class="d-inline"
                onsubmit="return confirm(<?php echo json_encode(t('profile.remove_confirm')); ?>);">
                <input type="hidden" name="remove_photo" value="1">
                <button type="submit" class="btn btn-link btn-sm text-danger p-0"><?php echo htmlspecialchars(t('profile.remove')); ?></button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/profile/update">
          <?php
          $nameFieldRow = $u;
          $nameFieldPrefix = '';
          $nameFieldIdPrefix = 'profile_';
          require BASE_PATH . '/app/Views/partials/person_name_fields.php';
          ?>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label><?php echo h(t('profile.email')); ?></label>
              <input type="text" class="form-control" value="<?php echo htmlspecialchars((string) ($u['email'] ?? '')); ?>" disabled>
            </div>
            <div class="form-group col-md-6">
              <label for="profile_phone_visible"><?php echo htmlspecialchars(t('profile.phone')); ?> *</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text">+91</span>
                </div>
                <input type="text" class="form-control" id="profile_phone_visible" maxlength="10" pattern="\d{10}" required value="<?php echo htmlspecialchars((string) $phoneDisplay); ?>" placeholder="10-digit mobile" autocomplete="tel">
              </div>
              <input type="hidden" name="phone" id="profile_phone_hidden" value="<?php echo htmlspecialchars((string) ($u['phone'] ?? '')); ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-3">
              <label for="dob"><?php echo htmlspecialchars(t('profile.dob')); ?> *</label>
              <input type="date" class="form-control" id="dob" name="dob" required value="<?php echo htmlspecialchars(format_date_input(isset($p['dob']) ? (string) $p['dob'] : null)); ?>">
            </div>
            <div class="form-group col-md-3">
              <label for="gender"><?php echo htmlspecialchars(t('profile.gender')); ?> *</label>
              <select class="form-control" id="gender" name="gender" required>
                <option value=""><?php echo htmlspecialchars(t('profile.select')); ?></option>
                <?php foreach (gender_options() as $g): ?>
                  <option value="<?php echo htmlspecialchars($g); ?>"<?php echo $gender === $g ? ' selected' : ''; ?>><?php echo htmlspecialchars(t('profile.gender_' . strtolower($g))); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label for="marital_status"><?php echo htmlspecialchars(t('profile.marital_status')); ?> *</label>
              <select class="form-control" id="marital_status" name="marital_status" required>
                <option value=""><?php echo htmlspecialchars(t('profile.select')); ?></option>
                <?php foreach (marital_status_options() as $ms): ?>
                  <option value="<?php echo htmlspecialchars($ms); ?>"<?php echo $maritalStatus === $ms ? ' selected' : ''; ?>><?php echo htmlspecialchars(t('profile.marital_' . strtolower($ms))); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label for="blood_group"><?php echo htmlspecialchars(t('profile.blood_group')); ?> *</label>
              <select class="form-control" id="blood_group" name="blood_group" required>
                <option value=""><?php echo htmlspecialchars(t('profile.select')); ?></option>
                <?php foreach (blood_group_options() as $bg): ?>
                  <?php $bgLabel = $bg === 'Unknown' ? t('profile.blood_unknown') : $bg; ?>
                  <option value="<?php echo htmlspecialchars($bg); ?>"<?php echo $bloodGroup === $bg ? ' selected' : ''; ?>><?php echo htmlspecialchars($bgLabel); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label for="highest_education"><?php echo htmlspecialchars(t('profile.education')); ?> *</label>
              <?php
              $edu = (string) ($p['highest_education'] ?? '');
              $eduLegacy = ['SSLC/10th' => 'SSC/10th', 'PUC/12th' => 'HSC/12th'];
              if (isset($eduLegacy[$edu])) {
                  $edu = $eduLegacy[$edu];
              }
              ?>
              <select class="form-control" id="highest_education" name="highest_education" required>
                <option value=""><?php echo htmlspecialchars(t('profile.select')); ?></option>
                <?php foreach ([
                  'SSC/10th',
                  'HSC/12th',
                  'Diploma',
                  'ITI',
                  'Undergraduate',
                  'Graduate',
                  'Postgraduate',
                  'Doctorate',
                  'Other'
                ] as $ed): ?>
                  <option value="<?php echo htmlspecialchars($ed); ?>"<?php echo $edu === $ed ? ' selected' : ''; ?>><?php echo htmlspecialchars($ed); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="profile-addr-line profile-addr-line--line1">
            <div class="form-group profile-addr-narrow">
              <label for="house_number"><?php echo h(t('profile.house_number')); ?></label>
              <input type="text" class="form-control" id="house_number" name="house_number" maxlength="32" value="<?php echo htmlspecialchars((string) ($p['house_number'] ?? '')); ?>" autocomplete="address-line1">
            </div>
            <div class="form-group profile-addr-wide">
              <label for="address_line1"><?php echo h(t('profile.address1')); ?> *</label>
              <input type="text" class="form-control" id="address_line1" name="address_line1" required value="<?php echo htmlspecialchars((string) ($p['address_line1'] ?? '')); ?>" autocomplete="address-line2">
            </div>
          </div>
          <div class="profile-addr-line profile-addr-line--line2">
            <div class="form-group profile-addr-wide">
              <label for="address_line2"><?php echo h(t('profile.address2')); ?></label>
              <input type="text" class="form-control" id="address_line2" name="address_line2" value="<?php echo htmlspecialchars((string) ($p['address_line2'] ?? '')); ?>" autocomplete="address-line3">
            </div>
            <div class="form-group profile-addr-narrow">
              <label for="area"><?php echo h(t('profile.area')); ?> *</label>
              <input type="text" class="form-control" id="area" name="area" required maxlength="50" value="<?php echo htmlspecialchars($area); ?>" placeholder="<?php echo h(t('profile.area_placeholder')); ?>">
            </div>
          </div>
          <div class="form-row profile-location-row">
            <div class="form-group col-md-6 col-lg-4">
              <label for="country"><?php echo h(t('profile.country')); ?></label>
              <input type="text" class="form-control" id="country" value="<?php echo h(t('profile.country_india')); ?>" readonly>
            </div>
            <div class="form-group col-md-6 col-lg-4">
              <label for="pincode"><?php echo h(t('profile.pincode')); ?> *</label>
              <input type="text" class="form-control" id="pincode" name="pincode" required maxlength="6" value="<?php echo htmlspecialchars((string) ($p['pincode'] ?? '')); ?>">
              <small id="pincode_lookup_msg" class="text-muted"></small>
            </div>
          </div>
          <div class="form-row profile-location-row profile-location-row--city-state">
            <div class="form-group col-md-6 col-lg-4">
              <label for="state"><?php echo h(t('profile.state')); ?> *</label>
              <input type="text" class="form-control" id="state" name="state" required readonly value="<?php echo htmlspecialchars((string) ($p['state'] ?? '')); ?>">
            </div>
            <div class="form-group col-md-6 col-lg-4">
              <label for="city"><?php echo h(t('profile.city')); ?> *</label>
              <input type="text" class="form-control" id="city" name="city" required readonly value="<?php echo htmlspecialchars((string) ($p['city'] ?? '')); ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4 mb-2">
              <label class="mb-1 d-block"><?php echo h(t('profile.native_option')); ?></label>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="native_same_as_current" name="native_same_as_current" value="1"<?php echo $nativeSameDefault ? ' checked' : ''; ?>>
                <label class="form-check-label" for="native_same_as_current"><?php echo h(t('profile.native_same')); ?></label>
              </div>
            </div>
            <div class="form-group col-md-4">
              <label for="profession_type"><?php echo h(t('profile.profession_type')); ?> *</label>
              <select class="form-control" id="profession_type" name="profession_type" required>
                <option value=""><?php echo h(t('profile.select')); ?></option>
                <?php foreach (profession_type_options() as $professionOption): ?>
                  <option value="<?php echo htmlspecialchars($professionOption); ?>"<?php echo $professionType === $professionOption ? ' selected' : ''; ?>><?php echo h(t(profession_type_lang_key($professionOption))); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-4 job-title-field">
              <label for="job_title"><?php echo h(t('profile.job_title')); ?></label>
              <input type="text" class="form-control" id="job_title" name="job_title" value="<?php echo htmlspecialchars((string) ($p['job_title'] ?? '')); ?>">
            </div>
          </div>
          <div class="form-row job-only-field-row">
            <div class="form-group col-md-4 profession-common-field">
              <label for="company_name"><?php echo h(t('profile.company')); ?></label>
              <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars((string) ($p['company_name'] ?? '')); ?>">
            </div>
            <div class="form-group col-md-4 job-only-field">
              <label for="industry_sector"><?php echo h(t('profile.industry')); ?> *</label>
              <input type="text" class="form-control" id="industry_sector" name="industry_sector" value="<?php echo htmlspecialchars((string) ($p['industry_sector'] ?? '')); ?>">
            </div>
            <div class="form-group col-md-4 profession-common-field">
              <label for="company_website"><?php echo h(t('profile.website')); ?></label>
              <?php
                $websiteStored = trim((string) ($p['company_website'] ?? ''));
                $websiteDisplay = $websiteStored !== ''
                  ? (string) preg_replace('#^https?://#i', '', $websiteStored)
                  : '';
              ?>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text">https://</span>
                </div>
                <input type="text" class="form-control" id="company_website" name="company_website" placeholder="example.com" inputmode="url" autocomplete="url" value="<?php echo htmlspecialchars($websiteDisplay); ?>">
              </div>
            </div>
          </div>
          <div class="form-row native-location-row" id="native_pincode_row">
            <div class="form-group col-md-4">
              <label for="native_pincode"><?php echo h(t('profile.native_pincode')); ?> *</label>
              <input type="text" class="form-control" id="native_pincode" name="native_pincode" maxlength="6" value="<?php echo htmlspecialchars((string) ($p['native_pincode'] ?? '')); ?>">
              <small id="native_pincode_lookup_msg" class="text-muted"></small>
            </div>
            <div class="form-group col-md-4">
              <label for="native_state"><?php echo h(t('profile.native_state')); ?> *</label>
              <input type="text" class="form-control" id="native_state" name="native_state" required readonly value="<?php echo htmlspecialchars((string) ($p['native_state'] ?? '')); ?>">
            </div>
            <div class="form-group col-md-4">
              <label for="native_city"><?php echo h(t('profile.native_city')); ?> *</label>
              <input type="text" class="form-control" id="native_city" name="native_city" required readonly value="<?php echo htmlspecialchars((string) ($p['native_city'] ?? '')); ?>">
            </div>
          </div>
          <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(t('profile.save')); ?></button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var pinInput = document.getElementById('pincode');
  var cityInput = document.getElementById('city');
  var stateInput = document.getElementById('state');
  var msg = document.getElementById('pincode_lookup_msg');
  var nativeSame = document.getElementById('native_same_as_current');
  var nativePinInput = document.getElementById('native_pincode');
  var nativeCityInput = document.getElementById('native_city');
  var nativeStateInput = document.getElementById('native_state');
  var nativeMsg = document.getElementById('native_pincode_lookup_msg');
  var nativeRow = document.getElementById('native_pincode_row');
  var professionTypeSel = document.getElementById('profession_type');
  var jobTitleInput = document.getElementById('job_title');
  var companyNameInput = document.getElementById('company_name');
  var industrySectorInput = document.getElementById('industry_sector');
  var companyWebsiteInput = document.getElementById('company_website');
  if (!pinInput || !cityInput || !stateInput || !msg) return;
  var timer = null;
  var nativeTimer = null;

  function lookup() {
    var pin = (pinInput.value || '').trim();
    if (!/^\d{6}$/.test(pin)) {
      msg.textContent = '';
      return;
    }
    msg.textContent = <?php echo json_encode(t('profile.pincode_fetching')); ?>;
    fetch('<?php echo htmlspecialchars($b); ?>/organization/pincode-lookup?pincode=' + encodeURIComponent(pin), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.ok) {
          msg.textContent = (data && data.error) ? data.error : <?php echo json_encode(t('profile.pincode_error')); ?>;
          return;
        }
        cityInput.value = data.city || cityInput.value;
        stateInput.value = data.state || stateInput.value;
        msg.textContent = <?php echo json_encode(t('profile.pincode_updated')); ?>;
      })
      .catch(function () {
        msg.textContent = <?php echo json_encode(t('profile.pincode_error')); ?>;
      });
  }

  pinInput.addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(lookup, 350);
  });
  pinInput.addEventListener('blur', lookup);

  function nativeLookup() {
    if (!nativePinInput || !nativeCityInput || !nativeStateInput || !nativeMsg) return;
    var pin = (nativePinInput.value || '').trim();
    if (!/^\d{6}$/.test(pin)) {
      nativeMsg.textContent = '';
      return;
    }
    nativeMsg.textContent = <?php echo json_encode(t('profile.native_pincode_fetching')); ?>;
    fetch('<?php echo htmlspecialchars($b); ?>/organization/pincode-lookup?pincode=' + encodeURIComponent(pin), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.ok) {
          nativeMsg.textContent = (data && data.error) ? data.error : <?php echo json_encode(t('profile.native_pincode_error')); ?>;
          return;
        }
        nativeCityInput.value = data.city || nativeCityInput.value;
        nativeStateInput.value = data.state || nativeStateInput.value;
        nativeMsg.textContent = <?php echo json_encode(t('profile.native_pincode_updated')); ?>;
      })
      .catch(function () {
        nativeMsg.textContent = <?php echo json_encode(t('profile.native_pincode_error')); ?>;
      });
  }

  function syncNativeMode() {
    if (!nativeSame || !nativeRow || !nativeCityInput || !nativeStateInput || !nativePinInput) return;
    var same = nativeSame.checked;
    if (same) {
      nativeRow.style.display = 'none';
      nativeCityInput.value = cityInput.value || '';
      nativeStateInput.value = stateInput.value || '';
      nativePinInput.value = pinInput.value || '';
      nativeCityInput.required = false;
      nativeStateInput.required = false;
      nativePinInput.required = false;
    } else {
      nativeRow.style.display = '';
      nativeCityInput.required = true;
      nativeStateInput.required = true;
      nativePinInput.required = true;
    }
  }

  function syncProfessionMode() {
    if (!professionTypeSel) return;
    var selected = (professionTypeSel.value || '');
    var isJob = selected === 'job';
    var isBusiness = selected === 'business';
    var isProfessional = selected === 'professional';
    var showCompany = isJob || isBusiness || isProfessional;
    var showJobTitle = isJob || isProfessional;
    var jobTitleFields = document.querySelectorAll('.job-title-field');
    var commonFields = document.querySelectorAll('.profession-common-field');
    var jobFields = document.querySelectorAll('.job-only-field');
    jobTitleFields.forEach(function (el) {
      el.style.display = showJobTitle ? '' : 'none';
    });
    commonFields.forEach(function (el) {
      el.style.display = showCompany ? '' : 'none';
    });
    jobFields.forEach(function (el) {
      el.style.display = isJob ? '' : 'none';
    });
    if (jobTitleInput) jobTitleInput.required = showJobTitle;
    if (companyNameInput) companyNameInput.required = (isJob || isBusiness);
    if (industrySectorInput) industrySectorInput.required = isJob;
    if (companyWebsiteInput) companyWebsiteInput.required = false;
    if (!showCompany) {
      if (companyNameInput) companyNameInput.value = '';
      if (companyWebsiteInput) companyWebsiteInput.value = '';
    }
    if (!showJobTitle) {
      if (jobTitleInput) jobTitleInput.value = '';
    }
    if (!isJob) {
      if (industrySectorInput) industrySectorInput.value = '';
    }
  }

  function stripWebsiteScheme() {
    if (!companyWebsiteInput) return;
    var v = (companyWebsiteInput.value || '').trim();
    if (!v) return;
    companyWebsiteInput.value = v.replace(/^https?:\/\//i, '').replace(/^\/\//, '');
  }
  if (companyWebsiteInput) {
    companyWebsiteInput.addEventListener('blur', stripWebsiteScheme);
    companyWebsiteInput.addEventListener('paste', function () {
      setTimeout(stripWebsiteScheme, 0);
    });
  }

  if (nativePinInput) {
    nativePinInput.addEventListener('input', function () {
      clearTimeout(nativeTimer);
      nativeTimer = setTimeout(nativeLookup, 350);
    });
    nativePinInput.addEventListener('blur', nativeLookup);
  }
  if (nativeSame) {
    nativeSame.addEventListener('change', syncNativeMode);
  }
  if (professionTypeSel) {
    professionTypeSel.addEventListener('change', syncProfessionMode);
  }
  cityInput.addEventListener('change', syncNativeMode);
  stateInput.addEventListener('change', syncNativeMode);
  pinInput.addEventListener('change', syncNativeMode);
  syncNativeMode();
  syncProfessionMode();
})();
</script>

<style>
.profile-photo-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1.25rem;
}
.profile-photo-preview {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  overflow: hidden;
  flex: 0 0 96px;
  background: #e8f7f6;
  border: 2px solid rgba(52, 177, 170, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
}
.profile-photo-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.profile-photo-placeholder {
  font-size: 1.75rem;
  font-weight: 700;
  color: #34B1AA;
  letter-spacing: 0.05em;
}
.profile-photo-actions {
  flex: 1 1 220px;
  min-width: 0;
}
@media (min-width: 992px) {
  .profile-location-row {
    display: flex;
    flex-wrap: nowrap;
    margin-left: -8px;
    margin-right: -8px;
  }
  .profile-location-row > .form-group {
    flex: 1 1 25%;
    max-width: 25%;
    padding-left: 8px;
    padding-right: 8px;
  }
  .native-location-row {
    display: flex;
    flex-wrap: nowrap;
    margin-left: -8px;
    margin-right: -8px;
  }
  .native-location-row > .form-group {
    flex: 1 1 33.3333%;
    max-width: 33.3333%;
    padding-left: 8px;
    padding-right: 8px;
  }
}
</style>
<div class="row" style="padding-top: 16px;">
  <div class="col-lg-8">
    <?php require BASE_PATH . '/app/Views/partials/profile_email_memberships.php'; ?>
  </div>
</div>
<script>
(function () {
  var phoneVis = document.getElementById('profile_phone_visible');
  var phoneHid = document.getElementById('profile_phone_hidden');
  var form = document.querySelector('form[action$="/organization/profile/update"]');
  function syncPhone() {
    if (!phoneVis || !phoneHid) return;
    var d = (phoneVis.value || '').replace(/\D/g, '').slice(0, 10);
    phoneVis.value = d;
    phoneHid.value = d.length === 10 ? '91' + d : d;
  }
  if (phoneVis) {
    phoneVis.addEventListener('input', syncPhone);
  }
  if (form) {
    form.addEventListener('submit', function () {
      syncPhone();
      phoneHid.name = 'phone';
    });
  }
  syncPhone();
})();
</script>

