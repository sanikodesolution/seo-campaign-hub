# Site-wide Text Replace Design

**Status:** Implemented in 1.1.25  
**Date:** 2026-08-15  
**Plugin:** SEO Campaign Hub

## Goal

Let admins find/replace any visible text across the public site **anytime**, plus optionally rewrite stored database content (post titles, posts, Elementor meta, options, comments).

## Surfaces

Admin: **SEO Campaign Hub → Text Replace**

### 1. Live rules (anytime)

Saved mappings `find → replace` applied to front-end HTML **between tags only** (output buffer). Does not change attributes, tag names, or the database. Change/delete a rule anytime.

### 2. Database replace (permanent)

Serialized- and JSON-safe find/replace across post titles/content/excerpts, postmeta, options (no transients), comments, `sch_links.targeting_rules`. **Dry run** first; **Apply** requires confirmation. Skips GUIDs, Drive tokens, and this plugin’s URL/text rule options.

## Limits

- `manage_options` + nonces  
- Max 50 live rules  
- Find ≠ replace; find length ≥ 3 (UTF-8)  
- Replace may be empty (deletes the find text)  
- No `<` / `>` in find or replace (no HTML tags)  
- No `javascript:` targets  
- Live replace skips admin / AJAX / REST / feeds / cron / Elementor preview  
- Case-sensitive exact match (not regex)  
