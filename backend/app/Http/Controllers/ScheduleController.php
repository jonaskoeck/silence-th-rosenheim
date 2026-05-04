<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $filterServer = $request->get('server', '');
        $allServers   = Server::all();

        // MOCKDATEN — zum Entfernen einfach $schedules = [] setzen
        $schedules = [
            [
                'id'          => 1,
                'name'        => 'Nachtabschaltung',
                'server_name' => 'AXRO-SILENCE-01',
                'active'      => true,
                'events'      => [
                    ['day' => 'Mo', 'type' => 'start', 'time' => '07:00'],
                    ['day' => 'Mo', 'type' => 'stop',  'time' => '18:00'],
                    ['day' => 'Di', 'type' => 'start', 'time' => '07:00'],
                    ['day' => 'Di', 'type' => 'stop',  'time' => '18:00'],
                    ['day' => 'Fr', 'type' => 'stop',  'time' => '16:00'],
                ],
            ],
            [
                'id'          => 2,
                'name'        => 'Wochenende aus',
                'server_name' => 'AXRO-SILENCE-02',
                'active'      => false,
                'events'      => [
                    ['day' => 'Sa', 'type' => 'stop', 'time' => '00:00'],
                    ['day' => 'So', 'type' => 'stop', 'time' => '00:00'],
                ],
            ],
        ];

        return view('schedules', compact(
            'schedules', 'allServers', 'filterServer'
        ));
    }
}
