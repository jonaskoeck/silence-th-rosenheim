<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Weekday;
use App\Http\Requests\StoreServerActionRequest;
use App\Models\Server;
use App\Services\Contracts\ServerActionServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ServerActionController extends Controller
{
    public function __construct(private ServerActionServiceInterface $serverActions) {}

    public function store(StoreServerActionRequest $request): RedirectResponse|Response
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->groupedAttributes() as $attributes) {
                $this->serverActions->create($attributes);
            }
        });

        if ($request->header('HX-Request')) {
            return $this->schedulesPartial('Zeitplan wurde gespeichert.');
        }

        return redirect()->route('schedules');
    }

    public function destroyForServer(Request $request, Server $server): RedirectResponse|Response
    {
        $this->serverActions->deleteAllForServer($server);

        if ($request->header('HX-Request')) {
            return $this->schedulesPartial('Zeitplan wurde gelöscht.');
        }

        return redirect()->route('schedules');
    }

    private function schedulesPartial(string $toastMessage, string $toastType = 'success'): Response
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

        return response(view('partials.schedules-list', compact('schedules')))
            ->header('HX-Trigger', json_encode(['toast' => ['message' => $toastMessage, 'type' => $toastType]]));
    }
}
