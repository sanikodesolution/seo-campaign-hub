# Smart Language / Country Redirect Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let one short link redirect visitors to different WP post/page URLs by browser language and/or IP country, with a default EU/Americas/ME language pack.

**Architecture:** Store `redirect_priority` + `targeting_rules` JSON on `sch_links`. Resolve destination inside `ShortenerService::handle_redirect()`. Persist visitor `language` on analytics events and show a language breakdown on the Analytics dashboard.

**Tech Stack:** WordPress PHP 8.2+, existing `sch_links` / `sch_analytics` tables, admin-post forms, jQuery for rules UI rows.

## Global Constraints

- Do not build CPT campaign translation or GTranslate integration.
- Default languages: `en, es, pt, fr, de, it, nl, pl, ru, ar, he, tr, fa`.
- Per-link priority default: `language`.
- Existing links without rules keep current single-destination behavior.
- Prefer DB migration `1.1.0` so existing installs gain columns safely.

## File map

| File | Responsibility |
|------|----------------|
| `src/Database/Migrations/Version_1_1_0.php` | ALTER links + analytics columns |
| `src/Database/Database.php` | Bump `$db_version` to `1.1.0` |
| `src/Core/activator.php` | Schema for fresh installs + default localization options |
| `seo-campaign-hub.php` | Plugin version `1.1.0` |
| `src/Services/ShortenerService.php` | Rule sanitize + destination resolve on redirect |
| `src/Services/AnalyticsService.php` | Language detect/store + top languages query |
| `src/Admin/Settings.php` | Localization section + checkbox group |
| `src/Admin/Views/shortener.php` | Rules UI |
| `src/Admin/AdminInit.php` | Persist rules on create; pass languages to view |
| `src/Admin/Views/analytics.php` | Traffic by language table |
| `readme.md` / `changelog.md` | Docs |

---

### Task 1: Schema migration 1.1.0

**Files:**
- Create: `src/Database/Migrations/Version_1_1_0.php`
- Modify: `src/Database/Database.php`
- Modify: `src/Core/activator.php`
- Modify: `seo-campaign-hub.php`

- [ ] **Step 1:** Add migration that ALTERs `sch_links` (`redirect_priority`, `targeting_rules`) and `sch_analytics` (`language` + index), skipping columns that already exist.
- [ ] **Step 2:** Set `Database::$db_version = '1.1.0'` and update activator CREATE TABLE schemas + default options for localization.
- [ ] **Step 3:** Bump `SEO_CAMPAIGN_HUB_VERSION` / plugin header to `1.1.0`.

### Task 2: Shortener smart redirect engine

**Files:**
- Modify: `src/Services/ShortenerService.php`

- [ ] **Step 1:** Add `sanitize_targeting_rules(array $rules): array` and `resolve_destination($link, string $language, string $country): array{url, matched}` .
- [ ] **Step 2:** In `shorten_url()`, accept `redirect_priority` + `targeting_rules` and persist them.
- [ ] **Step 3:** In `handle_redirect()`, resolve smart destination when enabled; pass language/country/matched_rule_type into analytics track.

### Task 3: Analytics language support

**Files:**
- Modify: `src/Services/AnalyticsService.php`
- Modify: `src/Admin/Views/analytics.php`

- [ ] **Step 1:** Add `detect_visitor_language(): string` and public country helper reusing `geolocate()`.
- [ ] **Step 2:** Persist `language` in `track_event()` (payload or auto-detect).
- [ ] **Step 3:** Include `top_languages` in `get_dashboard_summary()` and render beside country table.

### Task 4: Admin UI + settings

**Files:**
- Modify: `src/Admin/Settings.php`
- Modify: `src/Admin/Views/shortener.php`
- Modify: `src/Admin/AdminInit.php`
- Modify: `assets/admin/js/admin.js` (or inline script in shortener view)

- [ ] **Step 1:** Add Localization settings section + fields (`enable_smart_redirects`, `default_redirect_priority`, `enabled_languages`, `default_fallback_language`).
- [ ] **Step 2:** Add smart rules UI on shortener create form; JS add/remove rows; toggle match control by type.
- [ ] **Step 3:** Parse/sanitize posted rules in `handle_shortener_create()` and pass enabled languages into the view.

### Task 5: Docs + verify

- [ ] **Step 1:** Update `readme.md` with smart redirect + localization docs.
- [ ] **Step 2:** Add `changelog.md` 1.1.0 entry.
- [ ] **Step 3:** PHP lint edited files with XAMPP PHP.
