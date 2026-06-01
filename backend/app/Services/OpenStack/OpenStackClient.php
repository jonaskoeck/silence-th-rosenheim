<?php

declare(strict_types=1);

namespace App\Services\OpenStack;

use App\Services\Contracts\OpenStackClientInterface;
use App\Services\OpenStack\Exceptions\InvalidOpenStackCredentialsException;
use App\Services\OpenStack\Exceptions\OpenStackServerActionException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenStackClient implements OpenStackClientInterface
{
    public function __construct(private string $authUrl) {}

    public function authenticate(string $applicationCredentialId, string $applicationCredentialSecret): AuthenticationResultDto
    {
        $url = rtrim($this->authUrl, '/').'/v3/auth/tokens';

        Log::debug('OpenStack auth request', [
            'url' => $url,
            'application_credential_id' => $applicationCredentialId,
        ]);

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->post($url, [
                    'auth' => [
                        'identity' => [
                            'methods' => ['application_credential'],
                            'application_credential' => [
                                'id' => $applicationCredentialId,
                                'secret' => $applicationCredentialSecret,
                            ],
                        ],
                    ],
                ]);
        } catch (ConnectionException $e) {
            Log::warning('OpenStack auth connection failed', ['message' => $e->getMessage()]);

            throw InvalidOpenStackCredentialsException::fromTransportError($e);
        }

        Log::debug('OpenStack auth response', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        if ($response->unauthorized() || $response->forbidden() || $response->badRequest() || $response->notFound()) {
            throw InvalidOpenStackCredentialsException::fromInvalidCredentials();
        }

        if ($response->failed()) {
            throw InvalidOpenStackCredentialsException::fromTransportError(
                new RuntimeException("OpenStack auth returned status {$response->status()}.")
            );
        }

        $token = $response->header('X-Subject-Token');
        $projectId = $response->json('token.project.id');

        if (! is_string($token) || $token === '' || ! is_string($projectId) || $projectId === '') {
            throw InvalidOpenStackCredentialsException::fromTransportError(
                new RuntimeException('OpenStack auth response was missing a token or project id.')
            );
        }

        $catalog = $response->json('token.catalog') ?? [];
        $computeEndpoint = $this->extractComputeEndpoint($catalog);

        return new AuthenticationResultDto($token, $projectId, $computeEndpoint);
    }

    /**
     * Ruft alle Server des Projekts von OpenStack ab.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listServers(string $token, string $computeEndpoint): array
    {
        $url = $computeEndpoint.'/servers/detail';

        Log::debug('OpenStack list servers request', ['url' => $url]);

        try {
            $response = Http::withHeader('X-Auth-Token', $token)
                ->withHeader('X-OpenStack-Nova-Microversion', '2.47')
                ->acceptJson()
                ->get($url);
        } catch (ConnectionException $e) {
            throw new RuntimeException('OpenStack compute connection failed: '.$e->getMessage(), previous: $e);
        }

        if ($response->failed()) {
            throw new RuntimeException("OpenStack compute returned status {$response->status()}.");
        }

        return $response->json('servers') ?? [];
    }

    public function startServer(string $token, string $computeEndpoint, string $serverId): void
    {
        $url = $computeEndpoint.'/servers/'.$serverId.'/action';

        Log::debug('OpenStack start server request', ['url' => $url, 'server_id' => $serverId]);

        try {
            $response = Http::withHeader('X-Auth-Token', $token)
                ->acceptJson()
                ->asJson()
                ->post($url, ['os-start' => null]);
        } catch (ConnectionException $e) {
            throw OpenStackServerActionException::fromTransportError('start', $e);
        }

        Log::debug('OpenStack start server response', ['status' => $response->status()]);

        if ($response->failed()) {
            throw OpenStackServerActionException::fromUnexpectedStatus('start', $response->status());
        }
    }

    public function stopServer(string $token, string $computeEndpoint, string $serverId): void
    {
        $url = $computeEndpoint.'/servers/'.$serverId.'/action';

        Log::debug('OpenStack stop server request', ['url' => $url, 'server_id' => $serverId]);

        try {
            $response = Http::withHeader('X-Auth-Token', $token)
                ->acceptJson()
                ->asJson()
                ->post($url, ['os-stop' => null]);
        } catch (ConnectionException $e) {
            throw OpenStackServerActionException::fromTransportError('stop', $e);
        }

        Log::debug('OpenStack stop server response', ['status' => $response->status()]);

        if ($response->failed()) {
            throw OpenStackServerActionException::fromUnexpectedStatus('stop', $response->status());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getServer(string $token, string $computeEndpoint, string $serverId): array
    {
        $url = $computeEndpoint.'/servers/'.$serverId;

        Log::debug('OpenStack get server request', ['url' => $url]);

        try {
            $response = Http::withHeader('X-Auth-Token', $token)
                ->acceptJson()
                ->get($url);
        } catch (ConnectionException $e) {
            throw OpenStackServerActionException::fromTransportError('fetch', $e);
        }

        if ($response->failed()) {
            throw OpenStackServerActionException::fromUnexpectedStatus('fetch', $response->status());
        }

        Log::debug('OpenStack get server response', [
            'status' => $response->status(),
            'server_status' => $response->json('server.status'),
        ]);

        return $response->json('server') ?? [];
    }

    public function getFlavorName(string $token, string $computeEndpoint, string $flavorId): ?string
    {
        $url = $computeEndpoint.'/flavors/'.$flavorId;

        try {
            $response = Http::withHeader('X-Auth-Token', $token)
                ->acceptJson()
                ->get($url);
        } catch (ConnectionException $e) {
            Log::warning('OpenStack flavor lookup failed', ['flavor_id' => $flavorId, 'message' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            Log::warning('OpenStack flavor lookup returned error', ['flavor_id' => $flavorId, 'status' => $response->status()]);

            return null;
        }

        return $response->json('flavor.name');
    }

    /**
     * Sucht im OpenStack Service-Catalog nach der öffentlichen URL des Compute-Dienstes.
     *
     * @param  array<mixed>  $catalog
     */
    private function extractComputeEndpoint(array $catalog): string
    {
        foreach ($catalog as $service) {
            if (($service['type'] ?? '') !== 'compute') {
                continue;
            }

            foreach ($service['endpoints'] ?? [] as $endpoint) {
                if (($endpoint['interface'] ?? '') === 'public') {
                    return rtrim((string) $endpoint['url'], '/');
                }
            }
        }

        return '';
    }
}
