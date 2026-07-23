<?php
$b = base_url();
$flashOk = \flash_get('ok');
$flashErr = \flash_get('error');
$threads = isset($threads) && is_array($threads) ? $threads : [];
$activeThread = isset($activeThread) && is_array($activeThread) ? $activeThread : null;
$activeMessages = isset($activeMessages) && is_array($activeMessages) ? $activeMessages : [];
$activeThreadId = (int) ($activeThreadId ?? 0);
?>
<div class="member-chat-admin-page">
  <div class="page-header-wrap">
    <h1 class="page-title"><?php echo h(t('member_chat.admin_page_title')); ?></h1>
    <p class="page-subtitle text-muted mb-0"><?php echo h(t('member_chat.admin_page_subtitle')); ?></p>
  </div>

  <?php if ($flashOk): ?>
    <div class="alert alert-success mt-3"><?php echo htmlspecialchars((string) $flashOk); ?></div>
  <?php endif; ?>
  <?php if ($flashErr): ?>
    <div class="alert alert-danger mt-3"><?php echo htmlspecialchars((string) $flashErr); ?></div>
  <?php endif; ?>

  <div class="member-chat-admin-layout">
    <aside class="member-chat-admin-threads card">
      <div class="card-body p-0">
        <div class="member-chat-admin-threads__head px-3 py-2 border-bottom">
          <strong><?php echo h(t('member_chat.admin_open_threads')); ?></strong>
        </div>
        <?php if ($threads === []): ?>
          <p class="text-muted small px-3 py-3 mb-0"><?php echo h(t('member_chat.admin_no_threads')); ?></p>
        <?php else: ?>
          <ul class="member-chat-admin-thread-list list-unstyled mb-0">
            <?php foreach ($threads as $thread): ?>
              <?php
              $tid = (int) ($thread['id'] ?? 0);
              $isActive = $tid === $activeThreadId;
              $memberLabel = user_display_name($thread);
              $preview = trim((string) ($thread['last_body'] ?? ''));
              if (mb_strlen($preview) > 80) {
                  $preview = mb_substr($preview, 0, 77) . '...';
              }
              ?>
              <li>
                <a class="member-chat-admin-thread-item<?php echo $isActive ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($b); ?>/organization/member-messages?thread=<?php echo $tid; ?>">
                  <strong class="member-chat-admin-thread-item__name"><?php echo htmlspecialchars($memberLabel); ?></strong>
                  <?php if ($preview !== ''): ?>
                    <span class="member-chat-admin-thread-item__preview text-muted"><?php echo htmlspecialchars($preview); ?></span>
                  <?php endif; ?>
                  <time class="member-chat-admin-thread-item__time text-muted"><?php echo htmlspecialchars(format_pretty_date((string) ($thread['last_message_at'] ?? $thread['updated_at'] ?? ''))); ?></time>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </aside>

    <section class="member-chat-admin-detail card">
      <?php if ($activeThread === null): ?>
        <div class="card-body text-muted text-center py-5">
          <?php echo h(t('member_chat.admin_select_thread')); ?>
        </div>
      <?php else: ?>
        <div class="card-body member-chat-admin-detail__body">
          <header class="member-chat-admin-detail__head mb-3">
            <h2 class="h5 mb-1"><?php echo htmlspecialchars(user_display_name($activeThread)); ?></h2>
            <?php if (trim((string) ($activeThread['phone'] ?? '')) !== ''): ?>
              <span class="text-muted small"><?php echo htmlspecialchars((string) $activeThread['phone']); ?></span>
            <?php endif; ?>
          </header>
          <div class="member-chat-bubbles member-chat-bubbles--page" id="member-chat-admin-bubbles">
            <?php foreach ($activeMessages as $msg): ?>
              <?php
              $role = (string) ($msg['sender_role'] ?? '');
              $isAdmin = $role === 'admin';
              ?>
              <div class="member-chat-bubble<?php echo $isAdmin ? ' member-chat-bubble--out' : ' member-chat-bubble--in'; ?>">
                <div class="member-chat-bubble__body"><?php echo nl2br(htmlspecialchars((string) ($msg['body'] ?? ''))); ?></div>
                <time class="member-chat-bubble__time"><?php echo htmlspecialchars(format_pretty_date((string) ($msg['created_at'] ?? ''))); ?></time>
              </div>
            <?php endforeach; ?>
          </div>
          <form method="post" action="<?php echo htmlspecialchars($b); ?>/organization/member-messages/reply" class="member-chat-admin-reply-form mt-3">
            <input type="hidden" name="thread_id" value="<?php echo (int) ($activeThread['id'] ?? 0); ?>">
            <label class="form-label" for="member-chat-admin-reply"><?php echo h(t('member_chat.admin_reply_label')); ?></label>
            <textarea class="form-control mb-2" id="member-chat-admin-reply" name="body" rows="3" maxlength="2000" required placeholder="<?php echo htmlspecialchars(t('member_chat.admin_reply_placeholder')); ?>"></textarea>
            <button type="submit" class="btn btn-primary"><?php echo h(t('member_chat.admin_reply_btn')); ?></button>
          </form>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>
