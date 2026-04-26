<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Services\Contracts\ProjectServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class ProjectService implements ProjectServiceInterface
{
    public function __construct(private ProjectRepositoryInterface $projects) {}

    public function getAll(): Collection
    {
        return $this->projects->all();
    }

    public function findOrFail(int $id): Project
    {
        return $this->projects->findOrFail($id);
    }

    public function create(array $attributes): Project
    {
        $project = new Project($attributes);

        return $this->projects->save($project);
    }

    public function update(Project $project, array $attributes): Project
    {
        $project->fill($attributes);

        return $this->projects->save($project);
    }

    public function delete(Project $project): void
    {
        $this->projects->delete($project);
    }
}
