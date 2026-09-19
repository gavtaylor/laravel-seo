<?php

declare(strict_types=1);

namespace GavTaylor\Seo\IndexNow;

/**
 * Hashes a page's rendered HTML after removing the parts that change on
 * every request (CSRF tokens, nonces) or that are purely decorative (inline
 * SVG, which sites often rotate at random) so an unchanged page always
 * hashes the same and is never resubmitted. Extra regular expressions to
 * strip can be listed in `seo.indexnow.hash_ignore`.
 */
final class ContentHasher
{
    public function hash(string $html): string
    {
        $html = (string) preg_replace('/<meta\s+name=["\']csrf-token["\'][^>]*>/i', '', $html);
        $html = (string) preg_replace('/<input[^>]*name=["\']_token["\'][^>]*>/i', '', $html);
        $html = (string) preg_replace('/\snonce=["\'][^"\']*["\']/i', '', $html);
        $html = (string) preg_replace('/<svg\b.*?<\/svg>/is', '', $html);

        /** @var list<string> $ignore */
        $ignore = config('seo.indexnow.hash_ignore', []);

        foreach ($ignore as $pattern) {
            $html = (string) preg_replace($pattern, '', $html);
        }

        $html = (string) preg_replace('/\s+/', ' ', $html);

        return hash('sha256', trim($html));
    }
}
