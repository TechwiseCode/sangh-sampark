<?php
$b = base_url();
$receipts = isset($receipts) && is_array($receipts) ? $receipts : [];
?>
<div class="row">
  <div class="col-12 border-bottom">
    <h3 class="mb-0"><?php echo h('nav.receipts'); ?></h3>
    <p class="text-muted small mb-0"><?php echo h('receipts.member_subtitle'); ?></p>
  </div>
</div>
<div class="row" style="padding-top: 16px;">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-striped mb-0">
            <thead>
              <tr>
                <th><?php echo h('receipts.list_col_receipt_no'); ?></th>
                <th><?php echo h('receipts.list_col_date'); ?></th>
                <th><?php echo h('receipts.list_col_fy'); ?></th>
                <th><?php echo h('receipts.list_col_purpose'); ?></th>
                <th><?php echo h('receipts.list_col_amount'); ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php if ($receipts === []): ?>
                <tr><td colspan="6" class="text-muted"><?php echo h('family.show.receipts_empty'); ?></td></tr>
              <?php else: ?>
                <?php foreach ($receipts as $r): ?>
                  <tr>
                    <td><?php echo htmlspecialchars(sprintf('%s/%04d', (string) ($r['financial_year'] ?? ''), (int) ($r['receipt_no'] ?? 0))); ?></td>
                    <td><?php echo htmlspecialchars(format_pretty_date(isset($r['receipt_date']) ? (string) $r['receipt_date'] : null)); ?></td>
                    <td><?php echo htmlspecialchars((string) ($r['financial_year'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars((string) ($r['purpose'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars(number_format((float) ($r['amount'] ?? 0), 2)); ?></td>
                      <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="<?php echo htmlspecialchars($b); ?>/organization/receipt/print?id=<?php echo (int) ($r['id'] ?? 0); ?>"><?php echo h('receipts.list_print_btn'); ?></a>
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
