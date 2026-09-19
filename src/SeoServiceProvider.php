<?php

declare(strict_types=1);

namespace GavTaylor\Seo;

use GavTaylor\Seo\IndexNow\Console\SubmitCommand;
use GavTaylor\Seo\IndexNow\Contracts\UrlProvider;
use GavTaylor\Seo\IndexNow\UrlProviders\ConfigUrlProvider;
use GavTaylor\Seo\IndexNow\UrlProviders\SitemapUrlProvider;
use GavTaylor\Sitemap\SitemapCache;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/seo.php', 'seo');

        $this->app->bind(UrlProvider::class, function (Application $app): UrlProvider {
            $custom = config('seo.indexnow.url_provider');

            if (is_string($custom) && $custom !== '') {
                /** @var UrlProvider */
                return $app->make($custom);
            }

            return class_exists(SitemapCache::class)
                ? $app->make(SitemapUrlProvider::class)
                : $app->make(ConfigUrlProvider::class);
        });
    }

    public function boot(): void
    {
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'seo');

        if ($this->app->runningInConsole()) {
            $this->commands([SubmitCommand::class]);

            $this->publishes([
                __DIR__.'/../config/seo.php' => config_path('seo.php'),
            ], 'seo-config');
        }

        $this->bootIndexNow();
    }

    /**
     * IndexNow only exists on a site once it has a key: the table, the key
     * file route and the command's work all depend on it.
     */
    private function bootIndexNow(): void
    {
        $key = config('seo.indexnow.key');

        if (! is_string($key) || preg_match('/^[A-Za-z0-9-]{8,128}$/', $key) !== 1) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Route::get('/'.$key.'.txt', fn () => response($key, 200, ['Content-Type' => 'text/plain; charset=UTF-8']))
            ->name('seo.indexnow.key');
    }
}
