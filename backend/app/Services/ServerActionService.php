<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Server;
use App\Models\ServerAction;
use App\Services\Contracts\ServerActionServiceInterface;
use Illuminate\Database\Eloquent\Collection;

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
}
