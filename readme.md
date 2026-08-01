# SEO Campaign Hub

Advanced SEO landing page builder with affiliate marketing, URL shortening, analytics, and campaign management for WordPress.

## Description

SEO Campaign Hub helps you create and manage SEO-focused landing campaigns, affiliate offers, short tracking links, QR codes, and performance analytics — all from one WordPress admin menu.

Use it to:

- Publish campaign landing pages (`/campaigns/your-campaign`)
- Manage affiliate / product offers (`/offers/your-offer`)
- Create tracked short links (default pattern: `/go/your-slug`)
- Generate QR codes for campaigns and links
- Track clicks, page views, and conversions
- Add schema markup and SEO meta automatically
- Serve **Google AdSense `ads.txt`** from your site root (`/ads.txt`)
- Import / export campaign data
- Back up plugin data to **Google Drive** (manual + scheduled; WPvivid-style cloud workflow)
- Share published posts to Facebook, X, LinkedIn, Pinterest, and more from **Posts → All Posts** (browser share — no App ID)
- Optimize images to **WebP** (keep originals; auto on upload + bulk tool)

## Requirements

- WordPress 6.0+
- PHP 8.2+
- Administrator capability (`manage_options`) for admin screens

## Installation

1. Upload the plugin folder to `/wp-content/plugins/seo-campaign-hub`
2. In WordPress admin, go to **Plugins → Installed Plugins**
3. Click **Activate** on **SEO Campaign Hub**
4. After activation, go to **Settings → Permalinks** and click **Save Changes** once (this flushes rewrite rules for campaigns, offers, and `/go/` short links)
5. Open **SEO Campaign Hub** in the left admin menu

## Quick start (recommended workflow)

Use **normal WordPress posts/pages** for SEO landing content. Use this plugin for short links, language/country routing, and analytics.

1. **Create SEO posts/pages** — one per language if needed (e.g. `/en/deal/`, `/ar/deal/`)
2. **Flush permalinks** — **Settings → Permalinks → Save Changes** (once)
3. **Configure Localization** — **SEO Campaign Hub → Settings → Localization**
4. **Create a short link** — Destination URL = your default WP post; add smart language/country rules
5. **Share** `/go/your-slug` and monitor **Analytics** (country + language)

Optional: use the built-in Campaigns/Offers CPTs if you want plugin-owned landing content instead of regular posts.

---

## Admin menu overview

After activation you will see **SEO Campaign Hub** in the WordPress admin sidebar:

| Menu item | What it does |
|-----------|----------------|
| **Dashboard** | Overview of published campaigns and offers |
| **Campaigns** | Create and manage campaign landing pages |
| **Offers** | Create and manage offers / affiliate destinations |
| **URL Shortener** | Create and manage short tracking links |
| **QR Codes** | Generate and manage QR codes |
| **Analytics** | View traffic, clicks, and conversion data |
| **Image Optimization** | Create WebP alongside JPEG/PNG; bulk optimize; serve WebP to supporting browsers |
| **Settings** | Configure SEO, analytics, shortener, QR, ads.txt, localization, and performance options |
| **Import/Export** | Download or upload JSON backups (campaigns, offers, links, settings) |
| **Cloud Backup** | Connect Google Drive, run plugin backups, schedules, and retention |
| **Help** | In-plugin help and support notes |

You can also reach the Dashboard and Settings from the plugin row on the **Plugins** page.

---

## How to create a campaign

Campaigns are stored as a custom post type (`sch_campaign`) and appear on the front end under `/campaigns/`.

1. Go to **SEO Campaign Hub → Campaigns**
2. Click **Add New**
3. Enter a title (this becomes the campaign name and default slug)
4. Write your landing page content in the editor
5. Optionally set:
   - Featured image
   - Excerpt
   - Campaign category
   - Campaign tags
6. Click **Publish**

**Front-end URL example:**

```text
https://yoursite.com/campaigns/summer-sale/
```

**Campaign archive:**

```text
https://yoursite.com/campaigns/
```

### Tip

Keep campaign titles clear and keyword-focused. The slug is used in the public URL, so edit it if you need a cleaner path (for example `summer-sale` instead of a long title).

---

## How to create an offer

Offers are destinations users click through to (affiliate links, product pages, signup URLs, etc.). They use the `sch_offer` post type and appear under `/offers/`.

1. Go to **SEO Campaign Hub → Offers**
2. Click **Add New**
3. Enter the offer title and content
4. Assign an offer category if needed
5. Publish the offer

**Front-end URL example:**

```text
https://yoursite.com/offers/premium-hosting-deal/
```

Connect offers to campaigns when building landing pages, then promote those pages with short links or QR codes.

---

## How to use the URL shortener

Short links redirect visitors to a destination URL and can carry UTM parameters for tracking.

Default format:

```text
https://yoursite.com/go/{slug}
```

### Create a short link

1. Go to **SEO Campaign Hub → URL Shortener**
2. Enter the destination URL (campaign page, offer URL, or external affiliate link)
3. Optionally set a custom slug (otherwise one is generated)
4. Optionally add UTM fields (`utm_source`, `utm_medium`, `utm_campaign`, etc.)
5. Save the link and share the short URL

### Configure shortener defaults

In **SEO Campaign Hub → Settings → URL Shortener Settings**:

- Enable / disable the shortener
- Set slug length (default: 6)
- Set URL prefix (default: `go`)
- Choose default redirect type in General Settings (`301`, `302`, or `307`)

### Smart language / country redirects

One short link can send visitors to different WordPress posts/pages based on browser language and/or IP country.

1. Create separate WP posts/pages per language (for example `/en/deal/`, `/ar/deal/`, `/es/deal/`)
2. Go to **URL Shortener** and set the default Destination URL (fallback)
3. Open **Smart redirect rules** and add language and/or country rules
4. Choose **Match priority** per link: Language first or Country first
5. Share the single `/go/{slug}` URL

Default language pack (Settings → Localization):  
`en`, `es`, `pt`, `fr`, `de`, `it`, `nl`, `pl`, `ru`, `ar`, `he`, `tr`, `fa`  
(Europe, Americas, Middle East coverage)

Matching order:

1. Primary type (language or country, based on link priority)
2. Secondary type
3. Fallback Destination URL

### Important

If `/go/your-slug` returns a 404, flush permalinks: **Settings → Permalinks → Save Changes**.

---

## How to generate QR codes

1. Go to **SEO Campaign Hub → QR Codes**
2. Create a QR code for a campaign, offer, or short link URL
3. Download and use it in print, packaging, or offline ads

Configure defaults under **Settings → QR Code Settings**:

- Default size (px)
- Format (`PNG`, `SVG`, `PDF`)
- Foreground and background colors

---

## How to use Analytics

1. Go to **SEO Campaign Hub → Analytics**
2. Review page views, clicks, and conversions for your campaigns and links
3. Review **Traffic by country** and **Traffic by language**

Tune tracking under **Settings → Analytics Settings**:

- Enable analytics
- Anonymize IP addresses (recommended for GDPR)
- Ignore bots / crawlers
- Track page views, clicks, and conversions
- Set data retention (30–365 days)

---

## Settings guide

Open **SEO Campaign Hub → Settings** and review these sections:

### General

- Enable or disable plugin functionality
- Default redirect type for links (`301` / `302` / `307`)

### SEO

- Schema markup
- Meta tags
- Open Graph tags for social sharing

### Analytics

- Tracking switches, bot filtering, and retention

### URL Shortener

- Prefix, slug length, enable/disable

### QR Codes

- Size, format, colors

### Localization

- Enable smart redirects
- Default match priority (language or country first)
- Enabled languages checklist (EU / Americas / Middle East pack)
- Default fallback language

### Social Share

- Enable admin share icons on **Posts → All Posts** (row actions under the title)
- Choose networks: Facebook, X, LinkedIn, Pinterest, WhatsApp, Email, Copy link
- Uses browser share pages — no App ID or connected accounts

### Ads.txt (Google AdSense)

- **Enable ads.txt** — when on, the plugin serves your content at `https://yoursite.com/ads.txt`
- **ads.txt Content** — paste the lines from **Google AdSense → Sites → Ads.txt** (or your ad network’s authorized sellers list)
- After saving, open `/ads.txt` in the browser to confirm the file loads as plain text
- **Conflict note:** If your host already serves a physical `ads.txt` in the web root, or another plugin/theme manages `ads.txt`, use only one method. Disable this feature here (or remove the other file/plugin handler) so advertisers see a single, consistent file

### Advanced

- Caching
- Cache expiration
- Minify assets
- Defer JavaScript

Save settings after changing any options.

---

## Google AdSense ads.txt

Use this when you want Google AdSense (or similar networks) to verify authorized sellers without uploading a file via FTP.

1. In **Google AdSense**, open **Sites** and select your domain
2. Open **Ads.txt** and copy the suggested content (one or more `google.com, pub-…` lines)
3. In WordPress, go to **SEO Campaign Hub → Settings → Ads.txt (Google AdSense)**
4. Check **Enable ads.txt**
5. Paste the content into **ads.txt Content**
6. Click **Save Settings**
7. Visit `https://yoursite.com/ads.txt` and confirm it matches what you pasted
8. Return to AdSense and complete verification (status may take a few hours)

If `/ads.txt` is empty, wrong, or a 404, confirm the feature is enabled and that no other `ads.txt` file or plugin is taking precedence. Flush permalinks once if you recently activated the plugin.

---

## Cloud Backup (Google Drive)

Back up plugin data to Google Drive (WPvivid-style workflow). **Phase 1** is available now; **Phase 2** (full site: database + `wp-content`) is planned for a later release.

### Phase 1 (current)

- Connect Google Drive with OAuth (Client ID + Secret)
- Parent folder + per-site subfolder + `plugin` folder for JSON files
- Manual **Backup now** and optional schedule (daily / weekly)
- Retention: keep the newest N plugin backups on Drive; older files are deleted automatically
- Last backup status shown on the Cloud Backup page

### Phase 2 (planned)

- Full WordPress site backup (database dump + `wp-content` archive)
- Chunked upload, optional separate DB vs files schedules
- Download from Drive and restore flows

### Connect Google Drive

1. In [Google Cloud Console](https://console.cloud.google.com/), create or select a project
2. Enable the **Google Drive API**
3. Create **OAuth 2.0 Client ID** credentials (application type: **Web application**)
4. Under **Authorized redirect URIs**, add the exact **Authorized redirect URI** shown on **SEO Campaign Hub → Cloud Backup** (copy it from that page)
5. Copy the **Client ID** and **Client Secret** into **Cloud Backup → Cloud Storage — Google Drive**
6. Set **Parent folder on Drive** and **Site subfolder** (defaults work for most sites; use a unique subfolder per site if one Google account backs up multiple WordPress installs)
7. Click **Save cloud settings**
8. Click **Authenticate with Google Drive** and approve access in Google
9. When connected, use **Backup plugin data to Google Drive** or configure **Backup schedule**

OAuth uses the `drive.file` scope (files created by this app). Tokens are stored in WordPress options; only administrators (`manage_options`) can manage backups.

### Folder layout on Drive

| Level | Default name | Purpose |
|-------|----------------|--------|
| Parent folder | `seo-campaign-hub-backups` | Top-level folder in your Google Drive |
| Site subfolder | e.g. `yoursite_com` (from your domain) | Separates backups when one account serves multiple sites |
| Plugin folder | `plugin` | JSON plugin backups for that site |

**Example path:**

```text
seo-campaign-hub-backups / yoursite_com / plugin / seo-campaign-hub-plugin-backup-2026-07-27-143052.json
```

Each backup file contains the same **Everything** JSON payload as **Import/Export** (campaigns, offers, short links, settings).

### Run and schedule backups

- **Backup now** — **Cloud Backup → Backup plugin data to Google Drive** (requires an active Google connection)
- **Schedule** — enable **Run automatic plugin backups**, choose **Daily** or **Weekly**, and click **Save schedule**
- Schedules use **WordPress cron** (`seo_campaign_hub_cloud_backup_cron`), which runs when your site receives traffic; low-traffic sites may run late unless you use a real server cron or a cron manager plugin

### Disconnect

On **Cloud Backup**, when Google shows as connected, click **Disconnect** to remove stored tokens. Your Client ID, Secret, and folder names remain saved until you change them. Re-authenticate anytime to resume uploads.

Design reference: `docs/superpowers/specs/2026-07-27-google-drive-backup-design.md`

---

## Shortcodes

You can embed plugin content in posts, pages, or landing templates.

### Single campaign

```text
[sch_campaign id="123"]
```

or

```text
[sch_campaign slug="summer-sale"]
```

### Single offer

```text
[sch_offer id="45"]
```

or

```text
[sch_offer slug="premium-hosting-deal"]
```

### Offer list

```text
[sch_offers campaign_id="123" limit="10" orderby="date" order="DESC"]
```

### Short link

```text
[sch_short_link url="https://yoursite.com/go/deal" target="_blank"]Shop the deal[/sch_short_link]
```

### QR code

```text
[sch_qr_code id="10" size="200"]
```

---

## REST API

Namespace: `seo-campaign-hub/v1`

Base URL example:

```text
https://yoursite.com/wp-json/seo-campaign-hub/v1/
```

Most endpoints require an authenticated WordPress user with permission to manage the plugin. The health check is public.

### Common endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `GET` | `/health` | Health check (public) |
| `GET` / `POST` | `/campaigns` | List / create campaigns |
| `GET` / `PUT` / `DELETE` | `/campaigns/{id}` | Read / update / delete a campaign |
| `GET` / `POST` | `/offers` | List / create offers |
| `GET` / `PUT` / `DELETE` | `/offers/{id}` | Read / update / delete an offer |
| `POST` | `/shorten` | Create a short link |
| `GET` | `/links` | List short links |
| `GET` / `DELETE` | `/links/{id}` | Read / delete a short link |
| `GET` / `POST` | `/qr-codes` | List / generate QR codes |
| `GET` / `DELETE` | `/qr-codes/{id}` | Read / delete a QR code |
| `GET` | `/analytics` | Analytics data |
| `POST` | `/analytics/events` | Track an event (public) |
| `GET` | `/analytics/summary` | Analytics summary |
| `GET` / `POST` | `/schemas` | List / create schema records |
| `GET` / `PUT` / `DELETE` | `/schemas/{id}` | Read / update / delete a schema |
| `GET` / `POST` | `/redirects` | List / create redirects |
| `GET` / `PUT` / `DELETE` | `/redirects/{id}` | Read / update / delete a redirect |
| `GET` / `PUT` | `/settings` | Read / update plugin settings |
| `GET` | `/export` | Export plugin data (JSON) |
| `POST` | `/import` | Import plugin data (JSON) |
| `GET` | `/search` | Search campaigns, offers, and links |
| `GET` | `/stats` | Dashboard-style stats summary |

Campaigns and offers are also available through the WordPress REST API under:

- `/wp-json/wp/v2/sch-campaigns`
- `/wp-json/wp/v2/sch-offers`

Cloud Backup (Google Drive OAuth and uploads) is admin-only and is **not** exposed via this REST namespace.

---

## Import / Export

1. Go to **SEO Campaign Hub → Import/Export**
2. Under **Export**, choose what to download, then click **Download JSON**
3. Under **Import**, upload a previously exported `.json` file when moving between sites or restoring data

### Export types

| Type | Contents |
|------|----------|
| **Everything** | Campaigns, offers, short links, and plugin settings |
| **Campaigns only** | Campaign CPT posts |
| **Offers only** | Offer CPT posts |
| **Short links only** | URL shortener records |
| **Analytics snapshot** | Analytics rows (**export only** — cannot be imported back) |
| **Settings only** | Allowlisted plugin options |

Campaigns and offers are exported from WordPress posts; short links come from the plugin database. On import, existing items are matched by slug (campaigns/offers) or slug/key (links). Choose **Update** or **Skip** when an item already exists.

### Limits and cloud backups

- Import files must be **JSON** and **5 MB or smaller**
- For hands-off backups and retention on Google Drive, use **[Cloud Backup (Google Drive)](#cloud-backup-google-drive)** (same **Everything** payload as manual export)

Always keep a full WordPress backup before large imports.

---

## Typical use cases

### Affiliate landing campaign

1. Create an **Offer** with your affiliate destination URL details
2. Create a **Campaign** landing page that explains the product and links to the offer
3. Create a **short link** with UTMs for each traffic source (`facebook`, `email`, `youtube`)
4. Share the short links; monitor results in **Analytics**

### Offline / print promotion

1. Create a campaign page
2. Create a short link pointing to that page
3. Generate a **QR code** for the short link
4. Place the QR code on flyers, packaging, or posters

### SEO landing pages

1. Enable Schema, Meta Tags, and Open Graph in **Settings → SEO**
2. Publish optimized campaign content
3. Categorize and tag campaigns for organization
4. Promote with short links and track rankings / conversions

### Multi-language / multi-country short link

1. Publish one WordPress post or page per language (or market)
2. Enable **Settings → Localization** and select languages
3. Create one short link with a default Destination URL
4. Add smart language and/or country rules; set match priority
5. Share a single `/go/{slug}` URL; review country + language in **Analytics**

### AdSense ads.txt verification

1. Copy ads.txt lines from Google AdSense
2. Paste them under **Settings → Ads.txt**, enable, and save
3. Confirm `https://yoursite.com/ads.txt` matches; finish verification in AdSense

---

## Troubleshooting

| Problem | What to try |
|---------|-------------|
| Campaign / offer / short link URLs return 404 | Go to **Settings → Permalinks** and click **Save Changes** |
| Plugin pages look incomplete | Confirm you are logged in as an administrator |
| Short links not redirecting | Check that URL Shortener is enabled in Settings and the link is active |
| Smart redirect goes to wrong page | Confirm Localization is enabled, link rules and match priority are correct, and fallback Destination URL is valid |
| Analytics show no data | Confirm Analytics is enabled and that Ignore Bots is not filtering your test traffic |
| `/ads.txt` wrong, empty, or 404 | Enable **Settings → Ads.txt**; remove conflicting physical `ads.txt` or disable another plugin that serves it; flush permalinks once |
| Google Drive backup fails | Confirm Drive API is enabled, redirect URI matches Cloud Backup exactly, Google is connected, and folder names are valid; check **Last backup** message on Cloud Backup |
| Scheduled cloud backup never runs | WordPress cron needs site visits — enable the schedule on Cloud Backup, verify **Save schedule** succeeded, and on quiet sites use server cron or a cron plugin to trigger `wp-cron.php` |
| PHP / WordPress version notice | Upgrade to PHP 8.2+ and WordPress 6.0+ |

---

## Uninstall notes

Deactivating the plugin keeps your data. Fully deleting the plugin can remove plugin tables and options depending on uninstall settings. Export important campaigns first if you may need them later.

## Support

For support, visit [seocampaignhub.com](https://seocampaignhub.com) or contact the support team.

## Changelog

### 1.1.5 (2026-08-01)

- Image Optimization: WebP alongside originals, auto on upload, bulk tool, front-end WebP serving

### 1.1.4 (2026-08-01)

- Admin Social Share on Posts list (row-action icons; browser share URLs; no App ID)
- Settings → Social Share enable + network checklist

### 1.1.3 (2026-07-29)

- Settings → **Ads.txt (Google AdSense)** (enable, paste content, serve at `/ads.txt`)
- **Cloud Backup**: Google Drive OAuth, manual backup, daily/weekly schedule, retention
- Help screen coverage for Localization, Ads.txt, Cloud Backup, and Import/Export
- Import/Export docs aligned (export types, 5 MB limit); design spec at `docs/superpowers/specs/2026-07-27-google-drive-backup-design.md`
- REST API docs expanded (links, QR, schemas, redirects, settings, import/export, search, stats)

### 1.1.2

- Fix WordPress not loading after plugin install (safer boot + admin-only migrations)

### 1.1.1

- Fix critical error on admin dashboard during schema upgrade on some hosts

### 1.1.0

- Smart language / country redirects for short links
- Default Localization language pack (Europe, Americas, Middle East)
- Per-link redirect priority (language or country first)
- Analytics language tracking and Traffic by language dashboard

### 1.0.0

- Initial release
- Campaign management
- Offer management
- URL shortener
- Analytics tracking
- QR code generation
- Schema markup
- Admin dashboard
- REST API
