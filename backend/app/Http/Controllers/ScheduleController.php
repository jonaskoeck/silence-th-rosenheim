<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $schedules    = [];
        $allServers   = [];
        $filterServer = $request->get('server', '');

        return view('schedules', compact(
            'schedules', 'allServers', 'filterServer'
        ));
    }
}
