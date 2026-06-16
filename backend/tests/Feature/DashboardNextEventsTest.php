<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Server;
use App\Models\ServerAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardNextEventsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Keine echten OpenStack-Calls im Dashboard auslösen.
        Http::fake();
    }

    public function test_next_events_includes_actions_of_active_schedules(): void
    {
        $server = Server::factory()->create([
            'name' => 'ActiveScheduleServer',
            'schedule_active' => true,
        ]);

        ServerAction::create([
            'server_id' => $server->id,
            'weekday' => 1, // Monday
            'time' => '08:00',
            'type' => 'START',
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();

        $eventServers = collect($response->viewData('nextEvents'))->pluck('server');
        $this->assertContains('ActiveScheduleServer', $eventServers);
    }

    public function test_next_events_omits_actions_of_inactive_schedules(): void
    {
        $server = Server::factory()->create([
            'name' => 'InactiveScheduleServer',
            'schedule_active' => false,
        ]);

        ServerAction::create([
            'server_id' => $server->id,
            'weekday' => 1, // Monday
            'time' => '08:00',
            'type' => 'START',
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();

        $eventServers = collect($response->viewData('nextEvents'))->pluck('server');
        $this->assertNotContains('InactiveScheduleServer', $eventServers);
    }

    public function test_next_events_endpoint_recomputes_and_renders_the_list(): void
    {
        $server = Server::factory()->create(['name' => 'PollingServer', 'schedule_active' => true]);
        ServerAction::create([
            'server_id' => $server->id,
            'weekday' => 1, // Monday
            'time' => '08:00',
            'type' => 'START',
        ]);

        $response = $this->get(route('dashboard.next-events'));

        $response->assertOk();
        $response->assertSee('PollingServer');

        // Must be the bare list partial — not a full page reload. Asserting the
        // absence of layout/page containers guards against accidentally returning
        // the whole dashboard (which would reload everything and disrupt polling).
        $response->assertDontSee('<!DOCTYPE', escape: false);
        $response->assertDontSee('id="dashboard-content"', escape: false);
        $response->assertDontSee('id="main-content"', escape: false);
    }
}
