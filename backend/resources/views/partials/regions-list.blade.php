<table class="table table-hover align-middle mb-0">
    <thead class="table-light">
        <tr>
            <th>Kürzel</th>
            <th>Host-URL</th>
            <th>Projekte</th>
            <th class="text-end">Aktionen</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($regions as $region)
        <tr>
            <td><span class="fw-semibold">{{ $region->code }}</span></td>
            <td class="text-muted font-monospace small">{{ $region->host_url }}</td>
            <td><span class="badge text-bg-secondary rounded-pill">{{ $region->projects_count }}</span></td>
            <td class="text-end">
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="modal" data-bs-target="#editRegionModal"
                            onclick="prepareEditRegionModal({{ $region->id }}, '{{ addslashes($region->code) }}', '{{ addslashes($region->host_url) }}')"
                            title="Region bearbeiten">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger"
                            data-bs-toggle="modal" data-bs-target="#deleteRegionModal"
                            onclick="prepareDeleteRegionModal({{ $region->id }}, '{{ addslashes($region->code) }}')"
                            title="Region löschen">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="text-center text-muted small py-4">Keine Regionen vorhanden.</td>
        </tr>
        @endforelse
    </tbody>
</table>
