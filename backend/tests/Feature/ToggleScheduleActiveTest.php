<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Server;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ToggleScheduleActiveTest extends TestCase
{
    use DatabaseTransactions;

    public function test_toggle_flips_schedule_active_from_true_to_false(): void
    {
        $server = Server::factory()->create(['schedule_active' => true]);

        $response = $this->post(route('server-actions.toggle-for-server', $server));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('schedules'));
        $this->assertFalse($server->fresh()->schedule_active);
    }

    public function test_toggle_flips_schedule_active_from_false_to_true(): void
    {
        $server = Server::factory()->create(['schedule_active' => false]);

        $response = $this->post(route('server-actions.toggle-for-server', $server));

        $response->assertSessionHasNoErrors();
        $this->assertTrue($server->fresh()->schedule_active);
    }

    public function test_toggle_returns_htmx_partial_when_htmx_header_present(): void
    {
        $server = Server::factory()->create(['schedule_active' => true]);

        $response = $this->withHeaders(['HX-Request' => 'true'])
            ->post(route('server-actions.toggle-for-server', $server));

        $response->assertOk();
        $response->assertHeaderMissing('HX-Trigger');
        $this->assertFalse($server->fresh()->schedule_active);
    }

    public function test_toggle_redirects_to_schedules_for_normal_request(): void
    {
        $server = Server::factory()->create(['schedule_active' => true]);

        $response = $this->post(route('server-actions.toggle-for-server', $server));

        $response->assertRedirect(route('schedules'));
    }
}
