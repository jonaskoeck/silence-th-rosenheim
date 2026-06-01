<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Server;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StopServerTest extends TestCase
{
    use DatabaseTransactions;

    private const OS_PROJECT_ID = 'a4d3f1c2b5e64d7a8c9b0e1f2a3b4c5d';

    private const OS_SERVER_ID = 'os-server-uuid-1234';

    private function fakeOpenStack(string $listedStatus = 'SHUTOFF'): void
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
            'compute.test/servers/detail' => Http::response(
                body: ['servers' => [[
                    'id' => self::OS_SERVER_ID,
                    'name' => 'web-01',
                    'status' => $listedStatus,
                ]]],
                status: 200,
            ),
            'compute.test/servers/'.self::OS_SERVER_ID => Http::response(
                body: ['server' => ['id' => self::OS_SERVER_ID, 'status' => $listedStatus]],
                status: 200,
            ),
        ]);
    }

    private function makeProjectWithServer(): Server
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
        ]);
    }

    public function test_stop_endpoint_calls_openstack_stop_action(): void
    {
        $server = $this->makeProjectWithServer();
        $this->fakeOpenStack(listedStatus: 'SHUTOFF');

        $response = $this->post(route('servers.stop', $server));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/servers/'.self::OS_SERVER_ID.'/action')
            && $request['os-stop'] === null);
    }

    public function test_stop_failure_does_not_call_action_endpoint(): void
    {
        $server = $this->makeProjectWithServer();

        config(['services.openstack.auth_url' => 'https://openstack.test']);
        Http::fake([
            'openstack.test/v3/auth/tokens' => Http::response(status: 401),
        ]);

        $response = $this->post(route('servers.stop', $server));

        $response->assertRedirect();

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/action'));
    }

    public function test_polling_stops_after_max_attempts(): void
    {
        $server = $this->makeProjectWithServer();
        $this->fakeOpenStack(listedStatus: 'ACTIVE');

        $response = $this->get(route('servers.status', $server).'?expecting=SHUTOFF&attempt=45');

        $response->assertOk();
        $response->assertDontSee('hx-trigger', escape: false);
    }

    public function test_dashboard_polls_after_stop_when_user_switches_tabs(): void
    {
        $server = $this->makeProjectWithServer();
        $this->fakeOpenStack(listedStatus: 'ACTIVE');

        $this->post(route('servers.stop', $server));

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
            'compute.test/servers/detail' => Http::response(
                body: ['servers' => [[
                    'id' => self::OS_SERVER_ID,
                    'name' => 'web-01',
                    'status' => 'ACTIVE',
                ]]],
                status: 200,
            ),
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Stoppt', escape: false);
        $response->assertSee('hx-trigger="every 2s"', escape: false);
        $response->assertSee('expecting=SHUTOFF', escape: false);
    }
}
