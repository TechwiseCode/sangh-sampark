<?php
declare(strict_types=1);

$androidSteps = array_values(array_filter(array_map('trim', explode('|', t('pwa.install_android_steps')))));
$iosSteps = array_values(array_filter(array_map('trim', explode('|', t('pwa.install_ios_steps')))));
?>
<script>
window.SanghSamparkPwa = {
  baseUrl: <?php echo json_encode(pwa_web_base_url()); ?>,
  swPath: <?php echo json_encode(pwa_service_worker_path()); ?>,
  swScope: <?php echo json_encode(pwa_service_worker_scope()); ?>,
  title: <?php echo json_encode(t('pwa.install_title')); ?>,
  prompt: <?php echo json_encode(t('pwa.install_prompt')); ?>,
  iosHelp: <?php echo json_encode(t('pwa.install_ios_help')); ?>,
  androidHelp: <?php echo json_encode(t('pwa.install_android_help')); ?>,
  httpsRequired: <?php echo json_encode(t('pwa.install_https_required')); ?>,
  androidReloadStep: <?php echo json_encode(t('pwa.install_android_reload_step')); ?>,
  configMismatch: <?php echo json_encode(t('pwa.install_config_mismatch')); ?>,
  swFailed: <?php echo json_encode(t('pwa.install_sw_failed')); ?>,
  iosSteps: <?php echo json_encode($iosSteps, JSON_HEX_TAG | JSON_HEX_AMP); ?>,
  androidSteps: <?php echo json_encode($androidSteps, JSON_HEX_TAG | JSON_HEX_AMP); ?>,
  install: <?php echo json_encode(t('pwa.install_btn')); ?>,
  installWaiting: <?php echo json_encode(t('pwa.install_waiting')); ?>,
  installBtn: <?php echo json_encode(t('pwa.install_btn')); ?>,
  installPreparing: <?php echo json_encode(t('pwa.install_preparing')); ?>,
  installPreparingHint: <?php echo json_encode(t('pwa.install_preparing_hint')); ?>,
  installNotReady: <?php echo json_encode(t('pwa.install_not_ready')); ?>,
  swNotControlling: <?php echo json_encode(t('pwa.install_sw_not_controlling')); ?>,
  dismiss: <?php echo json_encode(t('pwa.install_dismiss')); ?>,
  gotIt: <?php echo json_encode(t('pwa.install_got_it')); ?>
};
</script>
<script src="<?php echo htmlspecialchars(asset_url('themes/js/pwa-install.js')); ?>" defer></script>
