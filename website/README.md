# Website updates: Trappe bathroom page, after-request "what to expect" page, 24-month financing and a menu fix for every page

New page: **https://foxandfernservices.com/bathroom-remodeling-trappe-pa/**

It targets "bathroom remodeling Trappe PA", the #2 keyword in the bathroom research. No competitor page was found for it. The page is **not in the menu** but is fully indexable and linked, so Google can find and rank it.

> **Status:** Fox & Fern Additions is installed and active on foxandfernservices.com (WordPress > Plugins). 1.0.1 went live Oct 27, 2026 (Trappe page and menu fix); 1.1.0 adds the `/thank-you/` page and 24-month financing.
>
> **How it's published:** your site's pages aren't files in Hostinger's `public_html`. They're served by your **Fox & Fern Site** WordPress plugin, with the **Fox & Fern Service Area Update** plugin layered on top. These changes go live the same way, as a small add-on plugin: **Fox & Fern Additions** (`wordpress/fox-fern-additions/`). Deactivating it in WordPress > Plugins undoes everything at once. Don't upload the files in `public_html/` to Hostinger: a real file there would bypass the site plugin for that URL.

## What the Fox & Fern Additions plugin does

| URL | Change |
|---|---|
| `/bathroom-remodeling-trappe-pa/` | **New page** (`public_html/bathroom-remodeling-trappe-pa/index.html`) |
| `/llm/pages/bathroom-remodeling-trappe-pa.md` | **New** plain-text version for ChatGPT, Perplexity and other AI search tools, same format as your other `/llm/` pages |
| `/images/projects/bathroom-before-after-king-of-prussia-og.jpg` | **New** 1200×630 share image (Facebook, texts, Google Discover) |
| `/residential-remodeling/` | One sentence added to the Bathroom Remodeling section, with a link to the new page |
| `/service-areas/` | One sentence added to the Collegeville card ("In neighboring Trappe…"), with a link to the new page |
| `/sitemap.xml` | Adds the page; updates `lastmod` for the two pages above |
| `/image-sitemap.xml` | Adds the page's photos |
| `/sitemap.md`, `/llm/topics/services.md` | Adds the page to your AI content map |
| `/thank-you/` | **New page** shown after the estimate request form: what to expect, the team, daily progress app, warranty, 24-month financing, licensing and insurance (below) |
| `/contact/` | After a successful send, the form continues to `/thank-you/`; financing text and the payment graphic say 24 months |
| `/`, `/residential-remodeling/`, `/about/`, the painting cost blog post, and their `/llm/pages/*.md` copies | Financing now reads **0% APR for up to 24 months** (was 12) |
| Every page with the site header (all pages, blog, blog posts, 404) | Menu fix for phones turned sideways (below) |

`public_html/` holds the finished copy of each changed file, for review. The plugin's edits are exact text insertions, built by `build/build_additions_plugin.py`, which checks that applying them to a fresh download of each live file reproduces these copies exactly. Each edit is applied only if its anchor text is found exactly once. If you later change one of these pages and the anchor no longer matches, that edit is skipped and the page shows as it is. An edit that inserts text is also skipped when the page already has that text, and the edits that add the Trappe link are skipped on a page that already mentions the Trappe page (for example, once the site plugin itself includes the link).

### The `/thank-you/` page

After someone sends the estimate request form on `/contact/`, the form still shows its "Request received" message, then about a second later continues to `/thank-you/`. If the send fails, people stay on the form and see the existing "We couldn't send that" message, so no request is lost behind a redirect. The form saves the first name, preferred contact method and property type in the browser tab (sessionStorage), so the page can say "Thanks, Dana! Melissa will reach out by text…". Nothing extra is sent anywhere. Commercial requests don't see the homeowner financing section.

What's on it: a confirmation and who will call (Melissa and Dominic, with their photos), the 5 estimate steps and a "helpful to have ready" checklist, what you can count on (itemized estimates, one project manager, written change orders, protected spaces, permits, OSHA 30 safety), the daily progress app, the workmanship warranty certificate and product sheet (term set in your contract), 0% APR for up to 24 months with Wisetack (a $15,000 project at $625.00/mo for 24 months, with the required disclosures), licensing and insurance (PA HIC #PA217947, workers' compensation, commercial auto, certificates of insurance), six real project photos, and a call/email box. It's `noindex` and not in the sitemaps.

**Tracking tip:** the site has no Google Analytics or Google Ads tag yet. Once one is added, a visit to `/thank-you/` is a clean "estimate request" conversion.

Built by `build/build_thank_you.py` (with `thank-you.css`, `thank-you.js` and the shared `site_shell.py`).

### Menu fix for phones turned sideways

On every page, when a phone is turned sideways, the open menu runs past the bottom of the screen. Because the header is fixed, the last link ("About") can't be reached. This was tested on the live pages on iPhone SE, 13 and 15 Pro Max and Pixel 5. The fix adds this CSS at the end of each page's main stylesheet. On short, sideways screens it shows the menu in two columns and lets it scroll if needed. Upright phones, tablets and desktop look exactly the same.

```css
/* header menu on phones turned sideways: two columns, and it scrolls if it still doesn't fit, so every link stays reachable */
@media (orientation: landscape) and (max-height: 500px) and (max-width: 960px) {
  .nav.open .menu { grid-template-columns: repeat(2, minmax(0, 1fr)); column-gap: 8px; max-height: calc(100vh - 110px); max-height: calc(100dvh - 110px); overflow-y: auto; overscroll-behavior: contain; }
  .nav.open .menu a { padding: 12px; }
  .nav.open .menu li + li { border-top: 0; }
  .nav.open .menu li:nth-child(n+3) { border-top: 1px solid rgba(243,239,232,.08); }
}
```

## After it's switched on

1. The plugin clears the LiteSpeed cache when it's activated, and again the first time the new page is visited, so edited pages show right away. If a page still looks old, use LiteSpeed Cache > **Purge All**.
2. Open the page on your phone and check that it looks right and the "Free Estimate" button works. Turn the phone sideways, open the menu, and check that you can see "About".
3. **Google Search Console:** URL Inspection > paste the page URL > **Request indexing**. Then go to Sitemaps and resubmit `sitemap.xml`.

To update the page later: edit the build script, run `python3 build/build_trappe_bathroom.py`, then `python3 build/build_additions_plugin.py <fresh live copies> <output folder>`, and upload the new zip over the old plugin.

## How it stays out of the menu but still ranks

- **Not in the menu:** the header menu and footer are identical to your other pages, and neither links to it.
- **Indexable:** `index, follow`, a self-referencing canonical URL, and listed in `sitemap.xml` and `image-sitemap.xml`.
- **Linked in-text:** a link from the Residential Remodeling and Service Areas pages tells Google the page matters and passes some of their ranking strength. Pages with no internal links (orphans) rarely rank.
- **AI search:** listed in `sitemap.md` (the content map your `llms.txt` points AI tools to), with a clean Markdown version.

## What's on the page

- **Hero:** three-line H1 "Bathroom Remodeling in Trappe, PA" in your site's style; key points (design with your own designer or one of our partners, 0% APR up to 24 months*, daily photo updates); a before/after drag slider of your real King of Prussia bathroom.
- **Why us:** five reasons, the same pillars layout as Residential.
- **Services:** full remodels, tub-to-shower and walk-in showers, tile showers, powder rooms, and vanities. Each has a real project photo labeled with its actual town and a planning guide.
- **Design:** "Use Your Own Designer or One of Our Partner Designers", with a card for each option and a sample design board (floor plan, tile, vanity and fixture-finish swatches). It also appears in the hero, the Why Us line, the sample estimate, process step 3 and a new FAQ.
- **Local know-how:** Trappe's older homes near Main Street vs. newer neighborhoods, a **Trappe Borough permit** section (verified from the borough's own permit applications), and an approvals checklist with stamps.
- **Animated bathroom build:** a 15-day drawing with the project-app phone mockup, in the same style as your kitchen animation.
- **Pricing:** the sample itemized bathroom estimate (interactive) and what moves the price. No starting price is shown anywhere on the page.
- **Financing:** a Wisetack payment calculator ($10,000 to $50,000, starting at $15,000; 0% for 24 months or 12.9% for 42 months, with the required example disclosures), offer cards (0% APR, Hometown Heroes 15%, free estimate) and your full Wisetack terms.
- **Process**, **reviews** (your Housecall Pro widget), **10 Trappe FAQs**, nearby towns, and a closing call to action.
- **Phones only:** a sticky Call / Free Estimate bar appears after the hero.
- **Phones turned sideways:** a slimmer header, a two-column menu that always fits, photos and the build drawing sized to fit the screen, and a small floating Call button in place of the full bottom bar.

**Search details**
- Title (46 characters): `Bathroom Remodeling in Trappe, PA | Fox & Fern`
- Description (149 characters): `Bathroom remodeling in Trappe, PA: tile showers, walk-in showers and vanities. Your designer or one of our partners. 0% APR financing. Free estimate.`
- Structured data: BreadcrumbList, WebPage, Service (area served: Trappe, design options, six service offers; no price), GeneralContractor, FAQPage and six ImageObjects. Validated against the schema.org vocabulary with 0 errors, and every FAQ answer in the markup matches the visible text.
- Open Graph and Twitter cards with the new share image.

## Live check after publishing (Oct 27, 2026)

- `/bathroom-remodeling-trappe-pa/`, its Markdown copy and the share image are served byte for byte as built. Title, canonical, `index, follow` and the share image tags all check out in a real browser on desktop and iPhone.
- The homepage, Residential Remodeling, Service Areas, the other pages, `sitemap.xml`, `image-sitemap.xml`, `sitemap.md` and `llm/topics/services.md` match the reviewed copies in `public_html/` exactly. The menu fix was confirmed on the live About page with an iPhone SE turned sideways.
- Deactivating the plugin returns every page to exactly what the site plugin serves (tested on a local copy of the site with both Fox & Fern plugins' behavior reproduced).

## Test results (local preview using live assets)

| Check | Result |
|---|---|
| Lighthouse mobile | Performance 97–98 · Accessibility 100 · Best Practices 100 · SEO 100 · CLS 0 · TBT 0 ms |
| Lighthouse desktop | 100 · 100 · 100 · 100 |
| Console errors | None (desktop and iPhone) |
| Horizontal scroll | None at 320, 360, 375, 390, 412, 768, 1024, 1280 and 1440 px |
| Menu fix, all 8 pages | Turned sideways (iPhone SE, 13, 15 Pro Max, Pixel 5): every menu link on screen, previously cut off; upright iPhone SE and 13: menu identical to before |
| Phones turned sideways | iPhone SE, 8, 8 Plus, 13 and 15 Pro Max; Pixel 5 and 7; Galaxy S9+ and S24; Moto G4: no sideways scroll, menu fits, photos and build drawing fit the screen |
| HTML validation | Same style-only notes as your live pages; no new issues |
| local-seo-lint (whole site) | 0 findings on the new page and the two edited pages |

The Housecall Pro reviews widget was blocked in the test environment only. It's the same widget your homepage loads.

## To help it rank faster

1. **Add a real Trappe project** when you have one: photos plus a sentence in the services section. It's the strongest local signal you can add.
2. Ask Trappe customers to mention the town in their **Google reviews**.
3. Post the page on your **Google Business Profile** as an update.
4. In Google Ads, the **Collegeville/Trappe** ad group can use this page too. The `bathroom-remodeling-estimate` landing page (not live yet) stays the main ad page once it's published.

## Building the next town page (Royersford, Limerick, Collegeville…)

`build/patch_landscape_menu.py` adds the menu fix to fresh downloads of the live pages (the copies in `public_html/`) and skips pages that already have it.

`build/build_trappe_bathroom.py` builds the page from `build/template-residential-remodeling.html` (a copy of the live page, so the header, footer and styles always match) plus `build/town-page.css` and `build/town-page.js`. Copy the script, change the constants and the town-specific copy (local section, permits, FAQ), and run `python3 build_<town>.py`. Each town page needs its own local facts and, ideally, a local project. Copying the same text with the town name swapped will be treated as a doorway page.
