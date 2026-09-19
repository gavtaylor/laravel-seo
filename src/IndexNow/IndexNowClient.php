<?php

declare(strict_types=1);

namespace GavTaylor\Seo\IndexNow;

use Illuminate\Support\Facades\Http;

final class IndexNowClient
{
    /**
     * The most URLs the protocol accepts in one request.
     */
    public const int MAX_URLS_PER_REQUEST = 10000;

    /**
     * Submit URLs, returning the HTTP status (200 or 202 mean accepted).
     *
     * @param  list<string>  $urls
     */
    public function submit(array $urls): int
    {
        $key = (string) config('seo.indexnow.key');

        return Http::timeout((int) config('seo.indexnow.timeout', 15))
            ->acceptJson()
            ->asJson()
            ->post((string) config('seo.indexnow.endpoint'), [
                'host' => $this->host(),
                'key' => $key,
                'keyLocation' => 'https://'.$this->host().'/'.$key.'.txt',
                'urlList' => $urls,
            ])
            ->status();
    }

    public function host(): string
    {
        $host = config('seo.indexnow.host');

        if (is_string($host) && $host !== '') {
            return $host;
        }

        return (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    }
}
