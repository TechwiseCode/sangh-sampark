<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$events = isset($events) && is_array($events) ? $events : [];
$fid = (int) ($family['id'] ?? 0);
$familyPageTitle = (string) ($familyPageTitle ?? t('dashboard.my_family'));
?>
<div class="row">
  <div class="col-12 border-bottom pb-2 mb-1">
    <h3 class="mb-0"><?php echo htmlspecialchars($familyPageTitle); ?></h3>
    <p class="text-muted small mb-0"><?php echo htmlspecialchars(t('family.history.subtitle')); ?></p>
    <?php $familyTab = 'history'; require BASE_PATH . '/app/Views/partials/family_page_tabs.php'; ?>
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
        <?php if ($events === []): ?>
          <p class="text-muted mb-0"><?php echo htmlspecialchars(t('family.history.none')); ?></p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-sm">
              <thead>
                <tr>
                  <th><?php echo htmlspecialchars(t('family.history.col_date')); ?></th>
                  <th><?php echo htmlspecialchars(t('family.history.col_event')); ?></th>
                  <th><?php echo htmlspecialchars(t('family.history.col_member')); ?></th>
                  <th><?php echo htmlspecialchars(t('family.history.col_actor')); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($events as $e): ?>
                  <tr>
                    <td><?php echo htmlspecialchars(format_pretty_date(isset($e['created_at']) ? (string) $e['created_at'] : null)); ?></td>
                    <td><?php echo htmlspecialchars((string) ($e['event_label'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars((string) (($e['user_name'] ?? '') ?: '—')); ?></td>
                    <td><?php echo htmlspecialchars((string) (($e['actor_name'] ?? '') ?: t('family.history.system_actor'))); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

