(() => {
    const toggle = document.querySelector('.public-menu-toggle');
    const menu = document.querySelector('#public-mobile-menu');
    const scrim = document.querySelector('.public-menu-scrim');
    if (!toggle || !menu || !scrim) return;
    const setOpen = (open) => {
        document.body.classList.toggle('public-menu-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
        menu.setAttribute('aria-hidden', String(!open));
        scrim.hidden = !open;
        if (open) menu.querySelector('.public-menu-close')?.focus(); else toggle.focus();
    };
    toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
    document.querySelectorAll('[data-public-menu-close]').forEach((element) => element.addEventListener('click', () => setOpen(false)));
    menu.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') setOpen(false); });
    window.addEventListener('resize', () => { if (window.innerWidth > 850 && toggle.getAttribute('aria-expanded') === 'true') setOpen(false); });
})();
