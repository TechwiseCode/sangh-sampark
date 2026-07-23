<?php
declare(strict_types=1);
$nameFieldPrefix = $nameFieldPrefix ?? '';
$nameFieldIdPrefix = $nameFieldIdPrefix ?? $nameFieldPrefix;
$nameFieldRow = isset($nameFieldRow) && is_array($nameFieldRow) ? $nameFieldRow : [];
$nameFieldRequired = !isset($nameFieldRequired) || $nameFieldRequired;
$parts = person_name_parts_from_row($nameFieldRow);
$requiredAttr = $nameFieldRequired ? ' required' : '';
?>
<div class="person-name-fields">
  <div class="form-group person-name-fields__part">
    <label class="person-name-fields__label" for="<?php echo htmlspecialchars($nameFieldIdPrefix); ?>first_name"><?php echo h(t('person.first_name')); ?><?php echo $nameFieldRequired ? ' *' : ''; ?></label>
    <input type="text" class="form-control" id="<?php echo htmlspecialchars($nameFieldIdPrefix); ?>first_name" name="<?php echo htmlspecialchars($nameFieldPrefix); ?>first_name"<?php echo $requiredAttr; ?> autocomplete="given-name" value="<?php echo htmlspecialchars((string) $parts['first_name']); ?>">
  </div>
  <div class="form-group person-name-fields__part">
    <label class="person-name-fields__label" for="<?php echo htmlspecialchars($nameFieldIdPrefix); ?>middle_name"><?php echo h(t('person.middle_name')); ?></label>
    <input type="text" class="form-control" id="<?php echo htmlspecialchars($nameFieldIdPrefix); ?>middle_name" name="<?php echo htmlspecialchars($nameFieldPrefix); ?>middle_name" autocomplete="additional-name" value="<?php echo htmlspecialchars((string) ($parts['middle_name'] ?? '')); ?>">
  </div>
  <div class="form-group person-name-fields__part">
    <label class="person-name-fields__label" for="<?php echo htmlspecialchars($nameFieldIdPrefix); ?>last_name"><?php echo h(t('person.last_name')); ?><?php echo $nameFieldRequired ? ' *' : ''; ?></label>
    <input type="text" class="form-control" id="<?php echo htmlspecialchars($nameFieldIdPrefix); ?>last_name" name="<?php echo htmlspecialchars($nameFieldPrefix); ?>last_name"<?php echo $requiredAttr; ?> autocomplete="family-name" value="<?php echo htmlspecialchars((string) $parts['last_name']); ?>">
  </div>
</div>
