# Public URL Shortener Tool Design

**Date:** 2026-08-04  
**Plugin:** SEO Campaign Hub  
**Status:** Approved — implementing

## Goal

Public page (no WP login) where visitors paste a long URL and get a short `/go/{slug}` link. Same access pattern as Image → SVG.

## Guest capabilities

- Destination URL only (auto-generated slug)
- No custom slug, UTMs, or smart redirect rules

## Access

- Rewrite: `/tools/url-shortener/`
- Shortcode: `[sch_url_shortener]`
- Admin: SEO Campaign Hub → Public Shortener (enable, public URL, shortcode, embedded form)
- Settings → Public Tools: enable checkbox (+ same-site / rate-limit fields)

## Settings (`seo_campaign_hub_options`)

| Key | Default | Meaning |
|-----|---------|---------|
| `enable_public_url_shortener` | `1` | Public tool on/off |
| `public_shortener_same_site_only` | `0` | If `1`, destination host must match site host |
| `public_shortener_rate_limit` | `10` | Max creates per IP per hour |

Also requires core shortener enabled (`seo_campaign_hub_enable_shortener`).

## Abuse protection

- IP rate limit (transient counter)
- Honeypot field (must be empty)
- REST nonce (`wp_rest` / localized create nonce)
- Validate `http`/`https` only; optional same-site host check

## Data

- Reuse `ShortenerService::shorten_url()`
- `created_by = 0` marks guest-created links
- Appear in existing admin URL Shortener list with a **Public** badge

## Create API

`POST /wp-json/seo-campaign-hub/v1/public/shorten`  
Body: `{ "url": "...", "honeypot": "" }`  
Permission: public when tool enabled; rate-limited.

## Out of scope (v1)

- Captcha / Turnstile
- Guest custom slugs
- Separate public-links admin tab
