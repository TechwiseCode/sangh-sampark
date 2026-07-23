<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$scheme = $scheme ?? [];
?>
<div class="row">
  <div class="col-12 border-bottom">
    <h3 class="mb-0"><?php echo h(t('schemes.form.edit_title')); ?></h3>
    <p class="text-muted mb-0"><?php echo h(t('schemes.form.edit_subtitle')); ?></p>
  </div>
</div>
<?php if ($flashOk): ?>
  <div class="alert alert-success mt-3"><?php echo htmlspecialchars($flashOk); ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
  <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($flashErr); ?></div>
<?php endif; ?>

<div class="row" style="padding-top: 16px;">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/schemes/update">
          <input type="hidden" name="scheme_id" value="<?php echo (int) ($scheme['id'] ?? 0); ?>">
          <div class="form-group">
            <label for="scheme_name"><?php echo h(t('schemes.form.name')); ?></label>
            <input id="scheme_name" type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars((string) ($scheme['name'] ?? '')); ?>">
          </div>
          <div class="form-group">
            <label for="scheme_description"><?php echo h(t('schemes.form.description')); ?></label>
            <textarea id="scheme_description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars((string) ($scheme['description'] ?? '')); ?></textarea>
          </div>
          <div class="form-group">
            <label><?php echo h(t('schemes.form.scope_readonly')); ?></label>
            <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars((string) ($scheme['benefit_scope'] ?? '')); ?>">
          </div>
          <div class="form-group">
            <label for="benefit_type"><?php echo h(t('schemes.form.benefit_type')); ?></label>
            <input id="benefit_type" type="text" name="benefit_type" class="form-control" required value="<?php echo htmlspecialchars((string) ($scheme['benefit_type'] ?? '')); ?>">
          </div>
          <div class="form-group">
            <label for="benefit_value"><?php echo h(t('schemes.form.benefit_value')); ?></label>
            <input id="benefit_value" type="text" name="benefit_value" class="form-control" value="<?php echo htmlspecialchars((string) ($scheme['benefit_value'] ?? '')); ?>">
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="starts_at"><?php echo h(t('schemes.form.starts_at')); ?></label>
              <input id="starts_at" type="date" name="starts_at" class="form-control" value="<?php echo htmlspecialchars(format_date_input(isset($scheme['starts_at']) ? (string) $scheme['starts_at'] : null)); ?>">
            </div>
            <div class="form-group col-md-6">
              <label for="ends_at"><?php echo h(t('schemes.form.ends_at')); ?></label>
              <input id="ends_at" type="date" name="ends_at" class="form-control" value="<?php echo htmlspecialchars(format_date_input(isset($scheme['ends_at']) ? (string) $scheme['ends_at'] : null)); ?>">
            </div>
          </div>
          <div class="form-group form-check">
            <input id="is_active" type="checkbox" name="is_active" value="1" class="form-check-input" <?php echo ((int) ($scheme['is_active'] ?? 0) === 1) ? 'checked' : ''; ?>>
            <label for="is_active" class="form-check-label"><?php echo h(t('schemes.form.active')); ?></label>
          </div>
          <button type="submit" class="btn btn-primary"><?php echo h(t('schemes.form.save')); ?></button>
          <a class="btn btn-light" href="<?php echo htmlspecialchars($b); ?>/organization/events?event_tab=schemes"><?php echo h(t('schemes.form.cancel')); ?></a>
        </form>
      </div>
    </div>
  </div>
</div>

