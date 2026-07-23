<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$schemes = $schemes ?? [];
$eligibleSchemes = $eligibleSchemes ?? [];
$orgName = (string) (($current['name'] ?? '') ?: t('common.organization'));
?>
<div class="row">
  <div class="col-12 border-bottom">
    <h3 class="mb-0"><?php echo htmlspecialchars(t('schemes.title')); ?></h3>
    <p class="text-muted mb-0"><?php echo htmlspecialchars(t('schemes.desc')); ?></p>
  </div>
</div>
<?php if ($flashOk): ?>
  <div class="alert alert-success mt-3"><?php echo htmlspecialchars($flashOk); ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
  <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($flashErr); ?></div>
<?php endif; ?>

<div class="row" style="padding-top: 16px;">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <h4 class="card-title mb-0"><?php echo !empty($canManageOrg) ? htmlspecialchars(t('schemes.all')) : htmlspecialchars(t('schemes.my_benefits')); ?></h4>
          <?php if (!empty($canManageOrg)): ?>
            <a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars($b); ?>/organization/schemes/new"><?php echo htmlspecialchars(t('schemes.new')); ?></a>
          <?php endif; ?>
        </div>
        <?php if (!empty($canManageOrg)): ?>
          <?php if ($schemes === []): ?>
            <p class="text-muted mt-3 mb-0"><?php echo htmlspecialchars(t('schemes.none')); ?></p>
          <?php else: ?>
            <div class="table-responsive mt-3">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th><?php echo htmlspecialchars(t('common.name')); ?></th>
                    <th><?php echo htmlspecialchars(t('common.organization')); ?></th>
                    <th><?php echo htmlspecialchars(t('schemes.scope')); ?></th>
                    <th><?php echo htmlspecialchars(t('schemes.benefit')); ?></th>
                    <th><?php echo htmlspecialchars(t('schemes.assigned')); ?></th>
                    <th><?php echo htmlspecialchars(t('common.status')); ?></th>
                    <th><?php echo htmlspecialchars(t('common.actions')); ?></th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($schemes as $s): ?>
                  <tr>
                    <td>
                      <a href="<?php echo htmlspecialchars($b); ?>/organization/scheme?id=<?php echo (int) $s['id']; ?>">
                        <?php echo htmlspecialchars((string) $s['name']); ?>
                      </a>
                    </td>
                    <td><?php echo htmlspecialchars($orgName); ?></td>
                    <td><?php echo htmlspecialchars((string) $s['benefit_scope']); ?></td>
                    <td>
                      <?php echo htmlspecialchars((string) $s['benefit_type']); ?>
                      <?php if (!empty($s['benefit_value'])): ?>
                        — <?php echo htmlspecialchars((string) $s['benefit_value']); ?>
                      <?php endif; ?>
                    </td>
                    <td><?php echo (int) ($s['assignment_count'] ?? 0); ?></td>
                    <td>
                      <?php if ((int) ($s['is_active'] ?? 0) === 1): ?>
                        <span class="badge badge-success"><?php echo htmlspecialchars(t('common.active')); ?></span>
                      <?php else: ?>
                        <span class="badge badge-secondary"><?php echo htmlspecialchars(t('schemes.inactive')); ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                      <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($b); ?>/organization/schemes/edit?id=<?php echo (int) $s['id']; ?>"><?php echo htmlspecialchars(t('common.edit')); ?></a>
                      <?php
                        $whatsappShareMessage = scheme_whatsapp_share_message($s);
                        require BASE_PATH . '/app/Views/partials/whatsapp_share_button.php';
                      ?>
                      <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/schemes/delete" class="d-inline" onsubmit="return confirm(<?php echo json_encode(t('schemes.delete_confirm')); ?>);">
                        <input type="hidden" name="scheme_id" value="<?php echo (int) $s['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><?php echo htmlspecialchars(t('common.delete')); ?></button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <?php if ($eligibleSchemes === []): ?>
            <p class="text-muted mt-3 mb-0"><?php echo htmlspecialchars(t('schemes.no_eligible')); ?></p>
          <?php else: ?>
          <div class="table-responsive mt-3">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th><?php echo htmlspecialchars(t('common.name')); ?></th>
                  <th><?php echo htmlspecialchars(t('common.organization')); ?></th>
                  <th><?php echo htmlspecialchars(t('schemes.scope')); ?></th>
                  <th><?php echo htmlspecialchars(t('schemes.benefit')); ?></th>
                  <th><?php echo htmlspecialchars(t('common.status')); ?></th>
                  <th><?php echo htmlspecialchars(t('schemes.benefitted_at')); ?></th>
                  <th><?php echo htmlspecialchars(t('common.actions')); ?></th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($eligibleSchemes as $row): ?>
                <tr>
                  <td><?php echo htmlspecialchars((string) $row['name']); ?></td>
                  <td><?php echo htmlspecialchars($orgName); ?></td>
                  <td><?php echo htmlspecialchars((string) $row['benefit_scope']); ?></td>
                  <td>
                    <?php echo htmlspecialchars((string) $row['benefit_type']); ?>
                    <?php if (!empty($row['benefit_value'])): ?>
                      — <?php echo htmlspecialchars((string) $row['benefit_value']); ?>
                    <?php endif; ?>
                    <?php if ((string) $row['benefit_scope'] === 'family'): ?>
                      <span class="text-muted">(Family #<?php echo (int) ($row['family_id'] ?? 0); ?>)</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ((string) ($row['status'] ?? '') === 'claimed'): ?>
                      <span class="badge badge-success"><?php echo htmlspecialchars(t('schemes.benefitted')); ?></span>
                    <?php else: ?>
                      <span class="badge badge-warning"><?php echo htmlspecialchars(t('schemes.not_yet')); ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ((string) ($row['status'] ?? '') === 'claimed'): ?>
                      <?php echo htmlspecialchars(format_pretty_datetime(isset($row['claimed_at']) ? (string) $row['claimed_at'] : null)); ?>
                    <?php else: ?>
                      —
                    <?php endif; ?>
                  </td>
                  <td class="text-nowrap">
                    <?php
                      $whatsappShareMessage = scheme_eligible_whatsapp_share_message($row);
                      require BASE_PATH . '/app/Views/partials/whatsapp_share_button.php';
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

