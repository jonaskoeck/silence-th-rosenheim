<?php

declare(strict_types=1);

use App\Jobs\RunInventoryJob;
use App\Jobs\TriggerServerActionsJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new RunInventoryJob)
    ->everyMinute()
    ->withoutOverlapping();

// Fires due server actions on the configured grid (default every 5 min).
$triggerInterval = max(1, (int) config('scheduling.trigger_interval_minutes', 5));

Schedule::job(new TriggerServerActionsJob)
    ->cron("*/{$triggerInterval} * * * *")
    ->withoutOverlapping();

Schedule::call(fn () => DB::table('cache')->where('expiration', '<', time())->delete())
    ->daily()
    ->name('prune-stale-cache')
    ->onOneServer();
