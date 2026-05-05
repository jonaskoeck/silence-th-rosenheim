<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Weekday;
use App\Models\Server;
use App\Models\ServerAction;
use App\Services\Contracts\ServerActionServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    private const WEEKDAY_LABELS = [
        Weekday::MONDAY->name => 'Mo',
        Weekday::TUESDAY->name => 'Di',
        Weekday::WEDNESDAY->name => 'Mi',
        Weekday::THURSDAY->name => 'Do',
        Weekday::FRIDAY->name => 'Fr',
        Weekday::SATURDAY->name => 'Sa',
        Weekday::SUNDAY->name => 'So',
    ];

    public function __construct(private ServerActionServiceInterface $serverActions) {}

    public function index(Request $request): View
    {
        $allServers = Server::orderBy('name')->get(['id', 'name']);
        $filterServer = $request->get('server', '');

        $actions = $this->serverActions->getAll();
        if ($filterServer !== '') {
            $actions = $actions->where('server_id', (int) $filterServer);
        }

        $schedules = $actions
            ->groupBy('server_id')
            ->map(fn ($group, $serverId) => [
                'id' => $serverId,
                'server_name' => $group->first()->server?->name ?? '—',
                'name' => 'Zeitplan',
                'events' => $this->buildEvents($group),
            ])
            ->values()
            ->all();

        return view('schedules', compact(
            'schedules', 'allServers', 'filterServer'
        ));
    }

    /**
     * @param  iterable<int, ServerAction>  $actions
     * @return array<int, array{day: string, time: string, type: string}>
     */
    private function buildEvents(iterable $actions): array
    {
        $events = [];

        foreach ($actions as $action) {
            foreach ($action->weekdays() as $weekday) {
                $events[] = [
                    'day' => self::WEEKDAY_LABELS[$weekday->name],
                    'time' => $action->time,
                    'type' => strtolower($action->type->value),
                ];
            }
        }

        return $events;
    }
}
