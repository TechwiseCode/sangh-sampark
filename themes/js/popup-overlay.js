(function () {
  'use strict';

  var el = null;
  var activeClose = null;

  function ensure() {
    if (el) {
      return el;
    }
    el = document.getElementById('popupOverlay');
    if (!el) {
      el = document.createElement('div');
      el.id = 'popupOverlay';
      el.className = 'popup-overlay';
      el.setAttribute('aria-hidden', 'true');
      document.body.appendChild(el);
    }
    el.addEventListener('click', function () {
      if (activeClose) {
        activeClose();
      }
    });
    return el;
  }

  function show(onClose) {
    ensure();
    if (activeClose && activeClose !== onClose) {
      activeClose();
    }
    activeClose = onClose || null;
    document.body.classList.add('popup-overlay-open');
    el.setAttribute('aria-hidden', 'false');
  }

  function hide(onClose) {
    if (!el || activeClose !== onClose) {
      return;
    }
    document.body.classList.remove('popup-overlay-open');
    el.setAttribute('aria-hidden', 'true');
    activeClose = null;
  }

  window.SanghSamparkPopupOverlay = {
    show: show,
    hide: hide
  };
})();
