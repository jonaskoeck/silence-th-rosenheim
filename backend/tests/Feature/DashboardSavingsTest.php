<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Server;
use App\Models\ServerAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardSavingsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Keine echten OpenStack-Calls im Dashboard auslösen.
        Http::fake();
    }

    private function scheduledServer(): Server
    {
        return Server::factory()->create([
            'flavor' => 'SCS-1L-1',
            'schedule_active' => true,
        ]);
    }

    /**
     * Weeks per month used by the savings calculation (365.25/12 days ÷ 7).
     */
    private const WEEKS_PER_MONTH = 365.25 / 12 / 7;

    private function savingsHours(): float
    {
        $response = $this->get(route('dashboard'));
        $response->assertOk();

        return (float) $response->viewData('savingsHours');
    }

    public function test_weekend_window_counts_downtime_between_stop_and_start(): void
    {
        $server = $this->scheduledServer();

        // Stop Friday 18:00, start again Monday 08:00 → off across the weekend.
        ServerAction::create(['server_id' => $server->id, 'weekday' => 16, 'time' => '18:00', 'type' => 'STOP']);
        ServerAction::create(['server_id' => $server->id, 'weekday' => 1, 'time' => '08:00', 'type' => 'START']);

        // Fri 18:00 → Mon 08:00 = 62h per week.
        $this->assertEqualsWithDelta(62.0 * self::WEEKS_PER_MONTH, $this->savingsHours(), 0.001);
    }

    public function test_window_crossing_the_week_boundary_is_counted(): void
    {
        $server = $this->scheduledServer();

        // Off Monday 08:00 → Friday 18:00; the start lies later in the week than
        // the stop, so the running window wraps Sunday→Monday. A flat pass would
        // miss this and wrongly report the full week as saved.
        ServerAction::create(['server_id' => $server->id, 'weekday' => 1, 'time' => '08:00', 'type' => 'STOP']);
        ServerAction::create(['server_id' => $server->id, 'weekday' => 16, 'time' => '18:00', 'type' => 'START']);

        // Mon 08:00 → Fri 18:00 = 106h per week.
        $this->assertEqualsWithDelta(106.0 * self::WEEKS_PER_MONTH, $this->savingsHours(), 0.001);
    }

    public function test_lone_stop_without_start_counts_as_off_all_week(): void
    {
        $server = $this->scheduledServer();

        ServerAction::create(['server_id' => $server->id, 'weekday' => 1, 'time' => '08:00', 'type' => 'STOP']);

        // No start event → the server never comes back: full week of savings.
        $this->assertEqualsWithDelta(168.0 * self::WEEKS_PER_MONTH, $this->savingsHours(), 0.001);
    }

    public function test_lone_start_without_stop_yields_no_savings(): void
    {
        $server = $this->scheduledServer();

        ServerAction::create(['server_id' => $server->id, 'weekday' => 1, 'time' => '08:00', 'type' => 'START']);

        $this->assertEqualsWithDelta(0.0, $this->savingsHours(), 0.001);
    }
}
