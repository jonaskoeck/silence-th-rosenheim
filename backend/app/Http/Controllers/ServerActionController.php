<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Weekday;
use App\Http\Requests\StoreServerActionRequest;
use App\Models\Server;
use App\Services\Contracts\ServerActionServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServerActionController extends Controller
{
    public function __construct(private ServerActionServiceInterface $serverActions) {}

    public function store(StoreServerActionRequest $request): RedirectResponse|View
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->groupedAttributes() as $attributes) {
                $this->serverActions->create($attributes);
            }
        });

        if ($request->header('HX-Request')) {
            return $this->schedulesPartial();
        }

        return redirect()->route('schedules');
    }

    public function destroyForServer(Request $request, Server $server): RedirectResponse|View
    {
        $this->serverActions->deleteAllForServer($server);

        if ($request->header('HX-Request')) {
            return $this->schedulesPartial();
        }

        return redirect()->route('schedules');
    }

    private function schedulesPartial(): View
    {
        $weekdayLabels = [
            Weekday::MONDAY->name => 'Mo', Weekday::TUESDAY->name => 'Di',
            Weekday::WEDNESDAY->name => 'Mi', Weekday::THURSDAY->name => 'Do',
            Weekday::FRIDAY->name => 'Fr', Weekday::SATURDAY->name => 'Sa',
            Weekday::SUNDAY->name => 'So',
        ];

        $schedules = $this->serverActions->getAll()
            ->groupBy('server_id')
            ->map(function ($group, $serverId) use ($weekdayLabels) {
                $events = [];
                foreach ($group as $action) {
                    foreach ($action->weekdays() as $weekday) {
                        $events[] = [
                            'day' => $weekdayLabels[$weekday->name],
                            'time' => $action->time,
                            'type' => strtolower($action->type->value),
                        ];
                    }
                }
                return [
                    'id' => $serverId,
                    'server_name' => $group->first()->server?->name ?? '—',
                    'name' => 'Zeitplan',
                    'events' => $events,
                ];
            })
            ->values()
            ->all();

        return view('partials.schedules-list', compact('schedules'));
    }
}
