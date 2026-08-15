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
- Set **SEO Title** and **Meta Description** per post/page/campaign/offer, plus homepage defaults
- Block WordPress core folder listings from Google (`/wp-includes/SimplePie/`)
- Optimize front-end code (clean WP head, minify HTML, defer JS)
- Replace any URL site-wide (live rules + database find/replace)
- Replace any text site-wide (live rules + database find/replace)
- Serve **Google AdSense `ads.txt`** from your site root (`/ads.txt`)
- Import / export campaign data
- Back up plugin data to **Google Drive** (Client Secret + browser popup Sync; manual + scheduled)
- Share published posts from **SEO Campaign Hub → Social Share** and **Posts → All Posts** (Facebook, X, LinkedIn, Pinterest, WhatsApp, Blogger, Telegram, Quora, Reddit, Email, Copy link — browser share, no App ID)
- Optimize images to **WebP** (keep originals; auto on upload + bulk tool)
- Convert **PNG/JPG → SVG** on a public tool page (no login; browser-only)
- Create **short URLs** on a public tool page (no login; rate-limited)
- Monetize public tools with shared **Above/Below ad slots** (AdSense, affiliate, or company HTML)
- **Web Push** via OneSignal (soft prompt, auto on publish, manual send)

## Requirements

- WordPress 6.0+
- PHP 8.2+
- Administrator capability (`manage_options`) for admin screens
- For **Image Optimization (WebP):** PHP **Imagick** with WebP support, or **GD** with `imagewebp` (no external API)
- For **Image → SVG tool:** modern browser with FileReader/Canvas (no server conversion)
- For **Public URL Shortener:** core URL Shortener enabled; visitors use REST create endpoint (rate-limited)
- For **Web Push:** HTTPS site + free [OneSignal](https://onesignal.com) App ID and REST API Key

## Installation

1. Upload the plugin folder to `/wp-content/plugins/seo-campaign-hub`
2. In WordPress admin, go to **Plugins → Installed Plugins**
3. Click **Activate** on **SEO Campaign Hub**
4. After activation, go to **Settings → Permalinks** and click **Save Changes** once (this flushes rewrite rules for campaigns, offers, `/go/` short links, and `/tools/image-to-svg/`)
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
| **Image to SVG** | Convert PNG/JPG to SVG (admin tool + public page, no login) |
| **Public Shortener** | Let visitors create short links (no login; rate-limited) |
| **Social Share** | Enable networks and share published posts from the Posts list (browser share — no App ID) |
| **Web Push** | OneSignal subscribe prompt, auto-notify on publish, manual send |
| **Settings** | Configure SEO, analytics, shortener, QR, ads.txt, public tools, localization, and performance options |
| **Import/Export** | Download or upload JSON backups (campaigns, offers, links, settings) |
| **Cloud Backup** | Connect Google Drive (password + browser popup Sync), run plugin backups, schedules, and retention |
| **URL Replace** | Replace any URL site-wide: live rules (anytime) + optional database rewrite |
| **Text Replace** | Replace any text site-wide: live rules (anytime) + optional database rewrite |
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

## How to use Image Optimization (WebP)

Create **WebP** versions next to your JPEG/PNG Media Library files. Originals are kept. Supporting browsers get WebP; others still get the original.

1. Go to **SEO Campaign Hub → Image Optimization**
2. Confirm **WebP supported** (engine: Imagick or GD). If not available, enable Imagick WebP or GD `imagewebp` on the server
3. Configure:
   - **Auto-optimize on upload** — create WebP when new JPEG/PNG images are uploaded (default: on)
   - **Serve WebP on front end** — swap URLs when the browser sends `Accept: image/webp` (default: on)
   - **WebP quality** — 60–90 (default: 82)
4. Click **Save settings**
5. For existing Media Library images, click **Bulk Optimize** and wait for the progress bar to finish

Status on the page shows JPEG/PNG totals, optimized count, pending count, and the last bulk run.

Design reference: `docs/superpowers/specs/2026-08-01-image-optimization-design.md`

---

## How to use Image → SVG (public, no login)

Convert **PNG / JPG / WebP** to **SVG** in the visitor’s browser. Files are not uploaded to WordPress.

1. Go to **SEO Campaign Hub → Image to SVG** (also enable under **Settings → Public Tools**)
2. Flush permalinks once: **Settings → Permalinks → Save Changes**
3. Open the public URL (or click **Open public tool**):

```text
https://yoursite.com/tools/image-to-svg/
```

4. Choose a mode:
   - **Wrap in SVG** (default) — embeds the image inside an SVG (best for photos/product shots)
   - **Vectorize** — posterizes into SVG shapes (best for simple logos/icons; not photos)
5. Download the `.svg`

Optional shortcode (same UI on any page/post):

```text
[sch_image_to_svg]
```

Limits: ~5 MB client-side; modern browser required.

Design reference: `docs/superpowers/specs/2026-08-04-image-to-svg-tool-design.md`

---

## How to use the Public URL Shortener (no login)

Let visitors create short links without a WordPress account. Slugs are auto-generated. Links use your normal `/go/{slug}` redirects and show up in **URL Shortener** with a **Public** badge.

1. Go to **SEO Campaign Hub → Public Shortener** (also under **Settings → Public Tools**)
2. Confirm the core **URL Shortener** is enabled
3. Optionally enable **Same-site destinations only** and set the **rate limit** (default: 10 creates / IP / hour)
4. Flush permalinks once: **Settings → Permalinks → Save Changes**
5. Open the public URL (or click **Open public tool**):

```text
https://yoursite.com/tools/url-shortener/
```

6. Paste a long URL → **Shorten URL** → copy the short link

Optional shortcode (same UI on any page/post):

```text
[sch_url_shortener]
```

Abuse protection: honeypot field + per-IP hourly rate limit. No captcha in v1.

Design reference: `docs/superpowers/specs/2026-08-04-public-url-shortener-design.md`

---

## How to monetize public tools (ads)

Both standalone tool pages (`/tools/image-to-svg/` and `/tools/url-shortener/`) share two ad slots: **above** and **below** the tool. Shortcode embeds stay ad-free.

1. Go to **SEO Campaign Hub → Settings → Public Tools**
2. Paste ad HTML into:
   - **Public tools ad — above**
   - **Public tools ad — below**
3. For Google AdSense, put the `adsbygoogle.js` loader in **Settings → Header & Footer Scripts → Header Scripts**, then paste each unit’s `<ins class="adsbygoogle">…</ins>` (and push) into the Above/Below fields
4. Leave a field empty to hide that slot
5. Open either public tool URL to verify

### Faster loading (recommended)

Under **Settings → Header & Footer Scripts**:

- Enable **Delay AdSense load** (default on)
- Set **AdSense delay (seconds)** (default: 2)

AdSense then loads after the delay (and tool ad slots hydrate near the viewport), so content paints first. Keep GA/Pixel in **Footer Scripts** when delay is on.

Use affiliate banners, company promo HTML, or any network markup the same way.

Design reference: `docs/superpowers/specs/2026-08-04-public-tools-ad-slots-design.md`

---

## How to use Social Share

Share published WordPress **posts** from the admin using browser share links. No App ID, OAuth, or connected accounts.

1. Go to **SEO Campaign Hub → Social Share**
2. Enable **Show share icons on Posts → All Posts**
3. Select networks: Facebook, X, LinkedIn, Pinterest, WhatsApp, Blogger, Telegram, Quora, Reddit, Email, Copy link
4. Save, then open **Posts → All Posts**
5. Use the **Share** column icons (and row actions under the title) on published, scheduled, or private posts

Drafts show a “Publish to share” hint. Icons open in a new tab; **Copy link** copies the permalink to the clipboard.

Also configurable under **Settings → Social Share**.

Design reference: `docs/superpowers/specs/2026-08-01-admin-social-share-design.md`

---

## How to use Web Push (OneSignal)

Send browser push notifications to visitors who subscribe on your site. Requires a free OneSignal account and HTTPS.

1. In [OneSignal](https://onesignal.com), create an app → **Settings → Push & In-App → Web** → choose **Custom Code**
2. Set **Site URL** to your exact HTTPS origin (e.g. `https://yoursite.com`)
3. Copy **App ID** and **REST API Key** (Keys & IDs)
4. In WordPress, go to **SEO Campaign Hub → Web Push**
5. Enable Web Push, paste credentials, configure soft prompt + auto-notify, save
6. Visit the front end — after the delay, visitors see “Get deal alerts”; Allow triggers the browser permission
7. Publish a post (auto push) or use **Send push now** on the Web Push page / post editor metabox

The plugin serves `https://yoursite.com/OneSignalSDKWorker.js` automatically. Per-post: check **Don’t notify on publish** to skip auto-send.

Design reference: `docs/superpowers/specs/2026-08-01-onesignal-web-push-design.md`

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
- **Homepage SEO Title** and **Homepage Meta Description**
- Per **post / page / campaign / offer**: SEO metabox (title + description). Leave blank to use the WordPress title / excerpt.
- **Block core directory listings** — stop Google indexing `/wp-includes/SimplePie/` folder listings (`?SD`, `?MD`, “Duplicate without user-selected canonical”). Writes robots.txt Disallow + Apache 403. Leave on unless you use Nginx (then also set `autoindex off`).
- Do not run another SEO title plugin (Yoast / Rank Math) at the same time.

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

- Dedicated page: **SEO Campaign Hub → Social Share** (primary)
- Also under **Settings → Social Share**
- Enable admin share icons on **Posts → All Posts** (Share column + row actions under the title)
- Choose networks: Facebook, X, LinkedIn, Pinterest, WhatsApp, Blogger, Telegram, Quora, Reddit, Email, Copy link
- Uses browser share pages — no App ID or connected accounts
- Available for published, scheduled, and private posts (drafts show “Publish to share”)

### Image Optimization

- Dedicated page: **SEO Campaign Hub → Image Optimization** (settings + bulk tool live here, not under Settings)
- Auto WebP on upload, quality, front-end WebP serving
- Bulk optimize existing Media Library JPEG/PNG

### Ads.txt (Google AdSense)

- **Enable ads.txt** — when on, the plugin serves your content at `https://yoursite.com/ads.txt`
- **ads.txt Content** — paste the lines from **Google AdSense → Sites → Ads.txt** (or your ad network’s authorized sellers list)
- After saving, open `/ads.txt` in the browser to confirm the file loads as plain text
- **Conflict note:** If your host already serves a physical `ads.txt` in the web root, or another plugin/theme manages `ads.txt`, use only one method. Disable this feature here (or remove the other file/plugin handler) so advertisers see a single, consistent file

### Advanced

- Caching / cache expiration (plugin data cache)
- **Clean WordPress head** — drop emoji, generator, extra feeds, oEmbed discovery
- **Minify HTML** — smaller front-end markup (`script`/`style`/`pre` kept intact)
- **Defer JavaScript** — footer scripts only; skips jQuery and Elementor
- **Lazy-load images and iframes**

These run on the public site only (not wp-admin). If a slider/layout breaks, turn off Defer or Minify HTML.

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

Back up your full WordPress site and/or plugin data to Google Drive.

### What you can back up

- **Full site** — database dump + `wp-content` (themes, plugins, uploads) as a ZIP → Drive `…/full/`
- **Plugin data** — SEO Campaign Hub JSON → Drive `…/plugin/`
- Chunked AJAX progress for full backups; resumable Google Drive uploads
- Manual backup + schedule (daily/weekly; choose full or plugin)
- Retention: keep the newest N backups per folder; older files are deleted automatically

### Still planned later

- Download from Drive and one-click restore flows

### Connect Google Drive

1. In [Google Cloud Console](https://console.cloud.google.com/), create or select a project
2. Enable the **Google Drive API**
3. Create **OAuth 2.0 Client ID** credentials (application type: **Web application**)
4. Under **Authorized redirect URIs**, add the exact **Authorized redirect URI** shown on **SEO Campaign Hub → Cloud Backup** (copy it from that page)
5. Copy the **Client ID** and **Client Secret** into **Cloud Backup → Cloud Storage — Google Drive** (Secret stays a password field)
6. Set **Parent folder on Drive** and **Site subfolder** (defaults work for most sites; use a unique subfolder per site if one Google account backs up multiple WordPress installs)
7. Click **Save cloud settings**
8. Click **Sync with Google Drive** — Google sign-in opens in a **browser popup** (allow popups if nothing appears). Approve access; the popup closes and this page shows **Synced**
9. When connected, use **Backup full site to Google Drive** (or plugin JSON), and optionally configure **Backup schedule**

OAuth uses the `drive.file` scope (files created by this app). Tokens are stored in WordPress options; only administrators (`manage_options`) can manage backups.

### Folder layout on Drive

| Level | Default name | Purpose |
|-------|----------------|--------|
| Parent folder | `seo-campaign-hub-backups` | Top-level folder in your Google Drive |
| Site subfolder | e.g. `yoursite_com` (from your domain) | Separates backups when one account serves multiple sites |
| Plugin folder | `plugin` | JSON plugin backups for that site |
| Full folder | `full` | Full site ZIP backups (database.sql + wp-content) |

**Example paths:**

```text
seo-campaign-hub-backups / yoursite_com / plugin / seo-campaign-hub-plugin-backup-2026-07-27-143052.json
seo-campaign-hub-backups / yoursite_com / full / seo-campaign-hub-full-2026-08-09-120000.zip
```

Plugin JSON files match **Import/Export** “Everything”. Full ZIPs contain `database.sql` plus a `wp-content/` tree.

### Run and schedule backups

- **Full site** — **Cloud Backup → Backup full site to Google Drive** (keep the tab open; progress bar runs until upload finishes)
- **Plugin JSON** — **Backup plugin data to Google Drive**
- **Schedule** — enable automatic backups, choose **Full WordPress site** or **Plugin data only**, **Daily** or **Weekly**, then **Save schedule**
- Schedules use **WordPress cron** (`seo_campaign_hub_cloud_backup_cron`), which runs when your site receives traffic; low-traffic sites may run late unless you use a real server cron or a cron manager plugin

### Disconnect

On **Cloud Backup**, when Google shows as connected, click **Disconnect** to remove stored tokens. Your Client ID, Secret, and folder names remain saved until you change them. Click **Sync with Google Drive** again anytime to resume uploads.

Design reference: `docs/superpowers/specs/2026-07-27-google-drive-backup-design.md` and `docs/superpowers/specs/2026-08-08-google-drive-browser-sync-design.md`

---

## URL Replace

Swap any URL across the site from **SEO Campaign Hub → URL Replace**.

1. **Live rules** — enter Find URL + Replace URL → **Add live rule**. The public site HTML updates immediately (posts, Elementor, menus). Turn off or delete the rule anytime. The database is not changed.
2. **Database replace** (optional, permanent) — same Find/Replace → **Dry run** to count rows → backup → check confirm → **Apply to database**. Updates post content, post meta (including Elementor JSON), options, comments, and short-link destinations (serialized-safe).

Find must be at least 8 characters. Do not use `javascript:` targets.

Design: `docs/superpowers/specs/2026-08-09-url-replace-design.md`

---

## Text Replace

Find and replace any text across the site from **SEO Campaign Hub → Text Replace**.

1. **Live rules** — enter Find text + Replace with → **Add live rule**. Visible HTML text updates immediately (between tags only). Turn off or delete the rule anytime. The database is not changed.
2. **Database replace** (optional, permanent) — same Find/Replace → **Dry run** to count rows → backup → check confirm → **Apply to database**. Updates post titles/content, post meta (including Elementor JSON), options, comments, and short-link targeting (serialized-safe).

Find must be at least 3 characters. HTML tags and `javascript:` targets are not allowed. Use unique phrases, not short common words. Match is case-sensitive (not regex). Leave Replace empty to remove the find text.

Design: `docs/superpowers/specs/2026-08-15-text-replace-design.md`

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

### Public URL shortener form

```text
[sch_url_shortener]
```

### Image → SVG converter

```text
[sch_image_to_svg]
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
| `POST` | `/public/shorten` | Create a short link (public tool; rate-limited) |
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
2. Set **Homepage SEO Title** and **Homepage Meta Description** (optional)
3. On each post/page/campaign/offer, fill the **SEO** metabox (title + description)
4. Publish optimized content, then promote with short links and track rankings / conversions

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

### Share a blog post from admin

1. Publish the post
2. Enable networks under **SEO Campaign Hub → Social Share**
3. On **Posts → All Posts**, use the Share column (or row actions) to open Facebook, X, LinkedIn, etc., or Copy link

### Optimize Media Library images to WebP

1. Confirm WebP support on **SEO Campaign Hub → Image Optimization**
2. Enable auto-optimize and front-end serving; set quality
3. Run **Bulk Optimize** for existing JPEG/PNG; new uploads convert automatically when auto-optimize is on

### Web Push to subscribers (OneSignal)

1. Configure App ID + REST API Key under **SEO Campaign Hub → Web Push**
2. Visitors subscribe via the soft prompt
3. Publish posts (auto) or use **Send push now** for deals

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
| GSC “Duplicate without user-selected canonical” on `/wp-includes/SimplePie/` | Enable **Settings → SEO → Block core directory listings**, visit wp-admin once (writes `.htaccess`), confirm a sample URL returns **403**, then re-validate in Search Console. On Nginx disable `autoindex`. |
| Layout or slider breaks after optimize | Disable **Defer JavaScript** or **Minify HTML** under **Settings → Advanced**. jQuery/Elementor are never deferred. |
| Wrong URL still showing after live replace | Hard-refresh / purge LiteSpeed or host cache. Confirm the live rule is **On** and the Find string matches the URL exactly (including https). |
| Wrong text still showing after live replace | Hard-refresh / purge cache. Confirm the live rule is **On** and Find matches exactly (case-sensitive). Live rules only change visible text between HTML tags, not attributes. |
| Google Drive backup fails | Confirm Drive API is enabled, redirect URI matches Cloud Backup exactly, allow popups, click **Sync with Google Drive**, and check **Last backup** on Cloud Backup |
| Scheduled cloud backup never runs | WordPress cron needs site visits — enable the schedule on Cloud Backup, verify **Save schedule** succeeded, and on quiet sites use server cron or a cron plugin to trigger `wp-cron.php` |
| Share icons missing on Posts list | Enable Social Share; confirm post type is **post** (not pages/campaigns); publish the post; confirm you can edit it |
| Soft prompt never appears | Enable Web Push + soft prompt; confirm App ID; use HTTPS; clear `sch_onesignal_prompt_dismissed` in browser localStorage; allow notifications not already granted |
| Push send fails | Confirm REST API Key, Web platform enabled in OneSignal, and that **All Subscribers** has people who opted in |
| WebP status shows “not available” | Install/enable Imagick with WebP or GD with `imagewebp` on the server |
| Bulk Optimize disabled or no progress | Need WebP engine support and pending JPEG/PNG attachments; leave the page open until the AJAX batches finish |
| Front end still serves JPEG/PNG | Enable **Serve WebP on front end**; confirm WebP files exist for that attachment; test in a WebP-capable browser |
| `/tools/image-to-svg/` returns 404 | Enable **Settings → Public Tools → Image → SVG**; then **Settings → Permalinks → Save Changes** |
| `/tools/url-shortener/` returns 404 or create fails | Enable **Public Shortener** + core **URL Shortener**; flush Permalinks; check rate limit / same-site setting |
| PHP / WordPress version notice | Upgrade to PHP 8.2+ and WordPress 6.0+ |

---

## Uninstall notes

Deactivating the plugin keeps your data. Fully deleting the plugin can remove plugin tables and options depending on uninstall settings. Export important campaigns first if you may need them later.

## Support

For support, visit [seocampaignhub.com](https://seocampaignhub.com) or contact the support team.

## Changelog

### 1.1.25 (2026-08-15)

- **Text Replace** admin page: live text rules (anytime) + dry-run/apply database replace
- Design: `docs/superpowers/specs/2026-08-15-text-replace-design.md`

### 1.1.24 (2026-08-13)

- SEO Title + Meta Description metabox (posts, pages, campaigns, offers) and homepage defaults in Settings → SEO
- Design: `docs/superpowers/specs/2026-08-13-seo-title-meta-design.md`

### 1.1.15 (2026-08-09)

- **URL Replace** admin page: live URL rules (anytime) + dry-run/apply database replace
- Design: `docs/superpowers/specs/2026-08-09-url-replace-design.md`

### 1.1.14 (2026-08-08)

- Front-end code optimize: clean WP head, minify HTML, defer footer JS (skip jQuery/Elementor)
- Settings → Advanced toggles now apply on the public site
- Design: `docs/superpowers/specs/2026-08-08-frontend-code-optimize-design.md`

### 1.1.13 (2026-08-08)

- Block WP core directory listings from Google (SimplePie / “Duplicate without user-selected canonical”)
- Settings → SEO: **Block core directory listings** (robots.txt + Apache 403)
- Design: `docs/superpowers/specs/2026-08-08-index-protection-design.md`

### 1.1.12 (2026-08-08)

- Cloud Backup: **Sync with Google Drive** opens Google OAuth in a browser popup; Client Secret (password) field kept
- Design: `docs/superpowers/specs/2026-08-08-google-drive-browser-sync-design.md`

### 1.1.11 (2026-08-04)

- **Delay AdSense load** (Settings → Header & Footer Scripts): inject ads after N seconds; lazy tool ad slots
- Keeps first paint faster while still showing ads

### 1.1.9 (2026-08-04)

- Shared **Above/Below ad slots** on both public tool pages (Settings → Public Tools)
- Shortcodes remain ad-free; empty slots render nothing
- Design: `docs/superpowers/specs/2026-08-04-public-tools-ad-slots-design.md`

### 1.1.8 (2026-08-04)

- Public **URL Shortener** tool at `/tools/url-shortener/` (no login; auto slug; rate limit + honeypot)
- Shortcode `[sch_url_shortener]`; guest links appear in URL Shortener with a **Public** badge
- Settings: same-site-only toggle + per-IP hourly rate limit
- Design: `docs/superpowers/specs/2026-08-04-public-url-shortener-design.md`

### 1.1.7 (2026-08-04)

- Public **Image → SVG** tool at `/tools/image-to-svg/` (no login; browser-only Wrap + Vectorize)
- Shortcode `[sch_image_to_svg]` and Settings → Public Tools toggle

### 1.1.6 (2026-08-01)

- Web Push via OneSignal: soft prompt, auto-notify on post publish, manual send
- Serves `/OneSignalSDKWorker.js`; design: `docs/superpowers/specs/2026-08-01-onesignal-web-push-design.md`

### 1.1.5 (2026-08-01)

- Image Optimization: WebP alongside originals, auto on upload, bulk tool, front-end WebP serving (Imagick/GD; no API keys)
- Design: `docs/superpowers/specs/2026-08-01-image-optimization-design.md`
- Social Share networks expanded: Blogger, Telegram, Quora, Reddit
- Social Share discoverability: dedicated admin page + Share column on Posts list

### 1.1.4 (2026-08-01)

- Admin Social Share on Posts list (browser share URLs; no App ID)
- Networks: Facebook, X, LinkedIn, Pinterest, WhatsApp, Email, Copy link
- Settings → Social Share enable + network checklist
- Design: `docs/superpowers/specs/2026-08-01-admin-social-share-design.md`

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
