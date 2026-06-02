<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Services\OpenStack\AuthenticationResultDto;

interface OpenStackClientInterface
{
    public function authenticate(string $applicationCredentialId, string $applicationCredentialSecret): AuthenticationResultDto;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listServers(string $token, string $computeEndpoint): array;

    public function startServer(string $token, string $computeEndpoint, string $serverId): void;

    public function stopServer(string $token, string $computeEndpoint, string $serverId): void;

    /**
     * @return array<string, mixed>
     */
    public function getServer(string $token, string $computeEndpoint, string $serverId): array;

    public function getFlavorName(string $token, string $computeEndpoint, string $flavorId): ?string;
}
