<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\RunProjectInventoryJob;
use App\Services\Contracts\InventoryServiceInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class RunProjectInventoryJobTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Prüft: Der Job ruft `runForProject()` mit genau der Projekt-ID auf,
     * die ihm im Konstruktor übergeben wurde. Sicherstellt, dass der Job
     * die Inventory-Logik für das richtige Projekt delegiert.
     */
    public function test_handle_calls_run_for_project_with_correct_id(): void
    {
        $mock = Mockery::mock(InventoryServiceInterface::class);
        $mock->shouldReceive('runForProject')->with(42)->once();

        $job = new RunProjectInventoryJob(42);
        $job->handle($mock);
    }

    /**
     * Prüft: Jede Instanz des Jobs trägt ihre eigene Projekt-ID – zwei Jobs
     * mit unterschiedlichen IDs dürfen sich nicht gegenseitig beeinflussen.
     */
    public function test_handle_passes_the_project_id_it_was_constructed_with(): void
    {
        $mock = Mockery::mock(InventoryServiceInterface::class);
        $mock->shouldReceive('runForProject')->with(99)->once();
        $mock->shouldNotReceive('runForProject')->with(1);

        $job = new RunProjectInventoryJob(99);
        $job->handle($mock);
    }
}
