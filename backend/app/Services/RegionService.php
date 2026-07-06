<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Region;
use App\Services\Contracts\RegionServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class RegionService implements RegionServiceInterface
{
    public function getAll(): Collection
    {
        return Region::withCount('projects')->orderBy('code')->get();
    }

    public function findOrFail(int $id): Region
    {
        return Region::findOrFail($id);
    }

    public function create(array $attributes): Region
    {
        return Region::create($attributes);
    }

    public function update(Region $region, array $attributes): Region
    {
        $region->update($attributes);

        return $region;
    }

    public function delete(Region $region): void
    {
        $region->delete();
    }
}
