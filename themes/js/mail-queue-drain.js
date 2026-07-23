(function () {
  'use strict';

  function baseUrl() {
    var meta = document.querySelector('meta[name="app-base-url"]');
    return meta ? (meta.getAttribute('content') || '').replace(/\/$/, '') : '';
  }

  function drainMailQueue(timeoutMs) {
    if (typeof window.fetch !== 'function') {
      return Promise.resolve({ ok: false, sent: 0 });
    }
    var base = baseUrl();
    if (base === '') {
      return Promise.resolve({ ok: false, sent: 0 });
    }

    var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
    var timeoutId = controller
      ? window.setTimeout(function () {
          controller.abort();
        }, typeof timeoutMs === 'number' ? timeoutMs : 30000)
      : null;

    return fetch(base + '/mail/process-queue', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' },
      signal: controller ? controller.signal : undefined
    })
      .then(function (r) {
        return r.json().catch(function () {
          return { ok: false, sent: 0 };
        });
      })
      .then(function (body) {
        return { ok: !!(body && body.ok), sent: body && typeof body.sent === 'number' ? body.sent : 0 };
      })
      .catch(function () {
        return { ok: false, sent: 0 };
      })
      .finally(function () {
        if (timeoutId) {
          window.clearTimeout(timeoutId);
        }
      });
  }

  function scheduleDrain() {
    window.setTimeout(function () {
      drainMailQueue(30000);
    }, 15000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scheduleDrain);
  } else {
    scheduleDrain();
  }

  window.SZVS_MAIL = {
    drain: drainMailQueue
  };
})();
