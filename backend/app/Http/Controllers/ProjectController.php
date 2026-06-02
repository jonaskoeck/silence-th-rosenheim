<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServerLabel;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Services\Contracts\InventoryServiceInterface;
use App\Services\Contracts\PendingActionTrackerInterface;
use App\Services\Contracts\ProjectServiceInterface;
use App\Services\Contracts\ServerStatusServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectServiceInterface $projects,
        private InventoryServiceInterface $inventory,
        private ServerStatusServiceInterface $serverStatus,
        private PendingActionTrackerInterface $pendingActions,
    ) {}

    public function store(StoreProjectRequest $request): RedirectResponse|Response
    {
        $project = $this->projects->create($request->projectAttributes());

        $this->inventory->runForProject($project->id, triggeredAutomatically: true);

        if ($request->header('HX-Request')) {
            return $this->projectsPartial('Projekt wurde erstellt.');
        }

        return redirect()->back();
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse|Response
    {
        $this->projects->update($project, $request->projectAttributes());

        if ($request->header('HX-Request')) {
            return $this->projectsPartial("Projekt \"{$project->name}\" wurde aktualisiert.");
        }

        return redirect()
            ->route('servers')
            ->with('status', "Projekt \"{$project->name}\" wurde aktualisiert.");
    }

    public function destroy(Request $request, Project $project): RedirectResponse|Response
    {
        $this->projects->delete($project);

        if ($request->header('HX-Request')) {
            return $this->projectsPartial("Projekt \"{$project->name}\" wurde gelöscht.");
        }

        return redirect()
            ->route('servers')
            ->with('status', "Projekt \"{$project->name}\" wurde gelöscht.");
    }

    private function projectsPartial(string $toastMessage, string $toastType = 'success'): Response
    {
        $projectModels = $this->projects->getAll()->load('servers');
        $statuses = $this->serverStatus->statusesForProjects($projectModels);

        $rawStatusByServerId = [];
        foreach ($projectModels as $p) {
            foreach ($p->servers as $s) {
                $rawStatusByServerId[$s->id] = $statuses->statusFor($s->open_stack_server_id);
            }
        }
        $expectations = $this->pendingActions->expectationsFor($rawStatusByServerId);

        $projects = $projectModels->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'servers' => $p->servers->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'open_stack_server_id' => $s->open_stack_server_id,
                'raw_status' => $rawStatusByServerId[$s->id],
                'expecting' => $expectations[$s->id] ?? null,
                'label' => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
            ])->all(),
        ])->all();

        return response(view('partials.projects-list', compact('projects')))
            ->header('HX-Trigger', json_encode(['toast' => ['message' => $toastMessage, 'type' => $toastType]]));
    }
}
