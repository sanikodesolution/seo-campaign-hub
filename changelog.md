# Changelog

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
