<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function all(): Collection
    {
        return Project::all();
    }

    public function find(int $id): ?Project
    {
        return Project::find($id);
    }

    public function findOrFail(int $id): Project
    {
        return Project::findOrFail($id);
    }

    public function save(Project $project): Project
    {
        $project->save();

        return $project;
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }
}
