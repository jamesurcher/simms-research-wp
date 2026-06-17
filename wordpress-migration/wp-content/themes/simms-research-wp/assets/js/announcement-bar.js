(() => {
  const bar = document.querySelector('[data-simms-announcement]');

  if (!bar) {
    return;
  }

  const slides = Array.from(bar.querySelectorAll('.announcement-bar__item'));

  if (slides.length < 2) {
    return;
  }

  const interval = Math.max(2000, parseInt(bar.getAttribute('data-interval'), 10) || 5000);
  let current = 0;
  let timer;

  function show(index) {
    slides.forEach((slide, i) => {
      const active = i === index;
      slide.classList.toggle('is-active', active);
      slide.toggleAttribute('aria-hidden', !active);
    });
  }

  function advance() {
    current = (current + 1) % slides.length;
    show(current);
  }

  function start() {
    stop();
    timer = window.setInterval(() => {
      if (document.hidden || bar.matches(':hover')) {
        return;
      }
      advance();
    }, interval);
  }

  function stop() {
    if (timer) {
      window.clearInterval(timer);
      timer = undefined;
    }
  }

  bar.addEventListener('mouseenter', stop);
  bar.addEventListener('mouseleave', start);
  document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));

  start();
})();
