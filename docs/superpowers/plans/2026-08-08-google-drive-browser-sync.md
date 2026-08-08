# Google Drive Browser Sync Implementation Plan

> **For agentic workers:** Implement task-by-task in this session (user requested immediate implementation).

**Goal:** Keep Client Secret (password) and add browser-popup Google Drive Sync on Cloud Backup.

**Architecture:** Same OAuth redirect URI and token exchange. Admin JS opens Google consent in a named popup; after PHP callback redirect, popup refreshes the opener and closes. Popup-blocker falls back to same-tab.

**Tech Stack:** WordPress admin, vanilla JS, existing `GoogleDriveService`.

## Global Constraints

- PHP 8.2+, WordPress 6.0+, `manage_options` only
- Do not change OAuth scope (`drive.file`) or redirect URI
- Keep Client Secret as `type="password"`
- No backup encryption in this change

---

## Task 1: Cloud Backup Sync popup JS + UI

**Files:** `assets/admin/js/admin-cloud-backup.js` (new), `src/Admin/Views/cloud-backup.php`, `src/Admin/AdminInit.php`

- [ ] Add JS: click `[data-sch-google-auth]` → `window.open` Google auth URL; if blocked, navigate same tab
- [ ] Add JS: if `window.opener` and `sch_notice` is `oauth_ok` or `oauth_fail`, set opener location and `window.close()`
- [ ] Enqueue script only on Cloud Backup admin page
- [ ] Keep Client Secret password field; rename connect button to Sync with Google Drive; add short description that both password and sync stay available
- [ ] Handle Google `error=` on OAuth return (oauth_fail notice)

## Task 2: Docs + version

**Files:** `changelog.md`, `readme.md`, `src/Admin/Views/help.php`, `seo-campaign-hub.php`, `docs/superpowers/specs/2026-07-27-google-drive-backup-design.md`

- [ ] Changelog 1.1.12, readme connect steps, help troubleshooting, version bump
