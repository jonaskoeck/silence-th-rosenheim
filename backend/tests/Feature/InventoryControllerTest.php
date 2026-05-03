<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InventoryRun;
use App\Models\Project;
use App\Services\Contracts\InventoryServiceInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestCase;

class InventoryControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Prüft: Die Inventory-Seite (`GET /inventory`) ist erreichbar und
     * liefert HTTP 200. Smoke-Test, der sicherstellt, dass Route, Controller
     * und View grundsätzlich zusammenpassen.
     */
    public function test_inventory_index_returns_ok(): void
    {
        $response = $this->get(route('inventory'));

        $response->assertOk();
    }

    /**
     * Prüft: Auf der Inventory-Seite werden die Projekte aus der DB
     * tatsächlich angezeigt. Wir legen ein Projekt mit bekanntem Namen
     * an und prüfen mit `assertSee`, dass dieser im HTML auftaucht.
     */
    public function test_inventory_index_shows_projects(): void
    {
        $project = Project::factory()->create(['name' => 'Mein Projekt']);

        $response = $this->get(route('inventory'));

        $response->assertOk();
        $response->assertSee('Mein Projekt');
    }

    /**
     * Prüft: Vergangene InventoryRuns werden auf der Seite angezeigt.
     * Ein manueller Lauf muss in der Liste mit dem Text "Manuell"
     * erscheinen (im Gegensatz zu "Automatisch").
     */
    public function test_inventory_index_shows_runs(): void
    {
        InventoryRun::factory()->create([
            'start_time'              => now(),
            'end_time'                => now(),
            'triggered_automatically' => false,
            'had_errors'              => false,
            'found_new_servers'       => false,
        ]);

        $response = $this->get(route('inventory'));

        $response->assertOk();
        $response->assertSee('Manuell');
    }

    /**
     * Prüft: Ein POST auf `/inventory/run` ruft im Service die Methode
     * `runForAllProjects()` GENAU EINMAL auf und leitet danach zurück
     * auf die Inventory-Seite. Der Service ist gemockt – wir testen also
     * nur den Controller, nicht die echte OpenStack-Logik.
     */
    public function test_inventory_run_calls_service_and_redirects(): void
    {
        $mock = Mockery::mock(InventoryServiceInterface::class);
        $mock->shouldReceive('runForAllProjects')->once();
        $this->app->instance(InventoryServiceInterface::class, $mock);

        $response = $this->post(route('inventory.run'));

        $response->assertRedirect(route('inventory'));
    }

    /**
     * Prüft: Ein POST auf `/inventory/run/{project}` ruft im Service
     * `runForProject($id)` mit der korrekten Projekt-ID auf und leitet
     * danach zurück auf die Inventory-Seite. Stellt sicher, dass die
     * Route-Parameter sauber an den Service durchgereicht werden.
     */
    public function test_inventory_run_for_project_calls_service_and_redirects(): void
    {
        $project = Project::factory()->create();

        $mock = Mockery::mock(InventoryServiceInterface::class);
        $mock->shouldReceive('runForProject')->with($project->id)->once();
        $this->app->instance(InventoryServiceInterface::class, $mock);

        $response = $this->post(route('inventory.run.project', $project->id));

        $response->assertRedirect(route('inventory'));
    }
}
