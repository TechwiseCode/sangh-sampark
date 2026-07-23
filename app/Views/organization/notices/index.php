<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$notices = isset($notices) && is_array($notices) ? $notices : [];
$deactivatedNotices = isset($deactivatedNotices) && is_array($deactivatedNotices) ? $deactivatedNotices : [];
$canManageOrg = true;
$noticeCount = count($notices);
$hasPdf = false;
foreach (array_merge($notices, $deactivatedNotices) as $row) {
    if ((string) ($row['mime_type'] ?? '') === 'application/pdf') {
        $hasPdf = true;
        break;
    }
}
?>

<div class="notices-page">
  <div class="notices-page-header">
    <div>
      <h3 class="mb-0"><?php echo h('notices.title'); ?></h3>
    </div>
    <span class="notices-page-count"><?php echo (int) $noticeCount; ?></span>
  </div>

  <?php if ($flashOk): ?>
    <div class="alert alert-success mt-3 mb-0"><?php echo htmlspecialchars($flashOk); ?></div>
  <?php endif; ?>
  <?php if ($flashErr): ?>
    <div class="alert alert-danger mt-3 mb-0"><?php echo htmlspecialchars($flashErr); ?></div>
  <?php endif; ?>

  <div class="row notices-split">
    <div class="col-lg-8 notices-list-col">
      <div class="card notices-list-card">
        <div class="card-body">
          <div class="notices-list-head">
            <h4 class="notices-list-title mb-0"><?php echo h('notices.list_heading'); ?></h4>
          </div>
          <?php
          $inactiveSection = false;
          include __DIR__ . '/_grid.php';
          ?>
        </div>
      </div>

      <div class="card notices-list-card notices-list-card--deactivated mt-3">
        <div class="card-body">
          <div class="notices-list-head">
            <h4 class="notices-list-title mb-0"><?php echo h('notices.deactivated_heading'); ?></h4>
          </div>
          <?php
          $notices = $deactivatedNotices;
          $inactiveSection = true;
          include __DIR__ . '/_grid.php';
          ?>
        </div>
      </div>
    </div>

    <div class="col-lg-4 notices-form-col">
      <div class="card notices-form-card">
        <div class="card-body">
          <h4 class="notices-form-title mb-3"><?php echo h('notices.upload_heading'); ?></h4>
          <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/notices" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <div class="form-group">
              <label for="notice_title"><?php echo h('notices.label_title'); ?></label>
              <input type="text" class="form-control" id="notice_title" name="title" maxlength="255" required placeholder="<?php echo h('notices.title_placeholder'); ?>">
            </div>
            <div class="form-group">
              <label for="notice_description"><?php echo h('notices.label_description'); ?></label>
              <textarea class="form-control" id="notice_description" name="description" rows="3" maxlength="2000" placeholder="<?php echo h('notices.description_placeholder'); ?>"></textarea>
            </div>
            <div class="form-group">
              <label for="notice_file"><?php echo h('notices.label_file'); ?></label>
              <input type="file" class="form-control-file" id="notice_file" name="notice_file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
              <small class="form-text text-muted"><?php echo h('notices.file_hint'); ?></small>
            </div>
            <div class="form-group form-check mb-3">
              <input type="checkbox" class="form-check-input" id="notice_pinned" name="is_pinned" value="1">
              <label class="form-check-label" for="notice_pinned"><?php echo h('notices.pin_on_upload'); ?></label>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?php echo h('notices.submit'); ?></button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($hasPdf): ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" crossorigin="anonymous"></script>
<?php endif; ?>
<script>window.NoticeBoardI18n = { thumbError: <?php echo json_encode(t('notices.thumb_error')); ?> };</script>
<script src="<?php echo htmlspecialchars(asset_url('themes/js/notice-board.js')); ?>?v=<?php echo is_file(BASE_PATH . '/themes/js/notice-board.js') ? (string) filemtime(BASE_PATH . '/themes/js/notice-board.js') : '1'; ?>"></script>
