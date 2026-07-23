/* global self, caches, fetch */
/**
 * Lightweight SW: offline page fallback only. Static assets bypass the SW so
 * login and dashboard load at full network speed.
 */
var CACHE = 'sanghsampark-v11';

function cacheUrl(path) {
  return new URL(path, self.location.href).href;
}

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE).then(function (cache) {
      return cache.add(cacheUrl('./offline.html')).catch(function () {});
    })
  );
  self.skipWaiting();
});

self.addEventListener('message', function (event) {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(
        keys
          .filter(function (k) {
            return (k.indexOf('sanghsampark-') === 0 || k.indexOf('szvs-saas-') === 0) && k !== CACHE;
          })
          .map(function (k) {
            return caches.delete(k);
          })
      );
    }).then(function () {
      return self.clients.claim();
    })
  );
});

self.addEventListener('fetch', function (event) {
  var req = event.request;
  if (req.method !== 'GET' || req.mode !== 'navigate') {
    return;
  }
  event.respondWith(
    fetch(req).catch(function () {
      return caches.match(cacheUrl('./offline.html'));
    })
  );
});

self.addEventListener('push', function (event) {
  var payload = {};
  try {
    payload = event.data ? event.data.json() : {};
  } catch (e) {
    payload = { title: 'Notification', body: event.data ? event.data.text() : '' };
  }
  var title = payload.title || 'Notification';
  var data = payload.data || {};
  var options = {
    body: payload.body || '',
    icon: './icons/icon-192.png',
    badge: './icons/icon-192.png',
    data: data,
    tag: data.tag || (data.notificationId ? 'notif-' + data.notificationId : 'notif-generic'),
    renotify: true
  };
  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var data = event.notification.data || {};
  var targetUrl = data.url || './organization/notifications';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      var absoluteTarget = new URL(targetUrl, self.location.href).href;
      var origin = self.location.origin;
      for (var i = 0; i < clientList.length; i++) {
        var client = clientList[i];
        if (client.url.indexOf(origin) !== 0 || !('focus' in client)) {
          continue;
        }
        if (typeof client.navigate === 'function') {
          return client.focus().then(function () {
            return client.navigate(absoluteTarget);
          });
        }
        return client.focus();
      }
      if (clients.openWindow) {
        return clients.openWindow(absoluteTarget);
      }
    })
  );
});
