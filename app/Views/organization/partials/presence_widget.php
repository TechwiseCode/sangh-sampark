<?php
$b = base_url();
$canManageOrg = !empty($canManageOrg);
$presenceCurrent = isset($presenceCurrent) && is_array($presenceCurrent) ? $presenceCurrent : null;
$presenceHistory = isset($presenceHistory) && is_array($presenceHistory) ? $presenceHistory : [];
$currentNames = [];
if ($presenceCurrent !== null) {
    foreach ($presenceCurrent['members'] as $m) {
        $n = trim((string) ($m['display_name'] ?? ''));
        if ($n !== '') {
            $currentNames[] = $n;
        }
    }
}
$sinceLabel = $presenceCurrent !== null
    ? format_pretty_date((string) ($presenceCurrent['effective_from'] ?? ''))
    : '';
$peopleCount = count($currentNames);
?>
<section class="presence-now" id="presence">
  <div class="presence-now__panel">
    <header class="presence-now__head">
      <div class="presence-now__mark" aria-hidden="true">
        <i class="mdi mdi-flower-tulip-outline"></i>
      </div>
      <div class="presence-now__titles">
        <p class="presence-now__eyebrow"><?php echo h('presence.eyebrow'); ?></p>
        <h2 class="presence-now__title"><?php echo h('presence.title'); ?></h2>
        <p class="presence-now__meta">
          <?php if ($peopleCount > 0): ?>
            <span class="presence-now__count"><?php echo h('presence.people_count', ['count' => (string) $peopleCount]); ?></span>
          <?php endif; ?>
          <?php if ($sinceLabel !== ''): ?>
            <span class="presence-now__since"><?php echo h('presence.since'); ?> <?php echo htmlspecialchars($sinceLabel); ?></span>
          <?php endif; ?>
        </p>
      </div>
      <span class="presence-now__live" title="<?php echo h('presence.live'); ?>">
        <span class="presence-now__live-dot" aria-hidden="true"></span>
        <?php echo h('presence.live'); ?>
      </span>
    </header>

    <?php if ($canManageOrg): ?>
      <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/presence" class="presence-now__form" id="dash-presence-form">
        <ul class="presence-now__editor" id="dash-presence-rows">
          <?php
            $editRows = $currentNames !== [] ? $currentNames : [''];
            foreach ($editRows as $idx => $name):
          ?>
            <li class="presence-now__editor-row dash-presence-row">
              <span class="presence-now__num" aria-hidden="true"><?php echo str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT); ?></span>
              <input type="text" class="form-control form-control-sm presence-now__input" name="names[]" value="<?php echo htmlspecialchars($name); ?>" placeholder="<?php echo h('presence.name_placeholder'); ?>" maxlength="191">
              <button type="button" class="presence-now__remove dash-presence-remove" title="<?php echo h('presence.remove_row'); ?>" aria-label="<?php echo h('presence.remove_row'); ?>">
                <i class="mdi mdi-close" aria-hidden="true"></i>
              </button>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="presence-now__actions">
          <button type="button" class="btn btn-sm btn-outline-secondary" id="dash-presence-add">
            <i class="mdi mdi-plus" aria-hidden="true"></i>
            <?php echo h('presence.add_row'); ?>
          </button>
          <button type="submit" class="btn btn-sm btn-primary">
            <i class="mdi mdi-content-save-outline" aria-hidden="true"></i>
            <?php echo h('presence.save'); ?>
          </button>
        </div>
      </form>
    <?php else: ?>
      <?php if ($currentNames === []): ?>
        <div class="presence-now__empty">
          <i class="mdi mdi-account-group-outline" aria-hidden="true"></i>
          <p><?php echo h('presence.empty_current'); ?></p>
        </div>
      <?php else: ?>
        <ol class="presence-now__roster">
          <?php foreach ($currentNames as $i => $name): ?>
            <li class="presence-now__person" style="--presence-i: <?php echo (int) $i; ?>">
              <span class="presence-now__num" aria-hidden="true"><?php echo str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT); ?></span>
              <span class="presence-now__name"><?php echo htmlspecialchars($name); ?></span>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($presenceHistory !== []): ?>
      <details class="presence-now__history">
        <summary class="presence-now__history-toggle"><?php echo h('presence.history'); ?></summary>
        <div class="presence-now__history-list">
          <?php foreach ($presenceHistory as $entry): ?>
            <?php
              $histNames = [];
              foreach ($entry['members'] as $m) {
                  $n = trim((string) ($m['display_name'] ?? ''));
                  if ($n !== '') {
                      $histNames[] = $n;
                  }
              }
              if ($histNames === []) {
                  continue;
              }
              $from = format_pretty_date((string) ($entry['effective_from'] ?? ''));
              $until = format_pretty_date((string) ($entry['effective_until'] ?? ''));
            ?>
            <div class="presence-now__history-item">
              <div class="presence-now__history-range"><?php echo htmlspecialchars($from); ?> &ndash; <?php echo htmlspecialchars($until); ?></div>
              <ul class="presence-now__history-names">
                <?php foreach ($histNames as $name): ?>
                  <li><?php echo htmlspecialchars($name); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endforeach; ?>
        </div>
      </details>
    <?php endif; ?>
  </div>
</section>

<?php if ($canManageOrg): ?>
<script>
(function () {
  var list = document.getElementById('dash-presence-rows');
  var addBtn = document.getElementById('dash-presence-add');
  if (!list || !addBtn) return;

  var placeholder = <?php echo json_encode(t('presence.name_placeholder')); ?>;
  var removeLabel = <?php echo json_encode(t('presence.remove_row')); ?>;

  function renumber() {
    list.querySelectorAll('.dash-presence-row').forEach(function (row, i) {
      var num = row.querySelector('.presence-now__num');
      if (num) num.textContent = String(i + 1).padStart(2, '0');
    });
  }

  function bindRemove(btn) {
    btn.addEventListener('click', function () {
      var rows = list.querySelectorAll('.dash-presence-row');
      if (rows.length <= 1) {
        var input = rows[0].querySelector('input');
        if (input) input.value = '';
        return;
      }
      btn.closest('.dash-presence-row').remove();
      renumber();
    });
  }

  list.querySelectorAll('.dash-presence-remove').forEach(bindRemove);

  addBtn.addEventListener('click', function () {
    var li = document.createElement('li');
    li.className = 'presence-now__editor-row dash-presence-row';
    li.innerHTML =
      '<span class="presence-now__num" aria-hidden="true">01</span>' +
      '<input type="text" class="form-control form-control-sm presence-now__input" name="names[]" value="" placeholder="' + placeholder.replace(/"/g, '&quot;') + '" maxlength="191">' +
      '<button type="button" class="presence-now__remove dash-presence-remove" title="' + removeLabel.replace(/"/g, '&quot;') + '" aria-label="' + removeLabel.replace(/"/g, '&quot;') + '"><i class="mdi mdi-close" aria-hidden="true"></i></button>';
    list.appendChild(li);
    bindRemove(li.querySelector('.dash-presence-remove'));
    renumber();
    var input = li.querySelector('input');
    if (input) input.focus();
  });
})();
</script>
<?php endif; ?>
