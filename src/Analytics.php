<?php

declare(strict_types=1);

namespace GavTaylor\Seo;

use Illuminate\Support\Str;

/**
 * Decides whether the Google Analytics script should load for the
 * current request.
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
}
