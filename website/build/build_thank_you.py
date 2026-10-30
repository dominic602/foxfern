#!/usr/bin/env python3
"""Builds /thank-you/: the page people land on after sending the estimate request form on /contact/.

It confirms the request went through, explains what happens next, and repeats the reasons to choose Fox & Fern
(daily progress app, warranty, 24-month 0% financing, licensing and insurance). The contact form stores the first
name, preferred contact method and property type in sessionStorage; thank-you.js uses them to personalize the page.
Not for search: noindex, and not in the sitemaps.

Run:  python3 build_thank_you.py
Out:  ../public_html/thank-you/index.html
"""
import html
from pathlib import Path

from site_shell import shell

HERE = Path(__file__).resolve().parent
OUT = HERE.parent / 'public_html' / 'thank-you' / 'index.html'

SITE = 'https://foxandfernservices.com'
URL = SITE + '/thank-you/'
TITLE = 'Request Received: What to Expect | Fox & Fern'
DESC = ('Your free estimate request is in. Here is what happens next, how our daily progress app works, and how '
        'Fox & Fern protects your home and your budget.')
TEL = '+16108903537'
TEL_TXT = '(610)&nbsp;890&#8209;3537'
EMAIL = 'dominic@foxandfernservices.com'

# 0% APR, 24 months: 15000 / 24 = 625.00
EXAMPLE = '†Example: a $15,000 project financed at 0% APR for 24 months is $625.00 per month, $15,000.00 total of payments. Rates and terms shown are examples, not offers.'
FIN_TERMS = ('*All financing is subject to credit approval. Your terms may vary. Payment options through Wisetack are provided by our '
             'lending partners. For example, a $5,500 purchase could cost $253.54 per month for 24 months, based on a 9.9% APR, or '
             '$1,833.33 per month for 3 months, based on a 0% APR. Offers range from 0 to 35.9% APR based on amount requested and '
             'creditworthiness. Not all merchants and lending partners participate in 0% interest programs. Terms range from 3 - 120 '
             'months and may vary based on merchant, lending partner, or transaction size. State interest rate caps may apply. No '
             'prepaid finance charges or participation fees. See additional terms at wisetack.com/faqs.')

# ---------------------------------------------------------------- photos (all real Fox & Fern projects and people)
PHOTOS = {
    'melissa': dict(base='melissa-m-office-manager-fox-fern-4x5', w=[900], size=(720, 900),
                    alt='Melissa M., Office Manager at Fox & Fern Property Services, in a black Fox & Fern quarter-zip'),
    'dominic': dict(base='dominic-l-operations-manager-fox-fern-4x5', w=[900], size=(720, 900),
                    alt='Dominic L., Operations Manager at Fox & Fern Property Services, in a Fox & Fern cap and white polo'),
    'protect': dict(base='kitchen-remodel-dust-protection-4x3', w=[480, 800, 1200], tag='Kitchen remodel with dust protection · Willow Grove, PA',
                    alt='Kitchen remodel in progress with the countertops, double oven and island wrapped in plastic and paper while the backsplash wall is opened up'),
    'drywall': dict(base='drywall-finishing-floor-protection-4x3', w=[480, 800, 1200], tag='Day 6 photo',
                    alt='Joint compound bucket, taping knife and hawk on floor protection during drywall finishing'),
    'bath': dict(base='bathroom-remodel-floating-vanity-trough-sink-4x3', w=[480, 800], tag='Bathroom remodel · King of Prussia, PA',
                 alt='Bathroom after the remodel, with a floating wood vanity, white trough sink, matte black faucet and octagonal black mirror'),
    'deck': dict(base='two-level-composite-deck-white-railings-black-balusters-4x3', w=[480, 800], tag='Two-level composite deck · Pottstown, PA',
                 alt='Two-level gray composite deck with white railings, black balusters and wide steps behind a colonial house'),
    'fireplace': dict(base='shiplap-electric-fireplace-tv-wall-4x3', w=[480, 800], tag='Shiplap fireplace &amp; TV wall · Bryn Mawr, PA',
                      alt='Black shiplap feature wall with a wall-mounted TV, a wood mantel and a built-in linear electric fireplace'),
    'bedroom': dict(base='bedroom-remodel-hardwood-floor-gray-walls-crown-molding-4x3', w=[480, 800], tag='Bedroom remodel · Limerick, PA',
                    alt='Remodeled bedroom with gray walls, white crown molding, a black-framed triple window and light hardwood flooring'),
    'powder': dict(base='powder-room-wallpaper-wainscoting-vanity-4x3', w=[480, 800, 1200], tag='Powder room · Pottstown, PA',
                   alt='Powder room with botanical wallpaper, gray beadboard wainscoting, a new vanity with a brass faucet and a new toilet'),
    'driveway': dict(base='new-concrete-driveway-walkway-curved-borders-4x3', w=[480, 800, 1200], tag='New driveway &amp; walkway',
                     alt='New broom-finished concrete driveway and front walkway with smooth curved borders'),
}


def picture(key, sizes, eager=False):
    m = PHOTOS[key]
    base, ws = '/images/projects/' + m['base'], m['w']
    w, h = m.get('size', (800, 600))
    srcset = lambda ext: ', '.join(f'{base}-{x}.{ext} {x}w' for x in ws)
    load = 'loading="eager"' if eager else 'loading="lazy"'
    return (f'<picture><source type="image/avif" srcset="{srcset("avif")}" sizes="{sizes}">'
            f'<source type="image/webp" srcset="{srcset("webp")}" sizes="{sizes}">'
            f'<img src="{base}-{ws[-1] if len(ws) == 1 else 800}.jpg" srcset="{srcset("jpg")}" sizes="{sizes}" width="{w}" height="{h}" '
            f'alt="{html.escape(m["alt"])}" {load} decoding="async"></picture>')


def figure(key, sizes, cls='ph'):
    return (f'<figure class="{cls}">{picture(key, sizes)}'
            f'<figcaption class="ph-tag"><svg><use href="#i-cam"/></svg>{PHOTOS[key]["tag"]}</figcaption></figure>')


SPRITE_EXTRA = '''
  <symbol id="i-truck" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6h11v10H2zM13 9h4l4 4v3h-8"/><circle cx="6" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/></symbol>
  <symbol id="i-calendar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M8 14h3M8 17h6"/></symbol>
  <symbol id="i-home" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-7 9 7M5 10v10h14V10M10 20v-6h4v6"/></symbol>
  <symbol id="i-mail" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></symbol>'''

TEAM_SIZES = '(max-width: 520px) 44vw, 240px'
WIDE_SIZES = '(max-width: 960px) calc(100vw - 40px), 560px'
TILE_SIZES = '(max-width: 640px) calc(100vw - 40px), (max-width: 1080px) calc(50vw - 30px), 380px'
PHONE_SIZES = '(max-width: 700px) calc(100vw - 100px), 280px'


def hero():
    return f'''
  <!-- ============ HERO ============ -->
  <section class="r-hero ty-hero" aria-labelledby="page-title">
    <div class="inner r-hero-in">
      <div class="r-copy">
        <p class="ok"><svg><use href="#i-check"/></svg>Request received</p>
        <h1 id="page-title">
          <span class="l1" id="tyHello">Thank you!</span>
          <span class="l2">You're All Set</span>
          <span class="l3">here's what happens next</span>
        </h1>
        <p class="r-lede">Your free estimate request is in. Melissa, our office manager, will reach out<span id="tyHow"></span> to pick a time for your on-site walkthrough. Below is everything to expect, from that first call to the first day of work.</p>
        <ul class="ty-ticks">
          <li><svg><use href="#i-check"/></svg>No cost, no obligation</li>
          <li><svg><use href="#i-check"/></svg>Licensed &amp; insured</li>
          <li><svg><use href="#i-check"/></svg>Family-owned in Linfield</li>
        </ul>
        <div class="r-ctas">
          <a class="btn btn-gold" href="tel:{TEL}"><svg><use href="#i-phone"/></svg>Call {TEL_TXT}</a>
          <a class="btn btn-line" href="/projects/">See Recent Projects</a>
        </div>
        <p class="ty-note">Need to change something, or forgot a detail? Just call or reply when we reach out. We'll update your request.</p>
      </div>

      <div class="team" role="group" aria-label="Who you'll hear from">
        <p class="team-k">Who you'll hear from</p>
        <div class="team-cards">
          <figure class="tcard">{picture('melissa', TEAM_SIZES)}<figcaption><b>Melissa M.</b><span>Office Manager</span><small>Your first call. Melissa schedules your walkthrough and guides design, renderings and material samples.</small></figcaption></figure>
          <figure class="tcard">{picture('dominic', TEAM_SIZES)}<figcaption><b>Dominic L.</b><span>Operations Manager</span><small>Runs crews, scheduling and safety on every job. OSHA 30 trained.</small></figcaption></figure>
        </div>
      </div>
    </div>
  </section>'''


def next_steps():
    steps = [
        ('We set a time', 'Melissa reaches out by your preferred method to pick a time that works. If anyone else should weigh in, like a spouse, a property manager or your designer, invite them along.'),
        ('We walk the space with you', 'We measure, take photos and talk through what you want, what needs to stay, any HOA or building rules, and the budget you have in mind. No pressure.'),
        ('You get an itemized estimate', 'Every line item is listed, with clear payment terms, so you can see exactly where the money goes. If you change the scope later, you sign a written, priced change order before that work happens.'),
        ('Design and samples, if you need them', 'For remodels, Melissa works with you on layout, finishes and material samples before anything is ordered. Already working with a designer? Bring them in.'),
        ('Approve and meet your project manager', 'Once you approve, a dedicated project manager schedules the work, handles permits and sends daily progress updates to your phone.'),
    ]
    lis = ''.join(f'<li class="step"><span class="step-n" aria-hidden="true">{i:02d}</span><div><h3>{h}</h3><p>{p}</p></div></li>'
                  for i, (h, p) in enumerate(steps, 1))
    ready = ['Photos or inspiration you like', 'A rough budget range, so we can suggest options that fit', 'HOA, landlord or building rules',
             'When you\'d like the work done', 'Anyone who should weigh in', 'Questions about financing']
    ready_lis = ''.join(f'<li>{x}</li>' for x in ready)
    return f'''
  <!-- ============ WHAT TO EXPECT ============ -->
  <section class="sec sec-alt" id="next" aria-labelledby="next-title">
    <div class="inner">
      <div class="sec-head">
        <p class="eyebrow">What to expect</p>
        <h2 id="next-title">Your Free Estimate, Step by Step</h2>
        <p class="lede">No pressure and no guesswork. Here's exactly what happens after you send your request.</p>
      </div>
      <div class="ty-next">
        <ol class="steps">{lis}</ol>
        <aside class="ty-side">
          <div class="ready">
            <p class="ready-k"><svg><use href="#i-calendar"/></svg>Helpful to have ready</p>
            <ul>{ready_lis}</ul>
            <p class="ready-f">Nothing here is required. Bring what you have and we'll take it from there.</p>
          </div>
          {figure('protect', WIDE_SIZES)}
          <p class="ty-cap">Your home stays protected: dust barriers, floor protection and covered fixtures go up first.</p>
        </aside>
      </div>
    </div>
  </section>'''


def values():
    items = [
        ('i-receipt', 'Itemized estimates', 'Every line item is listed, with clear payment terms, so you can see exactly where the money goes.'),
        ('i-user', 'One project manager', 'One point of contact from start to finish, with daily progress updates and photos you can follow from your phone.'),
        ('i-pencil', 'Written change orders', 'Want to change the scope mid-project? You sign a written, priced change order before that work happens.'),
        ('i-home', 'Protected spaces', 'Dust barriers, floor protection and covered fixtures go up first, before any work starts.'),
        ('i-stamp', 'Permits &amp; approvals handled', 'We pull permits and prepare the drawings, specs and samples your HOA, landlord or building asks for.'),
        ('i-hardhat', 'Safety-trained leadership', 'Our operations manager is OSHA 30 trained and oversees safety on every job.'),
    ]
    cards = ''.join(f'<div class="val"><svg><use href="#{i}"/></svg><h3>{h}</h3><p>{p}</p></div>' for i, h, p in items)
    return f'''
  <!-- ============ VALUES ============ -->
  <section class="sec" id="why" aria-labelledby="why-title">
    <div class="inner">
      <div class="sec-head">
        <p class="eyebrow">Why homeowners choose us</p>
        <h2 id="why-title">What You Can Count On</h2>
        <p class="lede">The same standards on every job, big or small.</p>
      </div>
      <div class="vals-grid">{cards}</div>
    </div>
  </section>'''


def app():
    return f'''
  <!-- ============ PROGRESS APP ============ -->
  <section class="sec sec-alt" id="app" aria-labelledby="app-title">
    <div class="inner ty-app">
      <div class="phone" role="group" aria-label="Sample day in the Fox &amp; Fern project app">
        <div class="phone-top"><i></i></div>
        <div class="app-h">
          <p>Your project app · Sample</p>
          <p class="app-name">Hall Bath Remodel</p>
          <div class="app-bar"><span></span></div>
          <div class="app-meta"><span><b>Day 6</b> of 15</span><span>On schedule</span></div>
        </div>
        <div class="app-card">
          <div class="when"><span>Mon, Mar 8</span><span>Updated</span></div>
          <p class="app-t">Drywall &amp; prime</p>
          <p>Seams taped and finished with the floors still covered. Tomorrow: sand, prime and get ready for tile.</p>
          <figure class="ph">{picture('drywall', PHONE_SIZES)}<figcaption class="ph-tag">{PHOTOS['drywall']['tag']}</figcaption></figure>
        </div>
        <div class="app-foot" aria-hidden="true"><span>Updates</span><span>Photos</span><span>Products</span><span>Warranty</span></div>
      </div>
      <div class="app-copy">
        <p class="eyebrow">Daily progress app</p>
        <h2 id="app-title">Watch Your Project Happen, Day by Day</h2>
        <p class="lede">You won't have to wonder what happened today or chase anyone for an update. Every working day, your project app shows what got done, dated photos and what's next.</p>
        <ul class="build-notes">
          <li><svg><use href="#i-app"/></svg><span><b>Updated every working day</b>What was done, dated photos and what's next, on your phone.</span></li>
          <li><svg><use href="#i-cam"/></svg><span><b>Inside-the-wall photos</b>We photograph wiring and plumbing before walls are closed, for your records.</span></li>
          <li><svg><use href="#i-receipt"/></svg><span><b>Product sheets &amp; warranty</b>The exact products used and your warranty certificate, in the same app.</span></li>
        </ul>
      </div>
    </div>
  </section>'''


def warranty():
    rows = [('Tile', 'Manufacturer · collection · color'), ('Grout', 'Brand · color · sealer'), ('Paint', 'Brand · color · sheen'),
            ('Fixtures', 'Brand · model · finish'), ('Vanity &amp; top', 'Maker · model · material')]
    trs = ''.join(f'<li><b>{a}</b><span>{b}</span></li>' for a, b in rows)
    return f'''
  <!-- ============ WARRANTY ============ -->
  <section class="sec" id="warranty" aria-labelledby="warranty-title">
    <div class="inner ty-warranty">
      <div class="w-copy">
        <p class="eyebrow">When we're done</p>
        <h2 id="warranty-title">Backed in Writing</h2>
        <p class="lede">At your final walkthrough we finish every touch-up together. Then you get two documents that make life easier for years.</p>
        <ul class="w-list">
          <li><svg><use href="#i-shield"/></svg><span><b>A workmanship warranty certificate</b>Our warranty on the work we did, issued at closeout. The term is set in your contract.</span></li>
          <li><svg><use href="#i-receipt"/></svg><span><b>A product sheet</b>The exact manufacturer, model number, color and spec sheet for every product we install, so manufacturer warranties and future touch-ups are easy.</span></li>
        </ul>
      </div>
      <div class="w-docs" aria-hidden="true">
        <div class="cert">
          <p class="cert-k">Fox &amp; Fern Property Services</p>
          <p class="cert-t">Certificate of Workmanship Warranty</p>
          <p class="cert-l">Issued to <i></i></p>
          <p class="cert-l">Project <i></i></p>
          <p class="cert-l">Term <span>set in your contract</span></p>
          <span class="cert-seal">F&amp;F</span>
        </div>
        <div class="psheet">
          <p class="ps-k">Product sheet · Sample</p>
          <ul>{trs}</ul>
        </div>
      </div>
    </div>
  </section>'''


def financing():
    return f'''
  <!-- ============ FINANCING ============ -->
  <section class="sec sec-alt" id="financing" aria-labelledby="fin-title">
    <div class="inner">
      <div class="ty-fin">
        <div class="fin-copy">
          <p class="eyebrow">Financing through Wisetack</p>
          <h2 id="fin-title">0% APR for Up to 24 Months</h2>
          <p class="lede">We've upgraded our financing: eligible homeowners can now spread the cost over up to 24 months at 0% APR.* Ask about it at your walkthrough, or check your options from your phone.</p>
          <ol class="fin-steps">
            <li><span class="v-n">1</span><div><h3>Check your options</h3><p>Apply from your phone in a few minutes. Prequalifying is a soft credit check, so it doesn't affect your credit score.</p></div></li>
            <li><span class="v-n">2</span><div><h3>Choose a plan</h3><p>Pick the term and monthly payment that fit your budget. If you move forward with a plan, the lender may run a full credit check.</p></div></li>
            <li><span class="v-n">3</span><div><h3>Start your project</h3><p>We schedule the work and keep you updated every day. You make your monthly payments through Wisetack.</p></div></li>
          </ol>
        </div>
        <div class="fin-card">
          <p class="fc-k">Example</p>
          <p class="fc-n">$625<small>/mo</small></p>
          <p class="fc-s">for 24 months at 0% APR on a $15,000 project<sup>†</sup></p>
          <div class="fc-bar" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
          <p class="fc-f">24 equal payments, $15,000.00 total. No interest for eligible customers.*</p>
          <p class="fc-hero"><b>Hometown Heroes:</b> veterans, first responders, teachers and nurses get 15% off with valid ID or proof of service. Promotions can't be combined.</p>
        </div>
      </div>
      <p class="promo-legal">{EXAMPLE}</p>
      <p class="promo-legal">{FIN_TERMS}</p>
      <p class="promo-legal">Financing is for homeowners with residential projects. Commercial projects are billed on the payment terms in your bid. Promotions can't be combined and are subject to change.</p>
    </div>
  </section>'''


def credentials():
    items = [
        ('i-stamp', 'Licensed in Pennsylvania', 'Home Improvement Contractor Registration No. PA217947.'),
        ('i-shield', 'Insured', 'Licensed and insured, with certificates of insurance (including additional insureds) for commercial clients.'),
        ('i-hardhat', "Workers' compensation", "Our crew is covered by workers' compensation insurance while they work at your property."),
        ('i-truck', 'Commercial auto', 'Our work vehicles carry commercial auto insurance.'),
        ('i-user', 'OSHA 30 safety', 'Our operations manager has completed OSHA 30 training and oversees safety on every job.'),
        ('i-home', 'Family-owned &amp; local', 'A husband-and-wife team based at 1153 Main St, Ste 201, Linfield, PA 19468.'),
    ]
    tiles = ''.join(f'<li><svg><use href="#{i}"/></svg><div><b>{h}</b><span>{p}</span></div></li>' for i, h, p in items)
    return f'''
  <!-- ============ CREDENTIALS ============ -->
  <section class="sec" id="credentials" aria-labelledby="cred-title">
    <div class="inner">
      <div class="sec-head">
        <p class="eyebrow">Peace of mind</p>
        <h2 id="cred-title">Licensed, Insured and Accountable</h2>
        <p class="lede">You're letting us into your home. Here's what stands behind every person and truck we send.</p>
      </div>
      <ul class="cred-grid">{tiles}</ul>
      <p class="sec-links"><a class="tlink" href="https://share.google/WN2Vt5ldGNum1rxIm">Read our Google reviews</a><a class="tlink" href="https://www.yelp.com/biz/fox-and-fern-property-services-linfield">Read our Yelp reviews</a><a class="tlink" href="/about/">Meet the team</a></p>
    </div>
  </section>'''


def gallery():
    tiles = ''.join(figure(k, TILE_SIZES) for k in ['bath', 'deck', 'fireplace', 'bedroom', 'powder', 'driveway'])
    return f'''
  <!-- ============ RECENT WORK ============ -->
  <section class="sec sec-alt" id="work" aria-labelledby="work-title">
    <div class="inner">
      <div class="sec-head">
        <p class="eyebrow">Recent work</p>
        <h2 id="work-title">A Few Projects We're Proud Of</h2>
        <p class="lede">Real Fox &amp; Fern jobs from around the area. Bring photos of your own space to the walkthrough and we'll talk through what's possible.</p>
      </div>
      <div class="gallery">{tiles}</div>
      <p class="sec-links"><a class="btn btn-line" href="/projects/">See All Projects <svg class="arr"><use href="#i-arrow"/></svg></a></p>
    </div>
  </section>'''


def cta():
    return f'''
  <!-- ============ CTA ============ -->
  <section class="cta-band" aria-labelledby="cta-title">
    <div class="inner cta-in">
      <div>
        <h2 id="cta-title">Questions before your visit?</h2>
        <p>Call during office hours, or email us and we'll get right back to you.</p>
      </div>
      <div class="cta-actions">
        <a class="btn btn-dark" href="tel:{TEL}"><svg><use href="#i-phone"/></svg>Call {TEL_TXT}</a>
        <a class="cta-tel" href="mailto:{EMAIL}">{EMAIL}</a>
      </div>
    </div>
  </section>'''


def main():
    head_assets, sprite, header, footer = shell(SPRITE_EXTRA)
    css = (HERE / 'thank-you.css').read_text(encoding='utf-8')
    js = (HERE / 'thank-you.js').read_text(encoding='utf-8')
    esc = lambda s: html.escape(s, quote=True)
    head = f'''<!doctype html>
<html lang="en-US">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{esc(TITLE)}</title>
<meta name="description" content="{esc(DESC)}">
<link rel="canonical" href="{URL}">
<meta name="robots" content="noindex, follow">
<link rel="icon" type="image/png" href="/favicon-48x48.png" sizes="48x48">
<link rel="icon" type="image/png" href="/icon-192.png" sizes="192x192">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
{head_assets}<style>
{css}</style>
</head>'''
    main_html = ''.join([hero(), next_steps(), values(), app(), warranty(), financing(), credentials(), gallery(), cta()])
    page = f'''{head}
<body>
{sprite}


{header}

<main id="main">{main_html}
</main>

{footer}

<script>
{js}</script>
</body>
</html>
'''
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(page, encoding='utf-8')
    print(f'wrote {OUT} ({len(page):,} bytes)')


if __name__ == '__main__':
    main()
