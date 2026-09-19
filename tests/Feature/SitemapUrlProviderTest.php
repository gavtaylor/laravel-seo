<?php

declare(strict_types=1);

use GavTaylor\Seo\IndexNow\Contracts\UrlProvider;
use GavTaylor\Seo\IndexNow\UrlProviders\SitemapUrlProvider;
use Illuminate\Support\Facades\Route;

it('reads urls from the sitemap package when it is installed', function () {
    Route::get('/hello', fn () => 'hi')->name('hello');

    $urls = app(UrlProvider::class)->urls();

    expect(app(UrlProvider::class))->toBeInstanceOf(SitemapUrlProvider::class)
        ->and(collect($urls)->contains(fn (string $url): bool => str_ends_with($url, '/hello')))->toBeTrue();
});
