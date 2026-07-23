<?php
/**
 * WhatsApp share button (icon only).
 *
 * Required: $whatsappShareMessage (string)
 * Optional: $whatsappSharePhone, $whatsappShareLabel, $whatsappShareSize ('sm'), $whatsappShareClass
 */
if (!isset($whatsappShareMessage) || trim((string) $whatsappShareMessage) === '') {
    return;
}
$waPhone = isset($whatsappSharePhone) ? (string) $whatsappSharePhone : '';
$waUrl = whatsapp_share_url((string) $whatsappShareMessage, $waPhone !== '' ? $waPhone : null);
$waLabel = isset($whatsappShareLabel) ? (string) $whatsappShareLabel : t('common.share_whatsapp');
$waSize = isset($whatsappShareSize) ? (string) $whatsappShareSize : 'sm';
$waBtnClass = isset($whatsappShareClass) && (string) $whatsappShareClass !== ''
    ? (string) $whatsappShareClass
    : 'btn btn-success btn-whatsapp-icon ml-1' . ($waSize === 'sm' ? ' btn-sm' : '');
?>
<a href="<?php echo htmlspecialchars($waUrl, ENT_QUOTES, 'UTF-8'); ?>"
  class="<?php echo htmlspecialchars($waBtnClass, ENT_QUOTES, 'UTF-8'); ?>"
  target="_blank"
  rel="noopener noreferrer"
  title="<?php echo htmlspecialchars($waLabel, ENT_QUOTES, 'UTF-8'); ?>"
  aria-label="<?php echo htmlspecialchars($waLabel, ENT_QUOTES, 'UTF-8'); ?>">
  <i class="mdi mdi-whatsapp" aria-hidden="true"></i>
</a>
