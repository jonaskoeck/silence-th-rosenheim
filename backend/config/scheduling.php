<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Schedule Trigger Interval
    |--------------------------------------------------------------------------
    |
    | How often (in minutes) the backend cron evaluates and fires due server
    | start/stop actions. This same value drives the schedule time picker in
    | the frontend (selectable times snap to this grid). Changing it requires
    | restarting the scheduler container so the new cron expression is picked up.
    |
    */

    'trigger_interval_minutes' => (int) env('SCHEDULE_TRIGGER_INTERVAL_MINUTES', 5),

];
