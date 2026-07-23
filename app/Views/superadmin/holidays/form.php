<?php
$b = base_url();
$formError = isset($formError) ? (string) $formError : null;
$holidayId = isset($holidayId) ? (int) $holidayId : 0;
$isEdit = $holidayId > 0;
$categories = platform_holiday_category_options();
?>
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title"><?php echo h($isEdit ? t('holidays.superadmin.edit') : t('holidays.superadmin.add')); ?></h4>
        <p class="text-muted small"><?php echo h(t('holidays.superadmin.form_help')); ?></p>
        <?php if ($formError): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($formError); ?></div>
        <?php endif; ?>
        <form method="post" action="<?php echo htmlspecialchars($b); ?>/superadmin/holidays<?php echo $isEdit ? '/update' : ''; ?>">
          <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?php echo $holidayId; ?>">
          <?php endif; ?>
          <div class="form-group">
            <label for="holiday_title"><?php echo h(t('holidays.superadmin.label_title')); ?></label>
            <input type="text" class="form-control" id="holiday_title" name="title" required maxlength="191" value="<?php echo htmlspecialchars((string) ($titleDraft ?? '')); ?>">
          </div>
          <div class="form-group">
            <label for="holiday_title_gu"><?php echo h(t('holidays.superadmin.label_title_gu')); ?></label>
            <input type="text" class="form-control" id="holiday_title_gu" name="title_gu" maxlength="191" value="<?php echo htmlspecialchars((string) ($titleGuDraft ?? '')); ?>">
          </div>
          <div class="form-group">
            <label for="holiday_category"><?php echo h(t('holidays.superadmin.label_category')); ?></label>
            <select class="form-control" id="holiday_category" name="category" required>
              <?php foreach ($categories as $key => $label): ?>
                <option value="<?php echo htmlspecialchars($key); ?>"<?php echo ($categoryDraft ?? '') === $key ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="holiday_start"><?php echo h(t('holidays.superadmin.label_start')); ?></label>
              <input type="date" class="form-control" id="holiday_start" name="start_date" required value="<?php echo htmlspecialchars((string) ($startDateDraft ?? '')); ?>">
            </div>
            <div class="form-group col-md-6">
              <label for="holiday_end"><?php echo h(t('holidays.superadmin.label_end')); ?></label>
              <input type="date" class="form-control" id="holiday_end" name="end_date" value="<?php echo htmlspecialchars((string) ($endDateDraft ?? '')); ?>">
              <small class="text-muted"><?php echo h(t('holidays.superadmin.end_hint')); ?></small>
            </div>
          </div>
          <div class="form-group">
            <label for="holiday_notes"><?php echo h(t('holidays.superadmin.label_notes')); ?></label>
            <textarea class="form-control" id="holiday_notes" name="notes" rows="3"><?php echo htmlspecialchars((string) ($notesDraft ?? '')); ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary"><?php echo h($isEdit ? t('common.save') : t('holidays.superadmin.submit')); ?></button>
          <a class="btn btn-light" href="<?php echo htmlspecialchars($b); ?>/superadmin/holidays"><?php echo h(t('common.cancel')); ?></a>
        </form>
      </div>
    </div>
  </div>
</div>
