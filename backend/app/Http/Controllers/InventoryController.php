<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServerLabel;
use App\Models\InventoryRun;
use App\Services\Contracts\InventoryServiceInterface;
use App\Services\Contracts\ProjectServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        private InventoryServiceInterface $inventory,
        private ProjectServiceInterface $projects,
    ) {}

    public function index(): View
    {
        $projects = $this->projects->getAll();
        $runs = InventoryRun::latest()->with('discoveredServers')->get();

        return view('inventory', [
            'projects' => $projects,
            'runs' => $runs,
        ]);
    }

    public function run(Request $request): RedirectResponse|View
    {
        $this->inventory->runForAllProjects();

        if ($request->header('HX-Target') === 'projects-container') {
            $projectModels = $this->projects->getAll()->load('servers');
            $projects = $projectModels->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'servers' => $p->servers->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'open_stack_server_id' => $s->open_stack_server_id,
                    'status' => $s->status === 'ACTIVE' ? 'running' : 'stopped',
                    'label' => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
                ])->all(),
            ])->all();

            return view('partials.projects-list', compact('projects'));
        }

        if ($request->header('HX-Request')) {
            $runs = InventoryRun::latest()->with('discoveredServers')->get();

            return view('partials.inventory-runs', compact('runs'));
        }

        return redirect()->back();
    }

    public function runForProject(Request $request, int $project): RedirectResponse|View
    {
        $this->inventory->runForProject($project);

        if ($request->header('HX-Request')) {
            $projectModels = $this->projects->getAll()->load('servers');
            $projects = $projectModels->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'servers' => $p->servers->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'open_stack_server_id' => $s->open_stack_server_id,
                    'status' => $s->status === 'ACTIVE' ? 'running' : 'stopped',
                    'label' => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
                ])->all(),
            ])->all();

            return view('partials.projects-list', compact('projects'));
        }

        return redirect()->back();
    }
}
