<?php
$b = base_url();
?>
<?php if (!empty($emailMemberships) && is_array($emailMemberships) && count($emailMemberships) > 1): ?>
  <div class="mb-4">
    <?php require BASE_PATH . '/app/Views/partials/profile_email_memberships.php'; ?>
  </div>
<?php endif; ?>
<div class="org-settings-panel">
  <div class="org-settings-panel__head">
    <span class="org-settings-panel__icon" aria-hidden="true"><i class="mdi mdi-lock-outline"></i></span>
    <div>
      <h2 class="org-settings-panel__title"><?php echo h(t('settings.change_password')); ?></h2>
      <p class="org-settings-panel__desc mb-0"><?php echo h(t('settings.password_desc')); ?></p>
    </div>
  </div>
  <?php if (!empty($forcePasswordChange)): ?>
    <div class="alert alert-warning">
      Enter the <strong>6-letter code</strong> from your email as the current password, then choose a new password (8+ characters). You cannot open other pages until this is done.
    </div>
  <?php endif; ?>
  <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/settings/change-password" class="org-settings-form">
    <div class="form-group">
      <label for="current_password"><?php echo h(t('settings.current_password')); ?></label>
      <div class="password-field">
        <input id="current_password" type="password" name="current_password" class="form-control password-field__input" required autocomplete="current-password">
        <button type="button" class="password-field__toggle js-toggle-password" data-target="current_password" aria-label="<?php echo h(t('settings.show_password')); ?>">
          <i class="mdi mdi-eye-outline"></i>
        </button>
      </div>
    </div>
    <div class="form-group">
      <label for="new_password"><?php echo h(t('settings.new_password')); ?></label>
      <div class="password-field">
        <input id="new_password" type="password" name="new_password" class="form-control password-field__input" minlength="8" required autocomplete="new-password">
        <button type="button" class="password-field__toggle js-toggle-password" data-target="new_password" aria-label="<?php echo h(t('settings.show_password')); ?>">
          <i class="mdi mdi-eye-outline"></i>
        </button>
      </div>
      <small class="text-muted"><?php echo h(t('settings.password_hint')); ?></small>
    </div>
    <div class="form-group">
      <label for="confirm_password"><?php echo h(t('settings.confirm_password')); ?></label>
      <div class="password-field">
        <input id="confirm_password" type="password" name="confirm_password" class="form-control password-field__input" minlength="8" required autocomplete="new-password">
        <button type="button" class="password-field__toggle js-toggle-password" data-target="confirm_password" aria-label="<?php echo h(t('settings.show_password')); ?>">
          <i class="mdi mdi-eye-outline"></i>
        </button>
      </div>
    </div>
    <button type="submit" class="btn btn-primary"><?php echo h(t('settings.update_password')); ?></button>
  </form>
</div>
