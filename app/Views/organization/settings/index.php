<?php
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$settingsSection = (string) ($settingsSection ?? 'password');
$allowed = ['password', 'notifications', 'language'];
if (!in_array($settingsSection, $allowed, true)) {
    $settingsSection = 'password';
}
$sectionFile = BASE_PATH . '/app/Views/organization/settings/' . $settingsSection . '.php';
?>
<div class="org-settings-page">
  <header class="org-settings-page__header">
    <h1 class="org-settings-page__title"><?php echo h(t('settings.title')); ?></h1>
    <p class="org-settings-page__subtitle mb-0"><?php echo h(t('settings.subtitle')); ?></p>
  </header>

  <?php if ($flashOk): ?>
    <div class="alert alert-success org-settings-page__alert"><?php echo htmlspecialchars($flashOk); ?></div>
  <?php endif; ?>
  <?php if ($flashErr): ?>
    <div class="alert alert-danger org-settings-page__alert"><?php echo htmlspecialchars($flashErr); ?></div>
  <?php endif; ?>

  <div class="org-settings-page__content">
    <?php if (is_file($sectionFile)): ?>
      <?php require $sectionFile; ?>
    <?php endif; ?>
  </div>
</div>

<script>
(function () {
  document.querySelectorAll('.js-toggle-password').forEach(function (btnEye) {
    btnEye.addEventListener('click', function () {
      var targetId = btnEye.getAttribute('data-target') || '';
      var input = document.getElementById(targetId);
      if (!input) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      var icon = btnEye.querySelector('i');
      if (icon) {
        icon.className = show ? 'mdi mdi-eye-off-outline' : 'mdi mdi-eye-outline';
      }
    });
  });
})();
</script>
