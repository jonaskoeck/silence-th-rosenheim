@extends('layouts.app')

@section('title', 'Zeitpläne')

@section('content')
@php
$days = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
@endphp

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center page-header">
        <div>
            <h1 class="h4 fw-bold mb-0">Zeitpläne</h1>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newScheduleModal">
            <i class="bi bi-plus-lg me-1"></i>Neuer Zeitplan
        </button>
    </div>

    {{-- Filter --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2 px-3">
            <form method="get" action="{{ route('schedules') }}" class="d-flex align-items-center gap-2 flex-wrap">
                <i class="bi bi-funnel text-muted"></i>
                <select name="server" class="form-select form-select-sm" style="max-width:220px"
                        onchange="this.form.submit()">
                    <option value="">Alle Server</option>
                    @foreach ($allServers as $srv)
                    <option value="{{ $srv['id'] }}" {{ $filterServer === $srv['id'] ? 'selected' : '' }}>
                        {{ $srv['name'] }}
                    </option>
                    @endforeach
                </select>
                @if ($filterServer)
                <a href="{{ route('schedules') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-lg me-1"></i>Zurücksetzen
                </a>
                @endif
            </form>
        </div>
    </div>

    {{-- Zeitplan-Liste --}}
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
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2" style="cursor:pointer"
                     data-bs-toggle="collapse" data-bs-target="#schedule-{{ $sch['id'] }}">
                    <i class="bi bi-chevron-down text-muted" style="font-size:0.85rem"></i>
                    <span class="fw-semibold">{{ $sch['server_name'] }}</span>
                    <span class="text-muted small">— {{ $sch['name'] }}</span>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil me-1"></i>Bearbeiten
                    </button>
                    <form method="POST"
                          action="{{ route('server-actions.destroy-for-server', ['server' => $sch['id']]) }}"
                          onsubmit="return confirm('Wirklich alle Einträge dieses Zeitplans löschen?');"
                          class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">
                            <i class="bi bi-trash me-1"></i>Löschen
                        </button>
                    </form>
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

</div>

{{-- Neuer Zeitplan Modal --}}
<div class="modal fade" id="newScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="newScheduleForm" method="POST" action="{{ route('server-actions.store') }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold">Neuer Zeitplan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="new-name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Server <span class="text-danger">*</span></label>
                            <select class="form-select" id="new-server" name="server_id" required>
                                <option value="">Server wählen</option>
                                @foreach ($allServers as $srv)
                                <option value="{{ $srv['id'] }}">{{ $srv['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Kalender --}}
                    <div class="border rounded-3 overflow-hidden">
                        <div class="d-flex" style="min-height:120px">
                            @foreach ($days as $day)
                            <div class="{{ !$loop->last ? 'border-end' : '' }}" style="width:calc(100%/7); min-width:0" data-day="{{ $day }}">
                                <div class="text-center fw-semibold small py-2 border-bottom bg-light text-muted">{{ $day }}</div>
                                <div class="p-1 d-flex flex-column gap-1" style="min-height:80px">
                                    <div class="events-container d-flex flex-column gap-1 w-100" id="events-{{ $day }}"></div>
                                    <button type="button" class="btn btn-sm btn-light text-muted mt-auto w-100"
                                            style="font-size:0.75rem; border:1px dashed #dee2e6"
                                            onclick="showAddEvent('{{ $day }}')">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                    <div class="add-event-form d-none" id="form-{{ $day }}">
                                        <select class="form-select form-select-sm mb-1" id="type-{{ $day }}">
                                            <option value="START">Starten</option>
                                            <option value="STOP">Stoppen</option>
                                        </select>
                                        <input type="time" class="form-control form-control-sm mb-1"
                                               id="time-{{ $day }}" value="08:00">
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-primary flex-grow-1"
                                                    onclick="addEvent('{{ $day }}')">OK</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    onclick="hideAddEvent('{{ $day }}')">×</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div id="actions-payload"></div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Zeitplan speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const scheduleEvents = {};

const dayLabelToWeekday = {
    'Mo': 'MONDAY',
    'Di': 'TUESDAY',
    'Mi': 'WEDNESDAY',
    'Do': 'THURSDAY',
    'Fr': 'FRIDAY',
    'Sa': 'SATURDAY',
    'So': 'SUNDAY',
};

function showAddEvent(day) {
    document.getElementById('form-' + day).classList.remove('d-none');
}

function hideAddEvent(day) {
    document.getElementById('form-' + day).classList.add('d-none');
}

function addEvent(day) {
    const type = document.getElementById('type-' + day).value;
    const time = document.getElementById('time-' + day).value;
    if (!time) return;

    if (!scheduleEvents[day]) scheduleEvents[day] = [];
    const index = scheduleEvents[day].length;
    scheduleEvents[day].push({ type, time });

    const container = document.getElementById('events-' + day);
    const bg = type === 'START' ? '#198754' : '#dc3545';
    const label = type === 'START' ? 'Starten' : 'Stoppen';

    const el = document.createElement('div');
    el.className = 'rounded px-2 py-1 text-white d-flex justify-content-between align-items-center w-100';
    el.style.cssText = `background:${bg}; font-size:0.72rem`;
    el.innerHTML = `<span>${time} ${label}</span><span style="cursor:pointer" onclick="removeEvent('${day}', ${index}, this)">×</span>`;
    container.appendChild(el);

    hideAddEvent(day);
}

function removeEvent(day, index, el) {
    scheduleEvents[day].splice(index, 1);
    el.closest('div').remove();
}

document.getElementById('newScheduleForm').addEventListener('submit', function (e) {
    const groups = {};
    for (const [dayLabel, events] of Object.entries(scheduleEvents)) {
        const weekdayName = dayLabelToWeekday[dayLabel];
        if (!weekdayName) continue;
        for (const ev of events) {
            const key = ev.type + '|' + ev.time;
            if (!groups[key]) groups[key] = { type: ev.type, time: ev.time, days: [] };
            groups[key].days.push(weekdayName);
        }
    }

    const payload = document.getElementById('actions-payload');
    payload.innerHTML = '';

    const groupList = Object.values(groups);
    if (groupList.length === 0) {
        e.preventDefault();
        alert('Bitte mindestens einen Eintrag hinzufügen.');
        return;
    }

    groupList.forEach((group, i) => {
        payload.insertAdjacentHTML('beforeend',
            `<input type="hidden" name="actions[${i}][type]" value="${group.type}">` +
            `<input type="hidden" name="actions[${i}][time]" value="${group.time}">` +
            group.days.map(d => `<input type="hidden" name="actions[${i}][days][]" value="${d}">`).join('')
        );
    });
});
</script>
@endpush
