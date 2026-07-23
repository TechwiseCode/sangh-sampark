<?php
$b = base_url();
$mailStatus = is_array($mailStatus ?? null) ? $mailStatus : [];
$smtpProfileDraft = (string) ($smtpProfileDraft ?? 'env');
$secureLabel = strtoupper((string) ($mailStatus['secure'] ?? ''));
?>
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title"><?php echo h(t('superadmin.mail_test.title')); ?></h4>
        <p class="text-muted small"><?php echo h(t('superadmin.mail_test.subtitle')); ?></p>

        <?php if (!empty($formError)): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars((string) $formError); ?></div>
        <?php endif; ?>
        <?php if (!empty($formOk)): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars((string) $formOk); ?></div>
        <?php endif; ?>

        <div class="mb-4 p-3 bg-light rounded small">
          <div class="font-weight-bold mb-2"><?php echo h(t('superadmin.mail_test.current_config')); ?></div>
          <div><?php echo h(t('superadmin.mail_test.label_enabled')); ?>: <strong><?php echo !empty($mailStatus['smtp_enabled']) ? h(t('common.yes')) : h(t('common.no')); ?></strong></div>
          <div><?php echo h(t('superadmin.mail_test.label_from')); ?>: <strong><?php echo htmlspecialchars((string) ($mailStatus['from'] ?? '')); ?></strong></div>
          <div><?php echo h(t('superadmin.mail_test.label_host')); ?>: <strong><?php echo htmlspecialchars((string) ($mailStatus['host'] ?? '')); ?></strong></div>
          <div><?php echo h(t('superadmin.mail_test.label_port')); ?>: <strong><?php echo (int) ($mailStatus['port'] ?? 0); ?></strong> (<?php echo htmlspecialchars($secureLabel); ?>)</div>
          <div><?php echo h(t('superadmin.mail_test.label_user')); ?>: <strong><?php echo htmlspecialchars((string) ($mailStatus['user'] ?? '')); ?></strong></div>
          <div><?php echo h(t('superadmin.mail_test.label_pass')); ?>: <strong><?php echo !empty($mailStatus['pass_set']) ? h(t('superadmin.mail_test.pass_set')) : h(t('superadmin.mail_test.pass_missing_short')); ?></strong></div>
          <p class="text-muted mb-0 mt-2"><?php echo h(t('superadmin.mail_test.env_hint')); ?></p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars($b); ?>/superadmin/mail-test">
          <?php echo csrf_field(); ?>
          <div class="form-group">
            <label for="mail_test_to"><?php echo h(t('superadmin.mail_test.label_to')); ?></label>
            <input type="email" class="form-control" id="mail_test_to" name="to" required
              value="<?php echo htmlspecialchars((string) ($defaultRecipient ?? '')); ?>">
          </div>
          <div class="form-group">
            <label for="mail_test_profile"><?php echo h(t('superadmin.mail_test.label_profile')); ?></label>
            <select class="form-control" id="mail_test_profile" name="smtp_profile">
              <option value="env"<?php echo $smtpProfileDraft === 'env' ? ' selected' : ''; ?>>
                <?php echo h(t('superadmin.mail_test.profile_env', [
                  'port' => (int) ($mailStatus['port'] ?? 0),
                  'secure' => $secureLabel,
                ])); ?>
              </option>
              <option value="465_ssl"<?php echo $smtpProfileDraft === '465_ssl' ? ' selected' : ''; ?>>
                <?php echo h(t('superadmin.mail_test.profile_465')); ?>
              </option>
              <option value="587_tls"<?php echo $smtpProfileDraft === '587_tls' ? ' selected' : ''; ?>>
                <?php echo h(t('superadmin.mail_test.profile_587')); ?>
              </option>
            </select>
            <small class="text-muted"><?php echo h(t('superadmin.mail_test.profile_help')); ?></small>
          </div>
          <button type="submit" class="btn btn-primary"><?php echo h(t('superadmin.mail_test.submit')); ?></button>
        </form>

        <?php if (is_array($lastAttempt ?? null) && $lastAttempt !== []): ?>
          <p class="text-muted small mt-3 mb-0">
            <?php echo h(t('superadmin.mail_test.last_attempt', [
              'email' => (string) ($lastAttempt['to'] ?? ''),
              'port' => (int) ($lastAttempt['port'] ?? 0),
              'secure' => strtoupper((string) ($lastAttempt['secure'] ?? '')),
            ])); ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
