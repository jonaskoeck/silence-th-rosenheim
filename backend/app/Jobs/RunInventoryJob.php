<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Contracts\InventoryServiceInterface;

class RunInventoryJob
{
    public function handle(InventoryServiceInterface $inventory): void
    {
        $inventory->runForAllProjects(triggeredAutomatically: true);
    }
}
