(function () {
  'use strict';

  var cfg = window.SanghSamparkPwa || {};
  window.SanghSamparkPwa = cfg;
  var deferredPrompt = window.__pwaDeferredInstall || null;
  var iosModalEl = null;
  var installBusy = false;
  var promptWaiters = [];
  var DISMISS_KEY = 'szvs_pwa_install_dismissed';

  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  function isStandalone() {
    return document.documentElement.classList.contains('pwa-standalone')
      || window.matchMedia('(display-mode: standalone)').matches
      || window.navigator.standalone === true;
  }

  function isIos() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent || '');
  }

  function isInstallDismissed() {
    try {
      return window.localStorage.getItem(DISMISS_KEY) === '1';
    } catch (e) {
      return false;
    }
  }

  function markInstallDismissed() {
    try {
      window.localStorage.setItem(DISMISS_KEY, '1');
    } catch (e) {
      // ignore storage errors
    }
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function hideInstallBar() {
    document.documentElement.classList.add('pwa-standalone');
    document.documentElement.classList.add('pwa-install-dismissed');
  }

  function dismissInstallBar() {
    markInstallDismissed();
    hideInstallBar();
    removeIosModal();
  }

  function setPromoHint(text, isError) {
    var hint = document.getElementById('pwa_install_promo_hint');
    if (!hint) {
      return;
    }
    hint.textContent = text || '';
    hint.classList.toggle('is-error', !!isError && !!text);
  }

  function setInstallBusy(busy) {
    installBusy = !!busy;
    document.querySelectorAll('.js-pwa-install').forEach(function (btn) {
      btn.disabled = installBusy;
      btn.setAttribute('aria-busy', installBusy ? 'true' : 'false');
      var label = btn.querySelector('.pwa-install-bar__label');
      if (label) {
        label.textContent = installBusy
          ? (cfg.installPreparing || 'Preparing…')
          : (cfg.installBtn || cfg.install || 'Install app');
      }
    });
  }

  function markInstallReady() {
    var bar = document.getElementById('pwa_install_promo');
    if (bar) {
      bar.classList.add('is-ready');
    }
    setInstallBusy(false);
    setPromoHint('');
  }

  function notifyPromptWaiters(prompt) {
    var list = promptWaiters.slice();
    promptWaiters = [];
    list.forEach(function (resolve) {
      resolve(prompt);
    });
  }

  function waitForInstallPrompt(timeoutMs) {
    if (deferredPrompt) {
      return Promise.resolve(deferredPrompt);
    }
    return new Promise(function (resolve) {
      var done = false;
      var timer = setTimeout(function () {
        if (done) {
          return;
        }
        done = true;
        resolve(null);
      }, timeoutMs || 2500);
      promptWaiters.push(function (prompt) {
        if (done) {
          return;
        }
        done = true;
        clearTimeout(timer);
        resolve(prompt);
      });
    });
  }

  function captureInstallPrompt(e) {
    e.preventDefault();
    deferredPrompt = e;
    window.__pwaDeferredInstall = e;
    notifyPromptWaiters(e);
    markInstallReady();
  }

  function runNativeInstall() {
    if (!deferredPrompt) {
      return;
    }
    var prompt = deferredPrompt;
    setInstallBusy(true);
    prompt.prompt().catch(function () {}).finally(function () {
      deferredPrompt = null;
      window.__pwaDeferredInstall = null;
      setInstallBusy(false);
    });
  }

  function removeIosModal() {
    if (iosModalEl && iosModalEl.parentNode) {
      iosModalEl.parentNode.removeChild(iosModalEl);
    }
    iosModalEl = null;
    document.body.classList.remove('pwa-install-modal-open');
  }

  function showIosModal() {
    removeIosModal();
    var title = cfg.title || 'Install app';
    var steps = Array.isArray(cfg.iosSteps) ? cfg.iosSteps : [];
    iosModalEl = document.createElement('div');
    iosModalEl.className = 'pwa-install-modal';
    iosModalEl.setAttribute('role', 'dialog');
    iosModalEl.setAttribute('aria-modal', 'true');
    iosModalEl.innerHTML =
      '<div class="pwa-install-modal__backdrop" data-pwa-close="1"></div>' +
      '<div class="pwa-install-modal__panel">' +
        '<h3 class="pwa-install-modal__title">' + escapeHtml(title) + '</h3>' +
        '<p class="pwa-install-modal__text">' + escapeHtml(cfg.iosHelp || '') + '</p>' +
        (steps.length
          ? '<ol class="pwa-install-modal__steps">' + steps.map(function (s) {
            return '<li>' + escapeHtml(s) + '</li>';
          }).join('') + '</ol>'
          : '') +
        '<div class="pwa-install-modal__actions">' +
          '<button type="button" class="btn btn-primary btn-sm" data-pwa-close="1">' +
            escapeHtml(cfg.gotIt || 'Got it') +
          '</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(iosModalEl);
    document.body.classList.add('pwa-install-modal-open');
    iosModalEl.querySelectorAll('[data-pwa-close]').forEach(function (el) {
      el.addEventListener('click', removeIosModal);
    });
  }

  function promptInstall() {
    if (isStandalone() || installBusy) {
      return;
    }

    if (isIos()) {
      showIosModal();
      return;
    }

    if (deferredPrompt) {
      runNativeInstall();
      return;
    }

    setInstallBusy(true);
    waitForInstallPrompt(2500).then(function (prompt) {
      if (prompt) {
        runNativeInstall();
        return;
      }
      setInstallBusy(false);
      setPromoHint(cfg.installWaiting || '', false);
    });
  }

  function bindInstallTriggers() {
    document.querySelectorAll('.js-pwa-install').forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.preventDefault();
        promptInstall();
      });
    });
    document.querySelectorAll('.js-pwa-install-dismiss').forEach(function (el) {
      el.addEventListener('click', function (e) {
        e.preventDefault();
        dismissInstallBar();
      });
    });
  }

  function init() {
    if (window.__pwaDeferredInstall && !deferredPrompt) {
      deferredPrompt = window.__pwaDeferredInstall;
    }

    if (isStandalone() || isInstallDismissed()) {
      hideInstallBar();
      return;
    }

    bindInstallTriggers();

    if (deferredPrompt) {
      markInstallReady();
    }
  }

  window.addEventListener('beforeinstallprompt', captureInstallPrompt);
  document.addEventListener('pwa-install-ready', function () {
    if (!deferredPrompt && window.__pwaDeferredInstall) {
      deferredPrompt = window.__pwaDeferredInstall;
      markInstallReady();
    }
  });

  window.addEventListener('appinstalled', function () {
    deferredPrompt = null;
    window.__pwaDeferredInstall = null;
    removeIosModal();
    hideInstallBar();
  });

  cfg.promptInstall = promptInstall;

  onReady(init);
})();
