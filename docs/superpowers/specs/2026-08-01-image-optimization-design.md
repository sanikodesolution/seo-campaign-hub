# Image Optimization (WebP) Design

**Status:** Approved for implementation  
**Date:** 2026-08-01  
**Plugin:** SEO Campaign Hub

## Decisions (locked)

| Decision | Choice |
|----------|--------|
| Scope | Auto on upload + bulk optimize existing Media Library images |
| Originals | Keep JPEG/PNG; create WebP alongside |
| Serving | Serve WebP when browser sends `Accept: image/webp` |
| UI | Dedicated admin page: settings + Bulk Optimize + progress |
| Approach | GD/Imagick self-hosted (no external API) |

## Non-goals

- TinyPNG / Cloudflare / paid APIs  
- Replacing originals in place  
- Full-site HTML cache / page cache (separate from this feature)  
- AVIF (v1)  

## Admin page

**SEO Campaign Hub → Image Optimization**

- Status: WebP engine available (Imagick or GD), counts, last bulk run  
- Settings: enable auto on upload, WebP quality (60–90, default 82), enable front-end WebP serving  
- Bulk Optimize: AJAX batches with progress; skip already optimized  

## Generation

- Hook `wp_generate_attachment_metadata` (and/or after upload) to create `.webp` for full + intermediate sizes for `image/jpeg` and `image/png`  
- Prefer Imagick WebP; fall back to GD `imagewebp`  
- Store meta `_sch_webp_files` map of relative paths; mark `_sch_webp_optimized` = 1  
- Bulk: query attachments without flag; process N per AJAX request  

## Serving

- Filter `wp_get_attachment_image_src`, `wp_calculate_image_srcset`, and/or `the_content`/`post_thumbnail_html` to swap to `.webp` when request Accept includes `image/webp` and file exists  
- If no WebP or non-supporting client → original URL  

## Security / capability

- `manage_options` for admin page and bulk AJAX  
- Nonces on AJAX; only process image attachments owned by Media Library  

## Files (expected)

- `src/Services/ImageOptimizationService.php`  
- Admin view + AdminInit menu/handlers  
- Options under `seo_campaign_hub_options`  
- Admin JS for bulk progress  
