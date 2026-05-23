<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Weekday;
use App\Jobs\TriggerServerActionsJob;
use App\Models\Server;
use App\Models\ServerAction;
use App\Models\Setting;
use App\Services\Contracts\ServerControlServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use RuntimeException;
use Tests\TestCase;

class TriggerServerActionsJobTest extends TestCase
{
    use DatabaseTransactions;
    use MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /** 2024-01-01 = Montag, 2024-01-02 = Dienstag */
    private function freezeMonday(string $time): void
    {
        [$h, $m, $s] = array_pad(explode(':', $time), 3, '0');
        CarbonImmutable::setTestNow(
            CarbonImmutable::create(2024, 1, 1, (int) $h, (int) $m, (int) $s, config('app.display_timezone'))
        );
    }

    private function freezeTuesday(string $time): void
    {
        [$h, $m, $s] = array_pad(explode(':', $time), 3, '0');
        CarbonImmutable::setTestNow(
            CarbonImmutable::create(2024, 1, 2, (int) $h, (int) $m, (int) $s, config('app.display_timezone'))
        );
    }

    private function makeAction(Server $server, string $type, string $time, int $weekday): ServerAction
    {
        return ServerAction::create([
            'server_id' => $server->id,
            'type' => $type,
            'time' => $time,
            'weekday' => $weekday,
        ]);
    }

    public function test_action_at_slot_start_is_triggered(): void
    {
        $this->freezeMonday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')
            ->once()
            ->withArgs(fn (Server $s) => $s->id === $server->id);

        (new TriggerServerActionsJob)->handle($mock);
    }

    public function test_action_mid_slot_is_triggered(): void
    {
        $this->freezeMonday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '08:03', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')->once();

        (new TriggerServerActionsJob)->handle($mock);
    }

    public function test_action_at_slot_end_is_not_triggered(): void
    {
        $this->freezeMonday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '08:05', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldNotReceive('start');
        $mock->shouldNotReceive('stop');

        (new TriggerServerActionsJob)->handle($mock);
    }

    public function test_action_in_previous_slot_is_not_triggered(): void
    {
        $this->freezeMonday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '07:55', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldNotReceive('start');

        (new TriggerServerActionsJob)->handle($mock);
    }

    public function test_floor_is_robust_against_cron_jitter(): void
    {
        $this->freezeMonday('08:00:42');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')->once();

        (new TriggerServerActionsJob)->handle($mock);
    }

    public function test_bitmask_match_skips_wrong_weekday(): void
    {
        $this->freezeTuesday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value | Weekday::FRIDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldNotReceive('start');

        (new TriggerServerActionsJob)->handle($mock);
    }

    public function test_bitmask_match_triggers_on_matching_weekday(): void
    {
        $this->freezeMonday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value | Weekday::FRIDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')->once();

        (new TriggerServerActionsJob)->handle($mock);
    }

    public function test_schedule_inactive_servers_are_skipped(): void
    {
        $this->freezeMonday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => false]);
        $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldNotReceive('start');

        (new TriggerServerActionsJob)->handle($mock);
    }

    public function test_action_type_stop_calls_stop(): void
    {
        $this->freezeMonday('18:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'STOP', '18:00', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('stop')->once();
        $mock->shouldNotReceive('start');

        (new TriggerServerActionsJob)->handle($mock);
    }

    public function test_error_in_one_server_does_not_abort_job(): void
    {
        Log::spy();

        $this->freezeMonday('08:00:30');
        $failingServer = Server::factory()->create(['schedule_active' => true]);
        $okServer = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($failingServer, 'START', '08:00', Weekday::MONDAY->value);
        $this->makeAction($okServer, 'START', '08:01', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')
            ->twice()
            ->andReturnUsing(function (Server $server) use ($failingServer): void {
                if ($server->id === $failingServer->id) {
                    throw new RuntimeException('boom');
                }
            });

        (new TriggerServerActionsJob)->handle($mock);

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($msg, $ctx) => $msg === 'ServerAction trigger failed' && ($ctx['error'] ?? null) === 'boom')
            ->once();
    }

    public function test_setting_override_changes_slot_size(): void
    {
        Setting::set(Setting::KEY_SCHEDULE_POLL_INTERVAL_MINUTES, '10');

        $this->freezeMonday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        // 08:07 fällt in den 10er-Slot [08:00, 08:10) — würde mit Default 5 NICHT matchen.
        $this->makeAction($server, 'START', '08:07', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')->once();

        (new TriggerServerActionsJob)->handle($mock);
    }

    public function test_slot_wrapping_midnight_still_matches(): void
    {
        $this->freezeMonday('23:55:10');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'STOP', '23:55', Weekday::MONDAY->value);
        $this->makeAction($server, 'STOP', '23:59', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('stop')->twice();

        (new TriggerServerActionsJob)->handle($mock);
    }
}
