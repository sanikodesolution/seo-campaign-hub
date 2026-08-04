# Public Tools Ad Slots Implementation Plan

> Completed with the feature in 1.1.9.

**Goal:** Shared Above/Below ad HTML on both standalone public tool pages.

**Architecture:** `PublicToolsAds` helper reads `tools_ad_above` / `tools_ad_below` from options; both `/tools/...` views call `PublicToolsAds::render()`. Shortcodes unchanged.

---

- [x] Design spec
- [x] `PublicToolsAds` helper
- [x] Settings fields (code type)
- [x] Wire Image → SVG + Public Shortener pages
- [x] Admin hints + CSS
- [x] Docs (readme, changelog, help)
