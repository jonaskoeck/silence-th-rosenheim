<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Project;
use App\Models\Server;
use App\Services\ServerStatusesDto;
use Illuminate\Support\Collection;

interface ServerStatusServiceInterface
{
    /**
     * Fetch the current OpenStack status for every server in the given projects.
     *
     * @param  Collection<int, Project>  $projects
     */
    public function statusesForProjects(Collection $projects): ServerStatusesDto;

    /**
     * Fetch the current OpenStack status for a single server.
     * Returns the raw OpenStack status string (e.g. "ACTIVE", "BUILD") or null on failure.
     */
    public function statusForServer(Server $server): ?string;
}
