<?php
declare(strict_types=1);

$evId = (int) ($evId ?? 0);
$title = (string) ($title ?? '');
$eventDate = trim((string) ($eventDate ?? ''));
$fy = (string) ($fy ?? '');
$amount = (float) ($amount ?? 0);
$basis = strtolower((string) ($basis ?? 'per_family'));
$dueType = strtolower((string) ($dueType ?? 'event'));
$isCompulsory = !empty($isCompulsory);
$canManageOrg = !empty($canManageOrg);
$stats = isset($stats) && is_array($stats) ? $stats : null;
$passCount = (int) ($passCount ?? 0);
$redeemedCount = (int) ($redeemedCount ?? 0);
$amountPaid = (float) ($amountPaid ?? 0);
$b = (string) ($b ?? base_url());
$chargeLabel = $basis === 'per_person' ? t('events.per_person') : t('events.per_family');
?>
<article class="family-member-card">
  <div class="family-member-card__top">
    <div class="family-member-card__identity">
      <a class="family-member-card__name family-member-card__name--link" href="<?php echo htmlspecialchars($b); ?>/organization/event?id=<?php echo $evId; ?>"><?php echo htmlspecialchars($title); ?></a>
      <?php if ($dueType === 'occasion'): ?>
      <span class="family-member-card__pill family-member-card__pill--code"><?php echo h('receipts.dues_type_occasion'); ?></span>
      <?php endif; ?>
    </div>
    <a class="family-member-card__edit" href="<?php echo htmlspecialchars($b); ?>/organization/event?id=<?php echo $evId; ?>"><?php echo $canManageOrg ? h('events.redeem_passes') : h('events.col_open'); ?></a>
  </div>
  <div class="family-member-card__labels">
    <?php if ($eventDate !== ''): ?>
    <span class="family-member-card__pill family-member-card__pill--age"><?php echo htmlspecialchars(t('events.col_date')); ?>: <?php echo htmlspecialchars(format_pretty_date($eventDate)); ?></span>
    <?php endif; ?>
    <?php if ($fy !== ''): ?>
    <span class="family-member-card__pill family-member-card__pill--code"><?php echo htmlspecialchars(t('events.col_fy')); ?>: <?php echo htmlspecialchars($fy); ?></span>
    <?php endif; ?>
    <span class="family-member-card__pill family-member-card__pill--profession"><?php echo htmlspecialchars(t('events.col_amount')); ?>: <?php echo htmlspecialchars(number_format($amount, 2)); ?></span>
    <span class="family-member-card__pill family-member-card__pill--gender"><?php echo htmlspecialchars($chargeLabel); ?></span>
    <span class="family-member-card__pill family-member-card__pill--<?php echo $isCompulsory ? 'head' : 'dependent'; ?>"><?php echo $isCompulsory ? h('events.compulsory') : h('events.optional'); ?></span>
  </div>
  <?php if ($canManageOrg): ?>
  <p class="family-member-card__sub"><?php echo htmlspecialchars(t('events.col_active')); ?>: <?php echo $stats !== null ? (int) ($stats['active'] ?? 0) : 0; ?> · <?php echo htmlspecialchars(t('events.col_redeemed')); ?>: <?php echo $stats !== null ? (int) ($stats['redeemed'] ?? 0) : 0; ?></p>
  <?php elseif ($passCount > 0 || $redeemedCount > 0): ?>
  <p class="family-member-card__sub"><?php echo (int) $passCount; ?> <?php echo h('common.active'); ?><?php if ($redeemedCount > 0): ?> · <?php echo (int) $redeemedCount; ?> <?php echo h('common.redeemed'); ?><?php endif; ?></p>
  <?php elseif ($amountPaid > 0): ?>
  <p class="family-member-card__sub"><?php echo h('events.payment_recorded'); ?></p>
  <?php else: ?>
  <p class="family-member-card__sub"><?php echo h('events.no_passes'); ?></p>
  <?php endif; ?>
  <?php if (isset($whatsappShareMessage) && trim((string) $whatsappShareMessage) !== ''): ?>
  <div class="family-member-card__actions family-member-card__actions--buttons">
    <?php
      $whatsappShareClass = 'btn btn-success btn-whatsapp-icon btn-sm';
      require BASE_PATH . '/app/Views/partials/whatsapp_share_button.php';
    ?>
  </div>
  <?php endif; ?>
</article>
