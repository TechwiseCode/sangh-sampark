<?php
$b = base_url();
$dashNotices = isset($dashNotices) && is_array($dashNotices) ? $dashNotices : [];
$canManageOrg = !empty($canManageOrg);
$hasDashPdf = false;
foreach ($dashNotices as $row) {
    if ((string) ($row['mime_type'] ?? '') === 'application/pdf') {
        $hasDashPdf = true;
        break;
    }
}
?>

<section class="dash-notices" aria-label="<?php echo h('notices.title'); ?>">
  <div class="dash-notices__frame">
    <div class="dash-notices__head">
      <h2 class="dash-notices__title mb-0"><?php echo h('notices.title'); ?></h2>
      <?php if ($canManageOrg): ?>
        <a class="dash-notices__all" href="<?php echo htmlspecialchars($b); ?>/organization/notices"><?php echo h('notices.manage'); ?></a>
      <?php endif; ?>
    </div>

    <?php if ($dashNotices === []): ?>
      <p class="dash-notices__empty text-muted mb-0"><?php echo h('notices.none'); ?></p>
    <?php else: ?>
      <div class="dash-notices__scroller" role="list" id="dashNoticesScroller">
        <?php foreach ($dashNotices as $row): ?>
          <?php
          $itemId = (int) ($row['id'] ?? 0);
          $mime = (string) ($row['mime_type'] ?? '');
          $isPdf = $mime === 'application/pdf';
          $isPinned = !empty($row['is_pinned']);
          $fileUrl = $b . '/organization/notices/file?id=' . $itemId;
          $downloadUrl = $fileUrl . '&download=1';
          $title = (string) ($row['title'] ?? '');
          ?>
          <button
            type="button"
            class="dash-notices__card"
            role="listitem"
            data-file-url="<?php echo htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8'); ?>"
            data-download-url="<?php echo htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8'); ?>"
            data-mime="<?php echo htmlspecialchars($mime, ENT_QUOTES, 'UTF-8'); ?>"
            data-title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
            title="<?php echo htmlspecialchars($title); ?>"
          >
            <div class="dash-notices__thumb">
              <?php if ($isPdf): ?>
                <div class="notices-thumb notices-thumb--pdf dash-notices__preview" aria-hidden="true">
                  <canvas
                    class="notices-thumb__canvas"
                    data-pdf-url="<?php echo htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8'); ?>"
                    data-thumb-w="100"
                    data-thumb-h="130"
                    aria-hidden="true"
                  ></canvas>
                  <span class="notices-thumb__loading"><?php echo h('notices.thumb_loading'); ?></span>
                </div>
              <?php else: ?>
                <div class="notices-thumb notices-thumb--doc dash-notices__preview" aria-hidden="true">
                  <i class="mdi mdi-file-word-box-outline"></i>
                  <span><?php echo htmlspecialchars(org_notice_file_type_label($mime)); ?></span>
                </div>
              <?php endif; ?>
            </div>
            <div class="dash-notices__meta">
              <?php if ($isPinned): ?>
                <span class="dash-notices__pin"><?php echo h('notices.pinned_label'); ?></span>
              <?php endif; ?>
              <span class="dash-notices__name"><?php echo htmlspecialchars($title); ?></span>
            </div>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="notices-viewer" id="dashNoticesModal" hidden role="dialog" aria-modal="true" aria-labelledby="dashNoticesModalTitle">
        <div class="notices-viewer__backdrop" data-dash-notices-close></div>
        <div class="notices-viewer__dialog">
          <div class="notices-viewer__head">
            <h3 class="notices-viewer__title mb-0" id="dashNoticesModalTitle"></h3>
            <div class="notices-viewer__head-actions">
              <a class="btn btn-sm btn-outline-secondary" id="dashNoticesModalDownload" href="#" download><?php echo h('notices.download'); ?></a>
              <button type="button" class="btn btn-sm btn-link notices-viewer__close px-2" data-dash-notices-close aria-label="<?php echo h('notices.close_viewer'); ?>">
                <i class="mdi mdi-close" aria-hidden="true"></i>
              </button>
            </div>
          </div>
          <iframe class="notices-viewer__frame" id="dashNoticesModalFrame" title="<?php echo h('notices.preview_title'); ?>" hidden></iframe>
          <div class="notices-viewer__doc" id="dashNoticesModalDoc" hidden>
            <i class="mdi mdi-file-word-box-outline" aria-hidden="true"></i>
            <p class="mb-2"><?php echo h('notices.preview_word'); ?></p>
            <a class="btn btn-sm btn-primary" id="dashNoticesModalDocDownload" href="#"><?php echo h('notices.download'); ?></a>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($hasDashPdf): ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" crossorigin="anonymous"></script>
<?php endif; ?>
<script>window.NoticeBoardI18n = { thumbError: <?php echo json_encode(t('notices.thumb_error')); ?> };</script>
<script src="<?php echo htmlspecialchars(asset_url('themes/js/notice-board.js')); ?>?v=<?php echo is_file(BASE_PATH . '/themes/js/notice-board.js') ? (string) filemtime(BASE_PATH . '/themes/js/notice-board.js') : '1'; ?>"></script>
