<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Contracts\InventoryServiceInterface;

class RunProjectInventoryJob
{
    public function __construct(private int $projectId) {}

    public function handle(InventoryServiceInterface $inventory): void
    {
        $inventory->runForProject($this->projectId);
    }
}
