(function () {
  'use strict';

  if (typeof pdfjsLib !== 'undefined') {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    function renderThumb(canvas) {
      var url = canvas.getAttribute('data-pdf-url') || '';
      var wrap = canvas.closest('.notices-thumb');
      if (!url || !wrap) {
        return;
      }

      var thumbWidth = parseInt(canvas.getAttribute('data-thumb-w') || '140', 10) || 140;
      var thumbHeight = parseInt(canvas.getAttribute('data-thumb-h') || '180', 10) || 180;

      pdfjsLib.getDocument({ url: url, withCredentials: true }).promise
        .then(function (pdf) {
          return pdf.getPage(1);
        })
        .then(function (page) {
          var viewport = page.getViewport({ scale: 1 });
          var scale = Math.min(thumbWidth / viewport.width, thumbHeight / viewport.height);
          var scaled = page.getViewport({ scale: scale });
          canvas.width = Math.floor(scaled.width);
          canvas.height = Math.floor(scaled.height);
          return page.render({
            canvasContext: canvas.getContext('2d'),
            viewport: scaled
          }).promise;
        })
        .then(function () {
          wrap.classList.add('is-ready');
        })
        .catch(function () {
          wrap.classList.add('is-error');
          var loading = wrap.querySelector('.notices-thumb__loading');
          if (loading) {
            loading.textContent = (window.NoticeBoardI18n && window.NoticeBoardI18n.thumbError) || 'Preview unavailable';
          }
        });
    }

    document.querySelectorAll('.notices-thumb__canvas').forEach(function (canvas) {
      renderThumb(canvas);
    });
  }

  var scroller = document.getElementById('dashNoticesScroller');
  var modal = document.getElementById('dashNoticesModal');
  if (!scroller || !modal) {
    return;
  }

  var cards = scroller.querySelectorAll('.dash-notices__card');
  var titleEl = document.getElementById('dashNoticesModalTitle');
  var frame = document.getElementById('dashNoticesModalFrame');
  var downloadBtn = document.getElementById('dashNoticesModalDownload');
  var docPanel = document.getElementById('dashNoticesModalDoc');
  var docDownload = document.getElementById('dashNoticesModalDocDownload');
  var activeCard = null;

  function closeModal() {
    modal.hidden = true;
    document.body.classList.remove('notices-viewer-open');
    if (frame) {
      frame.hidden = true;
      frame.removeAttribute('src');
    }
    if (docPanel) {
      docPanel.hidden = true;
    }
    if (activeCard) {
      activeCard.classList.remove('is-active');
      activeCard = null;
    }
  }

  function openModal(card) {
    if (!card) {
      return;
    }

    var fileUrl = card.getAttribute('data-file-url') || '';
    var downloadUrl = card.getAttribute('data-download-url') || fileUrl;
    var mime = card.getAttribute('data-mime') || '';
    var title = card.getAttribute('data-title') || '';
    var isPdf = mime === 'application/pdf';

    if (activeCard && activeCard !== card) {
      activeCard.classList.remove('is-active');
    }
    activeCard = card;
    card.classList.add('is-active');

    if (titleEl) {
      titleEl.textContent = title;
    }
    if (downloadBtn) {
      downloadBtn.href = downloadUrl;
    }
    if (docDownload) {
      docDownload.href = downloadUrl;
    }

    if (isPdf && frame) {
      frame.hidden = false;
      frame.src = fileUrl;
      if (docPanel) {
        docPanel.hidden = true;
      }
      if (downloadBtn) {
        downloadBtn.hidden = false;
      }
    } else {
      if (frame) {
        frame.hidden = true;
        frame.removeAttribute('src');
      }
      if (docPanel) {
        docPanel.hidden = false;
      }
      if (downloadBtn) {
        downloadBtn.hidden = true;
      }
    }

    modal.hidden = false;
    document.body.classList.add('notices-viewer-open');
  }

  cards.forEach(function (card) {
    card.addEventListener('click', function () {
      openModal(card);
    });
  });

  modal.querySelectorAll('[data-dash-notices-close]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });
})();
