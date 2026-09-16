(function () {
  var toggle = document.querySelector('[data-nav-toggle]');
  var nav = document.querySelector('[data-nav]');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      nav.classList.toggle('is-open');
    });
  }

  document.querySelectorAll('[data-reveal]').forEach(function (el, i) {
    el.style.transitionDelay = (i % 6) * 0.05 + 's';
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          e.target.classList.add('is-in');
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.12 });
    io.observe(el);
  });

  document.querySelectorAll('[data-reveal-secret]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = document.getElementById(btn.getAttribute('data-reveal-secret'));
      if (!target) return;
      var hidden = target.getAttribute('data-secret') || '';
      if (target.textContent === '••••••••') {
        target.textContent = hidden;
        btn.textContent = 'Скрыть';
      } else {
        target.textContent = '••••••••';
        btn.textContent = 'Показать';
      }
    });
  });
})();
