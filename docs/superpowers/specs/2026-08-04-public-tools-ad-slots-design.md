# Public Tools Ad Slots Design

**Date:** 2026-08-04  
**Plugin:** SEO Campaign Hub  
**Status:** Approved — implementing

## Goal

Keep monetization slots on both public tool pages so the site owner can paste AdSense, affiliate, or custom company ad HTML.

## Decisions

- Positions: **Above** and **Below** the tool widget (2 slots)
- Content: **Shared** across Image → SVG and Public URL Shortener
- Scope: **Standalone `/tools/...` pages only** (shortcodes stay ad-free)

## Settings (`seo_campaign_hub_options`)

| Key | Type | Meaning |
|-----|------|---------|
| `tools_ad_above` | code (raw HTML/JS) | Markup above the tool |
| `tools_ad_below` | code (raw HTML/JS) | Markup below the tool |

Empty slots render nothing. AdSense loader can still live in Settings → Header Scripts.

## Out of scope

- Per-tool overrides
- Shortcode ads
- Built-in ad network integration beyond paste-your-HTML
