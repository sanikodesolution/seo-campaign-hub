# Public Image → SVG Tool Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans or implement task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a public no-login PNG/JPG→SVG converter at `/tools/image-to-svg/`.

**Architecture:** `ImageToSvgService` registers rewrite + shortcode; standalone public view; client-side JS does Wrap and Vectorize. Settings toggle enables the route.

**Tech Stack:** WordPress rewrites, vanilla JS/CSS, PHP 8.2+

## Global Constraints

- No WP login required for the public tool
- Conversion runs in browser only
- Default mode: Wrap in SVG; Vectorize optional
- Max upload ~5MB client-side

---

### Task 1: Service + rewrite + view

- [x] Create `ImageToSvgService`
- [x] Public view template
- [x] Wire Plugin container + init

### Task 2: Assets + settings + docs

- [x] Public CSS/JS
- [x] Settings checkbox + shortcode
- [x] Update readme.md
