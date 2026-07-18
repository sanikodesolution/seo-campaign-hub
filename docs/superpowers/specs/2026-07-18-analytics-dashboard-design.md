# Analytics Dashboard Design

## Goal

Replace the Analytics admin placeholder with a working, server-rendered
analytics dashboard and repair the event pipeline so the dashboard displays
real data.

## Scope

This release provides a production-safe MVP:

- Track page views, clicks, scroll depth, time on page, and conversions.
- Attribute events to posts, campaigns, offers, and short links when known.
- Show 7, 30, and 90 day dashboard views.
- Display page views, unique visitors, sessions, clicks, conversions, and
  conversion rate.
- Display daily trends, event breakdown, and top campaigns, offers, and links.
- Show whether analytics collection is enabled.

Interactive chart libraries, CSV export, arbitrary custom filters, and data
purge controls are outside this release.

## Architecture

The existing `AnalyticsService` remains the single owner of event writes and
analytics queries.

Frontend events flow through the existing WordPress AJAX endpoint. The endpoint
validates and sanitizes the payload, applies a per-IP request limit, normalizes
event names, and delegates to `AnalyticsService::track_event()`.

The Analytics admin page obtains bounded date-range input and aggregated data
from `AnalyticsService`. The view renders HTML and CSS only, avoiding a new
charting or build dependency.

## Event Collection

`track_event()` will write only columns present in the `sch_analytics` activation
schema. Unknown and frontend-specific event names will be normalized:

- `external_link` and `email_link` become `click`.
- `scroll_bottom` becomes `scroll`.
- QR and redirect activity become `click`.
- Batched frontend payloads are unpacked and each valid event is recorded
  separately; `batch` is never inserted as an event type.

Unavailable foreign IDs are stored as `NULL`, not zero. Event values, URLs,
UTM values, IDs, device data, and metadata are sanitized and size-bounded before
insertion.

Frontend tracking will use one localized JavaScript object name consistently,
load its required dependencies, preserve the tracker instance in callbacks,
and send schema-compatible field names. The original page URL supplies UTM and
landing-page data.

## Privacy and Abuse Controls

- Respect the plugin's analytics-enabled, bot-filtering, IP-anonymization, and
  event-specific tracking settings.
- Accept only known event types.
- Require the public tracking nonce.
- Rate-limit anonymous tracking submissions per IP without blocking normal
  page browsing.
- Do not expose raw IP addresses, visitor IDs, or user-agent strings on the
  dashboard.

## Dashboard

`src/Admin/Views/analytics.php` will provide:

1. A status notice when analytics is disabled.
2. Date presets for 7, 30, and 90 days.
3. Metric cards for page views, unique visitors, sessions, clicks,
   conversions, and conversion rate.
4. A daily trend rendered as an accessible table with CSS bars.
5. Event-type totals.
6. Top campaigns, offers, and short links, with edit/detail links where the
   referenced record exists.
7. An empty state explaining how to generate the first event.

All dashboard input is allowlisted and bounded. Dynamic SQL ordering and
direction are allowlisted before interpolation.

## Settings Compatibility

Analytics runtime reads will support the currently installed standalone options
and the nested settings option. Existing installations keep their saved values.
The retention option naming mismatch is normalized without deleting old options.

## Error Handling

- A missing analytics table shows an actionable admin notice instead of a
  fatal error.
- Failed event inserts return a generic failure response and log the database
  error only when WordPress debugging is enabled.
- Malformed events are rejected without affecting the visitor's page.
- Dashboard query failures return empty datasets and a visible admin warning.
- Disabling analytics stops new collection but preserves and displays existing
  records.

## Verification

Verification will cover:

- PHP syntax and repository linting for edited files.
- Valid and invalid event payload handling.
- Page-view, click, scroll, time, and conversion insertion compatibility with
  the activation schema.
- Correct 7, 30, and 90 day aggregates.
- Empty database and analytics-disabled dashboard states.
- Safe handling of invalid date-range and ordering input.
- Frontend script loading and localized configuration consistency.
- No regression to short-link redirects or their click counters.

