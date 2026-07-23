(function () {
  var cfg = window.SanghSamparkMemberChat;
  if (!cfg || !cfg.baseUrl) return;

  var root = document.getElementById('member-chat-widget');
  var toggle = document.getElementById('member-chat-toggle');
  var panel = document.getElementById('member-chat-panel');
  var closeBtn = document.getElementById('member-chat-close');
  var messagesEl = document.getElementById('member-chat-messages');
  var form = document.getElementById('member-chat-form');
  var input = document.getElementById('member-chat-input');
  var sendBtn = document.getElementById('member-chat-send');
  if (!root || !toggle || !panel || !messagesEl || !form || !input) return;

  var labels = cfg.labels || {};
  var pollTimer = null;
  var panelOpen = false;
  var storageKey = 'sanghsampark_member_chat_token';

  function sessionToken() {
    try {
      var existing = sessionStorage.getItem(storageKey);
      if (existing && /^[a-f0-9]{32}$/.test(existing)) {
        return existing;
      }
      var bytes = new Uint8Array(16);
      if (window.crypto && window.crypto.getRandomValues) {
        window.crypto.getRandomValues(bytes);
      } else {
        for (var i = 0; i < 16; i++) {
          bytes[i] = Math.floor(Math.random() * 256);
        }
      }
      var token = Array.prototype.map.call(bytes, function (b) {
        return ('0' + b.toString(16)).slice(-2);
      }).join('');
      sessionStorage.setItem(storageKey, token);
      return token;
    } catch (e) {
      return '';
    }
  }

  function chatHeaders() {
    var token = sessionToken();
    var headers = { Accept: 'application/json' };
    if (token) {
      headers['X-Chat-Session'] = token;
    }
    return headers;
  }

  function escapeHtml(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatBody(text) {
    return escapeHtml(text).replace(/\n/g, '<br>');
  }

  function renderMessages(items) {
    if (!items || !items.length) {
      messagesEl.innerHTML = '<p class="member-chat-widget__empty text-muted small mb-0">' + escapeHtml(labels.empty || '') + '</p>';
      return;
    }
    var html = '';
    items.forEach(function (m) {
      var isOut = m.role === 'member';
      var who = isOut ? (labels.you || 'You') : (labels.admin || 'Admin');
      html += '<div class="member-chat-bubble' + (isOut ? ' member-chat-bubble--out' : ' member-chat-bubble--in') + '">';
      html += '<span class="member-chat-bubble__who">' + escapeHtml(who) + '</span>';
      html += '<div class="member-chat-bubble__body">' + formatBody(m.body) + '</div>';
      html += '</div>';
    });
    messagesEl.innerHTML = html;
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function fetchMessages() {
    return fetch(cfg.baseUrl + '/organization/member-chat/messages', {
      credentials: 'same-origin',
      headers: chatHeaders()
    })
      .then(function (r) { return r.json(); })
      .then(function (body) {
        if (body && body.ok && Array.isArray(body.messages)) {
          renderMessages(body.messages);
        }
      })
      .catch(function () {});
  }

  function closeChatPanel() {
    setPanelOpen(false);
  }

  function setPanelOpen(open) {
    panelOpen = open;
    panel.hidden = !open;
    root.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) {
      if (window.SanghSamparkNotificationsApi && window.SanghSamparkNotificationsApi.close) {
        window.SanghSamparkNotificationsApi.close();
      }
      if (window.SanghSamparkUserMenu && window.SanghSamparkUserMenu.close) {
        window.SanghSamparkUserMenu.close();
      }
      fetchMessages();
      startPoll();
      setTimeout(function () { input.focus(); }, 100);
      if (window.SanghSamparkPopupOverlay) {
        window.SanghSamparkPopupOverlay.show(closeChatPanel);
      }
    } else {
      stopPoll();
      if (window.SanghSamparkPopupOverlay) {
        window.SanghSamparkPopupOverlay.hide(closeChatPanel);
      }
    }
  }

  function startPoll() {
    stopPoll();
    pollTimer = window.setInterval(fetchMessages, 12000);
  }

  function stopPoll() {
    if (pollTimer) {
      window.clearInterval(pollTimer);
      pollTimer = null;
    }
  }

  toggle.addEventListener('click', function () {
    setPanelOpen(!panelOpen);
  });
  if (closeBtn) {
    closeBtn.addEventListener('click', function () {
      setPanelOpen(false);
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var body = String(input.value || '').trim();
    if (!body) {
      alert(labels.errorEmpty || 'Enter a message');
      return;
    }
    if (sendBtn) {
      sendBtn.disabled = true;
      sendBtn.textContent = labels.sending || 'Sending...';
    }
    fetch(cfg.baseUrl + '/organization/member-chat/send', {
      method: 'POST',
      credentials: 'same-origin',
      headers: Object.assign(chatHeaders(), {
        'Content-Type': 'application/json'
      }),
      body: JSON.stringify({ body: body, session_token: sessionToken() })
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res && res.ok) {
          input.value = '';
          renderMessages(res.messages || []);
        } else {
          alert(labels.errorGeneric || 'Could not send');
        }
      })
      .catch(function () {
        alert(labels.errorGeneric || 'Could not send');
      })
      .finally(function () {
        if (sendBtn) {
          sendBtn.disabled = false;
          sendBtn.textContent = labels.send || 'Send';
        }
      });
  });

  document.addEventListener('visibilitychange', function () {
    if (panelOpen && !document.hidden) {
      fetchMessages();
    }
  });
})();
