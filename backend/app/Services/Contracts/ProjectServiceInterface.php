<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

interface ProjectServiceInterface
{
    public function getAll(): Collection;

    public function findOrFail(int $id): Project;

    public function create(array $attributes): Project;

    public function update(Project $project, array $attributes): Project;

    public function delete(Project $project): void;
}
