@forelse ($projects as $index => $project)
<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-0 d-flex align-items-stretch justify-content-between">
        <div class="d-flex align-items-center flex-grow-1 py-3" style="cursor:pointer"
             data-bs-toggle="collapse" data-bs-target="#project-{{ $index }}"
             data-project-name="{{ $project['name'] }}">
            <i class="bi bi-chevron-down me-2 text-muted" style="font-size:0.85rem"></i>
            <span class="fw-semibold">{{ $project['name'] }}</span>
        </div>
        <div class="d-flex gap-1 align-items-center py-3">
            <button class="btn btn-sm btn-outline-secondary"
                    data-bs-toggle="modal" data-bs-target="#editProjectModal"
                    onclick="prepareEditModal({{ $project['id'] }}, '{{ addslashes($project['name']) }}')"
                    title="Projekt bearbeiten">
                <i class="bi bi-pencil"></i>
            </button>
            <form id="delete-form-{{ $project['id'] }}" method="POST"
                  action="{{ route('projects.destroy', $project['id']) }}" style="display:none">
                @csrf
                @method('DELETE')
            </form>
            <button class="btn btn-sm btn-outline-danger"
                    data-bs-toggle="modal" data-bs-target="#deleteProjectModal"
                    onclick="prepareDeleteModal('delete-form-{{ $project['id'] }}', '{{ addslashes($project['name']) }}')"
                    title="Projekt löschen">
                <i class="bi bi-trash"></i>
            </button>
            <form method="POST" action="{{ route('inventory.run.project', $project['id']) }}"
                  hx-post="{{ route('inventory.run.project', $project['id']) }}"
                  hx-target="#projects-container"
                  hx-swap="innerHTML"
                  hx-on::before-request="window._collapseRestoreAfterSwap=[...document.querySelectorAll('.collapse.show')].map(el=>el.id)">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary" title="Inventarisieren">
                    <i class="bi bi-arrow-repeat"></i>
                </button>
            </form>
        </div>
    </div>
    <div class="collapse" id="project-{{ $index }}">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>OpenStack ID</th>
                        <th>Status</th>
                        <th>Typ</th>
                        <th class="text-end">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($project['servers'] as $srv)
                    <tr>
                        <td><div class="fw-semibold small">{{ $srv['name'] }}</div></td>
                        <td class="text-muted font-monospace small">{{ $srv['open_stack_server_id'] ?? '—' }}</td>
                        <td>@include('partials.server-status-badge', ['serverId' => $srv['id'], 'rawStatus' => $srv['raw_status'], 'expecting' => $srv['expecting'] ?? null])</td>
                        <td>
                            @if ($srv['label'] === 'production')
                            <span class="badge text-bg-danger rounded-pill badge-label">Produktiv</span>
                            @elseif ($srv['label'] === 'test')
                            <span class="badge text-bg-info rounded-pill badge-label">Test</span>
                            @elseif ($srv['label'] === 'development')
                            <span class="badge text-bg-primary rounded-pill badge-label">Entwicklung</span>
                            @else
                            <span class="badge text-bg-secondary rounded-pill badge-label">Unkategorisiert</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                @include('partials.server-toggle-button', ['serverId' => $srv['id'], 'rawStatus' => $srv['raw_status'], 'expecting' => $srv['expecting'] ?? null])
                                <a href="{{ route('schedules', ['server' => $srv['id'], 'edit' => 1]) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Zeitpläne"
                                   hx-get="{{ route('schedules', ['server' => $srv['id'], 'edit' => 1]) }}"
                                   hx-target="#main-content"
                                   hx-swap="innerHTML"
                                   hx-push-url="true">
                                    <i class="bi bi-clock"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#labelModal"
                                        data-server-id="{{ $srv['id'] }}"
                                        data-server-name="{{ $srv['name'] }}"
                                        data-server-label="{{ $srv['label'] }}"
                                        title="Label ändern">
                                    <i class="bi bi-tag"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted small py-3">
                            Keine Server in diesem Projekt.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@empty
<div class="text-center text-muted py-5">Keine Projekte vorhanden.</div>
@endforelse
