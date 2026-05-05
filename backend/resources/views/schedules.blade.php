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
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" id="scheduleSearch" class="form-control border-start-0 ps-0"
                       placeholder="Zeitplan suchen..." list="schedule-suggestions" autocomplete="off">
            </div>
            <datalist id="schedule-suggestions">
                @foreach ($schedules as $sch)
                <option value="{{ $sch['server_name'] }}">
                <option value="{{ $sch['name'] }}">
                @endforeach
            </datalist>
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
            <div class="card-header bg-white py-0 d-flex align-items-stretch justify-content-between">
                <div class="d-flex align-items-center gap-2 flex-grow-1 py-3" style="cursor:pointer"
                     data-bs-toggle="collapse" data-bs-target="#schedule-{{ $sch['id'] }}"
                     data-schedule-search="{{ $sch['server_name'] }} {{ $sch['name'] }}">
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

</div>

{{-- Zeitplan bearbeiten Modal --}}
<div class="modal fade" id="editScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold">Zeitplan bearbeiten</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Name</label>
                        <input type="text" class="form-control" id="edit-schedule-name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Server</label>
                        <p class="form-control-plaintext fw-semibold" id="edit-schedule-server"></p>
                    </div>
                </div>
                <div class="border rounded-3 overflow-hidden">
                    <div class="d-flex" style="min-height:120px">
                        @foreach ($days as $day)
                        <div class="{{ !$loop->last ? 'border-end' : '' }}" style="width:calc(100%/7); min-width:0">
                            <div class="text-center fw-semibold small py-2 border-bottom bg-light text-muted">{{ $day }}</div>
                            <div class="p-1 d-flex flex-column gap-1" style="min-height:80px">
                                <div class="d-flex flex-column gap-1 w-100" id="edit-events-{{ $day }}"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Speichern</button>
            </div>
        </div>
    </div>
</div>

{{-- Zeitplan löschen Modal --}}
<div class="modal fade" id="deleteScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold">Zeitplan löschen</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-0">
                    Möchtest du den Zeitplan <strong id="delete-schedule-name"></strong> wirklich löschen?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <form method="POST" id="deleteScheduleForm"
                      data-action-template="{{ route('server-actions.destroy-for-server', ['server' => '__ID__']) }}"
                      class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Löschen</button>
                </form>
            </div>
        </div>
    </div>
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
                        <label class="form-label fw-semibold">Name</label>
                        <input type="text" class="form-control" id="new-name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Server <span class="text-danger">*</span></label>
                        <select class="form-select" id="new-server" name="server_id" required>
                            <option value="">Server wählen</option>
                            @foreach ($allServers as $srv)
                            <option value="{{ $srv->id }}">{{ $srv->name }}</option>
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

document.addEventListener('DOMContentLoaded', () => {
    new TomSelect('#new-server', { maxOptions: 10 });
});

document.getElementById('scheduleSearch').addEventListener('input', function () {
    const normalize = str => str.toLowerCase().replace(/[^a-z0-9]/g, '');
    const query = normalize(this.value);
    document.querySelectorAll('[data-schedule-search]').forEach(card => {
        const text = normalize(card.dataset.scheduleSearch);
        card.closest('.card').style.display = text.includes(query) ? '' : 'none';
    });
});

document.getElementById('editScheduleModal').addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    document.getElementById('edit-schedule-name').value         = btn.dataset.scheduleName;
    document.getElementById('edit-schedule-server').textContent = btn.dataset.scheduleServer;

    ['Mo','Di','Mi','Do','Fr','Sa','So'].forEach(d => {
        document.getElementById('edit-events-' + d).innerHTML = '';
    });

    JSON.parse(btn.dataset.scheduleEvents || '[]').forEach(ev => {
        const container = document.getElementById('edit-events-' + ev.day);
        if (!container) return;
        const bg = ev.type === 'start' ? '#198754' : '#dc3545';
        const el = document.createElement('div');
        el.className = 'rounded px-2 py-1 text-white w-100';
        el.style.cssText = `background:${bg}; font-size:0.72rem`;
        el.textContent = ev.time + ' ' + (ev.type === 'start' ? 'Starten' : 'Stoppen');
        container.appendChild(el);
    });
});

document.getElementById('deleteScheduleModal').addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    document.getElementById('delete-schedule-name').textContent = btn.dataset.scheduleName;
    const form = document.getElementById('deleteScheduleForm');
    form.action = form.dataset.actionTemplate.replace('__ID__', btn.dataset.scheduleId);
});
</script>
@endpush
