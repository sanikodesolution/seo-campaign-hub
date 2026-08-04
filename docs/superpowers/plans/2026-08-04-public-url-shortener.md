# Public URL Shortener Tool Implementation Plan

> **For agentic workers:** Implement task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a public no-login URL shortener at `/tools/url-shortener/`.

**Architecture:** `PublicShortenerService` registers rewrite + shortcode + REST create; reuses `ShortenerService` for storage/redirects. Rate limit + honeypot. Admin page mirrors Image → SVG.

**Tech Stack:** WordPress rewrites, REST API, vanilla JS/CSS, PHP 8.2+

## Global Constraints

- No WP login required for the public tool
- Destination URL only (auto slug)
- Configurable same-site-only + rate limit
- Guest links show in admin shortener list (`created_by = 0`)

---

### Task 1: Service + rewrite + REST + views

- [x] Create `PublicShortenerService`
- [x] Public page + widget views
- [x] Wire Plugin container + init

### Task 2: Assets + admin + settings + docs

- [x] Public CSS/JS
- [x] Admin page + Settings fields
- [x] Public badge on shortener list
- [x] Update readme.md / changelog / help
