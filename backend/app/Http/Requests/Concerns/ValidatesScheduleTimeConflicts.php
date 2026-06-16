<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;

trait ValidatesScheduleTimeConflicts
{
    /**
     * Reject schedules that place more than one event on the same weekday at the
     * same time (e.g. a START and a STOP both at 12:00), which is contradictory.
     */
    protected function addScheduleTimeConflictErrors(Validator $validator): void
    {
        $actions = $this->input('actions');

        if (! is_array($actions)) {
            return;
        }

        $seen = [];

        foreach ($actions as $index => $action) {
            $time = $action['time'] ?? null;
            $days = $action['days'] ?? null;

            if (! is_string($time) || ! is_array($days)) {
                continue;
            }

            foreach ($days as $day) {
                if (! is_string($day)) {
                    continue;
                }

                $key = $day.'|'.$time;

                if (isset($seen[$key])) {
                    $validator->errors()->add(
                        "actions.{$index}.time",
                        "Pro Tag und Uhrzeit ist nur ein Ereignis erlaubt (Konflikt um {$time} Uhr).",
                    );

                    continue;
                }

                $seen[$key] = true;
            }
        }
    }
}
