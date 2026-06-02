<?php

declare(strict_types=1);

use App\Jobs\RunInventoryJob;
use App\Jobs\TriggerServerActionsJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new RunInventoryJob)
    ->everyMinute()
    ->withoutOverlapping();

Schedule::job(new TriggerServerActionsJob)
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(fn () => DB::table('cache')->where('expiration', '<', time())->delete())
    ->daily()
    ->name('prune-stale-cache')
    ->onOneServer();
