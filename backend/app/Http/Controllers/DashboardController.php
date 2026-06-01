<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ActionType;
use App\Enums\ServerLabel;
use App\Enums\Weekday;
use App\Models\InventoryRun;
use App\Services\Contracts\ProjectServiceInterface;
use App\Services\Contracts\ServerActionServiceInterface;
use App\Support\FlavorParser;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private ProjectServiceInterface $projects,
        private ServerActionServiceInterface $serverActions,
    ) {}

    public function index(Request $request): View
    {
        $projectModels = $this->projects->getAll()->load('servers.actions');

        $projects = $projectModels->sortByDesc('created_at')->take(3)->map(fn ($p) => [
            'name' => $p->name,
            'open_stack_project_id' => $p->open_stack_project_id,
            'servers' => $p->servers->map(fn ($s) => [
                'name' => $s->name,
                'status' => $s->status === 'ACTIVE' ? 'running' : 'stopped',
                'label' => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
            ])->all(),
        ])->all();

        $total = $projectModels->sum(fn ($p) => $p->servers->count());
        $running = $projectModels->sum(fn ($p) => $p->servers->where('status', 'ACTIVE')->count());

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
            'monthlySavings' => $this->calculateMonthlySavings($projectModels),
        ];

        if ($request->header('HX-Request')) {
            return view('partials.dashboard-content', $data);
        }

        return view('dashboard', $data);
    }

    private function calculateMonthlySavings(\Illuminate\Support\Collection $projects): float
    {
        $total = 0.0;

        foreach ($projects->flatMap(fn ($p) => $p->servers) as $server) {
            if ($server->actions->isEmpty()) {
                continue;
            }

            if (!$server->schedule_active) {
                continue;
            }

            if (!$server->flavor) {
                continue;
            }

            $rate           = FlavorParser::hourlyCost($server->flavor);
            $stoppedPerWeek = $this->weeklyStoppedHours($server->actions);
            $total         += $stoppedPerWeek * 4 * $rate;
        }

        return $total;
    }

    private function weeklyStoppedHours(\Illuminate\Database\Eloquent\Collection $actions): float
    {
        $dayOffset = [
            Weekday::MONDAY->name    =>    0,
            Weekday::TUESDAY->name   => 1440,
            Weekday::WEDNESDAY->name => 2880,
            Weekday::THURSDAY->name  => 4320,
            Weekday::FRIDAY->name    => 5760,
            Weekday::SATURDAY->name  => 7200,
            Weekday::SUNDAY->name    => 8640,
        ];

        $starts = [];
        $stops  = [];

        foreach ($actions as $action) {
            [$h, $m]  = explode(':', $action->time);
            $timeMin  = (int) $h * 60 + (int) $m;

            foreach (Weekday::unpack($action->weekday) as $day) {
                $abs = $dayOffset[$day->name] + $timeMin;
                if ($action->type === ActionType::START) {
                    $starts[] = $abs;
                } else {
                    $stops[] = $abs;
                }
            }
        }

        if (empty($starts) || empty($stops)) {
            return 0.0;
        }

        sort($starts);
        sort($stops);

        $runningMinutes = 0;
        foreach ($starts as $start) {
            foreach ($stops as $stop) {
                if ($stop > $start) {
                    $runningMinutes += $stop - $start;
                    break;
                }
            }
        }

        return max(0.0, 168.0 - ($runningMinutes / 60));
    }

    private function calculateNextEvents(int $limit = 7): array
    {
        $now = now(config('app.display_timezone', 'Europe/Berlin'));
        $events = [];

        $weekdayNumber = [
            Weekday::MONDAY->name    => 1,
            Weekday::TUESDAY->name   => 2,
            Weekday::WEDNESDAY->name => 3,
            Weekday::THURSDAY->name  => 4,
            Weekday::FRIDAY->name    => 5,
            Weekday::SATURDAY->name  => 6,
            Weekday::SUNDAY->name    => 7,
        ];

        $dayLabel = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'];

        foreach ($this->serverActions->getAll() as $action) {
            $serverName = $action->server?->name ?? '—';

            foreach ($action->weekdays() as $weekday) {
                $targetDay = $weekdayNumber[$weekday->name];
                $currentDay = $now->isoWeekday();
                $daysUntil = ($targetDay - $currentDay + 7) % 7;

                [$h, $m] = explode(':', $action->time);
                $actionMinutes = (int)$h * 60 + (int)$m;
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
                    'server'   => $serverName,
                    'type'     => $action->type,
                    'time'     => $action->time,
                    'day'      => $label,
                    'occursAt' => $occursAt,
                ];
            }
        }

        usort($events, fn ($a, $b) => $a['occursAt']->timestamp <=> $b['occursAt']->timestamp);

        return array_slice($events, 0, $limit);
    }
}
