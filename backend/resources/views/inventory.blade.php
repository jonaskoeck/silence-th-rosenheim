@extends('layouts.app')

@section('title', 'Inventarisierung')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center page-header">
        <div>
            <h1 class="h4 fw-bold mb-0">Inventarisierung</h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <select id="projectSelect" placeholder="Projekt wählen..." style="max-width:220px">
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-primary">
                <i class="bi bi-play-fill me-1"></i>Manuell inventarisieren
            </button>
            <button class="btn btn-sm btn-primary">
                <i class="bi bi-arrow-repeat me-1"></i>Alle Projekte inventarisieren
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-clock-history me-2 text-primary"></i>Inventarisierungen
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Startzeit</th>
                        <th>Endzeit</th>
                        <th>Automatisch</th>
                        <th>Fehler</th>
                        <th>Neue Server</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($runs as $run)
                    <tr>
                        <td class="text-muted small font-monospace">{{ $run->id }}</td>
                        <td class="small">{{ $run->start_time?->format('d.m.Y H:i') ?? '—' }}</td>
                        <td class="small">{{ $run->end_time?->format('d.m.Y H:i') ?? '—' }}</td>
                        <td>
                            @if ($run->triggered_automatically)
                                <span class="badge text-bg-secondary rounded-pill">Automatisch</span>
                            @else
                                <span class="badge text-bg-primary rounded-pill">Manuell</span>
                            @endif
                        </td>
                        <td>
                            @if ($run->had_errors)
                                <span class="badge text-bg-danger rounded-pill">Ja</span>
                            @else
                                <span class="badge text-bg-success rounded-pill">Nein</span>
                            @endif
                        </td>
                        <td>
                            @if ($run->found_new_servers)
                                <span class="badge text-bg-success rounded-pill">Ja</span>
                            @else
                                <span class="badge text-bg-secondary rounded-pill">Nein</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted small py-4">
                            Keine Inventarisierungen vorhanden.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    new TomSelect('#projectSelect', { maxOptions: 100 });
</script>
@endpush
