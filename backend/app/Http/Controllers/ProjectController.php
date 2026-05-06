<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServerLabel;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Services\Contracts\ProjectServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private ProjectServiceInterface $projects) {}

    public function store(StoreProjectRequest $request): RedirectResponse|View
    {
        $this->projects->create($request->projectAttributes());

        if ($request->header('HX-Request')) {
            return $this->projectsPartial();
        }

        return redirect()->back();
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse|View
    {
        $this->projects->update($project, $request->projectAttributes());

        if ($request->header('HX-Request')) {
            return $this->projectsPartial();
        }

        return redirect()
            ->route('servers')
            ->with('status', "Projekt \"{$project->name}\" wurde aktualisiert.");
    }

    public function destroy(Request $request, Project $project): RedirectResponse|View
    {
        $this->projects->delete($project);

        if ($request->header('HX-Request')) {
            return $this->projectsPartial();
        }

        return redirect()
            ->route('servers')
            ->with('status', "Projekt \"{$project->name}\" wurde gelöscht.");
    }

    private function projectsPartial(): View
    {
        $projects = $this->projects->getAll()->load('servers')->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'servers' => $p->servers->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'open_stack_server_id' => $s->open_stack_server_id,
                'status' => 'stopped',
                'label' => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
            ])->all(),
        ])->all();

        return view('partials.projects-list', compact('projects'));
    }
}
