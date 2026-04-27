<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Services\Contracts\ProjectServiceInterface;
use Illuminate\Http\RedirectResponse;

class ProjectController extends Controller
{
    public function __construct(private ProjectServiceInterface $projects) {}

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = $this->projects->create($request->projectAttributes());

        return redirect()
            ->route('dashboard')
            ->with('status', "Project stored with id {$project->id}");
    }
}
