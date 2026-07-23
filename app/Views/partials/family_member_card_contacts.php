<?php
$email = trim((string) ($email ?? ''));
$phoneDisplay = trim((string) ($phoneDisplay ?? ''));
$phoneTel = preg_replace('/\D+/', '', (string) ($phoneTel ?? $phoneDisplay));
if ($email === '' && $phoneDisplay === '') {
    return;
}
?>
<div class="family-member-card__contacts">
  <?php if ($email !== ''): ?>
  <a class="family-member-card__contact-row family-member-card__contact-row--email" href="mailto:<?php echo htmlspecialchars($email); ?>">
    <i class="mdi mdi-email-outline" aria-hidden="true"></i>
    <span class="family-member-card__contact-value"><?php echo htmlspecialchars($email); ?></span>
  </a>
  <?php endif; ?>
  <?php if ($phoneDisplay !== ''): ?>
  <a class="family-member-card__contact-row family-member-card__contact-row--phone" href="tel:<?php echo htmlspecialchars($phoneTel); ?>">
    <i class="mdi mdi-phone-outline" aria-hidden="true"></i>
    <span class="family-member-card__contact-value"><?php echo htmlspecialchars($phoneDisplay); ?></span>
  </a>
  <?php endif; ?>
</div>
