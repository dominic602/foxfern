# Fox & Fern Customer Portal

A private, sign-in-only portal for Fox & Fern customers, built as a WordPress plugin for foxandfernservices.com. It runs on your existing Hostinger WordPress, so there's no new monthly service. Logins, data and photos all stay on your own hosting.

> **Status: not live.** This code is only in GitHub and has never been installed on foxandfernservices.com. Follow "Going live" below when you're ready.

## What customers get (`/portal/`)

- **Overview:** status, % complete, scope of work, schedule/timeline, latest update, project manager contact, open change requests, warranties.
- **Daily Updates:** your daily progress log (crew, hours, weather, work completed, next steps, % progress, inspections, issues…) with photos. The fields are fully customizable, like the columns of your Google Sheet.
- **Photos:** every photo by day, tagged Before / Progress / After, with a full-screen viewer that swipes on phones.
- **Selections & Specs:** every product used, where it went and what it was for: paint (brand, color name, code like SW 7029, sheen), flooring, tile, grout, fixtures, cabinets, hardware. Grouped by category or by room, with optional data-sheet or photo attachments. Downloadable as CSV.
- **Documents:** warranty certificates (provider, coverage dates, what's covered, with Active / Ends soon / Expired badges), plus contracts, change orders, permits, invoices, manuals and lien waivers.
- **Messages:** comments, questions and **change requests** with photo/PDF attachments. Change requests move through Submitted → Under review → Approved / Declined → Completed, with your written response (cost, schedule impact).
- **Home record:** one printable page (or Save as PDF) with every selection and warranty, for touch-ups and resale.
- Email notifications for new updates, documents, replies and change-request decisions. "New" badges show what changed since their last visit.

## What you and your crew get

- **Post a daily update from your phone:** fill in the fields and add photos straight from the camera. Photos are shrunk on the phone before uploading, so it's quick on job-site LTE.
- **Drafts:** uncheck "Share with the customer" to keep an update private until you're ready. **Staff-only fields** (like Internal notes) are never shown to customers.
- **Import your Google Sheet.** In Sheets use File > Download > CSV, then upload it on the project's Manage tab. Columns are matched to your fields by name, unknown columns can be added as new fields automatically, and re-importing skips rows already brought in.
- **Customize the daily update** under Settings: add, rename, reorder or remove fields. Field types are short text, paragraph, number, progress %, dropdown, yes/no and date.
- **Customers & logins:** invite a customer by name and email. They get an email to set their password, and you also get a link you can text them. You can resend invites, edit details, remove someone from a project, or turn off access.
- **Schedule:** add phases with target dates and mark them upcoming / in progress / done.
- Archive finished jobs, download the daily log or spec sheet as CSV, delete a project (typed confirmation required).

**Roles**
| Role | Can do |
|---|---|
| Administrator (you) | Everything, including Settings |
| Portal Staff | All projects: post updates, photos, specs, documents, reply, invite customers. No Settings, and can't touch admin or staff accounts |
| Portal Customer | Only their own projects: view everything shared, download files, send messages and change requests |

## How data is stored

- **Database:** seven tables in your WordPress database (`wp_ffp_projects`, `_members`, `_logs`, `_files`, `_specs`, `_comments`, `_milestones`). Logins are ordinary WordPress users with the roles above.
- **Files:** `wp-content/uploads/ffp-private/<project>/` under random file names. The folder blocks direct web access (`.htaccess`, which Hostinger's LiteSpeed server honors), and every file is served through `/portal/file/<id>/` only after checking that the signed-in person may see it. Photos are resized to 2400px (plus a thumbnail) to save space.
- Hostinger's regular backups cover both. Deactivating the plugin keeps all data.

## Security built in

- Sign-in lockout after 5 failed attempts (15 minutes). Password reset doesn't reveal whether an email has an account. Reset and invite links are single-use (invites last 7 days, resets 24 hours).
- Every form has a CSRF token. Every action re-checks permissions server-side, so customers can't reach other projects, staff screens, draft updates, internal notes or other customers' files.
- Customers are kept out of wp-admin and never see the admin bar.
- Uploads are checked by their real content, not just the file name (a web page renamed to `.jpg` is rejected). Only photos, PDF, Word, Excel, CSV and text files are allowed.
- Portal pages send `noindex` and `no-cache` headers, so Google won't index them and LiteSpeed/CDN won't cache them.
- CSV exports neutralize spreadsheet formulas.

## Going live (when you're ready)

1. **Back up the site** in Hostinger (hPanel > Websites > Backups).
2. **Raise PHP upload limits** in hPanel > Websites > Advanced > PHP Configuration > PHP options: `upload_max_filesize` 64M, `post_max_size` 128M, `max_file_uploads` 50.
3. **Install the plugin.** Zip the `fox-fern-portal` folder (or use the zip Claude provided), then in WordPress go to Plugins > Add New > Upload Plugin, choose the zip, and click **Activate**. If `/portal/` shows "not found," go to Settings > Permalinks and click **Save** once.
4. **LiteSpeed Cache:** under LiteSpeed Cache > Cache > Excludes > Do Not Cache URIs, add `/portal/`. The plugin already sends no-cache headers; this is a belt-and-braces step. If the Hostinger CDN is on, purge it afterwards.
5. **Email delivery:** install an SMTP plugin (for example WP Mail SMTP) connected to your Hostinger mailbox, so invites and updates land in inboxes instead of spam. Test by inviting yourself.
6. **Fix the WordPress display name.** Your admin user currently shows as **"Claude Agent"** (see the SEO audit), and that name would appear as "Your project manager" and "Posted by." Change it under Users > Profile > "Display name publicly as."
7. **Portal settings** (`/portal/settings/`): set the staff notification emails, and set the logo URL to your site logo, e.g. `https://foxandfernservices.com/assets/logo-ad66ad972d.webp`. Adjust the daily update fields to match your Google Sheet columns.
8. **Add a "Client Login" link** to your site header or footer pointing to `https://foxandfernservices.com/portal/login/`. The shortcode `[ffp_portal_link]` also works in any WordPress page or widget.
9. **Add staff** under WordPress Users > Add New with the role **Portal Staff**.
10. **Dry run:** create a test project, invite yourself with a personal email, then post an update with photos from your phone and check what the customer sees.

## Testing done before handoff

Run against a local WordPress 6.8.3 copy, never the live site, with a real browser (Chromium/Playwright) on desktop and iPhone/Pixel screen sizes. **53 end-to-end checks passed**, covering:
- Sign-in, lockout message, invites, set-password, forgot/reset password, single-use links and sign-out.
- Settings and custom fields, project creation, Google Sheets CSV import (including re-import and new columns), schedule, daily updates with photos, drafts, gallery, spec sheet with attachments, warranty and other documents.
- Messages, change request → approval → reply, CSV exports and the printable home record.
- Access control: customers blocked from other projects, staff screens, draft photos, internal CSVs and wp-admin; signed-out file requests redirected; staff blocked from editing, resetting or disabling admin accounts.
- Upload checks: a fake `.jpg` was rejected, and a 3 MB 12-megapixel phone photo uploaded through a 2 MB server limit thanks to in-browser shrinking.

Not testable locally: the `.htaccess` block on direct file URLs (the local test server ignores `.htaccess`), and real email delivery (emails were captured to a log instead). Check both during the dry run.

## Ideas for later

Text-message notifications, e-signatures on change orders, a punch-list checklist for the final walkthrough, schedule templates per job type, live two-way Google Sheets sync, and links to Housecall Pro / Wisetack for invoices and financing.
