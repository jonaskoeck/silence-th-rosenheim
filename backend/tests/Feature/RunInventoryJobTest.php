<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\RunInventoryJob;
use App\Models\Setting;
use App\Services\Contracts\InventoryServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class RunInventoryJobTest extends TestCase
{
    use DatabaseTransactions;
    use MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function freezeAt(int $hour, int $minute): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::create(2024, 1, 1, $hour, $minute, 0, config('app.display_timezone'))
        );
    }

    public function test_job_runs_when_minute_aligns_with_interval(): void
    {
        // Default interval = 60 min. Minute 0 ist durch 60 teilbar → Gate passt.
        $this->freezeAt(8, 0);

        $mock = Mockery::mock(InventoryServiceInterface::class);
        $mock->shouldReceive('runForAllProjects')->once()->with(true);

        (new RunInventoryJob)->handle($mock);
    }

    public function test_job_skips_when_minute_does_not_align_with_interval(): void
    {
        // Default interval = 60 min. Minute 5 → 5 % 60 = 5 != 0 → Gate blockt.
        $this->freezeAt(8, 5);

        $mock = Mockery::mock(InventoryServiceInterface::class);
        $mock->shouldNotReceive('runForAllProjects');

        (new RunInventoryJob)->handle($mock);
    }

    public function test_job_picks_up_dynamic_interval_from_settings(): void
    {
        Setting::set(Setting::KEY_INVENTORY_INTERVAL_MINUTES, '15');

        // Minute 15 ist durch 15 teilbar → Gate passt.
        $this->freezeAt(8, 15);

        $mock = Mockery::mock(InventoryServiceInterface::class);
        $mock->shouldReceive('runForAllProjects')->once();

        (new RunInventoryJob)->handle($mock);
    }

    public function test_job_skips_when_minute_does_not_match_dynamic_interval(): void
    {
        Setting::set(Setting::KEY_INVENTORY_INTERVAL_MINUTES, '15');

        // Minute 10 → 10 % 15 = 10 != 0 → Gate blockt.
        $this->freezeAt(8, 10);

        $mock = Mockery::mock(InventoryServiceInterface::class);
        $mock->shouldNotReceive('runForAllProjects');

        (new RunInventoryJob)->handle($mock);
    }

    public function test_three_hour_interval_fires_at_aligned_hours(): void
    {
        Setting::set(Setting::KEY_INVENTORY_INTERVAL_MINUTES, '180');

        // 03:00 → minutesSinceMidnight=180, 180 % 180 = 0 → Gate passt
        $this->freezeAt(3, 0);

        $mock = Mockery::mock(InventoryServiceInterface::class);
        $mock->shouldReceive('runForAllProjects')->once();

        (new RunInventoryJob)->handle($mock);
    }

    public function test_three_hour_interval_skips_at_unaligned_hours(): void
    {
        Setting::set(Setting::KEY_INVENTORY_INTERVAL_MINUTES, '180');

        // 02:00 → minutesSinceMidnight=120, 120 % 180 = 120 != 0 → Gate blockt
        $this->freezeAt(2, 0);

        $mock = Mockery::mock(InventoryServiceInterface::class);
        $mock->shouldNotReceive('runForAllProjects');

        (new RunInventoryJob)->handle($mock);
    }

    public function test_daily_interval_fires_only_at_midnight(): void
    {
        Setting::set(Setting::KEY_INVENTORY_INTERVAL_MINUTES, '1440');

        $this->freezeAt(0, 0);

        $mock = Mockery::mock(InventoryServiceInterface::class);
        $mock->shouldReceive('runForAllProjects')->once();

        (new RunInventoryJob)->handle($mock);
    }

    public function test_daily_interval_skips_at_non_midnight(): void
    {
        Setting::set(Setting::KEY_INVENTORY_INTERVAL_MINUTES, '1440');

        // 12:00 → 720 % 1440 = 720 != 0
        $this->freezeAt(12, 0);

        $mock = Mockery::mock(InventoryServiceInterface::class);
        $mock->shouldNotReceive('runForAllProjects');

        (new RunInventoryJob)->handle($mock);
    }
}
