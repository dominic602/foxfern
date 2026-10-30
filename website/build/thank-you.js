(() => {
  const $ = id => document.getElementById(id);

  // ---------- Header: solid on scroll, mobile menu ----------
  const header = $('siteHeader');
  const setSolid = () => header.classList.toggle('solid', scrollY > 24);
  setSolid(); addEventListener('scroll', setSolid, { passive: true });
  const nav = $('nav'), menuBtn = nav.querySelector('.menu-toggle');
  menuBtn.addEventListener('click', () => {
    const open = nav.classList.toggle('open');
    menuBtn.setAttribute('aria-expanded', String(open));
    menuBtn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
  });
  addEventListener('keydown', e => { if (e.key === 'Escape' && nav.classList.contains('open')) { menuBtn.click(); menuBtn.focus(); } });
})();
