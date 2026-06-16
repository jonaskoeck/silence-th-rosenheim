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
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <div class="input-group input-group-sm flex-grow-1" style="min-width:12rem">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" id="projectSearch" name="search"
                           class="form-control border-start-0 ps-0"
                           placeholder="Projekt suchen..." list="project-suggestions">
                </div>
                <select id="projectRegionFilter" class="form-select form-select-sm" style="max-width:11rem;color:var(--bs-secondary-color)">
                    <option value="all">Alle Regionen</option>
                    @foreach ($regions as $region)
                    <option value="{{ $region->code }}">{{ $region->code }}</option>
                    @endforeach
                </select>
                <select id="projectFilter" class="form-select form-select-sm" style="max-width:11rem;color:var(--bs-secondary-color)">
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
        @include('partials.projects-list')
    </div>

    <div id="no-results" class="card border-0 shadow-sm" style="display:none">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-search me-1"></i>Keine Suchtreffer.
        </div>
    </div>

    <div id="status-sink" hidden
         hx-get="{{ route('servers.statuses') }}"
         hx-trigger="load"
         hx-swap="innerHTML"></div>

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
                    @if ($regions->isEmpty())
                    <div class="alert alert-warning small mb-0" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Es ist noch keine Region vorhanden. Bitte zuerst eine
                        <a href="{{ route('regions') }}"
                           hx-get="{{ route('regions') }}" hx-target="#main-content" hx-swap="innerHTML" hx-push-url="true"
                           data-bs-dismiss="modal" class="alert-link">Region anlegen</a>,
                        bevor ein Projekt hinzugefügt werden kann.
                    </div>
                    @else
                    <div>
                        <label class="form-label small fw-semibold">Name</label>
                        <input type="text" name="name" class="form-control">
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">Region</label>
                        <select name="region_id" class="form-select" required>
                            <option value="" disabled selected>Region wählen…</option>
                            @foreach ($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->code }} — {{ $region->host_url }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">App Credential ID</label>
                        <input type="text" name="app_credential_id" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">App Credential Secret</label>
                        <input type="password" name="app_credential_secret" class="form-control" required>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    @unless ($regions->isEmpty())
                    <button type="submit" class="btn btn-orange">Speichern</button>
                    @endunless
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
                        <label class="form-label small fw-semibold">Region</label>
                        <select name="region_id" id="edit-project-region" class="form-select @error('region_id') is-invalid @enderror">
                            @foreach ($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->code }} — {{ $region->host_url }}</option>
                            @endforeach
                        </select>
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
                    <button type="button" class="btn btn-outline-label-dev" onclick="setLabel('development')">
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

// Single source of truth for filtering: combines the text search and the status
// dropdown. Re-run on every htmx settle (e.g. status polling) so neither filter
// gets clobbered by an unrelated swap.
function applyFilters() {
    const norm = str => str.toLowerCase().replace(/[^a-z0-9]/g, '');
    const q = norm(document.getElementById('projectSearch')?.value ?? '');
    const statusFilter = document.getElementById('projectFilter')?.value ?? 'all';
    const regionFilter = document.getElementById('projectRegionFilter')?.value ?? 'all';

    let visibleCards = 0;

    document.querySelectorAll('#projects-container > .card').forEach(card => {
        const header = card.querySelector('[data-project-name]');

        // The "no projects" empty-state card has no project header — leave it alone.
        if (!header) {
            return;
        }

        // Region is a project-level attribute -> hide the whole card when it doesn't match.
        if (regionFilter !== 'all' && card.dataset.region !== regionFilter) {
            card.style.display = 'none';
            return;
        }

        const projectNameMatches = !q || norm(header.dataset.projectName).includes(q);
        let anyVisible = false;

        card.querySelectorAll('tbody tr').forEach(row => {
            const cells = row.querySelectorAll('td');
            const name = norm(cells[0]?.textContent ?? '');
            const id = norm(cells[1]?.textContent ?? '');
            const matchesSearch = projectNameMatches || name.includes(q) || id.includes(q);

            let matchesStatus = true;
            if (statusFilter !== 'all') {
                const badge = row.querySelector('.badge:not(.badge-label)');
                // No status badge yet means it is still loading -> keep visible until it resolves.
                if (badge) {
                    matchesStatus = statusFilter === 'running'
                        ? badge.classList.contains('text-bg-success')
                        : badge.classList.contains('text-bg-secondary');
                }
            }

            const show = matchesSearch && matchesStatus;
            row.style.display = show ? '' : 'none';
            if (show) {
                anyVisible = true;
            }
        });

        card.style.display = anyVisible ? '' : 'none';
        if (anyVisible) {
            visibleCards++;
        }
    });

    // Show a hint when an active search/filter matches nothing.
    const filtering = q !== '' || statusFilter !== 'all' || regionFilter !== 'all';
    const noResults = document.getElementById('no-results');
    if (noResults) {
        noResults.style.display = (filtering && visibleCards === 0) ? '' : 'none';
    }
}

document.getElementById('projectFilter').addEventListener('change', applyFilters);
document.getElementById('projectRegionFilter').addEventListener('change', applyFilters);
document.getElementById('projectSearch').addEventListener('input', applyFilters);
document.addEventListener('htmx:afterSettle', applyFilters);

(function () {
    const f = new URLSearchParams(window.location.search).get('filter');
    if (f === 'running' || f === 'stopped') {
        document.getElementById('projectFilter').value = f;
    }
    applyFilters();
})();

function setFormLoading(form, loading) {
    const btn = form.querySelector('button[type=submit]');
    btn.disabled = loading;
    btn.innerHTML = loading
        ? '<span class="spinner-border spinner-border-sm me-1"></span>Speichern…'
        : 'Speichern';
}

var pendingServerId = '';

function prepareDeleteModal(formId, projectName) {
    document.getElementById('delete-project-name').textContent = projectName;
    const btn = document.getElementById('deleteProjectBtn');
    const projectId = formId.replace('delete-form-', '');
    btn.setAttribute('hx-delete', '/projects/' + projectId);
    htmx.process(btn);
}

function prepareEditModal(projectId, projectName, regionId) {
    const editForm = document.getElementById('editProjectForm');
    editForm.action = '/projects/' + projectId;
    editForm.setAttribute('hx-put', '/projects/' + projectId);
    htmx.process(editForm);
    document.getElementById('edit-project-id').value  = projectId;
    document.getElementById('edit-project-name').value = projectName;
    document.getElementById('edit-project-region').value = regionId;
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

