<?php

declare(strict_types=1);

namespace GavTaylor\Seo\IndexNow\Contracts;

interface UrlProvider
{
    /**
     * Absolute URLs of the pages to watch for changes.
     *
     * @return list<string>
     */
    public function urls(): array;
}
