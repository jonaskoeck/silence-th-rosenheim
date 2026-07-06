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
        <span class="badge rounded-pill text-bg-secondary" style="font-size:0.65rem">{{ $event['day'] }}</span>
    </div>
</li>
@empty
<li class="list-group-item py-3 text-center text-muted small">Keine Zeitpläne vorhanden.</li>
@endforelse
