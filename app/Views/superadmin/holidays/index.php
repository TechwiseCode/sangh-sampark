<?php
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$b = base_url();
$holidays = isset($holidays) && is_array($holidays) ? $holidays : [];
$categories = platform_holiday_category_options();
$sort = (string) ($sort ?? 'dates');
$dir = (string) ($dir ?? 'desc');
$sortPath = '/superadmin/holidays';
?>
<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center flex-wrap border-bottom pb-3 mb-3">
    <div>
      <h3 class="mb-0"><?php echo h(t('holidays.superadmin.title')); ?></h3>
      <p class="text-muted mb-0 small"><?php echo h(t('holidays.superadmin.subtitle')); ?></p>
    </div>
    <a class="btn btn-primary btn-sm mt-2 mt-md-0" href="<?php echo htmlspecialchars($b); ?>/superadmin/holidays/new"><?php echo h(t('holidays.superadmin.add')); ?></a>
  </div>
</div>
<?php if ($flashOk): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($flashOk); ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($flashErr); ?></div>
<?php endif; ?>
<div class="card">
  <div class="card-body">
    <?php if ($holidays === []): ?>
      <p class="text-muted mb-0">
        <?php echo h(t('holidays.superadmin.none')); ?>
        <a href="<?php echo htmlspecialchars($b); ?>/superadmin/holidays/new"><?php echo h(t('holidays.superadmin.add_first')); ?></a>.
      </p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <?php echo sortable_th(t('holidays.superadmin.col_title'), 'title', $sort, $dir, $sortPath); ?>
              <?php echo sortable_th(t('holidays.superadmin.col_category'), 'category', $sort, $dir, $sortPath); ?>
              <?php echo sortable_th(t('holidays.superadmin.col_dates'), 'dates', $sort, $dir, $sortPath, [], 'desc'); ?>
              <?php echo sortable_th(t('holidays.superadmin.col_notes'), 'notes', $sort, $dir, $sortPath); ?>
              <th class="text-right"><?php echo h(t('holidays.superadmin.col_actions')); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($holidays as $row): ?>
              <?php
              $hid = (int) ($row['id'] ?? 0);
              $cat = normalize_platform_holiday_category((string) ($row['category'] ?? ''));
              $start = (string) ($row['start_date'] ?? '');
              $end = (string) ($row['end_date'] ?? '');
              $range = $start === $end ? format_pretty_date($start) : format_pretty_date($start) . ' – ' . format_pretty_date($end);
              ?>
              <tr>
                <td>
                  <strong><?php echo htmlspecialchars(platform_holiday_display_title($row)); ?></strong>
                  <?php if (trim((string) ($row['title_gu'] ?? '')) !== '' && current_locale() !== 'gu'): ?>
                    <br><span class="text-muted small"><?php echo htmlspecialchars((string) $row['title_gu']); ?></span>
                  <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars(platform_holiday_category_label($cat)); ?></td>
                <td><?php echo htmlspecialchars($range); ?></td>
                <td class="text-muted small"><?php echo htmlspecialchars(mb_strimwidth((string) ($row['notes'] ?? ''), 0, 80, '…')); ?></td>
                <td class="text-right text-nowrap">
                  <a class="btn btn-outline-primary btn-sm" href="<?php echo htmlspecialchars($b); ?>/superadmin/holidays/edit?id=<?php echo $hid; ?>"><?php echo h(t('common.edit')); ?></a>
                  <form method="post" action="<?php echo htmlspecialchars($b); ?>/superadmin/holidays/delete" class="d-inline" onsubmit="return confirm(<?php echo json_encode(t('holidays.superadmin.delete_confirm')); ?>);">
                    <input type="hidden" name="id" value="<?php echo $hid; ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm"><?php echo h(t('common.delete')); ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
