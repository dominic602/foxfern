(() => {
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
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

  // ---------- Date-limited promos hide outside their window ----------
  document.querySelectorAll('[data-end]').forEach(el => {
    const now = new Date(), start = new Date(el.dataset.start + 'T00:00:00-04:00'), end = new Date(el.dataset.end + 'T23:59:59-05:00');
    if (now < start || now > end) el.hidden = true;
  });

  // ---------- Before / after slider ----------
  const cmp = $('cmp'), cmpRange = $('cmpRange');
  cmpRange.addEventListener('input', () => {
    const v = +cmpRange.value;
    cmp.style.setProperty('--pos', v + '%');
    cmpRange.setAttribute('aria-valuetext', `${100 - v}% after photo showing`);
  });

  // ---------- Bathroom build scrubber + phone app ----------
  const DAYS = [
    ['Protection & demolition prep', 'Floors and the hallway are covered, a dust wall goes up and the water is shut off before anything comes out.'],
    ['Demolition', 'Old tub, surround, vanity and toilet removed and hauled away. Walls opened down to the studs.'],
    ['Framing & blocking', 'Shower niche framed, blocking added for the vanity and future grab bars, and the floor checked for level.'],
    ['Plumbing rough-in', 'New shower valve and drain set, vanity and toilet lines run. Old supply lines replaced while the walls are open.'],
    ['Electrical & ventilation', 'Vanity light box, GFCI outlets and a new exhaust fan ducted to the outside.'],
    ['Rough-in inspection', 'Rough-in inspection passed. Photos of every pipe and wire saved before the walls close.'],
    ['Shower pan & cement board', 'Shower pan set and sloped to the drain; cement board hung on the shower walls.'],
    ['Waterproofing', 'Waterproof membrane rolled over the shower walls, niche and pan, then flood-tested.'],
    ['Drywall & prime', 'Moisture-resistant drywall finished outside the shower and primed.'],
    ['Wall tile', 'Shower wall tile set to your layout, with the niche trimmed out.'],
    ['Floor tile', 'Floor tile set and leveled.'],
    ['Grout & seal', 'Tile grouted in your chosen color and sealed.'],
    ['Paint & vanity', 'Walls painted; vanity, top and sink installed.'],
    ['Fixtures, glass & lighting', 'Faucet, shower trim, toilet, mirror, light and glass door installed and tested.'],
    ['Complete', 'Final walkthrough and touch-ups done. Warranty certificate and product sheet delivered.'],
  ];
  const N = DAYS.length;
  // working-day dates starting Mon, Mar 1, 2027
  const dates = []; { const d = new Date(2027, 2, 1); while (dates.length < N) { const w = d.getDay(); if (w && w < 6) dates.push(d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })); d.setDate(d.getDate() + 1); } }
  const range = $('dayRange'), out = $('dayOut'), stages = document.querySelectorAll('#bath-build .stg');
  function setDay(n) {
    stages.forEach(g => g.classList.toggle('on', n >= +g.dataset.from && n <= +g.dataset.to));
    const [title, text] = DAYS[n - 1];
    out.textContent = 'Day ' + n;
    range.value = n;
    range.style.setProperty('--p', ((n - 1) / (N - 1) * 100) + '%');
    range.setAttribute('aria-valuetext', `Day ${n}: ${title}`);
    $('appDay').textContent = 'Day ' + n;
    $('appDate').textContent = dates[n - 1];
    $('appTitle').textContent = title;
    $('appText').textContent = text;
    $('appPhoto').textContent = 'Day ' + n + ' photo';
    $('appBar').style.setProperty('--w', (n / N * 100) + '%');
  }
  let timer = null;
  const playBtn = $('playBtn');
  const icon = name => `<svg><use href="#i-${name}"/></svg>`;
  function stop() { clearInterval(timer); timer = null; playBtn.innerHTML = icon('play'); playBtn.setAttribute('aria-label', 'Play the build'); }
  function play() {
    if (+range.value >= N) setDay(1);
    playBtn.innerHTML = icon('pause'); playBtn.setAttribute('aria-label', 'Pause the build');
    timer = setInterval(() => { const n = +range.value + 1; if (n > N) return stop(); setDay(n); }, 900);
  }
  playBtn.addEventListener('click', () => timer ? stop() : play());
  range.addEventListener('input', () => { stop(); setDay(+range.value); });
  setDay(1);
  // play once the first time the build scrolls into view (skipped for reduced motion)
  if (!reduce && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver(es => { if (es[0].isIntersecting) { io.disconnect(); if (+range.value === 1) play(); } }, { threshold: .55 });
    io.observe($('bath-build'));
  }

  // ---------- Pricing principles highlight estimate rows ----------
  const principles = document.querySelectorAll('.principle');
  function showType(t) {
    principles.forEach(p => p.setAttribute('aria-pressed', String(p.dataset.type === t)));
    document.querySelectorAll('.est tbody tr').forEach(r => r.classList.toggle('hl', r.dataset.type === t));
  }
  principles.forEach(p => p.addEventListener('click', () => showType(p.dataset.type)));
  showType('fixed');

  // ---------- Payment calculator (illustration only; terms in the disclosures) ----------
  const PLANS = [
    { apr: 0, months: 12, label: 'for 12 months at 0% APR*' },
    { apr: 12.9, months: 42, label: 'for 42 months at 12.9% APR†' },
  ];
  const budget = $('budget');
  const money = n => '$' + n.toLocaleString('en-US');
  function calc() {
    const P = +budget.value, plan = PLANS[+document.querySelector('[name="plan"]:checked').value];
    const r = plan.apr / 1200, m = r ? P * r / (1 - Math.pow(1 + r, -plan.months)) : P / plan.months;
    $('budgetOut').textContent = money(P);
    $('payN').textContent = money(Math.ceil(m));
    $('payS').textContent = plan.label;
    budget.style.setProperty('--p', ((P - +budget.min) / (+budget.max - +budget.min) * 100) + '%');
    budget.setAttribute('aria-valuetext', money(P));
  }
  budget.addEventListener('input', calc);
  document.querySelectorAll('[name="plan"]').forEach(r => r.addEventListener('change', calc));
  calc();

  // ---------- Phone sticky bar: appears once the hero buttons scroll away ----------
  const mbar = $('mbar'), heroCtas = document.querySelector('.r-ctas');
  if (mbar && heroCtas && 'IntersectionObserver' in window) {
    const cta = document.querySelector('.cta-band');
    let pastHero = false, atCta = false;
    const sync = () => mbar.classList.toggle('show', pastHero && !atCta);
    new IntersectionObserver(es => { pastHero = !es[0].isIntersecting && es[0].boundingClientRect.top < 0; sync(); }).observe(heroCtas);
    if (cta) new IntersectionObserver(es => { atCta = es[0].isIntersecting; sync(); }).observe(cta);
  }
})();
