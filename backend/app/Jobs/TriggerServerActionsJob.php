<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ActionType;
use App\Enums\Weekday;
use App\Models\ServerAction;
use App\Services\Contracts\PendingActionTrackerInterface;
use App\Services\Contracts\ServerControlServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

class TriggerServerActionsJob
{
    private const CATCHUP_WINDOW_MINUTES = 15;

    public function handle(ServerControlServiceInterface $control, PendingActionTrackerInterface $tracker): void
    {
        $displayTz = config('app.display_timezone');
        $now = CarbonImmutable::now($displayTz);
        $weekday = self::weekdayFromIso($now->dayOfWeekIso);
        $catchupCutoff = $now->subMinutes(self::CATCHUP_WINDOW_MINUTES);

        $candidates = ServerAction::query()
            ->whereHas('server', fn ($q) => $q->where('schedule_active', true))
            ->whereRaw('(weekday & ?) > 0', [$weekday->value])
            ->where('time', '<=', $now->format('H:i'))
            ->with('server.project')
            ->get();

        $due = $candidates->filter(function (ServerAction $action) use ($now, $catchupCutoff) {
            $todayScheduled = $now->setTimeFromTimeString($action->time);

            if ($now->lt($todayScheduled)) {
                return false;
            }

            if ($todayScheduled->lt($catchupCutoff)) {
                return false;
            }

            if ($action->last_triggered_at === null) {
                return true;
            }

            return $action->last_triggered_at->lt($todayScheduled);
        });

        if ($due->isEmpty()) {
            return;
        }

        foreach ($due->groupBy('server_id') as $actionsForServer) {
            $sorted = $actionsForServer->sortBy('time')->values();
            $latest = $sorted->last();
            $superseded = $sorted->slice(0, -1);

            try {
                match ($latest->type) {
                    ActionType::START => $control->start($latest->server),
                    ActionType::STOP => $control->stop($latest->server),
                };

                $tracker->record(
                    $latest->server_id,
                    $latest->type === ActionType::START ? 'ACTIVE' : 'SHUTOFF',
                );

                $latest->forceFill(['last_triggered_at' => $now])->save();

                foreach ($superseded as $skipped) {
                    $skipped->forceFill(['last_triggered_at' => $now])->save();
                }

                $message = "Server '{$latest->server->name}' (#{$latest->server_id}) → {$latest->type->value} um {$latest->time} ausgelöst";
                if ($superseded->isNotEmpty()) {
                    $obsolete = $superseded->map(fn (ServerAction $a) => "{$a->type->value}@{$a->time}")->implode(', ');
                    $message .= " (überholt: {$obsolete})";
                }
                Log::info($message);
            } catch (Throwable $e) {
                Log::error('ServerAction trigger failed', [
                    'server_action_id' => $latest->id,
                    'server_id' => $latest->server_id,
                    'type' => $latest->type->value,
                    'time' => $latest->time,
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
