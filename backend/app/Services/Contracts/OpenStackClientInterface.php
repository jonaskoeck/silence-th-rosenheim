<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Services\OpenStack\AuthenticationResultDto;
use App\Services\OpenStack\Exceptions\OpenStackUnreachableException;

interface OpenStackClientInterface
{
    public function authenticate(string $authUrl, string $applicationCredentialId, string $applicationCredentialSecret): AuthenticationResultDto;

    /**
     * Verify that a reachable OpenStack identity (Keystone v3) endpoint lives at the URL.
     *
     * @throws OpenStackUnreachableException
     */
    public function verifyIdentityEndpoint(string $authUrl): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listServers(string $token, string $computeEndpoint): array;

    /**
     * List servers for many projects concurrently (bounded request pool).
     *
     * @param  array<int, AuthenticationResultDto>  $authByProjectId  auth result keyed by project id
     * @return array<int, array<int, array<string, mixed>>> servers keyed by project id; projects whose
     *                                                      request failed are omitted
     */
    public function listServersMany(array $authByProjectId): array;

    public function startServer(string $token, string $computeEndpoint, string $serverId): void;

    public function stopServer(string $token, string $computeEndpoint, string $serverId): void;

    /**
     * @return array<string, mixed>
     */
    public function getServer(string $token, string $computeEndpoint, string $serverId): array;

    public function getFlavorName(string $token, string $computeEndpoint, string $flavorId): ?string;
}
