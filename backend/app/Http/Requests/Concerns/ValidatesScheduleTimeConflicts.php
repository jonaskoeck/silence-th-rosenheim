<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;

trait ValidatesScheduleTimeConflicts
{
    /**
     * Reject contradictory schedule events: a START and a STOP on the same
     * weekday at the same time (e.g. both at 12:00) makes no sense. Repeats of
     * the *same* type at the same day/time are harmless (they get grouped) and
     * are left alone.
     */
    protected function addScheduleTimeConflictErrors(Validator $validator): void
    {
        $actions = $this->input('actions');

        if (! is_array($actions)) {
            return;
        }

        $typeByDayTime = [];

        foreach ($actions as $index => $action) {
            $time = $action['time'] ?? null;
            $days = $action['days'] ?? null;
            $type = $action['type'] ?? null;

            if (! is_string($time) || ! is_array($days) || ! is_string($type)) {
                continue;
            }

            foreach ($days as $day) {
                if (! is_string($day)) {
                    continue;
                }

                $key = $day.'|'.$time;

                if (array_key_exists($key, $typeByDayTime) && $typeByDayTime[$key] !== $type) {
                    $validator->errors()->add(
                        "actions.{$index}.time",
                        "Pro Tag und Uhrzeit ist nur ein Ereignis erlaubt (Konflikt um {$time} Uhr).",
                    );

                    continue;
                }

                $typeByDayTime[$key] = $type;
            }
        }
    }
}
