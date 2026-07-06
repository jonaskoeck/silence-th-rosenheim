<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Region;
use App\Services\Contracts\InventoryServiceInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class StoreProjectTest extends TestCase
{
    use DatabaseTransactions;

    private const RESOLVED_PROJECT_ID = 'a4d3f1c2b5e64d7a8c9b0e1f2a3b4c5d';

    private Region $region;

    protected function setUp(): void
    {
        parent::setUp();

        $this->region = Region::factory()->create(['host_url' => 'https://openstack.test']);

        // InventoryService standardmäßig stubben, damit bestehende Tests nicht
        // durch den neuen runForProject()-Aufruf nach dem Store fehlschlagen.
        // Tests, die das Inventory-Verhalten explizit prüfen, überschreiben
        // diesen Stub mit einem eigenen Mock.
        $stub = Mockery::mock(InventoryServiceInterface::class);
        $stub->shouldReceive('runForProject')->andReturn(null);
        $this->app->instance(InventoryServiceInterface::class, $stub);
    }

    private function fakeSuccessfulAuth(): void
    {
        Http::fake([
            'openstack.test/v3/auth/tokens' => Http::response(
                body: [
                    'token' => [
                        'expires_at' => '2099-01-01T00:00:00Z',
                        'project' => ['id' => self::RESOLVED_PROJECT_ID, 'name' => 'demo'],
                    ],
                ],
                status: 201,
                headers: ['X-Subject-Token' => 'fake-token-value'],
            ),
        ]);
    }

    public function test_project_stores_with_open_stack_project_id_resolved_from_auth_response(): void
    {
        $this->fakeSuccessfulAuth();

        $response = $this->post(route('projects.store'), [
            'name' => 'Acme Production',
            'region_id' => $this->region->id,
            'app_credential_id' => 'cred-id-123',
            'app_credential_secret' => 'cred-secret-xyz',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('projects', [
            'name' => 'Acme Production',
            'open_stack_project_id' => self::RESOLVED_PROJECT_ID,
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://openstack.test/v3/auth/tokens'
                && $request['auth']['identity']['methods'] === ['application_credential']
                && $request['auth']['identity']['application_credential']['id'] === 'cred-id-123'
                && $request['auth']['identity']['application_credential']['secret'] === 'cred-secret-xyz';
        });
    }

    public function test_project_stores_without_a_name(): void
    {
        $this->fakeSuccessfulAuth();

        $response = $this->post(route('projects.store'), [
            'region_id' => $this->region->id,
            'app_credential_id' => 'cred-id-123',
            'app_credential_secret' => 'cred-secret-xyz',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('projects', [
            'open_stack_project_id' => self::RESOLVED_PROJECT_ID,
            'name' => self::RESOLVED_PROJECT_ID,
        ]);
    }

    public function test_project_is_rejected_when_open_stack_project_already_exists(): void
    {
        $this->fakeSuccessfulAuth();

        Project::create([
            'name' => 'Existing',
            'region_id' => $this->region->id,
            'open_stack_project_id' => self::RESOLVED_PROJECT_ID,
            'app_credential_id' => 'other-id',
            'app_credential_secret' => 'other-secret',
        ]);

        $countBefore = Project::query()->count();

        $response = $this->post(route('projects.store'), [
            'name' => 'Duplicate',
            'region_id' => $this->region->id,
            'app_credential_id' => 'cred-id-123',
            'app_credential_secret' => 'cred-secret-xyz',
        ]);

        $response->assertSessionHasErrors('app_credential_id');
        $this->assertSame($countBefore, Project::query()->count());
    }

    public function test_project_is_rejected_when_open_stack_credentials_are_invalid(): void
    {
        Http::fake([
            'openstack.test/v3/auth/tokens' => Http::response(
                body: ['error' => ['code' => 401, 'message' => 'The request you have made requires authentication.']],
                status: 401,
            ),
        ]);

        $countBefore = Project::query()->count();

        $response = $this->post(route('projects.store'), [
            'name' => 'Acme Production',
            'region_id' => $this->region->id,
            'app_credential_id' => 'wrong-id',
            'app_credential_secret' => 'wrong-secret',
        ]);

        $response->assertSessionHasErrors('app_credential_secret');
        $this->assertSame($countBefore, Project::query()->count());
    }

    /**
     * Prüft: Nach dem erfolgreichen Anlegen eines Projekts wird automatisch
     * ein Inventory-Lauf für genau dieses Projekt angestoßen. Damit sind
     * die Server des Projekts direkt nach dem Erstellen sichtbar, ohne
     * dass das RZ manuell einen Lauf starten muss.
     */
    public function test_inventory_is_run_for_new_project_after_store(): void
    {
        $this->fakeSuccessfulAuth();

        $mock = Mockery::mock(InventoryServiceInterface::class);
        $mock->shouldReceive('runForProject')
            ->once()
            ->withArgs(fn (int $id, bool $triggeredAutomatically) => $triggeredAutomatically === true
                && Project::where('id', $id)
                    ->where('open_stack_project_id', self::RESOLVED_PROJECT_ID)
                    ->exists());
        $this->app->instance(InventoryServiceInterface::class, $mock);

        $this->post(route('projects.store'), [
            'name' => 'Acme Production',
            'region_id' => $this->region->id,
            'app_credential_id' => 'cred-id-123',
            'app_credential_secret' => 'cred-secret-xyz',
        ]);
    }

    /**
     * Prüft: Schlägt die Validierung (ungültige Credentials) fehl, wird
     * kein Projekt angelegt – und damit auch kein Inventory-Lauf gestartet.
     */
    public function test_inventory_is_not_run_when_project_store_fails(): void
    {
        Http::fake([
            'openstack.test/v3/auth/tokens' => Http::response(status: 401),
        ]);

        $mock = Mockery::mock(InventoryServiceInterface::class);
        $mock->shouldNotReceive('runForProject');
        $this->app->instance(InventoryServiceInterface::class, $mock);

        $this->post(route('projects.store'), [
            'name' => 'Acme Production',
            'region_id' => $this->region->id,
            'app_credential_id' => 'wrong-id',
            'app_credential_secret' => 'wrong-secret',
        ]);
    }
}
