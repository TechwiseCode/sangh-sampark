<?php
$ageDropdownWrapId = (string) ($ageDropdownWrapId ?? '');
$ageDropdownToggleId = (string) ($ageDropdownToggleId ?? '');
if ($ageDropdownWrapId === '' || $ageDropdownToggleId === '') {
    return;
}
?>
<script>
(function () {
  var wrap = document.getElementById(<?php echo json_encode($ageDropdownWrapId); ?>);
  if (!wrap) return;
  var toggle = document.getElementById(<?php echo json_encode($ageDropdownToggleId); ?>);
  var menu = wrap.querySelector('.members-age-dropdown-menu');
  var labelEl = wrap.querySelector('.members-age-dropdown-label');
  var allLabel = <?php echo json_encode(t('members.filter_all_short')); ?>;
  var nSelectedTemplate = <?php echo json_encode(t('members.filter_age_n_selected', ['count' => ':count'])); ?>;

  function updateLabel() {
    if (!labelEl) return;
    var checked = wrap.querySelectorAll('input[type="checkbox"][name="age[]"]:checked');
    if (checked.length === 0) {
      labelEl.textContent = allLabel;
      return;
    }
    if (checked.length === 1) {
      labelEl.textContent = checked[0].getAttribute('data-age-label') || checked[0].value;
      return;
    }
    labelEl.textContent = nSelectedTemplate.replace(':count', String(checked.length));
  }

  function closeMenu() {
    if (!menu || !toggle) return;
    menu.classList.add('d-none');
    wrap.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
  }

  if (!toggle || !menu) return;

  toggle.addEventListener('click', function (e) {
    e.preventDefault();
    if (menu.classList.contains('d-none')) {
      menu.classList.remove('d-none');
      wrap.classList.add('is-open');
      toggle.setAttribute('aria-expanded', 'true');
    } else {
      closeMenu();
    }
  });

  wrap.querySelectorAll('input[type="checkbox"][name="age[]"]').forEach(function (input) {
    input.addEventListener('change', updateLabel);
  });

  document.addEventListener('click', function (e) {
    if (!wrap.contains(e.target)) {
      closeMenu();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeMenu();
    }
  });
})();
</script>
