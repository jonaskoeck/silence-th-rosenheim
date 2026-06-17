<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Weekday;
use App\Jobs\TriggerServerActionsJob;
use App\Models\Server;
use App\Models\ServerAction;
use App\Services\Contracts\PendingActionTrackerInterface;
use App\Services\Contracts\ServerControlServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use RuntimeException;
use Tests\TestCase;

class TriggerServerActionsJobTest extends TestCase
{
    use DatabaseTransactions;
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function tracker(): PendingActionTrackerInterface
    {
        return app(PendingActionTrackerInterface::class);
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

    public function test_action_fires_when_time_has_just_passed(): void
    {
        $this->freezeMonday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')
            ->once()
            ->withArgs(fn (Server $s) => $s->id === $server->id);

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());
    }

    public function test_action_does_not_fire_before_its_time(): void
    {
        $this->freezeMonday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '08:03', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldNotReceive('start');

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());
    }

    public function test_past_action_within_catchup_window_is_caught_up(): void
    {
        $this->freezeMonday('08:10:00');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')->once();

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());
    }

    public function test_catchup_window_scales_with_the_trigger_interval(): void
    {
        // Interval 10 -> window 30 min. An action 25 min ago would be outside the
        // default (15 min) window but is caught up because the window scaled.
        config(['scheduling.trigger_interval_minutes' => 10]);
        $this->freezeMonday('08:25:00');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')->once();

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());
    }

    public function test_past_action_outside_catchup_window_is_skipped(): void
    {
        // 30 min spaeter — ausserhalb des Fensters (Default-Intervall 5 -> 15 min)
        $this->freezeMonday('08:30:00');
        $server = Server::factory()->create(['schedule_active' => true]);
        $action = $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldNotReceive('start');

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());

        $this->assertNull($action->fresh()->last_triggered_at);
    }

    public function test_action_does_not_fire_twice_within_same_day(): void
    {
        $this->freezeMonday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')->once();

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());
        // Zweiter Lauf in der gleichen Minute — sollte nicht erneut feuern
        (new TriggerServerActionsJob)->handle($mock, $this->tracker());
    }

    public function test_action_fires_again_on_next_day(): void
    {
        $server = Server::factory()->create(['schedule_active' => true]);
        $action = $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value | Weekday::TUESDAY->value);

        $this->freezeMonday('08:00:30');
        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')->twice();
        (new TriggerServerActionsJob)->handle($mock, $this->tracker());

        $this->freezeTuesday('08:00:30');
        (new TriggerServerActionsJob)->handle($mock, $this->tracker());

        $this->assertNotNull($action->fresh()->last_triggered_at);
    }

    public function test_last_triggered_at_is_updated_after_successful_trigger(): void
    {
        $this->freezeMonday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $action = $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')->once();

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());

        $this->assertNotNull($action->fresh()->last_triggered_at);
    }

    public function test_last_triggered_at_is_not_updated_on_failure(): void
    {
        Log::spy();
        $this->freezeMonday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $action = $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')->once()->andThrow(new RuntimeException('boom'));

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());

        $this->assertNull($action->fresh()->last_triggered_at);
    }

    public function test_bitmask_match_skips_wrong_weekday(): void
    {
        $this->freezeTuesday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value | Weekday::FRIDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldNotReceive('start');

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());
    }

    public function test_bitmask_match_triggers_on_matching_weekday(): void
    {
        $this->freezeMonday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value | Weekday::FRIDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')->once();

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());
    }

    public function test_schedule_inactive_servers_are_skipped(): void
    {
        $this->freezeMonday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => false]);
        $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldNotReceive('start');

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());
    }

    public function test_action_type_stop_calls_stop(): void
    {
        $this->freezeMonday('18:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'STOP', '18:00', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('stop')->once();
        $mock->shouldNotReceive('start');

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());
    }

    public function test_error_in_one_server_does_not_abort_job(): void
    {
        Log::spy();

        $this->freezeMonday('08:01:30');
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

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($msg, $ctx) => $msg === 'ServerAction trigger failed' && ($ctx['error'] ?? null) === 'boom')
            ->once();
    }

    public function test_successful_start_records_active_expectation(): void
    {
        $this->freezeMonday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')->once();

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());

        $this->assertSame('ACTIVE', $this->tracker()->expectationFor($server->id, 'SHUTOFF'));
    }

    public function test_successful_stop_records_shutoff_expectation(): void
    {
        $this->freezeMonday('18:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'STOP', '18:00', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('stop')->once();

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());

        $this->assertSame('SHUTOFF', $this->tracker()->expectationFor($server->id, 'ACTIVE'));
    }

    public function test_failed_action_does_not_record_expectation(): void
    {
        Log::spy();
        $this->freezeMonday('08:00:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '08:00', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')->once()->andThrow(new RuntimeException('boom'));

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());

        $this->assertNull($this->tracker()->expectationFor($server->id, 'SHUTOFF'));
    }

    public function test_only_latest_action_fires_when_multiple_are_due_within_window(): void
    {
        // Drei Aktionen knapp hintereinander, alle innerhalb des Catch-Up-Fensters
        $this->freezeMonday('14:10:00');
        $server = Server::factory()->create(['schedule_active' => true]);
        $a1 = $this->makeAction($server, 'START', '14:00', Weekday::MONDAY->value);
        $a2 = $this->makeAction($server, 'STOP', '14:05', Weekday::MONDAY->value);
        $a3 = $this->makeAction($server, 'START', '14:08', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')->once();
        $mock->shouldNotReceive('stop');

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());

        $this->assertNotNull($a1->fresh()->last_triggered_at);
        $this->assertNotNull($a2->fresh()->last_triggered_at);
        $this->assertNotNull($a3->fresh()->last_triggered_at);
    }

    public function test_different_servers_are_processed_independently(): void
    {
        $this->freezeMonday('14:10:00');
        $serverA = Server::factory()->create(['schedule_active' => true]);
        $serverB = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($serverA, 'STOP', '14:00', Weekday::MONDAY->value);
        $this->makeAction($serverB, 'START', '14:05', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('stop')->once()->withArgs(fn (Server $s) => $s->id === $serverA->id);
        $mock->shouldReceive('start')->once()->withArgs(fn (Server $s) => $s->id === $serverB->id);

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());
    }

    public function test_fires_action_when_its_scheduled_time_is_reached(): void
    {
        // Der Job feuert Aktionen, sobald ihre Zeit erreicht ist und sie heute
        // noch nicht ausgelöst wurden — unabhängig von einem Intervall-Gating.
        $this->freezeMonday('08:07:30');
        $server = Server::factory()->create(['schedule_active' => true]);
        $this->makeAction($server, 'START', '08:07', Weekday::MONDAY->value);

        $mock = Mockery::mock(ServerControlServiceInterface::class);
        $mock->shouldReceive('start')->once();

        (new TriggerServerActionsJob)->handle($mock, $this->tracker());
    }
}
