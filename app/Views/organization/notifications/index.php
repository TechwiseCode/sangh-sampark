<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$canManageOrg = !empty($canManageOrg);
$pushConfigured = !empty($pushConfigured);
$pushSubscriptionCount = (int) ($pushSubscriptionCount ?? 0);
$campaigns = isset($campaigns) && is_array($campaigns) ? $campaigns : [];
$queueSummary = isset($queueSummary) && is_array($queueSummary) ? $queueSummary : [];
$orgUsersForWhatsApp = isset($orgUsersForWhatsApp) && is_array($orgUsersForWhatsApp) ? $orgUsersForWhatsApp : [];
$unreadNotifications = isset($unreadNotifications) && is_array($unreadNotifications) ? $unreadNotifications : [];
$readNotifications = isset($readNotifications) && is_array($readNotifications) ? $readNotifications : [];
$pendingRequests = isset($pendingRequests) && is_array($pendingRequests) ? $pendingRequests : [];
$unreadInWindow = (int) ($unreadInWindow ?? count($unreadNotifications));
$notificationsTotal = (int) ($notificationsTotal ?? 0);
$notificationsHasMore = !empty($notificationsHasMore);
$notificationsPageSize = (int) ($notificationsPageSize ?? 7);
$notificationsRecentMonths = (int) ($notificationsRecentMonths ?? 2);
$unreadCount = $unreadInWindow;
$queueMap = [];
foreach ($queueSummary as $qs) {
  $queueMap[(string) ($qs['status'] ?? '')] = (int) ($qs['cnt'] ?? 0);
}
$showWhatsAppUi = false;
?>
<div class="notifications-hub">
  <header class="notifications-hub__header">
    <div class="notifications-hub__header-main">
      <span class="notifications-hub__header-icon" aria-hidden="true"><i class="mdi mdi-bell-ring-outline"></i></span>
      <div>
        <h1 class="notifications-hub__title"><?php echo h('notifications.title'); ?></h1>
        <p class="notifications-hub__subtitle mb-0"><?php echo h('notifications.subtitle'); ?></p>
        <?php if (!$canManageOrg): ?>
          <p class="notifications-hub__window-note text-muted small mb-0 mt-1"><?php echo h('notifications.recent_window_note', ['months' => (string) $notificationsRecentMonths]); ?></p>
        <?php endif; ?>
      </div>
    </div>
    <?php if (!$canManageOrg && $unreadCount > 0): ?>
      <div class="notifications-hub__stat">
        <span class="notifications-hub__stat-value"><?php echo (int) $unreadCount; ?></span>
        <span class="notifications-hub__stat-label"><?php echo h('notifications.unread_label'); ?></span>
      </div>
    <?php endif; ?>
  </header>

  <?php if ($flashOk): ?>
    <div class="alert alert-success notifications-hub__alert"><?php echo htmlspecialchars($flashOk); ?></div>
  <?php endif; ?>
  <?php if ($flashErr): ?>
    <div class="alert alert-danger notifications-hub__alert"><?php echo htmlspecialchars($flashErr); ?></div>
  <?php endif; ?>

  <?php if ($canManageOrg): ?>
    <div class="notifications-hub__admin-grid">
      <section class="notifications-hub__panel notifications-hub__panel--compose">
        <div class="notifications-hub__panel-head">
          <span class="notifications-hub__panel-icon" aria-hidden="true"><i class="mdi mdi-bullhorn-outline"></i></span>
          <div>
            <h2 class="notifications-hub__panel-title"><?php echo h('notifications.composer_title'); ?></h2>
            <p class="notifications-hub__panel-desc mb-0"><?php echo h('notifications.composer_desc'); ?></p>
          </div>
        </div>

        <?php if ($showWhatsAppUi): ?>
        <details class="notifications-hub__details mb-3">
          <summary><?php echo h('notifications.whatsapp_section_title'); ?></summary>
          <div id="notify_single_panel" class="mt-3">
            <div class="form-row">
              <div class="form-group col-md-5">
                <label for="wa_user_id"><?php echo h('notifications.single_user_label'); ?></label>
                <select class="form-control" id="wa_user_id">
                  <option value=""><?php echo h('notifications.single_user_placeholder'); ?></option>
                  <?php foreach ($orgUsersForWhatsApp as $ou): ?>
                    <?php
                      $phoneRaw = trim((string) ($ou['phone'] ?? ''));
                      $name = (string) ($ou['name'] ?? '');
                    ?>
                    <option value="<?php echo (int) ($ou['user_id'] ?? 0); ?>" data-phone="<?php echo htmlspecialchars($phoneRaw); ?>">
                      <?php echo htmlspecialchars($name !== '' ? $name : ('User #' . (int) ($ou['user_id'] ?? 0))); ?>
                      <?php if ($phoneRaw !== ''): ?> · <?php echo htmlspecialchars($phoneRaw); ?><?php endif; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group col-md-3">
                <label for="wa_phone"><?php echo h('notifications.phone_label'); ?></label>
                <input type="text" class="form-control" id="wa_phone" placeholder="<?php echo h('notifications.phone_placeholder'); ?>">
              </div>
              <div class="form-group col-md-4">
                <label for="wa_message"><?php echo h('notifications.message_label'); ?></label>
                <input type="text" class="form-control" id="wa_message" placeholder="<?php echo h('notifications.message_placeholder'); ?>">
              </div>
            </div>
            <button type="button" class="btn btn-success btn-sm" id="open_wa_web_btn"><?php echo h('notifications.open_whatsapp_btn'); ?></button>
            <small class="text-muted ml-2"><?php echo h('notifications.open_whatsapp_hint'); ?></small>
          </div>
        </details>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/notifications/broadcast" id="notify_broadcast_panel" class="notifications-broadcast-form">
          <div class="notifications-hub__compose-block">
            <h3 class="notifications-hub__block-title"><?php echo h('notifications.broadcast_message_section'); ?></h3>
            <div class="form-group mb-3">
              <label for="title" class="notifications-field-label"><?php echo h('notifications.broadcast_title_label'); ?></label>
              <input type="text" class="form-control notifications-field-control" id="title" name="title" maxlength="255" required placeholder="<?php echo h('notifications.broadcast_title_placeholder'); ?>">
            </div>
            <div class="form-group mb-0">
              <label for="message" class="notifications-field-label"><?php echo h('notifications.broadcast_message_label'); ?></label>
              <textarea class="form-control notifications-message-input" id="message" name="message" rows="6" required placeholder="<?php echo h('notifications.broadcast_message_placeholder'); ?>"></textarea>
            </div>
          </div>

          <div class="notifications-hub__compose-block">
            <h3 class="notifications-hub__block-title"><?php echo h('notifications.broadcast_recipients_label'); ?></h3>
            <p class="notifications-hub__block-desc"><?php echo h('notifications.broadcast_recipients_hint'); ?></p>
            <?php
            $idPrefix = 'broadcast';
            $memberFilter = 'all';
            $genderFilter = 'all';
            $professionFilter = 'all';
            $donationFilter = 'all';
            $ageFilters = [];
            require BASE_PATH . '/app/Views/partials/member_directory_filters.php';
            ?>
            <div class="broadcast-recipients-filter-actions">
              <button type="button" class="btn btn-sm btn-outline-primary" id="broadcast_apply_filters">
                <i class="mdi mdi-account-search-outline mr-1" aria-hidden="true"></i><?php echo h('notifications.broadcast_show_members_btn'); ?>
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="broadcast_reset_filters"><?php echo h('common.reset'); ?></button>
            </div>
            <div class="broadcast-recipients-panel" id="broadcast_recipients_panel">
              <div class="broadcast-recipients-toolbar">
                <span class="broadcast-recipients-count" id="broadcast_recipients_summary"><?php echo h('notifications.broadcast_recipients_loading'); ?></span>
                <div class="broadcast-recipients-toolbar-actions">
                  <div class="broadcast-recipients-name-search">
                    <input type="search" class="form-control form-control-sm" id="broadcast_recipients_name_search" placeholder="<?php echo h('notifications.broadcast_recipients_search_placeholder'); ?>" autocomplete="off" aria-label="<?php echo h('notifications.broadcast_recipients_search_label'); ?>">
                  </div>
                  <button type="button" class="btn btn-link btn-sm p-0 mr-3" id="broadcast_select_all"><?php echo h('notifications.broadcast_select_all'); ?></button>
                  <button type="button" class="btn btn-link btn-sm p-0" id="broadcast_select_none"><?php echo h('notifications.broadcast_select_none'); ?></button>
                </div>
              </div>
              <div id="broadcast_recipients_loading" class="broadcast-recipients-state text-muted small py-3 d-none"><?php echo h('notifications.broadcast_recipients_loading'); ?></div>
              <div id="broadcast_recipients_empty" class="broadcast-recipients-state text-muted small py-3 d-none"><?php echo h('notifications.broadcast_recipients_empty'); ?></div>
              <div id="broadcast_recipients_name_empty" class="broadcast-recipients-state text-muted small py-3 d-none"><?php echo h('notifications.broadcast_recipients_search_empty'); ?></div>
              <div class="table-responsive broadcast-recipients-table-wrap d-none" id="broadcast_recipients_table_wrap">
                <table class="table table-sm mb-0 broadcast-recipients-table">
                  <thead>
                    <tr>
                      <th class="broadcast-recipients-col-check">
                        <input type="checkbox" id="broadcast_recipients_check_all" aria-label="<?php echo h('notifications.broadcast_select_all'); ?>">
                      </th>
                      <th><?php echo h('members.col_name'); ?></th>
                      <th><?php echo h('members.col_code'); ?></th>
                      <th><?php echo h('members.col_age'); ?></th>
                      <th><?php echo h('profile.gender'); ?></th>
                      <th><?php echo h('profile.profession_type'); ?></th>
                    </tr>
                  </thead>
                  <tbody id="broadcast_recipients_tbody"></tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="notifications-hub__compose-footer">
            <div class="notifications-channel-group">
              <span class="notifications-field-label d-block mb-2"><?php echo h('notifications.broadcast_channels_label'); ?></span>
              <div class="notifications-channel-pills">
                <label class="notifications-channel-pill">
                  <input type="checkbox" id="channel_in_app" name="channels[]" value="in_app" checked>
                  <span><i class="mdi mdi-bell-outline" aria-hidden="true"></i> <?php echo h('notifications.broadcast_channel_in_app'); ?></span>
                </label>
                <label class="notifications-channel-pill">
                  <input type="checkbox" id="channel_web_push" name="channels[]" value="web_push" checked>
                  <span><i class="mdi mdi-cellphone-arrow-down" aria-hidden="true"></i> <?php echo h('notifications.broadcast_channel_web_push'); ?></span>
                </label>
                <?php if ($showWhatsAppUi): ?>
                <label class="notifications-channel-pill">
                  <input type="checkbox" id="channel_whatsapp" name="channels[]" value="whatsapp">
                  <span><i class="mdi mdi-whatsapp" aria-hidden="true"></i> <?php echo h('notifications.broadcast_channel_whatsapp'); ?></span>
                </label>
                <?php endif; ?>
              </div>
            </div>
            <button type="submit" class="btn btn-primary notifications-send-btn">
              <i class="mdi mdi-send" aria-hidden="true"></i> <?php echo h('notifications.broadcast_send_btn'); ?>
            </button>
          </div>
          <?php if ($showWhatsAppUi): ?>
          <small class="text-muted d-block mt-2">
            <?php echo h('notifications.broadcast_queue_prefix'); ?> <?php echo (int) ($queueMap['pending'] ?? 0); ?> · <?php echo h('notifications.broadcast_queue_sent'); ?> <?php echo (int) ($queueMap['sent'] ?? 0); ?> · <?php echo h('notifications.broadcast_queue_failed'); ?> <?php echo (int) ($queueMap['failed'] ?? 0); ?>
          </small>
          <?php endif; ?>
        </form>
      </section>

      <section class="notifications-hub__panel notifications-hub__panel--history">
        <div class="notifications-hub__panel-head">
          <span class="notifications-hub__panel-icon notifications-hub__panel-icon--muted" aria-hidden="true"><i class="mdi mdi-history"></i></span>
          <div>
            <h2 class="notifications-hub__panel-title"><?php echo h('notifications.recent_title'); ?></h2>
            <p class="notifications-hub__panel-desc mb-0"><?php echo h('notifications.recent_subtitle'); ?></p>
          </div>
        </div>

        <?php if ($campaigns === []): ?>
          <div class="notifications-hub__empty">
            <i class="mdi mdi-email-outline notifications-hub__empty-icon" aria-hidden="true"></i>
            <p class="mb-0"><?php echo h('notifications.recent_empty'); ?></p>
          </div>
        <?php else: ?>
          <div class="notifications-campaign-list">
            <?php foreach ($campaigns as $c): ?>
              <?php
                $recipientFilters = trim((string) ($c['recipient_filters'] ?? ''));
                if ($recipientFilters !== '') {
                    $audienceLabel = member_directory_filters_summary_from_json($recipientFilters);
                } elseif (($c['audience'] ?? '') === 'family_heads') {
                    $audienceLabel = t('members.filter_heads');
                } else {
                    $audienceLabel = t('members.filter_all');
                }
              ?>
              <article class="notifications-campaign-card">
                <div class="notifications-campaign-card__top">
                  <h3 class="notifications-campaign-card__title"><?php echo htmlspecialchars((string) ($c['title'] ?? '')); ?></h3>
                  <time class="notifications-campaign-card__date"><?php echo htmlspecialchars(format_pretty_date(isset($c['created_at']) ? (string) $c['created_at'] : null)); ?></time>
                </div>
                <p class="notifications-campaign-card__audience mb-2"><?php echo htmlspecialchars($audienceLabel); ?></p>
                <div class="notifications-campaign-card__stats">
                  <span class="notifications-campaign-stat">
                    <i class="mdi mdi-account-multiple-outline" aria-hidden="true"></i>
                    <?php echo (int) ($c['total_recipients'] ?? 0); ?>
                  </span>
                  <span class="notifications-campaign-stat">
                    <i class="mdi mdi-bell-outline" aria-hidden="true"></i>
                    <?php echo (int) ($c['in_app_sent_count'] ?? 0); ?>
                  </span>
                  <span class="notifications-campaign-stat">
                    <i class="mdi mdi-cellphone-arrow-down" aria-hidden="true"></i>
                    <?php echo (int) ($c['push_sent_count'] ?? 0); ?>
                  </span>
                  <?php if ($showWhatsAppUi): ?>
                  <span class="notifications-campaign-stat">
                    <i class="mdi mdi-whatsapp" aria-hidden="true"></i>
                    <?php echo (int) ($c['whatsapp_queued_count'] ?? 0); ?>
                  </span>
                  <?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </div>

  <?php else: ?>
    <div class="notifications-hub__member-grid notifications-hub__member-grid--full">
      <div class="notifications-hub__member-main notifications-hub__member-main--wide">
        <?php if ($pendingRequests !== []): ?>
          <section class="notifications-hub__section">
            <div class="notifications-hub__section-head">
              <h2 class="notifications-hub__section-title"><?php echo h('notifications.waiting_title'); ?></h2>
              <span class="notifications-hub__count-pill"><?php echo count($pendingRequests); ?></span>
            </div>
            <div class="notifications-action-list">
              <?php foreach ($pendingRequests as $pr): ?>
                <article class="notifications-action-card">
                  <div class="notifications-action-card__icon" aria-hidden="true"><i class="mdi mdi-account-clock-outline"></i></div>
                  <div class="notifications-action-card__body">
                    <p class="notifications-action-card__text mb-2">
                      <strong><?php echo htmlspecialchars((string) $pr['requester_name']); ?></strong>
                      <?php if (empty($pr['family_id'])): ?>
                        <?php echo h('notifications.request_become_head_prefix'); ?>
                        <strong><?php echo h('notifications.request_head_word'); ?></strong>
                        <?php echo h('notifications.request_new_family_suffix'); ?>
                        <strong><?php echo htmlspecialchars((string) $pr['organization_name']); ?></strong>.
                      <?php elseif (!empty($pr['is_existing_family_member'])): ?>
                        <?php echo h('notifications.request_update_role_prefix'); ?>
                        <strong><?php echo htmlspecialchars((string) $pr['organization_name']); ?></strong>, <?php echo h('notifications.request_family_label'); ?> <?php echo (int) $pr['family_id']; ?>.
                      <?php else: ?>
                        <?php echo h('notifications.request_join_prefix'); ?>
                        <strong><?php echo htmlspecialchars((string) $pr['organization_name']); ?></strong>
                        (<?php echo h('notifications.request_family_label'); ?> <?php echo (int) $pr['family_id']; ?>).
                      <?php endif; ?>
                    </p>
                    <p class="notifications-action-card__meta mb-3">
                      <?php echo h('notifications.request_role_prefix'); ?>
                      <span class="notifications-action-card__role"><?php echo htmlspecialchars((string) $pr['requested_role']); ?></span>
                      <?php if (!empty($pr['related_to_user_id'])): ?>
                        · <?php echo h('notifications.request_related_to'); ?> <?php echo htmlspecialchars((string) ($pr['related_user_name'] ?? ('User #' . (int) $pr['related_to_user_id']))); ?>
                      <?php endif; ?>
                    </p>
                    <div class="notifications-action-card__actions">
                      <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/membership-request/respond">
                        <input type="hidden" name="request_id" value="<?php echo (int) $pr['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success btn-sm"><?php echo h('notifications.request_approve_btn'); ?></button>
                      </form>
                      <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/membership-request/respond">
                        <input type="hidden" name="request_id" value="<?php echo (int) $pr['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-outline-secondary btn-sm"><?php echo h('notifications.request_decline_btn'); ?></button>
                      </form>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

        <section class="notifications-hub__section">
          <?php if ($unreadNotifications !== []): ?>
            <div class="notifications-hub__section-head">
              <h2 class="notifications-hub__section-title"><?php echo h('notifications.unread_section'); ?></h2>
              <div class="notifications-hub__section-actions">
                <span class="notifications-hub__count-pill notifications-hub__count-pill--accent"><?php echo $unreadCount; ?></span>
                <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/notifications/mark-read" class="mb-0">
                  <input type="hidden" name="all" value="1">
                  <button type="submit" class="notifications-hub__text-btn"><?php echo h('notifications.mark_all_read'); ?></button>
                </form>
              </div>
            </div>
            <ul class="notifications-inbox-list notifications-inbox-list--spaced mb-4" id="notifications_inbox_unread" data-notif-section="unread">
              <?php foreach ($unreadNotifications as $n): ?>
                <?php $notificationRow = $n; $notificationCanManageOrg = $canManageOrg; require BASE_PATH . '/app/Views/partials/notification_inbox_item.php'; ?>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <ul class="notifications-inbox-list d-none" id="notifications_inbox_unread" data-notif-section="unread"></ul>
          <?php endif; ?>

          <div class="notifications-hub__section-head<?php echo $unreadNotifications === [] ? '' : ' mt-0'; ?>">
            <h2 class="notifications-hub__section-title"><?php echo h('notifications.earlier_section'); ?></h2>
          </div>

          <?php if ($readNotifications === [] && $unreadNotifications === []): ?>
            <div class="notifications-hub__empty" id="notifications_inbox_empty">
              <i class="mdi mdi-bell-off-outline notifications-hub__empty-icon" aria-hidden="true"></i>
              <p class="mb-0"><?php echo h('notifications.older_empty'); ?></p>
            </div>
            <ul class="notifications-inbox-list d-none" id="notifications_inbox_read" data-notif-section="read"></ul>
          <?php elseif ($readNotifications === []): ?>
            <div class="notifications-hub__empty notifications-hub__empty--compact<?php echo $notificationsHasMore ? ' d-none' : ''; ?>" id="notifications_inbox_read_empty">
              <p class="mb-0 text-muted"><?php echo h('notifications.earlier_empty'); ?></p>
            </div>
            <ul class="notifications-inbox-list<?php echo $notificationsHasMore ? ' d-none' : ''; ?>" id="notifications_inbox_read" data-notif-section="read"></ul>
          <?php else: ?>
            <ul class="notifications-inbox-list" id="notifications_inbox_read" data-notif-section="read">
              <?php foreach ($readNotifications as $n): ?>
                <?php $notificationRow = $n; $notificationCanManageOrg = $canManageOrg; require BASE_PATH . '/app/Views/partials/notification_inbox_item.php'; ?>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <div id="notifications_inbox_load_wrap" class="notifications-inbox-load-wrap<?php echo $notificationsHasMore ? '' : ' d-none'; ?>">
            <button type="button" class="btn btn-sm btn-outline-secondary notifications-inbox-load-more" id="notifications_inbox_load_more">
              <?php echo h('notifications.load_more'); ?>
            </button>
            <p class="notifications-inbox-load-status text-muted small mb-0 d-none" id="notifications_inbox_load_status" aria-live="polite"></p>
          </div>
        </section>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php if (!$canManageOrg): ?>
<script>
window.SanghSamparkNotificationsInbox = <?php echo json_encode([
    'baseUrl' => base_url(),
    'initialOffset' => count($recentNotifications ?? []),
    'pageSize' => $notificationsPageSize,
    'hasMore' => $notificationsHasMore,
    'markReadUrl' => base_url() . '/organization/notifications/mark-read',
    'listUrl' => base_url() . '/organization/notifications/list',
    'unreadLabel' => t('notifications.unread_label'),
    'readLabel' => t('notifications.read_label'),
    'markReadLabel' => t('notifications.mark_read'),
    'loadMoreLabel' => t('notifications.load_more'),
    'loadingMoreLabel' => t('notifications.loading_more'),
    'loadMoreDoneLabel' => t('notifications.load_more_done'),
    'memberChatOpenLabel' => t('member_chat.notification_open_reply'),
    'memberChatEnabled' => member_admin_chat_enabled(),
    'memberChatBaseUrl' => base_url() . '/organization/member-messages?thread=',
], JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="<?php echo htmlspecialchars(asset_url('themes/js/notifications-inbox.js')); ?>"></script>
<?php endif; ?>

<script>
<?php if ($showWhatsAppUi): ?>
(function () {
  var userSel = document.getElementById('wa_user_id');
  var phoneInput = document.getElementById('wa_phone');
  var messageInput = document.getElementById('wa_message');
  var openBtn = document.getElementById('open_wa_web_btn');
  if (!userSel || !phoneInput || !messageInput || !openBtn) return;

  function normalizePhone(raw) {
    var digits = String(raw || '').replace(/\D/g, '');
    if (!digits) return '';
    if (digits.length === 10) return '91' + digits;
    if (digits.length === 12 && digits.indexOf('91') === 0) return digits;
    if (digits.length > 12 && digits.indexOf('91') === 0) return digits.slice(0, 12);
    return digits;
  }

  function syncPhoneFromUser() {
    var opt = userSel.options[userSel.selectedIndex];
    var phone = opt ? (opt.getAttribute('data-phone') || '') : '';
    if (phone) {
      phoneInput.value = phone;
    }
  }

  userSel.addEventListener('change', syncPhoneFromUser);
  openBtn.addEventListener('click', function () {
    var phone = normalizePhone(phoneInput.value || '');
    var msg = String(messageInput.value || '').trim();
    if (!phone) {
      alert(<?php echo json_encode(t('notifications.js_phone_required')); ?>);
      return;
    }
    if (!msg) {
      alert(<?php echo json_encode(t('notifications.js_message_required')); ?>);
      return;
    }
    var url = 'https://web.whatsapp.com/send?phone=' + encodeURIComponent(phone) + '&text=' + encodeURIComponent(msg);
    window.open(url, '_blank', 'noopener');
  });
})();
<?php endif; ?>
</script>
<?php if ($canManageOrg): ?>
<?php
$ageDropdownWrapId = 'broadcast_age_dropdown';
$ageDropdownToggleId = 'broadcast_age_toggle';
require BASE_PATH . '/app/Views/partials/member_age_dropdown_script.php';
?>
<script src="<?php echo htmlspecialchars(asset_url('themes/js/broadcast-recipients.js')); ?>"></script>
<script>
window.BroadcastRecipientsConfig = <?php echo json_encode([
    'recipientsUrl' => base_url() . '/organization/notifications/broadcast/recipients',
    'headBadge' => t('members.badge_head'),
    'allAgeLabel' => t('members.filter_all_short'),
    'selectedSummary' => t('notifications.broadcast_recipients_selected_summary', ['selected' => ':selected', 'total' => ':total']),
    'noRecipientsSelected' => t('notifications.broadcast_no_recipients_selected'),
], JSON_UNESCAPED_UNICODE); ?>;
if (window.initBroadcastRecipients) {
  window.initBroadcastRecipients(window.BroadcastRecipientsConfig);
}
</script>
<?php endif; ?>
