<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Server;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StartServerTest extends TestCase
{
    use DatabaseTransactions;

    private const OS_PROJECT_ID = 'a4d3f1c2b5e64d7a8c9b0e1f2a3b4c5d';

    private const OS_SERVER_ID = 'os-server-uuid-1234';

    private function fakeOpenStack(string $serverStatusAfterStart = 'ACTIVE'): void
    {
        config(['services.openstack.auth_url' => 'https://openstack.test']);

        Http::fake([
            'openstack.test/v3/auth/tokens' => Http::response(
                body: [
                    'token' => [
                        'expires_at' => '2099-01-01T00:00:00Z',
                        'project' => ['id' => self::OS_PROJECT_ID, 'name' => 'demo'],
                        'catalog' => [[
                            'type' => 'compute',
                            'endpoints' => [[
                                'interface' => 'public',
                                'url' => 'https://compute.test',
                            ]],
                        ]],
                    ],
                ],
                status: 201,
                headers: ['X-Subject-Token' => 'fake-token'],
            ),
            'compute.test/servers/'.self::OS_SERVER_ID.'/action' => Http::response(status: 202),
            'compute.test/servers/'.self::OS_SERVER_ID => Http::response(
                body: ['server' => ['id' => self::OS_SERVER_ID, 'status' => $serverStatusAfterStart]],
                status: 200,
            ),
        ]);
    }

    private function makeProjectWithServer(string $initialStatus = 'SHUTOFF'): Server
    {
        $project = Project::create([
            'name' => 'Demo',
            'open_stack_project_id' => self::OS_PROJECT_ID,
            'app_credential_id' => 'cred-id',
            'app_credential_secret' => 'cred-secret',
        ]);

        return Server::factory()->create([
            'project_id' => $project->id,
            'open_stack_server_id' => self::OS_SERVER_ID,
            'name' => 'web-01',
            'status' => $initialStatus,
        ]);
    }

    public function test_start_endpoint_calls_openstack_and_updates_server_status(): void
    {
        $server = $this->makeProjectWithServer('SHUTOFF');
        $this->fakeOpenStack(serverStatusAfterStart: 'ACTIVE');

        $response = $this->post(route('servers.start', $server));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $server->refresh();
        $this->assertSame('ACTIVE', $server->status);

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/servers/'.self::OS_SERVER_ID.'/action')
            && $request['os-start'] === null);
    }

    public function test_servers_page_shows_running_badge_for_active_server(): void
    {
        $this->makeProjectWithServer('ACTIVE');

        $response = $this->get(route('servers'));

        $response->assertOk();
        $response->assertSee('Laufend');
    }

    public function test_servers_page_shows_stopped_badge_for_shutoff_server(): void
    {
        $this->makeProjectWithServer('SHUTOFF');

        $response = $this->get(route('servers'));

        $response->assertOk();
        $response->assertSee('Gestoppt');
    }

    public function test_start_failure_does_not_change_status(): void
    {
        $server = $this->makeProjectWithServer('SHUTOFF');

        config(['services.openstack.auth_url' => 'https://openstack.test']);
        Http::fake([
            'openstack.test/v3/auth/tokens' => Http::response(status: 401),
        ]);

        $response = $this->post(route('servers.start', $server));

        $response->assertRedirect();

        $server->refresh();
        $this->assertSame('SHUTOFF', $server->status);
    }
}
