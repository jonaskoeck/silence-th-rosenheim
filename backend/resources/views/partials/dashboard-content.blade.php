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
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-diagram-3 me-2 text-primary"></i>Server nach Projekt
                </h6>
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
                                    @elseif ($srv['label'] === 'test')
                                    <span class="badge text-bg-info rounded-pill" style="font-size:0.72rem">Test</span>
                                    @elseif ($srv['label'] === 'development')
                                    <span class="badge text-bg-primary rounded-pill" style="font-size:0.72rem">Entwicklung</span>
                                    @else
                                    <span class="badge text-bg-secondary rounded-pill" style="font-size:0.72rem">Unkategorisiert</span>
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


        @php
            $savingsData = [18, 52, 34, 91, 63, 175];
            $savingsMonths = [];
            for ($i = 5; $i >= 0; $i--) {
                $savingsMonths[] = now()->subMonths($i)->locale('de')->isoFormat('MMM');
            }
            $current = end($savingsData);
            $maxVal = max($savingsData);
            $svgPoints = [];
            foreach ($savingsData as $i => $val) {
                $x = $i * 60;
                $y = round(65 - ($val / $maxVal) * 60 + 3);
                $svgPoints[] = ['x' => $x, 'y' => $y];
            }
            $polyline = implode(' ', array_map(fn($p) => "{$p['x']},{$p['y']}", $svgPoints));
            $polygon  = $polyline . ' 300,70 0,70';
        @endphp
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-semibold mb-0">
                    <i class="bi bi-piggy-bank me-2 text-primary"></i>Kostenersparnis
                </h6>
            </div>
            <div class="card-body pb-2">
                <div class="d-flex align-items-baseline gap-2 mb-1">
                    <h3 class="fw-bold mb-0" id="savings-main-value">€ {{ number_format($current, 2, ',', '.') }}</h3>
                    <span id="savings-comparison" class="text-success small fw-semibold">
                        <i class="bi bi-arrow-up-short"></i>Ø pro Monat
                    </span>
                </div>
                <p class="text-muted mb-2" style="font-size:0.75rem">Klick auf Monat zum Vergleichen</p>
                <div style="height:70px;position:relative">
                    <svg viewBox="0 0 300 70" preserveAspectRatio="none" style="width:100%;height:100%">
                        <defs>
                            <linearGradient id="savingsGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#F29400" stop-opacity="0.25"/>
                                <stop offset="100%" stop-color="#F29400" stop-opacity="0"/>
                            </linearGradient>
                        </defs>
                        <polygon points="{{ $polygon }}" fill="url(#savingsGrad)"/>
                        <polyline points="{{ $polyline }}"
                                  fill="none" stroke="#F29400" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
                    </svg>
                    @foreach($svgPoints as $i => $pt)
                    <div class="savings-point"
                         style="position:absolute;
                                left:calc({{ round($pt['x'] / 300 * 100, 2) }}% - 5px);
                                top:calc({{ round($pt['y'] / 70 * 100, 2) }}% - 5px);
                                width:10px;height:10px;border-radius:50%;
                                background:white;border:2.5px solid #F29400;
                                cursor:pointer;transition:transform 0.15s"
                         data-month="{{ $savingsMonths[$i] }}"
                         data-value="{{ $savingsData[$i] }}"
                         data-current="{{ $current }}">
                    </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-between mt-1" style="font-size:0.68rem">
                    @foreach($savingsMonths as $i => $month)
                    <span class="savings-month-label text-muted" style="cursor:pointer"
                          data-index="{{ $i }}">{{ $month }}</span>
                    @endforeach
                </div>
            </div>
        </div>
        <script>
        (function() {
            const points = document.querySelectorAll('.savings-point');
            const labels = document.querySelectorAll('.savings-month-label');
            const comparison = document.getElementById('savings-comparison');
            const mainValue = document.getElementById('savings-main-value');

            const lastIdx = points.length - 1;

            function selectPoint(idx) {
                const pt = points[idx];
                const val = parseFloat(pt.dataset.value);
                const current = parseFloat(pt.dataset.current);
                const month = pt.dataset.month;

                points.forEach(p => { p.style.background = 'white'; p.style.transform = 'scale(1)'; });
                labels.forEach(l => { l.style.fontWeight = 'normal'; l.style.color = ''; });
                pt.style.background = '#F29400';
                labels[idx].style.fontWeight = '700';
                labels[idx].style.color = '#F29400';

                if (idx === lastIdx) {
                    comparison.innerHTML = `<i class="bi bi-calendar-check me-1"></i>Aktueller Monat`;
                    comparison.className = 'text-muted small fw-semibold';
                } else {
                    const diff = (current - val).toFixed(2).replace('.', ',');
                    const pct = Math.round((current - val) / val * 100);
                    const valFmt = val.toFixed(2).replace('.', ',');
                    comparison.innerHTML = `<i class="bi bi-arrow-up-short"></i>${month}: €${valFmt} → +€${diff} (+${pct}%)`;
                    comparison.className = 'text-success small fw-semibold';
                }
            }

            let selected = -1;
            points.forEach((pt, i) => {
                pt.addEventListener('click', () => { selected = i; selectPoint(i); });
                pt.addEventListener('mouseenter', () => { if (selected !== i) pt.style.transform = 'scale(1.5)'; });
                pt.addEventListener('mouseleave', () => { if (selected !== i) pt.style.transform = 'scale(1)'; });
            });
            labels.forEach((lb, i) => {
                lb.addEventListener('click', () => { selected = i; selectPoint(i); });
                lb.addEventListener('mouseenter', () => { if (selected !== i) lb.style.color = '#F29400'; });
                lb.addEventListener('mouseleave', () => { if (selected !== i) lb.style.color = ''; });
            });
        })();
        </script>

    </div>
</div>
