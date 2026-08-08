# Google Drive Browser Sync Design

**Status:** Approved (option B). User-facing docs: `readme.md` → Cloud Backup (Google Drive).

**Date:** 2026-08-08  
**Plugin:** SEO Campaign Hub

## Goal

Connect Google Drive from **Cloud Backup** in a **browser popup**, while keeping both:

- **Password** — Google Client Secret field (`type="password"`)
- **Sync** — button that opens Google OAuth in a popup (not a full-page redirect)

Existing Client ID + Secret OAuth, redirect URI, backup now, schedule, and retention stay unchanged.

## Decisions

| Decision | Choice |
|----------|--------|
| Auth model | Same server-side OAuth (`drive.file`), Client ID + Secret required |
| Connect UX | Named popup (`window.open`); popup-blocker fallback = same tab |
| Redirect URI | Unchanged (`admin.php?page=seo-campaign-hub-cloud-backup`) |
| After success/fail | Popup reloads opener with `sch_notice`, then closes |
| Cancel / Google `error=` | Treat as `oauth_fail`, same popup close flow |
| Encryption password | Out of scope (option A deferred) |

## Non-goals

- Backup file encryption
- Google Picker / GIS implicit flow without Client Secret
- Changing Drive folder layout or backup payload
- Front-end (non-admin) Drive sync

## UX

On **SEO Campaign Hub → Cloud Backup → Cloud Storage — Google Drive**:

1. Admin saves Client ID + **Client Secret** (password field) + folders + retention
2. If credentials exist and not connected: primary button **Sync with Google Drive**
3. Click opens a centered popup to Google consent; parent page stays on Cloud Backup
4. After approve/deny, popup returns to the same admin URL; PHP exchanges the code (or records error)
5. Popup JS detects `window.opener` + `sch_notice=oauth_ok|oauth_fail`, sets opener location to that URL, closes itself
6. Parent shows Connected + email, or an error notice
7. **Disconnect** unchanged

## Technical approach

| File | Role |
|------|------|
| `assets/admin/js/admin-cloud-backup.js` | Open Sync popup; close popup and refresh opener after OAuth |
| `src/Admin/Views/cloud-backup.php` | Password field stays; Sync button + `data-sch-google-auth` |
| `src/Admin/AdminInit.php` | Enqueue JS on Cloud Backup page; handle Google `error=` callback |
| `readme.md` / `changelog.md` / Help | Document popup Sync |

No new OAuth scopes. No new options. Tokens still in `seo_campaign_hub_google_drive_tokens`.

## Testing checklist

- Client Secret field remains password type after save
- Sync opens popup when not blocked; same-tab if blocked
- Successful consent → parent shows Connected, popup closes
- Deny / cancel → parent shows oauth fail, popup closes
- Redirect URI still matches Google Cloud Console (no Console change required)
- Disconnect still works
- Backup now / schedule unchanged
