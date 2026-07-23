(function () {
  function lowercaseValue(input) {
    var value = input.value;
    var lower = value.toLowerCase();
    if (value === lower) {
      return;
    }
    var start = input.selectionStart;
    var end = input.selectionEnd;
    input.value = lower;
    if (start !== null && end !== null) {
      input.setSelectionRange(start, end);
    }
  }

  function bindEmailInput(input) {
    if (!input || input.dataset.emailLowercaseBound === '1') {
      return;
    }
    input.dataset.emailLowercaseBound = '1';
    input.addEventListener('input', function () {
      lowercaseValue(input);
    });
    input.addEventListener('blur', function () {
      lowercaseValue(input);
    });
  }

  function bindIdentityInput(input) {
    if (!input || input.dataset.identityLowercaseBound === '1') {
      return;
    }
    input.dataset.identityLowercaseBound = '1';
    input.addEventListener('input', function () {
      if (input.value.indexOf('@') === -1) {
        return;
      }
      lowercaseValue(input);
    });
    input.addEventListener('blur', function () {
      if (input.value.indexOf('@') === -1) {
        return;
      }
      lowercaseValue(input);
    });
  }

  function initEmailLowercase(root) {
    var scope = root || document;
    scope.querySelectorAll('input[type="email"]').forEach(bindEmailInput);
    scope.querySelectorAll('input.js-lowercase-email').forEach(bindEmailInput);
    scope.querySelectorAll('input.js-lowercase-identity').forEach(bindIdentityInput);
    ['identity', 'identity-org'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) {
        bindIdentityInput(el);
      }
    });
    var forgotIdentity = scope.querySelector('form[action*="/forgot-password"] input[name="identity"]');
    if (forgotIdentity) {
      bindIdentityInput(forgotIdentity);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initEmailLowercase(document);
    });
  } else {
    initEmailLowercase(document);
  }

  window.initEmailLowercase = initEmailLowercase;
})();
