@extends('layouts.app')


@section('title', 'Projekte & Server')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center page-header">
        <div>
            <h1 class="h4 fw-bold mb-0 page-title">Projekte & Server</h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="POST" action="{{ route('inventory.run') }}"
                  hx-post="{{ route('inventory.run') }}"
                  hx-target="#projects-container"
                  hx-swap="innerHTML"
                  hx-on::before-request="window._collapseRestoreAfterSwap=[...document.querySelectorAll('.collapse.show')].map(el=>el.id)">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-arrow-repeat me-1"></i>Alle Projekte inventarisieren
                </button>
            </form>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createProjectModal">
                <i class="bi bi-plus-lg me-1"></i>Projekt
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2 px-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" id="projectSearch" name="search"
                       class="form-control border-start-0 ps-0"
                       placeholder="Projekt suchen..." list="project-suggestions"
                       hx-get="{{ route('servers.data') }}"
                       hx-trigger="input changed delay:300ms"
                       hx-target="#projects-container"
                       hx-swap="innerHTML"
                       hx-include="[name='search']">
                <select id="projectFilter" class="form-select form-select-sm border-start-0" style="max-width:11rem;color:var(--bs-secondary-color)">
                    <option value="all">Alle</option>
                    <option value="running">Laufend</option>
                    <option value="stopped">Gestoppt</option>
                </select>
            </div>
            <datalist id="project-suggestions">
                @foreach ($projects as $project)
                <option value="{{ $project['name'] }}">
                @endforeach
            </datalist>
        </div>
    </div>

    <div id="projects-container">
        @forelse ($projects as $index => $project)
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white py-0 d-flex align-items-stretch justify-content-between">
                <div class="d-flex align-items-center flex-grow-1 py-3 collapsed" style="cursor:pointer"
                     data-bs-toggle="collapse" data-bs-target="#project-{{ $index }}"
                     data-project-name="{{ $project['name'] }}">
                    <i class="bi bi-chevron-right me-2 text-muted collapse-icon-closed" style="font-size:0.85rem"></i>
                    <i class="bi bi-chevron-down me-2 text-muted collapse-icon-open" style="font-size:0.85rem"></i>
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
                    <table class="table table-hover align-middle mb-0 dash-table" style="table-layout:fixed">
                        <colgroup><col style="width:20%"><col style="width:28%"><col style="width:12%"><col style="width:15%"><col style="width:25%"></colgroup>
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
                                <td class="text-muted font-monospace small" style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis">{{ $srv['open_stack_server_id'] ?? '—' }}</td>
                                <td>
                                    <span id="srv-status-{{ $srv['id'] }}" class="placeholder-glow">
                                        <span class="placeholder rounded-pill" style="width:4.5rem;height:1.275rem;display:inline-block"></span>
                                    </span>
                                </td>
                                <td>
                                    @if ($srv['label'] === 'production')
                                    <span class="badge text-bg-danger rounded-pill">Produktiv</span>
                                    @elseif ($srv['label'] === 'test')
                                    <span class="badge text-bg-info rounded-pill">Test</span>
                                    @elseif ($srv['label'] === 'development')
                                    <span class="badge text-bg-primary rounded-pill">Entwicklung</span>
                                    @else
                                    <span class="badge text-bg-secondary rounded-pill">Unkategorisiert</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <button id="server-toggle-{{ $srv['id'] }}" class="btn btn-sm btn-outline-secondary" disabled title="Lädt…">
                                            <span class="spinner-border spinner-border-sm" style="width:0.75em;height:0.75em"></span>
                                        </button>
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
                                <td colspan="5" class="text-center text-muted small py-3">Keine Server in diesem Projekt.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">Keine Projekte vorhanden.</div>
        </div>
        @endforelse
    </div>

    <div id="status-sink" hidden></div>

</div>

<div class="modal fade" id="deleteProjectModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold">Projekt löschen</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-0">
                    Möchtest du das Projekt <strong id="delete-project-name" style="word-break:break-all"></strong> wirklich löschen?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-danger" id="deleteProjectBtn"
                        hx-target="#projects-container"
                        hx-swap="innerHTML"
                        hx-on::after-request="bootstrap.Modal.getInstance(document.getElementById('deleteProjectModal'))?.hide()">Löschen</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createProjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold">Projekt hinzufügen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('projects.store') }}"
                  hx-post="{{ route('projects.store') }}"
                  hx-target="#projects-container"
                  hx-swap="innerHTML"
                  hx-on::before-request="setFormLoading(this, true)"
                  hx-on::after-request="setFormLoading(this, false); if(event.detail.successful) bootstrap.Modal.getInstance(document.getElementById('createProjectModal'))?.hide()">
                @csrf
                <div class="modal-body d-flex flex-column gap-3">
                    <div>
                        <label class="form-label small fw-semibold">Name</label>
                        <input type="text" name="name" class="form-control">
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">App Credential ID</label>
                        <input type="text" name="app_credential_id" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">App Credential Secret</label>
                        <input type="password" name="app_credential_secret" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-orange">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editProjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold">Projekt bearbeiten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProjectForm" method="POST"
                  hx-target="#projects-container"
                  hx-swap="innerHTML"
                  hx-on::before-request="setFormLoading(this, true)"
                  hx-on::after-request="setFormLoading(this, false); if(event.detail.successful) bootstrap.Modal.getInstance(document.getElementById('editProjectModal'))?.hide()">
                @csrf
                @method('PUT')
                <div class="modal-body d-flex flex-column gap-3">
                    <input type="hidden" id="edit-project-id">
                    <div>
                        <label class="form-label small fw-semibold">Name</label>
                        <input type="text" name="name" id="edit-project-name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}">
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">App Credential ID</label>
                        <input type="text" name="app_credential_id" id="edit-project-credential-id"
                               class="form-control @error('app_credential_id') is-invalid @enderror" placeholder="Leer lassen um beizubehalten"
                               value="{{ old('app_credential_id') }}">
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">App Credential Secret</label>
                        <input type="password" name="app_credential_secret" id="edit-project-credential-secret"
                               class="form-control @error('app_credential_secret') is-invalid @enderror" placeholder="Leer lassen um beizubehalten"
                               value="{{ old('app_credential_secret') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-orange">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if (session('edit_project_id'))
    <script>
        (function () {
            function openEditProjectModal() {
                const projectId = {{ session('edit_project_id') }};
                document.getElementById('editProjectForm').action = '/projects/' + projectId;
                document.getElementById('edit-project-id').value = projectId;
                new bootstrap.Modal(document.getElementById('editProjectModal')).show();
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', openEditProjectModal);
            } else {
                openEditProjectModal();
            }
        })();
    </script>
@elseif (session('store_project_error'))
    <script>
        (function () {
            function openCreateProjectModal() {
                new bootstrap.Modal(document.getElementById('createProjectModal')).show();
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', openCreateProjectModal);
            } else {
                openCreateProjectModal();
            }
        })();
    </script>
@endif

<form id="labelForm" method="POST" style="display:none">
    @csrf
    @method('PATCH')
    <input type="hidden" name="label" id="label-input">
</form>

<div class="modal fade" id="labelModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold">
                    <i class="bi bi-tag me-2"></i>Label ändern
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Server: <strong id="modal-server-name"></strong>
                </p>
                <input type="hidden" id="modal-server-id">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-danger" onclick="setLabel('production')">
                        Produktiv setzen
                    </button>
                    <button type="button" class="btn btn-outline-warning" onclick="setLabel('test')">
                        Test setzen
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="setLabel('development')">
                        Entwicklung setzen
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="setLabel('none')">
                        None setzen
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
htmx.ajax('GET', '{{ route('servers.statuses') }}', { target: '#status-sink', swap: 'innerHTML' });

function applyProjectFilter() {
    const filter = document.getElementById('projectFilter')?.value ?? 'all';
    document.querySelectorAll('#projects-container > .card').forEach(card => {
        let visible = 0;
        card.querySelectorAll('tbody tr').forEach(row => {
            if (filter === 'all') { row.style.display = ''; visible++; return; }
            const badge = row.querySelector('.badge:not(.badge-label)');
            if (!badge) { row.style.display = 'none'; return; }
            const matches = filter === 'running'
                ? badge.classList.contains('text-bg-success')
                : badge.classList.contains('text-bg-secondary');
            row.style.display = matches ? '' : 'none';
            if (matches) visible++;
        });
        card.style.display = filter === 'all' || visible > 0 ? '' : 'none';
    });
}

document.getElementById('projectFilter').addEventListener('change', applyProjectFilter);
document.addEventListener('htmx:afterSettle', applyProjectFilter);

(function () {
    const f = new URLSearchParams(window.location.search).get('filter');
    if (f === 'running' || f === 'stopped') {
        document.getElementById('projectFilter').value = f;
    }
})();

function setFormLoading(form, loading) {
    const btn = form.querySelector('button[type=submit]');
    btn.disabled = loading;
    btn.innerHTML = loading
        ? '<span class="spinner-border spinner-border-sm me-1"></span>Speichern…'
        : 'Speichern';
}

var pendingServerId = '';


document.getElementById('projectSearch').addEventListener('input', function () {
    const norm = str => str.toLowerCase().replace(/[^a-z0-9]/g, '');
    const q = norm(this.value);
    document.querySelectorAll('#projects-container > .card').forEach(card => {
        const header = card.querySelector('[data-project-name]');
        if (!header) return;
        if (!q) {
            card.style.display = '';
            card.querySelectorAll('tbody tr').forEach(r => r.style.display = '');
            return;
        }
        if (norm(header.dataset.projectName).includes(q)) {
            card.style.display = '';
            card.querySelectorAll('tbody tr').forEach(r => r.style.display = '');
            return;
        }
        let anyVisible = false;
        card.querySelectorAll('tbody tr').forEach(row => {
            const cells = row.querySelectorAll('td');
            const name = norm(cells[0]?.textContent ?? '');
            const id   = norm(cells[1]?.textContent ?? '');
            const matches = name.includes(q) || id.includes(q);
            row.style.display = matches ? '' : 'none';
            if (matches) anyVisible = true;
        });
        card.style.display = anyVisible ? '' : 'none';
    });
});

function prepareDeleteModal(formId, projectName) {
    document.getElementById('delete-project-name').textContent = projectName;
    const btn = document.getElementById('deleteProjectBtn');
    const projectId = formId.replace('delete-form-', '');
    btn.setAttribute('hx-delete', '/projects/' + projectId);
    htmx.process(btn);
}

function prepareEditModal(projectId, projectName) {
    const editForm = document.getElementById('editProjectForm');
    editForm.action = '/projects/' + projectId;
    editForm.setAttribute('hx-put', '/projects/' + projectId);
    htmx.process(editForm);
    document.getElementById('edit-project-id').value  = projectId;
    document.getElementById('edit-project-name').value = projectName;
    document.getElementById('edit-project-credential-id').value    = '';
    document.getElementById('edit-project-credential-secret').value = '';
    document.querySelector('#editProjectModal .alert-danger')?.remove();
    document.querySelectorAll('#editProjectModal .is-invalid').forEach(el => el.classList.remove('is-invalid'));
}

document.getElementById('createProjectModal').addEventListener('hidden.bs.modal', () => {
    document.querySelector('#createProjectModal .alert-danger')?.remove();
});

document.addEventListener('click', e => {
    const btn = e.target.closest('[data-bs-target="#labelModal"]');
    if (btn) {
        pendingServerId = btn.dataset.serverId;
        document.getElementById('modal-server-name').textContent = btn.dataset.serverName;
    }
});

function setLabel(label) {
    const openCollapses = [...document.querySelectorAll('.collapse.show')].map(el => el.id);
    const token = document.querySelector('meta[name="csrf-token"]').content;

    bootstrap.Modal.getInstance(document.getElementById('labelModal'))?.hide();

    fetch('/servers/' + pendingServerId + '/label', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ label: label.toUpperCase() })
    }).then(() => {
        window._collapseRestoreAfterSwap = openCollapses;
        htmx.ajax('GET', '/servers/data', {
            target: '#projects-container',
            swap: 'innerHTML',
        });
    });
}

</script>
@endpush

