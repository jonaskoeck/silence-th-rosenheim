<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServerLabel;
use App\Models\Project;
use App\Models\Server;
use App\Services\Contracts\PendingActionTrackerInterface;
use App\Services\Contracts\ProjectServiceInterface;
use App\Services\Contracts\ServerControlServiceInterface;
use App\Services\Contracts\ServerStatusServiceInterface;
use App\Services\OpenStack\Exceptions\InvalidOpenStackCredentialsException;
use App\Services\OpenStack\Exceptions\OpenStackServerActionException;
use App\Services\ServerStatusesDto;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class ProjectServerController extends Controller
{
    public function __construct(
        private ProjectServiceInterface $projects,
        private ServerControlServiceInterface $control,
        private ServerStatusServiceInterface $serverStatus,
        private PendingActionTrackerInterface $pendingActions,
    ) {}

    public function index(Request $request): View|Response
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

        $statuses = $this->serverStatus->statusesForProjects($projectModels);
        $projects = $this->mapProjects($projectModels, $statuses);

        if ($request->header('HX-Target') === 'projects-container') {
            return $this->withStatusFailureToast(
                response(view('partials.projects-list', compact('projects'))),
                $statuses,
            );
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

        $this->pendingActions->record($server->id, 'ACTIVE');

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

        $this->pendingActions->record($server->id, 'SHUTOFF');

        if ($request->header('HX-Request')) {
            return $this->projectsPartial("Server \"{$server->name}\" wurde gestoppt.");
        }

        return back()->with('status', "Server \"{$server->name}\" wurde gestoppt.");
    }

    public function status(Request $request, Server $server): View
    {
        $expecting = $request->query('expecting');
        $attempt = (int) $request->query('attempt', 0);

        return view('partials.server-status-poll-response', [
            'serverId' => $server->id,
            'rawStatus' => $this->serverStatus->statusForServer($server),
            'expecting' => is_string($expecting) && in_array($expecting, ['ACTIVE', 'SHUTOFF'], true)
                ? $expecting
                : null,
            'attempt' => max(0, $attempt),
        ]);
    }

    private function projectsPartial(string $toastMessage, string $toastType = 'success'): Response
    {
        $projectModels = $this->projects->getAll()->load('servers');
        $statuses = $this->serverStatus->statusesForProjects($projectModels);
        $projects = $this->mapProjects($projectModels, $statuses);

        return response(view('partials.projects-list', compact('projects')))
            ->header('HX-Trigger', json_encode(['toast' => ['message' => $toastMessage, 'type' => $toastType]]));
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return array<int, array<string, mixed>>
     */
    private function mapProjects($projects, ServerStatusesDto $statuses): array
    {
        return $projects->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'servers' => $p->servers->map(function ($s) use ($statuses) {
                $rawStatus = $statuses->statusFor($s->open_stack_server_id);

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'open_stack_server_id' => $s->open_stack_server_id,
                    'raw_status' => $rawStatus,
                    'expecting' => $this->pendingActions->expectationFor($s->id, $rawStatus),
                    'label' => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
                ];
            })->all(),
        ])->all();
    }

    private function withStatusFailureToast(Response $response, ServerStatusesDto $statuses): Response
    {
        if ($payload = $statuses->toastTriggerPayload()) {
            $response->header('HX-Trigger', $payload);
        }

        return $response;
    }
}
