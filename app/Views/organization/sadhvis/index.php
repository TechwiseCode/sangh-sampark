<?php
$b = base_url();
$rows = isset($rows) && is_array($rows) ? $rows : [];
$sanghOptions = isset($sanghOptions) && is_array($sanghOptions) ? $sanghOptions : [];
$filterQ = trim((string) ($filterQ ?? ''));
$filterSanghId = (int) ($filterSanghId ?? 0);
$selectedSangh = isset($selectedSangh) && is_array($selectedSangh) ? $selectedSangh : null;
$count = count($rows);
$hasFilters = $filterQ !== '' || $filterSanghId > 0;
$isSanghView = $filterSanghId > 0 && $selectedSangh !== null;
$sanghName = $isSanghView ? (string) ($selectedSangh['name'] ?? '') : '';
$sanghNickname = $isSanghView ? trim((string) ($selectedSangh['nickname'] ?? '')) : '';
$sanghAddress = $isSanghView ? trim((string) ($selectedSangh['address'] ?? '')) : '';
$sanghMapsUrl = $isSanghView ? trim((string) ($selectedSangh['maps_url'] ?? '')) : '';
?>
<div class="sadhvis-page">
  <header class="sadhvis-hero">
    <div class="sadhvis-hero__glow" aria-hidden="true"></div>
    <div class="sadhvis-hero__content">
      <span class="sadhvis-hero__mark" aria-hidden="true"><i class="mdi mdi-spa"></i></span>
      <div>
        <p class="sadhvis-hero__eyebrow"><?php echo h('sadhvis.hero_eyebrow'); ?></p>
        <h1 class="sadhvis-hero__title"><?php echo h('sadhvis.title'); ?></h1>
        <p class="sadhvis-hero__subtitle"><?php echo h('sadhvis.subtitle'); ?></p>
      </div>
    </div>
    <div class="sadhvis-hero__stat<?php echo (!$isSanghView && $count > 0) ? '' : ' d-none'; ?>" id="sadhvis-hero-stat">
      <strong id="sadhvis-hero-stat-count"><?php echo (int) $count; ?></strong>
      <span id="sadhvis-hero-stat-label"><?php echo h($hasFilters ? t('sadhvis.stat_matching') : t('sadhvis.stat_present')); ?></span>
    </div>
  </header>

  <form method="get" action="<?php echo htmlspecialchars($b); ?>/organization/sadhvis" class="sadhvis-filters" id="sadhvis-filters" role="search">
    <div class="sadhvis-filters__row">
      <div class="sadhvis-filter-field sadhvis-filter-field--sangh">
        <label for="sadhvis_sangh"><?php echo h('sadhvis.filter_sangh'); ?></label>
        <select id="sadhvis_sangh" name="sangh_id" class="form-control sadhvis-control">
          <option value="0"<?php echo $filterSanghId === 0 ? ' selected' : ''; ?>><?php echo h('sadhvis.filter_sangh_all'); ?></option>
          <?php foreach ($sanghOptions as $opt): ?>
            <?php $oid = (int) ($opt['id'] ?? 0); ?>
            <option value="<?php echo $oid; ?>"<?php echo $filterSanghId === $oid ? ' selected' : ''; ?>>
              <?php echo htmlspecialchars((string) ($opt['name'] ?? '')); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="sadhvis-filter-field sadhvis-filter-field--q">
        <label for="sadhvis_q"><?php echo h('sadhvis.search_label'); ?></label>
        <div class="sadhvis-search-wrap">
          <i class="mdi mdi-magnify sadhvis-search-icon" aria-hidden="true"></i>
          <input
            type="search"
            class="form-control sadhvis-search-input sadhvis-control"
            id="sadhvis_q"
            name="q"
            value="<?php echo htmlspecialchars($filterQ); ?>"
            placeholder="<?php echo h('sadhvis.search_placeholder'); ?>"
            autocomplete="off"
            spellcheck="false"
            enterkeyhint="search"
          >
          <span class="sadhvis-search-spinner d-none" id="sadhvis-search-spinner" aria-hidden="true"></span>
        </div>
      </div>
      <div class="sadhvis-filter-actions">
        <button type="submit" class="btn btn-primary sadhvis-search-btn sadhvis-control">
          <i class="mdi mdi-magnify" aria-hidden="true"></i>
          <?php echo h('sadhvis.search_btn'); ?>
        </button>
        <a href="<?php echo htmlspecialchars($b); ?>/organization/sadhvis" class="btn btn-link sadhvis-reset<?php echo $hasFilters ? '' : ' d-none'; ?>" id="sadhvis-reset"><?php echo h('common.reset'); ?></a>
      </div>
    </div>
  </form>

  <div id="sadhvis-results" class="sadhvis-results" aria-live="polite">
  <?php if ($rows === []): ?>
    <div class="sadhvis-empty">
      <i class="mdi mdi-account-search-outline" aria-hidden="true"></i>
      <p><?php echo h($hasFilters ? t('sadhvis.none_filtered') : t('sadhvis.none')); ?></p>
    </div>
  <?php elseif ($isSanghView): ?>
    <section class="sadhvis-sheet">
      <header class="sadhvis-sheet__header">
        <div class="sadhvis-sheet__badge" aria-hidden="true"><i class="mdi mdi-home-heart"></i></div>
        <div class="sadhvis-sheet__intro">
          <p class="sadhvis-sheet__eyebrow"><?php echo h('sadhvis.group_eyebrow'); ?></p>
          <h2 class="sadhvis-sheet__org"><?php echo htmlspecialchars($sanghName); ?></h2>
          <?php if ($sanghNickname !== '' && strcasecmp($sanghNickname, $sanghName) !== 0): ?>
            <p class="sadhvis-sheet__nick"><?php echo htmlspecialchars($sanghNickname); ?></p>
          <?php endif; ?>
          <?php if ($sanghAddress !== ''): ?>
            <p class="sadhvis-sheet__addr">
              <i class="mdi mdi-map-marker-outline" aria-hidden="true"></i>
              <span><?php echo nl2br(htmlspecialchars($sanghAddress)); ?></span>
            </p>
          <?php endif; ?>
          <?php if ($sanghMapsUrl !== ''): ?>
            <div class="sadhvis-sheet__nav">
              <?php echo maps_navigate_button($sanghMapsUrl, ['class' => 'maps-nav-btn maps-nav-btn--primary']); ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="sadhvis-sheet__aside">
          <span class="sadhvis-sheet__meta"><?php echo h('sadhvis.group_count', ['count' => (string) $count]); ?></span>
        </div>
      </header>

      <ol class="sadhvis-roster">
        <?php foreach ($rows as $i => $row): ?>
          <li class="sadhvis-roster__item" style="--roster-i: <?php echo (int) $i; ?>">
            <span class="sadhvis-roster__index" aria-hidden="true"><?php echo str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT); ?></span>
            <span class="sadhvis-roster__name"><?php echo htmlspecialchars((string) ($row['display_name'] ?? '')); ?></span>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>
  <?php else: ?>
    <div class="sadhvis-gallery">
      <?php foreach ($rows as $i => $row): ?>
        <?php
        $name = (string) ($row['display_name'] ?? '');
        $orgName = (string) ($row['organization_name'] ?? '');
        $orgNickname = trim((string) ($row['organization_nickname'] ?? ''));
        $address = trim((string) ($row['organization_address'] ?? ''));
        $mapsUrl = trim((string) ($row['organization_maps_url'] ?? ''));
        $orgId = (int) ($row['organization_id'] ?? 0);
        $placeLabel = $orgNickname !== '' ? $orgNickname : $orgName;
        ?>
        <article class="sadhvis-tile" style="--tile-i: <?php echo (int) $i; ?>">
          <div class="sadhvis-tile__head">
            <div class="sadhvis-tile__avatar" aria-hidden="true">
              <?php echo htmlspecialchars(mb_strtoupper(mb_substr($name !== '' ? $name : 'S', 0, 1))); ?>
            </div>
            <h3 class="sadhvis-tile__name"><?php echo htmlspecialchars($name); ?></h3>
          </div>
          <div class="sadhvis-tile__body">
            <div class="sadhvis-tile__place">
              <i class="mdi mdi-home-outline" aria-hidden="true"></i>
              <?php if ($orgId > 0): ?>
                <a href="<?php echo htmlspecialchars($b); ?>/organization/sadhvis?sangh_id=<?php echo $orgId; ?>" class="sadhvis-tile__org-link">
                  <?php echo htmlspecialchars($placeLabel); ?>
                </a>
              <?php else: ?>
                <span><?php echo htmlspecialchars($placeLabel); ?></span>
              <?php endif; ?>
            </div>
            <?php if ($address !== ''): ?>
              <p class="sadhvis-tile__addr"><?php echo nl2br(htmlspecialchars($address)); ?></p>
            <?php endif; ?>
            <?php if ($mapsUrl !== ''): ?>
              <div class="sadhvis-tile__nav">
                <?php echo maps_navigate_button($mapsUrl, ['class' => 'maps-nav-btn maps-nav-btn--soft']); ?>
              </div>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  </div>
</div>
<script>
(function () {
  var form = document.getElementById('sadhvis-filters');
  var input = document.getElementById('sadhvis_q');
  var sangh = document.getElementById('sadhvis_sangh');
  var results = document.getElementById('sadhvis-results');
  var spinner = document.getElementById('sadhvis-search-spinner');
  var resetBtn = document.getElementById('sadhvis-reset');
  var heroStat = document.getElementById('sadhvis-hero-stat');
  var heroCount = document.getElementById('sadhvis-hero-stat-count');
  var heroLabel = document.getElementById('sadhvis-hero-stat-label');
  if (!form || !input || !sangh || !results) return;

  var base = <?php echo json_encode($b); ?>;
  var searchUrl = base + '/organization/sadhvis/search';
  var timer = null;
  var reqId = 0;
  var abortCtrl = null;

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function nl2br(s) {
    return esc(s).replace(/\n/g, '<br>');
  }

  function pad2(n) {
    return String(n).padStart(2, '0');
  }

  function initialOf(name) {
    var t = String(name || '').trim();
    return t ? t.charAt(0).toUpperCase() : 'S';
  }

  function groupCountLabel(tpl, count) {
    return String(tpl || '').replace('{count}', String(count));
  }

  function navBtn(url, cls, labels) {
    if (!url) return '';
    return '<a class="' + esc(cls) + '" href="' + esc(url) + '" target="_blank" rel="noopener noreferrer" aria-label="' + esc(labels.navigate_aria) + '" title="' + esc(labels.navigate_aria) + '">' +
      '<i class="mdi mdi-navigation" aria-hidden="true"></i><span>' + esc(labels.navigate) + '</span></a>';
  }

  function renderEmpty(labels, filtered) {
    return '<div class="sadhvis-empty"><i class="mdi mdi-account-search-outline" aria-hidden="true"></i><p>' +
      esc(filtered ? labels.none_filtered : labels.none) + '</p></div>';
  }

  function renderSangh(data) {
    var sangh = data.selected_sangh || {};
    var rows = data.rows || [];
    var labels = data.labels || {};
    var name = sangh.name || '';
    var nick = sangh.nickname || '';
    var addr = sangh.address || '';
    var maps = sangh.maps_url || '';
    var html = '<section class="sadhvis-sheet"><header class="sadhvis-sheet__header">' +
      '<div class="sadhvis-sheet__badge" aria-hidden="true"><i class="mdi mdi-home-heart"></i></div>' +
      '<div class="sadhvis-sheet__intro">' +
      '<p class="sadhvis-sheet__eyebrow">' + esc(labels.group_eyebrow) + '</p>' +
      '<h2 class="sadhvis-sheet__org">' + esc(name) + '</h2>';
    if (nick && nick.toLowerCase() !== name.toLowerCase()) {
      html += '<p class="sadhvis-sheet__nick">' + esc(nick) + '</p>';
    }
    if (addr) {
      html += '<p class="sadhvis-sheet__addr"><i class="mdi mdi-map-marker-outline" aria-hidden="true"></i><span>' + nl2br(addr) + '</span></p>';
    }
    if (maps) {
      html += '<div class="sadhvis-sheet__nav">' + navBtn(maps, 'maps-nav-btn maps-nav-btn--primary', labels) + '</div>';
    }
    html += '</div><div class="sadhvis-sheet__aside"><span class="sadhvis-sheet__meta">' +
      esc(groupCountLabel(labels.group_count, rows.length)) + '</span></div></header><ol class="sadhvis-roster">';
    rows.forEach(function (row, i) {
      html += '<li class="sadhvis-roster__item" style="--roster-i:' + i + '">' +
        '<span class="sadhvis-roster__index" aria-hidden="true">' + pad2(i + 1) + '</span>' +
        '<span class="sadhvis-roster__name">' + esc(row.display_name) + '</span></li>';
    });
    html += '</ol></section>';
    return html;
  }

  function renderGallery(data) {
    var rows = data.rows || [];
    var labels = data.labels || {};
    var html = '<div class="sadhvis-gallery">';
    rows.forEach(function (row, i) {
      var name = row.display_name || '';
      var orgName = row.organization_name || '';
      var nick = (row.organization_nickname || '').trim();
      var place = nick || orgName;
      var addr = (row.organization_address || '').trim();
      var maps = (row.organization_maps_url || '').trim();
      var orgId = parseInt(row.organization_id, 10) || 0;
      html += '<article class="sadhvis-tile" style="--tile-i:' + i + '">' +
        '<div class="sadhvis-tile__head">' +
        '<div class="sadhvis-tile__avatar" aria-hidden="true">' + esc(initialOf(name)) + '</div>' +
        '<h3 class="sadhvis-tile__name">' + esc(name) + '</h3></div>' +
        '<div class="sadhvis-tile__body">' +
        '<div class="sadhvis-tile__place"><i class="mdi mdi-home-outline" aria-hidden="true"></i>';
      if (orgId > 0) {
        html += '<a href="' + esc(base + '/organization/sadhvis?sangh_id=' + orgId) + '" class="sadhvis-tile__org-link">' + esc(place) + '</a>';
      } else {
        html += '<span>' + esc(place) + '</span>';
      }
      html += '</div>';
      if (addr) html += '<p class="sadhvis-tile__addr">' + nl2br(addr) + '</p>';
      if (maps) html += '<div class="sadhvis-tile__nav">' + navBtn(maps, 'maps-nav-btn maps-nav-btn--soft', labels) + '</div>';
      html += '</div></article>';
    });
    html += '</div>';
    return html;
  }

  function updateChrome(data) {
    var q = (data.q || '').trim();
    var sanghId = parseInt(data.sangh_id, 10) || 0;
    var count = parseInt(data.count, 10) || 0;
    var labels = data.labels || {};
    var filtered = q !== '' || sanghId > 0;
    if (resetBtn) resetBtn.classList.toggle('d-none', !filtered);
    if (heroStat && heroCount && heroLabel) {
      var showStat = sanghId === 0 && count > 0;
      heroStat.classList.toggle('d-none', !showStat);
      heroCount.textContent = String(count);
      heroLabel.textContent = filtered ? (labels.stat_matching || '') : (labels.stat_present || '');
    }
    try {
      var url = new URL(window.location.href);
      if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
      if (sanghId > 0) url.searchParams.set('sangh_id', String(sanghId)); else url.searchParams.delete('sangh_id');
      window.history.replaceState({}, '', url.pathname + url.search);
    } catch (e) {}
  }

  function setLoading(on) {
    if (spinner) spinner.classList.toggle('d-none', !on);
    input.classList.toggle('is-searching', !!on);
  }

  function runSearch() {
    var q = input.value.trim();
    var sanghId = parseInt(sangh.value, 10) || 0;
    var myId = ++reqId;
    if (abortCtrl) {
      try { abortCtrl.abort(); } catch (e) {}
    }
    abortCtrl = (typeof AbortController !== 'undefined') ? new AbortController() : null;
    setLoading(true);
    var url = searchUrl + '?q=' + encodeURIComponent(q) + '&sangh_id=' + encodeURIComponent(String(sanghId));
    var opts = { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } };
    if (abortCtrl) opts.signal = abortCtrl.signal;
    fetch(url, opts)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (myId !== reqId) return;
        if (!data || !data.ok) return;
        updateChrome(data);
        var filtered = (data.q || '').trim() !== '' || (parseInt(data.sangh_id, 10) || 0) > 0;
        if (!data.rows || data.rows.length === 0) {
          results.innerHTML = renderEmpty(data.labels || {}, filtered);
        } else if (data.selected_sangh) {
          results.innerHTML = renderSangh(data);
        } else {
          results.innerHTML = renderGallery(data);
        }
      })
      .catch(function (err) {
        if (err && err.name === 'AbortError') return;
      })
      .finally(function () {
        if (myId === reqId) setLoading(false);
      });
  }

  function scheduleSearch() {
    if (timer) clearTimeout(timer);
    timer = setTimeout(runSearch, 180);
  }

  input.addEventListener('input', scheduleSearch);
  input.addEventListener('search', scheduleSearch);
  sangh.addEventListener('change', runSearch);

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (timer) clearTimeout(timer);
    runSearch();
  });
})();
</script>
