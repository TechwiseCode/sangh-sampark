<?php
$b = base_url();
$month = (string) ($calendarMonth ?? date('Y-m'));
$items = isset($calendarItems) && is_array($calendarItems) ? $calendarItems : [];
$panchang = isset($calendarPanchang) && is_array($calendarPanchang) ? $calendarPanchang : [];
$todayPanchang = isset($calendarTodayPanchang) && is_array($calendarTodayPanchang) ? $calendarTodayPanchang : null;
$todayItems = isset($calendarTodayItems) && is_array($calendarTodayItems) ? $calendarTodayItems : [];
$monthNames = [];
for ($m = 1; $m <= 12; $m++) {
    $monthNames[$m] = t('calendar.month_' . $m);
}
$dayLabels = [
    t('calendar.day_sun'),
    t('calendar.day_mon'),
    t('calendar.day_tue'),
    t('calendar.day_wed'),
    t('calendar.day_thu'),
    t('calendar.day_fri'),
    t('calendar.day_sat'),
];
$parts = explode('-', $month);
$year = (int) ($parts[0] ?? (int) date('Y'));
$monthNum = (int) ($parts[1] ?? (int) date('m'));
$monthTitle = ($monthNames[$monthNum] ?? $month) . ' ' . $year;
$canManageOrg = !empty($canManageOrg);
?>
<section class="dash-calendar-section">
  <div class="card org-calendar-card">
    <div class="card-body">
      <div class="org-calendar-header">
        <h2 class="org-calendar-heading"><?php echo h('calendar.title'); ?></h2>
        <div class="org-calendar-header-actions">
          <?php if ($canManageOrg): ?>
            <a class="btn btn-sm btn-outline-primary org-calendar-manage-btn" href="<?php echo htmlspecialchars($b); ?>/organization/calendar-days"><?php echo h(t('calendar_days.org.manage')); ?></a>
          <?php endif; ?>
        <div class="calendar-legend">
          <span class="calendar-legend__item"><span class="calendar-dot calendar-dot--event"></span><?php echo h('calendar.legend_event'); ?></span>
          <span class="calendar-legend__item"><span class="calendar-dot calendar-dot--occasion"></span><?php echo h('calendar.legend_occasion'); ?></span>
          <span class="calendar-legend__item"><span class="calendar-dot calendar-dot--scheme"></span><?php echo h('calendar.legend_scheme'); ?></span>
          <span class="calendar-legend__item"><span class="calendar-dot calendar-dot--holiday"></span><?php echo h('calendar.legend_holiday'); ?></span>
          <span class="calendar-legend__item"><span class="calendar-dot calendar-dot--festival"></span><?php echo h('calendar.legend_festival'); ?></span>
          <span class="calendar-legend__item"><span class="calendar-dot calendar-dot--org_day"></span><?php echo h('calendar.legend_org_day'); ?></span>
          <?php if ($canManageOrg): ?>
          <span class="calendar-legend__item"><span class="calendar-dot calendar-dot--birthday"></span><?php echo h('calendar.legend_birthday'); ?></span>
          <?php endif; ?>
        </div>
        </div>
      </div>

      <div id="dash-cal-today-tithi" class="org-calendar-today-tithi">
        <h3 class="org-calendar-today-tithi__title"><?php echo h(t('calendar.today')); ?></h3>
        <div class="org-calendar-today-tithi__body">
          <div class="org-calendar-today-tithi__date" id="dash-cal-today-tithi-date"></div>
          <div class="org-calendar-today-tithi__summary" id="dash-cal-today-tithi-summary"></div>
          <div class="org-calendar-today-tithi__festival text-muted small" id="dash-cal-today-tithi-festival" hidden></div>
          <p class="org-calendar-today-tithi__empty text-muted small mb-0" id="dash-cal-today-tithi-empty" hidden><?php echo h(t('calendar.today_tithi_empty')); ?></p>
        </div>
        <div class="org-calendar-today-occasions" id="dash-cal-today-occasions">
          <h4 class="org-calendar-today-occasions__title"><?php echo h(t('calendar.today_scheduled')); ?></h4>
          <ul class="org-calendar-today-occasions__list" id="dash-cal-today-occasions-list"></ul>
          <p class="org-calendar-today-occasions__empty text-muted small mb-0" id="dash-cal-today-occasions-empty" hidden><?php echo h(t('calendar.today_scheduled_empty')); ?></p>
        </div>
      </div>

      <div class="org-calendar-toolbar">
        <div class="btn-group btn-group-sm">
          <button type="button" class="btn btn-outline-secondary" id="dash-cal-prev" aria-label="<?php echo h('calendar.prev_month'); ?>">&lsaquo;</button>
          <button type="button" class="btn btn-outline-secondary" id="dash-cal-today"><?php echo h('calendar.today'); ?></button>
          <button type="button" class="btn btn-outline-secondary" id="dash-cal-next" aria-label="<?php echo h('calendar.next_month'); ?>">&rsaquo;</button>
        </div>
        <h4 class="mb-0 org-calendar-month-title" id="dash-cal-month-title"><?php echo htmlspecialchars($monthTitle); ?></h4>
      </div>

      <div class="org-calendar-grid" aria-label="<?php echo h('calendar.title'); ?>">
        <?php foreach ($dayLabels as $dl): ?>
          <div class="org-calendar-weekday"><?php echo htmlspecialchars($dl); ?></div>
        <?php endforeach; ?>
        <div class="org-calendar-cells" id="dash-cal-cells"></div>
      </div>

      <p class="org-calendar-day-hint text-muted small mb-0" id="dash-cal-day-hint"><?php echo h('calendar.click_day_hint'); ?></p>
      <div id="dash-cal-day-panel" class="org-calendar-day-panel" hidden>
        <h5 class="org-calendar-day-panel__title" id="dash-cal-day-title"></h5>
        <ul class="org-calendar-day-panel__list list-group list-group-flush" id="dash-cal-day-list"></ul>
      </div>
      <p class="text-muted small mb-0 mt-3" id="dash-cal-empty-hint"><?php echo h('calendar.empty_month'); ?></p>
    </div>
  </div>
</section>

<script>
(function () {
  var base = <?php echo json_encode($b); ?>;
  var month = <?php echo json_encode($month); ?>;
  var items = <?php echo json_encode($items, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  var panchang = <?php echo json_encode($panchang, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  var todayPanchang = <?php echo json_encode($todayPanchang, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  var todayItems = <?php echo json_encode($todayItems, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  var monthNames = <?php echo json_encode($monthNames, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  var showBirthdays = <?php echo $canManageOrg ? 'true' : 'false'; ?>;
  var dotTypes = showBirthdays
    ? ['event', 'occasion', 'scheme', 'holiday', 'festival', 'org_day', 'birthday']
    : ['event', 'occasion', 'scheme', 'holiday', 'festival', 'org_day'];
  var labels = {
    noItems: <?php echo json_encode(t('calendar.day_empty')); ?>,
    typeEvent: <?php echo json_encode(t('calendar.type_event')); ?>,
    typeOccasion: <?php echo json_encode(t('calendar.type_occasion')); ?>,
    typeScheme: <?php echo json_encode(t('calendar.type_scheme')); ?>,
    typeBirthday: <?php echo json_encode(t('calendar.type_birthday')); ?>,
    typeHoliday: <?php echo json_encode(t('calendar.type_holiday')); ?>,
    typeFestival: <?php echo json_encode(t('calendar.type_festival')); ?>,
    typeOrgDay: <?php echo json_encode(t('calendar.type_org_day')); ?>,
    panchangFestival: <?php echo json_encode(t('calendar.panchang_festival')); ?>,
    catHoliday: <?php echo json_encode(t('calendar.holiday_cat_holiday')); ?>,
    catParyushan: <?php echo json_encode(t('calendar.holiday_cat_paryushan')); ?>,
    catReligious: <?php echo json_encode(t('calendar.holiday_cat_religious')); ?>,
    catVyakhyan: <?php echo json_encode(t('calendar.holiday_cat_vyakhyan')); ?>,
    catPratikraman: <?php echo json_encode(t('calendar.holiday_cat_pratikraman')); ?>,
    schemeRange: <?php echo json_encode(t('calendar.scheme_until')); ?>
  };

  var grid = document.getElementById('dash-cal-cells');
  var monthTitleEl = document.getElementById('dash-cal-month-title');
  var dayPanel = document.getElementById('dash-cal-day-panel');
  var dayHint = document.getElementById('dash-cal-day-hint');
  var dayTitle = document.getElementById('dash-cal-day-title');
  var dayList = document.getElementById('dash-cal-day-list');
  var emptyHint = document.getElementById('dash-cal-empty-hint');
  var todayTithiDate = document.getElementById('dash-cal-today-tithi-date');
  var todayTithiSummary = document.getElementById('dash-cal-today-tithi-summary');
  var todayTithiFestival = document.getElementById('dash-cal-today-tithi-festival');
  var todayTithiEmpty = document.getElementById('dash-cal-today-tithi-empty');
  var todayOccasionsList = document.getElementById('dash-cal-today-occasions-list');
  var todayOccasionsEmpty = document.getElementById('dash-cal-today-occasions-empty');
  var todayOccasionsWrap = document.getElementById('dash-cal-today-occasions');
  var selectedDay = null;

  function actualTodayKey() {
    var now = new Date();
    return now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
  }

  function isCompactPanchang() {
    return window.matchMedia('(max-width: 575.98px)').matches;
  }

  function renderTodayTithiBox() {
    var row = todayPanchang;
    if (!row || !row.summary) {
      if (todayTithiDate) todayTithiDate.textContent = formatDayLabel(actualTodayKey());
      if (todayTithiSummary) todayTithiSummary.textContent = '';
      if (todayTithiFestival) {
        todayTithiFestival.textContent = '';
        todayTithiFestival.hidden = true;
      }
      if (todayTithiEmpty) todayTithiEmpty.hidden = false;
      return;
    }
    if (todayTithiEmpty) todayTithiEmpty.hidden = true;
    if (todayTithiDate) {
      todayTithiDate.textContent = formatDayLabel(row.gregorian_date || actualTodayKey());
    }
    if (todayTithiSummary) {
      todayTithiSummary.textContent = row.summary;
    }
    if (todayTithiFestival) {
      var fest = (row.festival_notes || '').trim();
      if (fest) {
        todayTithiFestival.textContent = fest;
        todayTithiFestival.hidden = false;
      } else {
        todayTithiFestival.textContent = '';
        todayTithiFestival.hidden = true;
      }
    }
  }

  function todayItemSortKey(item) {
    if (item && item.time) {
      return '0-' + item.time;
    }
    return '1-' + (item && item.title ? item.title : '');
  }

  function renderTodayOccasions() {
    if (!todayOccasionsList) return;
    var list = Array.isArray(todayItems) ? todayItems.slice() : [];
    list.sort(function (a, b) {
      var cmp = todayItemSortKey(a).localeCompare(todayItemSortKey(b));
      if (cmp !== 0) return cmp;
      return typeLabel(a.type, a).localeCompare(typeLabel(b.type, b));
    });
    todayOccasionsList.innerHTML = '';
    if (!list.length) {
      if (todayOccasionsEmpty) todayOccasionsEmpty.hidden = false;
      if (todayOccasionsWrap) todayOccasionsWrap.classList.add('is-empty');
      return;
    }
    if (todayOccasionsEmpty) todayOccasionsEmpty.hidden = true;
    if (todayOccasionsWrap) todayOccasionsWrap.classList.remove('is-empty');
    list.forEach(function (it) {
      var li = document.createElement('li');
      li.className = 'org-calendar-today-occasion';
      var isFestival = it.type === 'festival';
      var wrap = document.createElement(isFestival || !it.url ? 'div' : 'a');
      if (!isFestival && it.url) {
        wrap.href = it.url;
        wrap.className = 'org-calendar-today-occasion__link';
      } else {
        wrap.className = 'org-calendar-today-occasion__body';
      }
      var badge = document.createElement('span');
      badge.className = 'calendar-type-badge calendar-type-badge--' + (it.type || 'event');
      badge.textContent = typeLabel(it.type, it);
      wrap.appendChild(badge);
      var title = document.createElement('span');
      title.className = 'org-calendar-today-occasion__title';
      title.textContent = it.title || '';
      wrap.appendChild(title);
      if (it.meta) {
        var meta = document.createElement('span');
        meta.className = 'org-calendar-today-occasion__meta text-muted';
        meta.textContent = it.meta;
        wrap.appendChild(meta);
      }
      li.appendChild(wrap);
      todayOccasionsList.appendChild(li);
    });
  }

  function renderTodayBox() {
    renderTodayTithiBox();
    renderTodayOccasions();
  }

  function parseMonth(m) {
    var p = m.split('-');
    return { y: parseInt(p[0], 10), m: parseInt(p[1], 10) };
  }

  function formatMonth(y, m) {
    return y + '-' + String(m).padStart(2, '0');
  }

  function shiftMonth(m, delta) {
    var p = parseMonth(m);
    p.m += delta;
    while (p.m < 1) { p.m += 12; p.y -= 1; }
    while (p.m > 12) { p.m -= 12; p.y += 1; }
    return formatMonth(p.y, p.m);
  }

  function monthTitleText(m) {
    var p = parseMonth(m);
    return (monthNames[p.m] || m) + ' ' + p.y;
  }

  function daysInMonth(y, m) {
    return new Date(y, m, 0).getDate();
  }

  function itemsByDay(list) {
    var map = {};
    list.forEach(function (it) {
      var start = it.start;
      var end = it.end || start;
      var cur = new Date(start + 'T00:00:00');
      var last = new Date(end + 'T00:00:00');
      while (cur <= last) {
        var key = cur.getFullYear() + '-' + String(cur.getMonth() + 1).padStart(2, '0') + '-' + String(cur.getDate()).padStart(2, '0');
        if (!map[key]) map[key] = [];
        map[key].push(it);
        cur.setDate(cur.getDate() + 1);
      }
    });
    return map;
  }

  function typeLabel(type, item) {
    if (type === 'occasion') return labels.typeOccasion;
    if (type === 'scheme') return labels.typeScheme;
    if (type === 'birthday') return labels.typeBirthday;
    if (type === 'org_day') {
      if (item && item.category === 'paryushan') return labels.catParyushan;
      if (item && item.category === 'religious') return labels.catReligious;
      if (item && item.category === 'holiday') return labels.catHoliday;
      if (item && item.category === 'vyakhyan') return labels.catVyakhyan;
      if (item && item.category === 'pratikraman') return labels.catPratikraman;
      return labels.typeOrgDay;
    }
    if (type === 'holiday' && item && item.category === 'paryushan') return labels.catParyushan;
    if (type === 'holiday' && item && item.category === 'religious') return labels.catReligious;
    if (type === 'holiday') return labels.catHoliday;
    if (type === 'festival') return labels.typeFestival;
    return labels.typeEvent;
  }

  function formatDayLabel(dayKey) {
    var parts = dayKey.split('-');
    var y = parseInt(parts[0], 10);
    var m = parseInt(parts[1], 10);
    var d = parseInt(parts[2], 10);
    return (monthNames[m] || parts[1]) + ' ' + d + ', ' + y;
  }

  function renderGrid() {
    if (!grid) return;
    var p = parseMonth(month);
    var firstDow = new Date(p.y, p.m - 1, 1).getDay();
    var total = daysInMonth(p.y, p.m);
    var today = new Date();
    var todayKey = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
    var map = itemsByDay(items);
    grid.innerHTML = '';
    var pad = firstDow;
    while (pad > 0) {
      var blank = document.createElement('div');
      blank.className = 'org-calendar-cell org-calendar-cell--blank';
      grid.appendChild(blank);
      pad--;
    }
    for (var d = 1; d <= total; d++) {
      var key = formatMonth(p.y, p.m) + '-' + String(d).padStart(2, '0');
      var cell = document.createElement('button');
      cell.type = 'button';
      cell.className = 'org-calendar-cell';
      if (key === todayKey) cell.classList.add('is-today');
      if (key === selectedDay) cell.classList.add('is-selected');
      cell.setAttribute('data-day', key);
      var num = document.createElement('span');
      num.className = 'org-calendar-cell__num';
      num.textContent = String(d);
      cell.appendChild(num);
      var pRow = panchang[key];
      if (pRow && (pRow.summary || pRow.short_label) && !isCompactPanchang()) {
        var tithiEl = document.createElement('span');
        tithiEl.className = 'org-calendar-cell__tithi';
        var fullLabel = pRow.summary || pRow.short_label;
        tithiEl.setAttribute('title', fullLabel);
        var parts = fullLabel.split(/\s*·\s*/);
        if (parts.length > 1) {
          parts.forEach(function (part) {
            part = part.trim();
            if (!part) return;
            var line = document.createElement('span');
            line.className = 'org-calendar-cell__tithi-line';
            line.textContent = part;
            tithiEl.appendChild(line);
          });
        } else {
          tithiEl.textContent = fullLabel;
        }
        cell.appendChild(tithiEl);
      }
      var dayItems = map[key] || [];
      if (dayItems.some(function (it) { return it.type === 'festival'; })) {
        cell.classList.add('org-calendar-cell--festival');
      }
      if (dayItems.length) {
        var dots = document.createElement('span');
        dots.className = 'org-calendar-cell__dots';
        var types = {};
        dayItems.forEach(function (it) { types[it.type] = true; });
        dotTypes.forEach(function (tp) {
          if (!types[tp]) return;
          var dot = document.createElement('span');
          dot.className = 'calendar-dot calendar-dot--' + tp;
          dots.appendChild(dot);
        });
        cell.appendChild(dots);
      }
      cell.addEventListener('click', function () {
        var dayKey = this.getAttribute('data-day');
        if (!dayKey) return;
        selectedDay = dayKey;
        var dayMap = itemsByDay(items);
        renderGrid();
        renderDayPanel(dayKey, dayMap[dayKey] || []);
      });
      grid.appendChild(cell);
    }
    if (monthTitleEl) monthTitleEl.textContent = monthTitleText(month);
    if (emptyHint) emptyHint.style.display = (items.length || Object.keys(panchang).length) ? 'none' : '';
  }

  function renderPanchangBlock(day) {
    var pRow = panchang[day];
    if (!pRow || !pRow.summary) return null;
    var wrap = document.createElement('div');
    wrap.className = 'org-calendar-panchang-block';
    var summary = document.createElement('p');
    summary.className = 'org-calendar-panchang-block__summary mb-1';
    summary.textContent = pRow.summary;
    wrap.appendChild(summary);
    return wrap;
  }

  function renderDayPanel(day, dayItems) {
    if (!dayPanel || !dayTitle || !dayList) return;
    dayTitle.textContent = formatDayLabel(day);
    dayList.innerHTML = '';
    var pBlock = renderPanchangBlock(day);
    if (pBlock) {
      var pLi = document.createElement('li');
      pLi.className = 'list-group-item px-0 border-0 org-calendar-panchang-item';
      pLi.appendChild(pBlock);
      dayList.appendChild(pLi);
    }
    if (!dayItems.length) {
      if (!pBlock) {
        var li = document.createElement('li');
        li.className = 'list-group-item text-muted border-0 px-0';
        li.textContent = labels.noItems;
        dayList.appendChild(li);
      }
    } else {
      dayItems.forEach(function (it) {
        var li = document.createElement('li');
        li.className = 'list-group-item px-0';
        var isFestival = it.type === 'festival';
        var wrap = document.createElement(isFestival ? 'div' : 'a');
        if (isFestival) {
          wrap.className = 'org-calendar-day-item';
        } else {
          wrap.href = it.url || '#';
          wrap.className = 'org-calendar-day-link';
        }
        var badge = document.createElement('span');
        badge.className = 'calendar-type-badge calendar-type-badge--' + (it.type || 'event');
        badge.textContent = typeLabel(it.type, it);
        wrap.appendChild(badge);
        var title = document.createElement('span');
        title.className = 'org-calendar-day-link__title';
        title.textContent = it.title || '';
        wrap.appendChild(title);
        if (it.type === 'scheme' && it.end && it.end !== it.start) {
          var small = document.createElement('small');
          small.className = 'text-muted d-block mt-1';
          small.textContent = labels.schemeRange.replace('{end}', it.end);
          wrap.appendChild(small);
        } else if (it.meta) {
          var meta = document.createElement('small');
          meta.className = 'text-muted d-block mt-1';
          meta.textContent = it.meta;
          wrap.appendChild(meta);
        }
        li.appendChild(wrap);
        dayList.appendChild(li);
      });
    }
    dayPanel.hidden = false;
    if (dayHint) dayHint.hidden = true;
  }

  function loadMonth(m) {
    month = m;
    selectedDay = null;
    if (dayPanel) dayPanel.hidden = true;
    if (dayHint) dayHint.hidden = false;
    fetch(base + '/organization/calendar/feed?month=' + encodeURIComponent(m), {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' }
    })
      .then(function (r) { return r.json(); })
      .then(function (body) {
        if (body && body.ok && Array.isArray(body.items)) {
          items = body.items;
        }
        if (body && body.ok && body.panchang && typeof body.panchang === 'object') {
          panchang = body.panchang;
        }
        if (body && body.ok && body.todayPanchang) {
          todayPanchang = body.todayPanchang;
        }
        if (body && body.ok && Array.isArray(body.todayItems)) {
          todayItems = body.todayItems;
        }
        renderTodayBox();
        renderGrid();
      })
      .catch(function () { renderGrid(); });
    if (window.history && window.history.replaceState) {
      window.history.replaceState({}, '', base + '/organization/dashboard?month=' + encodeURIComponent(m));
    }
  }

  document.getElementById('dash-cal-prev')?.addEventListener('click', function () { loadMonth(shiftMonth(month, -1)); });
  document.getElementById('dash-cal-next')?.addEventListener('click', function () { loadMonth(shiftMonth(month, 1)); });
  document.getElementById('dash-cal-today')?.addEventListener('click', function () {
    var now = new Date();
    loadMonth(formatMonth(now.getFullYear(), now.getMonth() + 1));
  });

  window.addEventListener('resize', function () {
    renderGrid();
  });

  renderTodayBox();
  renderGrid();
})();
</script>
