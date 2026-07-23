(function () {
  'use strict';

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.tagName !== 'FORM') {
      return;
    }
    if (form.getAttribute('data-no-submit-guard') === '1') {
      return;
    }
    if (form.getAttribute('data-submit-guarded') === '1') {
      e.preventDefault();
      return;
    }

    var submits = form.querySelectorAll('button[type="submit"], input[type="submit"]');
    if (!submits.length) {
      return;
    }

    var anyEnabled = false;
    submits.forEach(function (btn) {
      if (!btn.disabled) {
        anyEnabled = true;
      }
    });
    if (!anyEnabled) {
      e.preventDefault();
      return;
    }

    form.setAttribute('data-submit-guarded', '1');
    submits.forEach(function (btn) {
      if (btn.disabled) {
        return;
      }
      if (!btn.getAttribute('data-submit-guard-original-html')) {
        btn.setAttribute('data-submit-guard-original-html', btn.innerHTML);
      }
      btn.disabled = true;
      btn.setAttribute('aria-busy', 'true');
      var waitLabel = btn.getAttribute('data-submit-wait') || 'Please wait…';
      btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>' + waitLabel;
    });

    var noteId = 'submit-guard-note-' + (form.id || 'form');
    if (!form.querySelector('#' + noteId)) {
      var note = document.createElement('p');
      note.id = noteId;
      note.className = 'text-muted small mt-2 mb-0 submit-guard-note';
      note.textContent = form.getAttribute('data-submit-guard-note')
        || 'Please wait. Do not click again.';
      form.appendChild(note);
    }
  }, true);
})();
