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
        return ServerAction::create($attributes);
    }

    public function deleteAllForServer(Server $server): void
    {
        $server->actions()->delete();
    }
}
