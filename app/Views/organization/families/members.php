<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$members = isset($members) && is_array($members) ? $members : [];
$membersTab = (string) ($membersTab ?? 'list');
if ($membersTab !== 'import') {
    $membersTab = 'list';
}
$preview = isset($preview) && is_array($preview) ? $preview : null;
$errors = isset($errors) && is_array($errors) ? $errors : [];
$warnings = isset($warnings) && is_array($warnings) ? $warnings : [];
$memberFilter = ($memberFilter ?? 'all') === 'heads' ? 'heads' : 'all';
$genderFilter = $genderFilter ?? 'all';
$professionFilter = $professionFilter ?? 'all';
$donationFilter = $donationFilter ?? 'all';
$ageFilters = isset($ageFilters) && is_array($ageFilters) ? $ageFilters : [];
$hasActiveFilters = $memberFilter === 'heads' || $genderFilter !== 'all' || $professionFilter !== 'all' || $donationFilter !== 'all' || $ageFilters !== [];
$ageFilterSummary = member_directory_age_filter_summary($ageFilters);
$memberGenderLabel = static function (array $row): ?string {
    $gender = trim((string) ($row['profile_gender'] ?? ''));
    if ($gender === 'Male') {
        return t('profile.gender_male');
    }
    if ($gender === 'Female') {
        return t('profile.gender_female');
    }
    if ($gender === 'Other') {
        return t('profile.gender_other');
    }

    return null;
};
?>
<div class="row">
  <div class="col-12 border-bottom pb-2 mb-1">
    <div class="d-flex justify-content-between align-items-center flex-wrap members-page-header">
      <h3 class="mb-0"><?php echo h('members.title'); ?></h3>
      <?php if ($membersTab === 'list'): ?>
        <a class="btn btn-success mb-2" href="<?php echo htmlspecialchars($b); ?>/organization/families/new"><?php echo h('family.new'); ?></a>
      <?php endif; ?>
    </div>
    <ul class="nav nav-tabs mt-3 mb-0">
      <li class="nav-item">
        <a class="nav-link<?php echo $membersTab === 'list' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($b); ?>/organization/families"><?php echo h('members.tab_directory'); ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link<?php echo $membersTab === 'import' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($b); ?>/organization/families?members_tab=import"><?php echo h('members.tab_import'); ?></a>
      </li>
    </ul>
  </div>
</div>
<?php if ($flashOk): ?>
  <div class="alert alert-success mt-3"><?php echo htmlspecialchars($flashOk); ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
  <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($flashErr); ?></div>
<?php endif; ?>
<?php if ($membersTab === 'import'): ?>
<div class="row pt-3">
  <div class="col-12">
    <p class="text-muted small mb-0"><?php echo h(t('organization.import.subtitle')); ?></p>
  </div>
</div>
<div class="row mt-2">
  <div class="col-12">
    <?php
      $importBaseUrl = $b;
      $includeOrgCode = false;
      require BASE_PATH . '/app/Views/partials/family_import_panel.php';
    ?>
  </div>
</div>
<?php else: ?>
<div class="row pt-2">
  <div class="col-12">
    <form method="get" action="<?php echo htmlspecialchars($b); ?>/organization/families" class="members-filter-toolbar">
      <?php
      $idPrefix = 'members';
      $showFilterActions = true;
      $filterResetUrl = $b . '/organization/families';
      require BASE_PATH . '/app/Views/partials/member_directory_filters.php';
      ?>
    </form>
  </div>
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="family-member-cards d-lg-none">
          <?php if ($members === []): ?>
            <p class="text-muted small mb-0">
              <?php
              if ($hasActiveFilters) {
                  echo h('members.none_filtered');
              } elseif ($memberFilter === 'heads') {
                  echo h('members.none_heads');
              } else {
                  echo h('members.none');
              }
              ?>
            </p>
          <?php else: ?>
            <?php foreach ($members as $m): ?>
              <?php
              $familyId = (int) ($m['family_id'] ?? 0);
              $isHead = !empty($m['is_family_head']);
              $phoneRaw = preg_replace('/\D+/', '', (string) ($m['phone'] ?? ''));
              $phoneDisplay = $phoneRaw;
              if (strlen($phoneRaw) === 12 && strpos($phoneRaw, '91') === 0) {
                  $phoneDisplay = substr($phoneRaw, 2);
              }
              $displayName = user_display_name($m);
              $genderLabel = $memberGenderLabel($m);
              $professionLabel = profession_type_label_from_row($m);
              $ageYears = age_years_from_dob((string) ($m['profile_dob'] ?? ''));
              $userId = (int) ($m['user_id'] ?? 0);
              $nameParts = person_name_parts_from_row($m);
              $canEditMember = !empty($canManageOrg) && $familyId > 0 && $userId > 0;
              $memberCode = trim((string) ($m['full_member_code'] ?? ''));
              $familyRole = trim((string) ($m['family_role'] ?? ''));
              $email = trim((string) ($m['email'] ?? ''));
              $hasLabels = $ageYears !== null || $memberCode !== '' || $genderLabel !== null || $professionLabel !== null;
              ?>
              <article class="family-member-card">
                <div class="family-member-card__top">
                  <div class="family-member-card__identity">
                    <span class="family-member-card__name"><?php echo htmlspecialchars($displayName); ?></span>
                    <?php if ($isHead): ?>
                    <span class="family-member-card__pill family-member-card__pill--head"><?php echo h('members.badge_head'); ?></span>
                    <?php elseif ($familyId > 0 && $familyRole !== ''): ?>
                    <span class="family-member-card__pill family-member-card__pill--role"><?php echo htmlspecialchars($familyRole); ?></span>
                    <?php endif; ?>
                  </div>
                  <?php if ($canEditMember): ?>
                    <button
                      type="button"
                      class="family-member-card__edit js-open-member-edit-modal"
                      data-modal-id="membersEditModal"
                      data-family-id="<?php echo $familyId; ?>"
                      data-user-id="<?php echo $userId; ?>"
                      data-first-name="<?php echo htmlspecialchars((string) $nameParts['first_name'], ENT_QUOTES); ?>"
                      data-middle-name="<?php echo htmlspecialchars((string) ($nameParts['middle_name'] ?? ''), ENT_QUOTES); ?>"
                      data-last-name="<?php echo htmlspecialchars((string) $nameParts['last_name'], ENT_QUOTES); ?>"
                      data-name="<?php echo htmlspecialchars($displayName, ENT_QUOTES); ?>"
                      data-email="<?php echo htmlspecialchars((string) ($m['email'] ?? ''), ENT_QUOTES); ?>"
                      data-phone="<?php echo htmlspecialchars($phoneDisplay !== '' ? $phoneDisplay : '', ENT_QUOTES); ?>"
                    ><?php echo h(t('members.edit')); ?></button>
                  <?php endif; ?>
                </div>
                <?php if ($hasLabels): ?>
                <div class="family-member-card__labels">
                  <?php if ($ageYears !== null): ?>
                  <span class="family-member-card__pill family-member-card__pill--age"><?php echo htmlspecialchars(t('members.col_age')); ?>: <?php echo (int) $ageYears; ?></span>
                  <?php endif; ?>
                  <?php if ($memberCode !== ''): ?>
                  <span class="family-member-card__pill family-member-card__pill--code"><?php echo htmlspecialchars($memberCode); ?></span>
                  <?php endif; ?>
                  <?php if ($genderLabel !== null): ?>
                  <span class="family-member-card__pill family-member-card__pill--gender"><?php echo htmlspecialchars($genderLabel); ?></span>
                  <?php endif; ?>
                  <?php if ($professionLabel !== null): ?>
                  <span class="family-member-card__pill family-member-card__pill--profession"><?php echo htmlspecialchars($professionLabel); ?></span>
                  <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php
                  $phoneTel = $phoneRaw !== '' ? $phoneRaw : $phoneDisplay;
                  require BASE_PATH . '/app/Views/partials/family_member_card_contacts.php';
                ?>
                <?php if ($familyId > 0): ?>
                <div class="family-member-card__actions">
                  <a class="family-member-card__action-link" href="<?php echo htmlspecialchars($b); ?>/organization/family?id=<?php echo $familyId; ?>"><?php echo h('common.open'); ?></a>
                </div>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="table-responsive d-none d-lg-block">
          <table class="table table-striped table-hover align-middle members-directory-table">
            <thead>
              <tr>
                <th><?php echo h('members.col_name'); ?></th>
                <th><?php echo h('members.col_code'); ?></th>
                <th><?php echo h('members.col_age'); ?></th>
                <th><?php echo h('profile.gender'); ?></th>
                <th><?php echo h('profile.profession_type'); ?></th>
                <th><?php echo h('members.col_email'); ?></th>
                <th><?php echo h('members.col_phone'); ?></th>
                <th><?php echo h('members.col_family'); ?></th>
                <th class="text-right"><?php echo h('common.actions'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php if ($members === []): ?>
                <tr>
                  <td colspan="9" class="text-muted">
                    <?php
                    if ($hasActiveFilters) {
                        echo h('members.none_filtered');
                    } elseif ($memberFilter === 'heads') {
                        echo h('members.none_heads');
                    } else {
                        echo h('members.none');
                    }
                    ?>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($members as $m): ?>
                  <?php
                  $familyId = (int) ($m['family_id'] ?? 0);
                  $isHead = !empty($m['is_family_head']);
                  $phoneRaw = preg_replace('/\D+/', '', (string) ($m['phone'] ?? ''));
                  $phoneDisplay = $phoneRaw;
                  if (strlen($phoneRaw) === 12 && strpos($phoneRaw, '91') === 0) {
                      $phoneDisplay = substr($phoneRaw, 2);
                  }
                  $displayName = user_display_name($m);
                  $genderLabel = $memberGenderLabel($m);
                  $professionLabel = profession_type_label_from_row($m);
                  $ageYears = age_years_from_dob((string) ($m['profile_dob'] ?? ''));
                  $userId = (int) ($m['user_id'] ?? 0);
                  $nameParts = person_name_parts_from_row($m);
                  $canEditMember = !empty($canManageOrg) && $familyId > 0 && $userId > 0;
                  ?>
                  <tr>
                    <td>
                      <span class="person-name-inline"><?php echo htmlspecialchars($displayName); ?></span>
                      <?php if ($isHead): ?>
                        <span class="badge badge-members-head ml-1"><?php echo h('members.badge_head'); ?></span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars((string) (($m['full_member_code'] ?? '') ?: '—')); ?></td>
                    <td><?php echo $ageYears !== null ? (int) $ageYears : '—'; ?></td>
                    <td><?php echo htmlspecialchars($genderLabel ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($professionLabel ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars((string) (($m['email'] ?? '') ?: '—')); ?></td>
                    <td><?php echo htmlspecialchars($phoneDisplay !== '' ? $phoneDisplay : '—'); ?></td>
                    <td>
                      <?php if ($familyId > 0 && !empty($m['family_role'])): ?>
                        <span class="text-muted small"><?php echo htmlspecialchars((string) $m['family_role']); ?></span>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-right text-nowrap members-actions-cell">
                      <?php if ($canEditMember): ?>
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-primary js-open-member-edit-modal"
                          data-modal-id="membersEditModal"
                          data-family-id="<?php echo $familyId; ?>"
                          data-user-id="<?php echo $userId; ?>"
                          data-first-name="<?php echo htmlspecialchars((string) $nameParts['first_name'], ENT_QUOTES); ?>"
                          data-middle-name="<?php echo htmlspecialchars((string) ($nameParts['middle_name'] ?? ''), ENT_QUOTES); ?>"
                          data-last-name="<?php echo htmlspecialchars((string) $nameParts['last_name'], ENT_QUOTES); ?>"
                          data-name="<?php echo htmlspecialchars($displayName, ENT_QUOTES); ?>"
                          data-email="<?php echo htmlspecialchars((string) ($m['email'] ?? ''), ENT_QUOTES); ?>"
                          data-phone="<?php echo htmlspecialchars($phoneDisplay !== '' ? $phoneDisplay : '', ENT_QUOTES); ?>"
                        ><?php echo h(t('members.edit')); ?></button>
                      <?php endif; ?>
                      <?php if ($familyId > 0): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($b); ?>/organization/family?id=<?php echo $familyId; ?>"><?php echo h('common.open'); ?></a>
                      <?php else: ?>
                        <?php if (!$canEditMember): ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php if (!empty($canManageOrg)): ?>
  <?php
  $memberEditModalId = 'membersEditModal';
  $memberEditModalTitle = t('members.edit_title');
  $memberEditReturnUrl = $b . '/organization/families' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . (string) $_SERVER['QUERY_STRING'] : '');
  require BASE_PATH . '/app/Views/partials/member_basic_edit_modal.php';
  ?>
<?php endif; ?>
<?php endif; ?>
<script>
(function () {
  var wrap = document.getElementById('members_age_dropdown');
  if (!wrap) return;
  var toggle = document.getElementById('members_age_toggle');
  var menu = wrap.querySelector('.members-age-dropdown-menu');
  var labelEl = wrap.querySelector('.members-age-dropdown-label');
  var allLabel = <?php echo json_encode(t('members.filter_all_short')); ?>;
  var nSelectedTemplate = <?php echo json_encode(t('members.filter_age_n_selected', ['count' => ':count'])); ?>;

  function updateLabel() {
    if (!labelEl) return;
    var checked = wrap.querySelectorAll('input[type="checkbox"][name="age[]"]:checked');
    if (checked.length === 0) {
      labelEl.textContent = allLabel;
      return;
    }
    if (checked.length === 1) {
      labelEl.textContent = checked[0].getAttribute('data-age-label') || checked[0].value;
      return;
    }
    labelEl.textContent = nSelectedTemplate.replace(':count', String(checked.length));
  }

  function closeMenu() {
    if (!menu || !toggle) return;
    menu.classList.add('d-none');
    wrap.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
  }

  function openMenu() {
    if (!menu || !toggle) return;
    menu.classList.remove('d-none');
    wrap.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
  }

  toggle.addEventListener('click', function (e) {
    e.preventDefault();
    if (menu.classList.contains('d-none')) {
      openMenu();
    } else {
      closeMenu();
    }
  });

  wrap.querySelectorAll('input[type="checkbox"][name="age[]"]').forEach(function (input) {
    input.addEventListener('change', updateLabel);
  });

  document.addEventListener('click', function (e) {
    if (!wrap.contains(e.target)) {
      closeMenu();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeMenu();
    }
  });
})();
</script>
