/* Lumea Glow: fade-in on scroll, home header on scroll, treatments dropdown, mobile menu. */
(function () {
  var roots = document.querySelectorAll('.lg-root');
  for (var i = 0; i < roots.length; i++) roots[i].classList.add('lg-js');

  var fades = document.querySelectorAll('.lg-root .fade-up');
  var show = function (el) { el.classList.add('in'); };
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { show(e.target); io.unobserve(e.target); } });
    }, { threshold: 0.12 });
    fades.forEach(function (el) { io.observe(el); });
  } else {
    fades.forEach(show);
  }

  var h = document.querySelector('.lg-root header[data-lg-home]');
  if (h) {
    var on = function () {
      var s = window.scrollY > 40;
      h.classList.toggle('bg-transparent', !s);
      ['border-b', 'border-foreground/10', 'bg-ivory'].forEach(function (c) { h.classList.toggle(c, s); });
    };
    on();
    window.addEventListener('scroll', on, { passive: true });
  }

  var m = document.querySelector('.lg-root .lg-mob');
  document.querySelectorAll('.lg-root .lg-open').forEach(function (b) {
    b.addEventListener('click', function () { if (m) { m.classList.add('lg-on'); document.body.style.overflow = 'hidden'; } });
  });
  document.querySelectorAll('.lg-root .lg-close').forEach(function (b) {
    b.addEventListener('click', function () { if (m) { m.classList.remove('lg-on'); document.body.style.overflow = ''; } });
  });

  var dd = document.querySelector('.lg-root .lg-ddwrap');
  if (dd) {
    var bt = dd.querySelector('button');
    if (bt) bt.addEventListener('click', function () {
      var open = dd.classList.toggle('lg-ddon');
      bt.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) { if (!dd.contains(e.target)) dd.classList.remove('lg-ddon'); });
  }
})();
