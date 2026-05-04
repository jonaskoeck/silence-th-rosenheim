<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServerLabel;
use App\Services\Contracts\ProjectServiceInterface;
use Illuminate\Contracts\View\View;

class ProjectServerController extends Controller
{
    public function __construct(private ProjectServiceInterface $projects) {}

    public function index(): View
    {
        $projectModels = $this->projects->getAll()->load('servers');

        $projects = $projectModels->map(fn($p) => [
            'id'      => $p->id,
            'name'    => $p->name,
            'servers' => $p->servers->map(fn($s) => [
                'id'                   => $s->id,
                'name'                 => $s->name,
                'open_stack_server_id' => $s->open_stack_server_id,
                'status'               => 'stopped',
                'label'                => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
            ])->all(),
        ])->all();

        return view('servers', ['projects' => $projects]);
    }
}
