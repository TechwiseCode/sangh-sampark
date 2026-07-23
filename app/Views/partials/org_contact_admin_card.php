<?php
$orgContact = isset($orgContact) && is_array($orgContact) ? $orgContact : null;
if ($orgContact === null) {
    return;
}
$address = trim((string) ($orgContact['address'] ?? ''));
$email = trim((string) ($orgContact['email'] ?? ''));
$phoneDisplay = trim((string) ($orgContact['phone_display'] ?? ''));
$phoneTel = preg_replace('/\D+/', '', (string) ($orgContact['phone'] ?? $phoneDisplay));
$notSet = t('dashboard.org_contact_not_set');
$addressDisplay = $address !== '' ? preg_replace('/\s*\n\s*/', ', ', $address) : '';
$hasAny = $addressDisplay !== '' || $email !== '' || $phoneDisplay !== '';
if (!$hasAny) {
    return;
}
?>
<div class="dash-header-contact" aria-label="<?php echo htmlspecialchars(t('dashboard.org_contact_title')); ?>">
  <?php if ($addressDisplay !== ''): ?>
    <span class="dash-header-contact__item dash-header-contact__item--address" title="<?php echo htmlspecialchars(t('org_footer.address')); ?>">
      <i class="mdi mdi-map-marker-outline" aria-hidden="true"></i>
      <span><?php echo htmlspecialchars($addressDisplay); ?></span>
    </span>
  <?php endif; ?>
  <?php if ($email !== ''): ?>
    <a class="dash-header-contact__item dash-header-contact__link" href="mailto:<?php echo htmlspecialchars($email); ?>" title="<?php echo htmlspecialchars(t('org_footer.email')); ?>">
      <i class="mdi mdi-email-outline" aria-hidden="true"></i>
      <span><?php echo htmlspecialchars($email); ?></span>
    </a>
  <?php endif; ?>
  <?php if ($phoneDisplay !== ''): ?>
    <a class="dash-header-contact__item dash-header-contact__link" href="tel:<?php echo htmlspecialchars($phoneTel); ?>" title="<?php echo htmlspecialchars(t('org_footer.phone')); ?>">
      <i class="mdi mdi-phone-outline" aria-hidden="true"></i>
      <span><?php echo htmlspecialchars($phoneDisplay); ?></span>
    </a>
  <?php endif; ?>
</div>
