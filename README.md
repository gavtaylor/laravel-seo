# Laravel SEO

One package for a Laravel site's SEO plumbing. Each feature switches on only when its tracking code or key is configured, so a site uses just the parts it has set up.

| Feature | Switched on by |
|---|---|
| [Google Analytics](#google-analytics) | `GOOGLE_ANALYTICS_MEASUREMENT_ID` |
| [IndexNow](#indexnow) | `INDEXNOW_KEY` |

It sits alongside [`gavtaylor/laravel-sitemap`](https://github.com/gavtaylor/laravel-sitemap), which stays a separate package. If both are installed, IndexNow uses the sitemap's route scan as its URL list.

## Installation

```bash
composer require gavtaylor/laravel-seo
php artisan migrate   # only needed if you use IndexNow
```

The package auto-registers. Publish the config if you want to edit it directly:

```bash
php artisan vendor:publish --tag=seo-config
```

## Google Analytics

```dotenv
GOOGLE_ANALYTICS_MEASUREMENT_ID=G-XXXXXXXXXX
```

Put the component in your layout's `<head>`:

```blade
<x-seo::analytics />
```

- The Google script only loads in **production** by default, and never on routes matching `seo.analytics.exclude_routes` (default `admin.*`). Set `GOOGLE_ANALYTICS_ENABLED=true` or `false` to override the environment check.
- `gtag()` is always defined as a harmless queue, so inline handlers like `onclick="gtag('event', ...)"` never throw when tracking is off.
- With no measurement ID the component outputs only that stub.

### Privacy defaults

Analytics loads for statistics only, and each visitor's browser has the last word:

- **Statistics only.** `gtag('config')` gets `allow_google_signals: false` and `allow_ad_personalization_signals: false`, so Google's cross-device, demographics and advertising features stay off. Change the parameters in `seo.analytics.gtag_config`.
- **Global Privacy Control.** A browser sending GPC (`navigator.globalPrivacyControl`) is treated as an opt-out and the Google script is never requested. Set `seo.analytics.global_privacy_control` to `false` to ignore it.
- **Opt-out.** A visitor who turns analytics off with the switch below gets `analytics-opt-out` = `1` in localStorage (key: `seo.analytics.opt_out_key`), and the Google script is never requested for them. The check runs in the browser, so it works on cached pages.

The script is added from an inline script after those checks, rather than with a static `<script src>` tag.

### The opt-out switch

Put this on your cookies page:

```blade
<x-seo::analytics-opt-out />
```

It renders a status line and a button, hidden until its script runs (so visitors without JavaScript, who aren't running Analytics anyway, don't see a dead button). The button saves or clears the opt-out, and on opting out it sets Google's own `window['ga-disable-<id>']` switch to stop the copy already running on the page, and deletes the `_ga` and `_ga_*` cookies at every domain level. When the browser sends GPC, the status says so and the button is hidden. The switch works even where Analytics isn't loaded (locally, say), so the choice can be made ahead of time.

To use your own markup, pass it as the slot. Keep an element with `data-analytics-status` (the status text goes here; give it `role="status"`) and a button with `data-analytics-toggle`. Attributes such as `class` go on the wrapper, whose id is `analytics-opt-out` unless you pass another.

```blade
<x-seo::analytics-opt-out class="my-6 rounded-lg border p-5">
    <h3>Analytics in this browser</h3>
    <p data-analytics-status role="status"></p>
    <button type="button" data-analytics-toggle>Turn analytics off</button>
</x-seo::analytics-opt-out>
```

Every message can be changed with an attribute: `gpc-message`, `off-message`, `on-message`, `turned-off-message`, `turned-on-message`, `unsaved-message` (the browser refused to save the choice), `turn-off-label` and `turn-on-label`.

### Content Security Policy

Both components are inline scripts. They carry Laravel's Vite CSP nonce (`Vite::useCspNonce()`) when one is set, so they run under a policy without `'unsafe-inline'`. Pass `nonce="..."` to either component to use a different one. The policy's `script-src` also needs `https://www.googletagmanager.com`, and `connect-src` needs Google Analytics' collection hosts.

## IndexNow

[IndexNow](https://www.bing.com/indexnow/getstarted) tells Bing, Yandex, Naver, Seznam and others when pages change, so you do not submit URLs by hand. Google does not support it. Nothing is added to your pages: the package makes one server-side API request and serves a small key file.

Changes are detected by hashing each page's **rendered HTML**, not by watching source files or keeping a content database. A Blade edit, a config value, or database-driven copy that changes a page all count as a change. Only new or changed pages are submitted, as IndexNow asks.

Generate a key at <https://www.bing.com/indexnow/getstarted> (letters, numbers and dashes, 8-128 characters):

```dotenv
INDEXNOW_KEY=your-key-here
```

The package serves the key at `/{key}.txt` itself, so there is no file to upload. The key is public by design. Then run `php artisan migrate` to create the `indexnow_urls` table.

```bash
php artisan indexnow:submit             # submit pages whose content changed
php artisan indexnow:submit --dry-run   # list what would be submitted
php artisan indexnow:submit --all       # resubmit everything
php artisan indexnow:submit --url=https://example.com/about
```

The first run submits every page and records its hash. Later runs submit only pages whose hash changed. If the submission fails, hashes are not saved, so the next run retries.

Run it after each deploy, and optionally on the scheduler to catch changes that happen without a deploy:

```php
// routes/console.php
Schedule::command('indexnow:submit')->daily();
```

The command only contacts search engines in the `production` environment. Set `INDEXNOW_ENABLED=true` or `false` to override.

### Where the URLs come from

If `gavtaylor/laravel-sitemap` is installed, its route scan is used, so there is no second list to maintain. Otherwise list absolute URLs in `seo.indexnow.urls`, or point `seo.indexnow.url_provider` at your own class implementing `GavTaylor\Seo\IndexNow\Contracts\UrlProvider`.

CSRF tokens, nonces and inline `<svg>` blocks are ignored when hashing, so a randomly rotated decorative graphic does not count as a change. So are cache-busting versions on stylesheets and scripts (a `?v=` or `?id=` query string on a `.css` or `.js` URL, and the hash Vite adds to built filenames), so rebuilding assets does not resubmit every page. List regular expressions for anything else that varies between requests (a timestamp, a random banner) in `seo.indexnow.hash_ignore`.

The site's shared layout chrome is ignored too, so a change to the site-wide nav, footer or analytics tags does not resubmit every page. That means `<header>` and `<footer>` elements that are not inside `<main>` or an `<article>`, and every `<script>` except `<script type="application/ld+json">` structured data, are left out of the hash. A header inside the page's `<main>` or an `<article>` (typically a post's title and date) still counts, as does everything else in `<head>`, such as the title and meta description. Custom elements such as `<header-nav>` are not treated as headers.

If your layout puts page content in a `<header>` outside `<main>` (a hero with the page's `<h1>`, say), turn this off so changes to it still count:

```dotenv
INDEXNOW_IGNORE_LAYOUT=false
```

Switching it either way changes how every page is hashed, so the next run resubmits every page once.

Pages are fetched over HTTP from their public URL, so the command works even when run as a user that cannot write to `storage/` (for example a locked-down deploy user). Redirecting and non-200 pages are skipped. Use `seo.indexnow.exclude` to skip URL patterns.

## Configuration

See [`config/seo.php`](config/seo.php); every option is commented. The environment variables are `GOOGLE_ANALYTICS_MEASUREMENT_ID`, `GOOGLE_ANALYTICS_ENABLED`, `INDEXNOW_KEY`, `INDEXNOW_ENABLED`, `INDEXNOW_HOST`, `INDEXNOW_ENDPOINT` and `INDEXNOW_IGNORE_LAYOUT`.

## Testing

```bash
composer test
```

## License

MIT
