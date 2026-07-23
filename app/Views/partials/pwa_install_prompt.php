<?php
declare(strict_types=1);
?>
<div class="pwa-install-bar" id="pwa_install_promo" role="region" aria-label="<?php echo htmlspecialchars(t('pwa.install_title')); ?>">
  <button type="button" class="btn btn-primary btn-lg btn-block js-pwa-install pwa-install-bar__btn" id="pwa_install_primary_btn">
    <i class="mdi mdi-cellphone-arrow-down" aria-hidden="true"></i>
    <span class="pwa-install-bar__label"><?php echo htmlspecialchars(t('pwa.install_btn')); ?></span>
  </button>
  <button type="button" class="btn btn-link btn-sm btn-block pwa-install-bar__dismiss js-pwa-install-dismiss" id="pwa_install_dismiss_btn">
    <?php echo htmlspecialchars(t('pwa.install_dismiss')); ?>
  </button>
  <p class="pwa-install-bar__hint text-muted small mb-0" id="pwa_install_promo_hint"></p>
</div>
