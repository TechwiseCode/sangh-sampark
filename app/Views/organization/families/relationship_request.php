<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$fid = (int) $familyId;
$roleOptions = [
    'head' => t('family.show.role_head'),
    'wife' => t('family.show.role_wife'),
    'husband' => t('family.show.role_husband'),
    'son' => t('family.show.role_son'),
    'daughter' => t('family.show.role_daughter'),
    'mother' => t('family.show.role_mother'),
    'father' => t('family.show.role_father'),
    'daughter-in-law' => t('family.show.role_daughter_in_law'),
    'son-in-law' => t('family.show.role_son_in_law'),
    'brother' => t('family.show.role_brother'),
    'sister' => t('family.show.role_sister'),
    'other' => t('family.show.role_other'),
];
?>
<div class="row">
  <div class="col-12 d-flex justify-content-between align-items-center border-bottom flex-wrap">
    <div>
      <h3 class="mb-0"><?php echo htmlspecialchars(t('family.rel.change_title')); ?> · <?php echo htmlspecialchars(t('dashboard.family_num')); ?><?php echo $fid; ?></h3>
    </div>
    <a class="btn btn-light mb-2" href="<?php echo htmlspecialchars($b); ?>/organization/family?id=<?php echo $fid; ?>"><?php echo htmlspecialchars(t('family.rel.back_link')); ?></a>
  </div>
</div>
<?php if ($flashOk): ?>
  <div class="alert alert-success mt-3"><?php echo htmlspecialchars($flashOk); ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
  <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($flashErr); ?></div>
<?php endif; ?>
<div class="row" style="padding-top: 16px;">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3"><?php echo htmlspecialchars((string) $targetUser['name']); ?></h5>
        <dl class="row small mb-0">
          <dt class="col-sm-3 text-muted"><?php echo htmlspecialchars(t('family.rel.email_label')); ?></dt>
          <dd class="col-sm-9 mb-2"><?php echo htmlspecialchars((string) ($targetUser['email'] ?? '—')); ?></dd>
          <dt class="col-sm-3 text-muted"><?php echo htmlspecialchars(t('family.rel.phone_label')); ?></dt>
          <dd class="col-sm-9 mb-2"><?php echo htmlspecialchars((string) ($targetUser['phone'] ?? '—')); ?></dd>
          <dt class="col-sm-3 text-muted"><?php echo htmlspecialchars(t('family.rel.current_role_label')); ?></dt>
          <dd class="col-sm-9 mb-0"><?php echo htmlspecialchars((string) $currentMembership['role']); ?></dd>
        </dl>
      </div>
    </div>
    <div class="card mt-3">
      <div class="card-body">
        <h4 class="card-title"><?php echo htmlspecialchars(t('family.rel.new_role_title')); ?></h4>
        <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/family/relationship-request">
          <input type="hidden" name="family_id" value="<?php echo $fid; ?>">
          <input type="hidden" name="target_user_id" value="<?php echo (int) $targetUser['id']; ?>">
          <div class="form-group">
            <label for="role"><?php echo htmlspecialchars(t('family.rel.role_label')); ?></label>
            <select class="form-control" name="role" id="role" required>
              <?php foreach ($roleOptions as $val => $label): ?>
                <?php if ($val === 'head' && empty($canAddHead)) { continue; } ?>
                <option value="<?php echo htmlspecialchars($val); ?>"><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" id="related-group">
            <label for="related_to_user_id"><?php echo htmlspecialchars(t('family.rel.related_to_label')); ?></label>
            <select class="form-control" name="related_to_user_id" id="related_to_user_id">
              <option value=""><?php echo htmlspecialchars(t('family.rel.related_to_placeholder')); ?></option>
              <?php foreach ($members as $m): ?>
                <option value="<?php echo (int) $m['user_id']; ?>">
                  <?php echo htmlspecialchars((string) $m['user_name']); ?> (<?php echo htmlspecialchars((string) $m['role']); ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted"><?php echo htmlspecialchars(t('family.rel.related_to_help')); ?></small>
          </div>
          <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(t('family.rel.send_button')); ?></button>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  function roleSync() {
    var sel = document.getElementById('role');
    var isHead = sel && sel.value === 'head';
    var rel = document.getElementById('related_to_user_id');
    var grp = document.getElementById('related-group');
    if (rel) { rel.required = !isHead; }
    if (grp) { grp.style.display = isHead ? 'none' : 'block'; }
  }
  document.getElementById('role').addEventListener('change', roleSync);
  roleSync();
})();
</script>
