<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Weekday;
use App\Http\Requests\StoreServerActionRequest;
use App\Http\Requests\UpdateServerActionsRequest;
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

            $server = Server::find((int) $request->validated('server_id'));
            if ($server !== null) {
                $server->schedule_name = $this->normalizeScheduleName($request->validated('name'));
                $server->save();
            }
        });

        if ($request->header('HX-Request')) {
            return $this->schedulesPartial('Zeitplan wurde gespeichert.');
        }

        return redirect()->route('schedules');
    }

    public function updateForServer(UpdateServerActionsRequest $request, Server $server): RedirectResponse|Response
    {
        $this->serverActions->replaceAllForServer($server, $request->groupedAttributes());

        $server->schedule_name = $this->normalizeScheduleName($request->validated('name'));
        $server->save();

        if ($request->header('HX-Request')) {
            return $this->schedulesPartial('Zeitplan wurde aktualisiert.');
        }

        return redirect()->route('schedules');
    }

    private function normalizeScheduleName(?string $name): ?string
    {
        $trimmed = trim((string) $name);

        return $trimmed === '' ? null : $trimmed;
    }

    public function destroyForServer(Request $request, Server $server): RedirectResponse|Response
    {
        $this->serverActions->deleteAllForServer($server);

        if ($request->header('HX-Request')) {
            return $this->schedulesPartial('Zeitplan wurde gelöscht.');
        }

        return redirect()->route('schedules');
    }

    public function toggleForServer(Request $request, Server $server): RedirectResponse|Response
    {
        $this->serverActions->toggleScheduleActive($server);

        if ($request->header('HX-Request')) {
            return $this->schedulesPartial(null);
        }

        return redirect()->route('schedules');
    }

    private function schedulesPartial(?string $toastMessage, string $toastType = 'success'): Response
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

                usort($events, fn ($a, $b) => $a['time'] <=> $b['time']);

                return [
                    'id' => $serverId,
                    'server_name' => $group->first()->server?->name ?? '—',
                    'server_label' => $group->first()->server?->label?->value ?? 'NONE',
                    'name' => $group->first()->server?->schedule_name ?: 'Zeitplan',
                    'active' => (bool) ($group->first()->server?->schedule_active ?? true),
                    'events' => $events,
                ];
            })
            ->values()
            ->all();

        $response = response(view('partials.schedules-list', compact('schedules')));

        if ($toastMessage !== null) {
            $response->header('HX-Trigger', json_encode(['toast' => ['message' => $toastMessage, 'type' => $toastType]]));
        }

        return $response;
    }
}
