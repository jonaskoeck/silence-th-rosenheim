<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Server;
use App\Models\ServerAction;
use Illuminate\Database\Eloquent\Collection;

interface ServerActionServiceInterface
{
    /**
     * @return Collection<int, ServerAction>
     */
    public function getAll(): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ServerAction;

    public function deleteAllForServer(Server $server): void;
}
