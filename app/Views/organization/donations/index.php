<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$categories = isset($categories) && is_array($categories) ? $categories : [];
$allCategories = isset($allCategories) && is_array($allCategories) ? $allCategories : $categories;
$commitments = isset($commitments) && is_array($commitments) ? $commitments : [];
$dashboardRows = isset($dashboardRows) && is_array($dashboardRows) ? $dashboardRows : [];
$dashboardTotals = isset($dashboardTotals) && is_array($dashboardTotals) ? $dashboardTotals : [];
$financialYears = isset($financialYears) && is_array($financialYears) ? $financialYears : [];
$selectedFinancialYear = (string) ($selectedFinancialYear ?? '');
$donationFilters = isset($donationFilters) && is_array($donationFilters) ? $donationFilters : [];
$heads = isset($heads) && is_array($heads) ? $heads : [];
$activeDonationTab = (string) ($activeDonationTab ?? 'donation');
$defaultDate = (string) ($defaultDate ?? date('Y-m-d'));
$filterQ = (string) ($donationFilters['q'] ?? '');
$filterCategoryId = (int) ($donationFilters['category_id'] ?? 0);
$filterStatus = (string) ($donationFilters['status'] ?? '');
$fyQuery = $selectedFinancialYear !== '' ? '&financial_year=' . urlencode($selectedFinancialYear) : '';
?>
<div class="row">
  <div class="col-12 border-bottom">
    <h3 class="mb-0"><?php echo h('donations.title'); ?></h3>
    <p class="text-muted small mb-0"><?php echo h('donations.subtitle'); ?></p>
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
    <ul class="nav nav-tabs mb-3" id="donationsTabs">
      <li class="nav-item">
        <a class="nav-link<?php echo $activeDonationTab === 'donation' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($b); ?>/organization/donations?donation_tab=donation<?php echo htmlspecialchars($fyQuery); ?>"><?php echo h('donations.tab_donation'); ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link<?php echo $activeDonationTab === 'dashboard' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($b); ?>/organization/donations?donation_tab=dashboard<?php echo htmlspecialchars($fyQuery); ?>"><?php echo h('donations.tab_dashboard'); ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link<?php echo $activeDonationTab === 'categories' ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($b); ?>/organization/donations?donation_tab=categories"><?php echo h('donations.tab_categories'); ?></a>
      </li>
    </ul>

    <?php if ($activeDonationTab === 'categories'): ?>
    <div id="tab-donations-categories" class="donation-tab-panel">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title mb-1"><?php echo h('donations.categories_title'); ?></h4>
          <p class="text-muted small mb-3"><?php echo h('donations.categories_subtitle'); ?></p>
          <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/donations/categories" class="donations-category-add-form mb-4">
            <div class="form-row align-items-end">
              <div class="form-group col-md-8 mb-2 mb-md-0">
                <label for="category_name_gu"><?php echo h('donations.categories_add_label'); ?></label>
                <input type="text" class="form-control" id="category_name_gu" name="name_gu" maxlength="255" required placeholder="<?php echo h('donations.categories_add_placeholder'); ?>">
              </div>
              <div class="form-group col-md-4 mb-0">
                <button type="submit" class="btn btn-primary btn-block"><?php echo h('donations.categories_add_btn'); ?></button>
              </div>
            </div>
          </form>
          <div class="table-responsive">
            <table class="table table-striped table-bordered donations-categories-table mb-0">
              <thead>
                <tr>
                  <th><?php echo h('donations.categories_col_name'); ?></th>
                  <th><?php echo h('donations.categories_col_type'); ?></th>
                  <th><?php echo h('donations.categories_col_status'); ?></th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php if ($allCategories === []): ?>
                  <tr><td colspan="4" class="text-muted"><?php echo h('donations.categories_empty'); ?></td></tr>
                <?php else: ?>
                  <?php foreach ($allCategories as $catRow): ?>
                    <?php
                      $isDefault = (int) ($catRow['is_default'] ?? 0) === 1;
                      $isActive = (int) ($catRow['is_active'] ?? 0) === 1;
                    ?>
                    <tr<?php echo $isActive ? '' : ' class="text-muted"'; ?>>
                      <td><?php echo htmlspecialchars((string) ($catRow['name_gu'] ?? '')); ?></td>
                      <td>
                        <?php if ($isDefault): ?>
                          <span class="badge badge-secondary"><?php echo h('donations.categories_type_default'); ?></span>
                        <?php else: ?>
                          <span class="badge badge-info"><?php echo h('donations.categories_type_custom'); ?></span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($isActive): ?>
                          <span class="badge badge-success"><?php echo h('donations.categories_status_active'); ?></span>
                        <?php else: ?>
                          <span class="badge badge-light border"><?php echo h('donations.categories_status_inactive'); ?></span>
                        <?php endif; ?>
                      </td>
                      <td class="text-nowrap">
                        <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/donations/categories/toggle" class="d-inline">
                          <input type="hidden" name="id" value="<?php echo (int) ($catRow['id'] ?? 0); ?>">
                          <?php if ($isActive): ?>
                            <input type="hidden" name="activate" value="0">
                            <button type="submit" class="btn btn-sm btn-outline-secondary"><?php echo h('donations.categories_deactivate'); ?></button>
                          <?php else: ?>
                            <input type="hidden" name="activate" value="1">
                            <button type="submit" class="btn btn-sm btn-outline-primary"><?php echo h('donations.categories_activate'); ?></button>
                          <?php endif; ?>
                        </form>
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
    <?php elseif ($activeDonationTab === 'dashboard'): ?>
    <div id="tab-donations-dashboard" class="donation-tab-panel">
      <div class="card">
        <div class="card-body">
          <form method="get" action="<?php echo htmlspecialchars($b); ?>/organization/donations" class="form-inline mb-3">
            <input type="hidden" name="donation_tab" value="dashboard">
            <label class="mr-2" for="dashboard_fy"><?php echo h('donations.fy_label'); ?></label>
            <select class="form-control mr-2" id="dashboard_fy" name="financial_year">
              <?php foreach ($financialYears as $fyOpt): ?>
                <option value="<?php echo htmlspecialchars((string) $fyOpt); ?>"<?php echo $selectedFinancialYear === (string) $fyOpt ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $fyOpt); ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary"><?php echo h('common.apply'); ?></button>
          </form>
          <div class="table-responsive">
            <table class="table table-striped table-bordered donations-dashboard-table">
              <thead>
                <tr>
                  <th><?php echo h('donations.col_category'); ?></th>
                  <th class="text-right"><?php echo h('donations.col_pledged'); ?></th>
                  <th class="text-right"><?php echo h('donations.col_collected'); ?></th>
                  <th class="text-right"><?php echo h('donations.col_balance'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($dashboardRows as $row): ?>
                  <tr>
                    <td><?php echo htmlspecialchars((string) ($row['name_gu'] ?? '')); ?></td>
                    <td class="text-right"><?php echo htmlspecialchars(number_format((float) ($row['pledged'] ?? 0), 2)); ?></td>
                    <td class="text-right"><?php echo htmlspecialchars(number_format((float) ($row['collected'] ?? 0), 2)); ?></td>
                    <td class="text-right"><?php echo htmlspecialchars(number_format((float) ($row['balance'] ?? 0), 2)); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="font-weight-bold">
                  <td><?php echo h('donations.total_row'); ?></td>
                  <td class="text-right"><?php echo htmlspecialchars(number_format((float) ($dashboardTotals['pledged'] ?? 0), 2)); ?></td>
                  <td class="text-right"><?php echo htmlspecialchars(number_format((float) ($dashboardTotals['collected'] ?? 0), 2)); ?></td>
                  <td class="text-right"><?php echo htmlspecialchars(number_format((float) ($dashboardTotals['balance'] ?? 0), 2)); ?></td>
                </tr>
              </tfoot>
            </table>
          </div>
          <p class="text-muted small mb-0"><?php echo h('donations.dashboard_hint'); ?></p>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div id="tab-donations-record" class="donation-tab-panel">
      <div class="donations-record-layout">
      <div class="card donations-record-form">
        <div class="card-body">
          <h4 class="card-title"><?php echo h('donations.new_title'); ?></h4>
          <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/donations/commitment" id="donationCommitmentForm" class="donations-commitment-form">
            <div class="donations-form-grid">
              <div class="donations-field donations-field--full">
                <label for="category_id"><?php echo h('donations.category_label'); ?></label>
                <select class="form-control" id="category_id" name="category_id" required>
                  <option value=""><?php echo h('donations.category_placeholder'); ?></option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo (int) ($cat['id'] ?? 0); ?>"><?php echo htmlspecialchars((string) ($cat['name_gu'] ?? '')); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="donations-field">
                <label for="committed_amount"><?php echo h('donations.amount_label'); ?></label>
                <input type="number" step="0.01" min="0.01" class="form-control" id="committed_amount" name="committed_amount" required>
              </div>
              <div class="donations-field">
                <label for="committed_date"><?php echo h('donations.date_label'); ?></label>
                <input type="date" class="form-control" id="committed_date" name="committed_date" value="<?php echo htmlspecialchars($defaultDate); ?>" required>
              </div>

              <div class="donations-field donations-field--full">
                <span class="donations-field-label"><?php echo h('donations.donor_type_label'); ?></span>
                <div class="donations-donor-type-toggle" role="radiogroup" aria-label="<?php echo htmlspecialchars(t('donations.donor_type_label')); ?>">
                  <label class="donations-type-option" for="donor_type_member">
                    <input class="donations-type-input" type="radio" name="donor_type" id="donor_type_member" value="member" checked>
                    <span><?php echo h('donations.donor_member'); ?></span>
                  </label>
                  <label class="donations-type-option" for="donor_type_guest">
                    <input class="donations-type-input" type="radio" name="donor_type" id="donor_type_guest" value="guest">
                    <span><?php echo h('donations.donor_guest'); ?></span>
                  </label>
                </div>
              </div>

              <div class="donations-field donations-field--full" id="memberDonorFields">
                <label for="family_id"><?php echo h('donations.member_label'); ?></label>
                <select class="form-control" id="family_id" name="family_id">
                  <option value=""><?php echo h('donations.member_placeholder'); ?></option>
                  <?php foreach ($heads as $h): ?>
                    <option value="<?php echo (int) ($h['id'] ?? 0); ?>" data-user-id="<?php echo (int) ($h['head_user_id'] ?? 0); ?>">
                      <?php echo htmlspecialchars((string) ($h['head_name'] ?? '')); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <input type="hidden" id="user_id" name="user_id" value="">
              </div>

              <div class="donations-field donations-field--full d-none" id="guestDonorFields">
                <div class="donations-form-grid">
                  <div class="donations-field">
                    <label for="donor_name"><?php echo h('donations.guest_name_label'); ?></label>
                    <input type="text" class="form-control" id="donor_name" name="donor_name" maxlength="191">
                  </div>
                  <div class="donations-field">
                    <label for="donor_phone"><?php echo h('donations.guest_phone_label'); ?></label>
                    <input type="text" class="form-control" id="donor_phone" name="donor_phone" maxlength="32" placeholder="10-digit mobile">
                  </div>
                </div>
              </div>

              <div class="donations-field donations-field--full">
                <label for="notes"><?php echo h('donations.notes_label'); ?></label>
                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
              </div>

              <div class="donations-field donations-field--full">
                <label class="donations-check-option" for="direct_payment">
                  <input class="donations-check-input" type="checkbox" id="direct_payment" name="direct_payment" value="1">
                  <span><?php echo h('donations.direct_payment_label'); ?></span>
                </label>
              </div>
            </div>

            <div id="directPaymentFields" class="donations-payment-panel d-none">
              <h5 class="donations-payment-title"><?php echo h('donations.payment_section'); ?></h5>
              <div class="donations-form-grid">
                <div class="donations-field">
                  <label for="payment_date"><?php echo h('donations.payment_date_label'); ?></label>
                  <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?php echo htmlspecialchars($defaultDate); ?>">
                </div>
                <div class="donations-field">
                  <label for="payment_mode"><?php echo h('donations.payment_mode_label'); ?></label>
                  <select class="form-control" id="payment_mode" name="payment_mode">
                    <option value=""><?php echo h('donations.payment_mode_placeholder'); ?></option>
                    <option value="cash"><?php echo h('donations.mode_cash'); ?></option>
                    <option value="upi"><?php echo h('donations.mode_upi'); ?></option>
                    <option value="bank"><?php echo h('donations.mode_bank'); ?></option>
                    <option value="cheque"><?php echo h('donations.mode_cheque'); ?></option>
                  </select>
                </div>
                <div class="donations-field">
                  <label for="reference_no"><?php echo h('donations.reference_label'); ?></label>
                  <input type="text" class="form-control" id="reference_no" name="reference_no" maxlength="100">
                </div>
                <div class="donations-field">
                  <label for="bank_name"><?php echo h('donations.bank_label'); ?></label>
                  <input type="text" class="form-control" id="bank_name" name="bank_name" maxlength="100">
                </div>
                <div class="donations-field donations-field--full">
                  <label for="cheque_date"><?php echo h('donations.cheque_date_label'); ?></label>
                  <input type="date" class="form-control" id="cheque_date" name="cheque_date">
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-primary donations-submit-btn"><?php echo h('common.save'); ?></button>
          </form>
        </div>
      </div>

      <div class="card donations-record-list mb-0">
        <div class="card-body">
          <h4 class="card-title"><?php echo h('donations.list_title'); ?></h4>
          <form method="get" action="<?php echo htmlspecialchars($b); ?>/organization/donations" class="donations-filters mb-3">
            <input type="hidden" name="donation_tab" value="donation">
            <div class="donations-filters-row donations-filters-row--primary">
              <div class="donations-filter-field donations-filter-field--fy">
                <label for="list_fy"><?php echo h('donations.fy_label'); ?></label>
                <select class="form-control" id="list_fy" name="financial_year">
                  <?php foreach ($financialYears as $fyOpt): ?>
                    <option value="<?php echo htmlspecialchars((string) $fyOpt); ?>"<?php echo $selectedFinancialYear === (string) $fyOpt ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $fyOpt); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="donations-filter-field donations-filter-field--cat">
                <label for="list_category"><?php echo h('donations.category_label'); ?></label>
                <select class="form-control" id="list_category" name="category_id">
                  <option value=""><?php echo h('donations.all_categories'); ?></option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo (int) ($cat['id'] ?? 0); ?>"<?php echo $filterCategoryId === (int) ($cat['id'] ?? 0) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) ($cat['name_gu'] ?? '')); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="donations-filter-field donations-filter-field--status">
                <label for="list_status"><?php echo h('donations.status_label'); ?></label>
                <select class="form-control" id="list_status" name="status">
                  <option value=""><?php echo h('donations.all_statuses'); ?></option>
                  <option value="open"<?php echo $filterStatus === 'open' ? ' selected' : ''; ?>><?php echo h('donations.status_open'); ?></option>
                  <option value="partial"<?php echo $filterStatus === 'partial' ? ' selected' : ''; ?>><?php echo h('donations.status_partial'); ?></option>
                  <option value="fulfilled"<?php echo $filterStatus === 'fulfilled' ? ' selected' : ''; ?>><?php echo h('donations.status_fulfilled'); ?></option>
                </select>
              </div>
              <div class="donations-filter-field donations-filter-field--apply">
                <label class="donations-filter-apply-label" aria-hidden="true">&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-block"><?php echo h('common.apply'); ?></button>
              </div>
            </div>
            <div class="donations-filters-row donations-filters-row--search">
              <div class="donations-filter-field donations-filter-field--search">
                <label for="list_q"><?php echo h('donations.search_label'); ?></label>
                <input type="text" class="form-control" id="list_q" name="q" value="<?php echo htmlspecialchars($filterQ); ?>" placeholder="<?php echo h('donations.search_placeholder'); ?>">
              </div>
            </div>
          </form>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th><?php echo h('donations.col_date'); ?></th>
                  <th><?php echo h('donations.col_donor'); ?></th>
                  <th><?php echo h('donations.col_category'); ?></th>
                  <th class="text-right"><?php echo h('donations.col_committed'); ?></th>
                  <th class="text-right"><?php echo h('donations.col_paid'); ?></th>
                  <th class="text-right"><?php echo h('donations.col_balance'); ?></th>
                  <th><?php echo h('donations.col_status'); ?></th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php if ($commitments === []): ?>
                  <tr><td colspan="8" class="text-muted"><?php echo h('donations.empty_list'); ?></td></tr>
                <?php else: ?>
                  <?php foreach ($commitments as $c): ?>
                    <?php
                      $status = (string) ($c['status'] ?? '');
                      $balance = (float) ($c['balance'] ?? 0);
                      $canPay = in_array($status, ['open', 'partial'], true) && $balance > 0;
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars(format_pretty_date((string) ($c['committed_date'] ?? ''))); ?></td>
                      <td>
                        <?php echo htmlspecialchars((string) ($c['donor_name'] ?? '')); ?>
                        <?php if (!empty($c['donor_phone'])): ?>
                          <br><span class="text-muted small"><?php echo htmlspecialchars((string) $c['donor_phone']); ?></span>
                        <?php endif; ?>
                      </td>
                      <td><?php echo htmlspecialchars((string) ($c['category_name'] ?? '')); ?></td>
                      <td class="text-right"><?php echo htmlspecialchars(number_format((float) ($c['committed_amount'] ?? 0), 2)); ?></td>
                      <td class="text-right"><?php echo htmlspecialchars(number_format((float) ($c['paid_total'] ?? 0), 2)); ?></td>
                      <td class="text-right"><?php echo htmlspecialchars(number_format($balance, 2)); ?></td>
                      <td>
                        <?php if ($status === 'open'): ?>
                          <span class="badge badge-warning"><?php echo h('donations.status_open'); ?></span>
                        <?php elseif ($status === 'partial'): ?>
                          <span class="badge badge-info"><?php echo h('donations.status_partial'); ?></span>
                        <?php elseif ($status === 'fulfilled'): ?>
                          <span class="badge badge-success"><?php echo h('donations.status_fulfilled'); ?></span>
                        <?php else: ?>
                          <span class="badge badge-secondary"><?php echo htmlspecialchars($status); ?></span>
                        <?php endif; ?>
                      </td>
                      <td class="text-nowrap">
                        <?php if ($canPay): ?>
                          <button type="button" class="btn btn-sm btn-outline-primary btn-record-payment"
                            data-id="<?php echo (int) ($c['id'] ?? 0); ?>"
                            data-donor="<?php echo htmlspecialchars((string) ($c['donor_name'] ?? ''), ENT_QUOTES); ?>"
                            data-balance="<?php echo htmlspecialchars(number_format($balance, 2, '.', '')); ?>"
                            data-category="<?php echo htmlspecialchars((string) ($c['category_name'] ?? ''), ENT_QUOTES); ?>">
                            <?php echo h('donations.record_payment'); ?>
                          </button>
                        <?php endif; ?>
                        <?php
                          $whatsappShareMessage = donation_whatsapp_share_message($c);
                          $whatsappSharePhone = whatsapp_share_phone_from_row($c, ['donor_phone', 'donor_user_phone']);
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
    <?php endif; ?>
  </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/donations/payment">
        <div class="modal-header">
          <h5 class="modal-title"><?php echo h('donations.record_payment'); ?></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="parent_id" id="pay_parent_id" value="">
          <p class="mb-2"><strong id="pay_donor_label"></strong></p>
          <p class="text-muted small mb-3" id="pay_category_label"></p>
          <p class="small mb-3"><?php echo h('donations.balance_label'); ?>: <strong id="pay_balance_label"></strong></p>
          <div class="form-group">
            <label for="paid_amount"><?php echo h('donations.amount_label'); ?></label>
            <input type="number" step="0.01" min="0.01" class="form-control" id="paid_amount" name="paid_amount" required>
          </div>
          <div class="form-group">
            <label for="modal_payment_date"><?php echo h('donations.payment_date_label'); ?></label>
            <input type="date" class="form-control" id="modal_payment_date" name="payment_date" value="<?php echo htmlspecialchars($defaultDate); ?>" required>
          </div>
          <div class="form-group">
            <label for="modal_payment_mode"><?php echo h('donations.payment_mode_label'); ?></label>
            <select class="form-control" id="modal_payment_mode" name="payment_mode" required>
              <option value=""><?php echo h('donations.payment_mode_placeholder'); ?></option>
              <option value="cash"><?php echo h('donations.mode_cash'); ?></option>
              <option value="upi"><?php echo h('donations.mode_upi'); ?></option>
              <option value="bank"><?php echo h('donations.mode_bank'); ?></option>
              <option value="cheque"><?php echo h('donations.mode_cheque'); ?></option>
            </select>
          </div>
          <div class="form-group">
            <label for="modal_reference_no"><?php echo h('donations.reference_label'); ?></label>
            <input type="text" class="form-control" id="modal_reference_no" name="reference_no" maxlength="100">
          </div>
          <div class="form-group">
            <label for="modal_bank_name"><?php echo h('donations.bank_label'); ?></label>
            <input type="text" class="form-control" id="modal_bank_name" name="bank_name" maxlength="100">
          </div>
          <div class="form-group">
            <label for="modal_cheque_date"><?php echo h('donations.cheque_date_label'); ?></label>
            <input type="date" class="form-control" id="modal_cheque_date" name="cheque_date">
          </div>
          <div class="form-group mb-0">
            <label for="modal_notes"><?php echo h('donations.notes_label'); ?></label>
            <textarea class="form-control" id="modal_notes" name="notes" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-dismiss="modal"><?php echo h('common.cancel'); ?></button>
          <button type="submit" class="btn btn-primary"><?php echo h('common.save'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  var familySelect = document.getElementById('family_id');
  var userIdInput = document.getElementById('user_id');
  function syncMemberUser() {
    if (!familySelect || !userIdInput) return;
    var opt = familySelect.options[familySelect.selectedIndex];
    userIdInput.value = opt ? (opt.getAttribute('data-user-id') || '') : '';
  }
  if (familySelect) {
    familySelect.addEventListener('change', syncMemberUser);
    syncMemberUser();
  }

  var donorMember = document.getElementById('donor_type_member');
  var donorGuest = document.getElementById('donor_type_guest');
  var memberFields = document.getElementById('memberDonorFields');
  var guestFields = document.getElementById('guestDonorFields');
  function syncDonorType() {
    var isGuest = donorGuest && donorGuest.checked;
    if (memberFields) memberFields.classList.toggle('d-none', isGuest);
    if (guestFields) guestFields.classList.toggle('d-none', !isGuest);
    if (familySelect) familySelect.required = !isGuest;
    var donorName = document.getElementById('donor_name');
    if (donorName) donorName.required = !!isGuest;
    document.querySelectorAll('.donations-type-option').forEach(function (label) {
      var input = label.querySelector('.donations-type-input');
      label.classList.toggle('is-selected', !!(input && input.checked));
    });
  }
  if (donorMember) donorMember.addEventListener('change', syncDonorType);
  if (donorGuest) donorGuest.addEventListener('change', syncDonorType);
  syncDonorType();

  var directCb = document.getElementById('direct_payment');
  var directFields = document.getElementById('directPaymentFields');
  var paymentMode = document.getElementById('payment_mode');
  function syncDirectPayment() {
    var on = directCb && directCb.checked;
    if (directFields) directFields.classList.toggle('d-none', !on);
    if (paymentMode) paymentMode.required = !!on;
    var checkLabel = directCb ? directCb.closest('.donations-check-option') : null;
    if (checkLabel) checkLabel.classList.toggle('is-selected', !!on);
  }
  if (directCb) directCb.addEventListener('change', syncDirectPayment);
  syncDirectPayment();

  document.querySelectorAll('.btn-record-payment').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-id') || '';
      var donor = btn.getAttribute('data-donor') || '';
      var balance = btn.getAttribute('data-balance') || '';
      var category = btn.getAttribute('data-category') || '';
      var parentInput = document.getElementById('pay_parent_id');
      var donorLabel = document.getElementById('pay_donor_label');
      var catLabel = document.getElementById('pay_category_label');
      var balLabel = document.getElementById('pay_balance_label');
      var amountInput = document.getElementById('paid_amount');
      if (parentInput) parentInput.value = id;
      if (donorLabel) donorLabel.textContent = donor;
      if (catLabel) catLabel.textContent = category;
      if (balLabel) balLabel.textContent = balance;
      if (amountInput) {
        amountInput.value = balance;
        amountInput.max = balance;
      }
      if (window.jQuery) {
        window.jQuery('#paymentModal').modal('show');
      } else {
        var modal = document.getElementById('paymentModal');
        if (modal) modal.style.display = 'block';
      }
    });
  });
})();
</script>

<style>
.donations-record-layout {
  display: grid;
  grid-template-columns: minmax(320px, 0.92fr) minmax(0, 1.38fr);
  gap: 1rem;
  align-items: start;
}
.donations-record-form .card-body,
.donations-record-list .card-body {
  padding: 1.1rem 1.15rem;
}
.donations-record-form .card-title,
.donations-record-list .card-title {
  font-size: 1.05rem;
  margin-bottom: 0.85rem;
}

/* Commitment form — equal two-column grid */
.donations-form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.1rem 1.25rem;
}
.donations-field {
  min-width: 0;
  margin: 0;
}
.donations-field--full {
  grid-column: 1 / -1;
}
.donations-field label,
.donations-field .donations-field-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.5rem;
  line-height: 1.3;
}
.donations-field .form-control {
  width: 100%;
  min-height: 38px;
}
.donations-field textarea.form-control {
  min-height: 68px;
  resize: vertical;
}

/* Donor type — equal side-by-side toggles */
.donations-donor-type-toggle {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.5rem;
}
.donations-type-option {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  min-height: 38px;
  margin: 0;
  padding: 0.45rem 0.65rem;
  border: 1px solid #dee2e6;
  border-radius: 0.35rem;
  background: #fff;
  color: #495057;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s, color 0.15s;
  text-align: center;
}
.donations-type-option:hover {
  border-color: rgba(52, 177, 170, 0.45);
  background: #f8fdfc;
}
.donations-type-input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}
.donations-type-option:has(.donations-type-input:checked),
.donations-type-option.is-selected {
  border-color: #34B1AA;
  background: #e8f7f6;
  color: #1f6f6a;
  font-weight: 600;
}

.donations-check-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-height: 38px;
  margin: 0;
  padding: 0.5rem 0.75rem;
  border: 1px solid #dee2e6;
  border-radius: 0.35rem;
  background: #fafbfc;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
}
.donations-check-option:has(.donations-check-input:checked),
.donations-check-option.is-selected {
  border-color: #34B1AA;
  background: #e8f7f6;
}
.donations-check-input {
  margin: 0;
  flex: 0 0 auto;
}

.donations-payment-panel {
  margin: 0.25rem 0 0.85rem;
  padding: 0.85rem;
  border: 1px solid #e9ecef;
  border-radius: 0.4rem;
  background: #f8fafb;
}
.donations-payment-title {
  font-size: 0.95rem;
  font-weight: 600;
  margin: 0 0 0.75rem;
}
.donations-submit-btn {
  margin-top: 0.15rem;
}

/* List filters — DataTables style: 3 selects + apply on top, search full width below */
.donations-filters {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}
.donations-filters-row--primary {
  display: grid;
  grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.35fr) minmax(0, 1fr) auto;
  gap: 0.85rem 1rem;
  align-items: end;
}
.donations-filters-row--search {
  width: 100%;
}
.donations-filter-field {
  min-width: 0;
  margin: 0;
}
.donations-filter-field--apply {
  min-width: 88px;
}
.donations-filter-field label,
.donations-filter-field .donations-filter-apply-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.4rem;
  line-height: 1.25;
}
.donations-filter-field .form-control {
  width: 100%;
  min-height: 38px;
}
.donations-filter-field--apply .btn {
  white-space: nowrap;
  padding-left: 1rem;
  padding-right: 1rem;
}
.donations-filter-field--search .form-control {
  max-width: 100%;
}

.donations-dashboard-table th,
.donations-dashboard-table td {
  vertical-align: middle;
}

.donations-categories-table th,
.donations-categories-table td {
  vertical-align: middle;
}

.donations-category-add-form .form-control {
  min-height: 38px;
}

@media (max-width: 1199.98px) {
  .donations-record-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 767.98px) {
  .donations-filters-row--primary {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .donations-filter-field--apply {
    grid-column: 1 / -1;
  }
  .donations-filter-field--apply .btn {
    width: 100%;
  }
}

@media (max-width: 575.98px) {
  .donations-filters-row--primary {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 479.98px) {
  .donations-form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
