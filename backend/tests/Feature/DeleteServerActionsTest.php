<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Server;
use App\Models\ServerAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DeleteServerActionsTest extends TestCase
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

    public function test_delete_removes_all_actions_for_server(): void
    {
        $server = Server::factory()->create();
        $this->makeAction($server, 'START', '08:00', 1);
        $this->makeAction($server, 'STOP', '18:00', 1 | 2);

        $response = $this->delete(route('server-actions.destroy-for-server', $server));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('schedules'));
        $this->assertSame(0, ServerAction::where('server_id', $server->id)->count());
    }

    public function test_delete_does_not_touch_other_servers_actions(): void
    {
        $target = Server::factory()->create();
        $other = Server::factory()->create();
        $this->makeAction($target, 'START', '08:00', 1);
        $survivor = $this->makeAction($other, 'START', '08:00', 1);

        $this->delete(route('server-actions.destroy-for-server', $target));

        $this->assertSame(0, ServerAction::where('server_id', $target->id)->count());
        $this->assertDatabaseHas('server_actions', ['id' => $survivor->id]);
    }

    public function test_delete_unknown_server_returns_404(): void
    {
        $response = $this->delete(route('server-actions.destroy-for-server', 999999));

        $response->assertNotFound();
    }

    public function test_delete_succeeds_when_server_has_no_actions(): void
    {
        $server = Server::factory()->create();

        $response = $this->delete(route('server-actions.destroy-for-server', $server));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('schedules'));
    }
}
