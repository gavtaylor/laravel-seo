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
