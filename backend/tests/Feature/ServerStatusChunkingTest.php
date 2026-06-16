<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Region;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServerStatusChunkingTest extends TestCase
{
    use DatabaseTransactions;

    private function fakeOpenStack(): void
    {
        Http::fake([
            'openstack.test/v3/auth/tokens' => Http::response([
                'token' => [
                    'expires_at' => now()->addHour()->toIso8601String(),
                    'project' => ['id' => 'p'],
                    'catalog' => [['type' => 'compute', 'endpoints' => [['interface' => 'public', 'url' => 'https://compute.test']]]],
                ],
            ], 201, ['X-Subject-Token' => 't']),
            'compute.test/servers/detail' => Http::response(['servers' => []], 200),
        ]);
    }

    public function test_status_loading_chunks_and_chains_to_the_next_offset(): void
    {
        $this->fakeOpenStack();
        $region = Region::factory()->create(['host_url' => 'https://openstack.test']);
        Project::factory()->count(12)->create(['region_id' => $region->id]); // > chunk size (10)

        // First chunk chains to offset 10.
        $first = $this->get(route('servers.statuses'));
        $first->assertOk();
        $first->assertSee('offset=10', escape: false);

        // Final chunk (projects 10-11) has nothing more to load.
        $last = $this->get(route('servers.statuses', ['offset' => 10]));
        $last->assertOk();
        $last->assertDontSee('hx-trigger="load"', escape: false);
    }

    public function test_single_chunk_has_no_next_trigger(): void
    {
        $this->fakeOpenStack();
        $region = Region::factory()->create(['host_url' => 'https://openstack.test']);
        Project::factory()->count(3)->create(['region_id' => $region->id]); // <= chunk size

        $response = $this->get(route('servers.statuses'));
        $response->assertOk();
        $response->assertDontSee('hx-trigger="load"', escape: false);
    }
}
