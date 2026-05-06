<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServerLabel;
use App\Models\Server;
use App\Services\Contracts\ProjectServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProjectServerController extends Controller
{
    public function __construct(private ProjectServiceInterface $projects) {}

    public function index(Request $request): View
    {
        $projectModels = $this->projects->getAll()->load('servers');

        if ($search = $request->input('search', '')) {
            $projectModels = $projectModels->filter(
                fn($p) => str_contains(
                    strtolower(preg_replace('/[^a-z0-9]/i', '', $p->name)),
                    strtolower(preg_replace('/[^a-z0-9]/i', '', $search))
                )
            );
        }

        $projects = $projectModels->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'servers' => $p->servers->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'open_stack_server_id' => $s->open_stack_server_id,
                'status' => 'stopped',
                'label' => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
            ])->all(),
        ])->all();

        if ($request->header('HX-Target') === 'projects-container') {
            return view('partials.projects-list', compact('projects'));
        }

        return view('servers', compact('projects'));
    }

    public function updateLabel(Request $request, Server $server): Response|RedirectResponse
    {
        $request->validate(['label' => ['required', 'string', 'in:NONE,DEVELOPMENT,TEST,PRODUCTION']]);
        $server->update(['label' => ServerLabel::from($request->input('label'))]);

        if ($request->header('HX-Target')) {
            return response()->noContent();
        }

        return back();
    }
}
