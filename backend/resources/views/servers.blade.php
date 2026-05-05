@extends('layouts.app')


@section('title', 'Projekte & Server')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center page-header">
        <div>
            <h1 class="h4 fw-bold mb-0">Projekte & Server</h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <select id="projectSelect" placeholder="Projekt wählen..." style="max-width:220px">
                @foreach ($projects as $project)
                    <option value="{{ $project['id'] }}">{{ $project['name'] }}</option>
                @endforeach
            </select>
            <form method="POST" id="manualForm" action="">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary" onclick="submitManual(event)">
                    <i class="bi bi-play-fill me-1"></i>Manuell inventarisieren
                </button>
            </form>
            <form method="POST" action="{{ route('inventory.run') }}">
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
                <input type="text" id="projectSearch" class="form-control border-start-0 ps-0"
                       placeholder="Projekt suchen..." list="project-suggestions" autocomplete="off">
            </div>
            <datalist id="project-suggestions">
                @foreach ($projects as $project)
                <option value="{{ $project['name'] }}">
                @endforeach
            </datalist>
        </div>
    </div>

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
                <form method="POST" action="{{ route('inventory.run.project', $project['id']) }}">
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
                        @php
                            [$sc, $sl] = match($srv['status']) {
                                'running' => ['success',   'Laufend'],
                                default   => ['secondary', 'Gestoppt'],
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold small">{{ $srv['name'] }}</div>
                            </td>
                            <td class="text-muted font-monospace small">{{ $srv['open_stack_server_id'] ?? '—' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $sc }} rounded-pill">{{ $sl }}</span>
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
                                    @if ($srv['status'] === 'running')
                                    <button class="btn btn-sm btn-outline-danger"
                                            data-server-action="stop"
                                            data-server-name="{{ $srv['name'] }}"
                                            data-server-id="{{ $srv['id'] }}"
                                            data-server-label="{{ $srv['label'] }}"
                                            title="Stoppen">
                                        <i class="bi bi-stop-fill"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning"
                                            data-server-action="restart"
                                            data-server-name="{{ $srv['name'] }}"
                                            data-server-id="{{ $srv['id'] }}"
                                            data-server-label="{{ $srv['label'] }}"
                                            title="Neustarten">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                    @else
                                    <button class="btn btn-sm btn-outline-success"
                                            data-server-action="start"
                                            data-server-name="{{ $srv['name'] }}"
                                            data-server-id="{{ $srv['id'] }}"
                                            title="Starten">
                                        <i class="bi bi-play-fill"></i>
                                    </button>
                                    @endif

                                    <a href="{{ route('schedules', ['server' => $srv['id']]) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Zeitpläne">
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
    <div class="text-center text-muted py-5">
        Keine Projekte vorhanden.
    </div>
    @endforelse

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
                    Möchtest du das Projekt <strong id="delete-project-name"></strong> wirklich löschen?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-danger" onclick="submitDeleteForm()">Löschen</button>
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
            <form method="POST" action="{{ route('projects.store') }}">
                @csrf
                <div class="modal-body d-flex flex-column gap-3">
                    @if (session('store_project_error'))
                        <div class="alert alert-danger small mb-0 py-2">
                            {{ $errors->first() }}
                        </div>
                    @endif
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
                    <button type="submit" class="btn btn-primary">Speichern</button>
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
            <form id="editProjectForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body d-flex flex-column gap-3">
                    @if ($errors->hasAny(['name', 'app_credential_id', 'app_credential_secret']))
                        <div class="alert alert-danger small mb-0 py-2">
                            <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
                        </div>
                    @endif
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
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->hasAny(['name', 'app_credential_id', 'app_credential_secret']))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const projectId = {{ session('edit_project_id', 0) }};
            if (projectId > 0) {
                document.getElementById('editProjectForm').action = '/projects/' + projectId;
                document.getElementById('edit-project-id').value = projectId;
            }
            const modal = new bootstrap.Modal(document.getElementById('editProjectModal'));
            modal.show();
        });
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
let pendingServerId = '';

document.addEventListener('DOMContentLoaded', () => {
    new TomSelect('#projectSelect', { maxOptions: 10 });
});

document.getElementById('projectSearch').addEventListener('input', function () {
    const normalize = str => str.toLowerCase().replace(/[^a-z0-9]/g, '');
    const query = normalize(this.value);
    document.querySelectorAll('[data-project-name]').forEach(el => {
        const card = el.closest('.card');
        if (card) card.style.display = normalize(el.dataset.projectName).includes(query) ? '' : 'none';
    });
});

function submitManual(e) {
    e.preventDefault();
    const projectId = document.getElementById('projectSelect').value;
    if (!projectId) return;
    const form = document.getElementById('manualForm');
    form.action = '/inventory/run/' + projectId;
    form.submit();
}

function prepareDeleteModal(formId, projectName) {
    document.getElementById('deleteProjectModal').dataset.targetForm = formId;
    document.getElementById('delete-project-name').textContent = projectName;
}

function submitDeleteForm() {
    const formId = document.getElementById('deleteProjectModal').dataset.targetForm;
    if (formId) document.getElementById(formId).submit();
}

function prepareEditModal(projectId, projectName) {
    document.getElementById('editProjectForm').action = '/projects/' + projectId;
    document.getElementById('edit-project-id').value  = projectId;
    document.getElementById('edit-project-name').value = projectName;
    document.getElementById('edit-project-credential-id').value    = '';
    document.getElementById('edit-project-credential-secret').value = '';
}

document.addEventListener('click', e => {
    const btn = e.target.closest('[data-bs-target="#labelModal"]');
    if (btn) {
        pendingServerId = btn.dataset.serverId;
        document.getElementById('modal-server-name').textContent = btn.dataset.serverName;
    }
});

function setLabel(label) {
    const form = document.getElementById('labelForm');
    form.action = '/servers/' + pendingServerId + '/label';
    document.getElementById('label-input').value = label.toUpperCase();
    form.submit();
}
</script>
@endpush

