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

it('strips extra patterns listed in seo.indexnow.hash_ignore', function () {
    config()->set('seo.indexnow.hash_ignore', ['/<time\b.*?<\/time>/is']);

    $a = '<p>Hi</p><time>10:01</time>';
    $b = '<p>Hi</p><time>10:02</time>';

    expect((new ContentHasher)->hash($a))->toBe((new ContentHasher)->hash($b));
});
