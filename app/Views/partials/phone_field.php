<?php
declare(strict_types=1);
$phoneFieldPrefix = $phoneFieldPrefix ?? '';
$phoneFieldIdPrefix = $phoneFieldIdPrefix ?? $phoneFieldPrefix;
$phoneFieldValue = (string) ($phoneFieldValue ?? '');
$phoneFieldRequired = !isset($phoneFieldRequired) || $phoneFieldRequired;
$phoneRaw = preg_replace('/\D+/', '', $phoneFieldValue);
$phoneDisplay = $phoneRaw;
if (strlen($phoneRaw) === 12 && strpos($phoneRaw, '91') === 0) {
    $phoneDisplay = substr($phoneRaw, 2);
}
$requiredAttr = $phoneFieldRequired ? ' required' : '';
$patternAttr = $phoneFieldRequired ? ' pattern="\\d{10}"' : '';
?>
<div class="form-group<?php echo isset($phoneFieldClass) ? ' ' . htmlspecialchars((string) $phoneFieldClass) : ''; ?>">
  <label for="<?php echo htmlspecialchars($phoneFieldIdPrefix); ?>phone_visible"><?php echo h(t('profile.phone')); ?><?php echo $phoneFieldRequired ? ' *' : ''; ?></label>
  <div class="input-group">
    <div class="input-group-prepend">
      <span class="input-group-text">+91</span>
    </div>
    <input type="tel" class="form-control" id="<?php echo htmlspecialchars($phoneFieldIdPrefix); ?>phone_visible" maxlength="10" inputmode="numeric"<?php echo $requiredAttr . $patternAttr; ?> autocomplete="tel" value="<?php echo htmlspecialchars($phoneDisplay); ?>">
  </div>
  <input type="hidden" name="<?php echo htmlspecialchars($phoneFieldPrefix); ?>phone" id="<?php echo htmlspecialchars($phoneFieldIdPrefix); ?>phone_hidden" value="<?php echo htmlspecialchars($phoneFieldValue); ?>">
</div>
