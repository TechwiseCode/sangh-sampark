<?php
$b = (string) ($importBaseUrl ?? base_url());
$includeOrgCode = !empty($includeOrgCode);
$preview = isset($preview) && is_array($preview) ? $preview : null;
$errors = isset($errors) && is_array($errors) ? $errors : [];
$warnings = isset($warnings) && is_array($warnings) ? $warnings : [];
$previewUrl = $b . ($includeOrgCode ? '/superadmin/import/families/preview' : '/organization/members/import/preview');
$applyUrl = $b . ($includeOrgCode ? '/superadmin/import/families/apply' : '/organization/members/import/apply');
$sampleUrl = $b . ($includeOrgCode ? '/superadmin/import/families/sample' : '/organization/members/import/sample');
?>
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="card-title mb-0"><?php echo h(t('import.families.upload_title')); ?></h4>
          <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($sampleUrl); ?>"><?php echo h(t('import.families.download_sample')); ?></a>
        </div>
        <p class="text-muted small mb-3"><?php echo h(t('import.families.subtitle')); ?></p>
        <?php if (!$includeOrgCode): ?>
          <p class="text-muted small mb-3"><?php echo h(t('import.families.org_scope_note')); ?></p>
        <?php endif; ?>
        <form method="post" action="<?php echo htmlspecialchars($previewUrl); ?>" enctype="multipart/form-data">
          <div class="form-group">
            <label for="import_file_families"><?php echo h(t('import.families.csv_label')); ?></label>
            <input type="file" class="form-control" id="import_file_families" name="import_file" accept=".csv,text/csv" required>
            <small class="text-muted"><?php echo h(t('import.families.csv_help')); ?></small>
          </div>
          <button type="submit" class="btn btn-primary btn-sm"><?php echo h(t('import.families.validate')); ?></button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title h6"><?php echo h(t('import.families.guide_title')); ?></h4>
        <p class="small text-muted mb-2"><?php echo h(t('import.families.guide_intro')); ?></p>
        <p class="small font-weight-bold mb-1"><?php echo h(t('import.families.guide_all_types')); ?></p>
        <ul class="small pl-3 mb-2">
          <li><?php echo h(t('import.families.guide_req_family_ref')); ?></li>
          <li><?php echo h(t('import.families.guide_req_person_type')); ?></li>
          <li><?php echo h(t('import.families.guide_req_name')); ?></li>
          <?php if ($includeOrgCode): ?>
            <li><?php echo h(t('import.families.guide_req_org_code')); ?></li>
          <?php endif; ?>
        </ul>
        <p class="small font-weight-bold mb-1"><?php echo h(t('import.families.guide_head_member')); ?></p>
        <ul class="small pl-3 mb-2">
          <li><?php echo h(t('import.families.guide_req_phone')); ?></li>
          <li><?php echo h(t('import.families.guide_profile_block')); ?></li>
        </ul>
        <p class="small font-weight-bold mb-1"><?php echo h(t('import.families.guide_dependent')); ?></p>
        <ul class="small pl-3 mb-0">
          <li><?php echo h(t('import.families.guide_dep_fields')); ?></li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php if ($errors !== []): ?>
  <div class="row mt-3">
    <div class="col-lg-10">
      <div class="alert alert-warning mb-0">
        <strong><?php echo h(t('import.families.validation_title')); ?></strong>
        <ul class="mb-0 mt-2 pl-3">
          <?php foreach (array_slice($errors, 0, 30) as $err): ?>
            <li><?php echo htmlspecialchars((string) $err); ?></li>
          <?php endforeach; ?>
          <?php if (count($errors) > 30): ?>
            <li class="text-muted"><?php echo h(t('import.families.more_errors', ['count' => (string) (count($errors) - 30)])); ?></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($warnings !== []): ?>
  <div class="row mt-3">
    <div class="col-lg-10">
      <div class="alert alert-info mb-0">
        <strong><?php echo h(t('import.families.warnings_title')); ?></strong>
        <p class="small mb-2 mt-1"><?php echo h(t('import.families.warnings_hint')); ?></p>
        <ul class="mb-0 pl-3 small">
          <?php foreach (array_slice($warnings, 0, 25) as $warn): ?>
            <li><?php echo htmlspecialchars((string) $warn); ?></li>
          <?php endforeach; ?>
          <?php if (count($warnings) > 25): ?>
            <li class="text-muted"><?php echo h(t('import.families.more_warnings', ['count' => (string) (count($warnings) - 25)])); ?></li>
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
          <h4 class="card-title"><?php echo h(t('import.families.preview_title')); ?></h4>
          <div class="row mb-3">
            <div class="col-md-2"><strong><?php echo h(t('import.families.rows')); ?></strong> <?php echo (int) ($preview['total_rows'] ?? 0); ?></div>
            <div class="col-md-2"><strong><?php echo h(t('import.families.families')); ?></strong> <?php echo (int) ($preview['group_count'] ?? 0); ?></div>
            <div class="col-md-2 text-success"><strong><?php echo h(t('import.families.valid_groups')); ?></strong> <?php echo (int) ($preview['valid_groups'] ?? 0); ?></div>
            <div class="col-md-2 text-danger"><strong><?php echo h(t('import.families.invalid_groups')); ?></strong> <?php echo (int) ($preview['invalid_groups'] ?? 0); ?></div>
            <div class="col-md-2 text-info"><strong><?php echo h(t('import.families.profile_warnings')); ?></strong> <?php echo (int) ($preview['warning_count'] ?? 0); ?></div>
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
              <thead>
                <tr>
                  <?php if ($includeOrgCode): ?>
                    <th><?php echo h(t('import.families.col_org')); ?></th>
                  <?php endif; ?>
                  <th><?php echo h(t('import.families.col_family_ref')); ?></th>
                  <th><?php echo h(t('import.families.col_people')); ?></th>
                  <th><?php echo h(t('import.families.col_head_rows')); ?></th>
                  <th><?php echo h(t('import.families.col_status')); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (($preview['groups'] ?? []) as $g): ?>
                  <?php $ok = ((int) ($g['heads'] ?? 0) === 1); ?>
                  <tr>
                    <?php if ($includeOrgCode): ?>
                      <td><?php echo htmlspecialchars((string) ($g['organization_code'] ?? '')); ?></td>
                    <?php endif; ?>
                    <td><?php echo htmlspecialchars((string) ($g['family_ref'] ?? '')); ?></td>
                    <td><?php echo (int) ($g['total'] ?? 0); ?></td>
                    <td><?php echo (int) ($g['heads'] ?? 0); ?></td>
                    <td><?php echo $ok ? '<span class="badge badge-success">' . h(t('import.families.valid_badge')) . '</span>' : '<span class="badge badge-danger">' . h(t('import.families.invalid_badge')) . '</span>'; ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p class="small text-muted mb-0 mt-3"><?php echo h(t('import.families.next_step')); ?></p>
          <?php if (!empty($preview['valid_rows_json']) && (int) ($preview['valid_groups'] ?? 0) > 0): ?>
            <form method="post" action="<?php echo htmlspecialchars($applyUrl); ?>" class="mt-3">
              <input type="hidden" name="valid_rows_json" value="<?php echo htmlspecialchars((string) $preview['valid_rows_json']); ?>">
              <button type="submit" class="btn btn-success btn-sm"><?php echo h(t('import.families.apply')); ?></button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
