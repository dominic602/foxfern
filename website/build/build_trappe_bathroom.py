#!/usr/bin/env python3
"""Builds /bathroom-remodeling-trappe-pa/index.html.

The page reuses the live site's head assets, shared CSS, header and footer from
template-residential-remodeling.html (a copy of https://foxandfernservices.com/residential-remodeling/),
so it looks and behaves like every other page. Page-specific content, structured data, CSS
(town-page.css) and script (town-page.js) are added here.

Run:  python3 build_trappe_bathroom.py
Out:  ../public_html/bathroom-remodeling-trappe-pa/index.html
"""
import html
import json
import re
from pathlib import Path

HERE = Path(__file__).resolve().parent
OUT = HERE.parent / 'public_html' / 'bathroom-remodeling-trappe-pa' / 'index.html'

SITE = 'https://foxandfernservices.com'
SLUG = '/bathroom-remodeling-trappe-pa/'
URL = SITE + SLUG
PUBLISHED = '2026-10-27'
TITLE = 'Bathroom Remodeling in Trappe, PA | Fox & Fern'
DESC = ('Bathroom remodeling in Trappe, PA: tile showers, walk-in showers and vanities. Your designer or one of our partners. '
        '0% APR financing. Free estimate.')
OG_TITLE = 'Bathroom Remodeling in Trappe, PA | Fox & Fern'
OG_IMAGE = SITE + '/images/projects/bathroom-before-after-king-of-prussia-og.jpg'
OG_ALT = 'Before and after: a dated oak vanity bathroom remodeled with a floating wood vanity, trough sink and matte black fixtures'
ESTIMATE = '/contact/?service=residential&amp;project=bathroom&amp;town=trappe'
TEL = '+16108903537'
TEL_TXT = '(610)&nbsp;890&#8209;3537'

# ---------------------------------------------------------------- images (all real Fox & Fern projects)
IMG = {
    'before': dict(base='bathroom-before-remodel-oak-vanity-4x3', w=[480, 800, 1125], town='King of Prussia',
                   caption='Before: dated bathroom vanity',
                   alt='Dated bathroom before the remodel, with an oak vanity, a tan drop-in sink, a laminate counter and a wall-width mirror'),
    'after': dict(base='bathroom-remodel-floating-vanity-trough-sink-4x3', w=[480, 800, 1125], town='King of Prussia',
                  caption='Floating vanity with a trough sink',
                  alt='Bathroom after the remodel, with a floating wood vanity, white trough sink, matte black faucet, octagonal black mirror and dark marble-look floor tile'),
    'shower': dict(base='bathroom-remodel-shower-base-cement-board-4x3', w=[480, 800, 1200, 1512], town='Lansdale',
                   caption='Bathroom remodel in progress',
                   alt='Bathroom remodel in progress with a new shower base, cement board on the shower walls and plumbing roughed in for the vanity'),
    'shower-wide': dict(base='bathroom-remodel-shower-base-cement-board-16x10', w=[480, 800, 1200, 1512], town='Lansdale',
                        caption='Bathroom remodel in progress',
                        alt='Bathroom remodel in progress with a new shower base and cement board on the shower walls', ratio=(800, 500)),
    'tub': dict(base='tub-surround-vertical-white-tile-black-grout-4x3', w=[480, 800, 1125], town='Philadelphia',
                caption='Tub surround: stacked white tile, black grout',
                alt='Bathtub surround in vertically stacked white tile with black grout and matte black shower fixtures'),
    'powder': dict(base='powder-room-wallpaper-wainscoting-vanity-4x3', w=[480, 800, 1200, 1600], town='Pottstown',
                   caption='Powder room: wallpaper and wainscoting',
                   alt='Powder room remodel with botanical monkey-print wallpaper, gray beadboard wainscoting, a new vanity with a brass faucet and a new toilet'),
    'vanity': dict(base='custom-oak-vanity-rope-columns-4x3', w=[480, 800, 1125], town='Philadelphia',
                   caption='Custom oak vanity with rope columns',
                   alt='Custom oak vanity cabinet with rope-twist columns and carved feet'),
}
SVC_SIZES = '(max-width: 760px) calc(100vw - 40px), (max-width: 1080px) calc(50vw - 29px), 586px'
HERO_SIZES = '(max-width: 960px) calc(100vw - 40px), 640px'
PHONE_SIZES = '(max-width: 700px) calc(50vw - 26px), 280px'


def jpg800(key):
    return f"{SITE}/images/projects/{IMG[key]['base']}-800.jpg"


def picture(key, sizes, eager=False):
    m = IMG[key]
    base, ws = '/images/projects/' + m['base'], m['w']
    w, h = m.get('ratio', (800, 600))
    srcset = lambda ext: ', '.join(f'{base}-{x}.{ext} {x}w' for x in ws)
    load = 'loading="eager"' if eager else 'loading="lazy"'
    return (f'<picture><source type="image/avif" srcset="{srcset("avif")}" sizes="{sizes}">'
            f'<source type="image/webp" srcset="{srcset("webp")}" sizes="{sizes}">'
            f'<img src="{base}-800.jpg" srcset="{srcset("jpg")}" sizes="{sizes}" width="{w}" height="{h}" '
            f'alt="{html.escape(m["alt"])}" {load} decoding="async"></picture>')


def figure(key, tag, sizes=SVC_SIZES, cls='ph'):
    return (f'<figure class="{cls}">{picture(key, sizes)}'
            f'<figcaption class="ph-tag"><svg><use href="#i-cam"/></svg>{tag}</figcaption></figure>')


# ---------------------------------------------------------------- FAQ (visible text and FAQPage markup share this)
FAQ = [
    ('How much does a bathroom remodel cost in Trappe, PA?',
     'Every bathroom is priced on its own. Your price depends on the size of the room, whether plumbing moves, and the tile, '
     'vanity and fixtures you choose. After a free on-site walkthrough you get a fixed, itemized estimate, so you can see '
     'exactly where the money goes and adjust before anything starts.'),
    ('Can I use my own designer?',
     'Yes. If you already work with a designer, we build from their layout and selections and work with them when questions '
     'come up on site. If you don\'t have one, we can connect you with one of our partner designers to plan the layout and '
     'choose tile, the vanity, fixtures and finishes. Already have plans? Bring them to your free walkthrough and we\'ll price '
     'your estimate from them.'),
    ('Do I need a permit to remodel a bathroom in Trappe?',
     'It depends on the work. Trappe Borough requires a plumbing permit when you move or add a toilet, tub, shower, sink or '
     'drain, or extend pipes for a new bathroom. Swapping existing fixtures in the same spot generally doesn\'t need one. '
     'New electrical circuits can need a permit too. We confirm the requirements with the borough for your project, file the '
     'applications and schedule the inspections.'),
    ('Can you convert my tub to a walk-in shower?',
     'Yes. Tub-to-shower conversions are one of our most common bathroom projects. We remove the tub, set a new shower pan, '
     'waterproof the walls and finish with tile, a niche and a glass door. Moving the drain usually means a plumbing permit, '
     'which we handle.'),
    ('How long will my bathroom be out of commission?',
     'Your estimate includes the schedule for your specific project. A refresh that keeps the plumbing where it is goes faster '
     'than a full remodel with layout changes and tile showers. Once work starts, your project app shows what was done each '
     'day and what\'s next.'),
    ('Do you offer financing for bathroom remodels?',
     'Yes, through Wisetack. Eligible homeowners can get 0% APR for up to 12 months, or a longer term with a lower monthly '
     'payment. Checking your options is a soft credit check and won\'t affect your credit score. All financing is subject to '
     'credit approval; see the terms on this page.'),
    ('Do you work on older homes near Trappe\'s Main Street?',
     'Yes. Older homes often hide uneven floors, plaster walls and aging supply lines. We check the floor framing before tile '
     'goes down, replace old supply lines while the walls are open, and keep details like trim profiles and wainscoting that '
     'suit the house.'),
    ('Will the price change once you start?',
     'Not without your signature. The price in your estimate only changes if you approve a written, priced change order.'),
    ('What warranty do I get?',
     'At closeout you receive a warranty certificate for our workmanship, with the term set in your contract, plus a product '
     'sheet listing the manufacturer, model and finish of everything we installed, so manufacturer warranties are easy to use.'),
    ('Do you remodel bathrooms near Trappe too?',
     'Yes. We\'re based in Linfield and remodel bathrooms across western Montgomery County, including Collegeville, Limerick, '
     'Royersford, Schwenksville, Skippack and Pottstown, plus King of Prussia and the rest of the county.'),
]

# ---------------------------------------------------------------- page sections
SPRITE_EXTRA = '''
  <symbol id="i-drop" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c3.6 4.6 6 8 6 11a6 6 0 0 1-12 0c0-3 2.4-6.4 6-11z"/><path d="M9 15a3 3 0 0 0 3 3"/></symbol>
  <symbol id="i-wind" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9h11a3 3 0 1 0-3-3M3 15h14a3 3 0 1 1-3 3M3 12h7"/></symbol>
  <symbol id="i-pin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.4-7-11a7 7 0 0 1 14 0c0 4.6-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></symbol>
  <symbol id="i-home" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-7 9 7M5 10v10h14V10M10 20v-6h4v6"/></symbol>
  <symbol id="i-card" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h4"/></symbol>
  <symbol id="i-swatch" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="18" rx="1.5"/><path d="M10 7.5l4.6-3.2a1.5 1.5 0 0 1 2.1.4l3.4 5a1.5 1.5 0 0 1-.4 2.1L10 18"/><path d="M10 16.5l8.7-.1a1.5 1.5 0 0 1 1.5 1.5v1.6a1.5 1.5 0 0 1-1.5 1.5H6.5"/><circle cx="6.5" cy="17" r="1"/></symbol>
  <symbol id="i-drag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7l-5 5 5 5M16 7l5 5-5 5M3 12h18"/></symbol>'''


def hero():
    return f'''
  <!-- ============ HERO ============ -->
  <section class="r-hero t-hero" aria-labelledby="page-title">
    <div class="inner r-hero-in">
      <div class="r-copy">
        <nav class="crumbs" aria-label="Breadcrumb">
          <ol>
            <li><a href="/">Home</a></li>
            <li><a href="/residential-remodeling/">Residential Remodeling</a></li>
            <li><span aria-current="page">Bathrooms in Trappe</span></li>
          </ol>
        </nav>
        <h1 id="page-title">
          <span class="l1">Bathroom</span>
          <span class="l2">Remodeling</span>
          <span class="l3">in Trappe, PA</span>
        </h1>
        <p class="r-lede">Tile showers, tub-to-shower conversions, new vanities and powder rooms for Trappe homes, from a family-owned, licensed contractor just up Route 422 in Linfield. One project manager, one fixed, itemized price and photo updates every working day.</p>
        <ul class="vals">
          <li><b>Design</b><span>Your designer or one of our partners</span></li>
          <li><b>0% APR</b><span>Financing up to 12 months*</span></li>
          <li><b>Daily</b><span>Photo updates in your app</span></li>
        </ul>
        <div class="r-ctas">
          <a class="btn btn-gold" href="{ESTIMATE}">Get a Free Estimate <svg class="arr"><use href="#i-arrow"/></svg></a>
          <a class="btn btn-line" href="#financing">See Monthly Payments</a>
        </div>
        <p class="creds">Licensed &amp; insured <i></i> PA HIC #PA217947</p>
      </div>

      <div class="hero-ba">
        <div class="cmp" id="cmp">
          <figure class="ph cmp-before">{picture('before', HERO_SIZES, eager=True)}<figcaption class="ph-tag"><svg><use href="#i-cam"/></svg>Before</figcaption></figure>
          <figure class="ph cmp-after">{picture('after', HERO_SIZES, eager=True)}<figcaption class="ph-tag"><svg><use href="#i-cam"/></svg>After</figcaption></figure>
          <div class="cmp-line" aria-hidden="true"></div>
          <div class="cmp-knob" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l-6 6 6 6M15 6l6 6-6 6"/></svg></div>
          <input type="range" id="cmpRange" min="0" max="100" value="50" aria-label="Compare before and after photos of a remodeled bathroom" aria-valuetext="Showing half before, half after">
        </div>
        <p class="ba-note"><svg><use href="#i-drag"/></svg><span><b>Drag to compare.</b> A real Fox &amp; Fern bathroom in King of Prussia: a dated oak vanity and drop-in sink replaced with a floating wood vanity, a trough sink, matte black fixtures and marble-look tile.</span></p>
      </div>
    </div>
  </section>'''


def why():
    return '''
  <!-- ============ WHY ============ -->
  <section class="sec sec-alt" aria-labelledby="why-title">
    <div class="inner">
      <div class="sec-head">
        <p class="eyebrow">Why Trappe homeowners call us</p>
        <h2 id="why-title">A Better Bathroom, Without the Guesswork</h2>
      </div>
      <div class="pillars">
        <div class="pillar"><svg><use href="#i-receipt"/></svg><h3>Fixed, itemized price</h3><p>Every line priced in writing. The total only changes if you sign a change order.</p></div>
        <div class="pillar"><svg><use href="#i-user"/></svg><h3>One project manager</h3><p>One person runs your bathroom from approval to the final walkthrough and checks the work every day.</p></div>
        <div class="pillar"><svg><use href="#i-app"/></svg><h3>Daily photo updates</h3><p>What got done, dated photos and what's next, in your project app every working day.</p></div>
        <div class="pillar"><svg><use href="#i-drop"/></svg><h3>Waterproofed right</h3><p>A waterproof membrane behind every tiled shower and an exhaust fan vented outdoors.</p></div>
        <div class="pillar"><svg><use href="#i-stamp"/></svg><h3>Permits handled</h3><p>We file with Trappe Borough when plumbing moves and schedule every inspection.</p></div>
      </div>
      <p class="not-jack">Design it with <b>your own designer</b> or <b>one of our partner designers</b>, and spread the cost with <b>0% APR financing for up to 12 months</b>.*</p>
    </div>
  </section>'''


def svc_row(id_, img, tag, kicker, h3, p, ticks, guide_title, guide):
    ticks_html = ''.join(f'<li>{t}</li>' for t in ticks)
    guide_html = ''.join(f'<li><b>{b}</b> {t}</li>' for b, t in guide)
    return f'''
        <article class="svc-row" id="{id_}">
          {figure(img, tag)}
          <div class="svc-text">
            <p class="kicker">{kicker}</p>
            <h3>{h3}</h3>
            <p>{p}</p>
            <ul class="ticks">{ticks_html}</ul>
            <details class="guide" name="bath-guide">
              <summary><span><svg><use href="#i-book"/></svg>{guide_title}</span></summary>
              <ul>{guide_html}</ul>
            </details>
          </div>
        </article>'''


def services():
    rows = ''.join([
        svc_row('full-remodel', 'after', 'Floating vanity with a trough sink · King of Prussia, PA',
                'Primary &amp; hall baths', 'Full Bathroom Remodels',
                'Down to the studs or a smart refresh: new layout, tile, vanity, lighting and ventilation for primary and hall baths in Trappe.',
                ['Layout changes &amp; plumbing moves', 'Tile floors, walls &amp; showers', 'Vanities, mirrors &amp; lighting', 'Exhaust fans vented outdoors'],
                'Planning guide: full remodels',
                [('Settle the layout first.', 'Moving a toilet, shower or sink changes plumbing and permit needs more than any finish choice.'),
                 ('Choose the shower valve before tile.', 'The valve goes in the wall at rough-in, so trim style and finish have to be decided early.'),
                 ('Plan your other bathroom.', 'We map out which bathroom you\'ll use while yours is torn out, and keep the path to it protected.'),
                 ('Order special items early.', 'Custom vanities, glass doors and some tile take weeks, so we order as soon as you approve.')]),
        svc_row('tub-to-shower', 'shower', 'Shower base &amp; cement board going in · Lansdale, PA',
                'Showers', 'Tub-to-Shower Conversions &amp; Walk-In Showers',
                'Trade a tub you never use for a walk-in shower with a low threshold, a built-in niche, blocking for grab bars and a glass door.',
                ['Tub removal &amp; new shower pan', 'Cement board &amp; waterproof membrane', 'Niches, benches &amp; grab-bar blocking', 'Glass doors with chrome, black or brass trim'],
                'Planning guide: tub-to-shower',
                [('Keep one tub in the house.', 'If you have young kids or plan to sell, a tub in the hall bath keeps the house appealing to more buyers.'),
                 ('Low threshold or curbless?', 'A curbless shower needs the floor recessed, which is easier during a full remodel.'),
                 ('Add blocking now.', 'Wood blocking behind the tile lets you add grab bars later without hunting for studs.'),
                 ('Moving the drain?', 'In Trappe that usually means a plumbing permit, which we file for you.')]),
        svc_row('tile-showers', 'tub', 'Stacked white tile, black grout · Philadelphia, PA',
                'Tile', 'Tile Showers &amp; Tub Surrounds',
                'Stacked, subway, large-format or mosaic tile, set over a waterproofed wall so it looks sharp and stays dry for years.',
                ['Wall &amp; floor tile', 'Niches &amp; trim pieces', 'Grout color &amp; sealing', 'Tub replacement'],
                'Planning guide: tile',
                [('Waterproofing matters more than tile.', 'Ask any contractor what membrane goes behind the shower walls.'),
                 ('Big tiles need flat walls.', 'Large-format tile shows every bump, so walls get flattened before it goes up.'),
                 ('Darker grout hides wear.', 'Charcoal or black grout keeps a white shower looking clean longer.'),
                 ('Keep a spare box.', 'We order 10–15% extra and leave the leftovers labeled for future repairs.')]),
        svc_row('powder-rooms', 'powder', 'Wallpaper &amp; wainscoting powder room · Pottstown, PA',
                'Powder rooms', 'Powder Room Makeovers',
                'A small room that makes a big first impression: wallpaper, wainscoting, a new vanity, faucet and toilet. A quick project when the plumbing stays put.',
                ['Wallpaper &amp; wainscoting', 'Vanity, faucet &amp; toilet', 'Lighting &amp; mirrors', 'Floor tile &amp; trim'],
                'Planning guide: powder rooms',
                [('Go bold here.', 'With no shower steam, a powder room can take wallpaper and colors you\'d skip in a full bath.'),
                 ('Match your metals.', 'Pick the faucet finish first, then match hardware, lighting and mirror frames to it.'),
                 ('Mind the door swing.', 'A smaller vanity or a pocket door can make a tight half bath feel twice the size.')]),
        svc_row('vanities', 'vanity', 'Custom oak vanity with rope columns · Philadelphia, PA',
                'Vanities &amp; fixtures', 'Vanities, Fixtures &amp; Storage',
                'Stock or custom vanities, tops and sinks, plus faucets, mirrors, lighting and storage sized to the room.',
                ['Stock &amp; custom vanities', 'Quartz, marble &amp; solid-surface tops', 'Faucets, toilets &amp; accessories', 'Vanity lighting &amp; outlets'],
                'Planning guide: vanities',
                [('Measure the door, not just the wall.', 'A vanity that fits the room still has to fit through the hallway and door.'),
                 ('Think about height.', 'Comfort-height vanities (about 36 inches) are easier on your back than older 30-inch cabinets.'),
                 ('Light the face, not the top of the head.', 'Sconces or a bar light at eye level beat a single ceiling fixture.')]),
    ])
    return f'''
  <!-- ============ SERVICES ============ -->
  <section class="sec" id="services" aria-labelledby="services-title">
    <div class="inner">
      <div class="sec-head">
        <p class="eyebrow">What we build</p>
        <h2 id="services-title">Bathroom Remodeling Services in Trappe</h2>
        <p class="lede">From a powder room refresh to a full primary bath, every bathroom gets the same plan, the same project manager and the same care behind the walls. The photos are real Fox &amp; Fern projects from around the area. <a class="inline" href="/projects/">See more of our projects</a>.</p>
      </div>
      <div class="svc-rows">{rows}
      </div>{design()}
    </div>
  </section>'''


def design():
    # sample floor plan, 8' x 5' hall bath: walk-in shower left, vanity on the back wall, toilet right, door at the front
    plan = '''<svg class="d-plan" viewBox="0 0 320 214" role="img" aria-labelledby="dp-title">
              <title id="dp-title">Sample floor plan of a hall bathroom with a walk-in shower, a vanity and a toilet</title>
              <g class="dm"><path d="M30 10 V20 M300 10 V20 M30 15 H140 M190 15 H300 M9 30 H19 M9 196 H19 M14 30 V88 M14 138 V196"/><text x="165" y="19" text-anchor="middle">8′-0″</text><text x="18" y="113" text-anchor="middle" transform="rotate(-90 14 113)">5′-0″</text></g>
              <rect class="wet" x="33" y="33" width="78" height="160"/>
              <path class="slope" d="M33 33 L72 113 L111 33 M33 193 L72 113 L111 193"/>
              <circle class="fx" cx="72" cy="113" r="6"/><path class="fx" d="M68 113 H76 M72 109 V117"/>
              <path class="acc" d="M50 30 H94 M112 33 V140 M36 62 V108"/>
              <path class="fx" d="M140 33 H232 V74 H140 Z"/><ellipse class="fx" cx="186" cy="52" rx="22" ry="11"/><path class="fx" d="M186 33 V41"/>
              <path class="fx" d="M282 92 H297 V136 H282 Z"/><ellipse class="fx" cx="264" cy="114" rx="18" ry="13"/>
              <path class="fx" d="M146 112 h16 v16 h-16 Z M146 112 l16 16 M162 112 l-16 16"/>
              <path class="door" d="M226 196 V140"/><path class="swing" d="M170 196 A56 56 0 0 1 226 140"/>
              <path class="wall" d="M170 196 H30 V30 H300 V196 H226"/>
              <g class="lbl"><text x="72" y="183" text-anchor="middle">SHOWER</text><text x="186" y="90" text-anchor="middle">VANITY</text><text x="264" y="152" text-anchor="middle">WC</text><text x="154" y="146" text-anchor="middle">FAN</text><text x="198" y="210" text-anchor="middle">DOOR</text></g>
            </svg>'''
    return f'''

      <div class="design" id="design">
        <div class="design-copy">
          <p class="kicker">Bathroom design</p>
          <h3>Use Your Own Designer or One of Our Partner Designers</h3>
          <p>A great bathroom starts with a good plan: the layout, the tile, the vanity and the finishes. Plan it with the designer you're most comfortable with, and we'll build it.</p>
          <div class="d-opts">
            <div class="d-opt"><svg><use href="#i-pencil"/></svg><h4>Have a designer?</h4><p>Bring them in. We build from their layout and selections and work with them when questions come up on site.</p></div>
            <div class="d-opt"><svg><use href="#i-swatch"/></svg><h4>Need a designer?</h4><p>We'll connect you with one of our partner designers to plan the layout and choose tile, the vanity, fixtures and finishes.</p></div>
          </div>
          <p class="ba-note"><svg><use href="#i-receipt"/></svg><span><b>Already have plans?</b> Bring them to your <a class="inline" href="{ESTIMATE}">free walkthrough</a> and we'll price your estimate from them.</span></p>
        </div>
        <div class="board" role="group" aria-label="Sample design board for a bathroom">
          <div class="sheet-h"><span>Design board · Sample</span><span>Trappe, PA</span></div>
          <p class="sheet-t">Hall bath plan</p>
          {plan}
          <div class="d-swatches">
            <div class="sw"><i class="sw-wall"></i><span>Wall tile<b>Stacked white</b></span></div>
            <div class="sw"><i class="sw-floor"></i><span>Floor<b>Marble-look</b></span></div>
            <div class="sw"><i class="sw-wood"></i><span>Vanity<b>Walnut</b></span></div>
          </div>
          <div class="d-finish"><span>Fixture finish</span><i class="f-chrome"></i><i class="f-black"></i><i class="f-brass"></i><b>Matte black</b></div>
          <ul class="d-sign">
            <li><svg><use href="#i-check"/></svg>Layout set</li>
            <li><svg><use href="#i-check"/></svg>Selections made</li>
            <li><svg><use href="#i-check"/></svg>Priced in your estimate</li>
          </ul>
        </div>
      </div>'''


def local():
    older = '<svg class="hc-art" viewBox="0 0 260 110" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M10 104H250M40 104V44L110 12L180 44V104M30 50L110 12L190 50M60 104V66H86V104M118 60H156V84H118ZM137 60V84M118 72H156M205 104V58H240V104M205 58L222 44L240 58M212 70H232V84H212Z" opacity=".9"/><path d="M44 50H176M44 58H176" opacity=".35"/></svg>'
    newer = '<svg class="hc-art" viewBox="0 0 260 110" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="M10 104H250M24 104V54L74 28L124 54V104M138 104V54L188 28L238 54V104M46 104V76H66V104M86 66H110V86H86ZM160 104V76H180V104M200 66H224V86H200ZM40 66H62V80H40ZM154 62H176V74H154Z"/></svg>'
    return f'''
  <!-- ============ LOCAL ============ -->
  <section class="sec sec-alt" id="trappe" aria-labelledby="trappe-title">
    <div class="inner">
      <div class="sec-head">
        <p class="eyebrow">Local know-how</p>
        <h2 id="trappe-title">Remodeling Bathrooms in Trappe's Older and Newer Homes</h2>
        <p class="lede">Trappe is a borough of about 4,000 people with a long history: Augustus Lutheran Church dates to 1743, and the Speaker's House on West Main Street, once home to Frederick Muhlenberg, the first Speaker of the U.S. House, reopened in 2026 after a 25-year restoration. Homes range from early houses near Main Street to newer neighborhoods, and each kind comes with its own bathroom surprises.</p>
      </div>
      <div class="homes">
        <article class="home-card">
          {older}
          <h3>Older homes near Main Street</h3>
          <p>Character worth keeping, plus a few things that only show up once the walls are open.</p>
          <ul class="hc-list">
            <li><svg><use href="#i-check"/></svg><span><b>Uneven floors:</b> we check the framing and flatten the floor before tile goes down.</span></li>
            <li><svg><use href="#i-check"/></svg><span><b>Aging supply lines and drains:</b> replaced while the walls are open, not patched later.</span></li>
            <li><svg><use href="#i-check"/></svg><span><b>Plaster walls:</b> cut cleanly and patched, or replaced with cement board where water will hit.</span></li>
            <li><svg><use href="#i-check"/></svg><span><b>No real ventilation:</b> a quiet exhaust fan ducted outdoors to protect the new room.</span></li>
          </ul>
        </article>
        <article class="home-card">
          {newer}
          <h3>Newer neighborhoods</h3>
          <p>Solid houses with bathrooms that are often still original, and ready for an upgrade.</p>
          <ul class="hc-list">
            <li><svg><use href="#i-check"/></svg><span><b>Fiberglass tub-shower units:</b> replaced with a tiled shower or a tub-to-shower conversion.</span></li>
            <li><svg><use href="#i-check"/></svg><span><b>Builder-grade vanities and cultured-marble tops:</b> swapped for furniture-style vanities and quartz.</span></li>
            <li><svg><use href="#i-check"/></svg><span><b>Fans venting into the attic:</b> re-ducted to the outside so moisture leaves the house.</span></li>
            <li><svg><use href="#i-check"/></svg><span><b>Dated lighting:</b> new vanity lights at face height and GFCI outlets where code calls for them.</span></li>
          </ul>
        </article>
      </div>

      <div class="permit">
        <div class="permit-text">
          <div>
            <h3><svg><use href="#i-stamp"/></svg>Trappe Borough permits</h3>
            <p>Trappe Borough builds under the Pennsylvania Uniform Construction Code, with plan reviews and inspections run through the borough's code office. Moving or adding a toilet, tub, shower, sink or drain needs a plumbing permit; swapping existing fixtures in the same spot generally doesn't. New electrical circuits can need a permit too. Applications include the contractor's certificate of insurance, and HOA approval if your neighborhood has an association. We confirm what your project needs, file the paperwork and schedule every inspection before the walls close.</p>
          </div>
          <div>
            <h3><svg><use href="#i-home"/></svg>Living at home during the work</h3>
            <p>Most Trappe homeowners stay put. We protect the floors and the path to the bathroom, put up a dust wall, haul debris out daily and tell you in the app which days the water will be off.</p>
          </div>
        </div>
        <div class="sheet" role="group" aria-label="Example approval checklist for a bathroom in Trappe">
          <div class="sheet-h"><span>Approvals · Sample bathroom</span><span>Trappe, PA</span></div>
          <p class="sheet-t">Before the walls close</p>
          <ol>
            <li>Scope confirmed: which fixtures stay and which move</li>
            <li>Plumbing permit filed with Trappe Borough, if fixtures move</li>
            <li>Certificate of insurance on file with the borough</li>
            <li>Rough-in inspection scheduled and passed</li>
            <li>Final inspection and sign-off</li>
          </ol>
          <div class="stamps" aria-hidden="true">
            <span class="stamp">Permit issued<small>Trappe Borough</small></span>
            <span class="stamp">Rough-in passed<small>Inspection</small></span>
            <span class="stamp">Final passed<small>Sign-off</small></span>
          </div>
        </div>
      </div>
      <p class="src-note">Permit details summarized from Trappe Borough's plumbing and building permit applications, October 2026. Requirements can change, so we confirm them with the borough's code office for every project.</p>
    </div>
  </section>'''


def bath_svg():
    # 640x400 elevation of a bathroom wall: shower alcove left (x60-250), vanity centre (x300-440), toilet right (x480-570).
    studs = ' '.join(f'M{x} 40 V360' for x in range(76, 600, 32))
    tile_v = ' '.join(f'M{x} 48 V338' for x in range(76, 250, 16))
    tile_h = ' '.join(f'M61 {y} H249' for y in range(96, 338, 48))
    floor = ' '.join(f'M{x} 352 V360' for x in range(256, 600, 10))
    return f'''<svg class="kitchen" id="bath-build" viewBox="0 0 640 400" role="img" aria-labelledby="bb-title">
            <title id="bb-title">Drawing of a bathroom being remodeled, stage by stage</title>
            <path class="ln thin" d="M20 360 H620 M40 40 H600 M40 40 V360 M600 40 V360"/>
            <g class="stg" data-from="1" data-to="1"><rect class="tub" x="62" y="296" width="186" height="62" rx="6"/><path class="ln" d="M62 296 H248 M62 120 H248 M62 120 V296 M248 120 V296 M70 300 Q155 330 240 300"/><path class="ln" d="M312 262 H428 V360 H312 Z M312 262 V255 H428 V262 M356 258 Q370 272 384 258 M330 140 H410 V220 H330 Z"/><path class="ln" d="M500 236 H548 V280 H500 Z M492 286 Q524 330 556 286 Z M512 330 H536 V358 H512 Z"/></g>
            <g class="stg" data-from="1" data-to="2"><path class="ln gold dash" d="M60 352 H580 M600 50 V350"/></g>
            <g class="stg" data-from="2" data-to="6"><path class="ln thin" d="{studs}"/></g>
            <g class="stg" data-from="3" data-to="6"><path class="ln" d="M118 168 H190 V214 H118 Z M114 164 H194 M114 218 H194"/><path class="ln gold" d="M206 222 H244 V230 H206 Z M300 244 H440 V252 H300 Z"/></g>
            <g class="stg" data-from="4" data-to="6"><path class="ln gold dash" d="M155 300 V112 M150 236 H160 M140 228 H170 V246 H140 Z M155 352 V346 M120 352 H190 M360 360 V300 M380 360 V290 M370 290 H390 M520 360 V346 M508 350 H532"/></g>
            <g class="stg" data-from="5" data-to="6"><path class="ln gold dash" d="M370 98 V60 H440 V40 M440 40 V24 M452 58 V250 M446 250 H458 V262 H446 Z M410 44 H470 V58 H410 Z"/><text class="lbl" x="470" y="30">FAN → OUTSIDE</text></g>
            <g class="stg k-stamp" data-from="6" data-to="6"><g transform="rotate(-8 470 150)"><rect x="370" y="126" width="200" height="48" rx="6"/><text x="470" y="157" text-anchor="middle">ROUGH-IN PASSED</text></g></g>
            <g class="stg" data-from="7" data-to="15"><path class="ln" d="M62 338 H248 V358 H62 Z M70 348 L155 354 L240 348"/></g>
            <g class="stg" data-from="7" data-to="9"><rect class="wall" x="61" y="41" width="188" height="297"/><path class="ln thin" d="M61 136 H249 M61 232 H249 M155 41 V338"/></g>
            <g class="stg" data-from="8" data-to="9"><rect class="membrane" x="61" y="41" width="188" height="317"/><text class="lbl" x="155" y="140" text-anchor="middle">MEMBRANE</text></g>
            <g class="stg" data-from="9" data-to="15"><rect class="wall" x="250" y="41" width="349" height="318"/></g>
            <g class="stg" data-from="10" data-to="15"><rect class="wall" x="61" y="41" width="188" height="297"/><path class="ln thin" d="{tile_v} {tile_h}"/><path class="ln" d="M118 168 H190 V214 H118 Z"/></g>
            <g class="stg" data-from="11" data-to="15"><path class="ln thin" d="{floor} M250 352 H599"/></g>
            <g class="stg" data-from="12" data-to="12"><path class="ln gold dash" d="M61 96 H249 M61 144 H249 M61 192 H249 M61 240 H249 M61 288 H249 M250 352 H599"/></g>
            <g class="stg" data-from="13" data-to="15"><rect class="counter" x="296" y="256" width="148" height="10" rx="1"/><path class="ln" d="M302 266 H438 V318 H302 Z M370 266 V318 M310 292 H362 M378 292 H430 M326 256 Q370 246 414 256"/><path class="ln" d="M350 126 H390 L410 146 V186 L390 206 H350 L330 186 V146 Z"/></g>
            <g class="stg" data-from="14" data-to="15"><path class="glow" d="M346 98 H394 L410 124 H330 Z"/><path class="ln" d="M346 92 H394 V98 H346 Z M370 256 V240 Q370 232 378 232 H384"/><path class="ln gold" d="M140 112 H170 L162 122 H148 Z M147 236 A8 8 0 1 0 163 236 A8 8 0 1 0 147 236"/><rect class="glass" x="234" y="60" width="14" height="278"/><path class="ln" d="M500 232 H548 V278 H500 Z M492 284 Q524 326 556 284 Z M514 326 H534 V358 H514 Z"/></g>
            <g class="stg k-done" data-from="15" data-to="15"><rect x="460" y="54" width="128" height="34" rx="17"/><text x="524" y="76" text-anchor="middle">COMPLETE</text></g>
          </svg>'''


def build():
    return f'''
  <!-- ============ PROGRESS APP ============ -->
  <section class="sec" id="progress" aria-labelledby="progress-title">
    <div class="inner">
      <div class="sec-head">
        <p class="eyebrow">Daily progress app</p>
        <h2 id="progress-title">Follow Your Bathroom Remodel Day by Day</h2>
        <p class="lede">Drag through a sample bathroom remodel. Every working day, your project app shows what happened, dated photos and what's next.</p>
      </div>
      <div class="build">
        <div class="build-stage">
          {bath_svg()}
          <div class="scrub">
            <button type="button" id="playBtn" aria-label="Play the build"><svg><use href="#i-play"/></svg></button>
            <input type="range" class="day-range" id="dayRange" min="1" max="15" step="1" value="1" aria-label="Project day" aria-valuetext="Day 1">
            <output id="dayOut" for="dayRange">Day 1</output>
          </div>
          <div class="phase-row" aria-hidden="true"><span>Demo</span><span>Rough-in</span><span>Inspection</span><span>Waterproof</span><span>Tile</span><span>Vanity</span><span>Fixtures</span><span>Done</span></div>
        </div>
        <div class="phone" aria-live="polite">
          <div class="phone-top"><i></i></div>
          <div class="app-h">
            <p>Your project app · Sample</p>
            <p class="app-name">Bathroom Remodel</p>
            <div class="app-bar"><span id="appBar"></span></div>
            <div class="app-meta"><span><b id="appDay">Day 1</b> of 15</span><span>On schedule</span></div>
          </div>
          <div class="app-card">
            <div class="when"><span id="appDate">Mon, Mar 1</span><span>Updated</span></div>
            <p class="app-t" id="appTitle">Protection &amp; demolition prep</p>
            <p id="appText">Floors and the hallway are covered, a dust wall goes up and the water is shut off before anything comes out.</p>
            <figure class="ph">{picture('shower-wide', PHONE_SIZES)}<figcaption class="ph-tag" id="appPhoto">Day 1 photo</figcaption></figure>
          </div>
          <div class="app-foot" aria-hidden="true"><span>Updates</span><span>Photos</span><span>Products</span><span>Warranty</span></div>
        </div>
      </div>
      <ul class="build-notes">
        <li><svg><use href="#i-app"/></svg><span><b>Updated every working day</b>What was done, dated photos and what's next.</span></li>
        <li><svg><use href="#i-cam"/></svg><span><b>Inside-the-wall photos</b>The valve, drain and wiring are photographed before cement board goes up, for your records.</span></li>
        <li><svg><use href="#i-receipt"/></svg><span><b>Product sheet &amp; warranty</b>The exact tile, grout, fixtures and paint used, plus your warranty certificate, in the same app.</span></li>
      </ul>
    </div>
  </section>'''


def pricing():
    rows = [
        ('fixed', 'Design &amp; selections', 'Layout and selections from your designer or one of our partner designers', 'Fixed'),
        ('incl', 'Permits &amp; inspections', 'Trappe Borough plumbing permit and inspections, when fixtures move', 'Included'),
        ('fixed', 'Protection &amp; demolition', 'Floor and hallway protection, dust wall, removal and haul-away', 'Fixed'),
        ('fixed', 'Plumbing', 'Shower valve and drain; vanity and toilet connections', 'Fixed'),
        ('fixed', 'Electrical &amp; ventilation', 'Vanity light, GFCI outlets, exhaust fan vented outdoors', 'Fixed'),
        ('fixed', 'Waterproofing', 'Cement board and waterproof membrane in the shower', 'Fixed'),
        ('allow', 'Tile', 'Wall, floor and niche tile with setting materials', 'Allowance'),
        ('allow', 'Vanity &amp; top', 'Vanity, top and sink', 'Allowance'),
        ('allow', 'Fixtures &amp; glass', 'Faucet, shower trim, toilet, mirror and glass door', 'Allowance'),
        ('incl', 'Project management', 'Project manager, daily app updates, final walkthrough', 'Included'),
        ('pay', 'Payment schedule', 'Amounts and due dates written into the estimate', 'Written'),
        ('change', 'Change orders', 'Only by written, signed change order', 'Signed'),
    ]
    trs = ''.join(
        f'<tr data-type="{t}"><td>{a}</td><td>{b}</td><td><span class="type{" allow" if t == "allow" else ""}">{c}</span></td></tr>'
        for t, a, b, c in rows)
    return f'''
  <!-- ============ PRICING ============ -->
  <section class="sec sec-alt" id="pricing" aria-labelledby="pricing-title">
    <div class="inner">
      <div class="sec-head">
        <p class="eyebrow">Transparent pricing</p>
        <h2 id="pricing-title">What a Bathroom Remodel Costs in Trappe</h2>
        <p class="lede">Every bathroom is priced on its own. Your price depends on the size of the room, whether plumbing moves, and the tile, vanity and fixtures you choose. Tap a principle to see where it shows up on a sample estimate.</p>
      </div>
      <div class="pricing">
        <div class="principles">
          <button type="button" class="principle" data-type="fixed" aria-pressed="true"><b>Fixed, itemized price</b><span>Every line is priced and described. The total doesn't move unless you approve a change.</span></button>
          <button type="button" class="principle" data-type="allow" aria-pressed="false"><b>Allowances, explained</b><span>For tile, vanities and fixtures you haven't picked yet, we set a clear budget. If your choice costs more or less, you see the difference in writing first.</span></button>
          <button type="button" class="principle" data-type="incl" aria-pressed="false"><b>Nothing hidden</b><span>Permits, inspections and project management are on the estimate, not added later.</span></button>
          <button type="button" class="principle" data-type="change" aria-pressed="false"><b>Changes in writing</b><span>Want to change the scope mid-project? You sign a written, priced change order before the work happens.</span></button>
          <button type="button" class="principle" data-type="pay" aria-pressed="false"><b>Payment schedule up front</b><span>Your estimate spells out what's due and when. Financing through Wisetack is available; 0% APR up to 12 months for eligible customers.*</span></button>
        </div>
        <div class="estimate">
          <div class="est-h"><div><p>Sample estimate · Not a quote</p><h3>Bathroom Remodel</h3></div><p>Fox &amp; Fern · PA217947</p></div>
          <div class="est-wrap" tabindex="0" role="region" aria-label="Sample estimate line items">
            <table class="est">
              <thead><tr><th scope="col">Line item</th><th scope="col">What's included</th><th scope="col">Pricing</th></tr></thead>
              <tbody>{trs}</tbody>
            </table>
          </div>
          <p class="est-foot">Prices are set per project after an on-site walkthrough. This sample shows how an estimate is organized, not what your bathroom will cost.</p>
        </div>
      </div>
      <ul class="drivers">
        <li><b>Room size</b><span>A powder room and a primary bath with a separate shower are very different jobs.</span></li>
        <li><b>Plumbing moves</b><span>Keeping fixtures where they are saves the most; relocating a drain adds labor and a permit.</span></li>
        <li><b>Tile &amp; finishes</b><span>Porcelain vs. natural stone, mosaic vs. large format, and how much wall gets tiled.</span></li>
        <li><b>Shower glass &amp; extras</b><span>Frameless glass, niches, benches, heated floors and custom vanities.</span></li>
      </ul>
    </div>
  </section>'''


FIN_TERMS = ('*All financing is subject to credit approval. Your terms may vary. Payment options through Wisetack are provided by our '
             'lending partners. For example, a $5,500 purchase could cost $253.54 per month for 24 months, based on a 9.9% APR, or '
             '$1,833.33 per month for 3 months, based on a 0% APR. Offers range from 0 to 35.9% APR based on amount requested and '
             'creditworthiness. Not all merchants and lending partners participate in 0% interest programs. Terms range from 3 - 120 '
             'months and may vary based on merchant, lending partner, or transaction size. State interest rate caps may apply. No '
             'prepaid finance charges or participation fees. See additional terms at wisetack.com/faqs.')


def financing():
    return f'''
  <!-- ============ FINANCING ============ -->
  <section class="sec" id="financing" aria-labelledby="financing-title">
    <div class="inner">
      <div class="fin">
        <div class="fin-copy">
          <p class="eyebrow">Financing through Wisetack</p>
          <h2 id="financing-title">Spread the Cost of Your New Bathroom</h2>
          <p class="lede">Slide to your budget and pick a plan. When you're ready, checking your real options takes a few minutes on your phone and is a soft credit check, so it won't affect your credit score.</p>
          <ul class="checks">
            <li><svg><use href="#i-check"/></svg><span><b>0% APR for up to 12 months*</b> for eligible homeowners</span></li>
            <li><svg><use href="#i-check"/></svg><span><b>Lower monthly payments</b> with longer terms, like $446/mo on a $15,000 bathroom<sup>†</sup></span></li>
            <li><svg><use href="#i-check"/></svg><span><b>Ask at your walkthrough:</b> we'll show your options next to your estimate</span></li>
          </ul>
        </div>
        <div class="calc" id="calc">
          <div class="calc-row">
            <label for="budget">Your bathroom budget</label>
            <output id="budgetOut" for="budget">$15,000</output>
          </div>
          <input type="range" id="budget" min="10000" max="50000" step="500" value="15000" aria-describedby="calcNote">
          <div class="calc-ends" aria-hidden="true"><span>$10,000</span><span>$50,000</span></div>
          <fieldset class="plans">
            <legend>Payment plan</legend>
            <label><input type="radio" name="plan" value="0" checked><span><b>0% APR</b><small>12 months*</small></span></label>
            <label><input type="radio" name="plan" value="1"><span><b>Lowest monthly</b><small>42 months<sup>†</sup></small></span></label>
          </fieldset>
          <div class="pay" aria-live="polite">
            <span class="pay-k">Estimated payment</span>
            <span class="pay-n"><b id="payN">$1,250</b>/mo</span>
            <span class="pay-s" id="payS">for 12 months at 0% APR*</span>
          </div>
          <a class="btn btn-gold" href="/contact/?promo=financing-12mo&amp;project=bathroom&amp;town=trappe">Get My Estimate &amp; Financing Options <svg class="arr"><use href="#i-arrow"/></svg></a>
          <p class="calc-note" id="calcNote">Estimates for illustration only, not a loan offer. Your actual rate and terms depend on credit approval through Wisetack.</p>
        </div>
      </div>

      <div class="tickets">
        <a class="ticket" href="/contact/?promo=financing-12mo&amp;project=bathroom&amp;town=trappe">
          <div class="t-main">
            <div class="t-top"><span class="t-no">Offer 01</span><span class="t-status">Always available</span></div>
            <p class="t-value">0%<small>APR up to 12 months*</small></p>
            <h3 class="t-title">Financing Through Wisetack</h3>
            <p class="t-desc">Pay over time instead of all at once. 0% APR available up to 12 months for eligible customers.* Checking your options does not impact your credit score.</p>
            <p class="t-fine">*All financing is subject to credit approval. Your terms may vary. Promotions can't be combined.</p>
          </div>
          <div class="t-stub" aria-hidden="true"><span>Claim offer</span><svg><use href="#i-arrow-down"/></svg></div>
        </a>
        <a class="ticket" href="/contact/?promo=heroes-15&amp;project=bathroom&amp;town=trappe">
          <div class="t-main">
            <div class="t-top"><span class="t-no">Offer 02</span><span class="t-status">Always available</span></div>
            <p class="t-value">15%<small>off your project</small></p>
            <h3 class="t-title">Hometown Heroes</h3>
            <p class="t-desc">Our thank-you to veterans, first responders, teachers and nurses.</p>
            <p class="t-fine">Valid ID or proof of service required. Promotions can't be combined.</p>
          </div>
          <div class="t-stub" aria-hidden="true"><span>Claim offer</span><svg><use href="#i-arrow-down"/></svg></div>
        </a>
        <a class="ticket" href="{ESTIMATE}">
          <div class="t-main">
            <div class="t-top"><span class="t-no">Offer 03</span><span class="t-status">Always free</span></div>
            <p class="t-value">$0<small>on-site estimate</small></p>
            <h3 class="t-title">Free Walkthrough &amp; Estimate</h3>
            <p class="t-desc">We measure your bathroom, talk through options and send a fixed, itemized estimate. No cost, no obligation.</p>
            <p class="t-fine">Serving Trappe and nearby towns from our Linfield office.</p>
          </div>
          <div class="t-stub" aria-hidden="true"><span>Book it</span><svg><use href="#i-arrow-down"/></svg></div>
        </a>
      </div>
      <p class="promo-legal" id="financing-terms">†Example: a $15,000 bathroom financed at 12.9% APR for 42 months is $445.70 per month, $18,719.40 total of payments. 0% APR example: a $15,000 bathroom is $1,250.00 per month for 12 months, $15,000 total of payments. Rates and terms shown are examples, not offers.</p>
      <p class="promo-legal">{FIN_TERMS}</p>
      <p class="promo-legal">Promotions can't be combined and are subject to change. <b>Pennsylvania Home Improvement Contractor Registration No. PA217947.</b></p>
    </div>
  </section>'''


def process():
    steps = [
        ('Free on-site walkthrough', 'We come to your home in Trappe, measure the bathroom and talk through what you want and what you\'d like to spend.'),
        ('Fixed, itemized estimate', 'A line-by-line price with written payment terms. It only changes if you sign a change order.'),
        ('Design &amp; selections', 'Plan the layout and pick tile, vanity and fixtures with your own designer or one of our partner designers. Then we file permits if plumbing moves and order long-lead items.'),
        ('Build, with daily updates', 'Your project manager runs the job; the app shows what was done, photos and what\'s next every day.'),
        ('Final walkthrough &amp; warranty', 'We walk the finished bathroom together, finish every touch-up and hand over your warranty certificate and product sheet.'),
    ]
    lis = ''.join(f'<li class="step"><span class="step-n" aria-hidden="true">{i:02d}</span><div><h3>{h}</h3><p>{p}</p></div></li>'
                  for i, (h, p) in enumerate(steps, 1))
    return f'''
  <!-- ============ PROCESS ============ -->
  <section class="sec sec-alt" id="process" aria-labelledby="process-title">
    <div class="inner">
      <div class="sec-head">
        <p class="eyebrow">How it works</p>
        <h2 id="process-title">How Your Trappe Bathroom Remodel Works</h2>
        <p class="lede">A clear price, one project manager and daily updates, so you never have to guess where your bathroom stands.</p>
      </div>
      <ol class="steps">{lis}</ol>
      <p class="sec-links"><a class="btn btn-gold" href="{ESTIMATE}">Book Your Free Walkthrough <svg class="arr"><use href="#i-arrow"/></svg></a><a class="tlink" href="tel:{TEL}">Or call {TEL_TXT}</a></p>
    </div>
  </section>'''


def reviews():
    return '''
  <!-- ============ REVIEWS ============ -->
  <section class="sec" id="reviews" aria-labelledby="reviews-title">
    <div class="inner">
      <div class="sec-head">
        <p class="eyebrow">Reviews</p>
        <h2 id="reviews-title">What Our Customers Say</h2>
        <p class="lede">Reviews from Fox &amp; Fern customers, collected through Housecall Pro.</p>
      </div>
      <div class="reviews-frame">
        <iframe class="hcp-reviews" src="https://client.housecallpro.com/reviews/widget/698af876-7701-4cd9-a95f-e91a2caefac8" title="Fox &amp; Fern customer reviews on Housecall Pro" loading="lazy" width="1000" height="1000"></iframe>
      </div>
      <p class="sec-links"><a class="tlink" href="https://share.google/WN2Vt5ldGNum1rxIm">Read our Google reviews</a><a class="tlink" href="https://www.yelp.com/biz/fox-and-fern-property-services-linfield">Read our Yelp reviews</a></p>
    </div>
  </section>'''


def faq():
    items = ''.join(
        f'<details name="faq"{" open" if i == 0 else ""}><summary>{html.escape(q, quote=False)}</summary><p>{html.escape(a, quote=False)}</p></details>'
        for i, (q, a) in enumerate(FAQ))
    return f'''
  <!-- ============ FAQ ============ -->
  <section class="sec sec-alt" id="faq" aria-labelledby="faq-title">
    <div class="inner">
      <div class="sec-head">
        <p class="eyebrow">Questions</p>
        <h2 id="faq-title">Trappe Bathroom Remodeling Questions</h2>
      </div>
      <div class="faq">{items}</div>
    </div>
  </section>'''


def areas():
    return '''
  <!-- ============ NEARBY ============ -->
  <section class="sec" id="areas" aria-labelledby="areas-title">
    <div class="inner">
      <div class="sec-head">
        <p class="eyebrow">Where we work</p>
        <h2 id="areas-title">Bathroom Remodeling in Trappe and Nearby Towns</h2>
        <p class="lede">Our office is in Linfield, in Limerick Township, a short drive from Trappe along the Route 422 corridor. We remodel bathrooms across western Montgomery County and the rest of the county.</p>
      </div>
      <ul class="near">
        <li><span class="here" aria-current="page">Trappe</span></li>
        <li><a href="/service-areas/#collegeville">Collegeville</a></li>
        <li><a href="/service-areas/#limerick">Limerick</a></li>
        <li><a href="/service-areas/#linfield">Linfield</a></li>
        <li><a href="/service-areas/#royersford">Royersford</a></li>
        <li><a href="/service-areas/#pottstown">Pottstown</a></li>
        <li><a href="/service-areas/#king-of-prussia">King of Prussia</a></li>
        <li><span>Schwenksville</span></li>
        <li><span>Skippack</span></li>
      </ul>
      <p class="areas-link"><a class="inline" href="/service-areas/">See every town we serve</a></p>
    </div>
  </section>'''


def explore():
    return '''
  <!-- ============ INTERNAL LINKS ============ -->
  <section class="sec sec-alt" id="explore" aria-labelledby="explore-title">
    <div class="inner">
      <div class="sec-head">
        <p class="eyebrow">Explore more</p>
        <h2 id="explore-title">More from Fox &amp; Fern</h2>
      </div>
      <ul class="xl-links">
        <li><a href="/residential-remodeling/"><b>Residential remodeling</b><span>Kitchens, bathrooms, basements and exterior work</span><svg><use href="#i-arrow"/></svg></a></li>
        <li><a href="/projects/"><b>Projects</b><span>Before-and-after photos from recent jobs</span><svg><use href="#i-arrow"/></svg></a></li>
        <li><a href="/repairs-maintenance/"><b>Repairs &amp; maintenance</b><span>Leaks, fixtures, drywall and small bathroom fixes</span><svg><use href="#i-arrow"/></svg></a></li>
        <li><a href="/about/"><b>About our team</b><span>Meet the family behind your project</span><svg><use href="#i-arrow"/></svg></a></li>
      </ul>
    </div>
  </section>'''


def cta():
    return f'''
  <!-- ============ CTA ============ -->
  <section class="cta-band" aria-labelledby="cta-title">
    <div class="inner cta-in">
      <div>
        <h2 id="cta-title">Plan your Trappe bathroom with a free walkthrough</h2>
        <p>We'll measure, talk through options and send a fixed, itemized estimate.</p>
      </div>
      <div class="cta-actions">
        <a class="btn btn-dark" href="{ESTIMATE}">Get a Free Estimate <svg class="arr"><use href="#i-arrow"/></svg></a>
        <a class="cta-tel" href="tel:{TEL}">{TEL_TXT}</a>
      </div>
    </div>
  </section>'''


# ---------------------------------------------------------------- structured data
def image_object(key):
    m = IMG[key]
    return {
        '@type': 'ImageObject', '@id': jpg800(key) + '#image', 'contentUrl': jpg800(key),
        'width': m.get('ratio', (800, 600))[0], 'height': m.get('ratio', (800, 600))[1],
        'caption': m['caption'], 'description': m['alt'],
        'creator': {'@id': SITE + '/#business'}, 'creditText': 'Fox & Fern Property Services',
        'copyrightNotice': '© 2026 Fox & Fern Property Services, LLC',
        'contentLocation': {'@type': 'Place', 'address': {'@type': 'PostalAddress', 'addressLocality': m['town'], 'addressRegion': 'PA', 'addressCountry': 'US'}},
    }


def jsonld():
    trappe = {'@type': 'City', 'name': 'Trappe, PA', 'sameAs': 'https://en.wikipedia.org/wiki/Trappe,_Pennsylvania',
              'containedInPlace': {'@type': 'AdministrativeArea', 'name': 'Montgomery County, PA'}}
    imgs = ['after', 'before', 'shower', 'tub', 'powder', 'vanity']
    services = ['Full bathroom remodeling', 'Tub-to-shower conversion', 'Walk-in shower installation',
                'Tile shower and tub surround installation', 'Powder room remodeling', 'Vanity and fixture installation']
    graph = [
        {'@type': 'BreadcrumbList', '@id': URL + '#breadcrumb', 'itemListElement': [
            {'@type': 'ListItem', 'position': 1, 'name': 'Home', 'item': SITE + '/'},
            {'@type': 'ListItem', 'position': 2, 'name': 'Residential Remodeling', 'item': SITE + '/residential-remodeling/'},
            {'@type': 'ListItem', 'position': 3, 'name': 'Bathrooms in Trappe', 'item': URL}]},
        {'@type': 'WebPage', '@id': URL + '#webpage', 'url': URL, 'name': TITLE, 'description': DESC, 'inLanguage': 'en-US',
         'isPartOf': {'@id': SITE + '/#website'}, 'about': {'@id': URL + '#service'}, 'breadcrumb': {'@id': URL + '#breadcrumb'},
         'primaryImageOfPage': {'@id': jpg800('after') + '#image'}, 'datePublished': PUBLISHED, 'dateModified': PUBLISHED},
        {'@type': 'Service', '@id': URL + '#service', 'name': 'Bathroom Remodeling in Trappe, PA', 'serviceType': 'Bathroom remodeling',
         'description': 'Bathroom remodeling for homes in Trappe, PA: full remodels, tub-to-shower conversions, tile showers, powder rooms, '
                        'vanities and fixtures. Design with your own designer or one of Fox & Fern\'s partner designers; permits handled, '
                        'a fixed itemized price and daily photo updates.',
         'provider': {'@id': SITE + '/#business'}, 'url': URL, 'areaServed': trappe,
         'hasOfferCatalog': {'@type': 'OfferCatalog', 'name': 'Bathroom remodeling services in Trappe, PA',
                             'itemListElement': [{'@type': 'Offer', 'itemOffered': {'@type': 'Service', 'name': s}} for s in services]},
         'image': [{'@id': jpg800(k) + '#image'} for k in imgs]},
        {'@type': 'GeneralContractor', '@id': SITE + '/#business', 'name': 'Fox & Fern Property Services', 'url': SITE + '/',
         'telephone': '+1-610-890-3537',
         'address': {'@type': 'PostalAddress', 'streetAddress': '1153 Main St, Ste 201', 'addressLocality': 'Linfield',
                     'addressRegion': 'PA', 'postalCode': '19468', 'addressCountry': 'US'}},
        {'@type': 'FAQPage', '@id': URL + '#faq', 'mainEntity': [
            {'@type': 'Question', 'name': q, 'acceptedAnswer': {'@type': 'Answer', 'text': a}} for q, a in FAQ]},
    ] + [image_object(k) for k in imgs]
    return json.dumps({'@context': 'https://schema.org', '@graph': graph}, ensure_ascii=False, indent=1).replace('</', '<\\/')


# ---------------------------------------------------------------- assemble
def main():
    tpl = (HERE / 'template-residential-remodeling.html').read_text(encoding='utf-8')
    head_assets = tpl[tpl.index('<link rel="preload" href="/assets/fonts'):tpl.index('</head>')]
    body = tpl[tpl.index('<body>'):]
    sprite = re.search(r'<svg width="0" height="0".*?</svg>', body, re.S).group(0)
    sprite = sprite[:sprite.rindex('</svg>')] + SPRITE_EXTRA + '\n</svg>'
    header = re.search(r'<header class="site-header".*?</header>', body, re.S).group(0)
    header = header.replace(' aria-current="page"', '')  # this page isn't in the menu
    footer = re.search(r'<footer class="footer">.*?</footer>', body, re.S).group(0)
    css = (HERE / 'town-page.css').read_text(encoding='utf-8')
    js = (HERE / 'town-page.js').read_text(encoding='utf-8')
    esc = lambda s: html.escape(s, quote=True)

    head = f'''<!doctype html>
<html lang="en-US">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{esc(TITLE)}</title>
<meta name="description" content="{esc(DESC)}">
<link rel="canonical" href="{URL}">
<meta name="robots" content="index, follow, max-image-preview:large">
<meta name="geo.region" content="US-PA">
<meta name="geo.placename" content="Trappe, Pennsylvania">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Fox &amp; Fern Property Services">
<meta property="og:locale" content="en_US">
<meta property="og:title" content="{esc(OG_TITLE)}">
<meta property="og:description" content="{esc(DESC)}">
<meta property="og:url" content="{URL}">
<meta property="og:image" content="{OG_IMAGE}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{esc(OG_ALT)}">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" type="image/png" href="/favicon-48x48.png" sizes="48x48">
<link rel="icon" type="image/png" href="/icon-192.png" sizes="192x192">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<script type="application/ld+json">
{jsonld()}
</script>
{head_assets}<style>
{css}</style>
</head>'''

    main_html = ''.join([hero(), why(), services(), local(), build(), pricing(), financing(), process(), reviews(), faq(), areas(), explore(), cta()])
    mbar = f'''<div class="mbar" id="mbar">
  <a class="btn btn-line" href="tel:{TEL}"><svg><use href="#i-phone"/></svg>Call</a>
  <a class="btn btn-gold" href="{ESTIMATE}">Free Estimate</a>
</div>'''
    page = f'''{head}
<body>
{sprite}


{header}

<main id="main">{main_html}
</main>

{footer}

{mbar}

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
