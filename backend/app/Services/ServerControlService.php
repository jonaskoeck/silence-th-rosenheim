<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Server;
use App\Services\Contracts\OpenStackClientInterface;
use App\Services\Contracts\ServerControlServiceInterface;

class ServerControlService implements ServerControlServiceInterface
{
    public function __construct(private OpenStackClientInterface $openStack) {}

    public function start(Server $server): void
    {
        $auth = $this->openStack->authenticate(
            $server->project->app_credential_id,
            $server->project->app_credential_secret,
        );

        $this->openStack->startServer(
            $auth->token,
            $auth->computeEndpoint,
            $server->open_stack_server_id,
        );
    }

    public function stop(Server $server): void
    {
        $auth = $this->openStack->authenticate(
            $server->project->app_credential_id,
            $server->project->app_credential_secret,
        );

        $this->openStack->stopServer(
            $auth->token,
            $auth->computeEndpoint,
            $server->open_stack_server_id,
        );
    }
}
