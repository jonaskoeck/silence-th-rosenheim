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
        $schedules    = [];

        return view('schedules', compact(
            'schedules', 'allServers', 'filterServer'
        ));
    }
}
