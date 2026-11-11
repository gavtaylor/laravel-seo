<?php

declare(strict_types=1);

use GavTaylor\Seo\IndexNow\ContentHasher;

it('ignores csrf tokens, nonces and whitespace', function () {
    $a = '<meta name="csrf-token" content="aaa"><p>Hello   world</p><script nonce="x1">1</script><input type="hidden" name="_token" value="aaa">';
    $b = '<meta name="csrf-token" content="bbb"><p>Hello world</p><script nonce="y2">1</script><input type="hidden" name="_token" value="bbb">';

    expect((new ContentHasher)->hash($a))->toBe((new ContentHasher)->hash($b));
});

it('changes when the visible content changes', function () {
    $hasher = new ContentHasher;

    expect($hasher->hash('<p>One</p>'))->not->toBe($hasher->hash('<p>Two</p>'));
});

it('ignores inline svg, which sites often rotate at random', function () {
    $a = '<p>Hello</p><svg viewBox="0 0 1 1"><path d="M0 0"/></svg>';
    $b = '<p>Hello</p><svg viewBox="0 0 9 9"><path d="M9 9"/><g/></svg>';

    expect((new ContentHasher)->hash($a))->toBe((new ContentHasher)->hash($b));
});

it('ignores cache-busting query strings on stylesheets and scripts', function () {
    $a = '<link rel="stylesheet" href="/css/app.css?v=1700000000"><script src="/js/app.js?id=abc123"></script><p>Hello</p>';
    $b = '<link rel="stylesheet" href="/css/app.css?v=1700009999"><script src="/js/app.js?id=def456"></script><p>Hello</p>';

    expect((new ContentHasher)->hash($a))->toBe((new ContentHasher)->hash($b));
});

it('ignores the content hash vite adds to built asset filenames', function () {
    $a = '<link rel="stylesheet" href="/build/assets/app-BHt8xL2a.css"><script type="module" src="/build/assets/app-Dk3_9qZr.js"></script><p>Hello</p>';
    $b = '<link rel="stylesheet" href="/build/assets/app-C7mPq1-X.css"><script type="module" src="/build/assets/app-a1B2c3D4.js"></script><p>Hello</p>';

    expect((new ContentHasher)->hash($a))->toBe((new ContentHasher)->hash($b));
});

it('still notices when a page links a different asset', function () {
    $hasher = new ContentHasher;

    expect($hasher->hash('<link rel="stylesheet" href="/css/app.css?v=1">'))
        ->not->toBe($hasher->hash('<link rel="stylesheet" href="/css/print.css?v=1">'));
});

it('leaves query strings on page links alone', function () {
    $hasher = new ContentHasher;

    expect($hasher->hash('<a href="/events?month=1">Events</a>'))
        ->not->toBe($hasher->hash('<a href="/events?month=2">Events</a>'));
});

it('strips extra patterns listed in seo.indexnow.hash_ignore', function () {
    config()->set('seo.indexnow.hash_ignore', ['/<time\b.*?<\/time>/is']);

    $a = '<p>Hi</p><time>10:01</time>';
    $b = '<p>Hi</p><time>10:02</time>';

    expect((new ContentHasher)->hash($a))->toBe((new ContentHasher)->hash($b));
});

/**
 * A page shaped like a typical Blade layout, with the parts a test wants to
 * vary passed in.
 */
function layoutPage(array $parts = []): string
{
    $parts += [
        'title' => 'About us',
        'head' => '',
        'nav' => '<a href="/">Home</a>',
        'article_header' => '<h1>About us</h1><time>1 May</time>',
        'body' => '<p>We are a community group.</p>',
        'json_ld' => '{"@type":"Organization","name":"Example"}',
        'footer' => '<p>Registered charity</p>',
        'scripts' => '',
    ];

    return <<<HTML
        <!doctype html>
        <html>
        <head>
            <title>{$parts['title']}</title>
            {$parts['head']}
            <script type="application/ld+json">{$parts['json_ld']}</script>
        </head>
        <body>
            <header class="site-header"><nav>{$parts['nav']}</nav></header>
            <main>
                <article>
                    <header>{$parts['article_header']}</header>
                    {$parts['body']}
                </article>
            </main>
            <footer>{$parts['footer']}</footer>
            {$parts['scripts']}
        </body>
        </html>
        HTML;
}

/**
 * Layout edits that should not count as a content change once
 * seo.indexnow.ignore_layout is on.
 */
dataset('layout changes', [
    'site header' => [['nav' => '<a href="/">Home</a><a href="/news">News</a>']],
    'site footer' => [['footer' => '<p>Registered charity</p><a href="/privacy" data-ga="footer">Privacy</a>']],
    'inline script' => [['scripts' => '<script>document.addEventListener("click", () => gtag("event", "click"))</script>']],
    'analytics tag in head' => [['head' => '<script async src="https://www.googletagmanager.com/gtag/js?id=G-1"></script><script>gtag("config", "G-1")</script>']],
]);

it('ignores layout header, footer and script changes when ignore_layout is on', function (array $change) {
    config()->set('seo.indexnow.ignore_layout', true);
    $hasher = new ContentHasher;

    expect($hasher->hash(layoutPage($change)))->toBe($hasher->hash(layoutPage()));
})->with('layout changes');

it('ignores layout changes by default', function () {
    $hasher = new ContentHasher;

    expect(config('seo.indexnow.ignore_layout'))->toBeTrue()
        ->and($hasher->hash(layoutPage(['nav' => '<a href="/news">News</a>'])))->toBe($hasher->hash(layoutPage()));
});

it('counts layout header, footer and script changes when ignore_layout is off', function (array $change) {
    config()->set('seo.indexnow.ignore_layout', false);
    $hasher = new ContentHasher;

    expect($hasher->hash(layoutPage($change)))->not->toBe($hasher->hash(layoutPage()));
})->with('layout changes');

/**
 * Edits that are content and must still count with ignore_layout on.
 */
dataset('content changes', [
    'article header' => [['article_header' => '<h1>About the association</h1><time>1 May</time>']],
    'main body' => [['body' => '<p>We are a community association.</p>']],
    'json-ld structured data' => [['json_ld' => '{"@type":"Organization","name":"Renamed"}']],
    'head title' => [['title' => 'About the association']],
    'meta description' => [['head' => '<meta name="description" content="New summary">']],
]);

it('still counts content changes when ignore_layout is on', function (array $change) {
    config()->set('seo.indexnow.ignore_layout', true);
    $hasher = new ContentHasher;

    expect($hasher->hash(layoutPage($change)))->not->toBe($hasher->hash(layoutPage()));
})->with('content changes');

it('keeps a header inside main that is not in an article', function () {
    config()->set('seo.indexnow.ignore_layout', true);
    $hasher = new ContentHasher;

    expect($hasher->hash('<main><header><h1>Events</h1></header></main>'))
        ->not->toBe($hasher->hash('<main><header><h1>News</h1></header></main>'));
});

it('keeps an article header on a page without a main element', function () {
    config()->set('seo.indexnow.ignore_layout', true);
    $hasher = new ContentHasher;

    expect($hasher->hash('<header>Site</header><article><header><h1>One</h1></header></article>'))
        ->not->toBe($hasher->hash('<header>Site</header><article><header><h1>Two</h1></header></article>'));
});

it('does not treat custom elements as layout headers or footers', function () {
    config()->set('seo.indexnow.ignore_layout', true);
    $hasher = new ContentHasher;

    expect($hasher->hash('<header-nav>One</header-nav><footer-links>A</footer-links>'))
        ->not->toBe($hasher->hash('<header-nav>Two</header-nav><footer-links>A</footer-links>'))
        ->and($hasher->hash('<footer-links>A</footer-links>'))
        ->not->toBe($hasher->hash('<footer-links>B</footer-links>'));
});
