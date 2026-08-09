# Changelog

## 1.1.17 - 2026-08-09
- **Cloud Backup**: Download full site ZIP to your computer (no Google account required)
- Drive upload remains available when connected

## 1.1.16 - 2026-08-09
- **Cloud Backup Phase 2**: Full WordPress site backup (database + `wp-content`) to Google Drive
- Chunked AJAX progress UI; resumable Drive uploads; `full/` folder + retention
- Schedule can run full site or plugin JSON
- Design: `docs/superpowers/specs/2026-08-09-full-site-drive-backup-design.md`

## 1.1.15 - 2026-08-09
- **URL Replace**: live find→replace rules for any URL on the public site (change anytime)
- Optional serialized/JSON-safe database replace (posts, meta, options, comments, short links) with dry run
- Design: `docs/superpowers/specs/2026-08-09-url-replace-design.md`

## 1.1.14 - 2026-08-08
- **Front-end code optimize**: Clean WP head, minify HTML, defer footer JS (skip jQuery/Elementor), lazy-load media
- Settings → Advanced toggles now apply on the public site (not admin)
- Design: `docs/superpowers/specs/2026-08-08-frontend-code-optimize-design.md`

## 1.1.13 - 2026-08-08
- **SEO**: Block WordPress core directory listings (SimplePie `?SD` / “Duplicate without user-selected canonical”)
- robots.txt Disallow + Apache 403 for `/wp-includes/SimplePie/` and other library folders; Settings → SEO toggle (on by default)
- Design: `docs/superpowers/specs/2026-08-08-index-protection-design.md`

## 1.1.12 - 2026-08-08
- **Cloud Backup**: Sync Google Drive in a browser popup; keep Client Secret (password) + Sync both available
- Google cancel/deny on the consent screen returns a clear error and closes the popup
- Design: `docs/superpowers/specs/2026-08-08-google-drive-browser-sync-design.md`

## 1.1.11 - 2026-08-04
- **Delay AdSense load**: Header AdSense scripts inject after a configurable delay (default 2s)
- Public tools Above/Below ad slots lazy-load near the viewport when delay is enabled
- Settings → Header & Footer Scripts: enable toggle + delay seconds

## 1.1.9 - 2026-08-04
- Shared **Above/Below ad slots** on `/tools/image-to-svg/` and `/tools/url-shortener/`
- Settings → Public Tools: paste AdSense, affiliate, or company ad HTML
- Shortcode embeds stay ad-free
- Design: `docs/superpowers/specs/2026-08-04-public-tools-ad-slots-design.md`

## 1.1.8 - 2026-08-04
- **Public URL Shortener**: `/tools/url-shortener/` (no login), shortcode `[sch_url_shortener]`
- Destination URL only (auto slug); rate limit + honeypot; optional same-site destinations
- Guest links show in admin URL Shortener with a **Public** badge
- Design: `docs/superpowers/specs/2026-08-04-public-url-shortener-design.md`

## 1.1.6 - 2026-08-01
- **Web Push (OneSignal)**: soft subscribe prompt, auto-notify on post publish, manual send from Web Push page and post editor
- Serves `/OneSignalSDKWorker.js` from the plugin; App ID on front end; REST API Key server-only
- Design: `docs/superpowers/specs/2026-08-01-onesignal-web-push-design.md`

## 1.1.5 - 2026-08-01
- **Image Optimization** admin page: auto WebP on upload, bulk optimize with progress, front-end WebP serving (keeps JPEG/PNG originals)
- Uses Imagick or GD; no external API keys
- Design: `docs/superpowers/specs/2026-08-01-image-optimization-design.md`
- **Social Share** expansions: Blogger, Telegram, Quora, Reddit
- Dedicated **SEO Campaign Hub → Social Share** page + always-visible Share column on Posts → All Posts

## 1.1.4 - 2026-08-01
- Admin **Social Share** on Posts → All Posts: Facebook, X, LinkedIn, Pinterest, WhatsApp, Email, Copy link (browser share URLs — no App ID / OAuth)
- Settings → Social Share: enable toggle + network checklist
- Design/plan: `docs/superpowers/specs/2026-08-01-admin-social-share-design.md`

## 1.1.3 - 2026-07-29
- Settings → **Ads.txt (Google AdSense)**: enable and paste content served at `/ads.txt`
- **Cloud Backup** admin page: Google Drive OAuth, manual backup, daily/weekly schedule, retention
- Import/Export aligned with cloud backup (same “Everything” JSON payload; 5 MB import limit)
- Help screen: guides and troubleshooting for Localization, Ads.txt, Cloud Backup, and Import/Export
- Documentation: `readme.md` + Google Drive design spec

## 1.1.2 - 2026-07-22
- Prevent plugin boot failures from taking down the whole WordPress site
- Run DB schema upgrades only in admin/CLI (not on frontend)
- Harden autoloader for case-sensitive hosts / disabled glob()
- Defer analytics cookies until tracking runs
- Soft rate-limit (no wp_die white-screen)

## 1.1.1 - 2026-07-22
- Fix critical admin crash on hosts where JSON/ENUM ALTER or DDL transactions fail
- Safer migration runner (no site-wide fatal on schema upgrade)
- Use LONGTEXT/VARCHAR for targeting columns for broader MySQL/MariaDB support

## 1.1.0 - 2026-07-22
- Smart language / country redirects for short links
- Default Localization language pack (Europe, Americas, Middle East)
- Per-link redirect priority (language first or country first)
- Analytics language column + Traffic by language dashboard
- Settings → Localization section

## 1.0.0 - 2024-01-01
- Initial release
- Campaign management
- Offer management
- URL shortener
- Analytics tracking
- QR code generation
- Schema markup
- Admin dashboard
- REST API
- Security features
- Performance optimization
