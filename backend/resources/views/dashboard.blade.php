@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center page-header">
        <div>
            <h1 class="h4 fw-bold mb-0">Dashboard</h1>
        </div>
        <span class="text-muted small font-monospace">
            <i class="bi bi-arrow-clockwise me-1"></i>
            <span>Letzte Inventarisierung: {{ $lastInventory ? $lastInventory->start_time->format('d.m.Y H:i') . ' Uhr' : '—' }}</span>
        </span>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-12 col-md-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-2">Gesamt Server</p>
                    <h3 class="fw-bold mb-0">{{ $total }}</h3>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-2">Laufend</p>
                    <h3 class="fw-bold text-success mb-1">{{ $running }}</h3>
                    <div class="progress" style="height:4px">
                        <div class="progress-bar bg-success" style="width:{{ $total > 0 ? round($running/$total*100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-2">Gestoppt</p>
                    <h3 class="fw-bold text-secondary mb-1">{{ $stopped }}</h3>
                    <div class="progress" style="height:4px">
                        <div class="progress-bar bg-secondary" style="width:{{ $total > 0 ? round($stopped/$total*100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3">

        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-diagram-3 me-2 text-primary"></i>Server nach Projekt
                    </h6>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createProjectModal">
                        <i class="bi bi-plus-lg me-1"></i>Projekt
                    </button>
                </div>

                @forelse ($projects as $project)
                <div class="border-bottom">
                    <div class="px-3 py-2 bg-light d-flex flex-column">
                        <span class="fw-semibold small">{{ $project['name'] }}</span>
                        <span class="text-muted font-monospace" style="font-size:0.7rem">{{ $project['open_stack_project_id'] }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Typ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($project['servers'] as $srv)
                                @php
                                    [$sc, $sl] = match($srv['status']) {
                                        'running' => ['success', 'Laufend'],
                                        default   => ['secondary', 'Gestoppt'],
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold small">{{ $srv['name'] }}</div>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-{{ $sc }} rounded-pill">
                                            {{ $sl }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($srv['label'] === 'production')
                                        <span class="badge text-bg-danger rounded-pill" style="font-size:0.72rem">Produktiv</span>
                                        @else
                                        <span class="badge text-bg-warning rounded-pill" style="font-size:0.72rem">Test</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted small py-3">
                                        Keine Server in diesem Projekt.

                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-5">
                    Keine Projekte vorhanden.
                </div>
                @endforelse

                <div class="card-footer bg-white border-top text-center py-2">
                    <a href="{{ route('servers') }}" class="btn btn-sm btn-link text-decoration-none small">
                        Alle verwalten
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 d-flex flex-column gap-3">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-clock-history me-2 text-primary"></i>Aktive Zeitpläne
                    </h6>
                </div>
                <ul class="list-group list-group-flush">
                    @foreach ($schedules->where('active', true) as $sch)
                    <li class="list-group-item py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small fw-semibold">{{ $sch['server_name'] }}</div>
                                <div class="text-muted" style="font-size:0.73rem">
                                    <i class="bi bi-play-fill text-success"></i> {{ $sch['start']['time'] }} Uhr
                                    &nbsp;&middot;&nbsp;
                                    <i class="bi bi-stop-fill text-danger"></i> {{ $sch['stop']['time'] }} Uhr
                                </div>
                            </div>
                            <span class="badge text-bg-success rounded-pill" style="font-size:0.65rem">Aktiv</span>
                        </div>
                    </li>
                    @endforeach
                </ul>
                <div class="card-footer bg-white border-top text-center py-2">
                    <a href="{{ route('schedules') }}" class="btn btn-sm btn-link text-decoration-none small">
                        Alle Zeitpläne
                    </a>
                </div>
            </div>


        </div>
    </div>

</div>
@push('scripts')
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
                    @if ($errors->any())
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
@endpush

@endsection
