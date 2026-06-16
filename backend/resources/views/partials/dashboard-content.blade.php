<div class="row g-3 mb-4">

    <div class="col-12 col-md-4">
        <a href="{{ route('servers') }}"
           hx-get="{{ route('servers') }}"
           hx-target="#main-content"
           hx-swap="innerHTML"
           hx-push-url="true"
           class="text-decoration-none">
            <div class="card stat-card shadow-sm h-100" style="cursor:pointer">
                <div class="card-body">
                    <p class="text-muted small mb-2">Gesamt Server</p>
                    <h3 class="fw-bold mb-0 dash-value" style="min-height:2.1rem">{{ $total }}</h3>
                </div>
            </div>
        </a>
    </div>

    <div class="col-12 col-md-4">
        <a href="{{ route('servers') }}?filter=running"
           hx-get="{{ route('servers') }}?filter=running"
           hx-target="#main-content" hx-swap="innerHTML" hx-push-url="true"
           class="text-decoration-none">
            <div class="card stat-card shadow-sm h-100" style="cursor:pointer">
                <div class="card-body">
                    <p class="text-muted small mb-2">Laufend</p>
                    <h3 class="fw-bold text-success mb-1 dash-value" style="min-height:2.1rem">{{ $running }}</h3>
                    <div class="progress" style="height:4px">
                        <div class="progress-bar bg-success dash-bar" style="--bar-width:{{ $total > 0 ? round($running/$total*100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-12 col-md-4">
        <a href="{{ route('servers') }}?filter=stopped"
           hx-get="{{ route('servers') }}?filter=stopped"
           hx-target="#main-content" hx-swap="innerHTML" hx-push-url="true"
           class="text-decoration-none">
            <div class="card stat-card shadow-sm h-100" style="cursor:pointer">
                <div class="card-body">
                    <p class="text-muted small mb-2">Gestoppt</p>
                    <h3 class="fw-bold text-secondary mb-1 dash-value" style="min-height:2.1rem">{{ $stopped }}</h3>
                    <div class="progress" style="height:4px">
                        <div class="progress-bar bg-secondary dash-bar" style="--bar-width:{{ $total > 0 ? round($stopped/$total*100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </a>
    </div>

</div>

<div class="row g-3">

    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-diagram-3 me-2 text-primary"></i>Server nach Projekt
                </h6>
            </div>

            @include('partials.dashboard-projects')

            <div class="card-footer bg-white border-top text-center py-2">
                <a href="{{ route('servers') }}" class="btn btn-sm btn-link text-decoration-none small fw-semibold"
                   style="color:#F29400"
                   hx-get="{{ route('servers') }}"
                   hx-target="#main-content"
                   hx-swap="innerHTML"
                   hx-push-url="true">
                    Alle verwalten
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-4 d-flex flex-column gap-3">

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-clock-history me-2 text-primary"></i>Nächste Events
                </h6>
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($nextEvents as $event)
                <li class="list-group-item py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small fw-semibold">{{ $event['server'] }}</div>
                            <div class="text-muted" style="font-size:0.73rem">
                                @if ($event['type']->value === 'START')
                                    <i class="bi bi-play-fill text-success"></i>
                                @else
                                    <i class="bi bi-stop-fill text-danger"></i>
                                @endif
                                {{ $event['type']->value === 'START' ? 'Starten' : 'Stoppen' }}
                                &nbsp;·&nbsp; {{ $event['time'] }} Uhr
                            </div>
                        </div>
                        <span class="badge rounded-pill text-bg-secondary" style="font-size:0.65rem">
                            {{ $event['day'] }}
                        </span>
                    </div>
                </li>
                @empty
                <li class="list-group-item py-3 text-center text-muted small">
                    Keine Zeitpläne vorhanden.
                </li>
                @endforelse
            </ul>
            <div class="card-footer bg-white border-top text-center py-2">
                <a href="{{ route('schedules') }}" class="btn btn-sm btn-link text-decoration-none small fw-semibold"
                   style="color:#F29400"
                   hx-get="{{ route('schedules') }}"
                   hx-target="#main-content"
                   hx-swap="innerHTML"
                   hx-push-url="true">
                    Alle Zeitpläne
                </a>
            </div>
        </div>


        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-piggy-bank me-2 text-primary"></i>Kostenersparnis
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-1">Durch Zeitpläne diesen Monat gespart (geschätzt)</p>
                <h3 class="fw-bold mb-0">€ {{ number_format($monthlySavings, 2, ',', '.') }}</h3>
                @if ($savingsHours > 0)
                <p class="text-muted mt-2 mb-0" style="font-size:0.75rem">
                    {{ number_format($savingsHours, 0, ',', '.') }} Std./Monat × Ø {{ number_format($savingsAvgRate, 4, ',', '.') }} €/Std.
                </p>
                @else
                <p class="text-muted mt-2 mb-0" style="font-size:0.75rem">
                    Keine aktiven Zeitpläne mit bekanntem Flavor
                </p>
                @endif
            </div>
        </div>

    </div>
</div>
