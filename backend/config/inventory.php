<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Inventory Cron Interval
    |--------------------------------------------------------------------------
    |
    | How often (in minutes) the automatic inventory run is triggered.
    | Set INVENTORY_INTERVAL_MINUTES in your .env to override the default.
    | Minimum value: 1 minute.
    |
    */

    'interval_minutes' => (int) env('INVENTORY_INTERVAL_MINUTES', 60),

];
