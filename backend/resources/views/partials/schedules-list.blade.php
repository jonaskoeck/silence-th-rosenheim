@php
$days = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
@endphp

@if (empty($schedules))
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        Keine Zeitpläne vorhanden.
    </div>
</div>
@else
<div class="d-flex flex-column gap-3">
    @foreach ($schedules as $sch)
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-0 d-flex align-items-stretch justify-content-between">
            <div class="d-flex align-items-center gap-2 flex-grow-1 py-3" style="cursor:pointer"
                 data-bs-toggle="collapse" data-bs-target="#schedule-{{ $sch['id'] }}">
                <i class="bi bi-chevron-down text-muted" style="font-size:0.85rem"></i>
                <span class="fw-semibold">{{ $sch['server_name'] }}</span>
                <span class="text-muted small">— {{ $sch['name'] }}</span>
            </div>
            <div class="d-flex gap-2 align-items-center py-3">
                <button class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="modal" data-bs-target="#editScheduleModal"
                        data-schedule-id="{{ $sch['id'] }}"
                        data-schedule-name="{{ $sch['name'] }}"
                        data-schedule-server="{{ $sch['server_name'] }}"
                        data-schedule-events="{{ json_encode($sch['events'] ?? []) }}">
                    <i class="bi bi-pencil me-1"></i>Bearbeiten
                </button>
                <button class="btn btn-sm btn-outline-danger"
                        data-bs-toggle="modal" data-bs-target="#deleteScheduleModal"
                        data-schedule-id="{{ $sch['id'] }}"
                        data-schedule-name="{{ $sch['name'] }}">
                    <i class="bi bi-trash me-1"></i>Löschen
                </button>
            </div>
        </div>
        <div class="collapse" id="schedule-{{ $sch['id'] }}">
            <div class="card-body p-3">
                <div class="schedule-calendar">
                    <div class="row g-2">
                        @foreach ($days as $day)
                        <div class="col">
                            <div class="text-center fw-semibold small text-muted mb-2">{{ $day }}</div>
                            <div class="d-flex flex-column gap-1">
                                @foreach ($sch['events'] ?? [] as $ev)
                                    @if ($ev['day'] === $day)
                                    <div class="rounded px-2 py-1 text-white"
                                         style="font-size:0.72rem; background:{{ $ev['type'] === 'start' ? '#198754' : '#dc3545' }}">
                                        {{ $ev['time'] }} {{ $ev['type'] === 'start' ? 'Starten' : 'Stoppen' }}
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
