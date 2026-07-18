# Import / Export Design

## Goal

Replace the Import/Export placeholder with a working JSON backup/restore UI that exports real Campaign and Offer CPT content plus short links.

## Scope

- Export types: `all`, `campaigns`, `offers`, `links`, `analytics` (export-only), `settings`
- Import types: campaigns, offers, links, settings (optional)
- Conflict mode: `update` (default) or `skip`
- Admin-only (`manage_options`), nonce-protected forms
- Max upload size: 5 MB, JSON only

## Architecture

- Campaigns/Offers are read/written as WordPress posts (`sch_campaign`, `sch_offer`)
- Short links use `sch_links`
- Analytics is export-only (no import)
- Settings import only allowlisted `seo_campaign_hub_*` options

## Security

- Capability + nonce checks
- Sanitize titles, content, URLs, slugs
- Reject non-JSON uploads
- Do not execute uploaded content
