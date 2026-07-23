(function ($) {
  'use strict';

  var MOBILE_MQ = window.matchMedia('(max-width: 991px)');

  function isMobile() {
    return MOBILE_MQ.matches;
  }

  function isNavigableSidebarLink(link) {
    if (!link || link.tagName !== 'A') {
      return false;
    }
    // Submenu choices (e.g. Event receipts / Donation receipts) keep the drawer open.
    if (link.closest('.sub-menu')) {
      return false;
    }
    var href = (link.getAttribute('href') || '').trim();
    if (href === '' || href === '#' || href.charAt(0) === '#') {
      return false;
    }
    if (link.hasAttribute('data-bs-toggle') || link.hasAttribute('data-toggle')) {
      return false;
    }
    if (link.getAttribute('aria-controls')) {
      return false;
    }

    return true;
  }

  function shouldIgnoreMobileSidebarClick(target) {
    if (!target || !target.closest) {
      return false;
    }
    return !!target.closest(
      '.search-select-wrap, .search-select-menu, .search-select-toggle, .search-select-option, ' +
      '.nav-tabs, [data-tab-target]'
    );
  }

  function closeSidebar(sidebar) {
    if (!sidebar) {
      sidebar = document.getElementById('sidebar');
    }
    if (!sidebar) {
      return;
    }
    sidebar.classList.remove('active');
    document.body.classList.remove('sidebar-open');
    if (window.jQuery) {
      window.jQuery(sidebar).removeClass('active');
      window.jQuery('body').removeClass('sidebar-open');
    }
  }

  function openSidebar(sidebar) {
    if (!isMobile() || !sidebar) {
      return;
    }
    sidebar.classList.add('active');
    document.body.classList.add('sidebar-open');
    if (window.jQuery) {
      window.jQuery(sidebar).addClass('active');
      window.jQuery('body').addClass('sidebar-open');
    }
  }

  function toggleSidebar(sidebar) {
    if (!sidebar) {
      return;
    }
    if (sidebar.classList.contains('active')) {
      closeSidebar(sidebar);
    } else {
      openSidebar(sidebar);
    }
  }

  window.SanghSamparkSidebar = {
    close: function () {
      closeSidebar(document.getElementById('sidebar'));
    },
    isMobile: isMobile,
  };

  document.addEventListener('click', function (e) {
    if (!isMobile()) {
      return;
    }
    var sidebar = document.getElementById('sidebar');
    if (!sidebar || !sidebar.classList.contains('sidebar-offcanvas')) {
      return;
    }
    var toggleBtn = e.target.closest('[data-bs-toggle="offcanvas"]');
    if (toggleBtn) {
      e.preventDefault();
      toggleSidebar(sidebar);
      return;
    }
    if (shouldIgnoreMobileSidebarClick(e.target)) {
      return;
    }
    var link = e.target.closest('#sidebar a');
    if (!link || !sidebar.contains(link) || !isNavigableSidebarLink(link)) {
      return;
    }
    closeSidebar(sidebar);
  }, true);

  $(function () {
    var $sidebar = $('#sidebar.sidebar-offcanvas');
    if (!$sidebar.length) {
      return;
    }

    var sidebarEl = $sidebar.get(0);
    var $backdrop = $('<div>').addClass('sidebar-backdrop').attr('aria-hidden', 'true');
    $('body').append($backdrop);

    $backdrop.on('click', function () {
      closeSidebar(sidebarEl);
    });

    $(window).on('resize', function () {
      if (!isMobile()) {
        closeSidebar(sidebarEl);
      }
    });

    window.addEventListener('pageshow', function (event) {
      if (event.persisted) {
        closeSidebar(sidebarEl);
      }
    });

    closeSidebar(sidebarEl);
  });
})(jQuery);
