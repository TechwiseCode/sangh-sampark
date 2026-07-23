<?php
declare(strict_types=1);

$row = isset($row) && is_array($row) ? $row : [];
$b = (string) ($b ?? base_url());
$benefitText = (string) ($row['benefit_type'] ?? '');
if (!empty($row['benefit_value'])) {
    $benefitText .= ' — ' . (string) $row['benefit_value'];
}
$isClaimed = (string) ($row['status'] ?? '') === 'claimed';
?>
<article class="family-member-card">
  <div class="family-member-card__top">
    <div class="family-member-card__identity">
      <span class="family-member-card__name"><?php echo htmlspecialchars((string) ($row['name'] ?? '')); ?></span>
      <span class="family-member-card__pill family-member-card__pill--<?php echo $isClaimed ? 'head' : 'profession'; ?>"><?php echo $isClaimed ? h('schemes.benefitted') : h('schemes.not_yet'); ?></span>
    </div>
  </div>
  <div class="family-member-card__labels">
    <span class="family-member-card__pill family-member-card__pill--role"><?php echo htmlspecialchars(t('schemes.scope')); ?>: <?php echo htmlspecialchars((string) ($row['benefit_scope'] ?? '')); ?></span>
    <?php if ($benefitText !== ''): ?>
    <span class="family-member-card__pill family-member-card__pill--code"><?php echo htmlspecialchars(t('schemes.benefit')); ?>: <?php echo htmlspecialchars($benefitText); ?></span>
    <?php endif; ?>
  </div>
  <?php if ($isClaimed): ?>
  <p class="family-member-card__sub"><?php echo htmlspecialchars(t('schemes.benefitted_at')); ?>: <?php echo htmlspecialchars(format_pretty_datetime(isset($row['claimed_at']) ? (string) $row['claimed_at'] : null)); ?></p>
  <?php endif; ?>
  <div class="family-member-card__actions family-member-card__actions--buttons">
    <?php
      $whatsappShareMessage = scheme_eligible_whatsapp_share_message($row);
      $whatsappShareClass = 'btn btn-success btn-whatsapp-icon btn-sm';
      require BASE_PATH . '/app/Views/partials/whatsapp_share_button.php';
    ?>
  </div>
</article>
