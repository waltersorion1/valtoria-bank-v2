(() => {
  const toggle = document.querySelector('.menu-toggle');
  if (!toggle) return;
  const setOpen = (open) => {
    document.body.classList.toggle('menu-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    if (open) document.querySelector('.app-sidebar a')?.focus();
  };
  toggle.addEventListener('click', () => setOpen(!document.body.classList.contains('menu-open')));
  document.querySelectorAll('[data-menu-close]').forEach((button) => button.addEventListener('click', () => setOpen(false)));
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') setOpen(false); });
  window.matchMedia('(min-width: 901px)').addEventListener('change', (event) => { if (event.matches) setOpen(false); });
})();
