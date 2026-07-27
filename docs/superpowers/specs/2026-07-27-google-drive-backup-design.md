# Google Drive Cloud Backup Design

## Goal

WPvivid-style cloud backup inside SEO Campaign Hub: connect Google Drive, backup now, schedules, folder organization, retention.

## Phases

### Phase 1 (implemented first)

- Admin page: Cloud Backup
- Google OAuth (Client ID + Secret in settings on page)
- Authenticate / disconnect Google Drive
- Parent folder + per-site subfolder on Drive
- Plugin JSON backup (Import/Export “everything” payload)
- Manual “Backup now” + WP-Cron schedule (daily / weekly)
- Retention: delete oldest plugin backups on Drive when limit exceeded
- Last backup status on admin page

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
