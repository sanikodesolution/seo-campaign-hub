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
- Import / export campaign data

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
| **Settings** | Configure SEO, analytics, shortener, QR, and performance options |
| **Import/Export** | Backup or move campaign/offer data |
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

### Advanced

- Caching
- Cache expiration
- Minify assets
- Defer JavaScript

Save settings after changing any options.

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
| `GET` / `POST` | `/qr-codes` | List / generate QR codes |
| `GET` | `/analytics` | Analytics data |
| `POST` | `/analytics/events` | Track an event (public) |
| `GET` | `/analytics/summary` | Analytics summary |

Campaigns and offers are also available through the WordPress REST API under:

- `/wp-json/wp/v2/sch-campaigns`
- `/wp-json/wp/v2/sch-offers`

---

## Import / Export

1. Go to **SEO Campaign Hub → Import/Export**
2. Export campaigns/offers for backup or migration
3. Import a previously exported file when moving between sites or restoring data

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

---

## Troubleshooting

| Problem | What to try |
|---------|-------------|
| Campaign / offer / short link URLs return 404 | Go to **Settings → Permalinks** and click **Save Changes** |
| Plugin pages look incomplete | Confirm you are logged in as an administrator |
| Short links not redirecting | Check that URL Shortener is enabled in Settings and the link is active |
| Analytics show no data | Confirm Analytics is enabled and that Ignore Bots is not filtering your test traffic |
| PHP / WordPress version notice | Upgrade to PHP 8.2+ and WordPress 6.0+ |

---

## Uninstall notes

Deactivating the plugin keeps your data. Fully deleting the plugin can remove plugin tables and options depending on uninstall settings. Export important campaigns first if you may need them later.

## Support

For support, visit [seocampaignhub.com](https://seocampaignhub.com) or contact the support team.

## Changelog

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
