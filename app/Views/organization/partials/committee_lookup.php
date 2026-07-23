<?php
$b = base_url();
$canManageOrg = !empty($canManageOrg);
$committeeMembers = isset($committeeMembers) && is_array($committeeMembers) ? $committeeMembers : [];
$committeeCount = count($committeeMembers);
$committeeLookupPart = (string) ($committeeLookupPart ?? 'all');
$showButton = $committeeLookupPart === 'all' || $committeeLookupPart === 'button';
$showModal = $committeeLookupPart === 'all' || $committeeLookupPart === 'modal';
?>
<?php if ($showButton): ?>
<button type="button" class="dash-action dash-action--committee" id="dashCommitteeOpen" aria-haspopup="dialog" aria-controls="dashCommitteeModal">
  <i class="mdi mdi-account-tie dash-action-icon" aria-hidden="true"></i>
  <span class="dash-action-label"><?php echo h(t('committee.title')); ?></span>
  <?php if ($committeeCount > 0): ?>
    <span class="dash-committee-btn__count"><?php echo (int) $committeeCount; ?></span>
  <?php endif; ?>
</button>
<?php endif; ?>

<?php if ($showModal): ?>
<div class="dash-committee-modal" id="dashCommitteeModal" hidden role="dialog" aria-modal="true" aria-labelledby="dashCommitteeModalTitle">
  <div class="dash-committee-modal__backdrop" data-committee-close tabindex="-1"></div>
  <div class="dash-committee-modal__panel">
    <div class="dash-committee-modal__head">
      <div>
        <h2 class="dash-committee-modal__title" id="dashCommitteeModalTitle"><?php echo h(t('committee.title')); ?></h2>
        <p class="dash-committee-modal__sub text-muted mb-0"><?php echo h(t('committee.modal_subtitle')); ?></p>
      </div>
      <button type="button" class="dash-committee-modal__close" data-committee-close aria-label="<?php echo h(t('common.cancel')); ?>">&times;</button>
    </div>

    <div class="dash-committee-modal__body">
      <?php if ($committeeMembers === []): ?>
        <p class="text-muted mb-0"><?php echo h(t($canManageOrg ? 'committee.empty_admin' : 'committee.empty_member')); ?></p>
      <?php else: ?>
        <ul class="dash-committee-list">
          <?php foreach ($committeeMembers as $row): ?>
            <?php
            $name = trim((string) ($row['person_name'] ?? ''));
            if ($name === '') {
                $name = trim((string) ($row['user_name'] ?? ''));
            }
            $designation = committee_designation_label((string) ($row['designation_key'] ?? ''));
            $phone = trim((string) ($row['user_phone'] ?? ''));
            $photoUrl = user_photo_url(isset($row['photo_path']) ? (string) $row['photo_path'] : null);
            $initials = user_photo_initials($name !== '' ? $name : '?');
            ?>
            <li class="dash-committee-item">
              <div class="dash-committee-item__avatar<?php echo $photoUrl !== null ? ' has-photo' : ''; ?>" aria-hidden="true"<?php if ($photoUrl !== null): ?> style="background-image:url('<?php echo htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8'); ?>')"<?php endif; ?>>
                <?php if ($photoUrl === null): ?>
                  <span><?php echo htmlspecialchars($initials); ?></span>
                <?php endif; ?>
              </div>
              <div class="dash-committee-item__body">
                <strong class="dash-committee-item__name"><?php echo htmlspecialchars($name); ?></strong>
                <span class="dash-committee-item__role"><?php echo htmlspecialchars($designation); ?></span>
                <?php if ($phone !== ''): ?>
                  <a class="dash-committee-item__phone" href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $phone) ?? $phone); ?>">
                    <i class="mdi mdi-phone-outline" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($phone); ?>
                  </a>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <?php if ($canManageOrg): ?>
      <div class="dash-committee-modal__foot">
        <a class="btn btn-sm btn-primary" href="<?php echo htmlspecialchars($b); ?>/organization/committee">
          <?php echo h(t($committeeCount > 0 ? 'committee.manage' : 'committee.add')); ?>
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
(function () {
  var openBtn = document.getElementById('dashCommitteeOpen');
  var modal = document.getElementById('dashCommitteeModal');
  if (!openBtn || !modal) return;

  function openModal() {
    modal.hidden = false;
    document.body.classList.add('dash-committee-open');
  }

  function closeModal() {
    modal.hidden = true;
    document.body.classList.remove('dash-committee-open');
    if (location.hash === '#committee') {
      history.replaceState(null, '', location.pathname + location.search);
    }
  }

  openBtn.addEventListener('click', openModal);
  modal.querySelectorAll('[data-committee-close]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.hidden) closeModal();
  });
  if (location.hash === '#committee') openModal();
})();
</script>
<?php endif; ?>
