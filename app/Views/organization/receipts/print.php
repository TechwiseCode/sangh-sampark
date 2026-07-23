<?php
$r = $receipt ?? [];
$amount = (float) ($r['amount'] ?? 0);
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars(current_locale()); ?>">
<head>
  <meta charset="utf-8">
  <title><?php echo htmlspecialchars(receipt_number_label($r)); ?></title>
  <style>
    @page { size: A5 landscape; margin: 12mm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; color: #111; font-size: 13px; }
    .wrap { border: 1px solid #bbb; padding: 16px; }
    .title { font-size: 24px; font-weight: 700; margin: 0 0 8px 0; }
    .sub { color: #666; margin-bottom: 16px; }
    .row { margin-bottom: 8px; }
    .label { display: inline-block; width: 160px; color: #555; }
    .value { font-weight: 600; }
    .amount { font-size: 22px; font-weight: 700; margin-top: 12px; }
    .footer { margin-top: 28px; color: #666; font-size: 12px; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="title"><?php echo h('receipts.print_title'); ?></div>
    <div class="sub"><?php echo htmlspecialchars((string) (($r['organization_name'] ?? '') ?: t('receipts.print_org_fallback'))); ?></div>

    <div class="row">
      <span class="label"><?php echo h('receipts.print_receipt_no'); ?></span>
      <span class="value">
        <?php echo htmlspecialchars(receipt_number_label($r)); ?>
      </span>
    </div>
    <div class="row"><span class="label"><?php echo h('receipts.print_date'); ?></span><span class="value"><?php echo htmlspecialchars(format_pretty_date(isset($r['receipt_date']) ? (string) $r['receipt_date'] : null)); ?></span></div>
    <div class="row"><span class="label"><?php echo h('receipts.print_fy'); ?></span><span class="value"><?php echo htmlspecialchars((string) ($r['financial_year'] ?? '')); ?></span></div>
    <div class="row"><span class="label"><?php echo h('receipts.print_received_from'); ?></span><span class="value"><?php echo htmlspecialchars((string) (($r['recipient_name'] ?? '') ?: '—')); ?></span></div>
    <div class="row"><span class="label"><?php echo h('receipts.print_purpose'); ?></span><span class="value"><?php echo htmlspecialchars((string) (($r['purpose'] ?? '') ?: '—')); ?></span></div>

    <div class="amount"><?php echo h('receipts.print_amount_prefix'); ?> <?php echo htmlspecialchars(number_format($amount, 2)); ?></div>

    <div class="footer">
      <?php echo h('receipts.print_footer_note'); ?>
    </div>
  </div>
</body>
</html>

