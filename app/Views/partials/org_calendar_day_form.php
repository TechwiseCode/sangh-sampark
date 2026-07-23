<?php
$b = base_url();
$formError = isset($formError) ? (string) $formError : null;
$dayId = isset($dayId) ? (int) $dayId : 0;
$isEdit = $dayId > 0;
$categories = org_calendar_day_category_options();
?>
<div class="calendar-days-form-card card">
  <div class="card-body">
    <div class="calendar-days-form-head">
      <div>
        <h4 class="calendar-days-form-title mb-0"><?php echo h($isEdit ? t('calendar_days.org.edit') : t('calendar_days.org.add')); ?></h4>
        <p class="text-muted small mb-0 mt-1"><?php echo h(t('calendar_days.org.form_help')); ?></p>
      </div>
      <?php if ($isEdit): ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($b); ?>/organization/calendar-days"><?php echo h(t('calendar_days.org.add_new')); ?></a>
      <?php endif; ?>
    </div>
    <?php if ($formError): ?>
      <div class="alert alert-danger mt-3 mb-0"><?php echo htmlspecialchars($formError); ?></div>
    <?php endif; ?>
    <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/calendar-days<?php echo $isEdit ? '/update' : ''; ?>" class="calendar-days-form mt-3">
      <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?php echo $dayId; ?>">
      <?php endif; ?>
      <div class="form-group">
        <label for="day_title"><?php echo h(t('calendar_days.org.label_title')); ?></label>
        <input type="text" class="form-control form-control-sm" id="day_title" name="title" required maxlength="191" value="<?php echo htmlspecialchars((string) ($titleDraft ?? '')); ?>">
      </div>
      <div class="form-group">
        <label for="day_title_gu"><?php echo h(t('calendar_days.org.label_title_gu')); ?></label>
        <input type="text" class="form-control form-control-sm" id="day_title_gu" name="title_gu" maxlength="191" value="<?php echo htmlspecialchars((string) ($titleGuDraft ?? '')); ?>">
      </div>
      <div class="form-group">
        <label for="day_category"><?php echo h(t('calendar_days.org.label_category')); ?></label>
        <select class="form-control form-control-sm" id="day_category" name="category" required>
          <?php foreach ($categories as $key => $label): ?>
            <option value="<?php echo htmlspecialchars($key); ?>"<?php echo ($categoryDraft ?? '') === $key ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group<?php echo org_calendar_day_shows_event_time((string) ($categoryDraft ?? '')) ? '' : ' d-none'; ?>" id="day_event_time_wrap">
        <label for="day_event_time"><?php echo h(t('calendar_days.org.label_time')); ?></label>
        <input type="time" class="form-control form-control-sm" id="day_event_time" name="event_time" value="<?php echo htmlspecialchars((string) ($eventTimeDraft ?? '')); ?>">
        <small class="text-muted"><?php echo h(t('calendar_days.org.time_hint')); ?></small>
      </div>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label for="day_start"><?php echo h(t('calendar_days.org.label_start')); ?></label>
          <input type="date" class="form-control form-control-sm" id="day_start" name="start_date" required value="<?php echo htmlspecialchars((string) ($startDateDraft ?? '')); ?>">
        </div>
        <div class="form-group col-md-6">
          <label for="day_end"><?php echo h(t('calendar_days.org.label_end')); ?></label>
          <input type="date" class="form-control form-control-sm" id="day_end" name="end_date" value="<?php echo htmlspecialchars((string) ($endDateDraft ?? '')); ?>">
          <small class="text-muted"><?php echo h(t('calendar_days.org.end_hint')); ?></small>
        </div>
      </div>
      <div class="form-group mb-0">
        <label for="day_notes"><?php echo h(t('calendar_days.org.label_notes')); ?></label>
        <textarea class="form-control form-control-sm" id="day_notes" name="notes" rows="3"><?php echo htmlspecialchars((string) ($notesDraft ?? '')); ?></textarea>
      </div>
      <div class="calendar-days-form-actions">
        <button type="submit" class="btn btn-primary btn-sm"><?php echo h($isEdit ? t('common.save') : t('calendar_days.org.submit')); ?></button>
        <?php if ($isEdit): ?>
          <a class="btn btn-light btn-sm" href="<?php echo htmlspecialchars($b); ?>/organization/calendar-days"><?php echo h(t('common.cancel')); ?></a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
<script>
(function () {
  var categorySelect = document.getElementById('day_category');
  var timeWrap = document.getElementById('day_event_time_wrap');
  if (!categorySelect || !timeWrap) return;
  var timedCategories = ['vyakhyan', 'pratikraman'];
  function syncTimedCategory() {
    timeWrap.classList.toggle('d-none', timedCategories.indexOf(categorySelect.value) === -1);
  }
  categorySelect.addEventListener('change', syncTimedCategory);
  syncTimedCategory();
})();
</script>
