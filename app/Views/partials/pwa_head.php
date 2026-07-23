<?php
declare(strict_types=1);

$pwaBase = pwa_web_base_url();
$swPath = pwa_service_worker_path();
$swScope = pwa_service_worker_scope();
?>
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars(app_name()); ?>">
<link rel="apple-touch-icon" href="<?php echo htmlspecialchars($pwaBase); ?>/icons/icon-192.png">
<script>
window.__pwaDeferredInstall = null;
window.addEventListener('beforeinstallprompt', function (e) {
  e.preventDefault();
  window.__pwaDeferredInstall = e;
  document.dispatchEvent(new CustomEvent('pwa-install-ready'));
});
if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
  document.documentElement.classList.add('pwa-standalone');
}
try {
  if (window.localStorage.getItem('szvs_pwa_install_dismissed') === '1') {
    document.documentElement.classList.add('pwa-install-dismissed');
  }
} catch (e) {}
(function () {
  if (!('serviceWorker' in navigator)) {
    return;
  }
  var swPath = <?php echo json_encode($swPath); ?>;
  var swScope = <?php echo json_encode($swScope); ?>;

  function registerSw() {
    navigator.serviceWorker.register(swPath, { scope: swScope })
      .then(function (reg) {
        window.SanghSamparkPwaSw = reg;
        if (reg.waiting && navigator.serviceWorker.controller) {
          reg.waiting.postMessage({ type: 'SKIP_WAITING' });
        }
      })
      .catch(function (err) {
        window.SanghSamparkPwaSwError = err && err.message ? err.message : 'register_failed';
      });
  }

  if (document.readyState === 'complete') {
    registerSw();
  } else {
    window.addEventListener('load', registerSw, { once: true });
  }
})();
</script>
