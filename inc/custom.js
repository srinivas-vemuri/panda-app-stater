// inc/custom.js
// Place your custom JS here (optional for now).
// inc/custom.js

(() => {
    const menu = document.getElementById('quickMenu');
    const btn  = document.getElementById('menuToggle');
  
    // Keep menu positioned just below the navbar, regardless of height
    const setNavHeightVar = () => {
      const nav = document.querySelector('nav.navbar');
      const h = nav ? nav.getBoundingClientRect().height : 56;
      document.documentElement.style.setProperty('--sb-nav-height', `${Math.round(h)}px`);
    };
    setNavHeightVar();
    window.addEventListener('resize', setNavHeightVar);
  
    if (!menu || !btn) return;
  
    const open = () => {
      menu.classList.add('show');
      btn.setAttribute('aria-expanded', 'true');
      menu.setAttribute('aria-hidden', 'false');
    };
    const close = () => {
      menu.classList.remove('show');
      btn.setAttribute('aria-expanded', 'false');
      menu.setAttribute('aria-hidden', 'true');
    };
    const toggle = () => (menu.classList.contains('show') ? close() : open());
  
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      toggle();
    });
  
    // Click outside to close
    document.addEventListener('click', (e) => {
      if (!menu.classList.contains('show')) return;
      const withinMenu = menu.contains(e.target);
      const onButton   = btn.contains(e.target);
      if (!withinMenu && !onButton) close();
    });
  
    // ESC to close
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && menu.classList.contains('show')) close();
    });
  
    // Close after clicking an item
    menu.addEventListener('click', (e) => {
      const a = e.target.closest('a');
      if (a) close();
    });
  })();
  