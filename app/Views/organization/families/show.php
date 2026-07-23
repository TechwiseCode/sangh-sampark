<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$fid = (int) $family['id'];
$roleOptions = [
    'head' => t('family.show.role_head'),
    'wife' => t('family.show.role_wife'),
    'husband' => t('family.show.role_husband'),
    'son' => t('family.show.role_son'),
    'daughter' => t('family.show.role_daughter'),
    'mother' => t('family.show.role_mother'),
    'father' => t('family.show.role_father'),
    'daughter-in-law' => t('family.show.role_daughter_in_law'),
    'son-in-law' => t('family.show.role_son_in_law'),
    'brother' => t('family.show.role_brother'),
    'sister' => t('family.show.role_sister'),
    'other' => t('family.show.role_other'),
];
$dependents = isset($dependents) && is_array($dependents) ? $dependents : [];
$familyPageTitle = (string) ($familyPageTitle ?? t('dashboard.my_family'));
$familyReturnUrl = $b . '/organization/family?id=' . $fid;
?>
<div class="row">
  <div class="col-12 border-bottom pb-2 mb-1">
    <h3 class="mb-0"><?php echo htmlspecialchars($familyPageTitle); ?></h3>
    <?php $familyTab = 'family'; require BASE_PATH . '/app/Views/partials/family_page_tabs.php'; ?>
    <?php if (!empty($canManageFamily)): ?>
    <div class="family-page-mobile-actions d-lg-none mt-3">
      <button type="button" class="btn btn-primary btn-block js-family-add-member-toggle" aria-controls="family-add-member-panel" aria-expanded="false">
        <i class="mdi mdi-account-plus-outline" aria-hidden="true"></i>
        <?php echo htmlspecialchars(t('family.show.add_member_btn')); ?>
      </button>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php if ($flashOk): ?>
  <div class="alert alert-success mt-3"><?php echo htmlspecialchars($flashOk); ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
  <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($flashErr); ?></div>
<?php endif; ?>
<div class="row" style="padding-top: 16px;">
  <div class="col-lg-7">
    <div class="card family-members-mobile-panel">
      <div class="card-body">
        <h4 class="card-title"><?php echo htmlspecialchars(t('family.show.members_title')); ?></h4>
        <div class="family-member-cards d-lg-none">
          <?php foreach ($members as $m): ?>
            <?php
              $dobUser = (string) ($m['dob'] ?? '');
              $ageUser = '';
              if ($dobUser !== '' && strtotime($dobUser) !== false) {
                  $ageUser = (string) ((new DateTimeImmutable($dobUser))->diff(new DateTimeImmutable('today'))->y);
              }
              $memberUserId = (int) ($m['user_id'] ?? 0);
              $memberRole = strtolower((string) ($m['role'] ?? ''));
              $isMemberHead = $memberRole === 'head';
              $canEditMember = !empty($canManageFamily) && $memberUserId > 0 && (!$isMemberHead || !empty($canManageOrg));
              $userAccountRole = strtolower((string) ($m['user_role'] ?? 'member'));
              $userIsActive = !isset($m['user_is_active']) || (int) $m['user_is_active'] === 1;
              $canToggleMemberActive = !empty($canManageOrg) && $memberUserId > 0 && $userAccountRole === 'member';
              $nameParts = person_name_parts_from_row(['name' => (string) ($m['user_name'] ?? '')]);
              $phoneRaw = preg_replace('/\D+/', '', (string) ($m['phone'] ?? ''));
              $phoneDisplay = $phoneRaw;
              if (strlen($phoneRaw) === 12 && strpos($phoneRaw, '91') === 0) {
                  $phoneDisplay = substr($phoneRaw, 2);
              }
              $roleLabel = $roleOptions[$memberRole] ?? (string) ($m['role'] ?? '');
              $memberBlood = normalize_blood_group($m['blood_group'] ?? '');
              if ($memberBlood === 'Unknown') {
                  $bloodDisplay = t('profile.blood_unknown');
              } elseif ($memberBlood !== null) {
                  $bloodDisplay = $memberBlood;
              } else {
                  $bloodDisplay = '';
              }
              $relatedText = $m['related_to_user_id']
                  ? (string) ($m['related_user_name'] ?? ('User #' . $m['related_to_user_id']))
                  : '';
              $email = trim((string) ($m['email'] ?? ''));
            ?>
            <article class="family-member-card<?php echo !$userIsActive ? ' family-member-card--inactive' : ''; ?>">
              <div class="family-member-card__top">
                <div class="family-member-card__identity">
                  <span class="family-member-card__name"><?php echo htmlspecialchars((string) $m['user_name']); ?></span>
                  <?php if (!$userIsActive): ?>
                  <span class="family-member-card__pill family-member-card__pill--dependent"><?php echo h(t('members.deactivated_badge')); ?></span>
                  <?php endif; ?>
                  <?php if ($roleLabel !== ''): ?>
                  <span class="family-member-card__pill family-member-card__pill--role"><?php echo htmlspecialchars($roleLabel); ?></span>
                  <?php endif; ?>
                </div>
                <?php if ($canEditMember || $canToggleMemberActive): ?>
                <div class="d-flex flex-wrap justify-content-end" style="gap:0.35rem;">
                <?php if ($canEditMember): ?>
                  <button
                    type="button"
                    class="family-member-card__edit js-open-member-edit-modal"
                    data-modal-id="familyMemberEditModal"
                    data-family-id="<?php echo $fid; ?>"
                    data-user-id="<?php echo $memberUserId; ?>"
                    data-first-name="<?php echo htmlspecialchars((string) $nameParts['first_name'], ENT_QUOTES); ?>"
                    data-middle-name="<?php echo htmlspecialchars((string) ($nameParts['middle_name'] ?? ''), ENT_QUOTES); ?>"
                    data-last-name="<?php echo htmlspecialchars((string) $nameParts['last_name'], ENT_QUOTES); ?>"
                    data-name="<?php echo htmlspecialchars((string) ($m['user_name'] ?? ''), ENT_QUOTES); ?>"
                    data-email="<?php echo htmlspecialchars((string) ($m['email'] ?? ''), ENT_QUOTES); ?>"
                    data-phone="<?php echo htmlspecialchars($phoneDisplay !== '' ? $phoneDisplay : '', ENT_QUOTES); ?>"
                    data-role="<?php echo htmlspecialchars((string) ($m['role'] ?? ''), ENT_QUOTES); ?>"
                    data-related-to-user-id="<?php echo (int) ($m['related_to_user_id'] ?? 0); ?>"
                  ><?php echo h(t('members.edit')); ?></button>
                <?php endif; ?>
                <?php if ($canToggleMemberActive): ?>
                  <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/family/member-active" class="d-inline" onsubmit="return confirm(<?php echo json_encode($userIsActive ? t('members.deactivate_confirm') : t('members.reactivate_confirm')); ?>);">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="family_id" value="<?php echo $fid; ?>">
                    <input type="hidden" name="user_id" value="<?php echo $memberUserId; ?>">
                    <input type="hidden" name="is_active" value="<?php echo $userIsActive ? '0' : '1'; ?>">
                    <button type="submit" class="btn btn-sm <?php echo $userIsActive ? 'btn-outline-warning' : 'btn-outline-success'; ?>">
                      <?php echo h($userIsActive ? t('members.deactivate') : t('members.reactivate')); ?>
                    </button>
                  </form>
                <?php endif; ?>
                </div>
                <?php endif; ?>
              </div>
              <?php if ($ageUser !== '' || $bloodDisplay !== ''): ?>
              <div class="family-member-card__labels">
                <?php if ($ageUser !== ''): ?>
                <span class="family-member-card__pill family-member-card__pill--age"><?php echo htmlspecialchars(t('family.show.col_age')); ?>: <?php echo htmlspecialchars($ageUser); ?></span>
                <?php endif; ?>
                <?php if ($bloodDisplay !== ''): ?>
                <span class="family-member-card__pill family-member-card__pill--blood"><?php echo htmlspecialchars(t('family.show.col_blood_group')); ?>: <?php echo htmlspecialchars($bloodDisplay); ?></span>
                <?php endif; ?>
              </div>
              <?php endif; ?>
              <?php if ($relatedText !== ''): ?>
              <p class="family-member-card__sub"><?php echo htmlspecialchars(t('family.show.col_related_to')); ?>: <?php echo htmlspecialchars($relatedText); ?></p>
              <?php endif; ?>
              <?php
                $phoneTel = $phoneRaw !== '' ? $phoneRaw : $phoneDisplay;
                require BASE_PATH . '/app/Views/partials/family_member_card_contacts.php';
              ?>
            </article>
          <?php endforeach; ?>
          <?php foreach ($dependents as $d): ?>
            <?php
              $dob = (string) ($d['dob'] ?? '');
              $age = '';
              if ($dob !== '' && strtotime($dob) !== false) {
                  $age = (string) ((new DateTimeImmutable($dob))->diff(new DateTimeImmutable('today'))->y);
              }
              $depRoleKey = strtolower((string) ($d['role'] ?? ''));
              $depRoleLabel = $roleOptions[$depRoleKey] ?? (string) ($d['role'] ?? '');
              $depRelated = !empty($d['related_user_name']) ? (string) $d['related_user_name'] : '';
            ?>
            <article class="family-member-card family-member-card--dependent">
              <div class="family-member-card__top">
                <div class="family-member-card__identity">
                  <span class="family-member-card__name"><?php echo htmlspecialchars((string) ($d['name'] ?? '')); ?></span>
                  <span class="family-member-card__pill family-member-card__pill--dependent"><?php echo htmlspecialchars(t('family.show.dependent_badge')); ?></span>
                  <?php if ($depRoleLabel !== ''): ?>
                  <span class="family-member-card__pill family-member-card__pill--role"><?php echo htmlspecialchars($depRoleLabel); ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <?php if ($age !== ''): ?>
              <div class="family-member-card__labels">
                <span class="family-member-card__pill family-member-card__pill--age"><?php echo htmlspecialchars(t('family.show.col_age')); ?>: <?php echo htmlspecialchars($age); ?></span>
              </div>
              <?php endif; ?>
              <?php if ($depRelated !== ''): ?>
              <p class="family-member-card__sub"><?php echo htmlspecialchars(t('family.show.col_related_to')); ?>: <?php echo htmlspecialchars($depRelated); ?></p>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
        <div class="table-responsive d-none d-lg-block">
          <table class="table table-sm">
            <thead>
              <tr>
                <th><?php echo htmlspecialchars(t('family.show.col_name')); ?></th>
                <th><?php echo htmlspecialchars(t('family.show.col_role')); ?></th>
                <th><?php echo htmlspecialchars(t('family.show.col_age')); ?></th>
                <th><?php echo htmlspecialchars(t('family.show.col_blood_group')); ?></th>
                <th><?php echo htmlspecialchars(t('family.show.col_related_to')); ?></th>
                <?php if (!empty($canManageFamily) || !empty($canManageOrg)): ?>
                  <th class="text-right"><?php echo htmlspecialchars(t('common.actions')); ?></th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($members as $m): ?>
                <?php
                  $dobUser = (string) ($m['dob'] ?? '');
                  $ageUser = '';
                  if ($dobUser !== '' && strtotime($dobUser) !== false) {
                      $ageUser = (string) ((new DateTimeImmutable($dobUser))->diff(new DateTimeImmutable('today'))->y);
                  }
                  $memberUserId = (int) ($m['user_id'] ?? 0);
                  $memberRole = strtolower((string) ($m['role'] ?? ''));
                  $isMemberHead = $memberRole === 'head';
                  $canEditMember = !empty($canManageFamily) && $memberUserId > 0 && (!$isMemberHead || !empty($canManageOrg));
                  $userAccountRole = strtolower((string) ($m['user_role'] ?? 'member'));
                  $userIsActive = !isset($m['user_is_active']) || (int) $m['user_is_active'] === 1;
                  $canToggleMemberActive = !empty($canManageOrg) && $memberUserId > 0 && $userAccountRole === 'member';
                  $nameParts = person_name_parts_from_row(['name' => (string) ($m['user_name'] ?? '')]);
                  $phoneRaw = preg_replace('/\D+/', '', (string) ($m['phone'] ?? ''));
                  $phoneDisplay = $phoneRaw;
                  if (strlen($phoneRaw) === 12 && strpos($phoneRaw, '91') === 0) {
                      $phoneDisplay = substr($phoneRaw, 2);
                  }
                ?>
                <tr<?php echo !$userIsActive ? ' class="table-warning"' : ''; ?>>
                  <td>
                    <?php echo htmlspecialchars((string) $m['user_name']); ?>
                    <?php if (!$userIsActive): ?>
                      <span class="badge badge-secondary ml-1"><?php echo h(t('members.deactivated_badge')); ?></span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars((string) $m['role']); ?></td>
                  <td><?php echo $ageUser !== '' ? htmlspecialchars($ageUser) : '—'; ?></td>
                  <td><?php
                    $memberBlood = normalize_blood_group($m['blood_group'] ?? '');
                    if ($memberBlood === 'Unknown') {
                        echo htmlspecialchars(t('profile.blood_unknown'));
                    } elseif ($memberBlood !== null) {
                        echo htmlspecialchars($memberBlood);
                    } else {
                        echo '—';
                    }
                  ?></td>
                  <td><?php echo $m['related_to_user_id'] ? htmlspecialchars((string) ($m['related_user_name'] ?? ('User #' . $m['related_to_user_id']))) : '—'; ?></td>
                  <?php if (!empty($canManageFamily) || !empty($canManageOrg)): ?>
                    <td class="text-right text-nowrap">
                      <?php if ($canEditMember): ?>
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-primary js-open-member-edit-modal"
                          data-modal-id="familyMemberEditModal"
                          data-family-id="<?php echo $fid; ?>"
                          data-user-id="<?php echo $memberUserId; ?>"
                          data-first-name="<?php echo htmlspecialchars((string) $nameParts['first_name'], ENT_QUOTES); ?>"
                          data-middle-name="<?php echo htmlspecialchars((string) ($nameParts['middle_name'] ?? ''), ENT_QUOTES); ?>"
                          data-last-name="<?php echo htmlspecialchars((string) $nameParts['last_name'], ENT_QUOTES); ?>"
                          data-name="<?php echo htmlspecialchars((string) ($m['user_name'] ?? ''), ENT_QUOTES); ?>"
                          data-email="<?php echo htmlspecialchars((string) ($m['email'] ?? ''), ENT_QUOTES); ?>"
                          data-phone="<?php echo htmlspecialchars($phoneDisplay !== '' ? $phoneDisplay : '', ENT_QUOTES); ?>"
                          data-role="<?php echo htmlspecialchars((string) ($m['role'] ?? ''), ENT_QUOTES); ?>"
                          data-related-to-user-id="<?php echo (int) ($m['related_to_user_id'] ?? 0); ?>"
                        ><?php echo h(t('members.edit')); ?></button>
                      <?php endif; ?>
                      <?php if ($canToggleMemberActive): ?>
                        <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/family/member-active" class="d-inline" onsubmit="return confirm(<?php echo json_encode($userIsActive ? t('members.deactivate_confirm') : t('members.reactivate_confirm')); ?>);">
                          <?php echo csrf_field(); ?>
                          <input type="hidden" name="family_id" value="<?php echo $fid; ?>">
                          <input type="hidden" name="user_id" value="<?php echo $memberUserId; ?>">
                          <input type="hidden" name="is_active" value="<?php echo $userIsActive ? '0' : '1'; ?>">
                          <button type="submit" class="btn btn-sm <?php echo $userIsActive ? 'btn-outline-warning' : 'btn-outline-success'; ?>">
                            <?php echo h($userIsActive ? t('members.deactivate') : t('members.reactivate')); ?>
                          </button>
                        </form>
                      <?php endif; ?>
                      <?php if (!$canEditMember && !$canToggleMemberActive): ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
              <?php foreach ($dependents as $d): ?>
                <?php
                  $dob = (string) ($d['dob'] ?? '');
                  $age = '';
                  if ($dob !== '' && strtotime($dob) !== false) {
                      $age = (string) ((new DateTimeImmutable($dob))->diff(new DateTimeImmutable('today'))->y);
                  }
                ?>
                <tr>
                  <td>
                    <?php echo htmlspecialchars((string) ($d['name'] ?? '')); ?>
                    <span class="badge ml-1 readable-badge"><?php echo htmlspecialchars(t('family.show.dependent_badge')); ?></span>
                  </td>
                  <td>
                    <?php echo htmlspecialchars((string) ($d['role'] ?? '')); ?>
                  </td>
                  <td><?php echo $age !== '' ? htmlspecialchars($age) : '—'; ?></td>
                  <td>—</td>
                  <td><?php echo !empty($d['related_user_name']) ? htmlspecialchars((string) $d['related_user_name']) : '—'; ?></td>
                  <?php if (!empty($canManageFamily)): ?>
                    <td></td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <?php if (!empty($canManageOrg)): ?>
    <div class="card mb-3 family-split-panel" id="family-split-panel">
      <button type="button" class="family-split-panel__toggle d-lg-none" aria-expanded="false" aria-controls="family-split-panel-body">
        <span><?php echo htmlspecialchars(t('family.show.split_title')); ?></span>
        <i class="mdi mdi-chevron-down" aria-hidden="true"></i>
      </button>
      <div class="card-body family-split-panel__body" id="family-split-panel-body">
        <h4 class="card-title d-none d-lg-block"><?php echo htmlspecialchars(t('family.show.split_title')); ?></h4>
        <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/family/split" onsubmit="return confirm(<?php echo json_encode(t('family.show.split_confirm')); ?>);">
          <input type="hidden" name="family_id" value="<?php echo $fid; ?>">
          <div class="form-group">
            <label for="new_head_user_id"><?php echo htmlspecialchars(t('family.show.split_new_head_label')); ?></label>
            <select class="form-control" id="new_head_user_id" name="new_head_user_id" required>
              <option value=""><?php echo htmlspecialchars(t('family.show.split_select_member_placeholder')); ?></option>
              <?php foreach ($members as $m): ?>
                <?php
                  $uid = (int) ($m['user_id'] ?? 0);
                  $role = strtolower((string) ($m['role'] ?? ''));
                  if ($uid < 1 || $role === 'head') { continue; }
                ?>
                <option value="<?php echo $uid; ?>">
                  <?php echo htmlspecialchars((string) ($m['user_name'] ?? '')); ?> (<?php echo htmlspecialchars((string) ($m['role'] ?? '')); ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-outline-primary"><?php echo htmlspecialchars(t('family.show.split_submit')); ?></button>
        </form>
      </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($canManageFamily)): ?>
    <div class="card family-add-member-panel" id="family-add-member-panel">
      <div class="card-body">
        <h4 class="card-title mb-3"><?php echo htmlspecialchars(t('family.show.add_member_title')); ?></h4>
        <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/family/add-member" id="form-add-member">
          <input type="hidden" name="family_id" value="<?php echo $fid; ?>">
          <div class="form-group">
            <label class="d-block text-muted small text-uppercase mb-2"><?php echo htmlspecialchars(t('family.show.member_type_label')); ?></label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="member_mode" id="mem_new" value="new" checked>
              <label class="form-check-label" for="mem_new"><?php echo htmlspecialchars(t('family.show.member_type_new')); ?></label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="member_mode" id="mem_dependent" value="dependent">
              <label class="form-check-label" for="mem_dependent"><?php echo htmlspecialchars(t('family.show.member_type_dependent')); ?></label>
            </div>
          </div>
          <div id="mem-panel-new" class="border rounded p-3 mb-3">
            <?php
            $headSurname = family_head_surname($family, $members);
            $nameFieldRow = $headSurname !== '' ? ['last_name' => $headSurname] : [];
            $nameFieldPrefix = 'new_';
            $nameFieldIdPrefix = 'new_';
            require BASE_PATH . '/app/Views/partials/person_name_fields.php';
            ?>
            <?php if ($headSurname !== ''): ?>
              <small class="form-text text-muted mb-3 d-block"><?php echo htmlspecialchars(t('family.show.last_name_from_head_hint')); ?></small>
            <?php endif; ?>
            <div class="form-group">
              <label for="new_email"><?php echo htmlspecialchars(t('family.show.email_label')); ?></label>
              <input type="email" class="form-control" name="new_email" id="new_email" required autocomplete="email">
              <div id="org_mem_email_warn" class="alert alert-danger mt-2 py-2 mb-0 small d-none" role="alert"><?php echo htmlspecialchars(t('family.show.email_exists_alert')); ?></div>
            </div>
            <div class="form-group mb-0">
              <label for="new_phone_visible"><?php echo htmlspecialchars(t('family.show.phone_label')); ?> *</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text">+91</span>
                </div>
                <input type="tel" class="form-control" id="new_phone_visible" maxlength="10" inputmode="numeric" pattern="[0-9]*" required autocomplete="off">
              </div>
              <input type="hidden" name="new_phone" id="new_phone_hidden" value="">
              <div id="org_mem_phone_warn" class="alert alert-danger mt-2 py-2 mb-0 small d-none" role="alert"><?php echo htmlspecialchars(t('family.show.phone_exists_alert')); ?></div>
            </div>
          </div>
          <div id="mem-panel-dependent" class="border rounded p-3 mb-3" style="display:none;">
            <div class="form-group">
              <label for="dep_name"><?php echo htmlspecialchars(t('family.show.dep_name_label')); ?></label>
              <input type="text" class="form-control" name="dep_name" id="dep_name">
            </div>
            <div class="form-group">
              <label for="dep_dob"><?php echo htmlspecialchars(t('family.show.dep_birthdate_label')); ?></label>
              <input type="date" class="form-control" name="dep_dob" id="dep_dob">
              <small id="dep_age_preview" class="text-muted"></small>
            </div>
            <div class="form-group">
              <label for="dep_pincode"><?php echo htmlspecialchars(t('family.show.dep_pincode_label')); ?></label>
              <input type="text" class="form-control" name="dep_pincode" id="dep_pincode" maxlength="6">
              <small id="dep_pincode_lookup_msg" class="text-muted"></small>
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="dep_city"><?php echo htmlspecialchars(t('family.show.dep_city_label')); ?></label>
                <input type="text" class="form-control" name="dep_city" id="dep_city" readonly>
              </div>
              <div class="form-group col-md-6">
                <label for="dep_state"><?php echo htmlspecialchars(t('family.show.dep_state_label')); ?></label>
                <input type="text" class="form-control" name="dep_state" id="dep_state" readonly>
              </div>
            </div>
          </div>
          <div class="form-group" id="role_group">
            <label for="role"><?php echo htmlspecialchars(t('family.show.role_label')); ?></label>
            <select class="form-control" name="role" id="role" required>
              <?php foreach ($roleOptions as $val => $label): ?>
                <?php if ($val === 'head' && !$canAddHead) { continue; } ?>
                <option value="<?php echo htmlspecialchars($val); ?>"><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" id="related-group">
            <label for="related_to_user_id"><?php echo htmlspecialchars(t('family.show.related_to_label')); ?></label>
            <select class="form-control" name="related_to_user_id" id="related_to_user_id">
              <option value=""><?php echo htmlspecialchars(t('family.show.related_to_placeholder')); ?></option>
              <?php foreach ($members as $m): ?>
                <option value="<?php echo (int) $m['user_id']; ?>">
                  <?php echo htmlspecialchars((string) $m['user_name']); ?> (<?php echo htmlspecialchars((string) $m['role']); ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <small class="form-text text-muted"><?php echo htmlspecialchars(t('family.show.related_to_help')); ?></small>
          </div>
          <button type="submit" class="btn btn-primary" id="btn_add_member_submit"><?php echo htmlspecialchars(t('family.show.save_button')); ?></button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php if (!empty($canManageFamily)): ?>
  <?php
  $memberEditModalId = 'familyMemberEditModal';
  $memberEditModalTitle = t('members.edit_title');
  $memberEditReturnUrl = $familyReturnUrl;
  $memberEditShowRelationship = true;
  $memberEditRoleOptions = $roleOptions;
  $memberEditFamilyMembers = array_values(array_filter($members, static fn (array $row): bool => (int) ($row['user_id'] ?? 0) > 0));
  $memberEditAllowHeadRole = !empty($canManageOrg);
  require BASE_PATH . '/app/Views/partials/member_basic_edit_modal.php';
  ?>
<?php endif; ?>
<?php if (!empty($canManageFamily)): ?>
<script>
(function () {
  document.querySelectorAll('.js-family-add-member-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var panel = document.getElementById('family-add-member-panel');
      if (!panel) {
        return;
      }
      panel.classList.add('is-open');
      btn.setAttribute('aria-expanded', 'true');
      panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
      var firstInput = panel.querySelector('input:not([type="hidden"]), select, textarea');
      if (firstInput) {
        firstInput.focus();
      }
    });
  });
})();
</script>
<script>
(function () {
  var base = <?php echo json_encode($b); ?>;
  var form = document.getElementById('form-add-member');
  var modeNew = document.getElementById('mem_new');
  var modeDependent = document.getElementById('mem_dependent');
  var roleGroup = document.getElementById('role_group');
  var relatedGroup = document.getElementById('related-group');
  var emailIn = document.getElementById('new_email');
  var emailWarn = document.getElementById('org_mem_email_warn');
  var phoneVis = document.getElementById('new_phone_visible');
  var phoneHid = document.getElementById('new_phone_hidden');
  var phoneWarn = document.getElementById('org_mem_phone_warn');
  var btn = document.getElementById('btn_add_member_submit');
  var emailTimer = null;
  var phoneTimer = null;
  var lastEmail = '';
  var lastPhone = '';
  var emailDup = false;
  var phoneDup = false;
  var depDob = document.getElementById('dep_dob');
  var depAgePreview = document.getElementById('dep_age_preview');
  var depPincode = document.getElementById('dep_pincode');
  var depCity = document.getElementById('dep_city');
  var depState = document.getElementById('dep_state');
  var depMsg = document.getElementById('dep_pincode_lookup_msg');
  var depAgePrefix = <?php echo json_encode(t('family.show.dep_age_prefix')); ?>;
  var depAgeSuffix = <?php echo json_encode(t('family.show.dep_age_suffix_years')); ?>;
  var depPinFetchingMsg = <?php echo json_encode(t('family.show.pincode_fetching')); ?>;
  var depPinErrorMsg = <?php echo json_encode(t('family.show.pincode_error')); ?>;
  var depPinUpdatedMsg = <?php echo json_encode(t('family.show.pincode_updated')); ?>;

  function refreshSubmit() {
    if (modeNew.checked) {
      btn.disabled = emailDup || phoneDup;
      return;
    }
    btn.disabled = false;
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

  function syncPhoneHidden() {
    if (!phoneVis || !phoneHid) return;
    var d = (phoneVis.value || '').replace(/\D/g, '').slice(0, 10);
    phoneVis.value = d;
    phoneHid.value = d.length === 10 ? '91' + d : '';
  }

  function runEmailCheck() {
    if (!modeNew.checked) return;
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
      .then(function (data) {
        if (!modeNew.checked || (emailIn.value || '').trim() !== lastEmail) return;
        setEmailDup(!!data.exists);
      })
      .catch(function () { setEmailDup(false); });
  }

  function runPhoneCheck() {
    if (!modeNew.checked) return;
    syncPhoneHidden();
    var full = phoneHid.value;
    if (full.length !== 12) {
      setPhoneDup(false);
      return;
    }
    lastPhone = full;
    fetch(base + '/organization/check-phone?phone=' + encodeURIComponent(full), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        syncPhoneHidden();
        if (!modeNew.checked || phoneHid.value !== lastPhone) return;
        setPhoneDup(!!data.exists);
      })
      .catch(function () { setPhoneDup(false); });
  }

  function memSync() {
    var dep = modeDependent.checked;
    var nw = modeNew.checked;
    document.getElementById('mem-panel-new').style.display = nw ? 'block' : 'none';
    document.getElementById('mem-panel-dependent').style.display = dep ? 'block' : 'none';
    document.getElementById('new_first_name').required = nw;
    document.getElementById('new_last_name').required = nw;
    document.getElementById('new_email').required = nw;
    document.getElementById('new_phone_visible').required = nw;
    document.getElementById('dep_name').required = dep;
    document.getElementById('dep_dob').required = dep;
    document.getElementById('dep_pincode').required = dep;
    document.getElementById('dep_city').required = dep;
    document.getElementById('dep_state').required = dep;
    btn.textContent = <?php echo json_encode(t('family.show.save_button')); ?>;
    if (roleGroup) roleGroup.style.display = '';
    if (relatedGroup) relatedGroup.style.display = '';
    if (nw) {
      runEmailCheck();
      runPhoneCheck();
    } else {
      setEmailDup(false);
      setPhoneDup(false);
    }
    refreshSubmit();
  }
  modeNew.addEventListener('change', memSync);
  modeDependent.addEventListener('change', memSync);

  if (emailIn) {
    emailIn.addEventListener('input', function () {
      clearTimeout(emailTimer);
      emailTimer = setTimeout(runEmailCheck, 400);
    });
    emailIn.addEventListener('blur', runEmailCheck);
  }

  if (phoneVis) {
    phoneVis.addEventListener('input', function () {
      syncPhoneHidden();
      clearTimeout(phoneTimer);
      phoneTimer = setTimeout(runPhoneCheck, 400);
    });
    phoneVis.addEventListener('blur', function () {
      syncPhoneHidden();
      runPhoneCheck();
    });
  }

  form.addEventListener('submit', function (e) {
    if (modeNew.checked) {
      syncPhoneHidden();
      if (emailDup || phoneDup) {
        e.preventDefault();
      }
    }
  });

  memSync();
  function updateDependentAge() {
    if (!depDob || !depAgePreview) return;
    var v = depDob.value || '';
    if (!v) {
      depAgePreview.textContent = '';
      return;
    }
    var dob = new Date(v + 'T00:00:00');
    if (isNaN(dob.getTime())) {
      depAgePreview.textContent = '';
      return;
    }
    var now = new Date();
    var age = now.getFullYear() - dob.getFullYear();
    var m = now.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && now.getDate() < dob.getDate())) age--;
    depAgePreview.textContent = age >= 0 ? (depAgePrefix + age + depAgeSuffix) : '';
  }
  function lookupDependentPin() {
    if (!depPincode || !depCity || !depState || !depMsg) return;
    var pin = (depPincode.value || '').trim();
    if (!/^\d{6}$/.test(pin)) {
      depMsg.textContent = '';
      return;
    }
    depMsg.textContent = depPinFetchingMsg;
    fetch(base + '/organization/pincode-lookup?pincode=' + encodeURIComponent(pin), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.ok) {
          depMsg.textContent = (data && data.error) ? data.error : depPinErrorMsg;
          return;
        }
        depCity.value = data.city || '';
        depState.value = data.state || '';
        depMsg.textContent = depPinUpdatedMsg;
      })
      .catch(function () {
        depMsg.textContent = depPinErrorMsg;
      });
  }
  if (depDob) depDob.addEventListener('change', updateDependentAge);
  if (depPincode) {
    depPincode.addEventListener('blur', lookupDependentPin);
    depPincode.addEventListener('input', function () {
      if ((depPincode.value || '').trim().length >= 6) lookupDependentPin();
    });
  }
  updateDependentAge();
  function roleSync() {
    var sel = document.getElementById('role');
    var isHead = sel && sel.value === 'head';
    var rel = document.getElementById('related_to_user_id');
    var grp = document.getElementById('related-group');
    if (rel) { rel.required = !isHead; }
    if (grp) { grp.style.display = isHead ? 'none' : 'block'; }
  }
  document.getElementById('role').addEventListener('change', roleSync);
  roleSync();
})();
</script>
<?php endif; ?>
<script>
(function () {
  var panel = document.getElementById('family-split-panel');
  if (!panel) return;

  var toggle = panel.querySelector('.family-split-panel__toggle');
  if (!toggle) return;

  toggle.addEventListener('click', function () {
    var open = panel.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
})();
</script>
<style>
.readable-badge {
  background-color: #e9ecef !important;
  color: #212529 !important;
  border: 1px solid #ced4da !important;
}
</style>
