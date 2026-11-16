<?php

declare(strict_types=1);

namespace GavTaylor\Seo;

use Illuminate\Foundation\Vite;
use Illuminate\Support\Str;

/**
 * Decides whether the Google Analytics script should load for the
 * current request, and supplies the browser-side settings that decide
 * it for each visitor (their opt-out and Global Privacy Control).
 */
final class Analytics
{
    public function measurementId(): ?string
    {
        $id = config('seo.analytics.measurement_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function shouldTrack(): bool
    {
        if ($this->measurementId() === null) {
            return false;
        }

        $enabled = config('seo.analytics.enabled');

        if (! ($enabled ?? app()->isProduction())) {
            return false;
        }

        $route = request()->route()?->getName();

        return $route === null || ! Str::is((array) config('seo.analytics.exclude_routes', []), $route);
    }

    public function optOutKey(): string
    {
        $key = config('seo.analytics.opt_out_key');

        return is_string($key) && $key !== '' ? $key : 'analytics-opt-out';
    }

    public function honoursGlobalPrivacyControl(): bool
    {
        return (bool) config('seo.analytics.global_privacy_control', true);
    }

    /**
     * @return array<string, mixed>
     */
    public function gtagConfig(): array
    {
        $config = config('seo.analytics.gtag_config', []);

        /** @var array<string, mixed> */
        return is_array($config) ? $config : [];
    }

    /**
     * The CSP nonce Laravel's Vite helper is using, so the inline scripts
     * run under a strict Content-Security-Policy without 'unsafe-inline'.
     */
    public function nonce(): ?string
    {
        if (! class_exists(Vite::class)) {
            return null;
        }

        $nonce = app(Vite::class)->cspNonce();

        return is_string($nonce) && $nonce !== '' ? $nonce : null;
    }

    /**
     * A value written into an inline <script> as a JavaScript literal, with
     * the characters that could close the script tag escaped.
     */
    public function js(mixed $value): string
    {
        if ($value === []) {
            $value = (object) [];
        }

        return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
