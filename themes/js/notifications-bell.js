(function () {
  'use strict';

  var cfg = window.SanghSamparkNotifications || {};
  var base = (cfg.baseUrl || '').replace(/\/$/, '');
  if (!base) {
    return;
  }

  var root = document.getElementById('notifBellRoot');
  var toggle = document.getElementById('notifBellToggle');
  var menu = document.getElementById('notifBellMenu');
  var listEl = document.getElementById('notif_bell_list');
  var badgeEl = document.getElementById('notif_badge');
  var summaryEl = document.getElementById('notif_bell_summary');
  var markAllBtn = document.getElementById('notif_mark_all_btn');
  var sidebarBadges = document.querySelectorAll('.sidebar-notif-badge');

  if (!root || !toggle || !menu || !listEl) {
    return;
  }

  var unreadCount = parseInt(String(cfg.initialUnread || 0), 10) || 0;
  var pageSize = parseInt(String(cfg.pageSize || 7), 10) || 7;
  var isOpen = false;
  var pollTimer = null;
  var listOffset = 0;
  var listHasMore = false;
  var listLoading = false;
  var listObserver = null;
  var loadSentinel = null;

  function fetchJson(path, options) {
    var opts = Object.assign({ credentials: 'same-origin' }, options || {});
    opts.headers = Object.assign({
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }, opts.headers || {});
    return fetch(base + path, opts)
      .then(function (res) {
        return res.json().then(function (data) {
          if (!res.ok) {
            throw new Error((data && data.error) || 'Request failed');
          }
          return data;
        });
      });
  }

  function setUnreadCount(count) {
    unreadCount = Math.max(0, parseInt(String(count), 10) || 0);
    if (badgeEl) {
      if (unreadCount > 0) {
        badgeEl.hidden = false;
        badgeEl.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
      } else {
        badgeEl.hidden = true;
      }
    }
    if (summaryEl) {
      if (unreadCount > 0) {
        summaryEl.textContent = (cfg.unreadSummary || ':count unread')
          .replace(/:count|\{count\}/g, String(unreadCount));
        summaryEl.hidden = false;
      } else {
        summaryEl.textContent = '';
        summaryEl.hidden = true;
      }
    }
    if (markAllBtn) {
      markAllBtn.hidden = unreadCount < 1;
    }
    sidebarBadges.forEach(function (el) {
      if (unreadCount > 0) {
        el.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
        el.style.display = '';
      } else {
        el.style.display = 'none';
      }
    });
  }

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
  }

  function createItemButton(item) {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'notif-bell-item' + (item.isUnread ? ' is-unread' : '');
    btn.setAttribute('data-id', String(item.id || ''));
    var status = item.isUnread ? (cfg.unreadLabel || 'Unread') : (cfg.readLabel || 'Read');
    btn.innerHTML =
      '<span class="notif-bell-item__head">' +
        '<strong class="notif-bell-item__title">' + escapeHtml(item.title) + '</strong>' +
        '<span class="notif-bell-item__status' + (item.isUnread ? ' is-unread' : '') + '">' + escapeHtml(status) + '</span>' +
      '</span>' +
      (item.message ? '<span class="notif-bell-item__message">' + escapeHtml(item.message) + '</span>' : '') +
      '<span class="notif-bell-item__date">' + escapeHtml(item.createdLabel || '') + '</span>';
    btn.addEventListener('click', function () {
      var id = parseInt(btn.getAttribute('data-id') || '0', 10);
      if (id > 0 && item.isUnread) {
        markRead(id).finally(function () {
          window.location.href = base + '/organization/notifications';
        });
      } else {
        window.location.href = base + '/organization/notifications';
      }
    });
    return btn;
  }

  function ensureLoadSentinel() {
    if (loadSentinel && loadSentinel.parentNode === listEl) {
      return loadSentinel;
    }
    loadSentinel = document.createElement('div');
    loadSentinel.className = 'notif-bell-dropdown__sentinel';
    loadSentinel.setAttribute('aria-hidden', 'true');
    listEl.appendChild(loadSentinel);
    return loadSentinel;
  }

  function teardownListObserver() {
    if (listObserver) {
      listObserver.disconnect();
      listObserver = null;
    }
  }

  function setupListObserver() {
    teardownListObserver();
    if (!listHasMore || typeof IntersectionObserver === 'undefined') {
      return;
    }
    var sentinel = ensureLoadSentinel();
    listObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          loadMoreItems();
        }
      });
    }, {
      root: listEl,
      rootMargin: '40px'
    });
    listObserver.observe(sentinel);
  }

  function renderEmpty() {
    teardownListObserver();
    listEl.innerHTML = '<p class="notif-bell-dropdown__empty text-muted small mb-0">' + escapeHtml(cfg.emptyLabel || 'No notifications yet.') + '</p>';
    loadSentinel = null;
  }

  function appendItems(items, reset) {
    if (reset) {
      listEl.innerHTML = '';
      loadSentinel = null;
    }
    if (!items || !items.length) {
      if (reset) {
        renderEmpty();
      }
      return;
    }
    items.forEach(function (item) {
      if (loadSentinel && loadSentinel.parentNode === listEl) {
        listEl.insertBefore(createItemButton(item), loadSentinel);
      } else {
        listEl.appendChild(createItemButton(item));
      }
    });
    if (listHasMore) {
      ensureLoadSentinel();
      setupListObserver();
    } else {
      teardownListObserver();
      if (loadSentinel && loadSentinel.parentNode === listEl) {
        loadSentinel.remove();
        loadSentinel = null;
      }
    }
  }

  function showListLoadingMore() {
    if (!loadSentinel) {
      ensureLoadSentinel();
    }
    if (loadSentinel) {
      loadSentinel.textContent = cfg.loadingMoreLabel || 'Loading…';
      loadSentinel.classList.add('is-loading');
    }
  }

  function hideListLoadingMore() {
    if (loadSentinel) {
      loadSentinel.textContent = '';
      loadSentinel.classList.remove('is-loading');
    }
  }

  function applyListResponse(data, reset) {
    if (!data || !data.ok) {
      return;
    }
    setUnreadCount(data.unreadCount || 0);
    listHasMore = !!data.hasMore;
    if (reset) {
      listOffset = 0;
    }
    appendItems(data.items || [], reset);
    listOffset += (data.items || []).length;
  }

  function loadPreview(reset) {
    var offset = reset ? 0 : listOffset;
    var path = '/organization/notifications/list?limit=' + encodeURIComponent(String(pageSize))
      + '&offset=' + encodeURIComponent(String(offset));
    return fetchJson(path)
      .then(function (data) {
        applyListResponse(data, !!reset);
      })
      .catch(function () {
        if (reset) {
          renderEmpty();
        }
      });
  }

  function loadMoreItems() {
    if (!listHasMore || listLoading || !isOpen) {
      return;
    }
    listLoading = true;
    showListLoadingMore();
    var path = '/organization/notifications/list?limit=' + encodeURIComponent(String(pageSize))
      + '&offset=' + encodeURIComponent(String(listOffset));
    fetchJson(path)
      .then(function (data) {
        if (!data || !data.ok) {
          return;
        }
        listHasMore = !!data.hasMore;
        appendItems(data.items || [], false);
        listOffset += (data.items || []).length;
        if (typeof data.unreadCount !== 'undefined') {
          setUnreadCount(data.unreadCount);
        }
      })
      .catch(function () {})
      .then(function () {
        listLoading = false;
        hideListLoadingMore();
      });
  }

  function markRead(id) {
    return fetchJson('/organization/notifications/mark-read', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id })
    }).then(function (data) {
      if (data && data.ok) {
        setUnreadCount(data.unreadCount || 0);
      }
    });
  }

  function markAllRead() {
    return fetchJson('/organization/notifications/mark-read', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ all: true })
    }).then(function (data) {
      if (data && data.ok) {
        setUnreadCount(0);
        return loadPreview(true);
      }
    });
  }

  function closeMenu() {
    isOpen = false;
    menu.classList.remove('show');
    root.classList.remove('show');
    toggle.setAttribute('aria-expanded', 'false');
    menu.style.removeProperty('top');
    menu.style.removeProperty('right');
    menu.style.removeProperty('left');
    menu.style.removeProperty('width');
    teardownListObserver();
    if (window.SanghSamparkPopupOverlay) {
      window.SanghSamparkPopupOverlay.hide(closeMenu);
    }
  }

  function positionMenu() {
    var margin = 12;
    var safeLeft = margin;
    var safeRight = margin;
    try {
      safeLeft = Math.max(margin, parseFloat(getComputedStyle(document.documentElement).getPropertyValue('env(safe-area-inset-left)')) || margin);
      safeRight = Math.max(margin, parseFloat(getComputedStyle(document.documentElement).getPropertyValue('env(safe-area-inset-right)')) || margin);
    } catch (e) {
      safeLeft = margin;
      safeRight = margin;
    }

    var rect = toggle.getBoundingClientRect();
    var maxWidth = Math.min(352, window.innerWidth - safeLeft - safeRight);
    var top = rect.bottom + 8;
    var right = Math.max(safeRight, window.innerWidth - rect.right);

    menu.style.width = maxWidth + 'px';
    menu.style.top = top + 'px';
    menu.style.left = 'auto';
    menu.style.right = right + 'px';

    var menuLeft = window.innerWidth - right - maxWidth;
    if (menuLeft < safeLeft) {
      menu.style.right = 'auto';
      menu.style.left = safeLeft + 'px';
      menu.style.width = Math.min(352, window.innerWidth - safeLeft - safeRight) + 'px';
    }
  }

  function openMenu() {
    if (window.SanghSamparkUserMenu && window.SanghSamparkUserMenu.close) {
      window.SanghSamparkUserMenu.close();
    }
    isOpen = true;
    menu.classList.add('show');
    root.classList.add('show');
    toggle.setAttribute('aria-expanded', 'true');
    positionMenu();
    if (window.SanghSamparkPopupOverlay) {
      window.SanghSamparkPopupOverlay.show(closeMenu);
    }
    listOffset = 0;
    listHasMore = false;
    listEl.innerHTML = '<p class="notif-bell-dropdown__loading text-muted small mb-0">' + escapeHtml(cfg.loadingLabel || 'Loading…') + '</p>';
    loadPreview(true);
  }

  toggle.addEventListener('click', function (e) {
    e.preventDefault();
    if (isOpen) {
      closeMenu();
    } else {
      openMenu();
    }
  });

  document.addEventListener('click', function (e) {
    if (!isOpen) {
      return;
    }
    if (!root.contains(e.target)) {
      closeMenu();
    }
  });

  window.addEventListener('resize', function () {
    if (isOpen) {
      positionMenu();
    }
  });

  if (markAllBtn) {
    markAllBtn.addEventListener('click', function (e) {
      e.preventDefault();
      markAllBtn.disabled = true;
      markAllRead()
        .catch(function () {})
        .then(function () {
          markAllBtn.disabled = false;
        });
    });
  }

  setUnreadCount(unreadCount);
  fetchJson('/organization/notifications/list?limit=0&offset=0')
    .then(function (data) {
      if (data && data.ok) {
        setUnreadCount(data.unreadCount || 0);
      }
    })
    .catch(function () {});

  function startPolling() {
    if (pollTimer) {
      clearInterval(pollTimer);
    }
    pollTimer = setInterval(function () {
      if (document.hidden) {
        return;
      }
      fetchJson('/organization/notifications/list?limit=0&offset=0').then(function (data) {
        if (data && data.ok) {
          setUnreadCount(data.unreadCount || 0);
          if (isOpen && !listLoading) {
            loadPreview(true);
          }
        }
      }).catch(function () {});
    }, 60000);
  }

  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) {
      fetchJson('/organization/notifications/list?limit=0&offset=0').then(function (data) {
        if (data && data.ok) {
          setUnreadCount(data.unreadCount || 0);
        }
      }).catch(function () {});
    }
  });

  startPolling();

  window.SanghSamparkNotificationsApi = {
    refresh: function () { return loadPreview(true); },
    setUnreadCount: setUnreadCount,
    close: closeMenu
  };
})();
