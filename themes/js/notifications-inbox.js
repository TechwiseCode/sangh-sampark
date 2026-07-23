(function () {
  'use strict';

  var cfg = window.SanghSamparkNotificationsInbox || {};
  var base = (cfg.baseUrl || '').replace(/\/$/, '');
  if (!base) {
    return;
  }

  var unreadList = document.getElementById('notifications_inbox_unread');
  var readList = document.getElementById('notifications_inbox_read');
  var emptyState = document.getElementById('notifications_inbox_empty');
  var readEmptyState = document.getElementById('notifications_inbox_read_empty');
  var loadWrap = document.getElementById('notifications_inbox_load_wrap');
  var loadBtn = document.getElementById('notifications_inbox_load_more');
  var loadStatus = document.getElementById('notifications_inbox_load_status');

  if (!loadBtn) {
    return;
  }

  var offset = parseInt(String(cfg.initialOffset || 0), 10) || 0;
  var pageSize = parseInt(String(cfg.pageSize || 7), 10) || 7;
  var hasMore = !!cfg.hasMore;
  var loading = false;

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
  }

  function nl2br(text) {
    return escapeHtml(text).replace(/\n/g, '<br>');
  }

  function iconClassForType(type) {
    if (type === 'broadcast') {
      return 'mdi-bullhorn-outline';
    }
    if (type === 'relationship_request') {
      return 'mdi-account-arrow-right-outline';
    }
    if (type === 'member_admin_chat' || type === 'member_admin_chat_reply') {
      return 'mdi-message-text-outline';
    }
    return 'mdi-bell-outline';
  }

  function buildItem(item) {
    var li = document.createElement('li');
    li.className = 'notifications-inbox-item' + (item.isUnread ? ' is-unread' : ' is-read');
    var statusLabel = item.isUnread ? (cfg.unreadLabel || 'Unread') : (cfg.readLabel || 'Read');
    var statusClass = item.isUnread ? 'notifications-unread-badge' : 'notifications-read-badge';
    var chatUrl = null;
    if (
      cfg.memberChatEnabled
      && item.type === 'member_admin_chat'
      && item.referenceId > 0
      && cfg.memberChatBaseUrl
    ) {
      chatUrl = cfg.memberChatBaseUrl + String(item.referenceId);
    }

    var html =
      '<div class="notifications-inbox-item__icon" aria-hidden="true">' +
        '<i class="mdi ' + iconClassForType(item.type) + '"></i>' +
      '</div>' +
      '<div class="notifications-inbox-body">' +
        '<div class="notifications-inbox-head">' +
          '<div class="notifications-inbox-title-wrap">' +
            '<strong class="notifications-inbox-title">' + escapeHtml(item.title) + '</strong>' +
            '<span class="' + statusClass + '">' + escapeHtml(statusLabel) + '</span>' +
          '</div>' +
          '<time class="notifications-inbox-date">' + escapeHtml(item.createdLabel || '') + '</time>' +
        '</div>';

    if (item.message) {
      html += '<p class="notifications-inbox-message mb-0">' + nl2br(item.message) + '</p>';
    }
    if (chatUrl) {
      html +=
        '<p class="mb-0 mt-2">' +
          '<a class="btn btn-sm btn-outline-primary" href="' + escapeHtml(chatUrl) + '">' +
            escapeHtml(cfg.memberChatOpenLabel || 'Open chat') +
          '</a>' +
        '</p>';
    }
    if (item.isUnread) {
      html +=
        '<form method="post" action="' + escapeHtml(cfg.markReadUrl || (base + '/organization/notifications/mark-read')) + '" class="notifications-inbox-mark-read mb-0">' +
          '<input type="hidden" name="id" value="' + String(item.id || 0) + '">' +
          '<button type="submit" class="notifications-inbox-mark-read-btn">' +
            '<i class="mdi mdi-check" aria-hidden="true"></i> ' + escapeHtml(cfg.markReadLabel || 'Mark read') +
          '</button>' +
        '</form>';
    }

    html += '</div>';
    li.innerHTML = html;
    return li;
  }

  function appendItems(items) {
    if (!items || !items.length) {
      return;
    }
    if (emptyState) {
      emptyState.classList.add('d-none');
    }
    items.forEach(function (item) {
      var target = item.isUnread ? unreadList : readList;
      if (!target) {
        return;
      }
      target.classList.remove('d-none');
      target.appendChild(buildItem(item));
      if (!item.isUnread && readEmptyState) {
        readEmptyState.classList.add('d-none');
      }
    });
  }

  function setLoading(on) {
    loading = !!on;
    if (loadBtn) {
      loadBtn.disabled = on;
      loadBtn.textContent = on
        ? (cfg.loadingMoreLabel || 'Loading…')
        : (cfg.loadMoreLabel || 'Load more');
    }
    if (loadStatus) {
      if (on) {
        loadStatus.textContent = cfg.loadingMoreLabel || 'Loading…';
        loadStatus.classList.remove('d-none');
      } else {
        loadStatus.classList.add('d-none');
      }
    }
  }

  function updateLoadUi() {
    if (!loadWrap) {
      return;
    }
    if (hasMore) {
      loadWrap.classList.remove('d-none');
      if (loadBtn) {
        loadBtn.classList.remove('d-none');
        loadBtn.disabled = false;
        loadBtn.textContent = cfg.loadMoreLabel || 'Load more';
      }
    } else {
      if (loadBtn) {
        loadBtn.classList.add('d-none');
      }
      if (offset > 0 && loadStatus) {
        loadStatus.textContent = cfg.loadMoreDoneLabel || 'No more notifications.';
        loadStatus.classList.remove('d-none');
      } else {
        loadWrap.classList.add('d-none');
      }
    }
  }

  function loadMore() {
    if (!hasMore || loading) {
      return;
    }
    setLoading(true);
    var url = (cfg.listUrl || (base + '/organization/notifications/list'))
      + '?inbox=1&limit=' + encodeURIComponent(String(pageSize))
      + '&offset=' + encodeURIComponent(String(offset));

    fetch(url, {
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        if (!data || !data.ok) {
          throw new Error('Failed to load');
        }
        appendItems(data.items || []);
        offset += (data.items || []).length;
        hasMore = !!data.hasMore;
        updateLoadUi();
      })
      .catch(function () {
        if (loadStatus) {
          loadStatus.textContent = cfg.loadingMoreLabel || 'Loading…';
          loadStatus.classList.remove('d-none');
        }
      })
      .then(function () {
        setLoading(false);
      });
  }

  loadBtn.addEventListener('click', loadMore);
  updateLoadUi();
})();
