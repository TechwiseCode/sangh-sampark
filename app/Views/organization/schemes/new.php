<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
?>
<div class="row">
  <div class="col-12 border-bottom">
    <h3 class="mb-0"><?php echo h(t('schemes.form.new_title')); ?></h3>
    <p class="text-muted mb-0"><?php echo h(t('schemes.form.new_subtitle')); ?></p>
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
        <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/schemes">
          <div class="form-group">
            <label for="scheme_name"><?php echo h(t('schemes.form.name')); ?></label>
            <input id="scheme_name" type="text" name="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="scheme_description"><?php echo h(t('schemes.form.description')); ?></label>
            <textarea id="scheme_description" name="description" class="form-control" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label for="benefit_scope"><?php echo h(t('schemes.form.scope')); ?></label>
            <select id="benefit_scope" name="benefit_scope" class="form-control" required>
              <option value="family"><?php echo h(t('schemes.form.scope_family')); ?></option>
              <option value="member"><?php echo h(t('schemes.form.scope_member')); ?></option>
            </select>
          </div>
          <div class="form-group">
            <label for="benefit_type"><?php echo h(t('schemes.form.benefit_type')); ?></label>
            <input id="benefit_type" type="text" name="benefit_type" class="form-control" placeholder="<?php echo h(t('schemes.form.benefit_type_placeholder')); ?>" required>
          </div>
          <div class="form-group">
            <label for="benefit_value"><?php echo h(t('schemes.form.benefit_value')); ?></label>
            <input id="benefit_value" type="text" name="benefit_value" class="form-control" placeholder="<?php echo h(t('schemes.form.benefit_value_placeholder')); ?>">
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="starts_at"><?php echo h(t('schemes.form.starts_at')); ?></label>
              <input id="starts_at" type="date" name="starts_at" class="form-control">
            </div>
            <div class="form-group col-md-6">
              <label for="ends_at"><?php echo h(t('schemes.form.ends_at')); ?></label>
              <input id="ends_at" type="date" name="ends_at" class="form-control">
            </div>
          </div>
          <button type="submit" class="btn btn-primary"><?php echo h(t('schemes.form.create')); ?></button>
          <a class="btn btn-light" href="<?php echo htmlspecialchars($b); ?>/organization/events?event_tab=schemes"><?php echo h(t('schemes.form.cancel')); ?></a>
        </form>
      </div>
    </div>
  </div>
</div>

