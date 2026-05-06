<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServerLabel;
use App\Models\InventoryRun;
use App\Services\Contracts\ProjectServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private ProjectServiceInterface $projects) {}

    public function index(Request $request): View
    {
        $projectModels = $this->projects->getAll()->load('servers');

        $projects = $projectModels->map(fn ($p) => [
            'name' => $p->name,
            'open_stack_project_id' => $p->open_stack_project_id,
            'servers' => $p->servers->map(fn ($s) => [
                'name' => $s->name,
                'status' => 'stopped',
                'label' => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
                'online_since' => null,
            ])->all(),
        ])->all();

        $total = $projectModels->sum(fn ($p) => $p->servers->count());

        $lastInventory = InventoryRun::latest()->first();

        $data = [
            'projects' => $projects,
            'schedules' => collect(),
            'activity' => [],
            'total' => $total,
            'running' => 0,
            'stopped' => $total,
            'activeSchedules' => 0,
            'lastInventory' => $lastInventory,
        ];

        if ($request->header('HX-Target') === 'dashboard-content') {
            return view('partials.dashboard-content', $data);
        }

        return view('dashboard', $data);
    }
}
