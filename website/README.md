# Website updates: Bathroom Remodeling in Trappe, PA

New page: **https://foxandfernservices.com/bathroom-remodeling-trappe-pa/**

It targets "bathroom remodeling Trappe PA", the #2 keyword in the bathroom research. No competitor page was found for it. The page is **not in the menu** but is fully indexable and linked, so Google can find and rank it.

> **Status: not uploaded yet.** Everything below is ready to upload to Hostinger. Nothing on the live site has changed.

## What's in `public_html/`

The folders mirror Hostinger's `public_html`, so each file goes to the same path on the server.

| File | What it is | New or changed |
|---|---|---|
| `bathroom-remodeling-trappe-pa/index.html` | The page | **New** |
| `images/projects/bathroom-before-after-king-of-prussia-og.jpg` | 1200×630 share image (Facebook, texts, Google Discover) | **New** |
| `llm/pages/bathroom-remodeling-trappe-pa.md` | Plain-text version for ChatGPT, Perplexity and other AI search engines, same format as your other `/llm/` pages | **New** |
| `sitemap.xml` | Adds the page; updates `lastmod` for the two pages below | Changed |
| `image-sitemap.xml` | Adds the page's photos | Changed |
| `sitemap.md`, `llm/topics/services.md` | Adds the page to your AI content map | Changed |
| `residential-remodeling/index.html` | **One sentence added** to the Bathroom Remodeling section, with a link to the new page | Changed |
| `service-areas/index.html` | **One sentence added** to the Collegeville card ("In neighboring Trappe…"), with a link to the new page | Changed |

The two changed pages are copies of the live pages downloaded on Oct 27, 2026 with one sentence added. **If you've edited either page since then, don't overwrite it.** Instead, paste the sentence into the live file:

- Residential Remodeling, end of the bathroom paragraph:
  `Remodeling in Trappe? See <a class="inline" href="/bathroom-remodeling-trappe-pa/">bathroom remodeling in Trappe, PA</a>.`
- Service Areas, end of the Collegeville description:
  `In neighboring Trappe, see our <a class="inline" href="/bathroom-remodeling-trappe-pa/">bathroom remodeling in Trappe</a>.`

## Upload steps (about 10 minutes)

1. Hostinger hPanel > Websites > **File Manager** > `public_html`.
2. Create the folder `bathroom-remodeling-trappe-pa` and upload its `index.html` into it.
3. Upload the share image into `public_html/images/projects/`.
4. Upload `llm/pages/bathroom-remodeling-trappe-pa.md` into `public_html/llm/pages/`.
5. Replace `sitemap.xml`, `image-sitemap.xml`, `sitemap.md` and `llm/topics/services.md`.
6. Replace (or paste the sentence into) `residential-remodeling/index.html` and `service-areas/index.html`.
7. **Purge the cache:** LiteSpeed Cache > Purge All, and the Hostinger CDN if it's on.
8. Open the page on your phone and check that it looks right and the "Free Estimate" button works.
9. **Google Search Console:** URL Inspection > paste the page URL > **Request indexing**. Then Sitemaps > resubmit `sitemap.xml`.

## How it stays out of the menu but still ranks

- **Not in the menu:** the header menu and footer are identical to your other pages, and neither links to it.
- **Indexable:** `index, follow`, a self-referencing canonical URL, and listed in `sitemap.xml` and `image-sitemap.xml`.
- **Linked in-text:** a link from the Residential Remodeling and Service Areas pages tells Google the page matters and passes some of their ranking strength. Pages with no internal links (orphans) rarely rank.
- **AI search:** listed in `sitemap.md` (the content map your `llms.txt` points AI tools to), with a clean Markdown version.

## What's on the page

- **Hero:** three-line H1 "Bathroom Remodeling in Trappe, PA" in your site's style; key numbers (from $5,000, 0% APR up to 12 months*, daily photo updates); a before/after drag slider of your real King of Prussia bathroom.
- **Why us:** five reasons, the same pillars layout as Residential.
- **Services:** full remodels, tub-to-shower and walk-in showers, tile showers, powder rooms, and vanities. Each has a real project photo labeled with its actual town and a planning guide.
- **Local know-how:** Trappe's older homes near Main Street vs. newer neighborhoods, a **Trappe Borough permit** section (verified from the borough's own permit applications), and an approvals checklist with stamps.
- **Animated bathroom build:** a 15-day drawing with the project-app phone mockup, in the same style as your kitchen animation.
- **Pricing:** the sample itemized bathroom estimate (interactive), what moves the price, and a comparison note against other local published prices.
- **Financing:** a Wisetack payment calculator (0% for 12 months or 12.9% for 42 months, with the required example disclosures), offer cards (0% APR, Hometown Heroes 15%, free estimate) and your full Wisetack terms.
- **Process**, **reviews** (your Housecall Pro widget), **9 Trappe FAQs**, nearby towns, and a closing call to action.
- **Phones only:** a sticky Call / Free Estimate bar appears after the hero.

**Search details**
- Title (58 characters): `Bathroom Remodeling in Trappe, PA from $5,000 | Fox & Fern`
- Description (149 characters): `Bathroom remodeling in Trappe, PA from $5,000: tile showers, tub-to-shower conversions and vanities. 0% APR financing up to 12 months. Free estimate.`
- Structured data: BreadcrumbList, WebPage, Service (area served: Trappe, price from $5,000, six service offers), GeneralContractor, FAQPage and six ImageObjects. Validated against the schema.org vocabulary with 0 errors, and every FAQ answer in the markup matches the visible text.
- Open Graph and Twitter cards with the new share image.

## Test results (local preview using live assets)

| Check | Result |
|---|---|
| Lighthouse mobile | Performance 97–98 · Accessibility 100 · Best Practices 100 · SEO 100 · CLS 0 · TBT 0 ms |
| Lighthouse desktop | 100 · 100 · 100 · 100 |
| Console errors | None (desktop and iPhone) |
| Horizontal scroll | None at 320, 360, 375, 390, 412, 768, 1024 and 1440 px |
| HTML validation | Same style-only notes as your live pages; no new issues |
| local-seo-lint (whole site) | 0 findings on the new page and the two edited pages |

The Housecall Pro reviews widget was blocked in the test environment only. It's the same widget your homepage loads.

## To help it rank faster

1. **Add a real Trappe project** when you have one: photos plus a sentence in the services section. It's the strongest local signal you can add.
2. Ask Trappe customers to mention the town in their **Google reviews**.
3. Post the page on your **Google Business Profile** as an update.
4. In Google Ads, the **Collegeville/Trappe** ad group can use this page too. The `bathroom-remodeling-estimate` landing page stays the main ad page.

## Building the next town page (Royersford, Limerick, Collegeville…)

`build/build_trappe_bathroom.py` builds the page from `build/template-residential-remodeling.html` (a copy of the live page, so the header, footer and styles always match) plus `build/town-page.css` and `build/town-page.js`. Copy the script, change the constants and the town-specific copy (local section, permits, FAQ), and run `python3 build_<town>.py`. Each town page needs its own local facts and, ideally, a local project. Copying the same text with the town name swapped will be treated as a doorway page.
