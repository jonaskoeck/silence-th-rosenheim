<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Contracts\ProjectServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private ProjectServiceInterface $projects) {}

    public function index(): View
    {
        return view('projects.index', ['projects' => $this->projects->getAll()]);
    }

    public function create(): View
    {
        return view('projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $project = $this->projects->create(
            $request->only(['name', 'open_stack_project_id', 'app_credential_id', 'app_credential_secret']),
        );

        return redirect()
            ->route('projects.create')
            ->with('status', "Project stored with id {$project->id}");
    }
}
