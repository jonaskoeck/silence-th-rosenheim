<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Server;
use App\Models\ServerAction;
use App\Services\Contracts\ServerActionServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ServerActionService implements ServerActionServiceInterface
{
    public function getAll(): Collection
    {
        return ServerAction::with('server')->get();
    }

    public function create(array $attributes): ServerAction
    {
        $existing = ServerAction::where('server_id', $attributes['server_id'])
            ->where('type', $attributes['type'])
            ->where('time', $attributes['time'])
            ->first();

        if ($existing !== null) {
            $existing->weekday = ((int) $existing->weekday) | ((int) $attributes['weekday']);
            $existing->save();

            return $existing;
        }

        return ServerAction::create($attributes);
    }

    public function deleteAllForServer(Server $server): void
    {
        $server->actions()->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $groupedAttributes
     */
    public function replaceAllForServer(Server $server, array $groupedAttributes): void
    {
        DB::transaction(function () use ($server, $groupedAttributes): void {
            $server->actions()->delete();

            foreach ($groupedAttributes as $attributes) {
                $this->create($attributes);
            }
        });
    }

    public function toggleScheduleActive(Server $server): bool
    {
        $server->schedule_active = ! $server->schedule_active;
        $server->save();

        return $server->schedule_active;
    }
}
