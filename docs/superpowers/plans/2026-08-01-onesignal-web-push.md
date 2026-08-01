# OneSignal Web Push Implementation Plan

> **For agentic workers:** Implement task-by-task. Steps use checkbox (`- [ ]`) syntax.

**Goal:** OneSignal Web Push for WordPress posts — soft prompt, auto on publish, manual send.

**Architecture:** `OneSignalWebPushService` owns options, REST send, SW route, front-end enqueue; `AdminInit` adds Web Push page + post metabox; front JS loads OneSignal SDK v16.

**Tech Stack:** WordPress PHP 8.2+, OneSignal Web SDK v16, `wp_remote_post` to `https://api.onesignal.com/notifications`.

## Global Constraints

- Posts only; App ID public; REST key server-only; `manage_options`.
- Spec: `docs/superpowers/specs/2026-08-01-onesignal-web-push-design.md`.

---

### Task 1: OneSignalWebPushService

**Files:** Create `src/Services/OneSignalWebPushService.php`; Modify `src/Core/Plugin.php`.

- [ ] Service: `is_enabled()`, getters, `send_notification()`, `send_for_post()`, `maybe_auto_notify()`, `enqueue_frontend()`, `serve_service_worker()`, `init()` hooks
- [ ] Register singleton + `init()` in Plugin

### Task 2: Admin UI

**Files:** Create `src/Admin/Views/web-push.php`; Modify `src/Admin/AdminInit.php`.

- [ ] Submenu Web Push; settings + manual send handlers
- [ ] Post metabox: skip auto + send now

### Task 3: Front assets + docs

**Files:** `assets/public/js/onesignal-init.js`; `assets/public/css/onesignal-prompt.css`; bump version; `changelog.md`; `readme.md`.

- [ ] Soft prompt + OneSignal.init
- [ ] Docs for setup
