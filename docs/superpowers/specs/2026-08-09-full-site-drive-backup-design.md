# Full Site Backup to Google Drive (Phase 2)

**Status:** Approved 2026-08-09  
**Plugin:** SEO Campaign Hub  
**Version target:** 1.1.16

## Goal

Replace WPvivid for this site’s needs: back up **all WordPress data** (database + `wp-content`) to Google Drive from **Cloud Backup**.

## Scope (v1)

- Database dump (tables with `$wpdb->prefix`)
- Zip of `wp-content` (exclude cache/temp/upgrade/backup junk)
- Upload to Drive: `{parent}/{site}/full/`
- Chunked AJAX job UI with progress
- Retention on `full/` folder (same retention setting)
- Schedule option: plugin JSON **or** full site
- Requires existing Google OAuth connection

## Out of scope (v1)

- One-click restore from Drive
- Bundled Google OAuth app (no Client ID)
- Backing up WordPress core files outside `wp-content` (DB + wp-content is enough to restore with a fresh WP)

## Architecture

| Piece | Role |
|-------|------|
| `FullSiteBackupService` | Job state, dump, zip, upload orchestration |
| `GoogleDriveService` | `full` folder + resumable chunked upload |
| `admin-cloud-backup.js` | Poll/tick AJAX until done |
| Cloud Backup UI | “Backup full site to Google Drive” + progress |

## Job phases

1. `init` — temp work dir, table list  
2. `dump` — batch SQL dump  
3. `zip` — batch add files + SQL into archive  
4. `upload` — resumable Drive upload  
5. `finish` — retention, cleanup, status  

## Security

- `manage_options` + AJAX nonce  
- Temp files under plugin `uploads/temp/` with cleanup  
- No tokens in front-end responses beyond progress messages  
