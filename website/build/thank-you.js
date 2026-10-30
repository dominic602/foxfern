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

  // ---------- Personal touch from the request form ----------
  // The contact form saves the first name, preferred contact method and property type in this tab's
  // sessionStorage just before sending people here. Nothing leaves the browser; without it the page stays generic.
  let req = {};
  try { req = JSON.parse(sessionStorage.getItem('ffRequest') || '{}') || {}; } catch (e) { /* storage blocked */ }
  const first = String(req.name || '').trim().slice(0, 40);
  if (first) $('tyHello').textContent = `Thanks, ${first}!`;
  const how = { Call: 'by phone', Text: 'by text', Email: 'by email' }[req.how];
  if (how) $('tyHow').textContent = ' ' + how;
  if (req.type === 'Commercial') document.documentElement.classList.add('ty-commercial');
})();
