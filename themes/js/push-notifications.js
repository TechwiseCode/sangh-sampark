(function () {
  'use strict';

  var cfg = window.SanghSamparkPush || {};
  var base = (cfg.baseUrl || '').replace(/\/$/, '');
  var swRegistrationPromise = null;

  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    var b64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var rawData = window.atob(b64);
    var outputArray = new Uint8Array(rawData.length);
    for (var i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  }

  function isIOSDevice() {
    return /iPhone|iPad|iPod/i.test(navigator.userAgent)
      || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  }

  function isStandalonePwa() {
    return window.matchMedia('(display-mode: standalone)').matches
      || window.navigator.standalone === true;
  }

  function setIosHints(visible, standalone) {
    var installEl = document.getElementById('push_ios_install_hint');
    var settingsEl = document.getElementById('push_ios_settings_hint');
    if (installEl) {
      installEl.style.display = visible && !standalone ? '' : 'none';
      if (visible && !standalone && cfg.iosInstallHint) {
        installEl.textContent = cfg.iosInstallHint;
      }
    }
    if (settingsEl) {
      settingsEl.style.display = visible && standalone ? '' : 'none';
      if (visible && standalone && cfg.iosSettingsHint) {
        settingsEl.textContent = cfg.iosSettingsHint;
      }
    }
  }

  function fetchJson(path, options) {
    return fetch(base + path, Object.assign({ credentials: 'same-origin' }, options || {}))
      .then(function (res) {
        return res.json().then(function (data) {
          if (!res.ok) {
            throw new Error((data && data.error) || 'Request failed');
          }
          return data;
        });
      });
  }

  function ensureServiceWorker() {
    if (!('serviceWorker' in navigator)) {
      return Promise.reject(new Error(cfg.unsupportedMessage || 'Service workers are not supported in this browser.'));
    }
    if (!swRegistrationPromise) {
      var swUrl = base + '/service-worker.js';
      var swScope = base + '/';
      swRegistrationPromise = navigator.serviceWorker.register(swUrl, { scope: swScope }).catch(function (err) {
        swRegistrationPromise = null;
        throw new Error((cfg.swFailedMessage || 'Could not register service worker.') + ' ' + (err && err.message ? err.message : ''));
      });
    }
    return swRegistrationPromise;
  }

  function getRegistration() {
    return ensureServiceWorker();
  }

  function getCurrentSubscription() {
    return getRegistration().then(function (registration) {
      return registration.pushManager.getSubscription();
    });
  }

  function saveSubscription(subscription) {
    var json = subscription.toJSON();
    return fetchJson('/organization/notifications/push/subscribe', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        endpoint: json.endpoint,
        keys: json.keys || {}
      })
    });
  }

  function fetchServerStatus() {
    return fetchJson('/organization/notifications/push/status');
  }

  function syncSubscriptionToServer(subscription) {
    if (!subscription) {
      return Promise.resolve(null);
    }
    return saveSubscription(subscription).then(function () {
      return fetchServerStatus();
    });
  }

  function setServerSyncHint(message, visible) {
    var el = document.getElementById('push_server_sync_hint');
    if (!el) return;
    if (visible && message) {
      el.textContent = message;
      el.style.display = '';
    } else {
      el.textContent = '';
      el.style.display = 'none';
    }
  }

  function removeSubscription(subscription) {
    return fetchJson('/organization/notifications/push/unsubscribe', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ endpoint: subscription.endpoint })
    });
  }

  function ensureNotificationPermission() {
    if (!('Notification' in window)) {
      return Promise.reject(new Error(cfg.unsupportedMessage || 'Notifications are not supported in this browser.'));
    }
    if (Notification.permission === 'granted') {
      return Promise.resolve();
    }
    if (Notification.permission === 'denied') {
      return Promise.reject(new Error(cfg.deniedMessage || 'Notifications are blocked in your browser settings.'));
    }
    return Notification.requestPermission().then(function (result) {
      if (result !== 'granted') {
        throw new Error(cfg.permissionDeclinedMessage || 'Notification permission was not granted.');
      }
    });
  }

  function subscribeUser() {
    if (!cfg.configured) {
      return Promise.reject(new Error(cfg.notConfiguredMessage || 'Push is not configured.'));
    }
    if (!window.isSecureContext) {
      return Promise.reject(new Error(cfg.httpsRequiredMessage || 'Push notifications require HTTPS.'));
    }
    if (!('PushManager' in window)) {
      return Promise.reject(new Error(cfg.unsupportedMessage || 'Push is not supported in this browser.'));
    }
    return ensureNotificationPermission()
      .then(function () {
        return fetchJson('/organization/notifications/push/vapid-public-key');
      })
      .then(function (data) {
        return getRegistration().then(function (registration) {
          var subPromise = typeof navigator.serviceWorker.ready !== 'undefined'
            ? navigator.serviceWorker.ready.then(function () { return registration; })
            : Promise.resolve(registration);
          return subPromise.then(function (reg) {
            return reg.pushManager.subscribe({
              userVisibleOnly: true,
              applicationServerKey: urlBase64ToUint8Array(data.publicKey)
            });
          });
        });
      })
      .then(function (subscription) {
        return saveSubscription(subscription).then(function (res) {
          return subscription;
        });
      });
  }

  function unsubscribeUser() {
    return getCurrentSubscription().then(function (subscription) {
      if (!subscription) {
        return null;
      }
      return removeSubscription(subscription).then(function () {
        return subscription.unsubscribe();
      });
    });
  }

  function setButtonVisible(btn, visible) {
    if (!btn) {
      return;
    }
    btn.style.display = visible ? '' : 'none';
  }

  function setPushStatusMode(mode) {
    var wrap = document.getElementById('push_status_wrap');
    if (!wrap) return;
    wrap.classList.remove('is-on', 'is-warn', 'is-muted');
    if (mode === 'on') {
      wrap.classList.add('is-on');
    } else if (mode === 'warn') {
      wrap.classList.add('is-warn');
    } else {
      wrap.classList.add('is-muted');
    }
  }

  function updateUi() {
    var statusEl = document.getElementById('push_status_text');
    var hintEl = document.getElementById('push_enable_hint');
    var enableBtn = document.getElementById('push_enable_btn');
    var disableBtn = document.getElementById('push_disable_btn');
    if (!statusEl) {
      return Promise.resolve();
    }
    var onIos = isIOSDevice();
    var standalone = isStandalonePwa();
    setIosHints(onIos, standalone);
    if (onIos && !standalone) {
      statusEl.textContent = cfg.iosInstallHint || cfg.unsupportedMessage || 'Install the app on your Home Screen first.';
      setButtonVisible(enableBtn, false);
      setButtonVisible(disableBtn, false);
      if (hintEl) hintEl.style.display = 'none';
      setPushStatusMode('warn');
      return Promise.resolve();
    }
    if (!cfg.configured) {
      statusEl.textContent = cfg.notConfiguredMessage || 'Push is not configured.';
      setButtonVisible(enableBtn, false);
      setButtonVisible(disableBtn, false);
      if (hintEl) hintEl.style.display = 'none';
      setPushStatusMode('warn');
      return Promise.resolve();
    }
    if (!window.isSecureContext) {
      statusEl.textContent = cfg.httpsRequiredMessage || 'Push notifications require HTTPS.';
      setButtonVisible(enableBtn, false);
      setButtonVisible(disableBtn, false);
      if (hintEl) hintEl.style.display = 'none';
      setPushStatusMode('warn');
      return Promise.resolve();
    }
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      statusEl.textContent = cfg.unsupportedMessage || 'Push is not supported in this browser.';
      setButtonVisible(enableBtn, false);
      setButtonVisible(disableBtn, false);
      if (hintEl) hintEl.style.display = 'none';
      setPushStatusMode('warn');
      return Promise.resolve();
    }
    if (Notification.permission === 'denied') {
      statusEl.textContent = cfg.deniedMessage || 'Notifications are blocked in your browser settings.';
      setButtonVisible(enableBtn, false);
      setButtonVisible(disableBtn, false);
      if (hintEl) hintEl.style.display = 'none';
      setPushStatusMode('warn');
      return Promise.resolve();
    }
    return getCurrentSubscription().then(function (subscription) {
      if (subscription) {
        return syncSubscriptionToServer(subscription).then(function (status) {
          var serverCount = status && status.subscriptionCount ? status.subscriptionCount : 0;
          if (serverCount < 1) {
            setServerSyncHint(cfg.serverSyncWarning || 'Server has no push subscription for this device.', true);
            setPushStatusMode('warn');
          } else {
            setServerSyncHint('', false);
            setPushStatusMode('on');
          }
          statusEl.textContent = cfg.enabledMessage || 'Push notifications are enabled on this device.';
          setButtonVisible(enableBtn, false);
          setButtonVisible(disableBtn, true);
          if (hintEl) hintEl.style.display = 'none';
        }).catch(function () {
          statusEl.textContent = cfg.enabledMessage || 'Push notifications are enabled on this device.';
          setButtonVisible(enableBtn, false);
          setButtonVisible(disableBtn, true);
          setServerSyncHint(cfg.serverSyncWarning || '', true);
          if (hintEl) hintEl.style.display = 'none';
          setPushStatusMode('warn');
        });
      } else {
        setServerSyncHint('', false);
        statusEl.textContent = cfg.disabledMessage || 'Enable push to get alerts on this device.';
        setButtonVisible(enableBtn, true);
        setButtonVisible(disableBtn, false);
        if (hintEl) hintEl.style.display = '';
        setPushStatusMode('muted');
      }
    }).catch(function (err) {
      statusEl.textContent = (err && err.message) ? err.message : (cfg.swFailedMessage || 'Could not set up push on this device.');
      setButtonVisible(enableBtn, true);
      setButtonVisible(disableBtn, false);
      if (hintEl) hintEl.style.display = '';
      setPushStatusMode('warn');
    });
  }

  function bindButtons() {
    var enableBtn = document.getElementById('push_enable_btn');
    var disableBtn = document.getElementById('push_disable_btn');
    if (enableBtn) {
      enableBtn.addEventListener('click', function () {
        enableBtn.disabled = true;
        subscribeUser()
          .then(updateUi)
          .catch(function (err) {
            alert(err && err.message ? err.message : 'Could not enable push notifications.');
            return updateUi();
          })
          .then(function () {
            enableBtn.disabled = false;
          });
      });
    }
    if (disableBtn) {
      disableBtn.addEventListener('click', function () {
        disableBtn.disabled = true;
        unsubscribeUser()
          .then(updateUi)
          .catch(function (err) {
            alert(err && err.message ? err.message : 'Could not disable push notifications.');
          })
          .then(function () {
            disableBtn.disabled = false;
          });
      });
    }
  }

  window.SanghSamparkPushApi = {
    subscribe: subscribeUser,
    unsubscribe: unsubscribeUser,
    refreshUi: updateUi
  };

  document.addEventListener('DOMContentLoaded', function () {
    if (!base) {
      return;
    }
    bindButtons();
    ensureServiceWorker()
      .then(updateUi)
      .catch(function (err) {
        var statusEl = document.getElementById('push_status_text');
        if (statusEl) {
          statusEl.textContent = (err && err.message) ? err.message : (cfg.swFailedMessage || 'Could not register service worker.');
        }
        setButtonVisible(document.getElementById('push_enable_btn'), false);
      });

    if (cfg.autoPrompt && cfg.configured && window.isSecureContext && Notification.permission === 'default') {
      if (!(isIOSDevice() && !isStandalonePwa())) {
        subscribeUser().then(updateUi).catch(function () {
          return updateUi();
        });
      }
    }
  });
})();
