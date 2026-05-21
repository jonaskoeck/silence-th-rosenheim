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
        $allServers = Server::orderBy('name')->get(['id', 'name', 'label']);
        $filterServer = $request->get('server', '');
        $search = $request->input('search', '');

        $actions = $this->serverActions->getAll();
        if ($filterServer !== '') {
            $actions = $actions->where('server_id', (int) $filterServer);
        }

        $schedules = $actions
            ->groupBy('server_id')
            ->map(fn ($group, $serverId) => [
                'id' => $serverId,
                'server_name' => $group->first()->server?->name ?? '—',
                'server_label' => $group->first()->server?->label?->value ?? 'NONE',
                'name' => $group->first()->server?->schedule_name ?: 'Zeitplan',
                'active' => (bool) ($group->first()->server?->schedule_active ?? true),
                'events' => $this->buildEvents($group),
            ])
            ->values()
            ->all();

        if ($search !== '') {
            $needle = strtolower(preg_replace('/[^a-z0-9]/i', '', $search ?? ''));
            $schedules = array_values(array_filter(
                $schedules,
                function ($sch) use ($needle) {
                    $haystack = strtolower(preg_replace(
                        '/[^a-z0-9]/i',
                        '',
                        ($sch['server_name'] ?? '').' '.($sch['name'] ?? '')
                    ));

                    return str_contains($haystack, $needle);
                }
            ));
        }

        if ($request->header('HX-Target') === 'schedules-container') {
            return view('partials.schedules-list', compact('schedules'));
        }

        $editSchedule = ($request->boolean('edit') && ! empty($schedules)) ? $schedules[0] : null;

        $preselectServerId = null;
        if ($editSchedule === null
            && $filterServer !== ''
            && $request->boolean('edit')
            && Server::whereKey((int) $filterServer)->exists()
        ) {
            $preselectServerId = (int) $filterServer;
            // Im Create-Pfad keinen Server-Filter auf die Liste anwenden,
            // damit nach dem Anlegen / Abbrechen alle Schedules sichtbar bleiben.
            $filterServer = '';
            $schedules = $this->serverActions->getAll()
                ->groupBy('server_id')
                ->map(fn ($group, $serverId) => [
                    'id' => $serverId,
                    'server_name' => $group->first()->server?->name ?? '—',
                    'server_label' => $group->first()->server?->label?->value ?? 'NONE',
                    'name' => $group->first()->server?->schedule_name ?: 'Zeitplan',
                    'active' => (bool) ($group->first()->server?->schedule_active ?? true),
                    'events' => $this->buildEvents($group),
                ])
                ->values()
                ->all();
        }

        return view('schedules', compact(
            'schedules', 'allServers', 'filterServer', 'editSchedule', 'preselectServerId'
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
