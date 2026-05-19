<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Server-Action Polling Interval
    |--------------------------------------------------------------------------
    |
    | How often (in minutes) the TriggerServerActionsJob polls the database
    | for ServerActions to dispatch. The job's slot window is exactly this
    | length, and the schedules-UI time-picker uses the same value as its
    | step attribute, so action times always align to slot boundaries.
    | Override via SCHEDULE_POLL_INTERVAL_MINUTES in .env. Minimum: 1 minute.
    |
    */

    'poll_interval_minutes' => max(1, (int) env('SCHEDULE_POLL_INTERVAL_MINUTES', 5)),

];
