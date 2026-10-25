# foxandfernservices.com: Performance & Local SEO Audit

**Date:** October 25, 2026 (site live about 24 hours)
**Market:** Montgomery County, PA (Linfield HQ), plus Philadelphia and Berks counties
**Pages reviewed:** all 10 URLs in `sitemap.xml`

---

## The short version

The website itself is in excellent shape. It's fast, technically clean and well structured, and it's already better built than most contractor sites in the county. What's missing is everything *around* the site: Google can't find it yet, nothing measures visitors or leads, and the off-site local signals (Google Business Profile, reviews, directory listings) that decide map-pack rankings in Montgomery County still need to be built.

| Area | Status | Notes |
|---|---|---|
| Speed (lab test) | **Strong** | Mobile 94–100, desktop 100 on every page |
| Technical SEO | **Strong** | Lighthouse SEO 100 on every page; clean canonicals, sitemaps and robots.txt |
| Local on-page signals | **Strong** | Consistent name/address/phone, full contractor schema, PA HIC # shown |
| Accessibility | **Strong** | 98–100 |
| Indexing by Google | **Not yet** | Expected at 24 hours, but needs Search Console to speed it up |
| Analytics / lead tracking | **Missing** | No analytics or conversion tracking installed |
| Service and town page depth | **Needs work** | Services and towns are sections on shared pages, not their own pages |
| Off-site local presence | **Needs work** | No directory listings found; no Facebook link; reviews hidden in an iframe |

---

## 1. Performance report

Google's PageSpeed Insights API was over its daily free quota, so I ran **Lighthouse 12.6.1** (the engine PageSpeed Insights uses) locally in Chromium. Mobile runs use Lighthouse's standard throttled phone profile.

| Page | Mobile perf | Desktop perf | Accessibility | Best practices | SEO | Mobile LCP | Mobile TBT | CLS |
|---|---|---|---|---|---|---|---|---|
| Home | 94 | 100 | 100 | 100 | 100 | 2.0 s | 250 ms | 0 |
| Residential remodeling | 99 | 100 | 100 | 100 | 100 | 1.8 s | 10 ms | 0 |
| Commercial renovations | 99 | 100 | 100 | 100 | 100 | 1.8 s | 0 ms | 0 |
| Repairs & maintenance | 99 | 100 | 100 | 100 | 100 | 1.8 s | 0 ms | 0 |
| Projects | 100 | 100 | 100 | 100 | 100 | 1.7 s | 0 ms | 0 |
| Service areas | 99 | 100 | 100 | 100 | 100 | 1.8 s | 0 ms | 0 |
| About | 99 | 100 | 100 | 100 | 100 | 2.0 s | 0 ms | 0 |
| Contact | 99 | 100 | 100 | 100 | 100 | 1.9 s | 0 ms | 0 |
| Blog | 100 | 100 | 100 | 100 | 100 | 1.5 s | 0 ms | 0 |
| Blog: cost to paint a house | 99 | 100 | 98 | 100 | 100 | 1.8 s | 0 ms | 0 |

All three Core Web Vitals are in Google's "good" range on every page: LCP under 2.5 s, CLS under 0.1, and low blocking time.

**What's working**
- Server response time is 170–190 ms. It's served from Hostinger's CDN with LiteSpeed page caching and Brotli compression (the homepage's 207 KB of HTML downloads as 55 KB).
- There's no third-party JavaScript at all: no external scripts, trackers or ad tags.
- Images come in AVIF/WebP/JPEG with set dimensions and lazy loading below the fold, so there's no layout shift.
- I rendered pages on a Pixel 7 profile and on desktop: no JavaScript console errors, no failed requests and no sideways scrolling on mobile.

**Small things to polish (none urgent)**
1. **The homepage animation costs 250 ms of blocking time on mobile.** The animated "whole-home renovation" drawing is behind about 2.8 s of main-thread work, mostly layout and paint. Starting it only when it scrolls into view, and skipping it for visitors with "reduce motion" turned on, would likely bring mobile up from 94 to 99–100.
2. **Some images are larger than they're displayed.** Serving correctly sized images would save about 177 KB on Projects and about 51 KB on the homepage on mobile.
3. **Cache lifetime.** Static assets are cached for 30 days. Files with a version hash in the name (`logo-ad66ad972d.webp`) can safely be cached for 1 year as `immutable`. `og-image.jpg` is cached for only 1 hour.
4. **Heavy page headers.** About 100 KB of inline CSS and JavaScript loads before any content in the homepage HTML, and the Projects page HTML is 323 KB. It isn't hurting scores today, but keep an eye on it as pages are added.

**Caveat:** these are lab numbers. Google ranks on *field* data from real Chrome users (CrUX), which a 1-day-old site doesn't have yet. Check PageSpeed Insights again in 4–8 weeks, once there's real traffic.

---

## 2. The two GitHub SEO tools in this repo

The repo has two root files, `seolocal` and `SEO RANK`, each with a one-step GitHub Action snippet. Neither has ever run, because they aren't in `.github/workflows/`. I pulled both tools and used them directly.

### local-seo-lint v0.2.0 (`clicksdynastyincali/local-seo-lint`): used ✅
This is a current, zero-dependency local SEO linter. It only reads files and sends nothing anywhere. I mirrored all 10 live pages plus `sitemap.xml` and `robots.txt` and ran it with your business details as the reference.

**Result: 0 real errors, 2 warnings.**
- It reported 46 "broken link" errors, but these are **false positives**: they're full-size gallery images that weren't in the HTML-only mirror. I checked all 851 image and font URLs referenced across the site on the live server, and every one returns `200 OK`.
- Warning: the Projects page schema names "Center City, Philadelphia" and "South Philadelphia," but neither name is visible on the page. Add the location to those project captions so the schema matches what visitors see.

**What passed** (and is genuinely good):
- Name, address and phone are identical on every page and in every schema block.
- Schema is valid JSON-LD with no syntax errors.
- No duplicate titles or meta descriptions.
- No two pages compete for the same keyword.
- No orphan pages (every page has links pointing to it).
- The sitemap matches the live pages exactly.
- One absolute canonical per page.
- AI search crawlers (ChatGPT, Perplexity, Claude, Bing, Apple) are allowed in `robots.txt`.
- Business schema includes `sameAs` profile links.

### seo-action v0.0.6 (`QiYuTechDev/seo-action`): recommend deleting ⚠️
This isn't an SEO auditor. It's a 2021 headless-browser runner that loads a URL and runs a JavaScript snippet; the default snippet scrapes Hacker News headlines. It was last updated in October 2021. It targets the `node12` Actions runtime, which GitHub has retired, and it installs MongoDB packages built for Ubuntu 20.04, so it won't run on today's runners. It also builds result-viewer links that put your **GitHub token in a URL on a third-party site** (`ci.2cc.net`).

**Recommendation:** delete the `SEO RANK` file and don't wire this action up. I did the job it was meant to do (render each page in a real browser, run a check script, take screenshots) with Playwright and Chromium instead. Those results are in the performance section above.

---

## 3. Local SEO for Montgomery County

### What's already right
- **Titles and H1s target the market.** Examples: "Linfield & Montgomery County General Contractor | Fox & Fern" (home) and "Kitchen, Bath and Basement Remodeling in Montgomery County, PA". Every title is under 70 characters and every meta description is under 160.
- **The business schema is thorough.** It uses `GeneralContractor` with full address, phone, hours, 10 towns plus 3 counties in `areaServed`, the PA HIC registration (#PA217947) as a credential, the legacy "Fox & Fern Painting Co." name as `alternateName`, and `BreadcrumbList` and `Service` schema on the inner pages.
- **The content is real and specific.** Service pages run 1,100–3,000 words, with process details, permits and HOA approvals, emergency first steps, seasonal maintenance and FAQs. That's far beyond the typical contractor template.
- **The trust signals are visible.** The PA HIC number, the husband-and-wife team with names, licensing and insurance, and financing details are all on the page.
- **The site is set up for AI search.** `llms.txt`, `sitemap.md` and Markdown copies of every page put it ahead of nearly every competitor for ChatGPT, Perplexity and Google AI answers.
- The 404 page correctly returns a 404 status with `noindex`. HTTP redirects to HTTPS, and URLs without a trailing slash redirect to the slash version.

### Not indexed yet
A search for `site:foxandfernservices.com` returns nothing yet, and I found no third-party listings for "Fox & Fern" in Linfield. Both are normal for a domain that's one day old, but both are where the work is now.

---

## 4. Prioritized action list

### Do this week (critical)
1. **Set up Google Search Console** as a *Domain* property verified through DNS. Submit `sitemap.xml` and `image-sitemap.xml`, then use URL Inspection → "Request indexing" on all 10 pages. I found no verification tag in the HTML (DNS verification wouldn't show one, which is fine). Then **set up Bing Webmaster Tools** by importing from Search Console. Bing's index powers Copilot and is one of the sources behind ChatGPT search.
2. **Install analytics and lead tracking.** No analytics are on the site today: no Google Analytics, Tag Manager or any other tool. Without it there's no way to report traffic or leads. Add Google Analytics 4 (or a lightweight privacy-friendly option) and track three conversions: `tel:` link taps, estimate-form submissions and the financing CTA.
3. **Test the estimate form end to end.** The form posts to `formsubmit.co`, which delivers to `dominic@foxandfernservices.com`. FormSubmit sends an activation email on the *first* submission, and nothing is delivered until someone clicks it. Send a test lead now and confirm it arrives and the mailbox is monitored. Your account email is on `foxandfernpainting.com`, so double-check that the `foxandfernservices.com` mailbox exists.
4. **Fix `www.foxandfernservices.com`.** It doesn't appear to resolve in DNS (two separate checks returned "not found"), so anyone who types `www.` gets an error page. Add a DNS record for `www` and 301-redirect it to `https://foxandfernservices.com/`.
5. **Clean up the WordPress user.** The admin display name is **"Claude Agent"**. It shows as the author on every post in the public RSS feed (`/feed/`). The public user endpoint (`/wp-json/wp/v2/users`) also exposes a username slug built from your Gmail login. Change the display name and nickname to "Dominic L." (or "Fox & Fern"), and block public user listing (most security plugins have a toggle). Disabling `xmlrpc.php` is good hygiene too.
6. **Get the Google Business Profile right.** It's the #1 factor for the map pack, and I can't see it from here.
   - Business name: exactly **Fox & Fern Property Services**, matching the site.
   - Primary category: *General contractor*. Secondary categories: *Remodeler*, *Bathroom remodeler*, *Kitchen remodeler*, *Painter*, *Handyman*, *Stucco contractor*, and *Commercial contractor* if offered.
   - Website: `https://foxandfernservices.com/`, ideally with a UTM tag so Analytics can separate it.
   - Service areas: the same towns as the site. Hours: the same as the schema (M–F 8–6, Sat 9–3).
   - Add project photos weekly and ask every completed customer for a review.
   - In the site schema, replace the `share.google/...` short link in `sameAs` with the profile's permanent Maps URL (`https://www.google.com/maps?cid=…`).

### Next 2–6 weeks (high impact)
7. **Give each major service its own page.** Right now kitchen, bath, basement, painting, stucco, roofing, siding, decks and concrete are subsections of three broad pages. People in Montgomery County search for "bathroom remodel Pottstown", "stucco repair Montgomery County" and "house painters Collegeville", and a page dedicated to that service will out-rank a subsection. Suggested order:
   1. **Interior & exterior painting**: the company's roots, and your only blog post (painting costs) currently links to a `#painting` anchor instead of a painting page.
   2. **Bathroom remodeling**
   3. **Kitchen remodeling**
   4. **Stucco repair & remediation**: a big need in southeastern PA's 1990s–2000s stucco homes.
   5. **Basement finishing**
   6. **Decks** and **concrete patios and driveways**
8. **Expand the core towns into real pages.** The service-areas page gives each town about 45 words. Build full pages for the 3–5 towns closest to Linfield, where you're most likely to rank: **Limerick/Linfield, Royersford, Pottstown and Collegeville/Trappe**. Each one needs content that only applies to that town: projects done there with photos, customer quotes from there, and notes on that township's permit office. Don't clone one template with the town name swapped; Google treats that as doorway pages. Google rankings are distance-based, so eastern towns like Bryn Mawr and Willow Grove are a longer-term play.
9. **Make reviews visible to Google.** The Housecall Pro reviews widget loads inside an iframe, and Google treats iframe content as belonging to Housecall Pro, not your page. Add 4–6 real customer quotes as plain text (first name, town, service), with a "Read more reviews on Google" link. Don't add review-star schema for your own reviews; Google ignores self-serving LocalBusiness reviews.
10. **Build directory listings (citations) with identical name, address and phone.** Bing Places, Apple Business Connect, a **Facebook business page** (none is linked anywhere on the site), BBB, Houzz, Angi/HomeAdvisor, Thumbtack, and local chambers such as the TriCounty Area Chamber of Commerce in Pottstown. Rename the Nextdoor page from "Fox & Fern Painting Co." to the new name. Then add each profile to the schema's `sameAs` list.

### Ongoing / medium
11. **Add a privacy policy page.** The estimate form collects name, phone, email, address and photos, and there's no privacy page (`/privacy/` returns a 404). It's a trust signal, and it's required if you run Google Ads.
12. **Blog regularly, with a named author.** Two locally specific posts a month is a good pace: remodeling permits in Limerick Township, bathroom remodel costs in Montgomery County, stucco inspection, and what to fix before listing a house in Royersford. Byline them to Dominic or Melissa with a short bio; named authors carry more weight than "the business."
13. **Small code fixes:**
    - Blog post: heading jumps from H1 to H3 ("Quick answer…"). Make it an H2. This is the only accessibility deduction on the site.
    - Blog index H1: the three `<span>`s have no spaces between them, so the plain text reads "Blog**Remodeling**…Advice**for**". Add a space between spans.
    - Projects: show "Center City" and "South Philadelphia" in the visible captions (see the local-seo-lint warning).
    - WordPress search results (`/?s=…`) return indexable 200 pages. Add `noindex` to search results.

---

## 5. What to expect

- **Indexing:** usually days to 2 weeks after Search Console submission.
- **Map pack (the 3 businesses on the map):** driven by the Google Business Profile's categories, reviews, distance and activity, not by the website alone. This is the fastest path to calls in western Montgomery County.
- **Organic rankings** for competitive terms like "general contractor Montgomery County PA" take months, and depend on dedicated service and town pages plus links and citations from other local sites. The current results are crowded with directories (HomeAdvisor, BBB, Houzz, BuildZoom) and established Pottstown and Royersford remodelers, which is beatable with the plan above.
- **Next check-in:** about 30 days after Search Console and analytics go live, re-run this audit with real impression, click and lead data, plus field Core Web Vitals.

---

### Method & limits
- Lighthouse 12.6.1 run from a cloud container through a network proxy. Absolute timings will differ from a real phone in Montgomery County, but the relative results are reliable.
- local-seo-lint v0.2.0 run against a mirror of the 10 sitemap URLs. Every "broken link" it reported was rechecked against the live site.
- I couldn't access: Google Search Console, Google Business Profile, Ahrefs backlink and keyword data (the connected Ahrefs plan didn't allow API access), or PageSpeed Insights field data (daily quota exhausted).
