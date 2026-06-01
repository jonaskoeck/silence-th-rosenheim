<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServerLabel;
use App\Enums\Weekday;
use App\Models\InventoryRun;
use App\Services\Contracts\PendingActionTrackerInterface;
use App\Services\Contracts\ProjectServiceInterface;
use App\Services\Contracts\ServerActionServiceInterface;
use App\Services\Contracts\ServerStatusServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DashboardController extends Controller
{
    public function __construct(
        private ProjectServiceInterface $projects,
        private ServerActionServiceInterface $serverActions,
        private ServerStatusServiceInterface $serverStatus,
        private PendingActionTrackerInterface $pendingActions,
    ) {}

    public function index(Request $request): View|Response
    {
        $projectModels = $this->projects->getAll()->load('servers')
            ->sortByDesc('created_at')
            ->take(3);

        $statuses = $this->serverStatus->statusesForProjects($projectModels);

        $projects = $projectModels->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'open_stack_project_id' => $p->open_stack_project_id,
            'servers' => $p->servers->map(function ($s) use ($statuses) {
                $rawStatus = $statuses->statusFor($s->open_stack_server_id);

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'raw_status' => $rawStatus,
                    'expecting' => $this->pendingActions->expectationFor($s->id, $rawStatus),
                    'label' => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
                    'online_since' => null,
                ];
            })->all(),
        ])->all();

        $total = $projectModels->sum(fn ($p) => $p->servers->count());
        $running = $projectModels->sum(
            fn ($p) => $p->servers->filter(
                fn ($s) => $statuses->statusFor($s->open_stack_server_id) === 'ACTIVE'
            )->count()
        );

        $lastInventory = InventoryRun::latest()->first();

        $nextEvents = $this->calculateNextEvents();

        $data = [
            'projects' => $projects,
            'schedules' => collect(),
            'nextEvents' => $nextEvents,
            'activity' => [],
            'total' => $total,
            'running' => $running,
            'stopped' => $total - $running,
            'activeSchedules' => 0,
            'lastInventory' => $lastInventory,
        ];

        if ($request->header('HX-Request')) {
            $response = response(view('partials.dashboard-content', $data));

            if ($payload = $statuses->toastTriggerPayload()) {
                $response->header('HX-Trigger', $payload);
            }

            return $response;
        }

        return view('dashboard', $data);
    }

    private function calculateNextEvents(int $limit = 7): array
    {
        $now = now(config('app.display_timezone', 'Europe/Berlin'));
        $events = [];

        $weekdayNumber = [
            Weekday::MONDAY->name => 1,
            Weekday::TUESDAY->name => 2,
            Weekday::WEDNESDAY->name => 3,
            Weekday::THURSDAY->name => 4,
            Weekday::FRIDAY->name => 5,
            Weekday::SATURDAY->name => 6,
            Weekday::SUNDAY->name => 7,
        ];

        $dayLabel = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'];

        foreach ($this->serverActions->getAll() as $action) {
            $serverName = $action->server?->name ?? '—';

            foreach ($action->weekdays() as $weekday) {
                $targetDay = $weekdayNumber[$weekday->name];
                $currentDay = $now->isoWeekday();
                $daysUntil = ($targetDay - $currentDay + 7) % 7;

                [$h, $m] = explode(':', $action->time);
                $actionMinutes = (int) $h * 60 + (int) $m;
                $nowMinutes = $now->hour * 60 + $now->minute;

                if ($daysUntil === 0 && $actionMinutes <= $nowMinutes) {
                    $daysUntil = 7;
                }

                $occursAt = $now->copy()->addDays($daysUntil)->setTimeFromTimeString($action->time);

                $label = match ($daysUntil) {
                    0 => 'Heute',
                    1 => 'Morgen',
                    default => $dayLabel[$targetDay],
                };

                $events[] = [
                    'server' => $serverName,
                    'type' => $action->type,
                    'time' => $action->time,
                    'day' => $label,
                    'occursAt' => $occursAt,
                ];
            }
        }

        usort($events, fn ($a, $b) => $a['occursAt']->timestamp <=> $b['occursAt']->timestamp);

        return array_slice($events, 0, $limit);
    }
}
