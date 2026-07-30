# Admin UI Redesign Design

**Status:** Approved for planning (awaiting user review of this written spec)  
**Date:** 2026-07-30  
**Plugin:** SEO Campaign Hub  
**Direction:** Modern WordPress-native · Full admin redesign · All admin pages

## Goals

Replace the uneven, placeholder-style admin UI with one consistent WordPress-native design system across every SEO Campaign Hub admin screen. Improve daily ops usability on the Dashboard and clean up layout/UX on Shortener, Analytics, Settings, Import/Export, Cloud Backup, and Help—without inventing new product features beyond data already available.

## Decisions (locked)

| Decision | Choice |
|----------|--------|
| Scope | All admin pages |
| Visual style | Modern WordPress-native |
| Depth | Full redesign (visual + UX + page-specific widgets/actions) |
| Shell layout | **A — Header + cards** (plugin header, in-plugin tabs, KPI/content panels) |
| Dashboard model | **Ops** (KPIs, quick actions, setup checklist, recent links) |
| CPT screens | Wrap Campaigns/Offers list tables with shared shell only; keep core WP list UI |

## Non-goals

- Custom left secondary nav inside the plugin (rejected layout B)
- SaaS / dark / purple / heavy-glow chrome
- New backend product features (e.g. new analytics engines, new backup types)
- Replacing WordPress Campaign/Offer list tables with custom grids
- Public front-end theme redesign

## Shared admin shell

Every plugin-owned page uses:

1. **Page background** — soft WP admin gray (`#f0f0f1`)
2. **Plugin header** — title, one-line description, page-specific primary + secondary actions
3. **In-plugin tab bar** — links to all hub pages; current tab uses WP blue (`#2271b1`)
4. **Content area** — white panels with `#c3c4c7` borders, light radius, consistent spacing
5. **Shared components** — KPI cards, panels, status badges, notices, empty states, form tables

### Tab order

Dashboard → Campaigns → Offers → URL Shortener → QR Codes → Analytics → Settings → Import/Export → Cloud Backup → Help

Campaigns and Offers tabs point to existing CPT list URLs (`edit.php?post_type=sch_campaign|sch_offer`). On those screens, inject the shared header + tabs via an admin hook when the screen is a hub CPT list (wrapper only).

## Design tokens

Use CSS custom properties on a root admin wrapper (e.g. `.sch-admin`):

- `--sch-bg: #f0f0f1`
- `--sch-surface: #fff`
- `--sch-border: #c3c4c7`
- `--sch-text: #1d2327`
- `--sch-muted: #646970`
- `--sch-accent: #2271b1`
- `--sch-accent-hover: #135e96`
- `--sch-success-bg: #d4edda`; `--sch-success-text: #155724`
- `--sch-warning-bg: #fff3cd`; `--sch-warning-text: #664d03`
- `--sch-error-bg: #f8d7da`; `--sch-error-text: #721c24`
- Radius: `4px` panels; spacing scale based on 8px

Typography stays WordPress admin system fonts (do not introduce Inter/Roboto custom stacks).

## Page designs

### Dashboard (Ops)

Rebuild from placeholder stats into:

- **KPI row:** Published campaigns, published offers, active short links, clicks (last 7 days), cloud backup status (OK / never / failed)
- **Quick actions panel:** New short link, New campaign, New offer, Open Cloud Backup (page link; backup runs only from Cloud Backup), Open Analytics
- **Setup checklist panel:** Permalinks pretty + flushed hint, Localization enabled, Ads.txt enabled, Google Drive connected — each item links to the fix screen
- **Recent short links panel:** slug, destination (truncated), clicks, active badge, edit link; empty state with CTA to create a link

Data sources: existing CPT counts, shortener service, analytics service (7-day clicks), cloud backup last status, settings options. No new tables.

### URL Shortener

- Header actions: primary “Add link” focus stays on create form
- Create form in a dedicated panel
- Links table in a second panel: badges for Active/Inactive, indicator when smart redirect rules exist
- Preserve existing save/edit/delete behavior; restyle only

### Analytics

- Top filter bar (existing date ranges)
- KPI cards then charts/tables in panels
- Country and language breakdowns in equal panels
- Clear empty state when tracking is disabled or no data

### Settings

- Convert long single-page sections into **client-side tabs**: General, SEO, Analytics, Shortener, QR, Localization, Ads.txt, Advanced
- One Save Settings control (still `options.php`); tabs are UI-only show/hide of existing sections
- Ads.txt verify link remains near Ads.txt tab content

### Import/Export

- Two equal panels: Export | Import
- Export: type picker + Download JSON
- Import: file upload + conflict policy + clear success/error notices
- Mention 5 MB limit and link to Cloud Backup for scheduled backups

### Cloud Backup

- Connection status card (connected / not connected)
- Credentials + redirect URI panel
- Schedule + retention panel
- Last backup status + Backup now action
- Keep Phase 1 wording; Phase 2 remains “later”

### QR Codes

- Match shell; create/list panels consistent with Shortener density

### Help

- Restyle existing guides to shell components
- Keep Localization, Ads.txt, Import/Export, Cloud Backup cards prominent
- Preserve system status table and shortcode copy buttons

### Campaigns / Offers (CPT)

- Shared header + tab bar above WP list table when viewing hub CPT screens
- Do not restyle core list table columns beyond minimal CSS if needed for spacing

## Technical approach

### Files / structure

- Expand `assets/admin/css/admin.css` into the design system (tokens + components + page helpers)
- Expand `assets/admin/js/admin.js` for Settings tab switching (and any small UI helpers)
- Add a shared view partial, e.g. `src/Admin/Views/partials/admin-shell.php` (header + tabs), called from each view and from CPT list hook
- Update each view under `src/Admin/Views/*.php` to use the shell
- Extend `AdminInit` (or equivalent) to:
  - Pass richer dashboard data to the dashboard view
  - Render shell on `sch_campaign` / `sch_offer` list screens
- Assets already enqueued via `Plugin::enqueue_admin_assets` — keep scoping to hub screens (and CPT list screens)

### Accessibility / WP norms

- Use native WP buttons (`.button`, `.button-primary`) inside the shell
- Preserve focus styles; tabs keyboard-reachable
- Escape all output; keep existing capability checks (`manage_options`)
- Notices use WP admin notice patterns or styled equivalents with role/status

### Testing checklist

- Every submenu page shows header + correct active tab
- Campaigns/Offers list shows shell without breaking WP bulk actions
- Settings tabs switch without losing unsaved field values until Save
- Dashboard KPIs and checklist reflect real option/service state
- Mobile/narrow wp-admin: tabs wrap; panels stack to one column
- No PHP notices; assets load only on relevant admin screens

## Implementation order

1. Design tokens + shared shell partial + tab map
2. Apply shell to all existing views
3. Dashboard Ops rebuild (data wiring + panels)
4. Settings tabs
5. Shortener / Analytics / Import-Export / Cloud Backup / Help / QR polish
6. CPT list shell injection
7. Visual QA pass + changelog note under next version

## Out of scope for v1 of this redesign

- Custom Elementor widgets
- REST API for Cloud Backup
- New dashboard charts beyond simple KPI numbers (full charts stay on Analytics)
- Rewriting CPT edit screens (post editor meta boxes)

## References

- Existing assets: `assets/admin/css/admin.css`, `assets/admin/js/admin.js`
- Views: `src/Admin/Views/`
- Prior product docs: `readme.md`, Cloud Backup design spec
