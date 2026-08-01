# Admin Social Share (Posts List) Design

**Status:** Approved for planning (awaiting user review of this written spec)  
**Date:** 2026-08-01  
**Plugin:** SEO Campaign Hub  
**Direction:** Browser-based share links in wp-admin Posts list row actions — no App ID / OAuth

## Goals

Let admins share a normal WordPress **post** to social networks from **Posts → All Posts** using icon row-actions under the title. Sharing uses public browser share URLs (and clipboard for Copy link). No Facebook/Pinterest API keys, no connected social accounts, no auto-posting.

## Decisions (locked)

| Decision | Choice |
|----------|--------|
| Surface | wp-admin only |
| Content types | Normal `post` only |
| UI placement | Row actions under the title (not a Share column, not editor metabox) |
| Networks | Core set: Facebook, X, LinkedIn, Pinterest, WhatsApp, Email, Copy link |
| Auth model | None — browser share intents / mailto / clipboard |
| Approach | Row actions with icons (approach B) |

## Non-goals

- Front-end visitor share buttons
- Campaigns / Offers / pages / other CPTs
- Blog2Social-style OAuth, scheduling, or multi-account posting
- Bulk “share selected posts”
- Auto-share on publish
- Analytics of social clicks from these buttons (optional later)

## UX

On `edit.php?post_type=post`, each eligible row’s `.row-actions` gains share icon links after the core actions (Edit | Quick Edit | Trash | View | …).

- Icons for: Facebook, X (Twitter), LinkedIn, Pinterest, WhatsApp, Email, Copy link  
- Network icons open in a **new tab** (`target="_blank"` + `rel="noopener noreferrer"`)  
- Copy link copies the permalink via admin JS and shows a brief “Copied!” affordance  
- Only show when the post has a usable public permalink (published or otherwise publicly viewable). Hide for pure drafts/private when no meaningful public URL exists  
- Users must be able to edit the post (`current_user_can( 'edit_post', $post_id )`) to see actions  

## Share URL builders

Built from `get_permalink( $post_id )`, `get_the_title( $post_id )`, and featured image URL when available (Pinterest `media`).

| Network | URL pattern (conceptual) |
|---------|--------------------------|
| Facebook | `https://www.facebook.com/sharer/sharer.php?u={url}` |
| X | `https://x.com/intent/tweet?url={url}&text={title}` |
| LinkedIn | `https://www.linkedin.com/sharing/share-offsite/?url={url}` |
| Pinterest | `https://www.pinterest.com/pin/create/button/?url={url}&description={title}&media={image}` |
| WhatsApp | `https://api.whatsapp.com/send?text={title}%20{url}` |
| Email | `mailto:?subject={title}&body={url}` |
| Copy link | Client-side `navigator.clipboard.writeText(url)` with fallback |

All query values URL-encoded. No third-party SDKs.

## Settings

Add a **Social Share** section under SEO Campaign Hub → Settings:

- **Enable admin post share actions** (default: on)  
- Optional checklist: which of the seven actions to show (default: all on)  

Store under existing `seo_campaign_hub_options` (same pattern as other settings). No separate OAuth options.

## Technical approach

### Files (expected)

| File | Role |
|------|------|
| `src/Services/SocialShareService.php` | Network definitions + URL builders for a post ID |
| `src/Admin/AdminInit.php` (or small dedicated admin class) | `post_row_actions` filter; enqueue assets on posts list |
| `src/Admin/Settings.php` | Social Share section + fields |
| `assets/admin/css/admin.css` | Icon styling in `.row-actions` |
| `assets/admin/js/admin.js` | Copy-link handler |
| `readme.md` / `changelog.md` | Document feature |

### Hooks

- `post_row_actions` — append share actions for `post` when enabled  
- `admin_enqueue_scripts` — load CSS/JS only when `$hook_suffix === 'edit.php'` and post type is `post`  
- Settings registration via existing Settings API  

### Accessibility / WP norms

- Each action has an accessible name (`aria-label` / screen-reader text), not icon-only without label  
- Escape URLs and titles; use `esc_url`, `esc_attr`, `esc_html`  
- Prefer Dashicons or simple inline SVG icons consistent with wp-admin  

### Testing checklist

- Published post: all enabled icons appear and open correct share pages  
- Draft without public URL: share actions hidden  
- Feature disabled in Settings: no share actions  
- Copy link works in modern browsers  
- Non-post types unchanged  
- Users without `edit_post` do not see actions  

## Implementation order

1. `SocialShareService` + unit-style URL builder sanity checks  
2. Settings section (enable + network checklist)  
3. `post_row_actions` UI + CSS icons  
4. Copy-link JS  
5. Docs  

## Out of scope for v1

- Editor sidebar panel  
- Share column  
- Front-end shortcode  
- Auto-share on publish  
- Custom networks beyond the core set  

## References

- Existing OG settings already help preview cards when networks scrape the URL  
- Distinct from Blog2Social (no API posting)
