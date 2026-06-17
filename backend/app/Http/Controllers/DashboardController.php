<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ActionType;
use App\Enums\ServerLabel;
use App\Enums\Weekday;
use App\Models\InventoryRun;
use App\Services\Contracts\PendingActionTrackerInterface;
use App\Services\Contracts\ProjectServiceInterface;
use App\Services\Contracts\ServerActionServiceInterface;
use App\Services\Contracts\ServerStatusServiceInterface;
use App\Support\FlavorParser;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
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
        $projectModels = $this->projects->getAll()->load('servers.actions');

        $total = $projectModels->sum(fn ($p) => $p->servers->count());
        $savings = $this->calculateMonthlySavings($projectModels);
        $monthlySavings = $savings['savings'];
        $savingsHours = $savings['hours'];
        $savingsAvgRate = $savings['avgRate'];
        $nextEvents = $this->calculateNextEvents();
        $lastInventory = InventoryRun::latest()->first();

        $projects = $projectModels->sortByDesc('created_at')->take(3)->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'open_stack_project_id' => $p->open_stack_project_id,
            'servers' => $p->servers->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'label' => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
            ])->all(),
        ])->all();

        $view = view('dashboard', compact('lastInventory', 'total', 'monthlySavings', 'savingsHours', 'savingsAvgRate', 'nextEvents', 'projects'));

        if ($request->header('HX-Request')) {
            return response($view);
        }

        return $view;
    }

    public function data(): Response
    {
        $projectModels = $this->projects->getAll()->load('servers.actions');

        $statuses = $this->serverStatus->statusesForProjects($projectModels);

        $rawStatusByServerId = [];
        foreach ($projectModels as $p) {
            foreach ($p->servers as $s) {
                $rawStatusByServerId[$s->id] = $statuses->statusFor($s->open_stack_server_id);
            }
        }
        $expectations = $this->pendingActions->expectationsFor($rawStatusByServerId);

        $projects = $projectModels->sortByDesc('created_at')->take(3)->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'open_stack_project_id' => $p->open_stack_project_id,
            'servers' => $p->servers->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'raw_status' => $rawStatusByServerId[$s->id],
                'expecting' => $expectations[$s->id] ?? null,
                'label' => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
            ])->all(),
        ])->all();

        $total = $projectModels->sum(fn ($p) => $p->servers->count());
        $running = $projectModels->sum(
            fn ($p) => $p->servers->filter(
                fn ($s) => $statuses->statusFor($s->open_stack_server_id) === 'ACTIVE'
            )->count()
        );

        $lastInventory = InventoryRun::latest()->first();

        $data = [
            'projects' => $projects,
            'schedules' => collect(),
            'nextEvents' => $this->calculateNextEvents(),
            'activity' => [],
            'total' => $total,
            'running' => $running,
            'stopped' => $total - $running,
            'activeSchedules' => 0,
            'lastInventory' => $lastInventory,
            'monthlySavings' => ($s = $this->calculateMonthlySavings($projectModels))['savings'],
            'savingsHours' => $s['hours'],
            'savingsAvgRate' => $s['avgRate'],
        ];

        $response = response(view('partials.dashboard-content', $data));

        if ($payload = $statuses->toastTriggerPayload()) {
            $response->header('HX-Trigger', $payload);
        }

        return $response;
    }

    /**
     * Freshly recomputed "next events" list — polled by the dashboard so it
     * stays accurate as time passes / scheduled actions fire.
     */
    public function nextEvents(): View
    {
        return view('partials.dashboard-next-events', ['nextEvents' => $this->calculateNextEvents()]);
    }

    /** @return array{savings: float, hours: float, avgRate: float} */
    private function calculateMonthlySavings(\Illuminate\Support\Collection $projects): array
    {
        $totalSavings = 0.0;
        $totalHours = 0.0;

        foreach ($projects->flatMap(fn ($p) => $p->servers) as $server) {
            if ($server->actions->isEmpty() || ! $server->schedule_active || ! $server->flavor) {
                continue;
            }

            $rate = FlavorParser::hourlyCost($server->flavor);
            $stoppedPerWeek = $this->weeklyStoppedHours($server->actions);
            // A month averages 365.25/12 days ≈ 4.345 weeks, not a flat 4.
            $monthlyHours = $stoppedPerWeek * (365.25 / 12 / 7);

            $totalSavings += $monthlyHours * $rate;
            $totalHours += $monthlyHours;
        }

        return [
            'savings' => $totalSavings,
            'hours' => $totalHours,
            'avgRate' => $totalHours > 0 ? $totalSavings / $totalHours : 0.0,
        ];
    }

    private function weeklyStoppedHours(Collection $actions): float
    {
        $dayOffset = [
            Weekday::MONDAY->name => 0,
            Weekday::TUESDAY->name => 1440,
            Weekday::WEDNESDAY->name => 2880,
            Weekday::THURSDAY->name => 4320,
            Weekday::FRIDAY->name => 5760,
            Weekday::SATURDAY->name => 7200,
            Weekday::SUNDAY->name => 8640,
        ];

        $weekMinutes = 7 * 24 * 60;

        /** @var list<array{minute: int, type: ActionType}> $events */
        $events = [];

        foreach ($actions as $action) {
            [$h, $m] = explode(':', $action->time);
            $timeMin = (int) $h * 60 + (int) $m;

            foreach (Weekday::unpack($action->weekday) as $day) {
                $events[] = [
                    'minute' => $dayOffset[$day->name] + $timeMin,
                    'type' => $action->type,
                ];
            }
        }

        if (empty($events)) {
            return 0.0;
        }

        usort($events, fn (array $a, array $b): int => $a['minute'] <=> $b['minute']);

        // A lone event never toggles back: STOP means off all week, START means on.
        if (count($events) === 1) {
            return $events[0]['type'] === ActionType::STOP ? $weekMinutes / 60 : 0.0;
        }

        // Sweep the week as a circular timeline (Sunday wraps to Monday); each
        // segment that begins with a STOP is downtime.
        $stoppedMinutes = 0;
        $count = count($events);

        foreach ($events as $i => $event) {
            if ($event['type'] !== ActionType::STOP) {
                continue;
            }

            $next = $events[($i + 1) % $count]['minute'];
            $stoppedMinutes += ($next - $event['minute'] + $weekMinutes) % $weekMinutes;
        }

        return $stoppedMinutes / 60;
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
            if ($action->server !== null && ! $action->server->schedule_active) {
                continue;
            }

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
