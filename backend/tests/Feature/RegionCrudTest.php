<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Region;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegionCrudTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Fake a reachable Keystone v3 identity endpoint for the host probe.
     */
    private function fakeReachableIdentity(): void
    {
        Http::fake([
            '*/v3' => Http::response(['version' => ['id' => 'v3.14', 'status' => 'stable']], 200),
        ]);
    }

    public function test_store_creates_a_region(): void
    {
        $this->fakeReachableIdentity();

        $response = $this->post(route('regions.store'), [
            'code' => 'muc',
            'host_url' => 'https://api.dc.muc.cloud.cnds.io:5000',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('regions', [
            'code' => 'muc',
            'host_url' => 'https://api.dc.muc.cloud.cnds.io:5000',
        ]);
    }

    public function test_store_rejects_duplicate_code(): void
    {
        Region::factory()->create(['code' => 'muc']);

        $response = $this->post(route('regions.store'), [
            'code' => 'muc',
            'host_url' => 'https://example.test:5000',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertSame(1, Region::where('code', 'muc')->count());
    }

    public function test_store_rejects_invalid_host_url(): void
    {
        $response = $this->post(route('regions.store'), [
            'code' => 'muc',
            'host_url' => 'not-a-url',
        ]);

        $response->assertSessionHasErrors('host_url');
    }

    public function test_store_rejects_unreachable_host(): void
    {
        Http::fake([
            '*/v3' => fn () => throw new ConnectionException('cURL error 6: Could not resolve host'),
        ]);

        $response = $this->post(route('regions.store'), [
            'code' => 'jkefeskfeksfj',
            'host_url' => 'https://jkefeskfeksfj.test:5000',
        ]);

        $response->assertSessionHasErrors('host_url');
        $this->assertDatabaseMissing('regions', ['code' => 'jkefeskfeksfj']);
    }

    public function test_store_rejects_host_that_is_not_keystone(): void
    {
        // Host responds, but it's not an OpenStack identity endpoint.
        Http::fake([
            '*/v3' => Http::response('<html>hello</html>', 200),
        ]);

        $response = $this->post(route('regions.store'), [
            'code' => 'muc',
            'host_url' => 'https://not-openstack.test:5000',
        ]);

        $response->assertSessionHasErrors('host_url');
        $this->assertDatabaseMissing('regions', ['code' => 'muc']);
    }

    public function test_update_changes_region(): void
    {
        $this->fakeReachableIdentity();
        $region = Region::factory()->create(['code' => 'muc', 'host_url' => 'https://old.test:5000']);

        $response = $this->put(route('regions.update', $region), [
            'code' => 'muc2',
            'host_url' => 'https://new.test:5000',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('regions', [
            'id' => $region->id,
            'code' => 'muc2',
            'host_url' => 'https://new.test:5000',
        ]);
    }

    public function test_update_rejects_unreachable_host(): void
    {
        Http::fake([
            '*/v3' => fn () => throw new ConnectionException('cURL error 6: Could not resolve host'),
        ]);
        $region = Region::factory()->create(['code' => 'muc', 'host_url' => 'https://old.test:5000']);

        $response = $this->put(route('regions.update', $region), [
            'code' => 'muc',
            'host_url' => 'https://gibberish.test:5000',
        ]);

        $response->assertSessionHasErrors('host_url');
        $this->assertDatabaseHas('regions', [
            'id' => $region->id,
            'host_url' => 'https://old.test:5000',
        ]);
    }

    public function test_destroy_deletes_region_without_projects(): void
    {
        $region = Region::factory()->create();

        $response = $this->delete(route('regions.destroy', $region));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('regions', ['id' => $region->id]);
    }

    public function test_destroy_is_blocked_when_region_has_projects(): void
    {
        $region = Region::factory()->create();
        Project::factory()->create(['region_id' => $region->id]);

        $response = $this->delete(route('regions.destroy', $region));

        $this->assertDatabaseHas('regions', ['id' => $region->id]);
        $response->assertSessionHas('region_error');
    }
}
