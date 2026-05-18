@extends('layouts.app')


@section('title', 'Projekte & Server')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center page-header">
        <div>
            <h1 class="h4 fw-bold mb-0">Projekte & Server</h1>
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
                       hx-get="{{ route('servers') }}"
                       hx-trigger="input changed delay:300ms"
                       hx-target="#projects-container"
                       hx-include="[name='search']">
            </div>
            <datalist id="project-suggestions">
                @foreach ($projects as $project)
                <option value="{{ $project['name'] }}">
                @endforeach
            </datalist>
        </div>
    </div>

    <div id="projects-container">
        @include('partials.projects-list')
    </div>

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
                  hx-on::after-request="if(event.detail.successful) bootstrap.Modal.getInstance(document.getElementById('createProjectModal'))?.hide()">
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
            <form id="editProjectForm" method="POST"
                  hx-target="#projects-container"
                  hx-swap="innerHTML"
                  hx-on::after-request="if(event.detail.successful) bootstrap.Modal.getInstance(document.getElementById('editProjectModal'))?.hide()">
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
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if (session('edit_project_id'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const projectId = {{ session('edit_project_id') }};
            document.getElementById('editProjectForm').action = '/projects/' + projectId;
            document.getElementById('edit-project-id').value = projectId;
            new bootstrap.Modal(document.getElementById('editProjectModal')).show();
        });
    </script>
@elseif (session('store_project_error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('createProjectModal')).show();
        });
    </script>
@endif

<form id="labelForm" method="POST" style="display:none">
    @csrf
    @method('PATCH')
    <input type="hidden" name="label" id="label-input">
</form>

<form id="startServerForm" method="POST" style="display:none">
    @csrf
</form>

<form id="stopServerForm" method="POST" style="display:none">
    @csrf
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
        htmx.ajax('GET', '/servers', {
            target: '#projects-container',
            swap: 'innerHTML',
            headers: { 'HX-Target': 'projects-container' }
        });
    });
}

function startServer(serverId) {
    const form = document.getElementById('startServerForm');
    form.action = '/servers/' + serverId + '/start';
    form.submit();
}

function stopServer(serverId) {
    const form = document.getElementById('stopServerForm');
    form.action = '/servers/' + serverId + '/stop';
    form.submit();
}
</script>
@endpush

