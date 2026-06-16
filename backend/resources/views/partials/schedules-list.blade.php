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
    @php($isActive = $sch['active'] ?? true)
    <div class="card border-0 shadow-sm {{ $isActive ? '' : 'opacity-50' }}">
        <div class="card-header bg-white py-0 d-flex align-items-stretch justify-content-between">
            <div class="d-flex align-items-center gap-2 flex-grow-1 py-3" style="cursor:pointer"
                 data-bs-toggle="collapse" data-bs-target="#schedule-{{ $sch['id'] }}">
                <i class="bi bi-chevron-down text-muted" style="font-size:0.85rem"></i>
                <span class="fw-semibold">{{ $sch['server_name'] }}</span>
                <span class="text-muted small">— {{ $sch['name'] }}</span>
                @unless ($isActive)
                <span class="badge text-bg-secondary ms-1">Inaktiv</span>
                @endunless
            </div>
            <div class="d-flex gap-2 align-items-center py-3">
                <form class="m-0"
                      hx-post="{{ route('server-actions.toggle-for-server', $sch['id']) }}"
                      hx-target="#schedules-container"
                      hx-swap="innerHTML">
                    @csrf
                    <div class="form-check form-switch m-0" title="Zeitplan aktivieren/deaktivieren"
                         data-bs-toggle="tooltip">
                        <input class="form-check-input schedule-toggle" type="checkbox" role="switch"
                               id="toggle-{{ $sch['id'] }}"
                               {{ $isActive ? 'checked' : '' }}
                               onchange="this.form.requestSubmit()">
                    </div>
                </form>
                <button class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="modal" data-bs-target="#editScheduleModal"
                        data-schedule-id="{{ $sch['id'] }}"
                        data-schedule-name="{{ $sch['name'] }}"
                        data-schedule-server="{{ $sch['server_name'] }}"
                        data-server-label="{{ $sch['server_label'] ?? 'NONE' }}"
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
                                    <div class="rounded px-2 py-1 text-white {{ $ev['type'] === 'start' ? 'schedule-event-start' : 'schedule-event-stop' }}"
                                         style="font-size:0.72rem">
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
