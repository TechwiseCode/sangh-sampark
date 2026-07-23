<?php
if (!member_admin_chat_enabled()) {
    return;
}
$b = base_url();
?>
<div id="member-chat-widget" class="member-chat-widget" aria-live="polite">
  <button type="button" class="member-chat-widget__toggle" id="member-chat-toggle" aria-expanded="false" aria-controls="member-chat-panel" aria-label="<?php echo htmlspecialchars(t('member_chat.widget_toggle')); ?>" title="<?php echo htmlspecialchars(t('member_chat.widget_toggle')); ?>">
    <i class="mdi mdi-chat-outline member-chat-widget__icon" aria-hidden="true"></i>
  </button>
  <div id="member-chat-panel" class="member-chat-widget__panel" hidden>
    <header class="member-chat-widget__header">
      <div>
        <strong class="member-chat-widget__title"><?php echo h(t('member_chat.widget_title')); ?></strong>
        <p class="member-chat-widget__hint mb-0"><?php echo h(t('member_chat.widget_hint')); ?></p>
      </div>
      <button type="button" class="member-chat-widget__close" id="member-chat-close" aria-label="<?php echo htmlspecialchars(t('common.cancel')); ?>">
        <i class="mdi mdi-close" aria-hidden="true"></i>
      </button>
    </header>
    <div class="member-chat-widget__messages" id="member-chat-messages" role="log"></div>
    <form class="member-chat-widget__form" id="member-chat-form">
      <label class="sr-only" for="member-chat-input"><?php echo h(t('member_chat.input_label')); ?></label>
      <textarea id="member-chat-input" class="form-control form-control-sm" rows="2" maxlength="2000" placeholder="<?php echo htmlspecialchars(t('member_chat.input_placeholder')); ?>" required></textarea>
      <button type="submit" class="btn btn-primary btn-sm mt-2" id="member-chat-send"><?php echo h(t('member_chat.send_btn')); ?></button>
    </form>
  </div>
</div>
<script>
window.SanghSamparkMemberChat = {
  baseUrl: <?php echo json_encode($b); ?>,
  labels: {
    you: <?php echo json_encode(t('member_chat.label_you')); ?>,
    admin: <?php echo json_encode(t('member_chat.label_admin')); ?>,
    empty: <?php echo json_encode(t('member_chat.widget_empty')); ?>,
    sent: <?php echo json_encode(t('member_chat.message_sent')); ?>,
    errorEmpty: <?php echo json_encode(t('member_chat.error_empty')); ?>,
    errorGeneric: <?php echo json_encode(t('member_chat.error_generic')); ?>,
    sending: <?php echo json_encode(t('member_chat.sending')); ?>,
    send: <?php echo json_encode(t('member_chat.send_btn')); ?>
  }
};
</script>
<script src="<?php echo htmlspecialchars(asset_url('themes/js/member-admin-chat.js')); ?>"></script>
