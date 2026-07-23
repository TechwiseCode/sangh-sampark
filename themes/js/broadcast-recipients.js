(function () {
  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function initBroadcastRecipients(config) {
    var form = document.getElementById('notify_broadcast_panel');
    if (!form || !config || !config.recipientsUrl) {
      return;
    }

    var applyBtn = document.getElementById('broadcast_apply_filters');
    var resetBtn = document.getElementById('broadcast_reset_filters');
    var tbody = document.getElementById('broadcast_recipients_tbody');
    var tableWrap = document.getElementById('broadcast_recipients_table_wrap');
    var loadingEl = document.getElementById('broadcast_recipients_loading');
    var emptyEl = document.getElementById('broadcast_recipients_empty');
    var summaryEl = document.getElementById('broadcast_recipients_summary');
    var checkAllEl = document.getElementById('broadcast_recipients_check_all');
    var selectAllBtn = document.getElementById('broadcast_select_all');
    var selectNoneBtn = document.getElementById('broadcast_select_none');
    var nameSearchEl = document.getElementById('broadcast_recipients_name_search');
    var nameSearchEmptyEl = document.getElementById('broadcast_recipients_name_empty');
    var filterSelects = form.querySelectorAll('#broadcast_filter, #broadcast_gender, #broadcast_profession, #broadcast_donation');
    var ageWrap = document.getElementById('broadcast_age_dropdown');
    var currentMembers = [];
    var preserveSelection = {};

    function getCheckedAgeValues() {
      if (!ageWrap) {
        return [];
      }
      return Array.prototype.map.call(
        ageWrap.querySelectorAll('input[type="checkbox"][name="age[]"]:checked'),
        function (input) {
          return input.value;
        }
      );
    }

    function buildQuery() {
      var params = new URLSearchParams();
      var filterEl = document.getElementById('broadcast_filter');
      var genderEl = document.getElementById('broadcast_gender');
      var professionEl = document.getElementById('broadcast_profession');
      var donationEl = document.getElementById('broadcast_donation');
      params.set('filter', filterEl ? filterEl.value : 'all');
      params.set('gender', genderEl ? genderEl.value : 'all');
      params.set('profession', professionEl ? professionEl.value : 'all');
      params.set('donation', donationEl ? donationEl.value : 'all');
      getCheckedAgeValues().forEach(function (ageValue) {
        params.append('age[]', ageValue);
      });
      return params.toString();
    }

    function setState(state) {
      if (loadingEl) {
        loadingEl.classList.toggle('d-none', state !== 'loading');
      }
      if (emptyEl) {
        emptyEl.classList.toggle('d-none', state !== 'empty');
      }
      if (tableWrap) {
        tableWrap.classList.toggle('d-none', state !== 'ready');
      }
    }

    function normalizeSearchQuery(value) {
      return String(value || '').trim().toLowerCase();
    }

    function isRowVisible(row) {
      return row && !row.classList.contains('d-none');
    }

    function getRecipientCheckboxes(visibleOnly) {
      if (!tbody) {
        return [];
      }
      return Array.prototype.filter.call(
        tbody.querySelectorAll('tr'),
        function (row) {
          return !visibleOnly || isRowVisible(row);
        }
      ).map(function (row) {
        return row.querySelector('input[type="checkbox"][name="recipient_ids[]"]');
      }).filter(Boolean);
    }

    function countSelected() {
      return getRecipientCheckboxes(false).filter(function (input) {
        return input.checked;
      }).length;
    }

    function applyNameSearch() {
      if (!tbody) {
        return;
      }
      var query = normalizeSearchQuery(nameSearchEl ? nameSearchEl.value : '');
      var rows = tbody.querySelectorAll('tr');
      var visibleCount = 0;
      rows.forEach(function (row) {
        var nameCell = row.cells && row.cells[1];
        var name = nameCell ? nameCell.textContent.trim().toLowerCase() : '';
        var show = !query || name.indexOf(query) !== -1;
        row.classList.toggle('d-none', !show);
        if (show) {
          visibleCount += 1;
        }
      });
      if (nameSearchEmptyEl) {
        var showNameEmpty = rows.length > 0 && query && visibleCount === 0;
        nameSearchEmptyEl.classList.toggle('d-none', !showNameEmpty);
      }
      if (tableWrap) {
        var hideTable = rows.length > 0 && query && visibleCount === 0;
        tableWrap.classList.toggle('d-none', hideTable);
      }
      updateSummary();
    }

    function clearNameSearch() {
      if (nameSearchEl) {
        nameSearchEl.value = '';
      }
      if (nameSearchEmptyEl) {
        nameSearchEmptyEl.classList.add('d-none');
      }
      if (tbody) {
        tbody.querySelectorAll('tr').forEach(function (row) {
          row.classList.remove('d-none');
        });
      }
    }

    function updateSummary() {
      if (!summaryEl) {
        return;
      }
      var selected = countSelected();
      var total = currentMembers.length;
      if (total === 0) {
        summaryEl.textContent = '';
        return;
      }
      summaryEl.textContent = String(config.selectedSummary || ':selected of :total selected')
        .replace(':selected', String(selected))
        .replace(':total', String(total));
      if (checkAllEl) {
        var visibleInputs = getRecipientCheckboxes(true);
        var visibleSelected = visibleInputs.filter(function (input) {
          return input.checked;
        }).length;
        var visibleTotal = visibleInputs.length;
        checkAllEl.checked = visibleTotal > 0 && visibleSelected === visibleTotal;
        checkAllEl.indeterminate = visibleSelected > 0 && visibleSelected < visibleTotal;
      }
    }

    function setAllChecked(checked) {
      var visibleOnly = normalizeSearchQuery(nameSearchEl ? nameSearchEl.value : '') !== '';
      getRecipientCheckboxes(visibleOnly).forEach(function (input) {
        input.checked = checked;
        preserveSelection[Number(input.value)] = checked;
      });
      updateSummary();
    }

    function renderMembers(members) {
      currentMembers = Array.isArray(members) ? members : [];
      if (!tbody) {
        return;
      }
      if (currentMembers.length === 0) {
        tbody.innerHTML = '';
        clearNameSearch();
        setState('empty');
        if (summaryEl) {
          summaryEl.textContent = '';
        }
        if (checkAllEl) {
          checkAllEl.checked = false;
          checkAllEl.indeterminate = false;
        }
        return;
      }

      var html = '';
      currentMembers.forEach(function (member) {
        var id = Number(member.id || 0);
        if (!id) {
          return;
        }
        var checked = preserveSelection[id] !== false;
        var headBadge = member.is_head
          ? ' <span class="badge badge-members-head ml-1">' + escapeHtml(config.headBadge || 'Head') + '</span>'
          : '';
        html += '<tr>'
          + '<td class="broadcast-recipients-col-check"><input type="checkbox" name="recipient_ids[]" value="' + id + '"' + (checked ? ' checked' : '') + '></td>'
          + '<td><span class="person-name-inline">' + escapeHtml(member.name || '') + '</span>' + headBadge + '</td>'
          + '<td>' + escapeHtml(member.code || '—') + '</td>'
          + '<td>' + escapeHtml(member.age != null ? String(member.age) : '—') + '</td>'
          + '<td>' + escapeHtml(member.gender || '—') + '</td>'
          + '<td>' + escapeHtml(member.profession || '—') + '</td>'
          + '</tr>';
      });
      tbody.innerHTML = html;
      setState('ready');
      applyNameSearch();
    }

    function rememberSelection() {
      preserveSelection = {};
      if (!tbody) {
        return;
      }
      tbody.querySelectorAll('input[type="checkbox"][name="recipient_ids[]"]').forEach(function (input) {
        preserveSelection[Number(input.value)] = input.checked;
      });
    }

    function resetFilters() {
      var filterEl = document.getElementById('broadcast_filter');
      var genderEl = document.getElementById('broadcast_gender');
      var professionEl = document.getElementById('broadcast_profession');
      var donationEl = document.getElementById('broadcast_donation');
      if (filterEl) filterEl.value = 'all';
      if (genderEl) genderEl.value = 'all';
      if (professionEl) professionEl.value = 'all';
      if (donationEl) donationEl.value = 'all';
      if (ageWrap) {
        ageWrap.querySelectorAll('input[type="checkbox"][name="age[]"]').forEach(function (input) {
          input.checked = false;
        });
        var labelEl = ageWrap.querySelector('.members-age-dropdown-label');
        if (labelEl && config.allAgeLabel) {
          labelEl.textContent = config.allAgeLabel;
        }
      }
      preserveSelection = {};
      clearNameSearch();
      loadRecipients();
    }

    function loadRecipients() {
      rememberSelection();
      clearNameSearch();
      setState('loading');
      if (summaryEl) {
        summaryEl.textContent = config.selectedSummary
          ? String(config.selectedSummary).replace(':selected', '0').replace(':total', '0')
          : '';
      }
      fetch(config.recipientsUrl + '?' + buildQuery(), {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          if (!data || !data.ok) {
            throw new Error((data && data.error) || 'Failed to load members');
          }
          var nextPreserve = {};
          (data.members || []).forEach(function (member) {
            var id = Number(member.id || 0);
            if (!id) {
              return;
            }
            if (Object.prototype.hasOwnProperty.call(preserveSelection, id)) {
              nextPreserve[id] = preserveSelection[id];
            } else {
              nextPreserve[id] = true;
            }
          });
          preserveSelection = nextPreserve;
          renderMembers(data.members || []);
        })
        .catch(function () {
          currentMembers = [];
          if (tbody) {
            tbody.innerHTML = '';
          }
          setState('empty');
          if (emptyEl) {
            emptyEl.textContent = 'Could not load members. Please try again.';
          }
        });
    }

    if (nameSearchEl) {
      nameSearchEl.addEventListener('input', applyNameSearch);
      nameSearchEl.addEventListener('search', applyNameSearch);
    }

    if (tbody) {
      tbody.addEventListener('change', function (event) {
        var target = event.target;
        if (!target || target.name !== 'recipient_ids[]') {
          return;
        }
        preserveSelection[Number(target.value)] = target.checked;
        updateSummary();
      });
    }

    if (checkAllEl) {
      checkAllEl.addEventListener('change', function () {
        setAllChecked(checkAllEl.checked);
      });
    }

    if (selectAllBtn) {
      selectAllBtn.addEventListener('click', function (event) {
        event.preventDefault();
        setAllChecked(true);
      });
    }

    if (selectNoneBtn) {
      selectNoneBtn.addEventListener('click', function (event) {
        event.preventDefault();
        setAllChecked(false);
      });
    }

    if (applyBtn) {
      applyBtn.addEventListener('click', function (event) {
        event.preventDefault();
        loadRecipients();
      });
    }

    if (resetBtn) {
      resetBtn.addEventListener('click', function (event) {
        event.preventDefault();
        resetFilters();
      });
    }

    filterSelects.forEach(function (selectEl) {
      selectEl.addEventListener('change', function () {
        loadRecipients();
      });
    });

    if (ageWrap) {
      ageWrap.querySelectorAll('input[type="checkbox"][name="age[]"]').forEach(function (input) {
        input.addEventListener('change', function () {
          loadRecipients();
        });
      });
    }

    form.addEventListener('submit', function (event) {
      if (countSelected() === 0) {
        event.preventDefault();
        alert(config.noRecipientsSelected || 'Select at least one member.');
      }
    });

    loadRecipients();
  }

  window.initBroadcastRecipients = initBroadcastRecipients;
})();
