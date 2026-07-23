<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$heads = isset($heads) && is_array($heads) ? $heads : [];
$receipts = isset($receipts) && is_array($receipts) ? $receipts : [];
$financialYears = isset($financialYears) && is_array($financialYears) ? $financialYears : [];
$selectedFinancialYear = (string) ($selectedFinancialYear ?? '');
$defaultReceiptDate = (string) ($defaultReceiptDate ?? date('Y-m-d'));
$receiptFilters = isset($receiptFilters) && is_array($receiptFilters) ? $receiptFilters : [];
$filterQ = (string) ($receiptFilters['q'] ?? '');
$filterDateFrom = (string) ($receiptFilters['date_from'] ?? '');
$filterDateTo = (string) ($receiptFilters['date_to'] ?? '');
$filterListDueDefinitionId = (int) ($receiptFilters['list_due_definition_id'] ?? 0);
$eventOccasionDefinitions = isset($eventOccasionDefinitions) && is_array($eventOccasionDefinitions) ? $eventOccasionDefinitions : [];
$dueDefinitions = isset($dueDefinitions) && is_array($dueDefinitions) ? $dueDefinitions : [];
$selectedDueDefinitionId = (int) ($selectedDueDefinitionId ?? 0);
$dueStatuses = isset($dueStatuses) && is_array($dueStatuses) ? $dueStatuses : [];
$selectedDueDefinition = isset($selectedDueDefinition) && is_array($selectedDueDefinition) ? $selectedDueDefinition : null;
$dueTrackerSummary = isset($dueTrackerSummary) && is_array($dueTrackerSummary) ? $dueTrackerSummary : null;
$trackerFilter = (string) ($trackerFilter ?? 'pending');
$isCompulsoryDue = $selectedDueDefinition !== null && !empty($selectedDueDefinition['is_compulsory']);
$isPerMemberDue = !empty($isPerMemberDue);
$perMemberRate = (float) ($perMemberRate ?? 0);
$isEventDue = !empty($isEventDue);
$activeReceiptTab = 'new';
if (isset($_GET['receipt_tab']) && in_array((string) $_GET['receipt_tab'], ['new', 'dues', 'list'], true)) {
    $activeReceiptTab = (string) $_GET['receipt_tab'];
} elseif (array_key_exists('tracker_filter', $_GET) || array_key_exists('due_definition_id', $_GET)) {
    $activeReceiptTab = 'dues';
} elseif ($filterListDueDefinitionId > 0 || $filterQ !== '' || $filterDateFrom !== '' || $filterDateTo !== '') {
    $activeReceiptTab = 'list';
}
$duesTabQuerySuffix = '&receipt_tab=dues';
?>
<div class="row">
  <div class="col-12 border-bottom">
    <h3 class="mb-0"><?php echo h('receipts.title'); ?></h3>
    <p class="text-muted small mb-0"><?php echo h('receipts.subtitle'); ?></p>
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
    <ul class="nav nav-tabs mb-3" id="receiptsTabs">
      <li class="nav-item">
        <a class="nav-link<?php echo $activeReceiptTab === 'new' ? ' active' : ''; ?>" href="#" data-tab-target="tab-new-receipt"><?php echo h('receipts.tab_new'); ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link<?php echo $activeReceiptTab === 'dues' ? ' active' : ''; ?>" href="#" data-tab-target="tab-dues"><?php echo h('receipts.tab_dues_tracker'); ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link<?php echo $activeReceiptTab === 'list' ? ' active' : ''; ?>" href="#" data-tab-target="tab-receipt-list"><?php echo h('receipts.tab_list'); ?></a>
      </li>
    </ul>

    <div id="tab-new-receipt" class="receipt-tab-panel<?php echo $activeReceiptTab !== 'new' ? ' d-none' : ''; ?>">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title"><?php echo h('receipts.new_title'); ?></h4>
          <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/receipts">
            <div class="form-row">
              <div class="form-group col-md-3">
                <label for="family_id"><?php echo h('receipts.recipient_label'); ?></label>
                <select class="form-control" id="family_id" name="family_id" required>
                  <option value=""><?php echo h('receipts.recipient_placeholder'); ?></option>
                  <?php foreach ($heads as $h): ?>
                    <option value="<?php echo (int) ($h['id'] ?? 0); ?>" data-user-id="<?php echo (int) ($h['head_user_id'] ?? 0); ?>" data-member-count="<?php echo (int) ($h['member_count'] ?? 1); ?>">
                      <?php echo htmlspecialchars((string) ($h['head_name'] ?? '')); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <input type="hidden" id="recipient_user_id" name="recipient_user_id" value="">
              </div>
              <div class="form-group col-md-3">
                <label for="new_receipt_due_definition_id"><?php echo h('receipts.due_label'); ?></label>
                <select class="form-control" id="new_receipt_due_definition_id" name="due_definition_id">
                  <option value=""><?php echo h('receipts.due_placeholder'); ?></option>
                  <?php foreach ($dueDefinitions as $dd): ?>
                    <?php
                      $ddBasis = strtolower((string) ($dd['charge_basis'] ?? ''));
                      if ($ddBasis === '' && strtolower((string) ($dd['due_type'] ?? '')) === 'membership') {
                        $ddBasis = 'per_person';
                      }
                      if ($ddBasis === '' && strtolower((string) ($dd['due_type'] ?? '')) === 'event') {
                        $ddBasis = 'per_person';
                      }
                      if ($ddBasis === '') {
                        $ddBasis = 'per_family';
                      }
                      $ddType = strtolower((string) ($dd['due_type'] ?? ''));
                      $showsQuantity = $ddType === 'event' || $ddBasis === 'per_person';
                      $quantityMode = $ddType === 'event' ? 'passes' : 'people';
                    ?>
                    <option value="<?php echo (int) ($dd['id'] ?? 0); ?>"
                      data-title="<?php echo htmlspecialchars((string) ($dd['title'] ?? '')); ?>"
                      data-amount="<?php echo htmlspecialchars((string) ($dd['amount'] ?? '0')); ?>"
                      data-charge-basis="<?php echo htmlspecialchars($ddBasis); ?>"
                      data-due-type="<?php echo htmlspecialchars($ddType); ?>"
                      data-shows-quantity="<?php echo $showsQuantity ? '1' : '0'; ?>"
                      data-quantity-mode="<?php echo htmlspecialchars($quantityMode); ?>">
                      <?php echo htmlspecialchars((string) ($dd['title'] ?? '')); ?>
                      <?php if ($ddType === 'event'): ?> (<?php echo h('receipts.due_option_event_suffix'); ?>)<?php endif; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group col-md-2 d-none" id="pass_count_wrap">
                <label for="pass_count" id="quantity_count_label"><?php echo h('receipts.pass_count_label'); ?></label>
                <input type="number" step="1" min="1" class="form-control" id="pass_count" name="pass_count" value="1">
                <small id="pass_rate_hint" class="text-muted"></small>
              </div>
              <div class="form-group col-md-2">
                <label for="amount"><?php echo h('receipts.amount_label'); ?></label>
                <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" required>
                <small id="amount_quantity_hint" class="text-muted d-none"></small>
              </div>
              <div class="form-group col-md-2">
                <label for="receipt_date"><?php echo h('receipts.date_label'); ?></label>
                <input type="date" class="form-control" id="receipt_date" name="receipt_date" value="<?php echo htmlspecialchars($defaultReceiptDate); ?>" required>
                <small id="receipt_fy_hint" class="text-muted"></small>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="purpose"><?php echo h('receipts.purpose_custom_label'); ?></label>
                <input type="text" class="form-control" id="purpose" name="purpose" placeholder="<?php echo h('receipts.purpose_custom_placeholder'); ?>">
              </div>
              <div class="form-group col-md-8">
                <label for="description"><?php echo h('receipts.description_label'); ?></label>
                <input type="text" class="form-control" id="description" name="description" placeholder="<?php echo h('receipts.description_placeholder'); ?>">
              </div>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo h('receipts.create_btn'); ?></button>
          </form>
        </div>
      </div>
    </div>

    <div id="tab-dues" class="receipt-tab-panel<?php echo $activeReceiptTab !== 'dues' ? ' d-none' : ''; ?>">
      <div class="row">
        <div class="col-lg-4">
          <div class="card mb-3">
            <div class="card-body">
              <h4 class="card-title"><?php echo h('receipts.dues_create_title'); ?></h4>
              <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/dues">
                <div class="form-group">
                  <label for="due_title"><?php echo h('receipts.dues_title_label'); ?></label>
                  <input type="text" class="form-control" id="due_title" name="title" placeholder="<?php echo h('receipts.dues_title_placeholder'); ?>" required>
                </div>
                <div class="form-group">
                  <label for="due_type"><?php echo h('receipts.dues_type_label'); ?></label>
                  <select class="form-control" id="due_type" name="due_type">
                    <option value="membership"><?php echo h('receipts.dues_type_membership'); ?></option>
                    <option value="other"><?php echo h('receipts.dues_type_other'); ?></option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="due_amount" id="due_amount_label"><?php echo h('receipts.dues_amount_label'); ?></label>
                  <input type="number" step="0.01" min="0.01" class="form-control" id="due_amount" name="amount" required>
                  <small id="due_amount_hint" class="form-text text-muted"><?php echo h('receipts.dues_amount_hint'); ?></small>
                </div>
                <div class="form-group" id="due_charge_basis_wrap">
                  <label class="d-block"><?php echo h('receipts.dues_charge_label'); ?></label>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="charge_basis" id="charge_basis_family" value="per_family" checked>
                    <label class="form-check-label" for="charge_basis_family"><?php echo h('receipts.dues_charge_per_family'); ?></label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="charge_basis" id="charge_basis_person" value="per_person">
                    <label class="form-check-label" for="charge_basis_person"><?php echo h('receipts.dues_charge_per_person'); ?></label>
                  </div>
                  <small id="due_charge_basis_hint" class="form-text text-muted d-block"><?php echo h('receipts.dues_charge_basis_hint'); ?></small>
                </div>
                <div class="form-group" id="due_event_date_wrap" style="display:none;">
                  <label for="due_event_date"><?php echo h('receipts.dues_event_date_label'); ?></label>
                  <input type="date" class="form-control" id="due_event_date" name="event_date" value="<?php echo htmlspecialchars($defaultReceiptDate); ?>">
                  <small class="form-text text-muted"><?php echo h('receipts.dues_event_date_hint'); ?></small>
                </div>
                <div class="form-group">
                  <label for="due_financial_year"><?php echo h('receipts.dues_fy_label'); ?></label>
                  <input type="text" class="form-control" id="due_financial_year" name="financial_year" value="<?php echo htmlspecialchars($selectedFinancialYear); ?>" required>
                </div>
                <div class="form-group form-check">
                  <input type="checkbox" class="form-check-input" id="due_is_compulsory" name="is_compulsory" value="1" checked>
                  <label class="form-check-label" for="due_is_compulsory"><?php echo h('receipts.dues_compulsory_label'); ?></label>
                  <small id="due_compulsory_hint" class="form-text text-muted d-block"><?php echo h('receipts.dues_compulsory_hint'); ?></small>
                </div>
                <button type="submit" class="btn btn-outline-primary btn-sm btn-block"><?php echo h('receipts.dues_create_btn'); ?></button>
              </form>
            </div>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h4 class="card-title mb-0"><?php echo h('receipts.tracker_title'); ?></h4>
              </div>
              <form method="get" class="form-inline mb-2">
                <input type="hidden" name="receipt_tab" value="dues">
                <input type="hidden" name="financial_year" value="<?php echo htmlspecialchars($selectedFinancialYear); ?>">
                <label class="mr-2 small mb-0" for="tracker_due_definition_id"><?php echo h('receipts.tracker_charge_label'); ?></label>
                <select class="form-control form-control-sm mr-2" id="tracker_due_definition_id" name="due_definition_id">
                  <option value=""><?php echo h('receipts.tracker_charge_placeholder'); ?></option>
                  <?php foreach ($dueDefinitions as $dd): ?>
                    <?php $ddId = (int) ($dd['id'] ?? 0); ?>
                    <option value="<?php echo $ddId; ?>"<?php echo $selectedDueDefinitionId === $ddId ? ' selected' : ''; ?>>
                      <?php echo htmlspecialchars((string) ($dd['title'] ?? '')); ?>
                      <?php
                        $ddType = strtolower((string) ($dd['due_type'] ?? ''));
                        if ($ddType === 'event') {
                          echo ' (event)';
                        }
                        echo !empty($dd['is_compulsory']) ? ' (compulsory)' : ' (optional)';
                      ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-secondary"><?php echo h('receipts.tracker_view_btn'); ?></button>
              </form>
              <?php if ($dueTrackerSummary !== null && (int) ($dueTrackerSummary['total'] ?? 0) > 0): ?>
                <?php
                $pendingCount = (int) ($dueTrackerSummary['unpaid'] ?? 0) + (int) ($dueTrackerSummary['partial'] ?? 0);
                $paidCount = (int) ($dueTrackerSummary['paid'] ?? 0);
                $totalCount = (int) ($dueTrackerSummary['total'] ?? 0);
                ?>
                <div class="alert <?php echo $isCompulsoryDue && $pendingCount > 0 ? 'alert-warning' : 'alert-light'; ?> py-2 mb-2">
                  <?php if ($isCompulsoryDue && $isPerMemberDue): ?>
                    <strong><?php echo h('receipts.tracker_membership_prefix'); ?></strong> <?php echo htmlspecialchars(number_format($perMemberRate, 2)); ?> <?php echo h('receipts.tracker_membership_rate_suffix'); ?>
                    <?php echo $paidCount; ?> <?php echo h('receipts.tracker_of'); ?> <?php echo $totalCount; ?> <?php echo h('receipts.tracker_heads_fully_paid'); ?>
                    <?php if ($pendingCount > 0): ?>
                      <span class="text-danger"><?php echo $pendingCount; ?> <?php echo h('receipts.tracker_pending_suffix'); ?></span>
                    <?php else: ?>
                      <span class="text-success"><?php echo h('receipts.tracker_all_paid'); ?></span>
                    <?php endif; ?>
                  <?php elseif ($isCompulsoryDue): ?>
                    <strong><?php echo h('receipts.tracker_compulsory_prefix'); ?></strong> <?php echo $paidCount; ?> <?php echo h('receipts.tracker_of'); ?> <?php echo $totalCount; ?> <?php echo h('receipts.tracker_heads_have_paid'); ?>
                    <?php if ($pendingCount > 0): ?>
                      <span class="text-danger"><?php echo $pendingCount; ?> <?php echo h('receipts.tracker_pending_suffix'); ?></span>
                    <?php else: ?>
                      <span class="text-success"><?php echo h('receipts.tracker_all_paid'); ?></span>
                    <?php endif; ?>
                  <?php elseif ($isEventDue): ?>
                    <strong><?php echo h('receipts.tracker_event_prefix'); ?></strong> <?php echo $paidCount; ?> <?php echo htmlspecialchars($paidCount === 1 ? t('receipts.tracker_family_singular') : t('receipts.tracker_family_plural')); ?> <?php echo h('receipts.tracker_paid_suffix'); ?>
                    <a href="<?php echo htmlspecialchars($b); ?>/organization/event?id=<?php echo (int) $selectedDueDefinitionId; ?>" class="alert-link"><?php echo h('receipts.tracker_event_view_passes'); ?></a>.
                  <?php else: ?>
                    <strong><?php echo h('receipts.tracker_optional_prefix'); ?></strong> <?php echo $totalCount; ?> <?php echo htmlspecialchars($totalCount === 1 ? t('receipts.tracker_family_singular') : t('receipts.tracker_family_plural')); ?> <?php echo h('receipts.tracker_paid_so_far_suffix'); ?>
                  <?php endif; ?>
                </div>
                <div class="btn-group btn-group-sm mb-2" role="group">
                <a class="btn btn-outline-secondary<?php echo $trackerFilter === 'pending' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($b); ?>/organization/receipts?financial_year=<?php echo urlencode($selectedFinancialYear); ?>&due_definition_id=<?php echo (int) $selectedDueDefinitionId; ?>&tracker_filter=pending<?php echo $duesTabQuerySuffix; ?>"><?php echo h('receipts.tracker_filter_pending'); ?></a>
                  <a class="btn btn-outline-secondary<?php echo $trackerFilter === 'all' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($b); ?>/organization/receipts?financial_year=<?php echo urlencode($selectedFinancialYear); ?>&due_definition_id=<?php echo (int) $selectedDueDefinitionId; ?>&tracker_filter=all<?php echo $duesTabQuerySuffix; ?>"><?php echo h('receipts.tracker_filter_all'); ?></a>
                  <a class="btn btn-outline-secondary<?php echo $trackerFilter === 'paid' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($b); ?>/organization/receipts?financial_year=<?php echo urlencode($selectedFinancialYear); ?>&due_definition_id=<?php echo (int) $selectedDueDefinitionId; ?>&tracker_filter=paid<?php echo $duesTabQuerySuffix; ?>"><?php echo h('receipts.tracker_filter_paid'); ?></a>
                </div>
              <?php elseif ($selectedDueDefinitionId > 0 && $isEventDue && !$isCompulsoryDue): ?>
                <p class="text-muted small mb-2"><?php echo h('receipts.tracker_optional_event_hint'); ?></p>
              <?php elseif ($selectedDueDefinitionId > 0 && !$isCompulsoryDue): ?>
                <p class="text-muted small mb-2"><?php echo h('receipts.tracker_optional_due_hint'); ?></p>
              <?php elseif ($selectedDueDefinitionId > 0 && $isEventDue): ?>
                <p class="text-muted small mb-2"><?php echo h('receipts.tracker_event_pass_hint'); ?></p>
              <?php endif; ?>
              <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                  <thead>
                    <tr>
                      <th><?php echo h('receipts.tracker_col_head'); ?></th>
                      <?php if ($isPerMemberDue): ?>
                        <th><?php echo h('receipts.tracker_col_people'); ?></th>
                      <?php endif; ?>
                      <th><?php echo h('receipts.tracker_col_due'); ?></th>
                      <th><?php echo h('receipts.tracker_col_paid'); ?></th>
                      <th><?php echo h('receipts.tracker_col_balance'); ?></th>
                      <th><?php echo h('receipts.tracker_col_status'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $trackerColspan = 5 + ($isPerMemberDue ? 1 : 0); ?>
                    <?php if ($dueStatuses === []): ?>
                      <tr><td colspan="<?php echo $trackerColspan; ?>" class="text-muted">
                        <?php
                        if ($trackerFilter === 'pending' && $isCompulsoryDue) {
                          echo h('receipts.tracker_empty_pending_all_paid');
                        } elseif ($trackerFilter === 'paid') {
                          echo h('receipts.tracker_empty_paid');
                        } else {
                          echo h('receipts.tracker_empty_generic');
                        }
                        ?>
                      </td></tr>
                    <?php else: ?>
                      <?php foreach ($dueStatuses as $ds): ?>
                        <?php
                          $due = (float) ($ds['amount_due'] ?? 0);
                          $paid = (float) ($ds['amount_paid'] ?? 0);
                          $bal = $due - $paid;
                          $st = (string) ($ds['status'] ?? 'unpaid');
                          $rowClass = $isCompulsoryDue && $st !== 'paid' ? 'table-warning' : '';
                          $memberCount = (int) ($ds['member_count'] ?? 1);
                          $rate = (float) ($ds['rate_per_member'] ?? $perMemberRate);
                        ?>
                        <tr class="<?php echo htmlspecialchars($rowClass); ?>">
                          <td><?php echo htmlspecialchars((string) ($ds['recipient_name'] ?? '')); ?></td>
                          <?php if ($isPerMemberDue): ?>
                            <td class="small">
                              <?php echo $memberCount; ?>
                              <span class="text-muted">× <?php echo htmlspecialchars(number_format($rate, 2)); ?></span>
                            </td>
                          <?php endif; ?>
                          <td><?php echo htmlspecialchars(number_format($due, 2)); ?></td>
                          <td><?php echo htmlspecialchars(number_format($paid, 2)); ?></td>
                          <td><?php echo htmlspecialchars(number_format(max(0, $bal), 2)); ?></td>
                          <td>
                            <?php if ($st === 'paid'): ?>
                      <span class="text-success"><?php echo h('receipts.tracker_status_paid'); ?></span>
                            <?php elseif ($st === 'partial'): ?>
                              <span class="text-warning"><?php echo h('receipts.tracker_status_partial'); ?></span>
                            <?php else: ?>
                              <span class="text-danger"><?php echo h('receipts.tracker_status_unpaid'); ?></span>
                            <?php endif; ?>
                          </td>
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
    </div>

    <div id="tab-receipt-list" class="receipt-tab-panel<?php echo $activeReceiptTab !== 'list' ? ' d-none' : ''; ?>">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title mb-2"><?php echo h('receipts.list_title'); ?></h4>
          <form method="get" class="mb-3">
            <input type="hidden" name="receipt_tab" value="list">
            <div class="receipts-filters-row">
              <div class="form-group mb-0 receipts-filter receipts-filter--fy">
                <label class="small mb-1" for="financial_year"><?php echo h('receipts.filter_fy_label'); ?></label>
                <select class="form-control" id="financial_year" name="financial_year">
                  <?php foreach ($financialYears as $fy): ?>
                    <option value="<?php echo htmlspecialchars((string) $fy); ?>"<?php echo $selectedFinancialYear === (string) $fy ? ' selected' : ''; ?>>
                      <?php echo htmlspecialchars((string) $fy); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group mb-0 receipts-filter receipts-filter--event">
                <label class="small mb-1" for="list_due_definition_id"><?php echo h('receipts.filter_event_label'); ?></label>
                <select class="form-control" id="list_due_definition_id" name="list_due_definition_id">
                  <option value=""><?php echo h('receipts.filter_event_all'); ?></option>
                  <?php foreach ($eventOccasionDefinitions as $def): ?>
                    <?php
                    $defId = (int) ($def['id'] ?? 0);
                    $defTitle = (string) ($def['title'] ?? '');
                    $defFy = (string) ($def['financial_year'] ?? '');
                    $defType = (string) ($def['due_type'] ?? '');
                    $typeLabel = $defType === 'occasion' ? h('receipts.filter_event_type_occasion') : h('receipts.filter_event_type_event');
                    $optionLabel = $defTitle . ($defFy !== '' ? ' (' . $defFy . ')' : '') . ' — ' . $typeLabel;
                    ?>
                    <option value="<?php echo $defId; ?>"<?php echo $filterListDueDefinitionId === $defId ? ' selected' : ''; ?>>
                      <?php echo htmlspecialchars($optionLabel); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group mb-0 receipts-filter receipts-filter--from">
                <label class="small mb-1" for="date_from"><?php echo h('receipts.filter_from_label'); ?></label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filterDateFrom); ?>">
              </div>
              <div class="form-group mb-0 receipts-filter receipts-filter--to">
                <label class="small mb-1" for="date_to"><?php echo h('receipts.filter_to_label'); ?></label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filterDateTo); ?>">
              </div>
              <div class="form-group mb-0 receipts-filter receipts-filter--q">
                <label class="small mb-1" for="q"><?php echo h('receipts.filter_search_label'); ?></label>
                <input type="text" class="form-control" id="q" name="q" value="<?php echo htmlspecialchars($filterQ); ?>" placeholder="<?php echo h('receipts.filter_search_placeholder'); ?>">
              </div>
              <div class="form-group mb-0 receipts-filter receipts-filter--apply">
                <label class="small mb-1 d-block">&nbsp;</label>
                <button type="submit" class="btn btn-outline-primary btn-block"><?php echo h('receipts.filter_apply_btn'); ?></button>
              </div>
              <div class="form-group mb-0 receipts-filter receipts-filter--reset">
                <label class="small mb-1 d-block">&nbsp;</label>
                <a class="btn btn-light btn-block" href="<?php echo htmlspecialchars($b); ?>/organization/receipts"><?php echo h('receipts.filter_reset_btn'); ?></a>
              </div>
            </div>
          </form>
          <div class="table-responsive">
            <table class="table table-striped table-sm">
              <thead>
                <tr>
                  <th><?php echo h('receipts.list_col_receipt_no'); ?></th>
                  <th><?php echo h('receipts.list_col_date'); ?></th>
                  <th><?php echo h('receipts.list_col_fy'); ?></th>
                  <th><?php echo h('receipts.list_col_recipient'); ?></th>
                  <th><?php echo h('receipts.list_col_purpose'); ?></th>
                  <th><?php echo h('receipts.list_col_amount'); ?></th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php if ($receipts === []): ?>
                  <tr><td colspan="7" class="text-muted"><?php echo h('receipts.list_empty'); ?></td></tr>
                <?php else: ?>
                  <?php foreach ($receipts as $r): ?>
                    <tr>
                      <td>
                        <?php echo htmlspecialchars(sprintf('%s/%04d', (string) ($r['financial_year'] ?? ''), (int) ($r['receipt_no'] ?? 0))); ?>
                      </td>
                      <td><?php echo htmlspecialchars(format_pretty_date(isset($r['receipt_date']) ? (string) $r['receipt_date'] : null)); ?></td>
                      <td><?php echo htmlspecialchars((string) ($r['financial_year'] ?? '')); ?></td>
                      <td><?php echo htmlspecialchars((string) (($r['recipient_name'] ?? '') ?: '—')); ?></td>
                      <td><?php echo htmlspecialchars((string) ($r['purpose'] ?? '')); ?></td>
                      <td><?php echo htmlspecialchars(number_format((float) ($r['amount'] ?? 0), 2)); ?></td>
                      <td>
                        <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?php echo htmlspecialchars($b); ?>/organization/receipt/print?id=<?php echo (int) ($r['id'] ?? 0); ?>"><?php echo h('receipts.list_print_btn'); ?></a>
                        <?php
                          $whatsappShareMessage = receipt_whatsapp_share_message($r);
                          $whatsappSharePhone = whatsapp_share_phone_from_row($r, ['recipient_phone']);
                          require BASE_PATH . '/app/Views/partials/whatsapp_share_button.php';
                        ?>
                      </td>
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
</div>

<script>
(function () {
  var familySelect = document.getElementById('family_id');
  var dueSelect = document.getElementById('new_receipt_due_definition_id');
  var recipientInput = document.getElementById('recipient_user_id');
  var dateInput = document.getElementById('receipt_date');
  var fyHint = document.getElementById('receipt_fy_hint');
  var purposeInput = document.getElementById('purpose');

  function syncRecipient() {
    if (!familySelect || !recipientInput) return;
    var opt = familySelect.options[familySelect.selectedIndex];
    var uid = opt ? (opt.getAttribute('data-user-id') || '') : '';
    recipientInput.value = uid;
  }

  function fyForDate(v) {
    if (!v) return '';
    var d = new Date(v + 'T00:00:00');
    if (isNaN(d.getTime())) return '';
    var y = d.getFullYear();
    var m = d.getMonth() + 1;
    var start = m >= 4 ? y : (y - 1);
    var end = start + 1;
    var endShort = String(end).slice(-2);
    return String(start) + '-' + endShort;
  }

  function syncFyHint() {
    if (!dateInput || !fyHint) return;
    var fy = fyForDate(dateInput.value || '');
    fyHint.textContent = fy ? (<?php echo json_encode(t('receipts.js_fy_prefix')); ?> + fy + <?php echo json_encode(t('receipts.js_fy_suffix')); ?>) : '';
  }

  if (familySelect) {
    familySelect.addEventListener('change', syncRecipient);
    syncRecipient();
  }
  if (dateInput) {
    dateInput.addEventListener('change', syncFyHint);
    syncFyHint();
  }
  function syncPurposeFromDue() {
    if (!dueSelect || !purposeInput) return;
    var opt = dueSelect.options[dueSelect.selectedIndex];
    var title = opt ? (opt.getAttribute('data-title') || '') : '';
    if (title) {
      purposeInput.value = title;
      purposeInput.readOnly = true;
    } else {
      purposeInput.readOnly = false;
    }
  }
  if (dueSelect) {
    syncPurposeFromDue();
  }

  var dueTypeSelect = document.getElementById('due_type');
  var compulsoryCheckbox = document.getElementById('due_is_compulsory');
  var compulsoryHint = document.getElementById('due_compulsory_hint');
  var chargeBasisWrap = document.getElementById('due_charge_basis_wrap');
  var chargeBasisFamily = document.getElementById('charge_basis_family');
  var chargeBasisPerson = document.getElementById('charge_basis_person');
  var amountInput = document.getElementById('amount');
  var passCountWrap = document.getElementById('pass_count_wrap');
  var passCountInput = document.getElementById('pass_count');
  var passRateHint = document.getElementById('pass_rate_hint');
  var amountQuantityHint = document.getElementById('amount_quantity_hint');
  var quantityCountLabel = document.getElementById('quantity_count_label');
  var labelPasses = <?php echo json_encode(t('receipts.pass_count_label')); ?>;
  var labelPeople = <?php echo json_encode(t('receipts.people_count_label')); ?>;
  var hintPasses = <?php echo json_encode(t('receipts.amount_event_hint')); ?>;
  var hintPeople = <?php echo json_encode(t('receipts.amount_people_hint')); ?>;

  function isQuantityDueOption(opt) {
    return !!(opt && opt.value && opt.getAttribute('data-shows-quantity') === '1');
  }

  function quantityModeForOption(opt) {
    return (opt && opt.getAttribute('data-quantity-mode')) || 'people';
  }

  function setQuantityVisible(show) {
    if (passCountWrap) passCountWrap.classList.toggle('d-none', !show);
    if (amountQuantityHint) amountQuantityHint.classList.toggle('d-none', !show);
  }

  function selectedFamilyMemberCount() {
    if (!familySelect) return 1;
    var fOpt = familySelect.options[familySelect.selectedIndex];
    return Math.max(1, parseInt(fOpt ? (fOpt.getAttribute('data-member-count') || '1') : '1', 10));
  }

  function defaultQuantityForDue(opt) {
    if (!opt || !opt.value) return 1;
    var basis = opt.getAttribute('data-charge-basis') || 'per_family';
    if (basis === 'per_person' || quantityModeForOption(opt) === 'people') {
      return selectedFamilyMemberCount();
    }
    return 1;
  }

  function syncQuantityUi(resetQuantity) {
    if (!dueSelect || !amountInput) return false;
    var dOpt = dueSelect.options[dueSelect.selectedIndex];
    var showQuantity = isQuantityDueOption(dOpt);
    setQuantityVisible(showQuantity);
    if (!showQuantity) {
      amountInput.readOnly = false;
      if (passRateHint) passRateHint.textContent = '';
      if (amountQuantityHint) amountQuantityHint.textContent = '';
      return false;
    }

    var mode = quantityModeForOption(dOpt);
    var isEvent = mode === 'passes';
    if (quantityCountLabel) {
      quantityCountLabel.textContent = isEvent ? labelPasses : labelPeople;
    }
    if (amountQuantityHint) {
      amountQuantityHint.textContent = isEvent ? hintPasses : hintPeople;
    }

    amountInput.readOnly = isEvent;
    var rate = parseFloat(dOpt.getAttribute('data-amount') || '0');
    var basis = dOpt.getAttribute('data-charge-basis') || 'per_family';
    if (passCountInput) {
      if (resetQuantity || !passCountInput.value) {
        passCountInput.value = String(defaultQuantityForDue(dOpt));
      }
      if (isEvent && basis !== 'per_person') {
        passCountInput.value = '1';
        passCountInput.readOnly = true;
        passCountInput.max = '1';
      } else if (isEvent) {
        passCountInput.readOnly = false;
        passCountInput.removeAttribute('max');
      } else {
        passCountInput.value = String(selectedFamilyMemberCount());
        passCountInput.readOnly = true;
        passCountInput.removeAttribute('max');
      }
      var qty = Math.max(1, parseInt(passCountInput.value || '1', 10));
      passCountInput.value = String(qty);
      if (rate > 0) {
        amountInput.value = (rate * qty).toFixed(2);
        if (passRateHint) {
          passRateHint.textContent = qty + ' × ' + rate.toFixed(2);
        }
      }
    }
    return true;
  }

  function syncReceiptAmountFromDue() {
    if (!dueSelect || !amountInput || !familySelect) return;
    var dOpt = dueSelect.options[dueSelect.selectedIndex];
    if (isQuantityDueOption(dOpt)) {
      syncQuantityUi(true);
      return;
    }
    setQuantityVisible(false);
    amountInput.readOnly = false;
    if (passRateHint) passRateHint.textContent = '';
    if (amountQuantityHint) amountQuantityHint.textContent = '';
    if (!dOpt || !dOpt.value) return;
    var rate = parseFloat(dOpt.getAttribute('data-amount') || '0');
    if (!(rate > 0)) return;
    amountInput.value = rate.toFixed(2);
  }

  function selectedChargeBasis() {
    if (chargeBasisPerson && chargeBasisPerson.checked) return 'per_person';
    return 'per_family';
  }

  function syncChargeBasisFromType() {
    if (!dueTypeSelect) return;
    var t = dueTypeSelect.value;
    if (t === 'membership') {
      if (chargeBasisWrap) chargeBasisWrap.style.display = 'none';
      if (chargeBasisPerson) chargeBasisPerson.checked = true;
    } else {
      if (chargeBasisWrap) chargeBasisWrap.style.display = '';
    }
    syncAmountLabelFromType();
    syncReceiptAmountFromDue();
  }

  var eventDateWrap = document.getElementById('due_event_date_wrap');

  function syncEventDateFromType() {
    if (!dueTypeSelect || !eventDateWrap) return;
    var show = dueTypeSelect.value === 'event' || dueTypeSelect.value === 'occasion';
    eventDateWrap.style.display = show ? '' : 'none';
  }

  function syncCompulsoryFromType() {
    if (!dueTypeSelect || !compulsoryCheckbox) return;
    syncEventDateFromType();
    if (dueTypeSelect.value === 'membership') {
      compulsoryCheckbox.checked = true;
      compulsoryCheckbox.disabled = true;
      if (compulsoryHint) {
        compulsoryHint.textContent = <?php echo json_encode(t('receipts.js_membership_compulsory_hint')); ?>;
      }
    } else {
      compulsoryCheckbox.disabled = false;
      if (dueTypeSelect.value === 'event') {
        if (compulsoryHint) {
          compulsoryHint.textContent = <?php echo json_encode(t('receipts.js_event_compulsory_hint')); ?>;
        }
      } else if (compulsoryHint) {
        compulsoryHint.textContent = <?php echo json_encode(t('receipts.js_compulsory_generic_hint')); ?>;
      }
    }
    syncChargeBasisFromType();
  }

  function syncAmountLabelFromType() {
    var label = document.getElementById('due_amount_label');
    var hint = document.getElementById('due_amount_hint');
    if (!dueTypeSelect || !label || !hint) return;
    var perPerson = dueTypeSelect.value === 'membership' || selectedChargeBasis() === 'per_person';
    if (perPerson) {
      label.textContent = <?php echo json_encode(t('receipts.js_amount_per_person_label')); ?>;
      hint.textContent = <?php echo json_encode(t('receipts.js_amount_per_person_hint')); ?>;
    } else {
      label.textContent = <?php echo json_encode(t('receipts.js_amount_per_family_label')); ?>;
      hint.textContent = <?php echo json_encode(t('receipts.js_amount_per_family_hint')); ?>;
    }
  }

  if (passCountInput) {
    passCountInput.addEventListener('input', function () { syncQuantityUi(false); });
    passCountInput.addEventListener('change', function () { syncQuantityUi(false); });
  }

  if (chargeBasisFamily) chargeBasisFamily.addEventListener('change', function () { syncAmountLabelFromType(); syncReceiptAmountFromDue(); });
  if (chargeBasisPerson) chargeBasisPerson.addEventListener('change', function () { syncAmountLabelFromType(); syncReceiptAmountFromDue(); });

  if (dueTypeSelect) {
    dueTypeSelect.addEventListener('change', function () {
      if (dueTypeSelect.value === 'event') {
        if (compulsoryCheckbox) compulsoryCheckbox.checked = false;
        if (chargeBasisPerson) chargeBasisPerson.checked = true;
      } else if (dueTypeSelect.value === 'membership') {
        if (chargeBasisPerson) chargeBasisPerson.checked = true;
      }
      syncCompulsoryFromType();
    });
    syncCompulsoryFromType();
  }

  if (dueSelect) {
    dueSelect.addEventListener('change', function () {
      syncPurposeFromDue();
      syncReceiptAmountFromDue();
    });
    syncReceiptAmountFromDue();
  }
  if (familySelect) {
    familySelect.addEventListener('change', function () {
      syncRecipient();
      syncReceiptAmountFromDue();
    });
  }

  var tabs = document.querySelectorAll('#receiptsTabs [data-tab-target]');
  var panels = document.querySelectorAll('.receipt-tab-panel');
  function openTab(panelId, tabEl) {
    panels.forEach(function (p) {
      p.classList.toggle('d-none', p.id !== panelId);
    });
    tabs.forEach(function (t) {
      t.classList.remove('active');
    });
    if (tabEl) tabEl.classList.add('active');
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function (e) {
      e.preventDefault();
      var target = tab.getAttribute('data-tab-target') || '';
      if (target) openTab(target, tab);
    });
  });
})();
</script>

<style>
.receipts-filters-row {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  flex-wrap: nowrap;
}
.receipts-filter--fy { flex: 0 0 14%; min-width: 150px; }
.receipts-filter--head { flex: 0 0 20%; min-width: 190px; }
.receipts-filter--from { flex: 0 0 12%; min-width: 130px; }
.receipts-filter--to { flex: 0 0 12%; min-width: 130px; }
.receipts-filter--q { flex: 1 1 24%; min-width: 180px; }
.receipts-filter--apply { flex: 0 0 85px; }
.receipts-filter--reset { flex: 0 0 85px; }
</style>

