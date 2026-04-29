<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServerLabel;
use App\Services\Contracts\ProjectServiceInterface;
use Illuminate\Contracts\View\View;

class ServerController extends Controller
{
    public function __construct(private ProjectServiceInterface $projects) {}

    public function index(): View
    {
        $projectModels = $this->projects->getAll()->load('servers');

        $projects = $projectModels->map(fn($p) => [
            'name'    => $p->name,
            'servers' => $p->servers->map(fn($s) => [
                'id'          => $s->id,
                'name'        => $s->name,
                'ip'          => '—',
                'status'      => 'stopped',
                'label'       => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
                'last_action' => '—',
            ])->all(),
        ])->all();

        return view('servers', ['projects' => $projects]);
    }
}
