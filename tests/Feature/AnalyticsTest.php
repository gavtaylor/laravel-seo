<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;

function renderAnalytics(string $component = '<x-seo::analytics />'): string
{
    return Blade::render($component);
}

beforeEach(function () {
    config()->set('seo.analytics.measurement_id', 'G-TEST123');
    config()->set('seo.analytics.enabled', true);
});

it('loads the gtag script and config when enabled', function () {
    expect(renderAnalytics())
        ->toContain("'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id)")
        ->toContain('var id = "G-TEST123";')
        ->toContain("gtag('config', id, ");
});

it('keeps analytics to statistics only by default', function () {
    expect(renderAnalytics())
        ->toContain('{"allow_google_signals":false,"allow_ad_personalization_signals":false}');
});

it('passes the configured gtag config through', function () {
    config()->set('seo.analytics.gtag_config', ['allow_google_signals' => true]);

    expect(renderAnalytics())->toContain("gtag('config', id, {\"allow_google_signals\":true});");

    config()->set('seo.analytics.gtag_config', []);

    expect(renderAnalytics())->toContain("gtag('config', id, {});");
});

it('skips loading when the visitor has opted out or sends Global Privacy Control', function () {
    expect(renderAnalytics())
        ->toContain('var optedOut = navigator.globalPrivacyControl === true;')
        ->toContain('window.localStorage.getItem("analytics-opt-out") === \'1\'')
        ->toContain("if (optedOut) { window['ga-disable-' + id] = true; return; }");
});

it('uses the configured opt-out key', function () {
    config()->set('seo.analytics.opt_out_key', 'no-stats');

    expect(renderAnalytics())->toContain('getItem("no-stats")');
});

it('can ignore Global Privacy Control', function () {
    config()->set('seo.analytics.global_privacy_control', false);

    expect(renderAnalytics())
        ->toContain('var optedOut = false;')
        ->not->toContain('globalPrivacyControl');
});

it('escapes the measurement id inside the script', function () {
    config()->set('seo.analytics.measurement_id', "G-1'</script><script>alert(1)");

    expect(renderAnalytics())
        ->not->toContain('</script><script>alert')
        ->toContain('\u003C/script\u003E');
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

it('adds the Vite CSP nonce to the script', function () {
    Vite::useCspNonce('abc123');

    expect(renderAnalytics())->toStartWith('<script nonce="abc123">');
    expect(renderAnalytics('<x-seo::analytics nonce="override" />'))->toContain('<script nonce="override">');
});

it('renders clean output with no stray blank lines', function () {
    expect(renderAnalytics())->toStartWith('<script>')->not->toContain("\n\n");

    config()->set('seo.analytics.enabled', false);

    expect(renderAnalytics())->toStartWith('<script>')->not->toContain("\n\n");
});

describe('opt-out switch', function () {
    it('renders a hidden switch with default markup and its script', function () {
        expect(renderAnalytics('<x-seo::analytics-opt-out />'))
            ->toContain('<div id="analytics-opt-out" hidden>')
            ->toContain('<p data-analytics-status role="status"></p>')
            ->toContain('<button type="button" data-analytics-toggle>Turn analytics off</button>')
            ->toContain('document.getElementById("analytics-opt-out")')
            ->toContain('var key = "analytics-opt-out";')
            ->toContain('var measurementId = "G-TEST123";')
            ->toContain("window['ga-disable-' + measurementId] = true;")
            ->toContain('clearAnalyticsCookies();');
    });

    it('renders even where analytics is not loaded, so the choice can be made ahead of time', function () {
        config()->set('seo.analytics.enabled', false);
        config()->set('seo.analytics.measurement_id', null);

        expect(renderAnalytics('<x-seo::analytics-opt-out />'))
            ->toContain('data-analytics-toggle')
            ->toContain('var measurementId = null;');
    });

    it('wraps the site\'s own markup and keeps its attributes', function () {
        $html = renderAnalytics(<<<'BLADE'
            <x-seo::analytics-opt-out class="my-6 rounded-lg">
                <h3>Analytics in this browser</h3>
                <p data-analytics-status role="status"></p>
                <button type="button" class="btn" data-analytics-toggle>Turn analytics off</button>
            </x-seo::analytics-opt-out>
            BLADE);

        expect($html)
            ->toContain('<div id="analytics-opt-out" class="my-6 rounded-lg" hidden>')
            ->toContain('<h3>Analytics in this browser</h3>')
            ->toContain('class="btn" data-analytics-toggle')
            ->and(substr_count($html, 'data-analytics-toggle'))->toBe(2);
    });

    it('takes custom wording', function () {
        expect(renderAnalytics('<x-seo::analytics-opt-out gpc-message="I treat it as an opt-out." turn-on-label="Back on" />'))
            ->toContain('"gpc":"I treat it as an opt-out."')
            ->toContain('"turnOn":"Back on"');
    });

    it('uses a custom id', function () {
        expect(renderAnalytics('<x-seo::analytics-opt-out id="stats-switch" />'))
            ->toContain('<div id="stats-switch" hidden>')
            ->toContain('document.getElementById("stats-switch")');
    });

    it('escapes wording inside the script', function () {
        expect(Blade::render('<x-seo::analytics-opt-out :off-message="$message" />', ['message' => '</script><b>']))
            ->not->toContain('</script><b>')
            ->toContain('\u003C/script\u003E\u003Cb\u003E');
    });

    it('adds the Vite CSP nonce', function () {
        Vite::useCspNonce('abc123');

        expect(renderAnalytics('<x-seo::analytics-opt-out />'))->toContain('<script nonce="abc123">');
    });
});
