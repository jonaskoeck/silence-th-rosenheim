<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Server;
use App\Services\Contracts\OpenStackClientInterface;
use App\Services\OpenStack\AuthenticationResultDto;
use App\Services\OpenStack\Exceptions\InvalidOpenStackCredentialsException;
use App\Services\OpenStack\Exceptions\OpenStackServerActionException;
use App\Services\ServerControlService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class ServerControlServiceTest extends TestCase
{
    use DatabaseTransactions;
    use MockeryPHPUnitIntegration;

    private function authDto(): AuthenticationResultDto
    {
        return new AuthenticationResultDto(
            token: 'tok',
            projectId: 'proj',
            computeEndpoint: 'https://compute.example/v2',
        );
    }

    public function test_start_calls_openstack_start_action(): void
    {
        $server = Server::factory()->create();

        $openStack = Mockery::mock(OpenStackClientInterface::class);
        $openStack->shouldReceive('authenticate')
            ->once()
            ->with($server->project->app_credential_id, $server->project->app_credential_secret)
            ->andReturn($this->authDto());
        $openStack->shouldReceive('startServer')
            ->once()
            ->with('tok', 'https://compute.example/v2', $server->open_stack_server_id);
        $openStack->shouldNotReceive('getServer');

        (new ServerControlService($openStack))->start($server);
    }

    public function test_start_propagates_invalid_credentials_exception(): void
    {
        $server = Server::factory()->create();

        $openStack = Mockery::mock(OpenStackClientInterface::class);
        $openStack->shouldReceive('authenticate')
            ->andThrow(new InvalidOpenStackCredentialsException('bad creds'));
        $openStack->shouldNotReceive('startServer');

        $this->expectException(InvalidOpenStackCredentialsException::class);
        (new ServerControlService($openStack))->start($server);
    }

    public function test_start_propagates_server_action_exception_from_start_call(): void
    {
        $server = Server::factory()->create();

        $openStack = Mockery::mock(OpenStackClientInterface::class);
        $openStack->shouldReceive('authenticate')->andReturn($this->authDto());
        $openStack->shouldReceive('startServer')
            ->andThrow(new OpenStackServerActionException('start failed'));
        $openStack->shouldNotReceive('getServer');

        $this->expectException(OpenStackServerActionException::class);
        (new ServerControlService($openStack))->start($server);
    }

    public function test_stop_calls_openstack_stop_action(): void
    {
        $server = Server::factory()->create();

        $openStack = Mockery::mock(OpenStackClientInterface::class);
        $openStack->shouldReceive('authenticate')->andReturn($this->authDto());
        $openStack->shouldReceive('stopServer')
            ->once()
            ->with('tok', 'https://compute.example/v2', $server->open_stack_server_id);
        $openStack->shouldNotReceive('getServer');

        (new ServerControlService($openStack))->stop($server);
    }
}
