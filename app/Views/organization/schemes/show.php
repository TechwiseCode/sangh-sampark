<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$scheme = $scheme ?? [];
$assignments = $assignments ?? [];
?>
<div class="row">
  <div class="col-12 border-bottom d-flex justify-content-between align-items-center flex-wrap">
    <div>
      <p class="mb-2"><a href="<?php echo htmlspecialchars($b); ?>/organization/events?event_tab=schemes" class="small">&larr; <?php echo htmlspecialchars(t('events.back_to_list')); ?></a></p>
      <h3 class="mb-0"><?php echo htmlspecialchars((string) ($scheme['name'] ?? 'Scheme')); ?></h3>
      <p class="text-muted mb-0">
        Scope: <strong><?php echo htmlspecialchars((string) ($scheme['benefit_scope'] ?? '')); ?></strong>
        · Benefit: <strong><?php echo htmlspecialchars((string) ($scheme['benefit_type'] ?? '')); ?></strong>
        <?php if (!empty($scheme['benefit_value'])): ?>
          · Value: <strong><?php echo htmlspecialchars((string) $scheme['benefit_value']); ?></strong>
        <?php endif; ?>
      </p>
    </div>
    <div class="mb-2">
      <?php
        $whatsappShareMessage = scheme_whatsapp_share_message($scheme);
        require BASE_PATH . '/app/Views/partials/whatsapp_share_button.php';
      ?>
    </div>
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
        <h4 class="card-title"><?php echo htmlspecialchars(t('schemes.mark_done')); ?></h4>
        <?php if ($assignments === []): ?>
          <p class="text-muted mb-0"><?php echo htmlspecialchars(t('schemes.no_assignments')); ?></p>
        <?php else: ?>
          <?php $pickerOptions = []; ?>
          <?php foreach ($assignments as $a): ?>
            <?php if ((string) ($a['status'] ?? '') === 'claimed') { continue; } ?>
            <?php
              $label = '';
              $memberCode = (string) ($a['beneficiary_full_member_code'] ?? '');
              if ((string) ($scheme['benefit_scope'] ?? '') === 'family') {
                  $label = 'Family #' . (int) ($a['family_id'] ?? 0)
                      . ' - Head: ' . (string) ($a['head_name'] ?? $a['beneficiary_name'] ?? '');
              } else {
                  $label = (string) ($a['member_name'] ?? $a['beneficiary_name'] ?? '');
              }
              if ($memberCode !== '') {
                  $label .= ' (' . $memberCode . ')';
              }
              $pickerOptions[] = [
                  'benefit_id' => (int) ($a['benefit_id'] ?? 0),
                  'label' => $label,
              ];
            ?>
          <?php endforeach; ?>
          <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/scheme/mark-done" id="mark-done-form">
            <input type="hidden" name="scheme_id" value="<?php echo (int) ($scheme['id'] ?? 0); ?>">
            <input type="hidden" id="benefit_id" name="benefit_id" value="" required>
            <label class="mb-2"><?php echo htmlspecialchars(t('schemes.select_member')); ?></label>
            <div class="benefit-picker mb-2" id="benefit-picker">
              <button type="button" class="form-control text-left benefit-picker-toggle" id="benefit-picker-toggle">
                <?php echo htmlspecialchars(t('common.choose')); ?>
              </button>
              <div class="benefit-picker-menu d-none" id="benefit-picker-menu">
                <input type="text" class="form-control form-control-sm mb-2" id="benefit-picker-search" placeholder="Type name or code...">
                <div class="benefit-picker-list" id="benefit-picker-list">
                  <?php foreach ($pickerOptions as $opt): ?>
                    <button type="button" class="benefit-option" data-benefit-id="<?php echo (int) $opt['benefit_id']; ?>" data-label="<?php echo htmlspecialchars((string) $opt['label'], ENT_QUOTES); ?>">
                      <?php echo htmlspecialchars((string) $opt['label']); ?>
                    </button>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-success"><?php echo htmlspecialchars(t('schemes.mark_done_btn')); ?></button>
            <small id="benefit-picker-help" class="text-muted d-block mt-1"><?php echo htmlspecialchars(t('schemes.search_hint')); ?></small>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row" style="padding-top: 16px;">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title"><?php echo htmlspecialchars(t('schemes.status_list')); ?></h4>
        <?php if ($assignments === []): ?>
          <p class="text-muted mb-0"><?php echo htmlspecialchars(t('schemes.no_records')); ?></p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th><?php echo htmlspecialchars(t('schemes.beneficiary')); ?></th>
                  <th><?php echo htmlspecialchars(t('schemes.scope_ref')); ?></th>
                  <th><?php echo htmlspecialchars(t('common.status')); ?></th>
                  <th><?php echo htmlspecialchars(t('schemes.claimed')); ?></th>
                  <th><?php echo htmlspecialchars(t('schemes.marked_by')); ?></th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($assignments as $a): ?>
                <tr>
                  <td>
                    <?php
                    if ((string) ($scheme['benefit_scope'] ?? '') === 'family') {
                        echo htmlspecialchars((string) ($a['head_name'] ?? $a['beneficiary_name'] ?? ''));
                    } else {
                        echo htmlspecialchars((string) ($a['member_name'] ?? $a['beneficiary_name'] ?? ''));
                    }
                    ?>
                  </td>
                  <td>
                    <?php if ((string) ($scheme['benefit_scope'] ?? '') === 'family'): ?>
                      Family #<?php echo (int) ($a['family_id'] ?? 0); ?>
                    <?php else: ?>
                      <?php echo htmlspecialchars((string) (($a['beneficiary_full_member_code'] ?? '') ?: '—')); ?>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ((string) ($a['status'] ?? '') === 'claimed'): ?>
                      <span class="badge badge-success"><?php echo htmlspecialchars(t('schemes.done')); ?></span>
                    <?php else: ?>
                      <span class="badge badge-warning"><?php echo htmlspecialchars((string) ($a['status'] ?? 'eligible')); ?></span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars(format_pretty_datetime(isset($a['claimed_at']) ? (string) $a['claimed_at'] : null)); ?></td>
                  <td><?php echo htmlspecialchars((string) ($a['claimed_by_name'] ?? '-')); ?></td>
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

<script>
(function () {
  var picker = document.getElementById('benefit-picker');
  var toggle = document.getElementById('benefit-picker-toggle');
  var menu = document.getElementById('benefit-picker-menu');
  var search = document.getElementById('benefit-picker-search');
  var hidden = document.getElementById('benefit_id');
  var form = document.getElementById('mark-done-form');
  var help = document.getElementById('benefit-picker-help');
  if (!picker || !toggle || !menu || !search || !hidden || !form) return;

  var options = Array.prototype.slice.call(picker.querySelectorAll('.benefit-option'));
  function normalize(v) {
    return (v || '').toLowerCase().replace(/[^a-z0-9]/g, '');
  }
  function openMenu() {
    menu.classList.remove('d-none');
    search.focus();
  }
  function closeMenu() {
    menu.classList.add('d-none');
  }
  function filterOptions() {
    var term = normalize(search.value || '');
    var hasVisible = false;
    options.forEach(function (btn) {
      var label = btn.getAttribute('data-label') || '';
      var visible = term === '' || normalize(label).indexOf(term) !== -1;
      btn.style.display = visible ? 'block' : 'none';
      if (visible) hasVisible = true;
    });
    if (help) {
      help.textContent = hasVisible ? 'Search by name or member code.' : 'No matching member found.';
      help.className = hasVisible ? 'text-muted d-block mt-1' : 'text-danger d-block mt-1';
    }
  }

  toggle.addEventListener('click', function () {
    if (menu.classList.contains('d-none')) openMenu(); else closeMenu();
  });
  search.addEventListener('input', filterOptions);
  options.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-benefit-id') || '';
      var label = btn.getAttribute('data-label') || 'Choose...';
      hidden.value = id;
      toggle.textContent = label;
      closeMenu();
      if (help) {
        help.textContent = 'Selected.';
        help.className = 'text-success d-block mt-1';
      }
    });
  });
  document.addEventListener('click', function (e) {
    if (!picker.contains(e.target)) closeMenu();
  });
  form.addEventListener('submit', function (e) {
    if (!hidden.value) {
      e.preventDefault();
      if (help) {
        help.textContent = 'Please select member/head.';
        help.className = 'text-danger d-block mt-1';
      }
      openMenu();
    }
  });
})();
</script>

<style>
.benefit-picker { position: relative; max-width: 540px; }
.benefit-picker-toggle { background: #fff; }
.benefit-picker-menu {
  position: absolute;
  z-index: 30;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid #ced4da;
  border-radius: 6px;
  padding: 8px;
  box-shadow: 0 6px 16px rgba(0,0,0,0.12);
}
.benefit-picker-list { max-height: 220px; overflow-y: auto; }
.benefit-option {
  width: 100%;
  text-align: left;
  border: 0;
  background: transparent;
  padding: 6px 8px;
  border-radius: 4px;
}
.benefit-option:hover { background: #f1f3f5; }
</style>


