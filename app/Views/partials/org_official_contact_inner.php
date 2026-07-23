<?php
/** @var string $orgName */
/** @var string $orgNickname */
/** @var string $addressDisplay */
/** @var string $email */
/** @var string $phoneDisplay */
/** @var string $phoneTel */
?>
<div class="org-portal-footer__inner">
  <div class="org-portal-footer__org">
    <span class="org-portal-footer__label"><?php echo htmlspecialchars(t('org_footer.contact')); ?></span>
    <strong class="org-portal-footer__name"><?php echo htmlspecialchars($orgName); ?></strong>
    <?php if ($orgNickname !== '' && strcasecmp($orgNickname, $orgName) !== 0): ?>
      <span class="org-portal-footer__nickname">(<?php echo htmlspecialchars($orgNickname); ?>)</span>
    <?php endif; ?>
  </div>
  <div class="org-portal-footer__fields">
    <?php if ($addressDisplay !== '' || $mapsUrl !== ''): ?>
      <div class="org-portal-footer__field">
        <span class="org-portal-footer__field-label"><?php echo htmlspecialchars(t('org_footer.address')); ?></span>
        <span class="org-portal-footer__field-value">
          <?php if ($addressDisplay !== ''): ?>
            <i class="mdi mdi-map-marker-outline" aria-hidden="true"></i>
            <?php echo htmlspecialchars($addressDisplay); ?>
          <?php endif; ?>
          <?php echo maps_navigate_button($mapsUrl, ['class' => 'maps-nav-btn maps-nav-btn--footer', 'compact' => true]); ?>
        </span>
      </div>
    <?php endif; ?>
    <?php if ($email !== ''): ?>
      <div class="org-portal-footer__field">
        <span class="org-portal-footer__field-label"><?php echo htmlspecialchars(t('org_footer.email')); ?></span>
        <a class="org-portal-footer__field-value org-portal-footer__link" href="mailto:<?php echo htmlspecialchars($email); ?>">
          <i class="mdi mdi-email-outline" aria-hidden="true"></i>
          <?php echo htmlspecialchars($email); ?>
        </a>
      </div>
    <?php endif; ?>
    <?php if ($phoneDisplay !== ''): ?>
      <div class="org-portal-footer__field">
        <span class="org-portal-footer__field-label"><?php echo htmlspecialchars(t('org_footer.phone')); ?></span>
        <a class="org-portal-footer__field-value org-portal-footer__link" href="tel:<?php echo htmlspecialchars($phoneTel); ?>">
          <i class="mdi mdi-phone-outline" aria-hidden="true"></i>
          <?php echo htmlspecialchars($phoneDisplay); ?>
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>
