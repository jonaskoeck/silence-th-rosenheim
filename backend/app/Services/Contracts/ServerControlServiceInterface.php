<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Server;
use App\Services\OpenStack\Exceptions\InvalidOpenStackCredentialsException;
use App\Services\OpenStack\Exceptions\OpenStackServerActionException;

interface ServerControlServiceInterface
{
    /**
     * Start the given server via OpenStack and refresh its status.
     *
     * @throws InvalidOpenStackCredentialsException
     * @throws OpenStackServerActionException
     */
    public function start(Server $server): void;

    /**
     * Stop the given server via OpenStack and refresh its status.
     *
     * @throws InvalidOpenStackCredentialsException
     * @throws OpenStackServerActionException
     */
    public function stop(Server $server): void;
}
