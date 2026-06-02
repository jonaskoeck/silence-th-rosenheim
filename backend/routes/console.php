<?php

declare(strict_types=1);

use App\Jobs\RunInventoryJob;
use App\Jobs\TriggerServerActionsJob;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

/*
 * Polling-Intervalle stehen jetzt in der `settings`-Tabelle und werden im GUI
 * gepflegt. Damit der Scheduler beim allerersten Container-Start (vor der
 * Migration) nicht crasht, fallen wir auf die Default-Werte zurueck.
 *
 * WICHTIG: Aenderungen im GUI greifen erst nach Neustart des silence-scheduler
 * Containers, weil `php artisan schedule:work` die Cron-Definition nur beim
 * Start einliest.
 */
$tableReady = Schema::hasTable('settings');

$inventoryIntervalMinutes = $tableReady
    ? Setting::inventoryIntervalMinutes()
    : Setting::DEFAULT_INVENTORY_INTERVAL_MINUTES;

Schedule::job(new RunInventoryJob)
    ->cron("*/{$inventoryIntervalMinutes} * * * *")
    ->withoutOverlapping();

$serverActionIntervalMinutes = $tableReady
    ? Setting::schedulePollIntervalMinutes()
    : Setting::DEFAULT_SCHEDULE_POLL_INTERVAL_MINUTES;

Schedule::job(new TriggerServerActionsJob)
    ->cron("*/{$serverActionIntervalMinutes} * * * *")
    ->withoutOverlapping();

Schedule::call(fn () => DB::table('cache')->where('expiration', '<', time())->delete())
    ->daily()
    ->name('prune-stale-cache')
    ->onOneServer();
