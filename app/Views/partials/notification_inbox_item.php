<?php
/** @var array<string,mixed> $notificationRow */
$n = isset($notificationRow) && is_array($notificationRow) ? notification_hydrate_row($notificationRow) : [];
$isUnread = empty($n['read_at']);
$type = (string) ($n['type'] ?? 'broadcast');
$referenceId = (int) ($n['reference_id'] ?? 0);
$canManageOrgForNotif = !empty($notificationCanManageOrg);
$memberChatOpenUrl = ($type === 'member_admin_chat' && $referenceId > 0 && $canManageOrgForNotif && member_admin_chat_enabled())
    ? base_url() . '/organization/member-messages?thread=' . $referenceId
    : null;
$iconClass = 'mdi-bell-outline';
if ($type === 'broadcast') {
    $iconClass = 'mdi-bullhorn-outline';
} elseif ($type === 'relationship_request') {
    $iconClass = 'mdi-account-arrow-right-outline';
} elseif ($type === 'member_admin_chat' || $type === 'member_admin_chat_reply') {
    $iconClass = 'mdi-message-text-outline';
}
?>
<li class="notifications-inbox-item<?php echo $isUnread ? ' is-unread' : ' is-read'; ?>">
  <div class="notifications-inbox-item__icon" aria-hidden="true">
    <i class="mdi <?php echo htmlspecialchars($iconClass); ?>"></i>
  </div>
  <div class="notifications-inbox-body">
    <div class="notifications-inbox-head">
      <div class="notifications-inbox-title-wrap">
        <strong class="notifications-inbox-title"><?php echo htmlspecialchars((string) ($n['title'] ?? '')); ?></strong>
        <?php if ($isUnread): ?>
          <span class="notifications-unread-badge"><?php echo h(t('notifications.unread_label')); ?></span>
        <?php else: ?>
          <span class="notifications-read-badge"><?php echo h(t('notifications.read_label')); ?></span>
        <?php endif; ?>
      </div>
      <time class="notifications-inbox-date"><?php echo htmlspecialchars(format_pretty_date(isset($n['created_at']) ? (string) $n['created_at'] : null)); ?></time>
    </div>
    <?php if (!empty($n['message'])): ?>
      <p class="notifications-inbox-message mb-0"><?php echo nl2br(htmlspecialchars((string) $n['message'])); ?></p>
    <?php endif; ?>
    <?php if ($memberChatOpenUrl !== null): ?>
      <p class="mb-0 mt-2">
        <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($memberChatOpenUrl); ?>"><?php echo h(t('member_chat.notification_open_reply')); ?></a>
      </p>
    <?php endif; ?>
    <?php if ($isUnread): ?>
      <form method="post" action="<?php echo htmlspecialchars(base_url()); ?>/organization/notifications/mark-read" class="notifications-inbox-mark-read mb-0">
        <input type="hidden" name="id" value="<?php echo (int) ($n['id'] ?? 0); ?>">
        <button type="submit" class="notifications-inbox-mark-read-btn">
          <i class="mdi mdi-check" aria-hidden="true"></i>
          <?php echo h(t('notifications.mark_read')); ?>
        </button>
      </form>
    <?php endif; ?>
  </div>
</li>
