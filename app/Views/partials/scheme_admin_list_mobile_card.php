<?php
declare(strict_types=1);

$s = isset($s) && is_array($s) ? $s : [];
$schemeId = (int) ($s['id'] ?? 0);
$b = (string) ($b ?? base_url());
$benefitText = (string) ($s['benefit_type'] ?? '');
if (!empty($s['benefit_value'])) {
    $benefitText .= ' — ' . (string) $s['benefit_value'];
}
$isActive = (int) ($s['is_active'] ?? 0) === 1;
?>
<article class="family-member-card">
  <div class="family-member-card__top">
    <div class="family-member-card__identity">
      <a class="family-member-card__name family-member-card__name--link" href="<?php echo htmlspecialchars($b); ?>/organization/scheme?id=<?php echo $schemeId; ?>"><?php echo htmlspecialchars((string) ($s['name'] ?? '')); ?></a>
      <span class="family-member-card__pill family-member-card__pill--<?php echo $isActive ? 'head' : 'dependent'; ?>"><?php echo $isActive ? h('common.active') : h('schemes.inactive'); ?></span>
    </div>
    <a class="family-member-card__edit" href="<?php echo htmlspecialchars($b); ?>/organization/schemes/edit?id=<?php echo $schemeId; ?>"><?php echo h('common.edit'); ?></a>
  </div>
  <div class="family-member-card__labels">
    <span class="family-member-card__pill family-member-card__pill--role"><?php echo htmlspecialchars(t('schemes.scope')); ?>: <?php echo htmlspecialchars((string) ($s['benefit_scope'] ?? '')); ?></span>
    <?php if ($benefitText !== ''): ?>
    <span class="family-member-card__pill family-member-card__pill--profession"><?php echo htmlspecialchars(t('schemes.benefit')); ?>: <?php echo htmlspecialchars($benefitText); ?></span>
    <?php endif; ?>
    <span class="family-member-card__pill family-member-card__pill--code"><?php echo htmlspecialchars(t('schemes.assigned')); ?>: <?php echo (int) ($s['assignment_count'] ?? 0); ?></span>
  </div>
  <div class="family-member-card__actions family-member-card__actions--buttons">
    <?php
      $whatsappShareMessage = scheme_whatsapp_share_message($s);
      $whatsappShareClass = 'btn btn-success btn-whatsapp-icon btn-sm';
      require BASE_PATH . '/app/Views/partials/whatsapp_share_button.php';
    ?>
    <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/schemes/delete" class="d-inline" onsubmit="return confirm(<?php echo json_encode(t('schemes.delete_confirm')); ?>);">
      <input type="hidden" name="scheme_id" value="<?php echo $schemeId; ?>">
      <button type="submit" class="btn btn-sm btn-outline-danger"><?php echo htmlspecialchars(t('common.delete')); ?></button>
    </form>
  </div>
</article>
