<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Region;
use App\Services\Contracts\ServerStatusServiceInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServerStatusServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function authResponse(string $token, string $computeUrl): Response|PromiseInterface
    {
        return Http::response([
            'token' => [
                'expires_at' => now()->addHour()->toIso8601String(),
                'project' => ['id' => 'os-'.$token],
                'catalog' => [[
                    'type' => 'compute',
                    'endpoints' => [['interface' => 'public', 'url' => $computeUrl]],
                ]],
            ],
        ], 201, ['X-Subject-Token' => $token]);
    }

    public function test_collects_statuses_for_multiple_projects(): void
    {
        $regionA = Region::factory()->create(['host_url' => 'https://os-a.test']);
        $regionB = Region::factory()->create(['host_url' => 'https://os-b.test']);
        Project::factory()->create(['region_id' => $regionA->id]);
        Project::factory()->create(['region_id' => $regionB->id]);

        Http::fake([
            'os-a.test/v3/auth/tokens' => $this->authResponse('tok-a', 'https://compute-a.test'),
            'os-b.test/v3/auth/tokens' => $this->authResponse('tok-b', 'https://compute-b.test'),
            'compute-a.test/servers/detail' => Http::response(['servers' => [['id' => 'srv-a', 'status' => 'ACTIVE']]], 200),
            'compute-b.test/servers/detail' => Http::response(['servers' => [['id' => 'srv-b', 'status' => 'SHUTOFF']]], 200),
        ]);

        $dto = app(ServerStatusServiceInterface::class)->statusesForProjects(Project::with('region')->get());

        $this->assertSame('ACTIVE', $dto->statusFor('srv-a'));
        $this->assertSame('SHUTOFF', $dto->statusFor('srv-b'));
        $this->assertFalse($dto->hasFailures());
    }

    public function test_one_failing_project_does_not_block_the_others(): void
    {
        $regionA = Region::factory()->create(['host_url' => 'https://os-a.test']);
        $regionB = Region::factory()->create(['host_url' => 'https://os-b.test']);
        Project::factory()->create(['region_id' => $regionA->id]);
        Project::factory()->create(['region_id' => $regionB->id]);

        Http::fake([
            'os-a.test/v3/auth/tokens' => $this->authResponse('tok-a', 'https://compute-a.test'),
            'os-b.test/v3/auth/tokens' => $this->authResponse('tok-b', 'https://compute-b.test'),
            'compute-a.test/servers/detail' => Http::response(['servers' => [['id' => 'srv-a', 'status' => 'ACTIVE']]], 200),
            // project B's compute endpoint is unreachable
            'compute-b.test/servers/detail' => fn () => throw new ConnectionException('cURL error 7: connection refused'),
        ]);

        $dto = app(ServerStatusServiceInterface::class)->statusesForProjects(Project::with('region')->get());

        $this->assertSame('ACTIVE', $dto->statusFor('srv-a')); // good project still works
        $this->assertNull($dto->statusFor('srv-b'));
        $this->assertTrue($dto->hasFailures());
    }
}
