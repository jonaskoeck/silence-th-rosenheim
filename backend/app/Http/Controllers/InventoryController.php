<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
        $projects = $this->projects->getAll()->load('servers');

        return view('inventory.index', [
            'projects' => $projects,
        ]);
    }

    public function run(): RedirectResponse
    {
        $this->inventory->runForAllProjects();

        return redirect()->route('dashboard');
    }
}
