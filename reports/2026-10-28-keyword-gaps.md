# SEO keyword gaps: where foxandfernservices.com can't rank yet

Researched 2026-10-28 for Fox & Fern Property Services (Linfield, PA). Market: Google organic search, English (US), Montgomery County PA and the Route 422 towns.

**Bottom line:** the site is fast and technically clean, but it has **one page trying to rank for 11 different services** and **one page trying to rank for 10 towns**. Searchers look for one service in one place ("deck builder Montgomery County PA", "general contractor Limerick PA"). Those searches go to pages built for exactly that, and most of today's results are directories (HomeAdvisor, BBB, Angi, Thumbtack) and franchises with one page per town, not strong local contractors. That's the opening.

Jump to: [Data and limits](#keyword-research) · [Top weak spots](#top-weak-spots) · [Keyword map](#keyword-map) · [Content gaps in detail](#content-gap) · [Decisions and order of work](#keyword-decisions) · [How to measure](#measurement) · [Evidence](#evidence)

---

## Keyword research

### What data this is built on

| Source | Status | What it gave |
|---|---|---|
| Your live pages (11 canonical URLs) | ✅ Downloaded 2026-10-27, headings and words per section measured | What each page actually covers ([coverage evidence](keyword-research-2026-10-28/raw/coverage-evidence-2026-10-28.md), [page inventory](keyword-research-2026-10-28/raw/page-inventory-2026-10-28.json)) |
| Search results for 12 query families | ✅ Checked 2026-10-28 | Which kinds of pages show up and who you're up against ([observations](keyword-research-2026-10-28/raw/serp-observations-2026-10-28.md)) |
| Earlier keyword research (Oct 27, branch `landing-page-bathroom-remodeling`, `keywords/*.md`) | ✅ Reused | Query wording, town competition counts |
| **Search volumes** (Ahrefs, Google Keyword Planner, Google Trends) | ❌ **Unavailable** | Ahrefs answers "Insufficient plan" for API access; Google Trends is blocked from this environment; I don't have Keyword Planner access |
| **Your Search Console data** | ❌ Unavailable | The site is about 5 days old, and I don't have access |

**Important limits:**
- **No search volumes.** Priorities below come from how much your business makes per job, what's ranking, and how thin your current coverage is, not from monthly search counts. Before spending on any one page, check volumes in Google Keyword Planner with the location set to Montgomery County, PA. It's free with a Google Ads account.
- **Not Google's own results.** The result checks used a general US web index, not Google, with no map pack, ads or location. They show *which kinds of sites* cover a search, not exact rankings.
- **Nothing here is a ranking promise.** It identifies where the site has nothing (or almost nothing) to rank with.

The formal query list behind this report (20 queries in 18 need groups, each with its evidence file and SHA-256 hash) is [`keyword-research-2026-10-28/query-corpus.json`](keyword-research-2026-10-28/query-corpus.json). It passes the SEO suite's query-corpus validator.

---

## Top weak spots

Ranked by how much each one holds the site back, combining business value, current coverage and what's ranking.

| # | Weak spot | What's there now | Why it matters |
|---|---|---|---|
| 1 | **No kitchen, bathroom or basement page for the county** | A 103–134-word section each on Residential Remodeling | These are the three biggest remodeling categories. Results today are directories, franchises and thin cost pages, so a real local page can compete. |
| 2 | **Concrete offer with no page** | "Book Spring Concrete Now, save 20%" on the home and contact pages, and a 142-word section | You're paying attention to a seasonal offer (book by Feb 28) with nowhere for search traffic to land. Concrete results are directories and a *Maryland* firm. |
| 3 | **Town coverage is 29–59 words per town** | One Service Areas page for 10 towns | "Contractor + town" searches go to BBB, HomeAdvisor, and franchises with one page per town (Mr. Handyman, Deck Guardian). **Limerick, your home township, had no contractor page in the results at all.** |
| 4 | **No painting page, even though it's your roots** | A 174-word section; your only blog post links to `/residential-remodeling/#painting`; your Nextdoor profile is `fox-fern-painting-co` | Painting results belong to painting specialists with county and town pages (Heiler Painting, CertaPro King of Prussia). You need a real page to compete, and the blog post already gives it a link. |
| 5 | **Stucco is underused** | A 104-word section plus two stucco jobs in Projects | Stucco failure is a well-known southeastern PA problem. Results are directories (Angi even has a *Trappe* stucco page) and painter blogs. Your real projects are an advantage. |
| 6 | **Decks: 57 words** | A section | Deck searches go to specialist builders with town pages. Spring demand means a page is needed by about February. |
| 7 | **Roofing, siding and masonry: 56–72 words each** | Sections | Roofing is a crowded specialist field. Decide whether it's a service you want to grow before building a page (see decisions). |
| 8 | **Cost questions are answered by spam** | Only the painting cost post | "Kitchen/bathroom remodel cost Montgomery County" results include low-quality pages that mix up Montgomery County, Maryland. Honest cost sections on the new service pages can win these. |
| 9 | **Brand name differs across profiles** | Every page's footer links Nextdoor as "Fox & Fern **Painting Co**" | Google matches your business across profiles by name. Use "Fox & Fern Property Services" everywhere you can edit it. |
| 10 | **One blog post** | The painting cost guide | Fine for now. Add guides only where the service pages can't answer the question. |

---

## Keyword map

One main search per page. "Planned" URLs don't exist yet. Query IDs refer to [`query-corpus.json`](keyword-research-2026-10-28/query-corpus.json). Demand is **unavailable** for every row (see limits).

| Query family (ID) | Searcher wants | Owner page today | Target owner | Coverage | Track · priority |
|---|---|---|---|---|---|
| general contractor montgomery county pa (`q-gc-county`) | Hire a GC | `/` | `/` | Covered; directories dominate | **Head term** · now (authority work) |
| home remodeling montgomery county pa (`q-remodel-county`) | Hire a remodeler | `/residential-remodeling/` | Same (hub) | Covered | **Head term** · now (refocus hub) |
| kitchen remodeling montgomery county pa (`q-kitchen-county`) + cost question (`q-kitchen-cost`) | Hire a kitchen remodeler; budget | Section `#kitchen` (118 words) | `/kitchen-remodeling/` *(planned)*; cost as a section | **Gap** | **Head term** · now |
| bathroom remodeling montgomery county pa (`q-bath-county`) | Hire a bath remodeler | Section `#bathroom` (103 words) | `/bathroom-remodeling/` *(planned)* | **Gap** | **Head term** · next |
| bathroom remodeling trappe pa (`q-bath-trappe`) | Same, in Trappe | `/bathroom-remodeling-trappe-pa/` | Same | Covered (new) | Defend · now |
| bathroom remodeling royersford pa (`q-bath-royersford`) | Same, in Royersford | Service Areas (43 words) | `/bathroom-remodeling-royersford-pa/` *(planned)* | Gap | Near-term · later |
| basement finishing montgomery county pa (`q-basement-county`) | Finish a basement | Section `#basement` (134 words) | `/basement-finishing/` *(planned)* | **Gap** | Near-term · next |
| concrete driveway contractor montgomery county pa (`q-concrete-county`, `q-concrete-patio`) | Driveway, patio, walkway | Section `#concrete` (142 words) + offer | `/concrete-patios-driveways/` *(planned)* | **Gap** | Near-term · **now** (seasonal offer) |
| house painters montgomery county pa (`q-painting-county`) | Hire painters | Section `#painting` (174 words) | `/painting/` *(planned)* | **Gap** | Near-term · **now** |
| cost to paint a house philadelphia suburbs (`q-painting-cost`) | Budget for painting | Blog post | Same | Covered | Defend · now (fix its link) |
| stucco repair montgomery county pa (`q-stucco-county`) | Fix or remediate stucco | Section `#stucco` (104 words) + 2 projects | `/stucco-repair/` *(planned)* | **Gap** | Near-term · next |
| deck builder montgomery county pa (`q-deck-county`) | Build or repair a deck | Section `#decks` (57 words) | `/deck-building/` *(planned)* | **Gap** | Near-term · next (before spring) |
| roofing contractor pottstown pa (`q-roof-town`) | Replace a roof | Section `#roofing` (64 words) | **Your decision** | Gap | Owner decision |
| general contractor limerick pa (`q-gc-limerick`) | Local GC, home turf | Service Areas (48 + 44 words) | `/service-areas/limerick-pa/` *(planned)* | **Gap** | Near-term · **now** |
| home remodeling contractor pottstown pa (`q-remodel-pottstown`) | Local remodeler | Service Areas (51 words) | `/service-areas/pottstown-pa/` *(planned)* | Gap | Near-term · next |
| handyman royersford pa (`q-handyman-royersford`) | Local repairs | Repairs (county) + Service Areas (43 words) | `/service-areas/royersford-pa/` *(planned)* | Gap | Near-term · next |
| commercial contractor montgomery county pa (`q-commercial-county`) | Commercial build-out | `/commercial-renovations/` | Same | Covered | Later (watch data) |
| fox and fern property services (`q-brand`) | Find you by name | `/` | Same | Covered; name mismatch on Nextdoor | Now (owner task) |

Pages outside this keyword scope: `/projects/`, `/about/` and `/contact/` support trust and conversion and aren't search targets. `/blog/` is the index for future guides.

---

## Content gap

What each planned page needs to beat what's ranking. The common thread: **real projects, real local permit facts, and a clear price process**. Directories can't offer any of those.

### Service pages (kitchen, bathroom, basement, concrete, painting, stucco, decks)

- **One page per service, 1,200–2,500 words, built like the Trappe page:** what's included, your real project photos labeled with their town, permit notes, a "what drives the price" section, process, FAQs and financing.
- **Cost section on each page.** These searches are answered by thin, sometimes wrong pages today (some confuse Montgomery County, MD). You removed the "$5,000" starting price from the Trappe page, so decide per service whether to publish price ranges or keep to "what drives the price + itemized estimate". Either can rank; a range answers the question more directly.
- **Kitchen:** wall removal and permits, cabinets (refacing vs. replacement), countertops, design with your own designer or a partner designer, and the daily progress app.
- **Basement:** permits and egress windows for bedrooms (Montgomery Township's packet requires permits, per the Oct 27 research), moisture first, what a finished basement costs.
- **Concrete:** driveways, patios, walkways, removal and replacement, and the spring booking offer *with its dates*. Use your Projects "Driveway & Walkway Replacement" job.
- **Painting:** interior, exterior and cabinets, prep and lead-safe practices for older homes (your blog post already cites EPA RRP), and link the blog post to this page.
- **Stucco:** the difference between repair and full remediation, moisture testing, and your two stucco projects. This is the strongest *trust* topic you have.
- **Decks:** composite vs. wood, permits, repair vs. rebuild, staining.

### Town pages (Limerick/Linfield first, then Royersford, Pottstown, Collegeville)

- **Each needs its own facts:** township permit office, the kinds of homes there, projects you've done in or near the town, and drive time from Linfield. A copied template with the town name swapped is treated as a doorway page and can hurt the whole site.
- **Town hub pages** (`/service-areas/<town>-pa/`) cover "contractor / remodeling / handyman + town". **Service + town pages** like the Trappe bathroom page come later, and only where you have local proof.
- **Keep `/service-areas/` as the index** that links to each town page.

### Fixes to existing pages

- **Residential Remodeling:** once the kitchen, bathroom and basement pages exist, change its main heading from "Kitchen, Bath and Basement Remodeling…" to a general "Home Remodeling in Montgomery County, PA", and link each section to its full page with descriptive link text. Otherwise the hub competes with its own child pages.
- **Painting blog post:** point its `#painting` link at the new painting page.
- **Contact page and home offers:** link "Book Spring Concrete Now" to the concrete page.

---

## Keyword decisions

### Head-term leadership track (the big category searches)

These are the searches that define the category in your county. Today they're dominated by directories, so the realistic route is **dedicated pages plus local authority**: Google Business Profile, reviews, and consistent listings. The objective is **top 3**, with **#1** as the stretch goal. That's a target, not a forecast.

| Search | Owner | What it takes | Milestones to watch (Search Console) |
|---|---|---|---|
| general contractor montgomery county pa | Homepage | Reviews and Business Profile activity; consistent name on Nextdoor, BBB, Houzz and Angi; links from local organizations (chamber, Ursinus-area or township sponsorships) | Impressions → top 20 → top 10 → top 3 |
| home remodeling montgomery county pa | Residential Remodeling (hub) | Refocus the heading; link down to every service page | Same |
| kitchen remodeling montgomery county pa | `/kitchen-remodeling/` *(build now)* | Full service page with real kitchen projects (you have a Kitchen Remodel project card on the homepage) | Indexed → impressions → top 20 → top 10 → top 3 |
| bathroom remodeling montgomery county pa | `/bathroom-remodeling/` *(build next)* | County page that links to Trappe and future town pages; real King of Prussia, Lansdale, Philadelphia and Pottstown photos | Same |

### Nearer-term capture track

| When | Work | Why now |
|---|---|---|
| **Now** (next 2–4 weeks) | Concrete page, and point the offer at it | The offer runs until Feb 28 and spring planning starts in winter. Results are weak (directories and a Maryland firm). |
| **Now** | Painting page, and fix the blog link | Your roots, an existing internal link, and a cost guide already written |
| **Now** | Limerick/Linfield town hub | Your home township. No contractor page showed up for it at all. |
| **Now** | Kitchen page | Head-term owner (above) |
| **Now** | Use "Fox & Fern Property Services" on Nextdoor and every other profile | Free, 10 minutes, helps Google match your listings |
| **Next** (1–3 months) | Bathroom (county), basement, stucco and deck pages | Decks before spring; stucco is a trust builder |
| **Next** | Royersford, Pottstown and Collegeville town hubs | Your closest markets after Limerick |
| **Later** | More bathroom town pages (Royersford, Limerick, King of Prussia); separate cost guides if Search Console shows cost searches | After the county pages exist, and only with local proof |
| **Owner decision** | Roofing and siding page | It's a crowded specialist field. Build it only if roofing is a service you want to grow. |
| **Later** | Commercial sub-pages (tenant fit-outs, office renovations) | One strong commercial page is enough until data shows demand |

### Ideas I'm declining

- **A page for every keyword or every service × town combination.** Thin, duplicate pages risk Google treating them as doorway pages.
- **Ranking organically for "near me" searches.** Those are decided mostly by your Google Business Profile and distance; use the Profile and ads.
- **AMP.** It gives no ranking benefit (see the earlier discussion).

---

## Measurement

- **Baseline:** 4 weeks after each page goes live, export that page's queries from Search Console (clicks, impressions, CTR, average position), with country = US and device split.
- **Monthly:** track each head term through *indexed → impressions → top 20 → top 10 → top 3*. Average position in Search Console is an average across searches, so confirm milestones with a manual check in a private browser window set to the Linfield area.
- **Fill the volume gap:** run the query list through Keyword Planner (Montgomery County, PA). If one family turns out to have far more searches than expected, it moves up.
- **Redo this research** when Search Console has 28 days of data, when a planned page goes live, when you add or drop a service (for example roofing), or when a competitor launches town pages in your 422 towns.

---

## Evidence

- Result-set observations with representative URLs: [`raw/serp-observations-2026-10-28.md`](keyword-research-2026-10-28/raw/serp-observations-2026-10-28.md)
- Words per section, the painting link, the concrete offer, the Nextdoor name: [`raw/coverage-evidence-2026-10-28.md`](keyword-research-2026-10-28/raw/coverage-evidence-2026-10-28.md)
- Titles, headings, word counts and internal links for every page: [`raw/page-inventory-2026-10-28.json`](keyword-research-2026-10-28/raw/page-inventory-2026-10-28.json)
- Query list with provenance and coverage decisions: [`query-corpus.json`](keyword-research-2026-10-28/query-corpus.json)

**Observed vs. inferred.**
- **Observed:** words per section, which pages exist and link where, the active concrete offer, the Nextdoor URL, and which site types appeared in the 12 result checks.
- **Inferred:** "the result set is weak or beatable", priority order, and the expected value of each page. These are judgment, made without search volumes.

Owners: content and page builds by Fox & Fern (I can build them the same way as the Trappe page); profile names and reviews by the business owner.
