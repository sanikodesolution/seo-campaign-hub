# OneSignal Web Push Design

**Status:** Approved for implementation  
**Date:** 2026-08-01  
**Plugin:** SEO Campaign Hub  

## Goals

Add visitor Web Push via **OneSignal** (App ID + REST API Key): soft subscribe prompt on the front end; **auto-notify on publish** for normal `post`s; **manual send** from admin and the post editor.

## Decisions (locked)

| Decision | Choice |
|----------|--------|
| Provider | OneSignal (Web SDK + REST API) |
| Content types | Normal WordPress `post` only |
| Send modes | Auto on first publish + manual compose / per-post send |
| Prompt | Soft prompt, then browser permission |
| Auth | App ID public (front end); REST API Key server-only |

## Non-goals (v1)

- Campaigns / Offers / pages  
- Segments UI, A/B tests, scheduling beyond “send now”  
- Self-hosted VAPID / Firebase  
- Requiring the official OneSignal WP plugin  

## Admin

**SEO Campaign Hub → Web Push**

- Enable toggle  
- OneSignal App ID  
- REST API Key (password field; never printed to front end)  
- Auto-notify on publish (default on)  
- Soft prompt enable (default on)  
- Soft prompt delay (seconds, default 8)  
- Manual send form: title, message, URL  
- Setup notes: HTTPS, OneSignal Web platform (Custom Code), site URL  

**Post editor metabox**

- “Don’t notify on publish”  
- “Send push now” (published posts)  

## Front end

- Load OneSignal Web SDK v16 when enabled + App ID set  
- Soft prompt HTML/CSS/JS; on Allow → OneSignal permission / opt-in  
- Serve `/OneSignalSDKWorker.js` from the plugin (importScripts CDN SW) so root upload is optional  

## Sending

`POST https://api.onesignal.com/notifications` with `Authorization: Key {REST_API_KEY}`.

Payload: `app_id`, `target_channel: push`, `included_segments: ["All"]`, `headings`, `contents`, `url`, optional image.

Auto: `transition_post_status` to `publish` from non-publish; skip if disabled, “don’t notify”, or already sent for this publish cycle. Store `_sch_webpush_sent_at`.

## Security

- `manage_options` for settings and send  
- Nonces on forms  
- REST key only in options / server `wp_remote_post`  

## Files

- `src/Services/OneSignalWebPushService.php`  
- `src/Admin/Views/web-push.php`  
- `assets/public/js/onesignal-init.js`  
- `assets/public/css/onesignal-prompt.css`  
- AdminInit menu/handlers/metabox; Plugin register + enqueue + SW route  
- `readme.md` / `changelog.md`  
