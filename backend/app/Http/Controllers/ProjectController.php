<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServerLabel;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Services\Contracts\InventoryServiceInterface;
use App\Services\Contracts\ProjectServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectServiceInterface $projects,
        private InventoryServiceInterface $inventory,
    ) {}

    public function store(StoreProjectRequest $request): RedirectResponse|Response
    {
        $project = $this->projects->create($request->projectAttributes());

        $this->inventory->runForProject($project->id);

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
        $projects = $this->projects->getAll()->load('servers')->map(fn ($p) => [
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

        return response(view('partials.projects-list', compact('projects')))
            ->header('HX-Trigger', json_encode(['toast' => ['message' => $toastMessage, 'type' => $toastType]]));
    }
}
