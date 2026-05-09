<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServerLabel;
use App\Models\InventoryRun;
use App\Services\Contracts\ProjectServiceInterface;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(private ProjectServiceInterface $projects) {}

    public function index(): View
    {
        $projectModels = $this->projects->getAll()->load('servers');

        $projects = $projectModels->map(fn ($p) => [
            'name' => $p->name,
            'open_stack_project_id' => $p->open_stack_project_id,
            'servers' => $p->servers->map(fn ($s) => [
                'name' => $s->name,
                'status' => $s->status === 'ACTIVE' ? 'running' : 'stopped',
                'label' => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
                'online_since' => null,
            ])->all(),
        ])->all();

        $total = $projectModels->sum(fn ($p) => $p->servers->count());
        $running = $projectModels->sum(fn ($p) => $p->servers->where('status', 'ACTIVE')->count());

        $lastInventory = InventoryRun::latest()->first();

        return view('dashboard', [
            'projects' => $projects,
            'schedules' => collect(),
            'activity' => [],
            'total' => $total,
            'running' => $running,
            'stopped' => $total - $running,
            'activeSchedules' => 0,
            'lastInventory' => $lastInventory,
        ]);
    }
}
