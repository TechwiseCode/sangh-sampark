<?php
$b = base_url();
$emailMemberships = isset($emailMemberships) && is_array($emailMemberships) ? $emailMemberships : [];
$currentOrgId = (int) ($orgId ?? current_organization_id() ?? 0);
$currentUserId = (int) (($profileUser['id'] ?? current_user()['id'] ?? 0));
?>
<div class="card profile-memberships-card">
  <div class="card-body">
    <h4 class="card-title"><?php echo htmlspecialchars(t('profile.membership_codes')); ?></h4>
    <p class="text-muted small mb-3"><?php echo htmlspecialchars(t('profile.memberships_help')); ?></p>
    <?php if ($emailMemberships === []): ?>
      <p class="text-muted mb-0"><?php echo htmlspecialchars(t('profile.no_memberships')); ?></p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
          <thead>
            <tr>
              <th><?php echo htmlspecialchars(t('common.organization')); ?></th>
              <th><?php echo htmlspecialchars(t('common.code')); ?></th>
              <th><?php echo htmlspecialchars(t('profile.membership_code')); ?></th>
              <th><?php echo htmlspecialchars(t('profile.role')); ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($emailMemberships as $m): ?>
              <?php
              $mid = (int) ($m['user_id'] ?? 0);
              $moid = (int) ($m['organization_id'] ?? ($m['id'] ?? 0));
              $isCurrent = $moid === $currentOrgId && $mid === $currentUserId;
              $roleKey = strtolower((string) ($m['membership_role'] ?? 'member')) === 'admin'
                ? 'profile.role_admin'
                : 'profile.role_member';
              ?>
              <tr class="<?php echo $isCurrent ? 'table-active' : ''; ?>">
                <td>
                  <?php echo htmlspecialchars((string) ($m['name'] ?? '')); ?>
                  <?php if ($isCurrent): ?>
                    <span class="badge badge-success ml-1"><?php echo h(t('profile.membership_current')); ?></span>
                  <?php endif; ?>
                </td>
                <td><span class="badge badge-light"><?php echo htmlspecialchars((string) (($m['org_code'] ?? '') ?: '—')); ?></span></td>
                <td><?php echo htmlspecialchars((string) (($m['full_member_code'] ?? '') ?: '—')); ?></td>
                <td><?php echo htmlspecialchars(t($roleKey)); ?></td>
                <td class="text-right text-nowrap">
                  <?php if (!$isCurrent && $mid > 0 && $moid > 0): ?>
                    <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/switch-org" class="d-inline">
                      <input type="hidden" name="user_id" value="<?php echo $mid; ?>">
                      <input type="hidden" name="organization_id" value="<?php echo $moid; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-primary"><?php echo h(t('profile.switch_org')); ?></button>
                    </form>
                  <?php elseif ($isCurrent): ?>
                    <span class="text-muted small"><?php echo h(t('profile.membership_active')); ?></span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
