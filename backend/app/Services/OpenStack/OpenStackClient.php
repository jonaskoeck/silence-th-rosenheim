<?php

declare(strict_types=1);

namespace App\Services\OpenStack;

use App\Services\Contracts\OpenStackClientInterface;
use App\Services\OpenStack\Exceptions\InvalidOpenStackCredentialsException;
use App\Services\OpenStack\Exceptions\OpenStackServerActionException;
use App\Services\OpenStack\Exceptions\OpenStackUnreachableException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenStackClient implements OpenStackClientInterface
{
    /** Max concurrent OpenStack requests per pooled batch. */
    private const POOL_SIZE = 10;

    public function authenticate(string $authUrl, string $applicationCredentialId, string $applicationCredentialSecret): AuthenticationResultDto
    {
        // Cache the token (valid for hours per Keystone config) so we don't
        // re-authenticate on every status/inventory call. Keyed by a hash of
        // host + credentials, so a credential or region change re-authenticates.
        $cacheKey = 'openstack-auth:'.hash('sha256', $authUrl.'|'.$applicationCredentialId.'|'.$applicationCredentialSecret);

        // Cache a plain array (not the DTO object): it round-trips reliably through
        // any cache store, whereas a serialized object can come back as an
        // incomplete class depending on the store/serializer.
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['token'], $cached['projectId'], $cached['computeEndpoint'])) {
            return new AuthenticationResultDto($cached['token'], $cached['projectId'], $cached['computeEndpoint']);
        }

        $url = rtrim($authUrl, '/').'/v3/auth/tokens';

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

            throw OpenStackUnreachableException::fromTransportError($e);
        }

        Log::debug('OpenStack auth response', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        if ($response->unauthorized() || $response->forbidden() || $response->badRequest() || $response->notFound()) {
            throw InvalidOpenStackCredentialsException::fromInvalidCredentials();
        }

        if ($response->failed()) {
            throw OpenStackUnreachableException::fromTransportError(
                new RuntimeException("OpenStack auth returned status {$response->status()}.")
            );
        }

        $token = $response->header('X-Subject-Token');
        $projectId = $response->json('token.project.id');

        if (! is_string($token) || $token === '' || ! is_string($projectId) || $projectId === '') {
            throw OpenStackUnreachableException::fromTransportError(
                new RuntimeException('OpenStack auth response was missing a token or project id.')
            );
        }

        $catalog = $response->json('token.catalog') ?? [];
        $computeEndpoint = $this->extractComputeEndpoint($catalog);

        $result = new AuthenticationResultDto($token, $projectId, $computeEndpoint);

        if (($ttl = $this->tokenCacheTtl($response->json('token.expires_at'))) > 0) {
            Cache::put($cacheKey, [
                'token' => $result->token,
                'projectId' => $result->projectId,
                'computeEndpoint' => $result->computeEndpoint,
            ], $ttl);
        }

        return $result;
    }

    /**
     * Seconds to cache a token for: until its expires_at minus a 60s safety
     * buffer, capped at a day. Returns 0 when it can't be determined (then the
     * token is simply not cached).
     */
    private function tokenCacheTtl(mixed $expiresAt): int
    {
        if (! is_string($expiresAt) || $expiresAt === '') {
            return 0;
        }

        try {
            $seconds = CarbonImmutable::parse($expiresAt)->getTimestamp() - CarbonImmutable::now()->getTimestamp() - 60;
        } catch (\Throwable) {
            return 0;
        }

        return max(0, min($seconds, 86400));
    }

    /**
     * Prüft (ohne Zugangsdaten), ob unter der Host-URL ein erreichbarer
     * OpenStack-Identity-Dienst (Keystone v3) antwortet. Wirft, wenn der Host
     * nicht aufgelöst/erreicht werden kann oder kein Keystone-v3-Endpunkt ist.
     *
     * @throws OpenStackUnreachableException
     */
    public function verifyIdentityEndpoint(string $authUrl): void
    {
        $url = rtrim($authUrl, '/').'/v3';

        try {
            $response = Http::acceptJson()->get($url);
        } catch (ConnectionException $e) {
            Log::warning('OpenStack identity probe connection failed', ['url' => $url, 'message' => $e->getMessage()]);

            throw OpenStackUnreachableException::fromTransportError($e);
        }

        $versionId = $response->json('version.id');

        if (! is_string($versionId) || ! str_starts_with($versionId, 'v3')) {
            throw OpenStackUnreachableException::fromTransportError(
                new RuntimeException("No OpenStack identity (Keystone v3) endpoint found at {$url}.")
            );
        }
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

    /**
     * @param  array<int, AuthenticationResultDto>  $authByProjectId
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function listServersMany(array $authByProjectId): array
    {
        $serversByProjectId = [];

        // Bounded concurrency: fire at most POOL_SIZE requests at a time so we
        // don't flood OpenStack (or trip its rate limits) with many projects.
        foreach (array_chunk($authByProjectId, self::POOL_SIZE, true) as $chunk) {
            $responses = Http::pool(fn (Pool $pool) => array_map(
                fn (int $projectId) => $pool->as((string) $projectId)
                    ->withHeader('X-Auth-Token', $chunk[$projectId]->token)
                    ->withHeader('X-OpenStack-Nova-Microversion', '2.47')
                    ->acceptJson()
                    ->get($chunk[$projectId]->computeEndpoint.'/servers/detail'),
                array_keys($chunk),
            ));

            foreach ($chunk as $projectId => $auth) {
                $response = $responses[(string) $projectId] ?? null;

                // A failed request comes back as an exception object, not a Response;
                // such projects are simply omitted (the caller treats them as failed).
                if ($response instanceof Response && $response->successful()) {
                    $serversByProjectId[$projectId] = $response->json('servers') ?? [];
                } else {
                    Log::warning('OpenStack list servers failed in pool', ['project_id' => $projectId]);
                }
            }
        }

        return $serversByProjectId;
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

        if ($response->status() === 409) {
            Log::info('OpenStack start ignored: action already in progress or already settled', ['server_id' => $serverId]);

            return;
        }

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

        if ($response->status() === 409) {
            Log::info('OpenStack stop ignored: action already in progress or already settled', ['server_id' => $serverId]);

            return;
        }

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
