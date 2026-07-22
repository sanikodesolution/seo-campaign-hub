# Smart Language / Country Redirect Design

## Goal

Add default multilanguage/country-aware redirects to the URL Shortener so one
short link (`/go/{slug}`) can send visitors to different WordPress posts/pages
based on browser language and/or IP country — covering Europe, Americas, and
Middle East language packs by default.

## Context

Operators use this plugin primarily for:

- URL shortening
- Country tracking / analytics

Landing content lives in normal WordPress posts/pages, not the plugin
`sch_campaign` CPT. Campaign CPT translation and GTranslate integration are
explicitly out of scope.

## Scope

### In scope (v1)

- Per-short-link smart redirect rules (language and/or country → destination URL)
- Per-link redirect priority: `language` or `country` (default: `language`)
- Global Localization settings with a curated default language pack
- Language detection from `Accept-Language`
- Country detection via existing geo/IP path used by analytics
- Persist visitor language on analytics click events
- Analytics dashboard section: traffic by language
- Migration for new `sch_links` columns and analytics language storage

### Out of scope (v1)

- Auto-translating WordPress post content
- GTranslate / DeepL / Google Translate API integration
- Plugin CPT (`sch_campaign` / `sch_offer`) multilingual content
- Localized short-link paths (e.g. `/go/bn/deal`)
- Separate short links per language as a required workflow

## Default language pack

Enabled on activation / as Localization defaults:

| Region | Codes |
|--------|-------|
| Americas | `en`, `es`, `pt`, `fr` |
| Europe | `en`, `de`, `fr`, `es`, `it`, `nl`, `pl`, `pt`, `ru` |
| Middle East | `ar`, `he`, `tr`, `fa`, `en` |

Unique core set (13):

`en`, `es`, `pt`, `fr`, `de`, `it`, `nl`, `pl`, `ru`, `ar`, `he`, `tr`, `fa`

Settings may enable/disable codes from this list. An extended pack can be added
later without changing the redirect engine.

## Architecture

Approach: **rules on the short link** (not a separate redirect-rules product).

```
Visitor hits /go/{slug}
  → ShortenerService::handle_redirect()
  → If smart redirects disabled → destination_url
  → Detect language (Accept-Language primary tag)
  → Detect country (existing geo lookup)
  → Evaluate targeting_rules by redirect_priority
  → First valid match → redirect URL
  → Else → destination_url
  → Track click with country, language, matched_rule_type
```

`ShortenerService` owns matching and redirect resolution.
`AnalyticsService` stores language on events and exposes language aggregates.
Admin shortener UI owns rule CRUD; Settings owns Localization defaults.

## Data model

### `sch_links` new columns

| Column | Type | Default | Purpose |
|--------|------|---------|---------|
| `redirect_priority` | `ENUM('language','country')` | `'language'` | Match order for this link |
| `targeting_rules` | `JSON` NULL | `NULL` | Ordered language/country rules |

### Rule shape

```json
[
  { "type": "language", "match": "ar", "url": "https://example.com/ar/deal/" },
  { "type": "language", "match": "es", "url": "https://example.com/es/deal/" },
  { "type": "country", "match": "SA", "url": "https://example.com/ar/deal/" },
  { "type": "country", "match": "BR", "url": "https://example.com/pt/deal/" }
]
```

Constraints:

- `type` is `language` or `country`
- `match` is a 2-letter lowercase language code or uppercase ISO country code
- `url` must be a valid absolute URL after sanitization
- Rules are evaluated in array order; first match wins

### Analytics

- Add `language CHAR(2) NULL` plus a `KEY language (language)` index to
  `sch_analytics` via migration (required for dashboard `GROUP BY`).
- On short-link clicks, record:
  - `country` (existing)
  - `language` (new column)
  - `matched_rule_type` in `meta_data`: `language` | `country` | `default`

## Matching logic

```
if smart redirects disabled OR no rules:
  return destination_url

primary = redirect_priority   // language | country
secondary = the other type

for each rule where type == primary:
  if rule matches visitor and url valid → return url

for each rule where type == secondary:
  if rule matches visitor and url valid → return url

return destination_url
```

Language match: primary language subtag from `Accept-Language` (e.g. `en-US` → `en`).
Country match: ISO-3166-1 alpha-2 from geo lookup.

## Settings (Localization)

New Settings section:

- Enable smart redirects (default: on)
- Default redirect priority: language | country (default: language)
- Enabled languages checklist (defaults = core pack above)
- Default fallback language code: `en`

These settings seed new-link defaults and constrain which language codes appear
in the shortener rule UI. Country codes remain free-form ISO-2 (validated).

## Admin UI (URL Shortener)

On create (and later edit if edit UI exists):

1. Existing destination URL = fallback
2. Redirect priority select (default from Settings)
3. Smart redirect rules table:
   - Type (language / country)
   - Match (language select from enabled pack, or country text input)
   - Destination URL
   - Remove row
4. Add rule button

Server-side: sanitize/validate rules before insert; drop invalid rows; store
JSON on the link row.

## Error handling

| Case | Behavior |
|------|----------|
| Feature disabled | Always `destination_url` |
| Empty / invalid rule URL | Skip rule |
| Missing Accept-Language | Skip language matches |
| Geo failure / private IP | Skip country matches |
| No rules | Current single-destination behavior |
| Malformed JSON | Treat as empty rules |
| Duplicate matches | First rule in list wins |

RTL (`ar`, `he`, `fa`) is handled by destination pages, not by the shortener.

## Testing checklist

1. Language-only rules: browser `ar` → Arabic URL
2. Country-only rules: country `SA` → Arabic URL
3. Priority language: language and country disagree → language URL
4. Priority country: language and country disagree → country URL
5. No match → default destination
6. Feature off → always default
7. Click event stores country, language, and `matched_rule_type`
8. Analytics shows language breakdown when data exists

## Implementation notes

- Prefer a DB migration version bump over editing only activator schema so
  existing installs gain columns safely.
- Reuse analytics geolocation where possible; avoid a second geo provider.
- Keep redirect path fast: cache link row as today; do not block redirect on
  remote geo longer than existing analytics tolerance (fail open to next match /
  fallback).
- Update `readme.md` with Localization + smart redirect docs after implementation.

## Success criteria

- One short link can target multiple WP landing URLs by language/country.
- Default EU / Americas / Middle East language pack is available without manual
  code edits.
- Priority is configurable per link (default language-first).
- Analytics retains country tracking and adds language visibility.
- Existing short links without rules keep current behavior.
