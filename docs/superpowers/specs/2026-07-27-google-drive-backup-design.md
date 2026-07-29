# Google Drive Cloud Backup Design

**Status:** Phase 1 shipped in **1.1.3**. User-facing docs: `readme.md` → [Cloud Backup (Google Drive)](../../../readme.md#cloud-backup-google-drive).

## Goal

WPvivid-style cloud backup inside SEO Campaign Hub: connect Google Drive, backup now, schedules, folder organization, retention.

## Phases

### Phase 1 (shipped in 1.1.3)

- Admin page: Cloud Backup (`seo-campaign-hub-cloud-backup`)
- Google OAuth (Client ID + Secret in settings on page)
- Authenticate / disconnect Google Drive
- Parent folder + per-site subfolder + `plugin` folder on Drive
- Plugin JSON backup (Import/Export “everything” payload)
- Manual “Backup now” + WP-Cron schedule (daily / weekly; hook `seo_campaign_hub_cloud_backup_cron`)
- Retention: delete oldest plugin backups on Drive when limit exceeded
- Last backup status on admin page
- Services: `GoogleDriveService`, `CloudBackupService`, `BackupSchedulerService`

### Phase 2 (later)

- Full site backup: database dump + `wp-content` zip, chunked upload
- Optional separate schedules for DB vs files
- Download from Drive; restore flows

## Security

- `manage_options` + nonces for all actions
- OAuth state transient per user
- Tokens in `seo_campaign_hub_google_drive_tokens` option
- Scope: `drive.file` (files created by the app)

## Google Cloud setup

Enable Drive API, OAuth Web client, redirect URI shown on Cloud Backup page.
