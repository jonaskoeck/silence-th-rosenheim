@extends('layouts.app')

@section('title', 'Regionen')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center page-header">
        <div>
            <h1 class="h4 fw-bold mb-0 page-title">Regionen</h1>
        </div>
        <button class="btn btn-sm btn-orange" data-bs-toggle="modal" data-bs-target="#createRegionModal">
            <i class="bi bi-plus-lg me-1"></i>Neue Region
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-geo-alt me-2 text-primary"></i>OpenStack-Regionen
            </h6>
        </div>
        <div class="table-responsive" id="regions-container">
            @include('partials.regions-list', ['regions' => $regions])
        </div>
    </div>

</div>

<div class="modal fade" id="createRegionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold">Region hinzufügen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('regions.store') }}"
                  hx-post="{{ route('regions.store') }}"
                  hx-target="#regions-container"
                  hx-swap="innerHTML"
                  hx-on::after-request="if(event.detail.successful){ this.reset(); bootstrap.Modal.getInstance(document.getElementById('createRegionModal'))?.hide(); }">
                @csrf
                <div class="modal-body d-flex flex-column gap-3">
                    <div>
                        <label class="form-label small fw-semibold">Kürzel</label>
                        <input type="text" name="code" class="form-control" placeholder="z.B. muc" required>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">Host-URL (Baselink)</label>
                        <input type="text" name="host_url" class="form-control" placeholder="https://api.dc.muc.cloud.cnds.io:5000" required>
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

<div class="modal fade" id="editRegionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold">Region bearbeiten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editRegionForm" method="POST"
                  hx-target="#regions-container"
                  hx-swap="innerHTML"
                  hx-on::after-request="if(event.detail.successful) bootstrap.Modal.getInstance(document.getElementById('editRegionModal'))?.hide()">
                @csrf
                @method('PUT')
                <div class="modal-body d-flex flex-column gap-3">
                    <div>
                        <label class="form-label small fw-semibold">Kürzel</label>
                        <input type="text" name="code" id="edit-region-code" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">Host-URL (Baselink)</label>
                        <input type="text" name="host_url" id="edit-region-host-url" class="form-control" required>
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

<div class="modal fade" id="deleteRegionModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold">Region löschen</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-0">
                    Möchtest du die Region <strong id="delete-region-code"></strong> wirklich löschen?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-danger" id="deleteRegionBtn"
                        hx-target="#regions-container"
                        hx-swap="innerHTML"
                        hx-on::after-request="bootstrap.Modal.getInstance(document.getElementById('deleteRegionModal'))?.hide()">Löschen</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function prepareEditRegionModal(regionId, code, hostUrl) {
    const form = document.getElementById('editRegionForm');
    form.setAttribute('hx-put', '/regions/' + regionId);
    document.getElementById('edit-region-code').value = code;
    document.getElementById('edit-region-host-url').value = hostUrl;
    htmx.process(form);
}

function prepareDeleteRegionModal(regionId, code) {
    document.getElementById('delete-region-code').textContent = code;
    const btn = document.getElementById('deleteRegionBtn');
    btn.setAttribute('hx-delete', '/regions/' + regionId);
    htmx.process(btn);
}
</script>
@endpush
