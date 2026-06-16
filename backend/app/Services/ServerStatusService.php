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

        // Phase 1: get a token per project. Authentication is cached, so this is
        // a fast in-memory hit on the common (warm) path; a project that fails to
        // authenticate is marked failed and excluded from the status fetch.
        $authByProjectId = [];
        foreach ($projects as $project) {
            try {
                $authByProjectId[$project->id] = $this->client->authenticate(
                    $project->region->host_url,
                    $project->app_credential_id,
                    $project->app_credential_secret,
                );
            } catch (Throwable $e) {
                Log::warning('Failed to authenticate project for status fetch', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage(),
                ]);
                $failedProjectIds[] = $project->id;
            }
        }

        // Phase 2: fetch all server lists concurrently (bounded pool).
        $serversByProjectId = $this->client->listServersMany($authByProjectId);

        foreach ($authByProjectId as $projectId => $auth) {
            if (! array_key_exists($projectId, $serversByProjectId)) {
                $failedProjectIds[] = $projectId; // list request failed

                continue;
            }

            foreach ($serversByProjectId[$projectId] as $osServer) {
                if (! isset($osServer['id'], $osServer['status'])) {
                    continue;
                }

                $statuses[(string) $osServer['id']] = (string) $osServer['status'];
            }
        }

        return new ServerStatusesDto($statuses, $failedProjectIds);
    }

    public function statusForServer(Server $server): ?string
    {
        $project = $server->project;

        try {
            $auth = $this->client->authenticate(
                $project->region->host_url,
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
