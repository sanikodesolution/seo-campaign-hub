# Help & Support Page Design

## Goal

Replace the Help & Support admin placeholder with a self-service help page that guides users through the plugin and surfaces safe system diagnostics.

## Scope

- Quick start checklist with deep links into the plugin
- Feature guides for Campaigns, Offers, URL Shortener, QR Codes, and Analytics
- Copy-ready shortcode examples
- Elementor usage notes
- Troubleshooting for common failures (404s, empty analytics, permalinks)
- System status: WordPress/PHP/plugin versions, database tables, Elementor presence, rewrite prefix
- Quick links to Dashboard, Settings, Permalinks, and create screens

Out of scope: support ticket forms, email sending, remote license checks, and exposing secrets (paths with credentials, IPs, DB passwords).

## Architecture

`AdminInit::render_help()` gathers a safe diagnostics array and passes it to `src/Admin/Views/help.php`. The view is server-rendered HTML/CSS only, matching Shortener and Analytics admin pages.

## Verification

- Help page no longer shows “coming soon”
- All internal admin links resolve
- Diagnostics never print secrets
- PHP syntax check passes
