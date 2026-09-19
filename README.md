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

CSRF tokens, nonces and inline `<svg>` blocks are ignored when hashing, so a randomly rotated decorative graphic does not count as a change. List regular expressions for anything else that varies between requests (a timestamp, a random banner) in `seo.indexnow.hash_ignore`.

Pages are fetched over HTTP from their public URL, so the command works even when run as a user that cannot write to `storage/` (for example a locked-down deploy user). Redirecting and non-200 pages are skipped. Use `seo.indexnow.exclude` to skip URL patterns.

## Configuration

See [`config/seo.php`](config/seo.php); every option is commented. The environment variables are `GOOGLE_ANALYTICS_MEASUREMENT_ID`, `GOOGLE_ANALYTICS_ENABLED`, `INDEXNOW_KEY`, `INDEXNOW_ENABLED`, `INDEXNOW_HOST` and `INDEXNOW_ENDPOINT`.

## Testing

```bash
composer test
```

## License

MIT
