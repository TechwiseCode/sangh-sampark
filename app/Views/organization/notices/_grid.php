<?php
/** @var list<array<string,mixed>> $notices */
/** @var bool $inactiveSection */
$b = base_url();
$inactiveSection = !empty($inactiveSection);
?>

<?php if ($notices === []): ?>
  <div class="notices-empty">
    <p class="text-muted mb-0"><?php echo h($inactiveSection ? 'notices.none_deactivated' : 'notices.none'); ?></p>
  </div>
<?php else: ?>
  <div class="notices-grid" role="list">
    <?php foreach ($notices as $row): ?>
      <?php
      $itemId = (int) ($row['id'] ?? 0);
      $mime = (string) ($row['mime_type'] ?? '');
      $isPdf = $mime === 'application/pdf';
      $isPinned = !empty($row['is_pinned']);
      $fileUrl = $b . '/organization/notices/file?id=' . $itemId;
      $downloadUrl = $fileUrl . '&download=1';
      $description = trim((string) ($row['description'] ?? ''));
      $title = (string) ($row['title'] ?? '');
      ?>

      <div class="notices-grid-item" role="listitem">
        <div class="card notices-grid-card<?php echo $inactiveSection ? ' notices-grid-card--inactive' : ''; ?>">
          <div class="card-body notices-grid-card-body">
            <div class="notices-grid-thumb-wrap">
              <?php if ($isPdf): ?>
                <div class="notices-thumb notices-thumb--pdf" aria-hidden="true">
                  <canvas class="notices-thumb__canvas" data-pdf-url="<?php echo htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></canvas>
                  <span class="notices-thumb__loading"><?php echo h('notices.thumb_loading'); ?></span>
                </div>
              <?php else: ?>
                <div class="notices-thumb notices-thumb--doc" aria-hidden="true">
                  <i class="mdi mdi-file-word-box-outline"></i>
                  <span><?php echo htmlspecialchars(org_notice_file_type_label($mime)); ?></span>
                </div>
              <?php endif; ?>
            </div>

            <div class="notices-grid-body">
              <div class="d-flex align-items-start justify-content-between gap-2">
                <h5 class="notices-grid-title mb-0"><?php echo htmlspecialchars($title); ?></h5>
                <div class="notices-grid-badges">
                  <?php if ($inactiveSection): ?>
                    <span class="notices-badge notices-badge--inactive"><?php echo h('notices.deactivated_label'); ?></span>
                  <?php elseif ($isPinned): ?>
                    <span class="notices-badge notices-badge--pinned"><?php echo h('notices.pinned_label'); ?></span>
                  <?php endif; ?>
                  <span class="notices-badge notices-badge--type"><?php echo htmlspecialchars(org_notice_file_type_label($mime)); ?></span>
                </div>
              </div>

              <?php if ($description !== ''): ?>
                <div class="text-muted small mt-2 notices-grid-desc">
                  <?php echo nl2br(htmlspecialchars(mb_strimwidth($description, 0, 180, '…'))); ?>
                </div>
              <?php endif; ?>

              <div class="notices-grid-meta text-muted small mt-2">
                <span><i class="mdi mdi-calendar-outline" aria-hidden="true"></i> <?php echo htmlspecialchars(format_pretty_date((string) ($row['created_at'] ?? ''))); ?></span>
                <?php if (!empty($row['uploaded_by_name'])): ?>
                  <span><i class="mdi mdi-account-outline" aria-hidden="true"></i> <?php echo htmlspecialchars((string) $row['uploaded_by_name']); ?></span>
                <?php endif; ?>
                <span><i class="mdi mdi-file-outline" aria-hidden="true"></i> <?php echo htmlspecialchars(format_file_size((int) ($row['file_size_bytes'] ?? 0))); ?></span>
              </div>

              <div class="notices-grid-actions mt-3">
                <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($downloadUrl); ?>">
                  <?php echo h('notices.download'); ?>
                </a>

                <?php if (!$inactiveSection): ?>
                  <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/notices/pin" class="d-inline">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo $itemId; ?>">
                    <input type="hidden" name="is_pinned" value="<?php echo $isPinned ? '0' : '1'; ?>">
                    <button type="submit" class="btn btn-sm btn-link px-1" title="<?php echo h($isPinned ? 'notices.unpin' : 'notices.pin'); ?>">
                      <i class="mdi <?php echo $isPinned ? 'mdi-pin-off-outline' : 'mdi-pin-outline'; ?>"></i>
                    </button>
                  </form>
                <?php endif; ?>
                <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/notices/active" class="d-inline"<?php echo $inactiveSection ? '' : ' onsubmit="return confirm(' . json_encode(t('notices.deactivate_confirm')) . ');"'; ?>>
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo $itemId; ?>">
                  <input type="hidden" name="is_active" value="<?php echo $inactiveSection ? '1' : '0'; ?>">
                  <button type="submit" class="btn btn-sm btn-link px-1<?php echo $inactiveSection ? '' : ' text-warning'; ?>" title="<?php echo h($inactiveSection ? 'notices.activate' : 'notices.deactivate'); ?>">
                    <i class="mdi <?php echo $inactiveSection ? 'mdi-eye-outline' : 'mdi-eye-off-outline'; ?>"></i>
                  </button>
                </form>
                <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/notices/delete" class="d-inline" onsubmit="return confirm(<?php echo json_encode(t('notices.delete_confirm')); ?>);">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo $itemId; ?>">
                  <button type="submit" class="btn btn-sm btn-link text-danger px-1" title="<?php echo h('common.delete'); ?>">
                    <i class="mdi mdi-trash-can-outline"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
