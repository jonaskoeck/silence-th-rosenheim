<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface InventoryServiceInterface
{
    public function runForAllProjects(bool $triggeredAutomatically = false): void;

    public function runForProject(int $projectId, bool $triggeredAutomatically = false): void;
}
