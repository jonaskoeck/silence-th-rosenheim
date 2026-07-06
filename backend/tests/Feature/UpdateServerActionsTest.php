<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ServerLabel;
use App\Models\Server;
use App\Models\ServerAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UpdateServerActionsTest extends TestCase
{
    use DatabaseTransactions;

    private function makeAction(Server $server, string $type, string $time, int $weekday): ServerAction
    {
        return ServerAction::create([
            'server_id' => $server->id,
            'type' => $type,
            'time' => $time,
            'weekday' => $weekday,
        ]);
    }

    public function test_update_replaces_all_existing_actions(): void
    {
        $server = Server::factory()->create();
        $this->makeAction($server, 'START', '08:00', 1);
        $this->makeAction($server, 'STOP', '18:00', 1 | 2 | 4);

        $response = $this->put(route('server-actions.update-for-server', $server), [
            'actions' => [
                ['type' => 'START', 'time' => '09:30', 'days' => ['TUESDAY', 'THURSDAY']],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('schedules'));

        $this->assertSame(1, ServerAction::where('server_id', $server->id)->count());
        $this->assertDatabaseHas('server_actions', [
            'server_id' => $server->id,
            'type' => 'START',
            'time' => '09:30',
            'weekday' => 2 | 8,
        ]);
        $this->assertDatabaseMissing('server_actions', [
            'server_id' => $server->id,
            'time' => '08:00',
        ]);
    }

    public function test_update_combines_weekdays_into_bitmask(): void
    {
        $server = Server::factory()->create();

        $response = $this->put(route('server-actions.update-for-server', $server), [
            'actions' => [
                ['type' => 'START', 'time' => '08:00', 'days' => ['MONDAY', 'WEDNESDAY', 'FRIDAY']],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('server_actions', [
            'server_id' => $server->id,
            'type' => 'START',
            'time' => '08:00',
            'weekday' => 1 | 4 | 16,
        ]);
    }

    public function test_update_merges_duplicate_time_type_into_single_row(): void
    {
        $server = Server::factory()->create();

        $response = $this->put(route('server-actions.update-for-server', $server), [
            'actions' => [
                ['type' => 'START', 'time' => '08:00', 'days' => ['MONDAY']],
                ['type' => 'START', 'time' => '08:00', 'days' => ['WEDNESDAY', 'FRIDAY']],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, ServerAction::where('server_id', $server->id)->count());
        $this->assertDatabaseHas('server_actions', [
            'server_id' => $server->id,
            'type' => 'START',
            'time' => '08:00',
            'weekday' => 1 | 4 | 16,
        ]);
    }

    public function test_update_does_not_touch_other_servers_actions(): void
    {
        $target = Server::factory()->create();
        $other = Server::factory()->create();
        $survivor = $this->makeAction($other, 'START', '08:00', 1);

        $this->put(route('server-actions.update-for-server', $target), [
            'actions' => [
                ['type' => 'STOP', 'time' => '20:00', 'days' => ['SUNDAY']],
            ],
        ]);

        $this->assertDatabaseHas('server_actions', ['id' => $survivor->id]);
    }

    public function test_update_rejects_empty_actions_array(): void
    {
        $server = Server::factory()->create();
        $this->makeAction($server, 'START', '08:00', 1);

        $response = $this->put(route('server-actions.update-for-server', $server), [
            'actions' => [],
        ]);

        $response->assertSessionHasErrors('actions');
        $this->assertSame(1, ServerAction::where('server_id', $server->id)->count());
    }

    public function test_update_rejects_start_and_stop_at_same_time_on_same_day(): void
    {
        $server = Server::factory()->create();
        $this->makeAction($server, 'START', '08:00', 1);

        $response = $this->put(route('server-actions.update-for-server', $server), [
            'actions' => [
                ['type' => 'START', 'time' => '12:00', 'days' => ['MONDAY']],
                ['type' => 'STOP', 'time' => '12:00', 'days' => ['MONDAY']],
            ],
        ]);

        $response->assertSessionHasErrors('actions.1.time');
        // The schedule must be left untouched when the update is rejected.
        $this->assertSame(1, ServerAction::where('server_id', $server->id)->count());
        $this->assertDatabaseHas('server_actions', ['server_id' => $server->id, 'time' => '08:00']);
    }

    public function test_update_allows_same_type_duplicate_at_same_day_and_time(): void
    {
        $server = Server::factory()->create();

        $response = $this->put(route('server-actions.update-for-server', $server), [
            'actions' => [
                ['type' => 'START', 'time' => '12:00', 'days' => ['MONDAY']],
                ['type' => 'START', 'time' => '12:00', 'days' => ['MONDAY', 'TUESDAY']],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, ServerAction::where('server_id', $server->id)->count());
        $this->assertDatabaseHas('server_actions', [
            'server_id' => $server->id,
            'type' => 'START',
            'time' => '12:00',
            'weekday' => 1 | 2,
        ]);
    }

    public function test_update_allows_start_and_stop_at_same_time_on_different_days(): void
    {
        $server = Server::factory()->create();

        $response = $this->put(route('server-actions.update-for-server', $server), [
            'actions' => [
                ['type' => 'START', 'time' => '12:00', 'days' => ['MONDAY']],
                ['type' => 'STOP', 'time' => '12:00', 'days' => ['TUESDAY']],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(2, ServerAction::where('server_id', $server->id)->count());
    }

    public function test_update_rejects_time_not_on_five_minute_step(): void
    {
        $server = Server::factory()->create();
        $this->makeAction($server, 'START', '08:00', 1);

        $response = $this->put(route('server-actions.update-for-server', $server), [
            'actions' => [
                ['type' => 'START', 'time' => '08:03', 'days' => ['MONDAY']],
            ],
        ]);

        $response->assertSessionHasErrors('actions.0.time');
        // schedule untouched
        $this->assertSame(1, ServerAction::where('server_id', $server->id)->count());
        $this->assertDatabaseHas('server_actions', ['server_id' => $server->id, 'time' => '08:00']);
    }

    public function test_update_accepts_time_on_five_minute_step(): void
    {
        $server = Server::factory()->create();

        $response = $this->put(route('server-actions.update-for-server', $server), [
            'actions' => [
                ['type' => 'START', 'time' => '08:05', 'days' => ['MONDAY']],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('server_actions', ['server_id' => $server->id, 'time' => '08:05']);
    }

    public function test_update_rejects_invalid_weekday_name(): void
    {
        $server = Server::factory()->create();

        $response = $this->put(route('server-actions.update-for-server', $server), [
            'actions' => [
                ['type' => 'START', 'time' => '08:00', 'days' => ['MONDAY', 'NOTADAY']],
            ],
        ]);

        $response->assertSessionHasErrors('actions.0.days.1');
        $this->assertSame(0, ServerAction::where('server_id', $server->id)->count());
    }

    public function test_update_rejects_production_server_without_confirmation(): void
    {
        $server = Server::factory()->create(['label' => ServerLabel::PRODUCTION]);
        $this->makeAction($server, 'START', '08:00', 1);

        $response = $this->put(route('server-actions.update-for-server', $server), [
            'actions' => [
                ['type' => 'START', 'time' => '09:00', 'days' => ['MONDAY']],
            ],
        ]);

        $response->assertSessionHasErrors('confirmed_production');
        $this->assertDatabaseHas('server_actions', [
            'server_id' => $server->id,
            'time' => '08:00',
        ]);
    }

    public function test_update_persists_for_production_server_with_confirmation(): void
    {
        $server = Server::factory()->create(['label' => ServerLabel::PRODUCTION]);
        $this->makeAction($server, 'START', '08:00', 1);

        $response = $this->put(route('server-actions.update-for-server', $server), [
            'confirmed_production' => '1',
            'actions' => [
                ['type' => 'START', 'time' => '09:00', 'days' => ['MONDAY']],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, ServerAction::where('server_id', $server->id)->count());
        $this->assertDatabaseHas('server_actions', [
            'server_id' => $server->id,
            'type' => 'START',
            'time' => '09:00',
            'weekday' => 1,
        ]);
    }

    public function test_update_unknown_server_returns_404(): void
    {
        $response = $this->put(route('server-actions.update-for-server', 999999), [
            'actions' => [
                ['type' => 'START', 'time' => '08:00', 'days' => ['MONDAY']],
            ],
        ]);

        $response->assertNotFound();
    }

    public function test_htmx_update_returns_partial_with_toast_header(): void
    {
        $server = Server::factory()->create();
        $this->makeAction($server, 'START', '08:00', 1);

        $response = $this->withHeaders(['HX-Request' => 'true'])
            ->put(route('server-actions.update-for-server', $server), [
                'actions' => [
                    ['type' => 'START', 'time' => '09:30', 'days' => ['TUESDAY']],
                ],
            ]);

        $response->assertOk();
        $this->assertNotNull($response->headers->get('HX-Trigger'));
        $this->assertStringContainsString('aktualisiert', $response->headers->get('HX-Trigger'));
    }

    public function test_update_persists_schedule_name_on_server(): void
    {
        $server = Server::factory()->create(['schedule_name' => null]);
        $this->makeAction($server, 'START', '08:00', 1);

        $response = $this->put(route('server-actions.update-for-server', $server), [
            'name' => '  Tagschicht  ',
            'actions' => [
                ['type' => 'START', 'time' => '09:00', 'days' => ['MONDAY']],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Tagschicht', $server->fresh()->schedule_name);
    }

    public function test_update_without_name_clears_schedule_name(): void
    {
        $server = Server::factory()->create(['schedule_name' => 'Alt']);
        $this->makeAction($server, 'START', '08:00', 1);

        $response = $this->put(route('server-actions.update-for-server', $server), [
            'actions' => [
                ['type' => 'START', 'time' => '09:00', 'days' => ['MONDAY']],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertNull($server->fresh()->schedule_name);
    }

    public function test_update_rejects_name_longer_than_120_chars(): void
    {
        $server = Server::factory()->create(['schedule_name' => 'Original']);
        $this->makeAction($server, 'START', '08:00', 1);

        $response = $this->put(route('server-actions.update-for-server', $server), [
            'name' => str_repeat('a', 121),
            'actions' => [
                ['type' => 'START', 'time' => '09:00', 'days' => ['MONDAY']],
            ],
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame('Original', $server->fresh()->schedule_name);
    }

    public function test_htmx_partial_renders_persisted_schedule_name(): void
    {
        $server = Server::factory()->create();
        $this->makeAction($server, 'START', '08:00', 1);

        $response = $this->withHeaders(['HX-Request' => 'true'])
            ->put(route('server-actions.update-for-server', $server), [
                'name' => 'Wochenende',
                'actions' => [
                    ['type' => 'START', 'time' => '09:00', 'days' => ['MONDAY']],
                ],
            ]);

        $response->assertOk();
        $response->assertSee('Wochenende');
    }

    public function test_htmx_partial_exposes_server_label_for_production_so_edit_button_can_trigger_confirmation(): void
    {
        $server = Server::factory()->create(['label' => ServerLabel::PRODUCTION]);
        $this->makeAction($server, 'START', '08:00', 1);

        $response = $this->withHeaders(['HX-Request' => 'true'])
            ->put(route('server-actions.update-for-server', $server), [
                'confirmed_production' => '1',
                'actions' => [
                    ['type' => 'START', 'time' => '09:30', 'days' => ['TUESDAY']],
                ],
            ]);

        $response->assertOk();
        $response->assertSee('data-server-label="PRODUCTION"', false);
    }
}
