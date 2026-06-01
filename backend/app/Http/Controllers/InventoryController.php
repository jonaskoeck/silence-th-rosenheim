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
use Illuminate\Http\Response;

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

    public function run(Request $request): RedirectResponse|View|Response
    {
        $this->inventory->runForAllProjects();

        $toast = $this->inventoryToast();

        if ($request->header('HX-Target') === 'projects-container') {
            return $this->projectsListResponse($toast);
        }

        if ($request->header('HX-Request')) {
            $runs = InventoryRun::latest()->with('discoveredServers')->get();

            return response(view('partials.inventory-runs', compact('runs')))->header('HX-Trigger', $toast);
        }

        return redirect()->back();
    }

    public function runForProject(Request $request, int $project): RedirectResponse|View|Response
    {
        $this->inventory->runForProject($project);

        if ($request->header('HX-Request')) {
            return $this->projectsListResponse($this->inventoryToast());
        }

        return redirect()->back();
    }

    private function inventoryToast(): string
    {
        $run = InventoryRun::latest()->first();

        return json_encode(['toast' => [
            'message' => $run?->had_errors ? 'Inventarisierung fehlgeschlagen.' : 'Inventarisierung abgeschlossen.',
            'type'    => $run?->had_errors ? 'danger' : 'success',
        ]]);
    }

    private function projectsListResponse(string $toast): Response
    {
        $projects = $this->projects->getAll()->load('servers')->map(fn ($p) => [
            'id'      => $p->id,
            'name'    => $p->name,
            'servers' => $p->servers->map(fn ($s) => [
                'id'                   => $s->id,
                'name'                 => $s->name,
                'open_stack_server_id' => $s->open_stack_server_id,
                'status'               => $s->status === 'ACTIVE' ? 'running' : 'stopped',
                'label'                => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
            ])->all(),
        ])->all();

        return response(view('partials.projects-list', compact('projects')))->header('HX-Trigger', $toast);
    }
}
