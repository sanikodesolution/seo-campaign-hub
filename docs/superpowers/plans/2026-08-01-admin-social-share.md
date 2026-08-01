# Admin Social Share Implementation Plan

> **For agentic workers:** Implement task-by-task. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Add browser-based social share icon row-actions on Posts → All Posts (no OAuth/App IDs).

**Architecture:** `SocialShareService` builds share URLs; `AdminInit` hooks `post_row_actions` + Share column + Social Share submenu; Settings / dedicated page add enable + network checklist under `seo_campaign_hub_options`.

**Tech Stack:** WordPress PHP 8.2+, Dashicons, tiny admin JS for copy-link.

## Global Constraints

- Admin only; `post` type only; Share column + row-actions under title.
- Networks: facebook, x, linkedin, pinterest, whatsapp, blogger, telegram, quora, reddit, email, copy.
- No App ID / OAuth / auto-post.
- Spec: `docs/superpowers/specs/2026-08-01-admin-social-share-design.md`.

---

### Task 1: SocialShareService + Settings

**Files:** Create `src/Services/SocialShareService.php`; Modify `src/Admin/Settings.php`; Modify `src/Core/Plugin.php` (register singleton).

- [ ] Service: `get_networks()`, `is_enabled()`, `get_enabled_networks()`, `can_share_post( WP_Post ): bool`, `get_share_urls( int $post_id ): array`, `build_row_actions_html( WP_Post ): array`
- [ ] Settings section `social_share`: `enable_admin_social_share` (default 1), `social_share_networks` checkbox_group

### Task 2: Row actions + assets

**Files:** Modify `src/Admin/AdminInit.php`; `assets/admin/css/admin.css`; `assets/admin/js/admin.js`

- [ ] Filter `post_row_actions`
- [ ] Enqueue CSS/JS on `edit.php` for `post` only (jquery + copy handler)
- [ ] Icon row-action markup with aria-labels

### Task 3: Docs

**Files:** `changelog.md`, `readme.md`; bump version if releasing.

---
