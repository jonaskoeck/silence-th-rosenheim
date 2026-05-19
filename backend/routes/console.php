<?php

declare(strict_types=1);

use App\Jobs\RunInventoryJob;
use Illuminate\Support\Facades\Schedule;

$intervalMinutes = max(1, (int) config('inventory.interval_minutes', 60));

Schedule::job(new RunInventoryJob())
    ->cron("*/{$intervalMinutes} * * * *")
    ->withoutOverlapping();
