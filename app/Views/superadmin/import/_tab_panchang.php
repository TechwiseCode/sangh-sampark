<?php
$b = base_url();
$preview = isset($preview) && is_array($preview) ? $preview : null;
$errors = isset($errors) && is_array($errors) ? $errors : [];
$existingCount = (int) ($existingCount ?? 0);
?>
<div class="row">
  <div class="col-lg-8">
    <div class="alert alert-light border mb-3">
      <?php echo h(t('superadmin.import.panchang.existing_count', ['count' => (string) $existingCount])); ?>
    </div>
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-3"><?php echo h(t('superadmin.import.panchang.upload_title')); ?></h4>
        <p class="small text-muted mb-3"><?php echo h(t('superadmin.import.panchang.subtitle')); ?></p>
        <p class="small text-muted"><?php echo h(t('superadmin.import.panchang.columns_help')); ?></p>
        <form method="post" action="<?php echo htmlspecialchars($b); ?>/superadmin/import/panchang/preview" enctype="multipart/form-data">
          <div class="form-group">
            <label for="import_file_panchang"><?php echo h(t('superadmin.import.panchang.csv_label')); ?></label>
            <input type="file" class="form-control" id="import_file_panchang" name="import_file" accept=".csv,text/csv" required>
            <small class="text-muted"><?php echo h(t('superadmin.import.panchang.csv_help')); ?></small>
          </div>
          <button type="submit" class="btn btn-primary btn-sm"><?php echo h(t('superadmin.import.panchang.validate')); ?></button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if ($errors !== []): ?>
  <div class="row mt-3">
    <div class="col-lg-10">
      <div class="alert alert-warning mb-0">
        <strong><?php echo h(t('superadmin.import.panchang.validation_title')); ?></strong>
        <ul class="mb-0 mt-2 pl-3">
          <?php foreach (array_slice($errors, 0, 20) as $err): ?>
            <li><?php echo htmlspecialchars((string) $err); ?></li>
          <?php endforeach; ?>
          <?php if (count($errors) > 20): ?>
            <li class="text-muted"><?php echo h(t('superadmin.import.panchang.more_errors', ['count' => (string) (count($errors) - 20)])); ?></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($preview !== null): ?>
  <div class="row mt-3">
    <div class="col-lg-10">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title"><?php echo h(t('superadmin.import.panchang.preview_title')); ?></h4>
          <div class="row mb-3">
            <div class="col-md-4"><strong><?php echo h(t('superadmin.import.panchang.rows')); ?></strong> <?php echo (int) ($preview['total_rows'] ?? 0); ?></div>
            <div class="col-md-4 text-success"><strong><?php echo h(t('superadmin.import.panchang.valid_rows')); ?></strong> <?php echo (int) ($preview['valid_rows'] ?? 0); ?></div>
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
              <thead>
                <tr>
                  <th><?php echo h(t('superadmin.import.panchang.col_date')); ?></th>
                  <th><?php echo h(t('superadmin.import.panchang.col_day')); ?></th>
                  <th><?php echo h(t('superadmin.import.panchang.col_panchang')); ?></th>
                  <th><?php echo h(t('superadmin.import.panchang.col_festival')); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (($preview['sample'] ?? []) as $row): ?>
                  <tr>
                    <td><?php echo htmlspecialchars((string) ($row['english_date'] ?? $row['gregorian_date'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars((string) ($row['weekday'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars((string) ($row['summary'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars((string) ($row['festival_notes'] ?? '')); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p class="small text-muted mb-0 mt-3"><?php echo h(t('superadmin.import.panchang.next_step')); ?></p>
          <?php if (!empty($preview['valid_rows_json']) && (int) ($preview['valid_rows'] ?? 0) > 0): ?>
            <form method="post" action="<?php echo htmlspecialchars($b); ?>/superadmin/import/panchang/apply" class="mt-3">
              <input type="hidden" name="valid_rows_json" value="<?php echo htmlspecialchars((string) $preview['valid_rows_json']); ?>">
              <button type="submit" class="btn btn-success btn-sm"><?php echo h(t('superadmin.import.panchang.apply')); ?></button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
