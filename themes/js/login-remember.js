(function () {
  'use strict';

  function createStore(storageKey) {
    return {
      read: function () {
        try {
          var raw = localStorage.getItem(storageKey);
          if (!raw) {
            return null;
          }
          var data = JSON.parse(raw);
          return data && typeof data === 'object' ? data : null;
        } catch (e) {
          return null;
        }
      },
      write: function (data) {
        try {
          localStorage.setItem(storageKey, JSON.stringify(data));
        } catch (e) {
          /* storage full or blocked */
        }
      },
      clear: function () {
        try {
          localStorage.removeItem(storageKey);
        } catch (e) {
          /* ignore */
        }
      }
    };
  }

  function credentialIdForOrg(orgCode, identity) {
    return String(orgCode || '').toUpperCase() + '|' + String(identity || '');
  }

  function parseOrgCredentialId(id) {
    var raw = String(id || '');
    var pipe = raw.indexOf('|');
    if (pipe > 0) {
      return {
        org_code: raw.slice(0, pipe).toUpperCase(),
        identity: raw.slice(pipe + 1)
      };
    }
    return { identity: raw };
  }

  function saveBrowserCredential(fields) {
    // Disabled: credentials.store() opens a Save-password UI that can hang the login redirect.
    return Promise.resolve();
  }

  function loadBrowserCredential() {
    return Promise.resolve(null);
  }

  window.SanghSamparkLoginStore = {
    org: createStore('sanghsampark_org_login_v1'),
    superadmin: createStore('sanghsampark_super_login_v1'),
    create: createStore,
    saveBrowserCredential: saveBrowserCredential,
    loadBrowserCredential: loadBrowserCredential,
    parseOrgCredentialId: parseOrgCredentialId,
    credentialIdForOrg: credentialIdForOrg
  };
})();
