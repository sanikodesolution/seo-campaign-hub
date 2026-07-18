# Analytics Dashboard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the Analytics admin section show real metrics by repairing event collection and adding a server-rendered dashboard.

**Architecture:** Keep `AnalyticsService` as the single write/query owner. Normalize frontend events to the existing `sch_analytics` schema, then render aggregates in `src/Admin/Views/analytics.php`.

**Tech Stack:** WordPress PHP 8.2+, jQuery, admin-ajax, existing `sch_analytics` table.

## Global Constraints

- Write only columns present in `Activator::get_table_schemas()` for `sch_analytics`.
- Allowed event types after normalize: `page_view`, `click`, `conversion`, `view`, `impression`, `scroll`, `time_on_page`, `bounce`, `exit`.
- Dashboard date presets only: 7, 30, 90 days.
- No new chart libraries or build tools.
- Do not expose IP, visitor ID, or user-agent on the dashboard.

---

### Task 1: Repair AnalyticsService tracking and summaries

**Files:**
- Modify: `src/Services/AnalyticsService.php`

**Interfaces:**
- Produces: `track_event(string $event_type, array $data = []): int|false`
- Produces: `get_dashboard_summary(int $days = 30): array`
- Produces: `is_enabled(): bool`

- [ ] **Step 1:** Normalize event types and map frontend payload aliases (`seconds`→`time_on_page`, `depth`→`scroll_depth`, `url`→`landing_page`).
- [ ] **Step 2:** Insert only schema columns; store missing IDs as `NULL`.
- [ ] **Step 3:** Unpack `batch` payloads in `ajax_track_event()`; rate-limit anonymous posts.
- [ ] **Step 4:** Replace broken footer batch tracker with a thin config bootstrap or remove duplicate tracking when `public.js` is active.
- [ ] **Step 5:** Add `get_dashboard_summary()` returning metric cards, daily trend, event breakdown, top campaigns/offers/links.
- [ ] **Step 6:** Allowlist `orderby`/`order` in `get_analytics()`.
- [ ] **Step 7:** Support standalone and nested analytics enable options; normalize retention option names.

### Task 2: Fix frontend tracking assets

**Files:**
- Modify: `assets/public/js/public.js`
- Modify: `src/Core/plugin.php`

- [ ] **Step 1:** Localize `seoCampaignHubPublic` with `ajaxUrl`, `nonce`, `tracking`, and current post attribution.
- [ ] **Step 2:** Add `jquery` dependency and load tracking on more than campaign/offer pages when analytics is enabled.
- [ ] **Step 3:** Fix click/scroll callback `this` binding and schema-compatible payload fields.

### Task 3: Build Analytics admin UI

**Files:**
- Create: `src/Admin/Views/analytics.php`
- Modify: `src/Admin/AdminInit.php`

- [ ] **Step 1:** Pass days + dashboard summary into the view from `render_analytics()`.
- [ ] **Step 2:** Render status, metric cards, daily bars, event breakdown, top lists, empty state.

### Task 4: Verify

- [ ] **Step 1:** PHP syntax-check edited files with XAMPP PHP.
- [ ] **Step 2:** Confirm Analytics page no longer shows “coming soon”.
- [ ] **Step 3:** Confirm tracking payload maps to schema columns.
