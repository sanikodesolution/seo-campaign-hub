# Site-wide URL Replace Design

**Status:** Implemented in 1.1.15  
**Date:** 2026-08-09  
**Plugin:** SEO Campaign Hub

## Goal

Let admins replace any URL across the public site **anytime**, plus optionally rewrite stored database content (posts, Elementor meta, options, short links).

## Surfaces

Admin: **SEO Campaign Hub → URL Replace**

### 1. Live rules (anytime)

Saved mappings `find → replace` applied to front-end HTML (output buffer). Change/delete a rule anytime without scanning the database. Covers theme, Elementor, widgets, menus.

### 2. Database replace (permanent)

Serialized- and JSON-safe find/replace across posts, postmeta, options (no transients), comments, `sch_links.destination_url`. **Dry run** first; **Apply** requires confirmation. Skips GUIDs and Drive tokens.

## Limits

- `manage_options` + nonces  
- Max 50 live rules  
- Find ≠ replace; find length ≥ 8  
- No `javascript:` targets  
- Live replace skips admin / AJAX / REST / feeds / cron  
