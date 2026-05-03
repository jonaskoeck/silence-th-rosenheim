<?php

declare(strict_types=1);

namespace App\Services\OpenStack;

use App\Services\Contracts\OpenStackClientInterface;
use App\Services\OpenStack\Exceptions\InvalidOpenStackCredentialsException;
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
        $url = $computeEndpoint.'/servers';

        Log::debug('OpenStack list servers request', ['url' => $url]);

        try {
            $response = Http::withHeader('X-Auth-Token', $token)
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
