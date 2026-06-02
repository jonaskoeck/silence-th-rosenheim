<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;

class FlavorParser
{
    public static function hourlyCost(string $flavor): float
    {
        if (! preg_match('/^SCS-(\d+)[VL]-(\d+)(?:-\d+s)?$/i', $flavor, $m)) {
            Log::warning("FlavorParser: Unbekannter Flavor '{$flavor}' — wird bei Kostenberechnung übersprungen.");

            return 0.0;
        }

        return ((int) $m[1] * config('costs.cpu_rate_per_hour'))
             + ((int) $m[2] * config('costs.ram_rate_per_hour'));
    }
}
