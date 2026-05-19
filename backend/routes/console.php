<?php

declare(strict_types=1);

use App\Jobs\RunInventoryJob;
use App\Jobs\TriggerServerActionsJob;
use Illuminate\Support\Facades\Schedule;

$intervalMinutes = max(1, (int) config('inventory.interval_minutes', 60));

Schedule::job(new RunInventoryJob)
    ->cron("*/{$intervalMinutes} * * * *")
    ->withoutOverlapping();

$serverActionInterval = max(1, (int) config('scheduler.poll_interval_minutes', 5));

Schedule::job(new TriggerServerActionsJob)
    ->cron("*/{$serverActionInterval} * * * *")
    ->withoutOverlapping();
