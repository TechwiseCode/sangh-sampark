<?php
$b = base_url();
$event = isset($event) && is_array($event) ? $event : [];
$summary = isset($summary) && is_array($summary) ? $summary : null;
$canManageOrg = !empty($canManageOrg);
$passStats = isset($passStats) && is_array($passStats) ? $passStats : null;
$eventPasses = isset($eventPasses) && is_array($eventPasses) ? $eventPasses : [];
$isPerPerson = !empty($isPerPerson);
$title = (string) ($event['title'] ?? 'Event');
$eventId = (int) ($event['id'] ?? 0);
$rate = (float) ($event['amount'] ?? 0);
$fy = (string) ($event['financial_year'] ?? '');
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
?>
<div class="row">
  <div class="col-12 border-bottom d-flex justify-content-between align-items-center flex-wrap">
    <div>
      <h3 class="mb-0"><?php echo htmlspecialchars($title); ?></h3>
      <p class="text-muted small mb-0"><?php echo htmlspecialchars($fy); ?></p>
    </div>
    <div class="mb-2">
      <a class="btn btn-sm btn-outline-secondary mb-2" href="<?php echo htmlspecialchars($b); ?>/organization/events">&larr; <?php echo htmlspecialchars(t('events.all_events')); ?></a>
      <?php
        $whatsappShareMessage = event_whatsapp_share_message($event);
        require BASE_PATH . '/app/Views/partials/whatsapp_share_button.php';
      ?>
    </div>
  </div>
</div>
<?php if ($flashOk): ?>
  <div class="alert alert-success mt-3"><?php echo htmlspecialchars($flashOk); ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
  <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($flashErr); ?></div>
<?php endif; ?>
<div class="row event-show-page" style="padding-top: 16px;">
  <div class="col-12 event-show-wrap<?php echo $canManageOrg ? '' : ' event-show-wrap--member'; ?>">
    <div class="card mb-3 event-details-card">
      <div class="card-body py-3">
        <h4 class="card-title mb-3"><?php echo htmlspecialchars(t('events.event_details')); ?></h4>
        <dl class="row mb-0 small event-details-dl">
          <dt class="col-5 col-sm-4 col-md-3"><?php echo htmlspecialchars(t('events.ticket_price')); ?></dt>
          <dd class="col-7 col-sm-8 col-md-9">
            <?php echo htmlspecialchars(number_format($rate, 2)); ?>
            <?php echo $isPerPerson ? ' ' . t('events.per_person') : ' ' . t('events.per_family'); ?>
          </dd>
          <dt class="col-5 col-sm-4 col-md-3"><?php echo htmlspecialchars(t('events.type')); ?></dt>
          <dd class="col-7 col-sm-8 col-md-9"><?php echo !empty($event['is_compulsory']) ? htmlspecialchars(t('events.compulsory')) : htmlspecialchars(t('events.optional')); ?></dd>
          <?php if ($canManageOrg && $passStats !== null): ?>
            <dt class="col-5 col-sm-4 col-md-3"><?php echo htmlspecialchars(t('events.passes')); ?></dt>
            <dd class="col-7 col-sm-8 col-md-9">
              <span class="badge badge-success"><?php echo (int) ($passStats['active'] ?? 0); ?> <?php echo htmlspecialchars(t('common.active')); ?></span>
              <span class="badge badge-danger"><?php echo (int) ($passStats['redeemed'] ?? 0); ?> <?php echo htmlspecialchars(t('common.redeemed')); ?></span>
              <span class="text-muted">(<?php echo (int) ($passStats['total'] ?? 0); ?> <?php echo htmlspecialchars(t('events.total')); ?>)</span>
            </dd>
          <?php endif; ?>
        </dl>
      </div>
    </div>

    <?php if ($canManageOrg): ?>
      <div class="row">
        <div class="col-12 col-lg-5 col-xl-4 mb-3 mb-lg-0">
          <div class="card redeem-card h-100">
            <div class="card-body">
              <h4 class="card-title mb-1"><?php echo htmlspecialchars(t('events.redeem_pass')); ?></h4>
              <p class="text-muted small mb-3"><?php echo htmlspecialchars(t('events.redeem_hint')); ?> <span class="text-monospace">E5B</span></p>
              <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/event/redeem" id="redeemPassForm" class="redeem-form">
                <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                <input type="hidden" name="pass_id" id="pass_id" value="">
                <label class="sr-only" for="pass_search"><?php echo h(t('events.pass_search_label')); ?></label>
                <div class="redeem-search-row">
                  <input type="text" class="form-control redeem-search-input" id="pass_search" name="pass_code"
                    placeholder="···" maxlength="12" autocomplete="off" autofocus inputmode="text"
                    aria-describedby="passSearchHint">
                </div>
              </form>
              <p id="passSearchHint" class="redeem-hint text-muted small mb-2"><?php echo htmlspecialchars(t('events.type_3_chars')); ?></p>
              <div id="passSearchResults" class="pass-search-results" role="listbox" aria-label="Matching passes"></div>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-7 col-xl-8">
          <div class="card h-100 mb-3 mb-lg-0">
            <div class="card-body">
              <h4 class="card-title mb-3"><?php echo htmlspecialchars(t('events.all_passes')); ?></h4>
          <?php if ($eventPasses === []): ?>
            <p class="text-muted mb-0"><?php echo htmlspecialchars(t('events.no_passes_issued')); ?></p>
          <?php else: ?>
            <div class="pass-registry">
              <?php foreach ($eventPasses as $p): ?>
                <?php
                  $pst = (string) ($p['status'] ?? '');
                  $code = (string) ($p['pass_code'] ?? '');
                  $suffix = strlen($code) <= 3 ? $code : substr($code, -3);
                  $holder = (string) ($p['holder_name'] ?? '');
                  $head = (string) ($p['head_name'] ?? '');
                ?>
                <div class="pass-registry-item<?php echo $pst === 'redeemed' ? ' is-redeemed' : ''; ?>">
                  <div class="pass-registry-suffix" title="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($suffix); ?></div>
                  <div class="pass-registry-main">
                    <div class="pass-registry-holder"><?php echo htmlspecialchars($holder); ?></div>
                    <?php if ($head !== '' && $head !== $holder): ?>
                      <div class="pass-registry-head"><?php echo htmlspecialchars($head); ?></div>
                    <?php endif; ?>
                    <div class="pass-registry-dates">
                      <?php echo htmlspecialchars(t('events.issued')); ?> <?php echo htmlspecialchars(format_pretty_date(isset($p['issued_at']) ? (string) $p['issued_at'] : null)); ?>
                      <?php if ($pst === 'redeemed'): ?>
                        &middot; <?php echo htmlspecialchars(t('common.redeemed')); ?> <?php echo htmlspecialchars(format_pretty_datetime(isset($p['redeemed_at']) ? (string) $p['redeemed_at'] : null)); ?>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="pass-registry-status">
                    <?php if ($pst === 'active'): ?>
                      <span class="badge badge-success"><?php echo htmlspecialchars(t('common.active')); ?></span>
                      <div class="mt-1">
                        <?php
                          $whatsappShareMessage = event_pass_whatsapp_share_message($event, $p);
                          require BASE_PATH . '/app/Views/partials/whatsapp_share_button.php';
                        ?>
                      </div>
                    <?php elseif ($pst === 'redeemed'): ?>
                      <span class="badge badge-danger"><?php echo htmlspecialchars(t('common.redeemed')); ?></span>
                      <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/event/unredeem" class="pass-undo-form mt-1"
                        onsubmit="return confirm(<?php echo json_encode(t('events.undo_confirm')); ?>);">
                        <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                        <input type="hidden" name="pass_id" value="<?php echo (int) ($p['id'] ?? 0); ?>">
                        <button type="submit" class="btn btn-link btn-sm p-0 pass-undo-btn"><?php echo htmlspecialchars(t('events.undo')); ?></button>
                      </form>
                    <?php else: ?>
                      <span class="badge badge-light"><?php echo htmlspecialchars($pst); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php elseif ($summary === null): ?>
      <div class="alert alert-light"><?php echo htmlspecialchars(t('events.not_linked')); ?></div>
    <?php else: ?>
      <?php
        $passCount = (int) ($summary['pass_count'] ?? 0);
        $redeemedCount = (int) ($summary['redeemed_count'] ?? 0);
        $amountPaid = (float) ($summary['amount_paid'] ?? 0);
        $amountDue = (float) ($summary['amount_due'] ?? 0);
        $tickets = (int) ($summary['tickets_from_payment'] ?? 0);
        $passes = isset($summary['passes']) && is_array($summary['passes']) ? $summary['passes'] : [];
        $myHolderId = isset($summary['my_pass']['holder_user_id']) ? (int) $summary['my_pass']['holder_user_id'] : 0;
      ?>
      <div class="card mb-3 border-success">
        <div class="card-body text-center py-4">
          <p class="text-muted mb-1"><?php echo htmlspecialchars(t('events.household_passes')); ?></p>
          <p class="display-4 mb-1 text-success font-weight-bold"><?php echo $passCount; ?></p>
          <p class="small mb-0">
            <span class="badge badge-success"><?php echo htmlspecialchars(t('common.active')); ?></span>
            <?php if ($redeemedCount > 0): ?>
              &nbsp;&middot;&nbsp;
              <span class="badge badge-danger"><?php echo $redeemedCount; ?> <?php echo htmlspecialchars(t('common.redeemed')); ?></span>
            <?php endif; ?>
          </p>
          <?php if ($isPerPerson && $rate > 0): ?>
            <p class="small text-muted mb-0 mt-2">
              Paid <?php echo htmlspecialchars(number_format($amountPaid, 2)); ?>
              &divide; <?php echo htmlspecialchars(number_format($rate, 2)); ?>
              = <?php echo $tickets; ?> ticket<?php echo $tickets === 1 ? '' : 's'; ?>
            </p>
          <?php endif; ?>
        </div>
      </div>
      <div class="card">
        <div class="card-body">
          <h4 class="card-title"><?php echo htmlspecialchars(t('events.your_passes')); ?></h4>
          <?php if ($passes === []): ?>
            <p class="text-muted mb-0"><?php echo htmlspecialchars(t('events.no_passes_yet')); ?></p>
          <?php else: ?>
            <div class="pass-list">
              <?php foreach ($passes as $p): ?>
                <?php
                  $pst = (string) ($p['status'] ?? '');
                  $code = (string) ($p['pass_code'] ?? '');
                  $suffix = strlen($code) <= 3 ? $code : substr($code, -3);
                  $isMine = $myHolderId > 0 && (int) ($p['holder_user_id'] ?? 0) === $myHolderId;
                ?>
                <div class="pass-list-item<?php echo $isMine ? ' is-mine' : ''; ?><?php echo $pst === 'redeemed' ? ' is-redeemed' : ''; ?>">
                  <span class="pass-list-suffix"><?php echo htmlspecialchars($suffix); ?></span>
                  <span class="pass-list-name"><?php echo htmlspecialchars((string) ($p['holder_name'] ?? '')); ?></span>
                  <?php if ($pst === 'redeemed'): ?>
                    <span class="badge badge-danger badge-pill"><?php echo htmlspecialchars(t('common.redeemed')); ?></span>
                  <?php else: ?>
                    <span class="badge badge-success badge-pill"><?php echo htmlspecialchars(t('common.active')); ?></span>
                    <div class="mt-1">
                      <?php
                        $whatsappShareMessage = event_pass_whatsapp_share_message($event, $p);
                        require BASE_PATH . '/app/Views/partials/whatsapp_share_button.php';
                      ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
<style>
.event-show-wrap {
  max-width: 1100px;
  margin-left: auto;
  margin-right: auto;
}
.event-show-wrap--member {
  max-width: 640px;
}
.event-details-card .card-title { font-size: 1.05rem; }
.redeem-card {
  border: 1px solid rgba(52, 177, 170, 0.35);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}
.redeem-search-row {
  max-width: 20rem;
}
.redeem-search-input {
  width: 100%;
  max-width: 20rem;
  text-align: center;
  text-transform: uppercase;
  font-size: 1.35rem;
  font-weight: 600;
  letter-spacing: 0.28em;
  padding: 0.55rem 0.5rem;
  border: 2px solid #e9ecef;
  border-radius: 0.5rem;
}
.redeem-search-input:focus {
  border-color: #34B1AA;
  box-shadow: 0 0 0 0.15rem rgba(52, 177, 170, 0.2);
}
.redeem-hint { min-height: 1.25rem; }
.pass-search-results {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(8.75rem, 1fr));
  gap: 0.5rem;
  max-height: min(42vh, 280px);
  overflow-y: auto;
  padding: 0.15rem 0.1rem 0.25rem;
}
.pass-result {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  width: 100%;
  min-height: 7.5rem;
  padding: 0.65rem 0.4rem;
  margin: 0;
  border: 1px solid #e9ecef;
  border-radius: 0.5rem;
  background: #fafbfc;
  cursor: pointer;
  text-align: center;
  transition: border-color 0.15s, background 0.15s, transform 0.1s;
}
.pass-result:hover:not(.is-disabled) {
  border-color: #34B1AA;
  background: #f0faf9;
  transform: translateY(-1px);
}
.pass-result.is-selected {
  border-color: #34B1AA;
  background: #e8f7f6;
  box-shadow: 0 0 0 2px rgba(52, 177, 170, 0.2);
}
.pass-result.is-disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: #f5f5f5;
}
.pass-result-suffix {
  font-size: 1.5rem;
  font-weight: 700;
  letter-spacing: 0.18em;
  line-height: 1.2;
  color: #34B1AA;
}
.pass-result-code {
  font-size: 0.65rem;
  color: #868e96;
  margin-top: 0.2rem;
  word-break: break-all;
  line-height: 1.25;
  max-width: 100%;
}
.pass-result-names {
  font-size: 0.78rem;
  margin-top: 0.35rem;
  color: #343a40;
  line-height: 1.3;
  overflow: hidden;
  max-width: 100%;
}
.pass-result-names .holder {
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.pass-result-names .head {
  color: #868e96;
  font-size: 0.72rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.pass-result-names .redeemed-tag {
  color: #dc3545;
  font-weight: 600;
  font-size: 0.72rem;
}
.pass-registry {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-height: min(60vh, 480px);
  overflow-y: auto;
}
.pass-registry-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem 0.75rem;
  border: 1px solid #e9ecef;
  border-radius: 0.5rem;
  background: #fff;
}
.pass-registry-item.is-redeemed {
  background: #fafafa;
  border-color: #f1d0d4;
}
.pass-registry-suffix {
  flex: 0 0 3.25rem;
  font-size: 1.15rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-align: center;
  color: #34B1AA;
  line-height: 1;
}
.pass-registry-item.is-redeemed .pass-registry-suffix {
  color: #dc3545;
}
.pass-registry-main {
  flex: 1 1 auto;
  min-width: 0;
}
.pass-registry-holder {
  font-weight: 600;
  font-size: 0.9rem;
  line-height: 1.25;
}
.pass-registry-head {
  font-size: 0.78rem;
  color: #868e96;
}
.pass-registry-dates {
  font-size: 0.72rem;
  color: #868e96;
  margin-top: 0.15rem;
}
.pass-registry-status {
  flex: 0 0 auto;
  text-align: right;
}
.pass-undo-form {
  line-height: 1;
}
.pass-undo-btn {
  font-size: 0.72rem;
  color: #6c757d;
  text-decoration: none;
}
.pass-undo-btn:hover {
  color: #34B1AA;
  text-decoration: underline;
}
.pass-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(10.5rem, 1fr));
  gap: 0.5rem;
}
.pass-list-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 0.75rem 0.5rem;
  border: 1px solid #e9ecef;
  border-radius: 0.5rem;
  background: #fafbfc;
}
.pass-list-item.is-mine {
  border-color: #34B1AA;
  background: #f0faf9;
  box-shadow: 0 0 0 1px rgba(52, 177, 170, 0.25);
}
.pass-list-item.is-redeemed {
  opacity: 0.85;
  background: #fff8f8;
  border-color: #f1d0d4;
}
.pass-list-suffix {
  font-size: 1.35rem;
  font-weight: 700;
  letter-spacing: 0.15em;
  color: #34B1AA;
  line-height: 1.1;
}
.pass-list-item.is-redeemed .pass-list-suffix {
  color: #dc3545;
}
.pass-list-name {
  font-size: 0.8rem;
  margin: 0.35rem 0;
  line-height: 1.2;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
@media (max-width: 575.98px) {
  .redeem-search-row { max-width: 100%; }
  .redeem-search-input { max-width: none; }
  .pass-search-results {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .pass-registry-item {
    flex-wrap: wrap;
  }
  .pass-registry-status {
    width: 100%;
    text-align: right;
  }
  .pass-list {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (min-width: 992px) {
  .redeem-card .card-body { padding: 1.25rem 1.35rem; }
}
</style>
<?php if ($canManageOrg): ?>
<script>
(function () {
  var eventId = <?php echo (int) $eventId; ?>;
  var baseUrl = <?php echo json_encode($b, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  var input = document.getElementById('pass_search');
  var form = document.getElementById('redeemPassForm');
  var passIdInput = document.getElementById('pass_id');
  var resultsEl = document.getElementById('passSearchResults');
  var hintEl = document.getElementById('passSearchHint');
  if (!input || !form || !resultsEl) return;

  var debounceTimer = null;
  var selectedId = 0;

  function normalize(val) {
    return val.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
  }

  function setSelected(id) {
    selectedId = id;
    if (passIdInput) passIdInput.value = id > 0 ? String(id) : '';
    var cards = resultsEl.querySelectorAll('.pass-result');
    for (var i = 0; i < cards.length; i++) {
      var cid = parseInt(cards[i].getAttribute('data-id'), 10) || 0;
      cards[i].classList.toggle('is-selected', cid === id);
    }
  }

  function setHint(text) {
    if (hintEl) hintEl.textContent = text;
  }

  function renderMatches(matches) {
    resultsEl.innerHTML = '';
    setSelected(0);
    if (!matches || !matches.length) {
      setHint(<?php echo json_encode(t('passes.search.none')); ?>);
      return;
    }
    if (matches.length === 1) {
      setHint(<?php echo json_encode(t('passes.search.one_match')); ?>);
    } else {
      setHint(matches.length + ' ' + <?php echo json_encode(t('passes.search.many_matches_suffix')); ?>);
    }
    matches.forEach(function (m) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'pass-result';
      btn.setAttribute('data-id', String(m.id || 0));
      var active = (m.status || '') === 'active';
      if (!active) {
        btn.classList.add('is-disabled');
        btn.disabled = true;
      }
      var suffix = document.createElement('div');
      suffix.className = 'pass-result-suffix';
      suffix.textContent = m.code_suffix || '';
      btn.appendChild(suffix);
      var code = document.createElement('div');
      code.className = 'pass-result-code';
      code.textContent = m.pass_code || '';
      btn.appendChild(code);
      var names = document.createElement('div');
      names.className = 'pass-result-names';
      var holder = (m.holder_name || '').trim();
      var head = (m.head_name || '').trim();
      if (holder) {
        var h = document.createElement('div');
        h.className = 'holder';
        h.textContent = holder;
        h.title = holder;
        names.appendChild(h);
      }
      if (head && head !== holder) {
        var hd = document.createElement('div');
        hd.className = 'head';
        hd.textContent = head;
        hd.title = head;
        names.appendChild(hd);
      } else if (head && !holder) {
        var hd2 = document.createElement('div');
        hd2.className = 'holder';
        hd2.textContent = head;
        names.appendChild(hd2);
      }
      if ((m.status || '') === 'redeemed') {
        var st = document.createElement('div');
        st.className = 'redeemed-tag';
        st.textContent = 'Redeemed';
        names.appendChild(st);
      }
      btn.appendChild(names);
      if (active) {
        btn.addEventListener('click', function () {
          setSelected(m.id);
          form.submit();
        });
      }
      resultsEl.appendChild(btn);
    });
  }

  function runSearch() {
    var q = normalize(input.value);
    input.value = q;
    setSelected(0);
    if (q.length < 3) {
      resultsEl.innerHTML = '';
      setHint(<?php echo json_encode(t('events.type_3_chars')); ?>);
      return;
    }
    setHint(<?php echo json_encode(t('passes.search.searching')); ?>);
    var url = baseUrl + '/organization/event/pass-search?event_id=' + encodeURIComponent(eventId)
      + '&q=' + encodeURIComponent(q);
    fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.ok) {
          setHint((data && data.error) ? data.error : <?php echo json_encode(t('passes.search.failed')); ?>);
          resultsEl.innerHTML = '';
          return;
        }
        renderMatches(data.matches || []);
      })
      .catch(function () {
        setHint(<?php echo json_encode(t('passes.search.failed_retry')); ?>);
        resultsEl.innerHTML = '';
      });
  }

  input.addEventListener('input', function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(runSearch, 220);
  });

  input.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') {
      return;
    }
    e.preventDefault();
    if (normalize(input.value).length < 3) {
      setHint(<?php echo json_encode(t('events.type_3_chars')); ?>);
      return;
    }
    form.submit();
  });

  form.addEventListener('submit', function (e) {
    if (selectedId < 1 && normalize(input.value).length >= 3) {
      return;
    }
    if (selectedId < 1) {
      e.preventDefault();
      setHint(<?php echo json_encode(t('passes.search.pick_from_list')); ?>);
    }
  });
})();
</script>
<?php endif; ?>
