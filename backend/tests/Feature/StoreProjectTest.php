<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StoreProjectTest extends TestCase
{
    use DatabaseTransactions;

    private const RESOLVED_PROJECT_ID = 'a4d3f1c2b5e64d7a8c9b0e1f2a3b4c5d';

    private function fakeSuccessfulAuth(): void
    {
        config(['services.openstack.auth_url' => 'https://openstack.test']);

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
            'open_stack_project_id' => self::RESOLVED_PROJECT_ID,
            'app_credential_id' => 'other-id',
            'app_credential_secret' => 'other-secret',
        ]);

        $countBefore = Project::query()->count();

        $response = $this->post(route('projects.store'), [
            'name' => 'Duplicate',
            'app_credential_id' => 'cred-id-123',
            'app_credential_secret' => 'cred-secret-xyz',
        ]);

        $response->assertSessionHasErrors('app_credential_id');
        $this->assertSame($countBefore, Project::query()->count());
    }

    public function test_project_is_rejected_when_open_stack_credentials_are_invalid(): void
    {
        config(['services.openstack.auth_url' => 'https://openstack.test']);

        Http::fake([
            'openstack.test/v3/auth/tokens' => Http::response(
                body: ['error' => ['code' => 401, 'message' => 'The request you have made requires authentication.']],
                status: 401,
            ),
        ]);

        $countBefore = Project::query()->count();

        $response = $this->post(route('projects.store'), [
            'name' => 'Acme Production',
            'app_credential_id' => 'wrong-id',
            'app_credential_secret' => 'wrong-secret',
        ]);

        $response->assertSessionHasErrors('app_credential_secret');
        $this->assertSame($countBefore, Project::query()->count());
    }
}
