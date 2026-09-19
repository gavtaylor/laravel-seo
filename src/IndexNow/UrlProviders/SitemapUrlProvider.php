<?php

declare(strict_types=1);

namespace GavTaylor\Seo\IndexNow\UrlProviders;

use GavTaylor\Seo\IndexNow\Contracts\UrlProvider;
use GavTaylor\Sitemap\SitemapCache;
use GavTaylor\Sitemap\SitemapUrl;

final class SitemapUrlProvider implements UrlProvider
{
    public function __construct(private readonly SitemapCache $sitemap)
    {
        //
    }

    /**
     * @return list<string>
     */
    public function urls(): array
    {
        return array_map(fn (SitemapUrl $url): string => $url->url, $this->sitemap->get());
    }
}
