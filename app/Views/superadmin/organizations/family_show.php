<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$fid = (int) $family['id'];
$oid = (int) $fromOrganizationId;
?>
<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center border-bottom flex-wrap">
    <div>
      <h3 class="mb-0"><?php echo h(t('superadmin.organizations.family.title')); ?> #<?php echo $fid; ?></h3>
      <p class="text-muted small mb-0">
        <?php echo htmlspecialchars((string) $organization['name']); ?> · <?php echo h(t('superadmin.organizations.family.view_only')); ?>
      </p>
    </div>
    <a class="btn btn-light mb-2" href="<?php echo htmlspecialchars($b); ?>/superadmin/organization?id=<?php echo $oid; ?>"><?php echo h(t('superadmin.organizations.family.back')); ?></a>
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
        <h4 class="card-title"><?php echo h(t('superadmin.organizations.family.members_title')); ?></h4>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th><?php echo h(t('superadmin.organizations.family.col_name')); ?></th>
                <th><?php echo h(t('superadmin.organizations.family.col_role')); ?></th>
                <th><?php echo h(t('superadmin.organizations.family.col_related_to')); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($members)): ?>
                <tr><td colspan="3" class="text-muted"><?php echo h(t('superadmin.organizations.family.none_members')); ?></td></tr>
              <?php else: ?>
                <?php foreach ($members as $m): ?>
                  <tr>
                    <td><?php echo htmlspecialchars((string) $m['user_name']); ?></td>
                    <td><?php echo htmlspecialchars((string) $m['role']); ?></td>
                    <td><?php echo $m['related_to_user_id'] ? htmlspecialchars((string) ($m['related_user_name'] ?? ('User #' . $m['related_to_user_id']))) : '—'; ?></td>
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
