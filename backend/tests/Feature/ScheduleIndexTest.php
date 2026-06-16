<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActionType;
use App\Enums\Weekday;
use App\Models\Server;
use App\Models\ServerAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ScheduleIndexTest extends TestCase
{
    use DatabaseTransactions;

    private function makeSchedule(Server $server): void
    {
        ServerAction::create([
            'server_id' => $server->id,
            'type' => ActionType::START,
            'time' => '08:00',
            'weekday' => Weekday::MONDAY->value,
        ]);
    }

    public function test_edit_without_a_server_does_not_open_an_existing_schedule(): void
    {
        $this->makeSchedule(Server::factory()->create());
        $this->makeSchedule(Server::factory()->create());

        $response = $this->get(route('schedules', ['edit' => 1]));

        $response->assertOk();
        $this->assertNull($response->viewData('editSchedule'));
        $this->assertNull($response->viewData('preselectServerId'));
    }

    public function test_edit_for_a_specific_server_opens_that_schedule(): void
    {
        $server = Server::factory()->create();
        $this->makeSchedule($server);

        $response = $this->get(route('schedules', ['server' => $server->id, 'edit' => 1]));

        $response->assertOk();
        $this->assertNotNull($response->viewData('editSchedule'));
        $this->assertSame($server->id, $response->viewData('editSchedule')['id']);
    }

    public function test_edit_for_a_server_without_a_schedule_preselects_it_for_creation(): void
    {
        $serverWithSchedule = Server::factory()->create();
        $this->makeSchedule($serverWithSchedule);
        $serverWithout = Server::factory()->create();

        $response = $this->get(route('schedules', ['server' => $serverWithout->id, 'edit' => 1]));

        $response->assertOk();
        $this->assertNull($response->viewData('editSchedule'));
        $this->assertSame($serverWithout->id, $response->viewData('preselectServerId'));
    }
}
