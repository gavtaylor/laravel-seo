<?php

declare(strict_types=1);

namespace GavTaylor\Seo\IndexNow;

/**
 * Hashes a page's rendered HTML after removing the parts that change on
 * every request (CSRF tokens, nonces), that are purely decorative (inline
 * SVG, which sites often rotate at random), or that change on every asset
 * build without the content changing (CSS/JS cache-busting versions), so an
 * unchanged page always hashes the same and is never resubmitted. The site's
 * shared layout chrome is left out too, unless `seo.indexnow.ignore_layout`
 * is turned off. Extra regular expressions to strip can be listed in
 * `seo.indexnow.hash_ignore`.
 */
final class ContentHasher
{
    public function hash(string $html): string
    {
        $html = (string) preg_replace('/<meta\s+name=["\']csrf-token["\'][^>]*>/i', '', $html);
        $html = (string) preg_replace('/<input[^>]*name=["\']_token["\'][^>]*>/i', '', $html);
        $html = (string) preg_replace('/\snonce=["\'][^"\']*["\']/i', '', $html);
        $html = (string) preg_replace('/<svg\b.*?<\/svg>/is', '', $html);

        // Cache-busting on stylesheets and scripts changes every page's HTML
        // whenever the assets are rebuilt. Drop a query string on a CSS/JS
        // URL (?v={filemtime}, Laravel Mix's ?id={hash}) and the content hash
        // Vite adds to built filenames (/build/assets/app-BHt8xL2a.css).
        $html = (string) preg_replace('/(\.(?:css|js|mjs))\?[^"\'\s>]*/i', '$1', $html);
        $html = (string) preg_replace('#(/build/assets/[^"\'\s>?]+?)-[A-Za-z0-9_-]{8}(\.(?:css|js|mjs))#i', '$1$2', $html);

        if (config('seo.indexnow.ignore_layout', true) === true) {
            $html = $this->stripLayout($html);
        }

        /** @var list<string> $ignore */
        $ignore = config('seo.indexnow.hash_ignore', []);

        foreach ($ignore as $pattern) {
            $html = (string) preg_replace($pattern, '', $html);
        }

        $html = (string) preg_replace('/\s+/', ' ', $html);

        return hash('sha256', trim($html));
    }

    /**
     * Remove the layout's site-wide <header> and <footer> and every <script>
     * except JSON-LD structured data, so a nav, footer or analytics change
     * does not count as every page changing. A <header> or <footer> inside
     * <main> or an <article> (a post's title and date) is content, so those
     * blocks are set aside first and put back afterwards.
     */
    private function stripLayout(string $html): string
    {
        $html = (string) preg_replace('/<script\b(?![^>]*application\/ld\+json)[^>]*>.*?<\/script>/is', '', $html);

        /** @var list<string> $kept */
        $kept = [];

        $html = (string) preg_replace_callback(
            '/<(main|article)(?=[\s>]).*?<\/\1>/is',
            function (array $match) use (&$kept): string {
                $kept[] = $match[0];

                return "\x00".(count($kept) - 1)."\x00";
            },
            $html,
        );

        $html = (string) preg_replace('/<(header|footer)(?=[\s>]).*?<\/\1>/is', '', $html);

        return (string) preg_replace_callback(
            '/\x00(\d+)\x00/',
            fn (array $match): string => $kept[(int) $match[1]],
            $html,
        );
    }
}
