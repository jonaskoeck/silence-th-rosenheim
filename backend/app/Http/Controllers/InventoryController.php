<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InventoryRun;
use App\Services\Contracts\InventoryServiceInterface;
use App\Services\Contracts\ProjectServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class InventoryController extends Controller
{
    public function __construct(
        private InventoryServiceInterface $inventory,
        private ProjectServiceInterface $projects,
    ) {}

    public function index(): View
    {
        $projects = $this->projects->getAll();
        $runs = InventoryRun::latest()->get();

        return view('inventory', [
            'projects' => $projects,
            'runs'     => $runs,
        ]);
    }

    public function run(): RedirectResponse
    {
        $this->inventory->runForAllProjects();

        return redirect()->route('inventory');
    }
}
