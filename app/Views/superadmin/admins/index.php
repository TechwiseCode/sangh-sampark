<?php
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$b = base_url();
$orgName = 'All organizations';
$admins = isset($admins) && is_array($admins) ? $admins : [];
?>
<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center flex-wrap border-bottom pb-3 mb-3">
    <div>
      <h3 class="mb-0"><?php echo h(t('superadmin.admins.index.title')); ?></h3>
      <p class="text-muted mb-0 small"><?php echo htmlspecialchars($orgName); ?></p>
    </div>
    <a class="btn btn-primary btn-sm mt-2 mt-md-0" href="<?php echo htmlspecialchars($b); ?>/superadmin/admins/new"><?php echo h(t('superadmin.admins.index.add')); ?></a>
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
    <?php if ($admins === []): ?>
      <p class="text-muted mb-0">
        <?php echo h(t('superadmin.admins.index.none')); ?>
        <a href="<?php echo htmlspecialchars($b); ?>/superadmin/admins/new"><?php echo h(t('superadmin.admins.index.add_first')); ?></a>.
      </p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th><?php echo h(t('superadmin.admins.index.col_name')); ?></th>
              <th><?php echo h(t('superadmin.admins.index.col_email')); ?></th>
              <th><?php echo h(t('superadmin.admins.index.col_phone')); ?></th>
              <th><?php echo h(t('superadmin.admins.index.col_since')); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($admins as $a): ?>
              <tr>
                <td><?php echo htmlspecialchars((string) ($a['name'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars((string) ($a['email'] ?? '—')); ?></td>
                <td><?php echo htmlspecialchars((string) ($a['phone'] ?? '—')); ?></td>
                <td><?php echo htmlspecialchars(format_pretty_date(isset($a['created_at']) ? (string) $a['created_at'] : null)); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
