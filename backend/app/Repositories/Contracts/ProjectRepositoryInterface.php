<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

interface ProjectRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Project;

    public function findOrFail(int $id): Project;

    public function save(Project $project): Project;

    public function delete(Project $project): void;
}
