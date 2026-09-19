<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Google Analytics
    |--------------------------------------------------------------------------
    |
    | Rendered by <x-seo::analytics /> in your layout's <head>. Nothing loads
    | unless a measurement ID is set. The gtag() function is always defined
    | (as a harmless queue) so inline click handlers that call it never throw,
    | but the Google script itself is only added when "enabled" is true.
    |
    | enabled:        null = production only. true/false to force.
    | exclude_routes: Route name patterns that are never tracked (Str::is).
    |
    */

    'analytics' => [
        'measurement_id' => env('GOOGLE_ANALYTICS_MEASUREMENT_ID'),
        'enabled' => env('GOOGLE_ANALYTICS_ENABLED'),
        'exclude_routes' => ['admin.*'],
    ],

    /*
    |--------------------------------------------------------------------------
    | IndexNow
    |--------------------------------------------------------------------------
    |
    | Active only when a key is set. See the README.
    |
    */

    'indexnow' => [
        /*
        |--------------------------------------------------------------------------
        | Enabled
        |--------------------------------------------------------------------------
        |
        | Whether indexnow:submit actually contacts the search engines. Left null,
        | it is enabled only in the production environment, so local and staging
        | copies of a site never tell Bing about URLs that are not live. Set
        | INDEXNOW_ENABLED=true/false to override.
        |
        */

        'enabled' => env('INDEXNOW_ENABLED'),

        /*
        |--------------------------------------------------------------------------
        | Key
        |--------------------------------------------------------------------------
        |
        | The IndexNow key (8-128 characters: letters, numbers and dashes) from
        | https://www.bing.com/indexnow/getstarted. It is served at
        | /{key}.txt so search engines can verify you own the host. The key is
        | public by design; it is not a secret.
        |
        */

        'key' => env('INDEXNOW_KEY'),

        /*
        |--------------------------------------------------------------------------
        | Host
        |--------------------------------------------------------------------------
        |
        | The host being submitted (no scheme). Defaults to the host of app.url.
        |
        */

        'host' => env('INDEXNOW_HOST'),

        /*
        |--------------------------------------------------------------------------
        | Endpoint
        |--------------------------------------------------------------------------
        |
        | api.indexnow.org shares each submission with every participating
        | search engine, so one request is enough.
        |
        */

        'endpoint' => env('INDEXNOW_ENDPOINT', 'https://api.indexnow.org/IndexNow'),

        /*
        |--------------------------------------------------------------------------
        | URL provider
        |--------------------------------------------------------------------------
        |
        | A class implementing GavTaylor\IndexNow\Contracts\UrlProvider. Left
        | null, the package uses gavtaylor/laravel-sitemap's route scan when that
        | package is installed, and the "urls" list below otherwise.
        |
        */

        'url_provider' => null,

        /*
        |--------------------------------------------------------------------------
        | URLs
        |--------------------------------------------------------------------------
        |
        | Absolute URLs to watch when no sitemap package is installed.
        |
        */

        'urls' => [],

        /*
        |--------------------------------------------------------------------------
        | Exclude
        |--------------------------------------------------------------------------
        |
        | URL patterns (Str::is wildcards) that are never fetched or submitted.
        |
        */

        'exclude' => [],

        /*
        |----------------------------------------------------------------------
        | Hash ignore
        |----------------------------------------------------------------------
        |
        | Regular expressions removed from a page's HTML before it is hashed,
        | for markup that changes between requests without the page really
        | changing (a random banner, a timestamp). CSRF tokens, nonces and
        | inline <svg> blocks are always ignored.
        |
        */

        'hash_ignore' => [],

        /*
        |--------------------------------------------------------------------------
        | Timeout
        |--------------------------------------------------------------------------
        |
        | Seconds to wait for each page fetch and for the submission request.
        |
        */

        'timeout' => 15,

    ],

];
