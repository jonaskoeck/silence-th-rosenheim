<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Server;
use App\Services\Contracts\OpenStackClientInterface;
use App\Services\Contracts\ServerStatusServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ServerStatusService implements ServerStatusServiceInterface
{
    public function __construct(
        private OpenStackClientInterface $client,
    ) {}

    public function statusesForProjects(Collection $projects): ServerStatusesDto
    {
        $statuses = [];
        $failedProjectIds = [];

        foreach ($projects as $project) {
            try {
                $auth = $this->client->authenticate(
                    $project->app_credential_id,
                    $project->app_credential_secret,
                );

                foreach ($this->client->listServers($auth->token, $auth->computeEndpoint) as $osServer) {
                    if (! isset($osServer['id'], $osServer['status'])) {
                        continue;
                    }

                    $statuses[(string) $osServer['id']] = (string) $osServer['status'];
                }
            } catch (Throwable $e) {
                Log::warning('Failed to load server statuses for project', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage(),
                ]);
                $failedProjectIds[] = $project->id;
            }
        }

        return new ServerStatusesDto($statuses, $failedProjectIds);
    }

    public function statusForServer(Server $server): ?string
    {
        $project = $server->project;

        try {
            $auth = $this->client->authenticate(
                $project->app_credential_id,
                $project->app_credential_secret,
            );

            $osServer = $this->client->getServer(
                $auth->token,
                $auth->computeEndpoint,
                $server->open_stack_server_id,
            );

            return isset($osServer['status']) ? (string) $osServer['status'] : null;
        } catch (Throwable $e) {
            Log::warning('Failed to load server status', [
                'server_id' => $server->id,
                'project_id' => $project?->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
