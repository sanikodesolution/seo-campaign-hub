# Core Directory Index Protection Design

**Status:** Implemented in 1.1.13  
**Date:** 2026-08-08  
**Plugin:** SEO Campaign Hub

## Problem

Google Search Console “Duplicate without user-selected canonical” on URLs like:

`/wp-includes/SimplePie/library/SimplePie/HTTP/?SD`

These are Apache **directory listings** (autoindex sort params `NA`/`ND`/`MA`/`MD`/`SA`/`SD`), not WordPress posts. They should never be indexed.

A PHP hook alone cannot stop them: the web server serves the folder before WordPress boots.

## Approach

1. **robots.txt** (all hosts): `Disallow` SimplePie and other non-public `wp-includes` library folders. Does not disallow `/wp-includes/js|css|images|fonts`.
2. **Apache `.htaccess`**: `RewriteRule` → 403 for those same folders (files like jQuery stay public). No `Options` directive (avoids 500 on hosts that forbid `Options`).
3. **Setting** (Settings → SEO): **Block core directory listings** — default on. Uncheck to remove rules.

Nginx: robots.txt still applies; host must set `autoindex off` (documented in Help).

## Out of scope

- Writing files into `wp-includes/`
- Canonical tags on directory listings
- Changing Google Search Console (user re-validates after 403)
