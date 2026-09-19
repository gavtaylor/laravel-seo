<?php

declare(strict_types=1);

use GavTaylor\Seo\SeoServiceProvider;

it('keeps package defaults for nested keys the app does not override', function () {
    config(['seo' => ['analytics' => ['measurement_id' => 'G-APP'], 'indexnow' => ['exclude' => ['*/x']]]]);

    (new SeoServiceProvider(app()))->register();

    expect(config('seo.analytics.measurement_id'))->toBe('G-APP')
        ->and(config('seo.analytics.exclude_routes'))->toBe(['admin.*'])
        ->and(config('seo.indexnow.exclude'))->toBe(['*/x'])
        ->and(config('seo.indexnow.endpoint'))->toBe('https://api.indexnow.org/IndexNow');
});

it('replaces list values instead of merging them by index', function () {
    config(['seo' => ['analytics' => ['exclude_routes' => ['members.*']]]]);

    (new SeoServiceProvider(app()))->register();

    expect(config('seo.analytics.exclude_routes'))->toBe(['members.*']);
});
