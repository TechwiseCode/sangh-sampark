<?php
$b = base_url();
$memberEditModalId = (string) ($memberEditModalId ?? 'memberBasicEditModal');
$memberEditModalTitle = (string) ($memberEditModalTitle ?? t('members.edit_title'));
$memberEditReturnUrl = (string) ($memberEditReturnUrl ?? '');
$memberEditShowRelationship = !empty($memberEditShowRelationship);
$memberEditRoleOptions = isset($memberEditRoleOptions) && is_array($memberEditRoleOptions) ? $memberEditRoleOptions : [];
$memberEditFamilyMembers = isset($memberEditFamilyMembers) && is_array($memberEditFamilyMembers) ? $memberEditFamilyMembers : [];
$memberEditAllowHeadRole = !empty($memberEditAllowHeadRole);
?>
<div id="<?php echo htmlspecialchars($memberEditModalId); ?>" class="member-edit-modal d-none" role="dialog" aria-modal="true" aria-hidden="true">
  <div class="member-edit-modal__backdrop js-close-member-edit-modal"></div>
  <div class="member-edit-modal__dialog">
    <div class="member-edit-modal__header">
      <h5 class="mb-0"><?php echo htmlspecialchars($memberEditModalTitle); ?></h5>
      <button type="button" class="btn btn-sm btn-light js-close-member-edit-modal" aria-label="<?php echo htmlspecialchars(t('common.cancel')); ?>">&times;</button>
    </div>
    <div class="member-edit-modal__body">
      <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/family/member-update" class="member-basic-edit-form">
        <input type="hidden" name="family_id" class="js-member-edit-family-id" value="">
        <input type="hidden" name="user_id" class="js-member-edit-user-id" value="">
        <?php if ($memberEditReturnUrl !== ''): ?>
          <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($memberEditReturnUrl); ?>">
        <?php endif; ?>
        <div class="person-name-fields person-name-fields--modal">
          <div class="form-group person-name-fields__part">
            <label class="person-name-fields__label" for="<?php echo htmlspecialchars($memberEditModalId); ?>_first_name"><?php echo h(t('person.first_name')); ?> *</label>
            <input type="text" name="first_name" id="<?php echo htmlspecialchars($memberEditModalId); ?>_first_name" class="form-control js-member-edit-first-name" required>
          </div>
          <div class="form-group person-name-fields__part">
            <label class="person-name-fields__label" for="<?php echo htmlspecialchars($memberEditModalId); ?>_middle_name"><?php echo h(t('person.middle_name')); ?></label>
            <input type="text" name="middle_name" id="<?php echo htmlspecialchars($memberEditModalId); ?>_middle_name" class="form-control js-member-edit-middle-name">
          </div>
          <div class="form-group person-name-fields__part">
            <label class="person-name-fields__label" for="<?php echo htmlspecialchars($memberEditModalId); ?>_last_name"><?php echo h(t('person.last_name')); ?> *</label>
            <input type="text" name="last_name" id="<?php echo htmlspecialchars($memberEditModalId); ?>_last_name" class="form-control js-member-edit-last-name" required>
          </div>
        </div>
        <div class="form-group">
          <label for="<?php echo htmlspecialchars($memberEditModalId); ?>_email"><?php echo h(t('family.index.head_email')); ?></label>
          <input type="email" name="email" id="<?php echo htmlspecialchars($memberEditModalId); ?>_email" class="form-control js-member-edit-email">
        </div>
        <div class="form-group">
          <label for="<?php echo htmlspecialchars($memberEditModalId); ?>_phone"><?php echo h(t('family.index.head_phone')); ?></label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text">+91</span>
            </div>
            <input type="text" name="phone" id="<?php echo htmlspecialchars($memberEditModalId); ?>_phone" class="form-control js-member-edit-phone" maxlength="10" pattern="\d{10}" required placeholder="<?php echo h(t('family.index.phone_placeholder')); ?>">
          </div>
        </div>
        <?php if ($memberEditShowRelationship): ?>
          <div class="form-group js-member-edit-role-wrap">
            <label for="<?php echo htmlspecialchars($memberEditModalId); ?>_role"><?php echo h(t('family.show.role_label')); ?></label>
            <select name="role" id="<?php echo htmlspecialchars($memberEditModalId); ?>_role" class="form-control js-member-edit-role" required>
              <?php foreach ($memberEditRoleOptions as $val => $label): ?>
                <?php if ($val === 'head' && !$memberEditAllowHeadRole) { continue; } ?>
                <option value="<?php echo htmlspecialchars((string) $val); ?>"><?php echo htmlspecialchars((string) $label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group js-member-edit-related-wrap">
            <label for="<?php echo htmlspecialchars($memberEditModalId); ?>_related"><?php echo h(t('family.show.related_to_label')); ?></label>
            <select name="related_to_user_id" id="<?php echo htmlspecialchars($memberEditModalId); ?>_related" class="form-control js-member-edit-related">
              <option value=""><?php echo h(t('family.show.related_to_placeholder')); ?></option>
              <?php foreach ($memberEditFamilyMembers as $fm): ?>
                <?php $fmUid = (int) ($fm['user_id'] ?? 0); if ($fmUid < 1) { continue; } ?>
                <option value="<?php echo $fmUid; ?>" class="js-member-edit-related-option" data-user-id="<?php echo $fmUid; ?>">
                  <?php echo htmlspecialchars((string) ($fm['user_name'] ?? '')); ?> (<?php echo htmlspecialchars((string) ($fm['role'] ?? '')); ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <small class="form-text text-muted"><?php echo h(t('family.show.related_to_help')); ?></small>
          </div>
        <?php endif; ?>
        <div class="text-right">
          <button type="button" class="btn btn-light js-close-member-edit-modal"><?php echo h(t('common.cancel')); ?></button>
          <button type="submit" class="btn btn-primary"><?php echo h(t('family.index.save_changes')); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
(function () {
  var modalId = <?php echo json_encode($memberEditModalId); ?>;
  var modal = document.getElementById(modalId);
  if (!modal) return;

  function openModal() {
    modal.classList.remove('d-none');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('member-edit-modal-open');
  }

  function closeModal() {
    modal.classList.add('d-none');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('member-edit-modal-open');
  }

  function syncRelatedVisibility(form) {
    var roleInput = form.querySelector('.js-member-edit-role');
    var relatedWrap = form.querySelector('.js-member-edit-related-wrap');
    if (!roleInput || !relatedWrap) return;
    var isHead = (roleInput.value || '').toLowerCase() === 'head';
    relatedWrap.style.display = isHead ? 'none' : '';
    var relatedSelect = form.querySelector('.js-member-edit-related');
    if (relatedSelect) {
      relatedSelect.required = !isHead;
      if (isHead) {
        relatedSelect.value = '';
      }
    }
  }

  document.querySelectorAll('.js-open-member-edit-modal[data-modal-id="' + modalId + '"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var form = modal.querySelector('.member-basic-edit-form');
      if (!form) return;
      var familyInput = form.querySelector('.js-member-edit-family-id');
      var userInput = form.querySelector('.js-member-edit-user-id');
      var firstNameInput = form.querySelector('.js-member-edit-first-name');
      var middleNameInput = form.querySelector('.js-member-edit-middle-name');
      var lastNameInput = form.querySelector('.js-member-edit-last-name');
      var emailInput = form.querySelector('.js-member-edit-email');
      var phoneInput = form.querySelector('.js-member-edit-phone');
      var roleInput = form.querySelector('.js-member-edit-role');
      var relatedInput = form.querySelector('.js-member-edit-related');
      if (!familyInput || !userInput || !firstNameInput || !lastNameInput || !emailInput || !phoneInput) return;
      var targetUserId = btn.getAttribute('data-user-id') || '';
      familyInput.value = btn.getAttribute('data-family-id') || '';
      userInput.value = targetUserId;
      firstNameInput.value = btn.getAttribute('data-first-name') || '';
      if (middleNameInput) middleNameInput.value = btn.getAttribute('data-middle-name') || '';
      lastNameInput.value = btn.getAttribute('data-last-name') || btn.getAttribute('data-name') || '';
      emailInput.value = btn.getAttribute('data-email') || '';
      phoneInput.value = btn.getAttribute('data-phone') || '';
      if (roleInput) {
        roleInput.value = btn.getAttribute('data-role') || roleInput.options[0].value;
      }
      if (relatedInput) {
        relatedInput.querySelectorAll('.js-member-edit-related-option').forEach(function (opt) {
          var optUserId = opt.getAttribute('data-user-id') || '';
          opt.hidden = optUserId !== '' && optUserId === targetUserId;
        });
        relatedInput.value = btn.getAttribute('data-related-to-user-id') || '';
      }
      syncRelatedVisibility(form);
      openModal();
    });
  });

  modal.querySelectorAll('.js-member-edit-role').forEach(function (roleInput) {
    roleInput.addEventListener('change', function () {
      var form = modal.querySelector('.member-basic-edit-form');
      if (form) syncRelatedVisibility(form);
    });
  });

  modal.querySelectorAll('.js-close-member-edit-modal').forEach(function (btn) {
    btn.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.classList.contains('d-none')) {
      closeModal();
    }
  });
})();
</script>
