<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Services\OpenStack\AuthenticationResultDto;

interface OpenStackClientInterface
{
    public function authenticate(string $applicationCredentialId, string $applicationCredentialSecret): AuthenticationResultDto;

    public function listServers(string $token, string $computeEndpoint): array;
}
