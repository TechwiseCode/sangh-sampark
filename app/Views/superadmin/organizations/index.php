<?php
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$b = base_url();
$sort = (string) ($sort ?? 'name');
$dir = (string) ($dir ?? 'asc');
$sortPath = '/superadmin/organizations';
?>
<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center border-bottom flex-wrap">
    <h3 class="mb-0"><?php echo h(t('superadmin.organizations.index.title')); ?></h3>
    <a class="btn btn-success mb-2" href="<?php echo htmlspecialchars($b); ?>/superadmin/organizations/new"><?php echo h(t('superadmin.organizations.index.add')); ?></a>
  </div>
</div>
<?php if ($flashOk): ?>
  <div class="alert alert-success mt-3"><?php echo htmlspecialchars($flashOk); ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
  <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($flashErr); ?></div>
<?php endif; ?>
<div class="row" style="padding-top: 16px;">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title"><?php echo h(t('superadmin.organizations.index.list_title')); ?></h4>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <?php echo sortable_th(t('superadmin.organizations.index.col_id'), 'id', $sort, $dir, $sortPath, [], 'desc'); ?>
                <?php echo sortable_th(t('superadmin.organizations.index.col_code'), 'code', $sort, $dir, $sortPath); ?>
                <?php echo sortable_th(t('superadmin.organizations.index.col_name'), 'name', $sort, $dir, $sortPath); ?>
                <?php echo sortable_th(t('superadmin.organizations.index.col_nickname'), 'nickname', $sort, $dir, $sortPath); ?>
                <?php echo sortable_th(t('superadmin.organizations.index.col_created_by'), 'created_by', $sort, $dir, $sortPath); ?>
                <?php echo sortable_th(t('superadmin.organizations.index.col_created_at'), 'created_at', $sort, $dir, $sortPath, [], 'desc'); ?>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($organizations)): ?>
                <tr><td colspan="7" class="text-muted"><?php echo h(t('superadmin.organizations.index.none')); ?></td></tr>
              <?php else: ?>
                <?php foreach ($organizations as $o): ?>
                  <tr>
                    <td><?php echo (int) $o['id']; ?></td>
                    <td><span class="badge badge-primary"><?php echo htmlspecialchars((string) ($o['org_code'] ?? '—')); ?></span></td>
                    <td><?php echo htmlspecialchars((string) $o['name']); ?><?php if (isset($o['is_active']) && (int) $o['is_active'] === 0): ?> <span class="badge badge-danger"><?php echo h(t('superadmin.organizations.disabled_badge')); ?></span><?php endif; ?></td>
                    <td class="text-muted small"><?php echo htmlspecialchars((string) (($o['nickname'] ?? '') !== '' ? $o['nickname'] : '—')); ?></td>
                    <td><?php echo htmlspecialchars((string) ($o['created_by_name'] ?? '—')); ?></td>
                    <td><?php echo htmlspecialchars(format_pretty_date(isset($o['created_at']) ? (string) $o['created_at'] : null)); ?></td>
                    <td><a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($b); ?>/superadmin/organization?id=<?php echo (int) $o['id']; ?>"><?php echo h(t('superadmin.organizations.index.view')); ?></a></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
