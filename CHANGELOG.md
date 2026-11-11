# Release Notes

## [Unreleased](https://github.com/gavtaylor/laravel-seo/compare/v0.2.0...main)

## [v0.2.0](https://github.com/gavtaylor/laravel-seo/compare/v0.1.3...v0.2.0) - 2026-11-11

### Changed

- The IndexNow content hash now leaves out a site's shared layout chrome: `<header>` and `<footer>` elements outside `<main>` and `<article>`, and every `<script>` except JSON-LD. A nav, footer or analytics change no longer resubmits every page. Controlled by the new `seo.indexnow.ignore_layout` setting (`INDEXNOW_IGNORE_LAYOUT`), which is on by default.
- **Upgrading resubmits every page once**: the first `indexnow:submit` after upgrading sees every page as changed, because the stored hashes were made under the old rules.
- If your layout puts page content in a `<header>` outside `<main>` (a hero with the page's `<h1>`, say), set `INDEXNOW_IGNORE_LAYOUT=false` so changes to it still count. Turning it off also resubmits every page once.
