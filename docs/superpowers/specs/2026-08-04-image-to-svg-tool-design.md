# Public Image → SVG Tool Design

**Date:** 2026-08-04  
**Plugin:** SEO Campaign Hub  
**Status:** Approved — implementing

## Goal

Public URL (no WP login) to convert PNG/JPG to SVG in the browser.

## Modes

1. **Wrap in SVG (default)** — embed raster as base64 `<image>` inside SVG  
2. **Vectorize** — client-side posterize + run-length rects for simple logos/icons  

## Access

- Rewrite: `/tools/image-to-svg/`  
- Shortcode: `[sch_image_to_svg]`  
- Setting: `enable_image_to_svg` (default on)

## Constraints

- Client-side only (no server upload)  
- Max ~5MB  
- PNG/JPG/WebP input  
