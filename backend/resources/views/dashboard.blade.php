@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center page-header">
        <div>
            <h1 class="h4 fw-bold mb-0 page-title">Dashboard</h1>
        </div>
        <span class="text-muted small font-monospace">
            <i class="bi bi-arrow-clockwise me-1"></i>
            <span>Letzte Inventarisierung: {{ $lastInventory ? $lastInventory->start_time->format('d.m.Y H:i') . ' Uhr' : '—' }}</span>
        </span>
    </div>

    <div id="dashboard-content"
         hx-get="{{ route('dashboard.data') }}"
         hx-trigger="load"
         hx-swap="innerHTML">

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-2">Gesamt Server</p>
                        <h3 class="fw-bold mb-0 placeholder-glow" style="min-height:2.1rem">
                            <span class="placeholder rounded" style="width:2.5rem;height:1.75rem;display:block;margin-top:0.175rem"></span>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-2">Laufend</p>
                        <h3 class="fw-bold mb-1 placeholder-glow" style="min-height:2.1rem">
                            <span class="placeholder rounded" style="width:2.5rem;height:1.75rem;display:block;margin-top:0.175rem"></span>
                        </h3>
                        <div class="progress" style="height:4px"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-2">Gestoppt</p>
                        <h3 class="fw-bold mb-1 placeholder-glow" style="min-height:2.1rem">
                            <span class="placeholder rounded" style="width:2.5rem;height:1.75rem;display:block;margin-top:0.175rem"></span>
                        </h3>
                        <div class="progress" style="height:4px"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-semibold mb-0"><i class="bi bi-diagram-3 me-2 text-primary"></i>Server nach Projekt</h6>
                    </div>
                    @include('partials.dashboard-projects')
                    <div class="card-footer bg-white border-top text-center py-2">
                        <a href="{{ route('servers') }}" class="btn btn-sm btn-link text-decoration-none small fw-semibold" style="color:#F29400"
                           hx-get="{{ route('servers') }}" hx-target="#main-content" hx-swap="innerHTML" hx-push-url="true">
                            Alle verwalten
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 d-flex flex-column gap-3">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-semibold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Nächste Events</h6>
                    </div>
                    <ul class="list-group list-group-flush"
                        hx-get="{{ route('dashboard.next-events') }}" hx-trigger="every 60s" hx-target="this" hx-swap="innerHTML">
                        @include('partials.dashboard-next-events')
                    </ul>
                    <div class="card-footer bg-white border-top text-center py-2">
                        <a href="{{ route('schedules') }}" class="btn btn-sm btn-link text-decoration-none small fw-semibold" style="color:#F29400"
                           hx-get="{{ route('schedules') }}" hx-target="#main-content" hx-swap="innerHTML" hx-push-url="true">
                            Alle Zeitpläne
                        </a>
                    </div>
                </div>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="fw-semibold mb-0"><i class="bi bi-piggy-bank me-2 text-primary"></i>Kostenersparnis</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-1">Durch Zeitpläne diesen Monat gespart (geschätzt)</p>
                        <h3 class="fw-bold mb-0">€ {{ number_format($monthlySavings, 2, ',', '.') }}</h3>
                        @if ($savingsHours > 0)
                        <p class="text-muted mt-2 mb-0" style="font-size:0.75rem">
                            ≈ {{ number_format($savingsHours, 0, ',', '.') }} Std./Monat × Ø {{ number_format($savingsAvgRate, 4, ',', '.') }} €/Std.
                        </p>
                        @else
                        <p class="text-muted mt-2 mb-0" style="font-size:0.75rem">Keine aktiven Zeitpläne mit bekanntem Flavor</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
