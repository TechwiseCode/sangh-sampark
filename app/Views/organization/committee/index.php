<?php
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$b = base_url();
$canManage = !empty($canManage);
$members = isset($members) && is_array($members) ? $members : [];
$pickerUsers = isset($pickerUsers) && is_array($pickerUsers) ? $pickerUsers : [];
$designationOptions = isset($designationOptions) && is_array($designationOptions) ? $designationOptions : [];
$editId = isset($editId) ? (int) $editId : 0;
$formError = isset($formError) ? (string) $formError : null;
$userIdDraft = isset($userIdDraft) ? (int) $userIdDraft : 0;
$designationDraft = isset($designationDraft) ? (string) $designationDraft : '';
$isEditing = $canManage && $editId > 0;
$memberCount = count($members);
?>
<div class="committee-page">
  <div class="committee-page-header">
    <div>
      <h3 class="mb-1"><?php echo h(t('committee.title')); ?></h3>
      <p class="text-muted mb-0 small"><?php echo h(t($canManage ? 'committee.subtitle_admin' : 'committee.subtitle_member')); ?></p>
      <?php if ($canManage): ?>
        <a class="small d-inline-block mt-1" href="<?php echo htmlspecialchars($b); ?>/organization/dashboard#committee"><?php echo h(t('committee.back_dashboard')); ?></a>
      <?php endif; ?>
    </div>
    <?php if ($memberCount > 0): ?>
      <span class="committee-count text-muted small"><?php echo (int) $memberCount; ?></span>
    <?php endif; ?>
  </div>

  <?php if ($flashOk): ?>
    <div class="alert alert-success mt-3 mb-0"><?php echo htmlspecialchars($flashOk); ?></div>
  <?php endif; ?>
  <?php if ($flashErr): ?>
    <div class="alert alert-danger mt-3 mb-0"><?php echo htmlspecialchars($flashErr); ?></div>
  <?php endif; ?>
  <?php if ($formError): ?>
    <div class="alert alert-danger mt-3 mb-0"><?php echo htmlspecialchars($formError); ?></div>
  <?php endif; ?>

  <div class="row committee-split">
    <div class="<?php echo $canManage ? 'col-lg-7' : 'col-12'; ?> committee-list-col">
      <?php if ($members === []): ?>
        <div class="committee-empty">
          <p class="text-muted mb-0"><?php echo h(t($canManage ? 'committee.empty_admin' : 'committee.empty_member')); ?></p>
        </div>
      <?php else: ?>
        <ul class="committee-grid">
          <?php foreach ($members as $row): ?>
            <?php
            $itemId = (int) ($row['id'] ?? 0);
            $name = trim((string) ($row['person_name'] ?? ''));
            if ($name === '') {
                $name = trim((string) ($row['user_name'] ?? ''));
            }
            $designationKey = (string) ($row['designation_key'] ?? '');
            $designation = committee_designation_label($designationKey);
            $phone = trim((string) ($row['user_phone'] ?? ''));
            $photoUrl = user_photo_url(isset($row['photo_path']) ? (string) $row['photo_path'] : null);
            $initials = user_photo_initials($name !== '' ? $name : '?');
            $isActive = $isEditing && $editId === $itemId;
            ?>
            <li class="committee-card<?php echo $isActive ? ' is-active' : ''; ?>">
              <div class="committee-card__avatar<?php echo $photoUrl !== null ? ' has-photo' : ''; ?>" aria-hidden="true"<?php if ($photoUrl !== null): ?> style="background-image:url('<?php echo htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8'); ?>')"<?php endif; ?>>
                <?php if ($photoUrl === null): ?>
                  <span><?php echo htmlspecialchars($initials); ?></span>
                <?php endif; ?>
              </div>
              <div class="committee-card__body">
                <strong class="committee-card__name"><?php echo htmlspecialchars($name); ?></strong>
                <span class="committee-card__role"><?php echo htmlspecialchars($designation); ?></span>
                <?php if ($phone !== ''): ?>
                  <a class="committee-card__phone" href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $phone) ?? $phone); ?>">
                    <i class="mdi mdi-phone-outline" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($phone); ?>
                  </a>
                <?php endif; ?>
              </div>
              <?php if ($canManage): ?>
                <div class="committee-card__actions">
                  <a class="btn btn-link btn-sm p-0" href="<?php echo htmlspecialchars($b); ?>/organization/committee?edit=<?php echo $itemId; ?>"><?php echo h(t('common.edit')); ?></a>
                  <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/committee/delete" class="d-inline" onsubmit="return confirm(<?php echo json_encode(t('committee.delete_confirm')); ?>);">
                    <input type="hidden" name="id" value="<?php echo $itemId; ?>">
                    <button type="submit" class="btn btn-link btn-sm text-danger p-0 ml-2"><?php echo h(t('common.delete')); ?></button>
                  </form>
                </div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <?php if ($canManage): ?>
      <div class="col-lg-5 committee-form-col">
        <div class="card committee-form-card">
          <div class="card-body">
            <h4 class="committee-form-title mb-3"><?php echo h(t($isEditing ? 'committee.form_edit' : 'committee.form_add')); ?></h4>
            <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/committee<?php echo $isEditing ? '/update' : ''; ?>">
              <?php if ($isEditing): ?>
                <input type="hidden" name="id" value="<?php echo (int) $editId; ?>">
              <?php endif; ?>

              <div class="form-group">
                <label for="committee_user_id"><?php echo h(t('committee.field_member')); ?></label>
                <select id="committee_user_id" name="user_id" class="form-control" required>
                  <option value=""><?php echo h(t('committee.field_member_placeholder')); ?></option>
                  <?php foreach ($pickerUsers as $u): ?>
                    <?php
                    $uid = (int) ($u['user_id'] ?? 0);
                    $label = trim((string) ($u['name'] ?? ''));
                    $code = trim((string) ($u['full_member_code'] ?? ''));
                    if ($code !== '') {
                        $label .= ' (' . $code . ')';
                    }
                    ?>
                    <option value="<?php echo $uid; ?>"<?php echo $userIdDraft === $uid ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="committee_designation_key"><?php echo h(t('committee.field_designation')); ?></label>
                <select id="committee_designation_key" name="designation_key" class="form-control" required>
                  <option value=""><?php echo h(t('committee.field_designation_placeholder')); ?></option>
                  <?php foreach ($designationOptions as $key => $label): ?>
                    <option value="<?php echo htmlspecialchars((string) $key); ?>"<?php echo $designationDraft === (string) $key ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $label); ?></option>
                  <?php endforeach; ?>
                </select>
                <small class="form-text text-muted"><?php echo h(t('committee.field_designation_hint')); ?></small>
              </div>

              <div class="d-flex align-items-center">
                <button type="submit" class="btn btn-primary"><?php echo h(t($isEditing ? 'common.save' : 'committee.add')); ?></button>
                <?php if ($isEditing): ?>
                  <a class="btn btn-link ml-2" href="<?php echo htmlspecialchars($b); ?>/organization/committee"><?php echo h(t('common.cancel')); ?></a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
