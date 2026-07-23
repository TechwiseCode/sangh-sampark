<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
?>
<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center border-bottom flex-wrap">
    <h3 class="mb-0"><?php echo htmlspecialchars(t('family.title')); ?></h3>
    <?php if (!empty($canManageOrg)): ?>
      <a class="btn btn-success mb-2" href="<?php echo htmlspecialchars($b); ?>/organization/families/new"><?php echo htmlspecialchars(t('family.new')); ?></a>
    <?php endif; ?>
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
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle family-main-table">
            <thead>
              <tr>
                <th><?php echo htmlspecialchars(t('family.id')); ?></th>
                <th><?php echo htmlspecialchars(t('family.head')); ?></th>
                <th class="text-right"><?php echo htmlspecialchars(t('common.actions')); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($families)): ?>
                <tr><td colspan="3" class="text-muted"><?php echo htmlspecialchars(t('common.none_yet')); ?><?php echo !empty($canManageOrg) ? htmlspecialchars(t('common.add_one')) : ''; ?></td></tr>
              <?php else: ?>
                <?php foreach ($families as $f): ?>
                  <?php
                    $memberRows = isset($f['members']) && is_array($f['members']) ? $f['members'] : [];
                    $headName = user_display_name($f);
                    $headParts = person_name_parts_from_row([
                        'name' => (string) ($f['head_name'] ?? ''),
                        'first_name' => $f['head_first_name'] ?? null,
                        'middle_name' => $f['head_middle_name'] ?? null,
                        'last_name' => $f['head_last_name'] ?? null,
                    ]);
                    $headEmail = (string) ($f['head_email'] ?? '');
                    $headPhoneRaw = preg_replace('/\D+/', '', (string) ($f['head_phone'] ?? ''));
                    $headPhoneDisplay = $headPhoneRaw;
                    if (strlen($headPhoneRaw) === 12 && strpos($headPhoneRaw, '91') === 0) {
                      $headPhoneDisplay = substr($headPhoneRaw, 2);
                    }
                    $accordionId = 'family-members-' . (int) $f['id'];
                  ?>
                  <tr>
                    <td><?php echo (int) $f['id']; ?></td>
                    <td>
                      <button type="button" class="btn btn-link p-0 js-toggle-family" data-target="<?php echo htmlspecialchars($accordionId); ?>" aria-expanded="false">
                        <?php echo htmlspecialchars($headName); ?>
                      </button>
                    </td>
                    <td class="text-right">
                      <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($b); ?>/organization/family?id=<?php echo (int) $f['id']; ?>"><?php echo htmlspecialchars(t('common.open')); ?></a>
                      <?php if (!empty($canManageOrg)): ?>
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-primary js-open-member-edit-modal"
                          data-modal-id="headEditModal"
                          data-family-id="<?php echo (int) $f['id']; ?>"
                          data-user-id="<?php echo (int) ($f['head_user_id'] ?? 0); ?>"
                          data-first-name="<?php echo htmlspecialchars((string) $headParts['first_name'], ENT_QUOTES); ?>"
                          data-middle-name="<?php echo htmlspecialchars((string) ($headParts['middle_name'] ?? ''), ENT_QUOTES); ?>"
                          data-last-name="<?php echo htmlspecialchars((string) $headParts['last_name'], ENT_QUOTES); ?>"
                          data-name="<?php echo htmlspecialchars($headName, ENT_QUOTES); ?>"
                          data-email="<?php echo htmlspecialchars($headEmail, ENT_QUOTES); ?>"
                          data-phone="<?php echo htmlspecialchars((string) $headPhoneDisplay, ENT_QUOTES); ?>"
                        ><?php echo h(t('family.index.edit_head')); ?></button>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <tr id="<?php echo htmlspecialchars($accordionId); ?>" class="d-none">
                    <td colspan="3" class="bg-light">
                      <?php if ($memberRows === []): ?>
                        <span class="text-muted"><?php echo h(t('family.index.no_members')); ?></span>
                      <?php else: ?>
                        <div class="table-responsive">
                          <table class="table table-sm table-bordered mb-0 family-members-table">
                            <thead>
                              <tr>
                                <th><?php echo h(t('family.index.col_name')); ?></th>
                                <th><?php echo h(t('family.index.col_email')); ?></th>
                                <th><?php echo h(t('family.index.col_phone')); ?></th>
                                <th><?php echo h(t('family.index.col_role')); ?></th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($memberRows as $m): ?>
                                <?php
                                  $memberRole = trim((string) ($m['role'] ?? ''));
                                  $isHeadRole = strcasecmp($memberRole, 'head') === 0;
                                ?>
                                <tr>
                                  <td><?php echo htmlspecialchars((string) ($m['name'] ?? '')); ?></td>
                                  <td><?php echo htmlspecialchars((string) (($m['email'] ?? '') ?: '—')); ?></td>
                                  <td><?php echo htmlspecialchars((string) (($m['phone'] ?? '') ?: '—')); ?></td>
                                  <td class="<?php echo $isHeadRole ? 'family-members-table__head-role' : ''; ?>"><span class="badge badge-light"><?php echo htmlspecialchars($memberRole); ?></span></td>
                                </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
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

<?php if (!empty($canManageOrg)): ?>
  <?php
  $memberEditModalId = 'headEditModal';
  $memberEditModalTitle = t('family.index.edit_head_title');
  $memberEditReturnUrl = $b . '/organization/families';
  require BASE_PATH . '/app/Views/partials/member_basic_edit_modal.php';
  ?>
<?php endif; ?>

<script>
(function () {
  document.querySelectorAll('.js-toggle-family').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var targetId = btn.getAttribute('data-target') || '';
      var row = document.getElementById(targetId);
      if (!row) return;
      var isHidden = row.classList.contains('d-none');
      row.classList.toggle('d-none');
      btn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
    });
  });
})();
</script>
<style>
.family-main-table > tbody > tr > td {
  vertical-align: top;
}
.family-members-table thead th {
  background: #f8f9fa;
}
.family-members-table .btn {
  min-width: 68px;
}
@media (max-width: 767.98px) {
  .family-members-table__head-role {
    display: none !important;
  }
}
</style>
