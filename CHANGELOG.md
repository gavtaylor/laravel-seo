# Release Notes

## [Unreleased](https://github.com/gavtaylor/laravel-seo/compare/v0.3.0...main)

## [v0.3.0](https://github.com/gavtaylor/laravel-seo/compare/v0.2.0...v0.3.0) - 2026-11-16

### Added

- `<x-seo::analytics-opt-out />`, an on/off switch for a cookies page. It saves the visitor's choice in localStorage, stops the copy of Analytics already running on the page and deletes the `_ga` cookies. It takes the site's own markup as its slot and every message as an attribute.
- Both analytics components carry Laravel's Vite CSP nonce when one is set, or a `nonce` attribute, so they work under a Content-Security-Policy without `'unsafe-inline'`.

### Changed

- `<x-seo::analytics />` now loads Analytics for statistics only (`allow_google_signals` and `allow_ad_personalization_signals` off, configurable in `seo.analytics.gtag_config`), and doesn't load it at all when the browser sends Global Privacy Control (`seo.analytics.global_privacy_control`) or the visitor has opted out (`seo.analytics.opt_out_key`). Existing layouts need no change to get this.
- The Google script is now added by the inline script after those checks, not by a static `<script async src>` tag. A site with a Content-Security-Policy needs to let the inline script run (see the README).

## [v0.2.0](https://github.com/gavtaylor/laravel-seo/compare/v0.1.3...v0.2.0) - 2026-11-11

### Changed

- The IndexNow content hash now leaves out a site's shared layout chrome: `<header>` and `<footer>` elements outside `<main>` and `<article>`, and every `<script>` except JSON-LD. A nav, footer or analytics change no longer resubmits every page. Controlled by the new `seo.indexnow.ignore_layout` setting (`INDEXNOW_IGNORE_LAYOUT`), which is on by default.
- **Upgrading resubmits every page once**: the first `indexnow:submit` after upgrading sees every page as changed, because the stored hashes were made under the old rules.
- If your layout puts page content in a `<header>` outside `<main>` (a hero with the page's `<h1>`, say), set `INDEXNOW_IGNORE_LAYOUT=false` so changes to it still count. Turning it off also resubmits every page once.
