# Admin UI Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a modern WordPress-native admin UI across SEO Campaign Hub, with the **Ops Dashboard** as the first major user-facing deliverable after the shared shell.

**Architecture:** Add a shared PHP shell partial (header + in-plugin tabs) and CSS design tokens in `admin.css`. Wire Dashboard data in `AdminInit::render_dashboard()` from existing services. Restyle remaining views onto the same shell; Settings uses client-side section tabs; CPT list screens get shell injection only.

**Tech Stack:** WordPress PHP 8.2+, existing admin CSS/JS (jQuery), `AdminInit` view renderer, existing Shortener/Analytics/CloudBackup/GoogleDrive services. No new build tools or chart libraries.

## Global Constraints

- Visual direction: modern WordPress-native (`#f0f0f1` bg, `#fff` panels, `#c3c4c7` borders, `#2271b1` accent).
- Shell layout: Header + cards (no plugin left sidebar).
- Dashboard model: Ops (KPIs, quick actions, setup checklist, recent links).
- No new DB tables or product features beyond existing option/service data.
- Cloud Backup quick action opens the Cloud Backup page only (does not run backup from Dashboard).
- Keep WP list tables for Campaigns/Offers; wrapper shell only.
- Escape all output; require `manage_options` on admin pages.
- Prefer existing class prefixes; new shell classes use `.sch-admin` namespace alongside existing `.seo-campaign-hub-*`.
- Spec: `docs/superpowers/specs/2026-07-30-admin-ui-redesign-design.md`.

## File map

| File | Responsibility |
|------|----------------|
| `src/Admin/Views/partials/admin-shell-start.php` | Opens `.wrap.sch-admin`, header, tab bar |
| `src/Admin/Views/partials/admin-shell-end.php` | Closes shell wrappers |
| `src/Admin/AdminShell.php` | Tab map + `current_tab` helper + render helpers |
| `assets/admin/css/admin.css` | Tokens + shell + KPI/panel/badge components |
| `assets/admin/js/admin.js` | Settings section tabs; keep existing helpers |
| `src/Admin/Views/dashboard.php` | Ops dashboard markup |
| `src/Admin/AdminInit.php` | Dashboard data; CPT shell hook; pass shell vars |
| `src/Admin/Views/{shortener,analytics,settings,import-export,cloud-backup,help}.php` | Adopt shell + panel polish |
| `src/Core/Plugin.php` | Ensure admin JS depends on `jquery`; enqueue on CPT lists |
| `changelog.md` / `readme.md` | Note UI redesign under next version |

---

### Task 1: Shared shell + design tokens

**Files:**
- Create: `src/Admin/AdminShell.php`
- Create: `src/Admin/Views/partials/admin-shell-start.php`
- Create: `src/Admin/Views/partials/admin-shell-end.php`
- Modify: `assets/admin/css/admin.css`
- Modify: `src/Core/Plugin.php` (jquery dependency for admin.js)

**Interfaces:**
- Produces: `SEO_Campaign_Hub\Admin\AdminShell::get_tabs(): array` — list of `[ 'id' => string, 'label' => string, 'url' => string ]`
- Produces: `AdminShell::resolve_current_tab( string $hook_or_hint = '' ): string`
- Produces: partials expecting `$page_title`, `$page_description`, `$current_tab`, `$header_actions` (HTML string or array of `[label,url,class]`)

- [ ] **Step 1: Add `AdminShell` with tab map**

```php
<?php
namespace SEO_Campaign_Hub\Admin;

class AdminShell {
    public static function get_tabs(): array {
        return [
            [ 'id' => 'dashboard', 'label' => __( 'Dashboard', 'seo-campaign-hub' ), 'url' => admin_url( 'admin.php?page=seo-campaign-hub' ) ],
            [ 'id' => 'campaigns', 'label' => __( 'Campaigns', 'seo-campaign-hub' ), 'url' => admin_url( 'edit.php?post_type=sch_campaign' ) ],
            [ 'id' => 'offers', 'label' => __( 'Offers', 'seo-campaign-hub' ), 'url' => admin_url( 'edit.php?post_type=sch_offer' ) ],
            [ 'id' => 'shortener', 'label' => __( 'URL Shortener', 'seo-campaign-hub' ), 'url' => admin_url( 'admin.php?page=seo-campaign-hub-shortener' ) ],
            [ 'id' => 'qr-codes', 'label' => __( 'QR Codes', 'seo-campaign-hub' ), 'url' => admin_url( 'admin.php?page=seo-campaign-hub-qr-codes' ) ],
            [ 'id' => 'analytics', 'label' => __( 'Analytics', 'seo-campaign-hub' ), 'url' => admin_url( 'admin.php?page=seo-campaign-hub-analytics' ) ],
            [ 'id' => 'settings', 'label' => __( 'Settings', 'seo-campaign-hub' ), 'url' => admin_url( 'admin.php?page=seo-campaign-hub-settings' ) ],
            [ 'id' => 'import-export', 'label' => __( 'Import/Export', 'seo-campaign-hub' ), 'url' => admin_url( 'admin.php?page=seo-campaign-hub-import-export' ) ],
            [ 'id' => 'cloud-backup', 'label' => __( 'Cloud Backup', 'seo-campaign-hub' ), 'url' => admin_url( 'admin.php?page=seo-campaign-hub-cloud-backup' ) ],
            [ 'id' => 'help', 'label' => __( 'Help', 'seo-campaign-hub' ), 'url' => admin_url( 'admin.php?page=seo-campaign-hub-help' ) ],
        ];
    }

    public static function resolve_current_tab( string $hint = '' ): string {
        if ( $hint !== '' ) {
            return $hint;
        }
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( $screen && $screen->post_type === 'sch_campaign' ) {
            return 'campaigns';
        }
        if ( $screen && $screen->post_type === 'sch_offer' ) {
            return 'offers';
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        $map = [
            'seo-campaign-hub' => 'dashboard',
            'seo-campaign-hub-shortener' => 'shortener',
            'seo-campaign-hub-qr-codes' => 'qr-codes',
            'seo-campaign-hub-analytics' => 'analytics',
            'seo-campaign-hub-settings' => 'settings',
            'seo-campaign-hub-import-export' => 'import-export',
            'seo-campaign-hub-cloud-backup' => 'cloud-backup',
            'seo-campaign-hub-help' => 'help',
        ];
        return $map[ $page ] ?? 'dashboard';
    }
}
```

- [ ] **Step 2: Create shell partials**

`admin-shell-start.php` must output:

```php
<div class="wrap sch-admin">
  <div class="sch-admin__header">
    <div class="sch-admin__titles">
      <h1><?php echo esc_html( $page_title ); ?></h1>
      <?php if ( ! empty( $page_description ) ) : ?>
        <p class="sch-admin__description"><?php echo esc_html( $page_description ); ?></p>
      <?php endif; ?>
    </div>
    <?php /* optional $header_actions as buttons */ ?>
  </div>
  <nav class="sch-admin__tabs" aria-label="<?php esc_attr_e( 'SEO Campaign Hub', 'seo-campaign-hub' ); ?>">
    <?php foreach ( \SEO_Campaign_Hub\Admin\AdminShell::get_tabs() as $tab ) : ?>
      <a class="sch-admin__tab<?php echo ( $current_tab === $tab['id'] ) ? ' is-active' : ''; ?>"
         href="<?php echo esc_url( $tab['url'] ); ?>"><?php echo esc_html( $tab['label'] ); ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="sch-admin__body">
```

`admin-shell-end.php` closes `sch-admin__body` and `wrap.sch-admin`.

- [ ] **Step 3: Add CSS tokens + shell components at top of `admin.css`**

Include at minimum:

```css
.sch-admin {
  --sch-bg: #f0f0f1;
  --sch-surface: #fff;
  --sch-border: #c3c4c7;
  --sch-text: #1d2327;
  --sch-muted: #646970;
  --sch-accent: #2271b1;
  --sch-accent-hover: #135e96;
  --sch-success-bg: #d4edda;
  --sch-success-text: #155724;
  --sch-warning-bg: #fff3cd;
  --sch-warning-text: #664d03;
  --sch-error-bg: #f8d7da;
  --sch-error-text: #721c24;
  margin: 20px 20px 0 0;
}
.sch-admin__tabs { display: flex; flex-wrap: wrap; gap: 4px; margin: 12px 0 16px; border-bottom: 1px solid var(--sch-border); }
.sch-admin__tab { text-decoration: none; padding: 8px 12px; color: var(--sch-muted); border-bottom: 2px solid transparent; }
.sch-admin__tab.is-active { color: var(--sch-accent); border-bottom-color: var(--sch-accent); font-weight: 600; }
.sch-panel { background: var(--sch-surface); border: 1px solid var(--sch-border); border-radius: 4px; padding: 16px; margin-bottom: 16px; }
.sch-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 16px; }
.sch-kpi { background: var(--sch-surface); border: 1px solid var(--sch-border); border-radius: 4px; padding: 16px; text-align: center; }
.sch-kpi__value { font-size: 28px; font-weight: 700; color: var(--sch-text); }
.sch-kpi__label { color: var(--sch-muted); font-size: 13px; }
.sch-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: 600; }
.sch-badge--ok { background: var(--sch-success-bg); color: var(--sch-success-text); }
.sch-badge--warn { background: var(--sch-warning-bg); color: var(--sch-warning-text); }
.sch-badge--err { background: var(--sch-error-bg); color: var(--sch-error-text); }
.sch-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 782px) { .sch-grid-2 { grid-template-columns: 1fr; } }
```

- [ ] **Step 4: Fix admin.js dependency**

In `Plugin::enqueue_admin_assets()`, change admin script deps from `[]` to `[ 'jquery' ]`. Update localize handle comments (it is jQuery admin JS, not a React SPA).

- [ ] **Step 5: Smoke-check**

Open any plugin page after Task 2 wiring; until then, verify PHP lint:

```bash
php -l src/Admin/AdminShell.php
```

Expected: `No syntax errors detected`

- [ ] **Step 6: Commit** (only if user requested commits)

```bash
git add src/Admin/AdminShell.php src/Admin/Views/partials assets/admin/css/admin.css src/Core/Plugin.php
git commit -m "feat(admin): add shared shell partial and design tokens"
```

---

### Task 2: Ops Dashboard (priority deliverable)

**Files:**
- Modify: `src/Admin/AdminInit.php` (`render_dashboard`, helpers)
- Rewrite: `src/Admin/Views/dashboard.php`

**Interfaces:**
- Consumes: `AdminShell`, `shortener->get_links()`, `analytics->get_dashboard_summary(7)`, `cloud_backup->get_last_status()`, `google_drive->is_connected()`, options for localization/ads.txt
- Produces: dashboard view vars:
  - `kpis`: `campaigns`, `offers`, `active_links`, `clicks_7d`, `backup_status` (`ok|never|failed`)
  - `checklist`: array of `[ 'label', 'ok' => bool, 'url' ]`
  - `recent_links`: up to 8 links
  - `page_title`, `page_description`, `current_tab` = `dashboard`

- [ ] **Step 1: Expand `render_dashboard()` data wiring**

```php
public function render_dashboard(): void {
    $campaign_count = wp_count_posts( 'sch_campaign' );
    $offer_count    = wp_count_posts( 'sch_offer' );
    $options        = get_option( 'seo_campaign_hub_settings', [] );
    if ( ! is_array( $options ) ) {
        $options = [];
    }

    $active_links = 0;
    $recent_links = [];
    try {
        $shortener = $this->container->get( 'shortener' );
        $recent_links = $shortener->get_links( [ 'limit' => 8, 'is_active' => null ] );
        $active_links = count( $shortener->get_links( [ 'limit' => 500, 'is_active' => 1 ] ) );
    } catch ( \Throwable $e ) {
        $active_links = $this->count_short_links();
    }

    $clicks_7d = 0;
    try {
        $summary = $this->container->get( 'analytics' )->get_dashboard_summary( 7 );
        $clicks_7d = (int) ( $summary['clicks'] ?? 0 );
    } catch ( \Throwable $e ) {
        $clicks_7d = 0;
    }

    $backup_status = 'never';
    try {
        $status = $this->container->get( 'cloud_backup' )->get_last_status();
        if ( $status['time'] === '' ) {
            $backup_status = 'never';
        } elseif ( (string) $status['success'] === '1' || $status['success'] === 'yes' ) {
            $backup_status = 'ok';
        } else {
            $backup_status = 'failed';
        }
    } catch ( \Throwable $e ) {
        $backup_status = 'never';
    }

    $drive_connected = false;
    try {
        $drive_connected = (bool) $this->container->get( 'google_drive' )->is_connected();
    } catch ( \Throwable $e ) {
        $drive_connected = false;
    }

    $checklist = [
        [
            'label' => __( 'Pretty permalinks enabled', 'seo-campaign-hub' ),
            'ok'    => ( get_option( 'permalink_structure' ) !== '' ),
            'url'   => admin_url( 'options-permalink.php' ),
        ],
        [
            'label' => __( 'Smart redirects / Localization enabled', 'seo-campaign-hub' ),
            'ok'    => ! empty( $options['enable_smart_redirects'] ),
            'url'   => admin_url( 'admin.php?page=seo-campaign-hub-settings' ),
        ],
        [
            'label' => __( 'Ads.txt enabled', 'seo-campaign-hub' ),
            'ok'    => ! empty( $options['enable_ads_txt'] ),
            'url'   => admin_url( 'admin.php?page=seo-campaign-hub-settings' ),
        ],
        [
            'label' => __( 'Google Drive connected', 'seo-campaign-hub' ),
            'ok'    => $drive_connected,
            'url'   => admin_url( 'admin.php?page=seo-campaign-hub-cloud-backup' ),
        ],
    ];

    $this->render_view( 'dashboard', [
        'page_title'       => __( 'Dashboard', 'seo-campaign-hub' ),
        'page_description' => __( 'Campaign ops overview: counts, setup, and recent short links.', 'seo-campaign-hub' ),
        'current_tab'      => 'dashboard',
        'kpis'             => [
            'campaigns'     => (int) ( $campaign_count->publish ?? 0 ),
            'offers'        => (int) ( $offer_count->publish ?? 0 ),
            'active_links'  => (int) $active_links,
            'clicks_7d'     => (int) $clicks_7d,
            'backup_status' => $backup_status,
        ],
        'checklist'        => $checklist,
        'recent_links'     => is_array( $recent_links ) ? $recent_links : [],
        'prefix'           => get_option( 'seo_campaign_hub_shortener_prefix', 'go' ),
    ] );
}
```

Adapt `get_links` args to the real `ShortenerService::get_links()` signature (use whatever keys the service already accepts for `limit` / active filter). If active filter is unavailable, show total link count from `count_short_links()` and still list recent rows.

- [ ] **Step 2: Rewrite `dashboard.php` with shell + Ops panels**

Structure:

1. Include `partials/admin-shell-start.php`
2. KPI row (`sch-kpi-grid`) for campaigns, offers, active links, clicks 7d, backup badge
3. `sch-grid-2`: Quick actions panel | Setup checklist panel
4. Recent short links panel (table or list); empty state CTA → shortener
5. Include `partials/admin-shell-end.php`

Quick action buttons (all links, no POST backup):

- New short link → `admin.php?page=seo-campaign-hub-shortener`
- New campaign → `post-new.php?post_type=sch_campaign`
- New offer → `post-new.php?post_type=sch_offer`
- Cloud Backup → `admin.php?page=seo-campaign-hub-cloud-backup`
- Analytics → `admin.php?page=seo-campaign-hub-analytics`

- [ ] **Step 3: Manual verify Ops Dashboard**

In wp-admin → SEO Campaign Hub → Dashboard:

1. Header + tabs visible; Dashboard tab active
2. KPI numbers match published CPT counts and shortener/analytics where data exists
3. Checklist items deep-link correctly
4. Recent links render or empty state shows
5. No PHP warnings

- [ ] **Step 4: Commit** (if requested)

```bash
git add src/Admin/AdminInit.php src/Admin/Views/dashboard.php
git commit -m "feat(admin): rebuild Ops dashboard with KPIs and setup checklist"
```

---

### Task 3: Adopt shell on remaining plugin views

**Files:**
- Modify: `src/Admin/Views/shortener.php`
- Modify: `src/Admin/Views/analytics.php`
- Modify: `src/Admin/Views/settings.php`
- Modify: `src/Admin/Views/import-export.php`
- Modify: `src/Admin/Views/cloud-backup.php`
- Modify: `src/Admin/Views/help.php`
- Modify: `src/Admin/AdminInit.php` (pass `page_description`, `current_tab` into each `render_*`)

**Interfaces:**
- Consumes: shell partials + `current_tab` ids from Task 1
- Produces: every submenu page shares header/tabs

- [ ] **Step 1:** For each `render_*` in `AdminInit`, add `current_tab` and a one-line `page_description`.
- [ ] **Step 2:** Replace top `<div class="wrap"><h1>…` in each view with shell start/end includes; keep page body inside `sch-admin__body`.
- [ ] **Step 3:** Wrap major blocks in `.sch-panel` where it improves scanability without changing form `action`/nonces.
- [ ] **Step 4:** Verify each submenu page shows correct active tab.
- [ ] **Step 5: Commit** (if requested)

```bash
git commit -m "feat(admin): wrap all hub pages in shared admin shell"
```

---

### Task 4: Settings section tabs

**Files:**
- Modify: `src/Admin/Views/settings.php`
- Modify: `assets/admin/js/admin.js`
- Modify: `assets/admin/css/admin.css` (settings tab styles)

**Interfaces:**
- Settings still posts to `options.php` with `settings_fields( 'seo_campaign_hub_settings' )`
- Tabs are UI-only; section keys from `Settings::define_sections()`:
  `general`, `seo`, `analytics`, `url_shortener`, `qr_codes`, `localization`, `google_analytics`, `header_footer_scripts`, `ads_txt`, `advanced`

- [ ] **Step 1: Restructure settings markup**

After shell start, render a secondary tab list `.sch-settings-tabs` with `data-section` buttons matching WordPress `h2` section titles / `#seo_campaign_hub_…` section IDs produced by Settings API.

Practical approach that avoids rewriting Settings.php:

1. Keep `do_settings_sections( 'seo_campaign_hub_settings' )` output inside `#sch-settings-sections`
2. On DOM ready, find each `h2` inside that form (WP prints section titles as `h2`), wrap each `h2` + following `.form-table` until next `h2` into `.sch-settings-pane` with `data-section-slug`
3. Build tab buttons from those `h2` texts
4. Show only active pane; default first pane (General)

Alternatively, if section IDs are stable in the DOM (`#seo_campaign_hub_settings` subsections), map explicitly:

```js
const order = [
  'general','seo','analytics','url_shortener','qr_codes',
  'localization','google_analytics','header_footer_scripts','ads_txt','advanced'
];
```

- [ ] **Step 2: Keep one Save button** visible below tabs (not inside a hidden pane).
- [ ] **Step 3: Keep Ads.txt verify link** near Ads.txt pane content (existing description block in `settings.php`).
- [ ] **Step 4: Manual verify** — switch tabs, fields remain in DOM (not destroyed), Save persists options for a field in a non-first tab.
- [ ] **Step 5: Commit** (if requested)

```bash
git commit -m "feat(admin): add Settings section tabs without changing options API"
```

---

### Task 5: Page polish (Shortener, Analytics, Import/Export, Cloud Backup, Help, QR)

**Files:**
- Modify views listed in Task 3 as needed for denser polish
- Modify: `src/Admin/AdminInit.php` `render_qr_codes` / create `src/Admin/Views/qr-codes.php` if still placeholder
- Modify: `assets/admin/css/admin.css`

**Interfaces:**
- No behavior changes to export/import handlers or cloud backup POST actions

- [ ] **Step 1: Shortener** — create form in `.sch-panel`; links table in `.sch-panel`; Active/Inactive `.sch-badge`; note when smart rules present if field exists on link row.
- [ ] **Step 2: Analytics** — filter bar + KPI cards + panels for country/language; empty state when disabled/no data.
- [ ] **Step 3: Import/Export** — two-column `.sch-grid-2` Export | Import panels; 5 MB note; link to Cloud Backup.
- [ ] **Step 4: Cloud Backup** — status card, credentials panel, schedule panel, last backup; keep existing forms/nonces.
- [ ] **Step 5: Help** — restyle grids to `.sch-panel`; keep copy buttons and status table.
- [ ] **Step 6: QR Codes** — if placeholder, add minimal panel shell matching Shortener density (list/create using existing service if already wired; otherwise styled placeholder with shell, no fake data).
- [ ] **Step 7: Manual pass** on each page at desktop and narrow width.
- [ ] **Step 8: Commit** (if requested)

```bash
git commit -m "feat(admin): polish hub tool pages to WordPress-native panels"
```

---

### Task 6: CPT list shell injection

**Files:**
- Modify: `src/Admin/AdminInit.php`
- Modify: `src/Core/Plugin.php` (enqueue already allows `sch_campaign` / `sch_offer` hooks — confirm list screens match)

**Interfaces:**
- Hook: `all_admin_notices` or `in_admin_header` is wrong for placement; prefer `manage_posts_extra_tablenav` is too late.
- Use `admin_notices` early OR output before list via:

```php
add_action( 'all_admin_notices', [ $this, 'render_cpt_shell_header' ], 1 );
```

Only when `$screen->id` is `edit-sch_campaign` or `edit-sch_offer`. Print shell header+tabs inside a container that does **not** break `#posts-filter`. Close wrapper with `admin_footer` action if an open wrapper was printed.

Simpler accepted approach from spec: print header+tabs as a notice-area block above the list (no wrapping the table), so bulk actions stay intact:

```php
public function render_cpt_shell_header(): void {
    $screen = get_current_screen();
    if ( ! $screen || ! in_array( $screen->id, [ 'edit-sch_campaign', 'edit-sch_offer' ], true ) ) {
        return;
    }
    $page_title = $screen->id === 'edit-sch_campaign'
        ? __( 'Campaigns', 'seo-campaign-hub' )
        : __( 'Offers', 'seo-campaign-hub' );
    $current_tab = $screen->id === 'edit-sch_campaign' ? 'campaigns' : 'offers';
    $page_description = __( 'Manage plugin landing content with the WordPress list table.', 'seo-campaign-hub' );
    include SEO_CAMPAIGN_HUB_PLUGIN_DIR . 'src/Admin/Views/partials/admin-shell-start.php';
    // Do not include shell-end here — close with a slim end partial that only closes tabs/header chrome if start was split.
}
```

Prefer splitting partials if needed:

- `admin-chrome.php` — header + tabs only (no body wrapper)
- Use chrome on CPT screens; full shell start/end on plugin pages

- [ ] **Step 1: Extract `admin-chrome.php`** (header+tabs) used by shell-start and CPT hook.
- [ ] **Step 2: Hook CPT list screens** to print chrome only.
- [ ] **Step 3: Verify** bulk edit, filters, and row actions still work.
- [ ] **Step 4: Commit** (if requested)

```bash
git commit -m "feat(admin): show hub chrome on Campaigns and Offers list screens"
```

---

### Task 7: Docs + version note

**Files:**
- Modify: `changelog.md`
- Modify: `readme.md` (brief Admin UI note under latest version section)

- [ ] **Step 1:** Add changelog entry for admin UI redesign (Ops dashboard, shared shell, settings tabs).
- [ ] **Step 2:** Ensure Help/readme do not contradict new Dashboard behavior.
- [ ] **Step 3: Commit** (if requested)

```bash
git commit -m "docs: note WordPress-native admin UI redesign"
```

---

## Spec coverage check

| Spec item | Task |
|-----------|------|
| Shared shell Header + cards | Task 1 |
| Ops Dashboard KPIs / actions / checklist / recent links | Task 2 |
| All plugin views on shell | Task 3 |
| Settings client-side tabs | Task 4 |
| Shortener/Analytics/IE/Cloud/Help/QR polish | Task 5 |
| CPT wrapper chrome | Task 6 |
| Docs | Task 7 |
| No backup-from-dashboard | Task 2 quick actions |
| Design tokens | Task 1 |

## Execution handoff

Plan complete and saved to `docs/superpowers/plans/2026-07-30-admin-ui-redesign.md`.

**Two execution options:**

1. **Subagent-Driven (recommended)** — fresh subagent per task, review between tasks  
2. **Inline Execution** — run tasks in this session with checkpoints  

Which approach?
