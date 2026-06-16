<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActionType;
use App\Enums\Weekday;
use App\Models\Server;
use App\Models\ServerAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ScheduleEventOrderingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_schedule_events_are_sorted_by_time(): void
    {
        $server = Server::factory()->create();

        // Created out of order on purpose: 16:00 before 15:00 before 12:00.
        ServerAction::create(['server_id' => $server->id, 'weekday' => Weekday::MONDAY->value, 'time' => '16:00', 'type' => ActionType::STOP]);
        ServerAction::create(['server_id' => $server->id, 'weekday' => Weekday::MONDAY->value, 'time' => '15:00', 'type' => ActionType::START]);
        ServerAction::create(['server_id' => $server->id, 'weekday' => Weekday::MONDAY->value, 'time' => '12:00', 'type' => ActionType::START]);

        $response = $this->get(route('schedules'));

        $response->assertOk();

        $schedules = collect($response->viewData('schedules'));
        $events = $schedules->firstWhere('id', $server->id)['events'];
        $times = array_column($events, 'time');

        $sorted = $times;
        sort($sorted);

        $this->assertSame($sorted, $times, 'Schedule events should be ordered ascending by time.');
        $this->assertSame(['12:00', '15:00', '16:00'], $times);
    }

    public function test_schedule_events_are_sorted_by_time_after_editing(): void
    {
        $server = Server::factory()->create();
        ServerAction::create(['server_id' => $server->id, 'weekday' => Weekday::MONDAY->value, 'time' => '09:00', 'type' => ActionType::START]);

        // Submit the edit with actions out of chronological order.
        $response = $this->withHeaders(['HX-Request' => 'true'])
            ->put(route('server-actions.update-for-server', $server), [
                'name' => 'Zeitplan',
                'actions' => [
                    ['type' => 'STOP', 'time' => '18:00', 'days' => ['MONDAY']],
                    ['type' => 'START', 'time' => '07:00', 'days' => ['MONDAY']],
                ],
            ]);

        $response->assertOk();

        $schedules = collect($response->viewData('schedules'));
        $events = $schedules->firstWhere('id', $server->id)['events'];
        $times = array_column($events, 'time');

        $this->assertSame(['07:00', '18:00'], $times);
    }
}
