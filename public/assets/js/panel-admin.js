(function () {
  var openBtn = document.querySelector('[data-sidebar-open]');
  var closeEls = document.querySelectorAll('[data-sidebar-close]');
  var body = document.body;

  function openSidebar() { body.classList.add('panel-sidebar-open'); }
  function closeSidebar() { body.classList.remove('panel-sidebar-open'); }

  if (openBtn) openBtn.addEventListener('click', openSidebar);
  closeEls.forEach(function (el) { el.addEventListener('click', closeSidebar); });

  var userWrap = document.querySelector('[data-user-menu]');
  var userToggle = document.querySelector('[data-user-toggle]');
  if (userWrap && userToggle) {
    userToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      userWrap.classList.toggle('is-open');
    });
    document.addEventListener('click', function () {
      userWrap.classList.remove('is-open');
    });
  }

  var searchBox = document.querySelector('[data-admin-nav-search]');
  var nav = document.querySelector('[data-admin-nav]');
  var emptyHint = document.querySelector('[data-nav-empty]');
  if (!searchBox || !nav) return;

  var input = searchBox.querySelector('input');
  var clearBtn = searchBox.querySelector('.clear');

  function filter() {
    var q = (input.value || '').trim().toLowerCase();
    searchBox.classList.toggle('is-active', q !== '');
    var visible = 0;

    nav.querySelectorAll('.nav-group').forEach(function (group) {
      var groupLabel = (group.getAttribute('data-nav-group') || '').toLowerCase();
      var groupMatch = q !== '' && groupLabel.indexOf(q) !== -1;
      var groupHas = groupMatch;

      group.querySelectorAll('a[data-nav-label]').forEach(function (a) {
        var label = (a.getAttribute('data-nav-label') || '').toLowerCase();
        var match = q === '' || groupMatch || label.indexOf(q) !== -1;
        a.style.display = match ? '' : 'none';
        if (match) {
          groupHas = true;
          visible += 1;
        }
      });

      group.style.display = (q === '' || groupHas) ? '' : 'none';
    });

    if (emptyHint) {
      emptyHint.hidden = !(q !== '' && visible === 0);
    }
  }

  input.addEventListener('input', filter);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      input.value = '';
      filter();
    }
  });
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      input.value = '';
      input.focus();
      filter();
    });
  }
})();
