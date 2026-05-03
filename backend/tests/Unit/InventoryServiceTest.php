<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ServerLabel;
use App\Models\InventoryRun;
use App\Models\Project;
use App\Models\Server;
use App\Services\Contracts\OpenStackClientInterface;
use App\Services\Contracts\ProjectServiceInterface;
use App\Services\InventoryService;
use App\Services\OpenStack\AuthenticationResultDto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use DatabaseTransactions;

    private OpenStackClientInterface $client;
    private ProjectServiceInterface $projects;
    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client   = Mockery::mock(OpenStackClientInterface::class);
        $this->projects = Mockery::mock(ProjectServiceInterface::class);
        $this->service  = new InventoryService($this->client, $this->projects);
    }

    /**
     * Prüft: Beim manuellen Lauf über alle Projekte wird ein InventoryRun
     * in der Datenbank angelegt – ohne Fehler und nicht automatisch ausgelöst.
     */
    public function test_run_for_all_projects_creates_inventory_run(): void
    {
        $project = Project::factory()->create();

        $this->projects->shouldReceive('getAll')->andReturn(new Collection([$project]));

        $this->client->shouldReceive('authenticate')->andReturn(
            new AuthenticationResultDto('fake-token', $project->open_stack_project_id, 'https://nova.test/v2.1')
        );

        $this->client->shouldReceive('listServers')->andReturn([]);

        $this->service->runForAllProjects();

        $this->assertDatabaseHas('inventory_runs', [
            'triggered_automatically' => false,
            'had_errors'              => false,
        ]);
    }

    /**
     * Prüft: Server, die OpenStack zurückgibt, werden in der DB angelegt.
     * Der Mock liefert zwei Server – beide müssen anschließend in der
     * `servers`-Tabelle vorhanden sein.
     */
    public function test_run_for_all_projects_creates_new_servers(): void
    {
        $project = Project::factory()->create();

        $this->projects->shouldReceive('getAll')->andReturn(new Collection([$project]));

        $this->client->shouldReceive('authenticate')->andReturn(
            new AuthenticationResultDto('fake-token', $project->open_stack_project_id, 'https://nova.test/v2.1')
        );

        $this->client->shouldReceive('listServers')->andReturn([
            ['id' => 'os-id-1', 'name' => 'Server 01'],
            ['id' => 'os-id-2', 'name' => 'Server 02'],
        ]);

        $this->service->runForAllProjects();

        $this->assertDatabaseHas('servers', ['name' => 'Server 01', 'open_stack_server_id' => 'os-id-1']);
        $this->assertDatabaseHas('servers', ['name' => 'Server 02', 'open_stack_server_id' => 'os-id-2']);
    }

    /**
     * Prüft: Neu entdeckte Server bekommen das Default-Label `NONE`.
     * Damit ist sichergestellt, dass jeder Server mit einem definierten
     * Label startet und später vom RZ manuell als "Test" oder "Produktiv"
     * markiert werden kann.
     */
    public function test_run_for_all_projects_sets_label_none_on_new_servers(): void
    {
        $project = Project::factory()->create();

        $this->projects->shouldReceive('getAll')->andReturn(new Collection([$project]));

        $this->client->shouldReceive('authenticate')->andReturn(
            new AuthenticationResultDto('fake-token', $project->open_stack_project_id, 'https://nova.test/v2.1')
        );

        $this->client->shouldReceive('listServers')->andReturn([
            ['id' => 'os-id-1', 'name' => 'Server 01'],
        ]);

        $this->service->runForAllProjects();

        $this->assertDatabaseHas('servers', [
            'open_stack_server_id' => 'os-id-1',
            'label'                => ServerLabel::NONE->value,
        ]);
    }

    /**
     * Prüft: Server, die in OpenStack nicht mehr existieren, werden auch
     * in unserer DB gelöscht. Der Mock liefert eine leere Liste – der zuvor
     * angelegte Server muss anschließend weg sein (Sync-Logik).
     */
    public function test_run_for_all_projects_deletes_removed_servers(): void
    {
        $project = Project::factory()->create();

        $server = Server::factory()->create([
            'project_id'           => $project->id,
            'open_stack_server_id' => 'old-os-id',
        ]);

        $this->projects->shouldReceive('getAll')->andReturn(new Collection([$project]));

        $this->client->shouldReceive('authenticate')->andReturn(
            new AuthenticationResultDto('fake-token', $project->open_stack_project_id, 'https://nova.test/v2.1')
        );

        // OpenStack gibt den alten Server nicht mehr zurück
        $this->client->shouldReceive('listServers')->andReturn([]);

        $this->service->runForAllProjects();

        $this->assertDatabaseMissing('servers', ['id' => $server->id]);
    }

    /**
     * Prüft: Wenn die Authentifizierung bei OpenStack fehlschlägt, wird
     * der InventoryRun mit `had_errors = true` markiert. Eine Exception
     * darf den gesamten Lauf NICHT abbrechen – andere Projekte müssten
     * weiterhin verarbeitet werden können.
     */
    public function test_run_for_all_projects_marks_run_with_errors_on_failure(): void
    {
        $project = Project::factory()->create();

        $this->projects->shouldReceive('getAll')->andReturn(new Collection([$project]));

        $this->client->shouldReceive('authenticate')->andThrow(new \RuntimeException('Verbindung fehlgeschlagen'));

        $this->service->runForAllProjects();

        $this->assertDatabaseHas('inventory_runs', ['had_errors' => true]);
    }

    /**
     * Prüft: Wenn der Lauf vom Scheduler (also automatisch) gestartet wird,
     * wird das Flag `triggered_automatically = true` gesetzt. Damit kann
     * man später unterscheiden, ob ein Lauf manuell oder per Cronjob lief.
     */
    public function test_run_for_all_projects_sets_triggered_automatically(): void
    {
        $this->projects->shouldReceive('getAll')->andReturn(new Collection([]));

        $this->service->runForAllProjects(triggeredAutomatically: true);

        $this->assertDatabaseHas('inventory_runs', ['triggered_automatically' => true]);
    }

    /**
     * Prüft: Nach einem erfolgreichen Lauf wird auf dem Projekt das Feld
     * `last_inventory_run_id` auf den aktuellen Run gesetzt. Damit kann
     * man später in der Übersicht sehen, wann das Projekt zuletzt
     * inventarisiert wurde.
     */
    public function test_run_for_all_projects_updates_last_inventory_run_on_project(): void
    {
        $project = Project::factory()->create();

        $this->projects->shouldReceive('getAll')->andReturn(new Collection([$project]));

        $this->client->shouldReceive('authenticate')->andReturn(
            new AuthenticationResultDto('fake-token', $project->open_stack_project_id, 'https://nova.test/v2.1')
        );

        $this->client->shouldReceive('listServers')->andReturn([]);

        $this->service->runForAllProjects();

        $run = InventoryRun::latest()->first();
        $this->assertDatabaseHas('projects', [
            'id'                    => $project->id,
            'last_inventory_run_id' => $run->id,
        ]);
    }

    /**
     * Prüft: Auch der Lauf für EIN einzelnes Projekt (`runForProject`)
     * legt einen eigenen InventoryRun in der DB an. Jeder Lauf ist
     * dokumentiert – egal ob für alle oder nur ein Projekt.
     */
    public function test_run_for_project_creates_inventory_run(): void
    {
        $project = Project::factory()->create();

        $this->client->shouldReceive('authenticate')->andReturn(
            new AuthenticationResultDto('fake-token', $project->open_stack_project_id, 'https://nova.test/v2.1')
        );

        $this->client->shouldReceive('listServers')->andReturn([]);

        $this->service->runForProject($project->id);

        $this->assertDatabaseHas('inventory_runs', ['had_errors' => false]);
    }

    /**
     * Prüft: `runForProject` legt die von OpenStack gelieferten Server
     * korrekt an und verknüpft sie über `project_id` mit dem richtigen
     * Projekt. Wichtig: Server gehören immer zu genau einem Projekt.
     */
    public function test_run_for_project_creates_servers(): void
    {
        $project = Project::factory()->create();

        $this->client->shouldReceive('authenticate')->andReturn(
            new AuthenticationResultDto('fake-token', $project->open_stack_project_id, 'https://nova.test/v2.1')
        );

        $this->client->shouldReceive('listServers')->andReturn([
            ['id' => 'os-id-1', 'name' => 'Server 01'],
        ]);

        $this->service->runForProject($project->id);

        $this->assertDatabaseHas('servers', [
            'open_stack_server_id' => 'os-id-1',
            'project_id'           => $project->id,
        ]);
    }
}
