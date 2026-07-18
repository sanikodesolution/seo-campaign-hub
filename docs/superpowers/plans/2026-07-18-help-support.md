# Help & Support Implementation Plan

> **For agentic workers:** Execute task-by-task. Steps use checkbox syntax.

**Goal:** Ship a working Help & Support admin page.

**Architecture:** Gather safe diagnostics in `AdminInit::render_help()`, render them in `src/Admin/Views/help.php`.

**Tech Stack:** WordPress PHP admin views.

---

### Task 1: Wire diagnostics + help view

**Files:**
- Create: `src/Admin/Views/help.php`
- Modify: `src/Admin/AdminInit.php`

- [ ] Collect plugin/WP/PHP versions, table existence, Elementor status, shortener prefix
- [ ] Render quick start, guides, shortcodes, troubleshooting, status, quick links
- [ ] Syntax-check edited PHP files
