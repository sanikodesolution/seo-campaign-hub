# Front-end Code Optimization (Option A)

**Status:** Implemented in 1.1.14  
**Date:** 2026-08-08  
**Plugin:** SEO Campaign Hub

## Goal

Make Advanced settings actually speed up the public site without Autoptimize-style CSS/JS bundling (avoids Elementor/Owl breakage).

## Features (front end only)

| Toggle | Setting key | Behavior |
|--------|-------------|----------|
| Clean WordPress head | `clean_wp_head` | Remove emoji scripts/styles, generator, RSD, shortlink, extra feeds, oEmbed discovery, REST link in `<head>` |
| Defer JavaScript | `defer_scripts` (existing) | Add `defer` to **footer** scripts only; never jQuery, Elementor, or already async/defer |
| Minify HTML | `minify_assets` (existing, relabeled) | Collapse whitespace; preserve `script`/`style`/`pre`/`textarea`/`code` |
| Lazy-load media | `lazy_load_images` | Keep WP lazy-loading on for images and iframes |

Defaults: on. Skips `wp-admin`, login, AJAX, REST, cron, feeds, sitemaps, Elementor canvas/preview.

## Non-goals

- Combine/minify all theme CSS/JS files
- Naive regex minify of `.js` files
- Full-page HTML cache (use a cache plugin)
