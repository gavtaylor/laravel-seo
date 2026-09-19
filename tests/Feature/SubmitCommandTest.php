<?php

declare(strict_types=1);

use GavTaylor\Seo\IndexNow\UrlProviders\ConfigUrlProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    pageA('<p>A</p>');
    config()->set('seo.indexnow.url_provider', ConfigUrlProvider::class);
    config()->set('seo.indexnow.urls', ['https://example.test/a', 'https://example.test/b']);
    config()->set('seo.indexnow.endpoint', 'https://api.indexnow.org/IndexNow');
});

function fakePages(array $overrides = []): void
{
    Http::fake($overrides + [
        'example.test/a' => fn () => Http::response(pageA()),
        'example.test/b' => Http::response('<p>B</p>'),
        'api.indexnow.org/*' => Http::response('', 200),
    ]);
}

function pageA(?string $set = null): string
{
    static $html = '<p>A</p>';

    return $html = $set ?? $html;
}

it('submits every page on the first run and nothing on the second', function () {
    fakePages();

    $this->artisan('indexnow:submit')->expectsOutputToContain('Submitted 2 changed page(s)')->assertSuccessful();

    Http::assertSent(fn ($request) => $request->url() === 'https://api.indexnow.org/IndexNow'
        && $request['host'] === 'example.test'
        && $request['key'] === 'abcdef1234567890'
        && $request['keyLocation'] === 'https://example.test/abcdef1234567890.txt'
        && $request['urlList'] === ['https://example.test/a', 'https://example.test/b']);

    $this->artisan('indexnow:submit')->expectsOutputToContain('No changed pages')->assertSuccessful();
});

it('resubmits only the page whose content changed', function () {
    fakePages();
    $this->artisan('indexnow:submit')->assertSuccessful();

    pageA('<p>A changed</p>');
    fakePages(['api.indexnow.org/*' => Http::response('', 202)]);

    $this->artisan('indexnow:submit')->expectsOutputToContain('Submitted 1 changed page(s)')->assertSuccessful();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.indexnow.org')
        && $request['urlList'] === ['https://example.test/a']);
});

it('keeps the old hash and fails when the submission is rejected', function () {
    fakePages(['api.indexnow.org/*' => Http::response('', 429)]);

    $this->artisan('indexnow:submit')->expectsOutputToContain('HTTP 429')->assertFailed();

    expect(DB::table('indexnow_urls')->count())->toBe(0);
});

it('skips redirecting and missing pages', function () {
    fakePages(['example.test/a' => Http::response('', 301), 'example.test/b' => Http::response('', 404)]);

    $this->artisan('indexnow:submit')->expectsOutputToContain('No changed pages')->assertSuccessful();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.indexnow.org'));
});

it('does not contact anyone on a dry run', function () {
    fakePages();

    $this->artisan('indexnow:submit --dry-run')->expectsOutputToContain('Would submit https://example.test/a')->assertSuccessful();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.indexnow.org'));
    expect(DB::table('indexnow_urls')->count())->toBe(0);
});

it('resubmits unchanged pages with --all', function () {
    fakePages();
    $this->artisan('indexnow:submit')->assertSuccessful();

    $this->artisan('indexnow:submit --all')->expectsOutputToContain('Submitted 2 changed page(s)')->assertSuccessful();
});

it('checks only the given urls', function () {
    fakePages();

    $this->artisan('indexnow:submit --url=https://example.test/a')->expectsOutputToContain('Submitted 1 changed page(s)')->assertSuccessful();
});

it('honours exclude patterns', function () {
    config()->set('seo.indexnow.exclude', ['*/b']);
    fakePages();

    $this->artisan('indexnow:submit')->expectsOutputToContain('Submitted 1 changed page(s)')->assertSuccessful();
});

it('does nothing when disabled', function () {
    config()->set('seo.indexnow.enabled', false);
    fakePages();

    $this->artisan('indexnow:submit')->expectsOutputToContain('disabled')->assertSuccessful();

    Http::assertNothingSent();
});

it('fails without a key', function () {
    config()->set('seo.indexnow.key', null);

    $this->artisan('indexnow:submit')->expectsOutputToContain('No IndexNow key')->assertFailed();
});
