(function () {
  'use strict';

  function token() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? (meta.getAttribute('content') || '') : '';
  }

  function ensureFormToken(form) {
    if (!form || form.method.toLowerCase() === 'get') {
      return;
    }
    var existing = form.querySelector('input[name="_csrf"]');
    var value = token();
    if (!value) {
      return;
    }
    if (existing) {
      existing.value = value;
      return;
    }
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = '_csrf';
    input.value = value;
    form.appendChild(input);
  }

  function injectAllForms() {
    var forms = document.querySelectorAll('form');
    for (var i = 0; i < forms.length; i++) {
      ensureFormToken(forms[i]);
    }
  }

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form && form.tagName === 'FORM') {
      ensureFormToken(form);
    }
  }, true);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectAllForms);
  } else {
    injectAllForms();
  }

  window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
      window.location.reload();
    }
  });

  if (typeof window.fetch !== 'function') {
    return;
  }

  function requestUrl(input) {
    if (typeof input === 'string') {
      return input;
    }
    if (input && typeof input.url === 'string') {
      return input.url;
    }
    return '';
  }

  function isSameOrigin(url) {
    try {
      return new URL(url, window.location.href).origin === window.location.origin;
    } catch (e) {
      return true;
    }
  }

  var nativeFetch = window.fetch.bind(window);
  window.fetch = function (input, init) {
    // Never touch third-party calls (GoDaddy TCCL, analytics, etc.).
    if (!isSameOrigin(requestUrl(input))) {
      return nativeFetch(input, init);
    }

    init = init || {};
    var method = (init.method || 'GET').toUpperCase();
    var headers = new Headers(init.headers || {});
    var t = token();
    if (method !== 'GET' && method !== 'HEAD' && method !== 'OPTIONS') {
      if (!headers.has('X-CSRF-Token') && !headers.has('X-XSRF-TOKEN') && t) {
        headers.set('X-CSRF-Token', t);
      }
      if (!headers.has('Accept')) {
        headers.set('Accept', 'application/json');
      }
      // Also embed token in JSON body (hosts that strip custom headers).
      if (t && typeof init.body === 'string' && headers.get('Content-Type') && headers.get('Content-Type').indexOf('application/json') !== -1) {
        try {
          var parsed = JSON.parse(init.body);
          if (parsed && typeof parsed === 'object' && !Array.isArray(parsed) && !parsed._csrf) {
            parsed._csrf = t;
            init.body = JSON.stringify(parsed);
          }
        } catch (e) {}
      }
    }
    init.headers = headers;
    if (init.credentials == null) {
      init.credentials = 'same-origin';
    }
    return nativeFetch(input, init);
  };

  window.SZVS_CSRF = {
    token: token,
    ensureFormToken: ensureFormToken
  };
})();
