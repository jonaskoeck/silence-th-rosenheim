<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServerLabel;
use App\Models\Server;
use App\Services\Contracts\ProjectServiceInterface;
use App\Services\Contracts\ServerControlServiceInterface;
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
        private ServerControlServiceInterface $control,
    ) {}

    public function index(Request $request): View
    {
        $projectModels = $this->projects->getAll()->load('servers');

        if ($search = $request->input('search', '')) {
            $projectModels = $projectModels->filter(
                fn ($p) => str_contains(
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

    public function start(Request $request, Server $server): RedirectResponse|View|Response
    {
        try {
            $this->control->start($server);
        } catch (InvalidOpenStackCredentialsException|OpenStackServerActionException) {
            if ($request->header('HX-Request')) {
                return response()->noContent(422)->header(
                    'HX-Trigger',
                    json_encode(['toast' => ['message' => "Server \"{$server->name}\" konnte nicht gestartet werden.", 'type' => 'danger']])
                );
            }

            return back()->with('server_action_error', "Server \"{$server->name}\" konnte nicht gestartet werden.");
        }

        if ($request->header('HX-Request')) {
            return $this->projectsPartial("Server \"{$server->name}\" wurde gestartet.");
        }

        return back()->with('status', "Server \"{$server->name}\" wurde gestartet.");
    }

    public function stop(Request $request, Server $server): RedirectResponse|View|Response
    {
        try {
            $this->control->stop($server);
        } catch (InvalidOpenStackCredentialsException|OpenStackServerActionException) {
            if ($request->header('HX-Request')) {
                return response()->noContent(422)->header(
                    'HX-Trigger',
                    json_encode(['toast' => ['message' => "Server \"{$server->name}\" konnte nicht gestoppt werden.", 'type' => 'danger']])
                );
            }

            return back()->with('server_action_error', "Server \"{$server->name}\" konnte nicht gestoppt werden.");
        }

        if ($request->header('HX-Request')) {
            return $this->projectsPartial("Server \"{$server->name}\" wurde gestoppt.");
        }

        return back()->with('status', "Server \"{$server->name}\" wurde gestoppt.");
    }

    private function projectsPartial(string $toastMessage, string $toastType = 'success'): Response
    {
        $projectModels = $this->projects->getAll()->load('servers');
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

        return response(view('partials.projects-list', compact('projects')))
            ->header('HX-Trigger', json_encode(['toast' => ['message' => $toastMessage, 'type' => $toastType]]));
    }

    private static function displayStatus(?string $rawStatus): string
    {
        return $rawStatus === 'ACTIVE' ? 'running' : 'stopped';
    }
}
