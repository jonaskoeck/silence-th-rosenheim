<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Region;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateProjectTest extends TestCase
{
    use DatabaseTransactions;

    private const RESOLVED_PROJECT_ID = 'a4d3f1c2b5e64d7a8c9b0e1f2a3b4c5d';

    private function fakeSuccessfulAuth(string $projectId = self::RESOLVED_PROJECT_ID): void
    {
        Http::fake([
            'openstack.test/v3/auth/tokens' => Http::response(
                body: [
                    'token' => [
                        'expires_at' => '2099-01-01T00:00:00Z',
                        'project' => ['id' => $projectId, 'name' => 'demo'],
                    ],
                ],
                status: 201,
                headers: ['X-Subject-Token' => 'fake-token-value'],
            ),
        ]);
    }

    private function makeProject(): Project
    {
        $region = Region::factory()->create(['host_url' => 'https://openstack.test']);

        return Project::create([
            'name' => 'Original',
            'region_id' => $region->id,
            'open_stack_project_id' => self::RESOLVED_PROJECT_ID,
            'app_credential_id' => 'old-id',
            'app_credential_secret' => 'old-secret',
        ]);
    }

    public function test_name_can_be_updated_without_reauth_when_credentials_unchanged(): void
    {
        $project = $this->makeProject();
        Http::fake();

        $response = $this->put(route('projects.update', $project), [
            'name' => 'Renamed',
            'app_credential_id' => 'old-id',
            'app_credential_secret' => 'old-secret',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('servers'));
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Renamed',
        ]);
        Http::assertNothingSent();
    }

    public function test_credentials_can_be_updated_when_new_creds_belong_to_same_open_stack_project(): void
    {
        $project = $this->makeProject();
        $this->fakeSuccessfulAuth(self::RESOLVED_PROJECT_ID);

        $response = $this->put(route('projects.update', $project), [
            'name' => 'Original',
            'app_credential_id' => 'new-id',
            'app_credential_secret' => 'new-secret',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'open_stack_project_id' => self::RESOLVED_PROJECT_ID,
        ]);
        $project->refresh();
        $this->assertSame('new-id', $project->app_credential_id);
        $this->assertSame('new-secret', $project->app_credential_secret);
    }

    public function test_update_is_rejected_when_new_credentials_belong_to_different_open_stack_project(): void
    {
        $project = $this->makeProject();
        $this->fakeSuccessfulAuth('different-openstack-project-id');

        $response = $this->put(route('projects.update', $project), [
            'name' => 'Original',
            'app_credential_id' => 'new-id',
            'app_credential_secret' => 'new-secret',
        ]);

        $response->assertSessionHasErrors('app_credential_id');
        $project->refresh();
        $this->assertSame('old-id', $project->app_credential_id);
    }

    public function test_switching_to_unreachable_region_reports_region_error_and_keeps_project(): void
    {
        $project = $this->makeProject();
        $unreachable = Region::factory()->create(['host_url' => 'https://gibberish.test:5000']);

        Http::fake([
            'gibberish.test:5000/*' => fn () => throw new ConnectionException('cURL error 6: Could not resolve host'),
        ]);

        $response = $this->put(route('projects.update', $project), [
            'name' => 'Original',
            'region_id' => $unreachable->id,
        ]);

        // Should blame the region, not the (valid) credentials.
        $response->assertSessionHasErrors('region_id');
        $response->assertSessionDoesntHaveErrors('app_credential_secret');
        $project->refresh();
        $this->assertNotSame($unreachable->id, $project->region_id);
    }

    public function test_update_is_rejected_when_new_credentials_are_invalid(): void
    {
        $project = $this->makeProject();
        Http::fake([
            'openstack.test/v3/auth/tokens' => Http::response(
                body: ['error' => ['code' => 401]],
                status: 401,
            ),
        ]);

        $response = $this->put(route('projects.update', $project), [
            'name' => 'Original',
            'app_credential_id' => 'wrong-id',
            'app_credential_secret' => 'wrong-secret',
        ]);

        $response->assertSessionHasErrors('app_credential_secret');
        $project->refresh();
        $this->assertSame('old-id', $project->app_credential_id);
    }
}
