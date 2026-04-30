<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface InventoryServiceInterface
{
    public function runForAllProjects(): void;

    public function runForProject(int $projectId): void;
}
