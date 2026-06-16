<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ServerLabel;
use App\Models\Project;
use App\Models\Server;
use App\Services\Contracts\PendingActionTrackerInterface;
use App\Services\Contracts\ProjectServiceInterface;
use App\Services\Contracts\RegionServiceInterface;
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
        private RegionServiceInterface $regions,
    ) {}

    public function index(Request $request): View|Response
    {
        $projects = $this->projects->getAll()->load('servers')->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'region_id' => $p->region_id,
            'servers' => $p->servers->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'open_stack_server_id' => $s->open_stack_server_id,
                'label' => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
            ])->all(),
        ])->all();

        $regions = $this->regions->getAll();

        $view = view('servers', compact('projects', 'regions'));

        if ($request->header('HX-Request')) {
            return response($view);
        }

        return $view;
    }

    public function statusAll(): Response
    {
        $projectModels = $this->projects->getAll()->load('servers');
        $statuses = $this->serverStatus->statusesForProjects($projectModels);
        $projects = $this->mapProjects($projectModels, $statuses);

        return $this->withStatusFailureToast(
            response(view('partials.projects-status-oob', compact('projects'))),
            $statuses,
        );
    }

    public function data(Request $request): Response
    {
        $projectModels = $this->projects->getAll()->load('servers');

        if ($search = $request->input('search', '')) {
            $q = strtolower(preg_replace('/[^a-z0-9]/i', '', $search));
            $norm = fn ($s) => strtolower(preg_replace('/[^a-z0-9]/i', '', $s ?? ''));
            $projectModels = $projectModels->filter(
                fn ($p) => str_contains($norm($p->name), $q)
                    || $p->servers->contains(
                        fn ($s) => str_contains($norm($s->name), $q)
                            || str_contains($norm($s->open_stack_server_id), $q)
                    )
            );
        }

        $statuses = $this->serverStatus->statusesForProjects($projectModels);
        $projects = $this->mapProjects($projectModels, $statuses);

        return $this->withStatusFailureToast(
            response(view('partials.projects-list', compact('projects'))),
            $statuses,
        );
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
            return response(view('partials.server-status-oob', [
                'serverId' => $server->id,
                'rawStatus' => 'SHUTOFF',
                'expecting' => 'ACTIVE',
            ]))->header('HX-Trigger', json_encode(['toast' => ['message' => "Server \"{$server->name}\" wurde gestartet.", 'type' => 'success']]));
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
            return response(view('partials.server-status-oob', [
                'serverId' => $server->id,
                'rawStatus' => 'ACTIVE',
                'expecting' => 'SHUTOFF',
            ]))->header('HX-Trigger', json_encode(['toast' => ['message' => "Server \"{$server->name}\" wurde gestoppt.", 'type' => 'success']]));
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

    /**
     * @param  Collection<int, Project>  $projects
     * @return array<int, array<string, mixed>>
     */
    private function mapProjects($projects, ServerStatusesDto $statuses): array
    {
        $rawStatusByServerId = [];
        foreach ($projects as $p) {
            foreach ($p->servers as $s) {
                $rawStatusByServerId[$s->id] = $statuses->statusFor($s->open_stack_server_id);
            }
        }
        $expectations = $this->pendingActions->expectationsFor($rawStatusByServerId);

        return $projects->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'region_id' => $p->region_id,
            'servers' => $p->servers->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'open_stack_server_id' => $s->open_stack_server_id,
                'raw_status' => $rawStatusByServerId[$s->id],
                'expecting' => $expectations[$s->id] ?? null,
                'label' => strtolower($s->label instanceof ServerLabel ? $s->label->value : $s->label),
            ])->all(),
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
