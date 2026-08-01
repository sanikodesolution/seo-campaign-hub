# Changelog

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
