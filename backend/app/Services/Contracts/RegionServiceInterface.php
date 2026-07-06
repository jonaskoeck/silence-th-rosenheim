<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Region;
use Illuminate\Database\Eloquent\Collection;

interface RegionServiceInterface
{
    public function getAll(): Collection;

    public function findOrFail(int $id): Region;

    public function create(array $attributes): Region;

    public function update(Region $region, array $attributes): Region;

    public function delete(Region $region): void;
}
