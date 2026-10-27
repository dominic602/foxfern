# Bathroom Remodeling Landing Page: Google Ads Setup

The page is `index.html` in this folder. It's a single self-contained file. Images, fonts and the logo load from foxandfernservices.com, so it must live on that domain.

## 1. Put it live (about 10 minutes)

Upload `index.html` to Hostinger at `public_html/bathroom-remodeling-estimate/index.html`.
The page will be at **https://foxandfernservices.com/bathroom-remodeling-estimate/**.

* The page is `noindex`, so it never competes with your SEO pages in Google search.
* After uploading, open it on your phone and send yourself one test lead. **The first submission triggers a one-time "Activate Form" email from FormSubmit.** Click it, or leads won't be delivered. (You may already have done this for the contact page. It's the same address.)

## 2. Turn on conversion tracking (most important step)

Google Ads > Goals > Conversions > New conversion action > Website:

1. **"Bathroom lead form"**: category *Submit lead form*, count *One*, value $150 (or your real number). Turn on **Enhanced conversions for leads**.
2. **"Bathroom click to call"**: category *Phone call lead*, count *One*.

Then copy the tag ID (`AW-…`) and the two conversion labels into the `ADS` block near the top of the `<script>` in `index.html`:

```js
const ADS = {
  id: 'AW-123456789',
  leadLabel: 'AbCdEfGhIjK',
  callLabel: 'LmNoPqRsTuV',
  leadValue: 150
};
```

Also add a **call extension / call asset** with Google forwarding numbers so calls straight from the ad are tracked too.

Every lead email also includes the `gclid`, keyword, match type and campaign. Keep them: if you mark which leads turned into jobs, you can later upload them as offline conversions and let Google optimize for **booked jobs**, not just form fills.

## 3. Final URL and town matching

Final URL: `https://foxandfernservices.com/bathroom-remodeling-estimate/`

Final URL suffix (campaign level):
```
utm_source=google&utm_medium=cpc&utm_campaign={campaignid}&utm_term={keyword}&matchtype={matchtype}&device={device}&town={_town}
```

Per ad group, set the custom parameter `{_town}` (e.g. `Collegeville`, `King of Prussia`, `Pottstown`). The headline then reads **"Bathroom Remodeling in Collegeville, PA"**: the searcher's town, matching their ad. With no town set it says "Montgomery County".

## 4. Campaign structure

* **Search only.** Turn off Display Network and Search Partners.
* **Location:** *Presence: people in or regularly in* your target areas (not "interested in"). Start with a radius of about 15 miles around Linfield plus King of Prussia and the Main Line.
* **Ad groups by town:** Collegeville/Trappe · Pottstown · Limerick/Royersford/Linfield · King of Prussia · Main Line (Bryn Mawr/Ardmore) · General (Montgomery County / "near me").
* **Bidding:** start with *Maximize conversions*. After about 30 conversions, move to *Target CPA*.
* **Schedule:** run around the clock, but bid +20% during office hours (Mon–Fri 8–6, Sat 9–3), when calls get answered.

**Keywords** (phrase and exact match):
```
"bathroom remodel" / "bathroom remodeling" / "bathroom remodeler"
"bathroom renovation" / "bathroom contractor"
"tub to shower conversion" / "shower remodel" / "walk in shower installation"
"bathroom remodel near me" / "bathroom remodeling [town]"
```
**Negative keywords (add on day one):**
```
diy, ideas, pictures, photos, design software, cost calculator, jobs, salary, hiring,
home depot, lowes, ikea, rebath, bath fitter, west shore home, jacuzzi, mobile home, rv,
cheap, used, free (as a broad term on its own), rent, apartment, landlord, youtube, how to
```
Check the search terms report weekly and keep adding negatives.

## 5. Ad copy (Responsive Search Ad)

Pin **H1** to position 1. Leave the rest unpinned.

**Headlines (max 30 characters each)**
| # | Headline | Chars |
|---|---|---|
| H1 (pin) | Bathroom Remodel {LOCATION(City)} | dynamic, default "Bathroom Remodeling PA" |
| 2 | Free In-Home Bathroom Estimate | 30 |
| 3 | Fixed, Itemized Price | 21 |
| 4 | 0% APR for Up to 12 Months* | 27 |
| 5 | Licensed & Insured, PA HIC | 26 |
| 6 | Family-Owned in Linfield, PA | 28 |
| 7 | One Project Manager, Start-End | 30 |
| 8 | Daily Photo Updates | 19 |
| 9 | Tub-to-Shower Conversions | 25 |
| 10 | Custom Tile Showers & Vanities | 30 |
| 11 | No Surprise Change Orders | 25 |
| 12 | 15% Off for Hometown Heroes | 27 |
| 13 | Book Your Free Walkthrough | 26 |
| 14 | Montgomery County Remodelers | 28 |
| 15 | Get Your Price in Writing | 25 |

**Descriptions (max 90 characters)**
1. `Fixed, itemized price that only changes if you sign. One PM & daily photo updates.` (82)
2. `Licensed & insured, family-owned in Linfield. Free on-site estimate. Book in 45 sec.` (84)
3. `Tile showers, tub-to-shower conversions, vanities & more. 0% APR up to 12 mo. avail.*` (85)
4. `Waterproofing done right behind the tile. Warranty certificate at every closeout.` (81)

**Assets to add (they raise CTR a lot):**
* Sitelinks: Free Estimate · Financing · Our Process · Before & After (link to page anchors or your main site pages)
* Callouts: Free Estimates · Fixed Itemized Price · Licensed & Insured · Family-Owned · 0% APR Available* · Daily Photo Updates
* Structured snippet (Services): Tile Showers, Tub-to-Shower, Vanities, Walk-In Showers, Powder Rooms, Tile Floors
* Call asset with (610) 890-3537 · Location asset (link your Google Business Profile) · Image assets: use your real before/after photos
* Lead form asset: optional, test it later

## 6. Two things that would raise conversions further (need your input)

1. **Real review quotes on the page.** Add 3–4 short quotes from real customers, with first name and town, as plain text near the form. Right now reviews only show inside the Housecall Pro widget. I didn't write any quotes, because they need to be real.
2. **A real starting price** ("Most hall bath remodels start around $X"). People who see a range self-qualify and convert better. I left price out because the site has no published bathroom pricing. Give me a real number and I'll add it.
