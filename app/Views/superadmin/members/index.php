<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$selectedOrganizationId = (int) ($selectedOrganizationId ?? 0);
$selectedRoleFilter = ($selectedRoleFilter ?? 'all') === 'admin' || ($selectedRoleFilter ?? '') === 'member'
  ? (string) $selectedRoleFilter
  : 'all';
$hasActiveFilters = $selectedOrganizationId > 0 || $selectedRoleFilter !== 'all';
$members = isset($members) && is_array($members) ? $members : [];
$sort = (string) ($sort ?? 'name');
$dir = (string) ($dir ?? 'asc');
$sortPath = '/superadmin/members';
$sortPreserve = [];
if ($selectedOrganizationId > 0) {
    $sortPreserve['organization_id'] = $selectedOrganizationId;
}
if ($selectedRoleFilter !== 'all') {
    $sortPreserve['role'] = $selectedRoleFilter;
}
?>
<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center flex-wrap border-bottom pb-3 mb-1 members-page-header">
    <h3 class="mb-0"><?php echo h(t('superadmin.members.index.title')); ?></h3>
    <a class="btn btn-primary btn-sm mt-2 mt-md-0" href="<?php echo htmlspecialchars($b); ?>/superadmin/admins/new"><?php echo h(t('superadmin.admins.index.add')); ?></a>
  </div>
</div>

<?php if ($flashOk): ?>
  <div class="alert alert-success mt-3"><?php echo htmlspecialchars($flashOk); ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
  <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($flashErr); ?></div>
<?php endif; ?>

<div class="row pt-2">
  <div class="col-12">
    <div class="card">
      <div class="card-body pb-0">
        <form method="get" action="<?php echo htmlspecialchars($b); ?>/superadmin/members" class="members-filter-toolbar sa-users-filter-toolbar" id="sa_users_filter_form">
          <?php if ($sort !== 'name' || $dir !== 'asc'): ?>
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
            <input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir); ?>">
          <?php endif; ?>
          <div class="members-filter-field sa-users-filter-field--org">
            <label for="organization_id"><?php echo h(t('superadmin.members.index.organization_label')); ?></label>
            <select id="organization_id" name="organization_id" class="form-control form-control-sm members-filter-select">
              <option value="0"<?php echo $selectedOrganizationId === 0 ? ' selected' : ''; ?>><?php echo h(t('superadmin.members.index.all_organizations')); ?></option>
              <?php foreach (($organizations ?? []) as $o): ?>
                <option value="<?php echo (int) $o['id']; ?>"<?php echo $selectedOrganizationId === (int) $o['id'] ? ' selected' : ''; ?>>
                  <?php echo htmlspecialchars((string) $o['name']); ?><?php if (!empty($o['org_code'])): ?> (<?php echo htmlspecialchars((string) $o['org_code']); ?>)<?php endif; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="members-filter-field sa-users-filter-field--role">
            <label for="sa_role_filter"><?php echo h(t('superadmin.members.index.role_label')); ?></label>
            <select id="sa_role_filter" name="role" class="form-control form-control-sm members-filter-select" data-no-search-dropdown>
              <option value="all"<?php echo $selectedRoleFilter === 'all' ? ' selected' : ''; ?>><?php echo h(t('superadmin.members.index.role_all')); ?></option>
              <option value="admin"<?php echo $selectedRoleFilter === 'admin' ? ' selected' : ''; ?>><?php echo h(t('superadmin.members.index.role_admins')); ?></option>
              <option value="member"<?php echo $selectedRoleFilter === 'member' ? ' selected' : ''; ?>><?php echo h(t('superadmin.members.index.role_members')); ?></option>
            </select>
          </div>
          <div class="members-filter-actions">
            <button type="submit" class="btn btn-sm btn-outline-primary members-filter-apply"><?php echo h(t('superadmin.members.index.apply')); ?></button>
            <?php if ($hasActiveFilters): ?>
              <a href="<?php echo htmlspecialchars($b); ?>/superadmin/members" class="btn btn-sm btn-outline-secondary members-filter-reset"><?php echo h(t('common.reset')); ?></a>
            <?php endif; ?>
          </div>
        </form>
      </div>
      <div class="card-body pt-2">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle">
            <thead>
              <tr>
                <?php echo sortable_th(t('superadmin.members.index.col_id'), 'id', $sort, $dir, $sortPath, $sortPreserve, 'desc'); ?>
                <?php echo sortable_th(t('superadmin.members.index.col_name'), 'name', $sort, $dir, $sortPath, $sortPreserve); ?>
                <?php echo sortable_th(t('superadmin.members.index.col_email'), 'email', $sort, $dir, $sortPath, $sortPreserve); ?>
                <?php echo sortable_th(t('superadmin.members.index.col_phone'), 'phone', $sort, $dir, $sortPath, $sortPreserve); ?>
                <?php echo sortable_th(t('superadmin.members.index.col_orgs'), 'orgs', $sort, $dir, $sortPath, $sortPreserve); ?>
                <?php echo sortable_th(t('superadmin.members.index.col_type'), 'type', $sort, $dir, $sortPath, $sortPreserve); ?>
                <?php echo sortable_th(t('superadmin.members.index.col_since'), 'since', $sort, $dir, $sortPath, $sortPreserve, 'desc'); ?>
              </tr>
            </thead>
            <tbody>
              <?php if ($members === []): ?>
                <tr>
                  <td colspan="7" class="text-muted">
                    <?php echo h($hasActiveFilters ? t('superadmin.members.index.none_filtered') : t('superadmin.members.index.none')); ?>
                    <?php if (!$hasActiveFilters): ?>
                      <a href="<?php echo htmlspecialchars($b); ?>/superadmin/admins/new"><?php echo h(t('superadmin.admins.index.add_first')); ?></a>.
                    <?php endif; ?>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($members as $m): ?>
                  <?php $isAdmin = ($m['role'] ?? '') === 'admin'; ?>
                  <tr>
                    <td><?php echo (int) ($m['id'] ?? 0); ?></td>
                    <td><?php echo htmlspecialchars((string) ($m['name'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars((string) (($m['email'] ?? '') ?: '—')); ?></td>
                    <td><?php echo htmlspecialchars(\format_india_phone($m['phone'] ?? null) ?: '—'); ?></td>
                    <td><?php echo htmlspecialchars((string) (($m['organization_name'] ?? '') ?: '—')); ?></td>
                    <td>
                      <?php if ($isAdmin): ?>
                        <span class="badge sa-user-role-badge sa-user-role-badge--admin"><?php echo h(t('superadmin.members.index.badge_org_admin')); ?></span>
                      <?php else: ?>
                        <span class="badge sa-user-role-badge sa-user-role-badge--member"><?php echo h(t('superadmin.members.index.badge_member')); ?></span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars(format_pretty_date(isset($m['created_at']) ? (string) $m['created_at'] : null)); ?></td>
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
