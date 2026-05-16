<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServerLabel;
use App\Models\Server;
use App\Services\Contracts\OpenStackClientInterface;
use App\Services\Contracts\ProjectServiceInterface;
use App\Services\OpenStack\Exceptions\InvalidOpenStackCredentialsException;
use App\Services\OpenStack\Exceptions\OpenStackServerActionException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProjectServerController extends Controller
{
    public function __construct(
        private ProjectServiceInterface $projects,
        private OpenStackClientInterface $openStack,
    ) {}

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
                'status' => self::displayStatus($s->status),
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

    public function start(Server $server): RedirectResponse
    {
        $project = $server->project;

        try {
            $auth = $this->openStack->authenticate(
                $project->app_credential_id,
                $project->app_credential_secret,
            );

            $this->openStack->startServer(
                $auth->token,
                $auth->computeEndpoint,
                $server->open_stack_server_id,
            );

            $fresh = $this->openStack->getServer(
                $auth->token,
                $auth->computeEndpoint,
                $server->open_stack_server_id,
            );

            $server->update(['status' => $fresh['status'] ?? null]);
        } catch (InvalidOpenStackCredentialsException|OpenStackServerActionException $e) {
            return back()->with('server_action_error', "Server \"{$server->name}\" konnte nicht gestartet werden.");
        }

        return back()->with('status', "Server \"{$server->name}\" wurde gestartet.");
    }

    public function stop(Server $server): RedirectResponse
    {
        $project = $server->project;

        try {
            $auth = $this->openStack->authenticate(
                $project->app_credential_id,
                $project->app_credential_secret,
            );

            $this->openStack->stopServer(
                $auth->token,
                $auth->computeEndpoint,
                $server->open_stack_server_id,
            );

            $fresh = $this->openStack->getServer(
                $auth->token,
                $auth->computeEndpoint,
                $server->open_stack_server_id,
            );

            $server->update(['status' => $fresh['status'] ?? null]);
        } catch (InvalidOpenStackCredentialsException|OpenStackServerActionException $e) {
            return back()->with('server_action_error', "Server \"{$server->name}\" konnte nicht gestoppt werden.");
        }

        return back()->with('status', "Server \"{$server->name}\" wurde gestoppt.");
    }

    private static function displayStatus(?string $rawStatus): string
    {
        return $rawStatus === 'ACTIVE' ? 'running' : 'stopped';
    }
}
