<?php

declare(strict_types=1);

namespace GavTaylor\Seo\IndexNow\UrlProviders;

use GavTaylor\Seo\IndexNow\Contracts\UrlProvider;

final class ConfigUrlProvider implements UrlProvider
{
    /**
     * @return list<string>
     */
    public function urls(): array
    {
        /** @var list<string> */
        return config('seo.indexnow.urls', []);
    }
}
