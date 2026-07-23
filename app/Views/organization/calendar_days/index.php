<?php
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$b = base_url();
$days = isset($days) && is_array($days) ? $days : [];
$dayId = isset($dayId) ? (int) $dayId : 0;
$dayCount = count($days);

$categoryBadgeClass = static function (string $cat): string {
    if ($cat === 'holiday') {
        return 'calendar-days-badge--holiday';
    }
    if ($cat === 'paryushan') {
        return 'calendar-days-badge--paryushan';
    }
    if ($cat === 'religious') {
        return 'calendar-days-badge--religious';
    }
    if ($cat === 'vyakhyan') {
        return 'calendar-days-badge--vyakhyan';
    }
    if ($cat === 'pratikraman') {
        return 'calendar-days-badge--pratikraman';
    }

    return 'calendar-days-badge--other';
};
?>
<div class="calendar-days-page">
  <div class="calendar-days-page-header">
    <div>
      <h3 class="mb-1"><?php echo h(t('calendar_days.org.title')); ?></h3>
      <p class="text-muted mb-0 small"><?php echo h(t('calendar_days.org.subtitle')); ?></p>
    </div>
  </div>

  <?php if ($flashOk): ?>
    <div class="alert alert-success mt-3 mb-0"><?php echo htmlspecialchars($flashOk); ?></div>
  <?php endif; ?>
  <?php if ($flashErr): ?>
    <div class="alert alert-danger mt-3 mb-0"><?php echo htmlspecialchars($flashErr); ?></div>
  <?php endif; ?>

  <div class="row calendar-days-split">
    <div class="col-lg-7 calendar-days-list-col">
      <div class="card calendar-days-list-card">
        <div class="card-body">
          <div class="calendar-days-list-head">
            <h4 class="calendar-days-list-title mb-0"><?php echo h(t('calendar_days.org.list_heading')); ?></h4>
            <span class="calendar-days-list-count text-muted small"><?php echo (int) $dayCount; ?></span>
          </div>

          <?php if ($days === []): ?>
            <div class="calendar-days-empty">
              <p class="text-muted mb-0"><?php echo h(t('calendar_days.org.none')); ?></p>
            </div>
          <?php else: ?>
            <ul class="calendar-days-list">
              <?php foreach ($days as $row): ?>
                <?php
                $itemId = (int) ($row['id'] ?? 0);
                $cat = normalize_org_calendar_day_category((string) ($row['category'] ?? ''));
                $start = (string) ($row['start_date'] ?? '');
                $end = (string) ($row['end_date'] ?? '');
                $range = $start === $end ? format_pretty_date($start) : format_pretty_date($start) . ' – ' . format_pretty_date($end);
                $notes = trim((string) ($row['notes'] ?? ''));
                $isActive = $dayId > 0 && $dayId === $itemId;
                ?>
                <li class="calendar-days-item<?php echo $isActive ? ' is-active' : ''; ?>">
                  <a class="calendar-days-item-main" href="<?php echo htmlspecialchars($b); ?>/organization/calendar-days?edit=<?php echo $itemId; ?>">
                    <div class="calendar-days-item-top">
                      <strong class="calendar-days-item-title"><?php echo htmlspecialchars(platform_holiday_display_title($row)); ?></strong>
                      <span class="calendar-days-badge <?php echo htmlspecialchars($categoryBadgeClass($cat)); ?>"><?php echo htmlspecialchars(org_calendar_day_category_label($cat)); ?></span>
                    </div>
                    <?php if (trim((string) ($row['title_gu'] ?? '')) !== '' && current_locale() !== 'gu'): ?>
                      <span class="calendar-days-item-sub text-muted"><?php echo htmlspecialchars((string) $row['title_gu']); ?></span>
                    <?php endif; ?>
                    <span class="calendar-days-item-date">
                      <i class="mdi mdi-calendar-blank-outline" aria-hidden="true"></i>
                      <?php echo htmlspecialchars($range); ?>
                    </span>
                    <?php if (org_calendar_day_shows_event_time($cat) && !empty($row['event_time'])): ?>
                      <span class="calendar-days-item-date">
                        <i class="mdi mdi-clock-outline" aria-hidden="true"></i>
                        <?php echo htmlspecialchars(format_org_calendar_event_time((string) $row['event_time'])); ?>
                      </span>
                    <?php endif; ?>
                    <?php if ($notes !== ''): ?>
                      <span class="calendar-days-item-notes text-muted"><?php echo htmlspecialchars(mb_strimwidth($notes, 0, 100, '…')); ?></span>
                    <?php endif; ?>
                  </a>
                  <div class="calendar-days-item-actions">
                    <a class="btn btn-link btn-sm p-0" href="<?php echo htmlspecialchars($b); ?>/organization/calendar-days?edit=<?php echo $itemId; ?>"><?php echo h(t('common.edit')); ?></a>
                    <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/calendar-days/delete" class="d-inline" onsubmit="return confirm(<?php echo json_encode(t('calendar_days.org.delete_confirm')); ?>);">
                      <input type="hidden" name="id" value="<?php echo $itemId; ?>">
                      <button type="submit" class="btn btn-link btn-sm text-danger p-0 ml-2"><?php echo h(t('common.delete')); ?></button>
                    </form>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-5 calendar-days-form-col">
      <?php require BASE_PATH . '/app/Views/partials/org_calendar_day_form.php'; ?>
    </div>
  </div>
</div>
