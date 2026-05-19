<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ActionType;
use App\Enums\Weekday;
use App\Models\ServerAction;
use App\Services\Contracts\ServerControlServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

class TriggerServerActionsJob
{
    public function handle(ServerControlServiceInterface $control): void
    {
        $interval = max(1, (int) config('scheduler.poll_interval_minutes', 5));
        $now = CarbonImmutable::now(config('app.display_timezone'));

        $slotMinute = intdiv($now->minute, $interval) * $interval;
        $slotStart = $now->setTime($now->hour, $slotMinute, 0);
        $slotEnd = $slotStart->addMinutes($interval);

        $weekday = self::weekdayFromIso($slotStart->dayOfWeekIso);

        $query = ServerAction::query()
            ->whereHas('server', fn ($q) => $q->where('schedule_active', true))
            ->whereRaw('(weekday & ?) > 0', [$weekday->value])
            ->where('time', '>=', $slotStart->format('H:i'));

        // Slot-Wrap über Mitternacht: HH:mm kann nicht > 23:59 sein, daher
        // ist eine offene obere Schranke korrekt. Sonst halb-offenes Intervall.
        if ($slotEnd->toDateString() === $slotStart->toDateString()) {
            $query->where('time', '<', $slotEnd->format('H:i'));
        }

        $actions = $query->with('server.project')->get();

        foreach ($actions as $action) {
            try {
                match ($action->type) {
                    ActionType::START => $control->start($action->server),
                    ActionType::STOP => $control->stop($action->server),
                };
            } catch (Throwable $e) {
                Log::error('ServerAction trigger failed', [
                    'server_action_id' => $action->id,
                    'server_id' => $action->server_id,
                    'type' => $action->type->value,
                    'time' => $action->time,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private static function weekdayFromIso(int $iso): Weekday
    {
        return match ($iso) {
            1 => Weekday::MONDAY,
            2 => Weekday::TUESDAY,
            3 => Weekday::WEDNESDAY,
            4 => Weekday::THURSDAY,
            5 => Weekday::FRIDAY,
            6 => Weekday::SATURDAY,
            7 => Weekday::SUNDAY,
        };
    }
}
