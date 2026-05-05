<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreServerActionRequest;
use App\Models\Server;
use App\Services\Contracts\ServerActionServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ServerActionController extends Controller
{
    public function __construct(private ServerActionServiceInterface $serverActions) {}

    public function store(StoreServerActionRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->groupedAttributes() as $attributes) {
                $this->serverActions->create($attributes);
            }
        });

        return redirect()->route('schedules');
    }

    public function destroyForServer(Server $server): RedirectResponse
    {
        $this->serverActions->deleteAllForServer($server);

        return redirect()->route('schedules');
    }
}
