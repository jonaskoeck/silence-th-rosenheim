<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Region;
use App\Services\Contracts\InventoryServiceInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ProjectRegionAuthTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // The post-store inventory run is irrelevant here; stub it out.
        $stub = Mockery::mock(InventoryServiceInterface::class);
        $stub->shouldReceive('runForProject')->andReturn(null);
        $this->app->instance(InventoryServiceInterface::class, $stub);
    }

    public function test_project_authenticates_against_its_own_region_host(): void
    {
        Region::factory()->create(['code' => 'rva', 'host_url' => 'https://hosta.test:5000']);
        $regionB = Region::factory()->create(['code' => 'rvb', 'host_url' => 'https://hostb.test:5000']);

        Http::fake([
            'hosta.test:5000/v3/auth/tokens' => Http::response(
                body: ['token' => ['project' => ['id' => 'project-a']]],
                status: 201,
                headers: ['X-Subject-Token' => 'token-a'],
            ),
            'hostb.test:5000/v3/auth/tokens' => Http::response(
                body: ['token' => ['project' => ['id' => 'project-b']]],
                status: 201,
                headers: ['X-Subject-Token' => 'token-b'],
            ),
        ]);

        $response = $this->post(route('projects.store'), [
            'name' => 'In region B',
            'region_id' => $regionB->id,
            'app_credential_id' => 'cred-id',
            'app_credential_secret' => 'cred-secret',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('projects', [
            'region_id' => $regionB->id,
            'open_stack_project_id' => 'project-b',
        ]);

        // Auth must have hit region B's host, never region A's.
        Http::assertSent(fn ($request) => $request->url() === 'https://hostb.test:5000/v3/auth/tokens');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'hosta.test'));
    }
}
