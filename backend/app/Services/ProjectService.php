<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Services\Contracts\ProjectServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class ProjectService implements ProjectServiceInterface
{
    public function getAll(): Collection
    {
        return Project::with('region')->get();
    }

    public function findOrFail(int $id): Project
    {
        return Project::findOrFail($id);
    }

    public function create(array $attributes): Project
    {
        return Project::create($attributes);
    }

    public function update(Project $project, array $attributes): Project
    {
        $project->update($attributes);

        return $project;
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }
}
