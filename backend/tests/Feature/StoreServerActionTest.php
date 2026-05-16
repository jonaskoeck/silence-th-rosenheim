<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ServerLabel;
use App\Models\Server;
use App\Models\ServerAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StoreServerActionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_store_persists_action_with_combined_weekday_bitmask(): void
    {
        $server = Server::factory()->create();

        $response = $this->post(route('server-actions.store'), [
            'server_id' => $server->id,
            'actions' => [
                [
                    'type' => 'START',
                    'time' => '08:00',
                    'days' => ['MONDAY', 'WEDNESDAY', 'FRIDAY'],
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('schedules'));

        $this->assertSame(1, ServerAction::where('server_id', $server->id)->count());
        $this->assertDatabaseHas('server_actions', [
            'server_id' => $server->id,
            'type' => 'START',
            'time' => '08:00',
            'weekday' => 1 | 4 | 16,
        ]);
    }

    public function test_store_persists_multiple_grouped_actions_as_separate_rows(): void
    {
        $server = Server::factory()->create();

        $response = $this->post(route('server-actions.store'), [
            'server_id' => $server->id,
            'actions' => [
                ['type' => 'START', 'time' => '08:00', 'days' => ['MONDAY']],
                ['type' => 'STOP',  'time' => '18:00', 'days' => ['MONDAY', 'TUESDAY']],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(2, ServerAction::where('server_id', $server->id)->count());
        $this->assertDatabaseHas('server_actions', [
            'server_id' => $server->id, 'type' => 'START', 'time' => '08:00', 'weekday' => 1,
        ]);
        $this->assertDatabaseHas('server_actions', [
            'server_id' => $server->id, 'type' => 'STOP', 'time' => '18:00', 'weekday' => 1 | 2,
        ]);
    }

    public function test_store_dedups_into_single_row_when_same_server_time_type_already_exists(): void
    {
        $server = Server::factory()->create();

        ServerAction::create([
            'server_id' => $server->id,
            'type' => 'START',
            'time' => '08:00',
            'weekday' => 1, // MONDAY
        ]);

        $response = $this->post(route('server-actions.store'), [
            'server_id' => $server->id,
            'actions' => [
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

    public function test_store_rejects_missing_server_id(): void
    {
        $response = $this->post(route('server-actions.store'), [
            'actions' => [
                ['type' => 'START', 'time' => '08:00', 'days' => ['MONDAY']],
            ],
        ]);

        $response->assertSessionHasErrors('server_id');
    }

    public function test_store_rejects_invalid_weekday_name(): void
    {
        $server = Server::factory()->create();

        $response = $this->post(route('server-actions.store'), [
            'server_id' => $server->id,
            'actions' => [
                ['type' => 'START', 'time' => '08:00', 'days' => ['MONDAY', 'NOTADAY']],
            ],
        ]);

        $response->assertSessionHasErrors('actions.0.days.1');
        $this->assertSame(0, ServerAction::where('server_id', $server->id)->count());
    }

    public function test_store_rejects_invalid_time_format(): void
    {
        $server = Server::factory()->create();

        $response = $this->post(route('server-actions.store'), [
            'server_id' => $server->id,
            'actions' => [
                ['type' => 'START', 'time' => '8am', 'days' => ['MONDAY']],
            ],
        ]);

        $response->assertSessionHasErrors('actions.0.time');
        $this->assertSame(0, ServerAction::where('server_id', $server->id)->count());
    }

    public function test_store_rejects_empty_days_array(): void
    {
        $server = Server::factory()->create();

        $response = $this->post(route('server-actions.store'), [
            'server_id' => $server->id,
            'actions' => [
                ['type' => 'START', 'time' => '08:00', 'days' => []],
            ],
        ]);

        $response->assertSessionHasErrors('actions.0.days');
        $this->assertSame(0, ServerAction::where('server_id', $server->id)->count());
    }

    public function test_store_rejects_invalid_action_type(): void
    {
        $server = Server::factory()->create();

        $response = $this->post(route('server-actions.store'), [
            'server_id' => $server->id,
            'actions' => [
                ['type' => 'PAUSE', 'time' => '08:00', 'days' => ['MONDAY']],
            ],
        ]);

        $response->assertSessionHasErrors('actions.0.type');
        $this->assertSame(0, ServerAction::where('server_id', $server->id)->count());
    }

    public function test_store_rejects_production_server_without_confirmation(): void
    {
        $server = Server::factory()->create(['label' => ServerLabel::PRODUCTION]);

        $response = $this->post(route('server-actions.store'), [
            'server_id' => $server->id,
            'actions' => [
                ['type' => 'START', 'time' => '08:00', 'days' => ['MONDAY']],
            ],
        ]);

        $response->assertSessionHasErrors('confirmed_production');
        $this->assertSame(0, ServerAction::where('server_id', $server->id)->count());
    }

    public function test_store_persists_for_production_server_with_confirmation(): void
    {
        $server = Server::factory()->create(['label' => ServerLabel::PRODUCTION]);

        $response = $this->post(route('server-actions.store'), [
            'server_id' => $server->id,
            'confirmed_production' => '1',
            'actions' => [
                ['type' => 'START', 'time' => '08:00', 'days' => ['MONDAY']],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, ServerAction::where('server_id', $server->id)->count());
    }

    public function test_store_persists_for_non_production_server_without_confirmation(): void
    {
        $server = Server::factory()->create(['label' => ServerLabel::DEVELOPMENT]);

        $response = $this->post(route('server-actions.store'), [
            'server_id' => $server->id,
            'actions' => [
                ['type' => 'START', 'time' => '08:00', 'days' => ['MONDAY']],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, ServerAction::where('server_id', $server->id)->count());
    }
}
