<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServerLabel;
use App\Services\Contracts\ProjectServiceInterface;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(private ProjectServiceInterface $projects) {}

    public function index(): View
    {
        $projectModels = $this->projects->getAll()->load('servers');

        $projects = $projectModels->map(fn($p) => [
            'name'    => $p->name,
            'servers' => $p->servers->map(fn($s) => [
                'name'         => $s->name,
                'ip'           => '—',
                'status'       => 'stopped',
                'label'        => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
                'online_since' => null,
            ])->all(),
        ])->all();

        $total = $projectModels->sum(fn($p) => $p->servers->count());

        return view('dashboard', [
            'projects'        => $projects,
            'schedules'       => collect(),
            'activity'        => [],
            'total'           => $total,
            'running'         => 0,
            'stopped'         => $total,
            'activeSchedules' => 0,
            'time'            => fn(?string $since): string => 'k.A.',
        ]);
    }
}
