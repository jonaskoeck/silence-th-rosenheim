<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ServerLabel;
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

    public function runForAllProjects(): void
    {
        // Alle Projekte aus der DB holen und nacheinander durchgehen
        foreach ($this->projects->getAll() as $project) {
            try {
                // Bei Keystone authentifizieren — gibt Token und Nova-URL zurück
                $auth = $this->client->authenticate(
                    $project->app_credential_id,
                    $project->app_credential_secret,
                );

                // Alle Server des Projekts von Nova holen
                $osServers = $this->client->listServers($auth->token, $auth->computeEndpoint);

                // Jeden Server mit der DB abgleichen
                foreach ($osServers as $osServer) {
                    // Server suchen — vorhanden: laden, nicht vorhanden: neu erstellen
                    $server = Server::firstOrNew([
                        'open_stack_server_id' => $osServer['id'],
                        'project_id'           => $project->id,
                    ]);

                    // Label nur bei neuen Servern setzen
                    if (! $server->exists) {
                        $server->label = ServerLabel::NONE;
                    }

                    // Name immer aktualisieren und speichern
                    $server->name = $osServer['name'];
                    $server->save();
                }

                // Server die nicht mehr in OpenStack existieren aus der DB löschen
                $fetchedIds = array_column($osServers, 'id');

                $project->servers()
                    ->whereNotIn('open_stack_server_id', $fetchedIds)
                    ->delete();

            } catch (Throwable $e) {
                // Fehler loggen aber mit dem nächsten Projekt weitermachen
                Log::error('Inventory failed for project', [
                    'project_id' => $project->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }
}
