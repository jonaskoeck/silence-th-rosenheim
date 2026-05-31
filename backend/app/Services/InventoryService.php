<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ServerLabel;
use App\Models\InventoryRun;
use App\Models\Project;
use App\Models\Server;
use App\Services\Contracts\InventoryServiceInterface;
use App\Services\Contracts\OpenStackClientInterface;
use App\Services\Contracts\ProjectServiceInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class InventoryService implements InventoryServiceInterface
{
    public function __construct(
        private OpenStackClientInterface $client,
        private ProjectServiceInterface $projects,
    ) {}

    public function runForAllProjects(bool $triggeredAutomatically = false): void
    {

        $run = InventoryRun::create([
            'start_time' => now(),
            'triggered_automatically' => $triggeredAutomatically,
            'had_errors' => false,
            'found_new_servers' => false,
        ]);

        $hadErrors = false;
        $foundNew = false;
        $deletedServers = [];

        foreach ($this->projects->getAll() as $project) {
            try {

                $auth = $this->client->authenticate(
                    $project->app_credential_id,
                    $project->app_credential_secret,
                );

                $osServers = $this->client->listServers($auth->token, $auth->computeEndpoint);

                foreach ($osServers as $osServer) {

                    $server = Server::firstOrNew([
                        'open_stack_server_id' => $osServer['id'],
                        'project_id' => $project->id,
                    ]);

                    // Label nur bei neuen Servern setzen
                    if (! $server->exists) {
                        $server->label = ServerLabel::NONE;
                        $server->discovered_by_run_id = $run->id;
                        $foundNew = true;
                    }

                    $server->name = $osServer['name'];
                    $server->status = $osServer['status'] ?? null;
                    $server->save();
                }

                // Server die nicht mehr in OpenStack existieren aus der DB löschen
                $fetchedIds = array_column($osServers, 'id');

                $toDelete = $project->servers()
                    ->whereNotIn('open_stack_server_id', $fetchedIds);

                $deletedServers = array_merge($deletedServers, $toDelete->pluck('name')->all());

                $toDelete->delete();

                $project->update(['last_inventory_run_id' => $run->id]);

            } catch (Throwable $e) {

                Log::error('Inventory failed for project', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage(),
                ]);
                $hadErrors = true;
            }
        }

        // Protokoll abschließen
        $run->update([
            'end_time' => now(),
            'had_errors' => $hadErrors,
            'found_new_servers' => $foundNew,
            'deleted_servers' => $deletedServers,
        ]);
    }

    public function runForProject(int $projectId): void
    {
        $project = Project::findOrFail($projectId);

        $run = InventoryRun::create([
            'start_time' => now(),
            'triggered_automatically' => false,
            'had_errors' => false,
            'found_new_servers' => false,
        ]);

        $hadErrors = false;
        $foundNew = false;
        $deletedServers = [];

        try {
            $auth = $this->client->authenticate(
                $project->app_credential_id,
                $project->app_credential_secret,
            );

            $osServers = $this->client->listServers($auth->token, $auth->computeEndpoint);

            foreach ($osServers as $osServer) {
                $server = Server::firstOrNew([
                    'open_stack_server_id' => $osServer['id'],
                    'project_id' => $project->id,
                ]);

                if (! $server->exists) {
                    $server->label = ServerLabel::NONE;
                    $server->discovered_by_run_id = $run->id;
                    $foundNew = true;
                }

                $server->name = $osServer['name'];
                $server->status = $osServer['status'] ?? null;
                $server->save();
            }

            $fetchedIds = array_column($osServers, 'id');

            $toDelete = $project->servers()
                ->whereNotIn('open_stack_server_id', $fetchedIds);

            $deletedServers = $toDelete->pluck('name')->all();

            $toDelete->delete();

            // Beim Projekt speichern welcher Run der letzte war
            $project->update(['last_inventory_run_id' => $run->id]);

        } catch (Throwable $e) {
            Log::error('Inventory failed for project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            $hadErrors = true;
        }

        $run->update([
            'end_time' => now(),
            'had_errors' => $hadErrors,
            'found_new_servers' => $foundNew,
            'deleted_servers' => $deletedServers,
        ]);
    }
}
