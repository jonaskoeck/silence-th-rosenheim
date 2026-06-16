<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Contracts\OpenStackClientInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenStackTokenCacheTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Use the database cache store (as in production) so the test exercises
        // real serialization — the array store would keep the object reference
        // and hide round-trip bugs.
        config(['cache.default' => 'database']);
    }

    private function fakeAuth(string $token = 'tok-1'): void
    {
        Http::fake([
            'os.test/v3/auth/tokens' => Http::response(
                body: [
                    'token' => [
                        'expires_at' => now()->addHour()->toIso8601String(),
                        'project' => ['id' => 'proj-1'],
                        'catalog' => [],
                    ],
                ],
                status: 201,
                headers: ['X-Subject-Token' => $token],
            ),
        ]);
    }

    public function test_token_is_cached_and_not_reauthenticated(): void
    {
        $this->fakeAuth();
        $client = app(OpenStackClientInterface::class);

        $first = $client->authenticate('https://os.test', 'cid', 'csecret');
        $second = $client->authenticate('https://os.test', 'cid', 'csecret');

        $this->assertSame('tok-1', $first->token);
        $this->assertSame('tok-1', $second->token);
        Http::assertSentCount(1); // second call served from cache
    }

    public function test_different_credentials_authenticate_separately(): void
    {
        $this->fakeAuth();
        $client = app(OpenStackClientInterface::class);

        $client->authenticate('https://os.test', 'cid', 'secret-a');
        $client->authenticate('https://os.test', 'cid', 'secret-b');

        Http::assertSentCount(2); // different credentials -> not shared
    }
}
