# SEO Title & Meta Description Design

**Status:** Implemented in 1.1.24  
**Date:** 2026-08-13  
**Plugin:** SEO Campaign Hub  
**Approach:** Lean — per-content metabox + homepage defaults (option B / approach 1)

## Goals

Let admins enter **SEO Title** and **Meta Description** for:

1. Individual **posts, pages, campaigns, and offers** (editor metabox)
2. The **homepage / site default** (Settings → SEO)

The front end must output those values as the document `<title>`, `<meta name="description">`, and (when enabled) Open Graph title/description. Empty fields fall back to WordPress title / excerpt / site name / tagline — never leave homepage with no controllable meta.

## Decisions (locked)

| Decision | Choice |
|----------|--------|
| Scope | Per-entry metabox **and** homepage Settings fields |
| Post types | `post`, `page`, `sch_campaign`, `sch_offer` |
| Fields (v1) | SEO Title + Meta Description only |
| Storage (post) | Existing keys `_seo_campaign_hub_meta_title`, `_seo_campaign_hub_meta_description` |
| Storage (site) | `seo_campaign_hub_options['homepage_meta_title']`, `seo_campaign_hub_options['homepage_meta_description']` |
| Title tag | `pre_get_document_title` when a custom title is set (full title, no extra ` - Sitename` suffix) |
| Description | `<meta name="description">` via `wp_head` |
| Open Graph | Same resolved title/description when `enable_open_graph` is on |
| Character counts | Advisory (≈60 / ≈160); do not truncate on save |
| Save max | Title 200 chars, description 500 chars (abuse cap only) |
| Meta toggle | `enable_meta_tags` gates title + description output (default on) |
| OG toggle | `enable_open_graph` gates OG independently (default on) |
| Metabox placement | Context `normal`, priority `high` (below editor, not side) |

## Non-goals (v1)

- Keywords, canonical URL, noindex/nofollow UI (keys may already exist; leave output as-is if meta is set elsewhere)
- Separate OG title/description/image fields
- SERP preview mockup
- `%title%` / `%sitename%` templates
- Archive, search, category, tag, author, 404 SEO
- Posts index (`is_home() && ! is_front_page()`) — leave WordPress default
- Detecting / disabling Yoast or Rank Math (document: do not run two title plugins)
- Wiring or fixing unused `src/Public/PublicInit.php` (not booted; all output stays in `SEOService`)

## UX

### Editor metabox — “SEO”

Shown on Add/Edit for `post`, `page`, `sch_campaign`, `sch_offer` (classic and block editor).

- **SEO Title** — text input; live character count; hint “Recommended: 50–60 characters”
- **Meta Description** — textarea; live character count; hint “Recommended: 120–160 characters”
- Empty fields: placeholder hint that the post title / excerpt (or homepage defaults on the static front page) will be used
- Save with the post (nonce + `edit_post` capability)

### Settings → SEO

After the existing Schema / Meta Tags / Open Graph / Block listings checkboxes:

- **Homepage SEO Title** (`text`) — used on the site front page
- **Homepage Meta Description** (`textarea`) — used on the site front page

Help text: applies to the front page. If the front page is a static Page, that page’s SEO metabox overrides these fields when filled.

## Resolution order

Pure resolver (`SeoMetaResolver`) decides strings. Empty string means “not set”.

Resolver input is a plain array (no WP calls inside the class), e.g. `context` (`front_posts` | `front_page` | `singular`), `custom_title`, `custom_description`, `homepage_title`, `homepage_description`, `post_title`, `excerpt`, `content`, `site_name`, `tagline`.

Return shape:

```text
[
  'title'            => string,
  'description'      => string,
  'title_is_custom'  => bool,  // true only if post/homepage SEO title was used
]
```

`SEOService` applies `pre_get_document_title` only when `title_is_custom` is true. OG uses `title` / `description` including fallbacks.

### Front page = latest posts (`is_front_page() && is_home()`)

| Field | Order |
|-------|--------|
| Title | homepage SEO title → `blogname` |
| Description | homepage SEO description → `blogdescription` (tagline) |

### Front page = static page (`is_front_page() && ! is_home()`)

| Field | Order |
|-------|--------|
| Title | page SEO title → homepage SEO title → page title |
| Description | page SEO description → homepage SEO description → excerpt → trimmed content |

### Singular post / page / campaign / offer (not front page)

| Field | Order |
|-------|--------|
| Title | entry SEO title → post title |
| Description | entry SEO description → excerpt → trimmed content |

Inner URLs **must not** fall back to homepage SEO fields.

### Other views

No plugin title/description/OG changes.

## Front end

`SEOService` remains the single public output path.

### Title

- If resolver returns a custom title (from post meta or homepage setting, not merely WP fallback): filter `pre_get_document_title` and return that string.
- If only WP fallback would apply: do not filter; leave core/theme title.

### Meta description

- If `enable_meta_tags` is on and resolver description is non-empty: output one `<meta name="description">`.
- Do **not** echo a second `<title>` tag.

### Open Graph (when `enable_open_graph` is on)

On front page and on singular `post` / `page` / `sch_campaign` / `sch_offer`:

| Tag | Value |
|-----|--------|
| `og:title` | Resolved title (including WP fallbacks so OG is never blank if a title exists) |
| `og:description` | Resolved description when non-empty |
| `og:url` | Front page: `home_url( '/' )`. Singular: permalink |
| `og:type` | Front page: `website`. Singular: `article` |
| `og:site_name` | `blogname` |
| `og:image` | Featured image URL when present (singular / static front page only) |

### Existing SEOService behavior to keep

- Canonical / robots / keywords output if those post meta keys are already set (no new UI)
- SEO score helpers unchanged

### Settings read

Read nested `seo_campaign_hub_options` the same way as other services (`'1'` = on for checkboxes). `enable_meta_tags` and `enable_open_graph` default to on when the key is missing.

## Admin implementation

### Metabox

Follow the Web Push metabox pattern in `AdminInit`:

- `add_meta_boxes` → register for the four post types
- `save_post` → verify nonce, skip autosave/revisions, `current_user_can( 'edit_post', $post_id )`, restrict to the four types
- Empty submitted values: `delete_post_meta` so fallbacks work
- Nonce action: `sch_seo_metabox`

### Character counter

Enqueue a small script only on `post.php` / `post-new.php` for those post types. Count Unicode code points (not bytes). Soft visual warning when over 60 / 160; still save.

### Settings fields

Add to `Settings::define_fields()` section `seo`:

- `homepage_meta_title` — type `text`, default `''`
- `homepage_meta_description` — type `textarea`, default `''`

Sanitize via existing `text` / `textarea` handlers, then apply the same 200 / 500 caps.

Import/Export already includes `seo_campaign_hub_options`; no whitelist change required.

## Files

| File | Role |
|------|------|
| `src/Services/SeoMetaResolver.php` | Pure title/description resolution (unit-tested, no WP I/O) |
| `src/Services/SEOService.php` | `pre_get_document_title`, meta description, OG; use resolver + options |
| `src/Admin/AdminInit.php` | Metabox register / render / save; enqueue counter |
| `src/Admin/Settings.php` | Homepage title + description fields |
| `assets/admin/js/seo-metabox.js` | Character counts |
| `tests/Services/SeoMetaResolverTest.php` | Resolver cases (front posts, static front, singular, empty fallbacks) |
| `phpunit.xml` | PHPUnit config if missing (plugin has `composer test` but no config yet) |
| `readme.md` | Settings → SEO + metabox usage |
| `changelog.md` | 1.1.24 entry |
| `src/Admin/Views/help.php` | Short “SEO title & meta” note |
| `seo-campaign-hub.php` | Version bump to **1.1.24** |

## Security

- Metabox: nonce + `edit_post`
- Settings: existing `manage_options` Settings API
- Escape all output: `esc_attr`, `esc_html`, `esc_url`
- Sanitize on save: `sanitize_text_field` / `sanitize_textarea_field`

## Testing

No WordPress bootstrap required for resolver tests.

Cover at least:

1. Latest-posts homepage uses homepage fields then site name/tagline
2. Static front page: page meta wins over homepage settings
3. Static front page: empty page meta uses homepage settings
4. Singular: custom meta wins; empty uses post title / excerpt
5. Singular never uses homepage fields
6. Empty description yields empty string (caller skips the meta tag)

Manual after implementation:

- Edit a post: set title + description → view source
- Clear fields → title/excerpt fallback
- Settings homepage fields → front page source
- Static front page metabox override
- Toggle Meta Tags / Open Graph off
- Campaign and Offer editors show the same metabox

## Docs copy (readme Settings → SEO)

- Schema markup
- Meta tags
- Open Graph tags for social sharing
- **Homepage SEO Title** and **Homepage Meta Description**
- Per post/page/campaign/offer: SEO metabox (title + description)
- Block core directory listings (unchanged)
