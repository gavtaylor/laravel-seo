<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

function renderAnalytics(): string
{
    return Blade::render('<x-seo::analytics />');
}

beforeEach(function () {
    config()->set('seo.analytics.measurement_id', 'G-TEST123');
    config()->set('seo.analytics.enabled', true);
});

it('renders the gtag script and config when enabled', function () {
    expect(renderAnalytics())
        ->toContain('https://www.googletagmanager.com/gtag/js?id=G-TEST123')
        ->toContain("gtag('config', 'G-TEST123')");
});

it('renders only the harmless gtag stub without a measurement id', function () {
    config()->set('seo.analytics.measurement_id', null);

    expect(renderAnalytics())
        ->toContain('function gtag()')
        ->not->toContain('googletagmanager')
        ->not->toContain('G-TEST123');
});

it('does not load the script outside production by default', function () {
    config()->set('seo.analytics.enabled', null);

    expect(renderAnalytics())->toContain('function gtag()')->not->toContain('googletagmanager');
});

it('does not track excluded routes', function () {
    Route::get('/admin/thing', fn () => Blade::render('<x-seo::analytics />'))->name('admin.thing');

    $this->get('/admin/thing')->assertOk()->assertDontSee('googletagmanager', false)->assertSee('function gtag()', false);
});

it('tracks other named routes', function () {
    Route::get('/about', fn () => Blade::render('<x-seo::analytics />'))->name('about');

    $this->get('/about')->assertOk()->assertSee('googletagmanager', false);
});
