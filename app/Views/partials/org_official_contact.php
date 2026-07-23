<?php
$orgContact = isset($orgContact) && is_array($orgContact) ? $orgContact : null;
if ($orgContact === null || trim((string) ($orgContact['name'] ?? '')) === '') {
    return;
}
$orgName = trim((string) ($orgContact['name'] ?? ''));
$orgNickname = trim((string) ($orgContact['nickname'] ?? ''));
$address = trim((string) ($orgContact['address'] ?? ''));
$email = trim((string) ($orgContact['email'] ?? ''));
$phoneDisplay = trim((string) ($orgContact['phone_display'] ?? ''));
$hasAnyDetail = $address !== '' || $email !== '' || $phoneDisplay !== '' || trim((string) ($orgContact['maps_url'] ?? '')) !== '';
if (!$hasAnyDetail) {
    return;
}
$addressDisplay = $address !== '' ? preg_replace('/\s*\n\s*/', ', ', $address) : '';
$mapsUrl = trim((string) ($orgContact['maps_url'] ?? ''));
$phoneTel = preg_replace('/\D+/', '', (string) ($orgContact['phone'] ?? $phoneDisplay));
?>
<footer class="org-portal-footer" role="contentinfo" aria-label="<?php echo htmlspecialchars(t('org_footer.aria')); ?>">
  <details class="org-portal-footer__drawer org-portal-footer__drawer--mobile">
    <summary class="org-portal-footer__summary">
      <span class="org-portal-footer__summary-main">
        <span class="org-portal-footer__label"><?php echo htmlspecialchars(t('org_footer.contact')); ?></span>
        <strong class="org-portal-footer__name"><?php echo htmlspecialchars($orgName); ?></strong>
      </span>
      <span class="org-portal-footer__summary-actions" aria-hidden="true">
        <?php if ($phoneDisplay !== ''): ?>
          <i class="mdi mdi-phone-outline"></i>
        <?php endif; ?>
        <?php if ($email !== ''): ?>
          <i class="mdi mdi-email-outline"></i>
        <?php endif; ?>
        <?php if ($mapsUrl !== ''): ?>
          <i class="mdi mdi-navigation"></i>
        <?php endif; ?>
        <i class="mdi mdi-chevron-down org-portal-footer__chevron"></i>
      </span>
    </summary>
    <?php require BASE_PATH . '/app/Views/partials/org_official_contact_inner.php'; ?>
  </details>
  <div class="org-portal-footer__drawer org-portal-footer__drawer--desktop">
    <?php require BASE_PATH . '/app/Views/partials/org_official_contact_inner.php'; ?>
  </div>
</footer>
