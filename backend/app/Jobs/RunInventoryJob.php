<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Setting;
use App\Services\Contracts\InventoryServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

class RunInventoryJob
{
    public function handle(InventoryServiceInterface $inventory): void
    {
        $interval = Setting::inventoryIntervalMinutes();
        $now = CarbonImmutable::now(config('app.display_timezone'));
        $minutesSinceMidnight = $now->hour * 60 + $now->minute;

        if ($minutesSinceMidnight % $interval !== 0) {
            return;
        }

        Log::info("Auto-inventory wird gestartet (Intervall {$interval} min)");

        $inventory->runForAllProjects(triggeredAutomatically: true);
    }
}
