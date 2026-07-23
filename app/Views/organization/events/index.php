<?php
$b = base_url();
$events = isset($events) && is_array($events) ? $events : [];
$schemes = isset($schemes) && is_array($schemes) ? $schemes : [];
$eligibleSchemes = isset($eligibleSchemes) && is_array($eligibleSchemes) ? $eligibleSchemes : [];
$canManageOrg = !empty($canManageOrg);
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$defaultFinancialYear = (string) ($defaultFinancialYear ?? date('Y'));
$defaultEventDate = (string) ($defaultEventDate ?? date('Y-m-d'));
$showCreateEvent = !empty($showCreateEvent);
$activeEventTab = (string) ($activeEventTab ?? 'events');
if (!in_array($activeEventTab, ['events', 'schemes'], true)) {
    $activeEventTab = 'events';
}
$eventsTabUrl = $b . '/organization/events?event_tab=events';
$schemesTabUrl = $b . '/organization/events?event_tab=schemes';
?>
<div class="row">
  <div class="col-12 border-bottom">
    <h3 class="mb-0"><?php echo htmlspecialchars(t('events.title')); ?></h3>
    <p class="text-muted small mb-0">
      <?php if ($canManageOrg): ?>
        <?php echo htmlspecialchars(t('events.admin_desc')); ?>
      <?php else: ?>
        <?php echo htmlspecialchars(t('events.member_desc')); ?>
      <?php endif; ?>
    </p>
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
    <ul class="nav nav-tabs mb-3" id="eventsPageTabs">
      <li class="nav-item">
        <a class="nav-link<?php echo $activeEventTab === 'events' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($eventsTabUrl); ?>"><?php echo h('events.tab_events'); ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link<?php echo $activeEventTab === 'schemes' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($schemesTabUrl); ?>"><?php echo h('events.tab_schemes'); ?></a>
      </li>
    </ul>

    <?php if ($activeEventTab === 'events'): ?>
    <div id="tab-events-list" class="events-page-panel">
      <?php if ($canManageOrg): ?>
        <div class="d-flex justify-content-end mb-3">
          <?php if ($showCreateEvent): ?>
            <a class="btn btn-outline-secondary btn-sm" href="<?php echo htmlspecialchars($eventsTabUrl); ?>"><?php echo htmlspecialchars(t('events.hide_create')); ?></a>
          <?php else: ?>
            <a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars($eventsTabUrl); ?>&create_event=1"><?php echo htmlspecialchars(t('events.add_event')); ?></a>
          <?php endif; ?>
        </div>
        <?php if ($showCreateEvent): ?>
          <div class="card mb-4">
            <div class="card-body">
              <h4 class="card-title"><?php echo h('events.create_title'); ?></h4>
              <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/dues">
                <input type="hidden" name="return_to" value="events">
                <div class="form-row">
                  <div class="form-group col-md-5">
                    <label for="due_title"><?php echo h('receipts.dues_title_label'); ?></label>
                    <input type="text" class="form-control" id="due_title" name="title" placeholder="<?php echo h('receipts.dues_title_placeholder'); ?>" required>
                  </div>
                  <div class="form-group col-md-3">
                    <label for="due_type"><?php echo h('receipts.dues_type_label'); ?></label>
                    <select class="form-control" id="due_type" name="due_type">
                      <option value="event"><?php echo h('receipts.dues_type_event'); ?></option>
                      <option value="occasion"><?php echo h('receipts.dues_type_occasion'); ?></option>
                    </select>
                  </div>
                  <div class="form-group col-md-2">
                    <label for="due_amount"><?php echo h('receipts.dues_amount_label'); ?></label>
                    <input type="number" step="0.01" min="0.01" class="form-control" id="due_amount" name="amount" required>
                  </div>
                  <div class="form-group col-md-2">
                    <label for="due_financial_year"><?php echo h('receipts.dues_fy_label'); ?></label>
                    <input type="text" class="form-control" id="due_financial_year" name="financial_year" value="<?php echo htmlspecialchars($defaultFinancialYear); ?>" required>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group col-md-4" id="due_charge_basis_wrap">
                    <label class="d-block"><?php echo h('receipts.dues_charge_label'); ?></label>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="charge_basis" id="charge_basis_family" value="per_family" checked>
                      <label class="form-check-label" for="charge_basis_family"><?php echo h('receipts.dues_charge_per_family'); ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="charge_basis" id="charge_basis_person" value="per_person">
                      <label class="form-check-label" for="charge_basis_person"><?php echo h('receipts.dues_charge_per_person'); ?></label>
                    </div>
                  </div>
                  <div class="form-group col-md-3" id="due_event_date_wrap">
                    <label for="due_event_date"><?php echo h('receipts.dues_event_date_label'); ?></label>
                    <input type="date" class="form-control" id="due_event_date" name="event_date" value="<?php echo htmlspecialchars($defaultEventDate); ?>">
                  </div>
                  <div class="form-group col-md-5">
                    <div class="form-check mt-4 pt-1">
                      <input type="checkbox" class="form-check-input" id="due_is_compulsory" name="is_compulsory" value="1">
                      <label class="form-check-label" for="due_is_compulsory"><?php echo h('receipts.dues_compulsory_label'); ?></label>
                      <small id="due_compulsory_hint" class="form-text text-muted d-block"><?php echo h('receipts.js_event_compulsory_hint'); ?></small>
                    </div>
                  </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><?php echo h('events.create_btn'); ?></button>
              </form>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <div class="card">
        <div class="card-body">
          <?php if ($events === []): ?>
            <p class="text-muted mb-0"><?php echo htmlspecialchars(t('events.none')); ?></p>
          <?php else: ?>
            <div class="family-member-cards d-lg-none">
              <?php foreach ($events as $row): ?>
                <?php
                  $ev = isset($row['event']) && is_array($row['event']) ? $row['event'] : [];
                  $evId = (int) ($ev['id'] ?? 0);
                  $title = (string) ($ev['title'] ?? 'Event');
                  $fy = (string) ($ev['financial_year'] ?? '');
                  $amount = (float) ($ev['amount'] ?? 0);
                  $eventDate = trim((string) ($ev['event_date'] ?? ''));
                  if ($eventDate === '' && !empty($ev['created_at'])) {
                      $eventDate = date('Y-m-d', strtotime((string) $ev['created_at']));
                  }
                  $basis = strtolower((string) ($ev['charge_basis'] ?? 'per_family'));
                  if ($basis === '' && strtolower((string) ($ev['due_type'] ?? '')) === 'event') {
                      $basis = 'per_person';
                  }
                  $dueType = strtolower((string) ($ev['due_type'] ?? 'event'));
                  $stats = isset($row['stats']) && is_array($row['stats']) ? $row['stats'] : null;
                  $passCount = (int) ($row['pass_count'] ?? 0);
                  $redeemedCount = (int) ($row['redeemed_count'] ?? 0);
                  $amountPaid = (float) ($row['amount_paid'] ?? 0);
                  $isCompulsory = !empty($ev['is_compulsory']);
                  $whatsappShareMessage = event_whatsapp_share_message($ev);
                  require BASE_PATH . '/app/Views/partials/event_list_mobile_card.php';
                ?>
              <?php endforeach; ?>
            </div>
            <div class="table-responsive d-none d-lg-block">
              <table class="table table-striped table-bordered events-schemes-table mb-0">
                <thead>
                  <tr>
                    <th><?php echo h('common.name'); ?></th>
                    <th><?php echo h('events.col_date'); ?></th>
                    <th><?php echo h('events.col_fy'); ?></th>
                    <th class="text-right"><?php echo h('events.col_amount'); ?></th>
                    <th><?php echo h('events.col_charge'); ?></th>
                    <th><?php echo h('events.col_compulsory'); ?></th>
                    <?php if ($canManageOrg): ?>
                      <th class="text-right"><?php echo h('events.col_active'); ?></th>
                      <th class="text-right"><?php echo h('events.col_redeemed'); ?></th>
                    <?php else: ?>
                      <th><?php echo h('events.col_passes'); ?></th>
                    <?php endif; ?>
                    <th><?php echo h('common.actions'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($events as $row): ?>
                    <?php
                      $ev = isset($row['event']) && is_array($row['event']) ? $row['event'] : [];
                      $evId = (int) ($ev['id'] ?? 0);
                      $title = (string) ($ev['title'] ?? 'Event');
                      $fy = (string) ($ev['financial_year'] ?? '');
                      $amount = (float) ($ev['amount'] ?? 0);
                      $eventDate = trim((string) ($ev['event_date'] ?? ''));
                      if ($eventDate === '' && !empty($ev['created_at'])) {
                          $eventDate = date('Y-m-d', strtotime((string) $ev['created_at']));
                      }
                      $basis = strtolower((string) ($ev['charge_basis'] ?? 'per_family'));
                      if ($basis === '' && strtolower((string) ($ev['due_type'] ?? '')) === 'event') {
                          $basis = 'per_person';
                      }
                      $dueType = strtolower((string) ($ev['due_type'] ?? 'event'));
                      $stats = isset($row['stats']) && is_array($row['stats']) ? $row['stats'] : null;
                      $passCount = (int) ($row['pass_count'] ?? 0);
                      $redeemedCount = (int) ($row['redeemed_count'] ?? 0);
                      $amountPaid = (float) ($row['amount_paid'] ?? 0);
                      $isCompulsory = !empty($ev['is_compulsory']);
                    ?>
                    <tr>
                      <td>
                        <a href="<?php echo htmlspecialchars($b); ?>/organization/event?id=<?php echo $evId; ?>">
                          <?php echo htmlspecialchars($title); ?>
                        </a>
                        <?php if ($dueType === 'occasion'): ?>
                          <span class="badge badge-light ml-1"><?php echo h('receipts.dues_type_occasion'); ?></span>
                        <?php endif; ?>
                      </td>
                      <td><?php echo $eventDate !== '' ? htmlspecialchars(format_pretty_date($eventDate)) : '—'; ?></td>
                      <td><?php echo htmlspecialchars($fy); ?></td>
                      <td class="text-right"><?php echo htmlspecialchars(number_format($amount, 2)); ?></td>
                      <td><?php echo $basis === 'per_person' ? h('events.per_person') : h('events.per_family'); ?></td>
                      <td>
                        <?php if ($isCompulsory): ?>
                          <span class="badge badge-warning"><?php echo h('events.compulsory'); ?></span>
                        <?php else: ?>
                          <span class="badge badge-light"><?php echo h('events.optional'); ?></span>
                        <?php endif; ?>
                      </td>
                      <?php if ($canManageOrg): ?>
                        <td class="text-right"><?php echo $stats !== null ? (int) ($stats['active'] ?? 0) : 0; ?></td>
                        <td class="text-right"><?php echo $stats !== null ? (int) ($stats['redeemed'] ?? 0) : 0; ?></td>
                      <?php else: ?>
                        <td>
                          <?php if ($passCount > 0 || $redeemedCount > 0): ?>
                            <span class="badge badge-success"><?php echo $passCount; ?> <?php echo h('common.active'); ?></span>
                            <?php if ($redeemedCount > 0): ?>
                              <span class="badge badge-danger"><?php echo $redeemedCount; ?> <?php echo h('common.redeemed'); ?></span>
                            <?php endif; ?>
                          <?php elseif ($amountPaid > 0): ?>
                            <span class="badge badge-warning"><?php echo h('events.payment_recorded'); ?></span>
                          <?php else: ?>
                            <span class="text-muted"><?php echo h('events.no_passes'); ?></span>
                          <?php endif; ?>
                        </td>
                      <?php endif; ?>
                      <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($b); ?>/organization/event?id=<?php echo $evId; ?>">
                          <?php echo $canManageOrg ? h('events.redeem_passes') : h('events.col_open'); ?>
                        </a>
                        <?php
                          $whatsappShareMessage = event_whatsapp_share_message($ev);
                          require BASE_PATH . '/app/Views/partials/whatsapp_share_button.php';
                        ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div id="tab-schemes-list" class="events-page-panel">
      <?php if ($canManageOrg): ?>
        <div class="d-flex justify-content-end mb-3">
          <a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars($b); ?>/organization/schemes/new"><?php echo htmlspecialchars(t('schemes.new')); ?></a>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-body">
          <p class="text-muted small mb-3"><?php echo htmlspecialchars(t('schemes.desc')); ?></p>
          <?php if (!empty($canManageOrg)): ?>
            <?php if ($schemes === []): ?>
              <p class="text-muted mb-0"><?php echo htmlspecialchars(t('schemes.none')); ?></p>
            <?php else: ?>
              <div class="family-member-cards d-lg-none">
                <?php foreach ($schemes as $s): ?>
                  <?php require BASE_PATH . '/app/Views/partials/scheme_admin_list_mobile_card.php'; ?>
                <?php endforeach; ?>
              </div>
              <div class="table-responsive d-none d-lg-block">
                <table class="table table-striped table-bordered events-schemes-table mb-0">
                  <thead>
                    <tr>
                      <th><?php echo htmlspecialchars(t('common.name')); ?></th>
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
              <p class="text-muted mb-0"><?php echo htmlspecialchars(t('schemes.no_eligible')); ?></p>
            <?php else: ?>
            <div class="family-member-cards d-lg-none">
              <?php foreach ($eligibleSchemes as $row): ?>
                <?php require BASE_PATH . '/app/Views/partials/scheme_eligible_list_mobile_card.php'; ?>
              <?php endforeach; ?>
            </div>
            <div class="table-responsive d-none d-lg-block">
              <table class="table table-striped table-bordered events-schemes-table mb-0">
                <thead>
                  <tr>
                    <th><?php echo htmlspecialchars(t('common.name')); ?></th>
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
                    <td><?php echo htmlspecialchars((string) $row['benefit_scope']); ?></td>
                    <td>
                      <?php echo htmlspecialchars((string) $row['benefit_type']); ?>
                      <?php if (!empty($row['benefit_value'])): ?>
                        — <?php echo htmlspecialchars((string) $row['benefit_value']); ?>
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
    <?php endif; ?>
  </div>
</div>

<?php if ($canManageOrg && $showCreateEvent): ?>
<script>
(function () {
  var dueTypeSelect = document.getElementById('due_type');
  var compulsoryHint = document.getElementById('due_compulsory_hint');
  if (!dueTypeSelect) return;
  function syncHints() {
    if (compulsoryHint) {
      compulsoryHint.textContent = dueTypeSelect.value === 'event'
        ? <?php echo json_encode(t('receipts.js_event_compulsory_hint')); ?>
        : <?php echo json_encode(t('receipts.js_compulsory_generic_hint')); ?>;
    }
  }
  dueTypeSelect.addEventListener('change', syncHints);
  syncHints();
})();
</script>
<?php endif; ?>

<style>
.events-schemes-table th,
.events-schemes-table td {
  vertical-align: middle;
}
.events-schemes-table thead th {
  white-space: nowrap;
}
</style>
