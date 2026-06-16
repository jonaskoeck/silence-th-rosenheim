@extends('layouts.app')

@section('title', 'Zeitpläne')

@section('content')
@php
$days = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
@endphp

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center page-header">
        <div>
            <h1 class="h4 fw-bold mb-0 page-title">Zeitpläne</h1>
        </div>
        <button id="new-schedule-btn" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newScheduleModal">
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
                <input type="text" id="scheduleSearch" name="search"
                       class="form-control border-start-0 ps-0"
                       placeholder="Zeitplan suchen..." list="schedule-suggestions"
                       hx-get="{{ route('schedules') }}"
                       hx-trigger="input changed delay:300ms"
                       hx-target="#schedules-container"
                       hx-include="[name='search']">
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
    <div id="schedules-container">
        @include('partials.schedules-list', ['schedules' => $schedules])
    </div>

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
                        <input type="text" class="form-control" id="edit-schedule-name" name="name" maxlength="120">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Server</label>
                        <p class="form-control-plaintext fw-semibold" id="edit-schedule-server"></p>
                    </div>
                </div>
                <div class="border rounded-3 overflow-hidden">
                    <div class="d-flex" style="min-height:120px">
                        @foreach ($days as $day)
                        <div class="{{ !$loop->last ? 'border-end' : '' }}" style="width:calc(100%/7); min-width:0" data-edit-day="{{ $day }}">
                            <div class="text-center fw-semibold small py-2 border-bottom bg-light text-muted">{{ $day }}</div>
                            <div class="p-1 d-flex flex-column gap-1" style="min-height:80px">
                                <div class="events-container d-flex flex-column gap-1 w-100" id="edit-events-{{ $day }}"></div>
                                <button type="button" class="btn btn-sm btn-light text-muted mt-auto w-100"
                                        style="font-size:0.75rem; border:1px dashed #dee2e6"
                                        onclick="showEditAddEvent('{{ $day }}')"
                                        title="Ereignis hinzufügen"
                                        data-bs-toggle="tooltip">
                                    <i class="bi bi-plus"></i>
                                </button>
                                <div class="add-event-form d-none" id="edit-form-{{ $day }}">
                                    <select class="form-select form-select-sm mb-1" id="edit-type-{{ $day }}">
                                        <option value="START">Starten</option>
                                        <option value="STOP">Stoppen</option>
                                    </select>
                                    <input type="time" class="form-control form-control-sm mb-1"
                                           id="edit-time-{{ $day }}" value="08:00" step="300" onchange="snapTime(this)">
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-primary flex-grow-1"
                                                onclick="addEditEvent('{{ $day }}')">OK</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                onclick="hideEditAddEvent('{{ $day }}')"
                                                title="Abbrechen"
                                                data-bs-toggle="tooltip">×</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" id="edit-schedule-submit">
                    <i class="bi bi-check-lg me-1"></i>Speichern
                </button>
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
                <button type="button" class="btn btn-danger" id="deleteScheduleBtn"
                        hx-target="#schedules-container"
                        hx-swap="innerHTML"
                        hx-on::after-request="bootstrap.Modal.getInstance(document.getElementById('deleteScheduleModal'))?.hide()">Löschen</button>
            </div>
        </div>
    </div>
</div>

{{-- Neuer Zeitplan Modal --}}
<div class="modal fade" id="newScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="newScheduleForm" method="POST" action="{{ route('server-actions.store') }}" novalidate>
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold">Neuer Zeitplan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Name</label>
                        <input type="text" class="form-control" id="new-name" name="name" maxlength="120">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Server <span class="text-danger">*</span></label>
                        <select class="form-select" id="new-server" name="server_id" required>
                            <option value="">Server wählen</option>
                            @foreach ($allServers as $srv)
                            <option value="{{ $srv->id }}" data-label="{{ $srv->label->value }}"
                                    @selected(($preselectServerId ?? null) === $srv->id)>{{ $srv->name }}</option>
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
                                            onclick="showAddEvent('{{ $day }}')"
                                            title="Ereignis hinzufügen"
                                            data-bs-toggle="tooltip">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                    <div class="add-event-form d-none" id="form-{{ $day }}">
                                        <select class="form-select form-select-sm mb-1" id="type-{{ $day }}">
                                            <option value="START">Starten</option>
                                            <option value="STOP">Stoppen</option>
                                        </select>
                                        <input type="time" class="form-control form-control-sm mb-1"
                                               id="time-{{ $day }}" value="08:00" step="300" onchange="snapTime(this)">
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-primary flex-grow-1"
                                                    onclick="addEvent('{{ $day }}')">OK</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    onclick="hideAddEvent('{{ $day }}')"
                                                    title="Abbrechen"
                                                    data-bs-toggle="tooltip">×</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div id="actions-payload"></div>
                    <input type="hidden" name="confirmed_production" id="confirmed-production" value="0">

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

{{-- Sicherheitsabfrage für Produktivserver --}}
<div class="modal fade" id="confirmProductionScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>Sicherheitsabfrage
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-0">
                    Der Server <strong id="confirm-production-server-name"></strong> ist als
                    <span class="badge text-bg-danger rounded-pill">Produktiv</span> markiert.
                    Möchtest du den Zeitplan wirklich
                    <strong id="confirm-production-action-label">speichern</strong>?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-warning" id="confirm-production-submit">
                    <i class="bi bi-check-lg me-1"></i>Bestätigen
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('scheduleSearch').addEventListener('input', function () {
    const norm = str => str.toLowerCase().replace(/[^a-z0-9]/g, '');
    const q = norm(this.value);
    document.querySelectorAll('#schedules-container .card').forEach(card => {
        const el = card.querySelector('[data-server-name]');
        if (!el) return;
        const matches = !q
            || norm(el.dataset.serverName).includes(q)
            || norm(el.dataset.scheduleName).includes(q);
        card.style.display = matches ? '' : 'none';
    });
});

var scheduleEvents = {};

var dayLabelToWeekday = {
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

// Start the "Neuer Zeitplan" modal from a clean slate so it never shows leftover
// input from a previous (unsaved) attempt. The preselect auto-open opens the modal
// directly without this button, so its preselected server is preserved.
function resetNewScheduleModal() {
    document.getElementById('new-name').value = '';
    document.getElementById('new-server').selectedIndex = 0;
    document.getElementById('confirmed-production').value = '0';
    scheduleEvents = {};
    ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'].forEach(d => {
        const container = document.getElementById('events-' + d);
        if (container) container.innerHTML = '';
        hideAddEvent(d);
    });
}

document.getElementById('new-schedule-btn').addEventListener('click', resetNewScheduleModal);

// Schedule times live on a 5-minute grid. The native time picker allows any
// minute, so snap the chosen value to the nearest 5 minutes (on change and again
// before reading it). The server enforces the same rule as a backstop.
function snapTime(input) {
    if (!input || !input.value) return;
    const [h, m] = input.value.split(':').map(Number);
    if (!Number.isInteger(h) || !Number.isInteger(m)) return;
    let minutes = Math.round(m / 5) * 5;
    let hours = h;
    if (minutes === 60) {
        minutes = 0;
        hours = (h + 1) % 24;
    }
    input.value = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
}

// Returns 'duplicate' (same type+time), 'conflict' (different type, same time) or null.
function eventTimeConflict(events, type, time) {
    const match = (events || []).find(ev => ev.time === time);
    if (!match) return null;
    return match.type === type ? 'duplicate' : 'conflict';
}

function rejectIfConflicting(events, type, time) {
    const conflict = eventTimeConflict(events, type, time);
    if (conflict === 'conflict') {
        showToast(`Um ${time} Uhr ist an diesem Tag bereits ein gegenteiliges Ereignis eingetragen.`, 'danger');
        return true;
    }
    // Same type + time is just a duplicate; it would be grouped together anyway,
    // so silently ignore it instead of nagging the user.
    return conflict === 'duplicate';
}

function addEvent(day) {
    const type = document.getElementById('type-' + day).value;
    const input = document.getElementById('time-' + day);
    snapTime(input);
    const time = input.value;
    if (!time) return;

    if (rejectIfConflicting(scheduleEvents[day], type, time)) return;

    if (!scheduleEvents[day]) scheduleEvents[day] = [];
    const index = scheduleEvents[day].length;
    scheduleEvents[day].push({ type, time });

    const container = document.getElementById('events-' + day);
    const eventClass = type === 'START' ? 'schedule-event-start' : 'schedule-event-stop';
    const label = type === 'START' ? 'Starten' : 'Stoppen';

    const el = document.createElement('div');
    el.className = `rounded px-2 py-1 text-white d-flex justify-content-between align-items-center w-100 ${eventClass}`;
    el.style.cssText = 'font-size:0.72rem';
    el.innerHTML = `<span>${time} ${label}</span><span style="cursor:pointer" onclick="removeEvent('${day}', ${index}, this)">×</span>`;
    container.appendChild(el);

    hideAddEvent(day);
}

function removeEvent(day, index, el) {
    scheduleEvents[day].splice(index, 1);
    el.closest('div').remove();
}

document.getElementById('newScheduleForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const serverId = document.getElementById('new-server').value;
    if (!serverId) {
        showToast('Bitte einen Server auswählen.', 'danger');
        return;
    }

    const existingServerIds = @json(array_column($schedules, 'id'));
    if (existingServerIds.includes(parseInt(serverId))) {
        showToast('Für diesen Server existiert bereits ein Zeitplan.', 'danger');
        return;
    }

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

    const groupList = Object.values(groups);
    if (groupList.length === 0) {
        showToast('Bitte mindestens einen Eintrag hinzufügen.', 'danger');
        return;
    }

    const payload = document.getElementById('actions-payload');
    payload.innerHTML = '';
    groupList.forEach((group, i) => {
        payload.insertAdjacentHTML('beforeend',
            `<input type="hidden" name="actions[${i}][type]" value="${group.type}">` +
            `<input type="hidden" name="actions[${i}][time]" value="${group.time}">` +
            group.days.map(d => `<input type="hidden" name="actions[${i}][days][]" value="${d}">`).join('')
        );
    });

    const selectedOption = document.getElementById('new-server').querySelector(`option[value="${serverId}"]`);
    const confirmedFlag = document.getElementById('confirmed-production');
    if (selectedOption?.dataset.label === 'PRODUCTION' && confirmedFlag.value !== '1') {
        document.getElementById('confirm-production-server-name').textContent = selectedOption.textContent.trim();
        document.getElementById('confirm-production-action-label').textContent = 'speichern';
        pendingProductionSubmit = () => {
            confirmedFlag.value = '1';
            document.getElementById('newScheduleForm').requestSubmit();
        };
        new bootstrap.Modal(document.getElementById('confirmProductionScheduleModal')).show();
        return;
    }

    const storeUrl = this.action;
    function closeOnSuccess(e) {
        if (e.detail.successful) {
            bootstrap.Modal.getInstance(document.getElementById('newScheduleModal'))?.hide();
        }
        document.removeEventListener('htmx:afterRequest', closeOnSuccess);
    }
    document.addEventListener('htmx:afterRequest', closeOnSuccess);

    htmx.ajax('POST', storeUrl, {
        source: this,
        target: '#schedules-container',
        swap: 'innerHTML',
    });
});

var pendingProductionSubmit = null;

document.getElementById('confirm-production-submit').addEventListener('click', () => {
    bootstrap.Modal.getInstance(document.getElementById('confirmProductionScheduleModal'))?.hide();
    if (typeof pendingProductionSubmit === 'function') {
        const submit = pendingProductionSubmit;
        pendingProductionSubmit = null;
        submit();
    }
});

// Focus the confirm button when the Sicherheitsabfrage opens so Enter confirms it.
// (Tabbing to "Abbrechen" and pressing Enter still cancels.)
document.getElementById('confirmProductionScheduleModal').addEventListener('shown.bs.modal', () => {
    document.getElementById('confirm-production-submit').focus();
});

document.getElementById('newScheduleModal').addEventListener('hidden.bs.modal', () => {
    document.getElementById('confirmed-production').value = '0';
    const url = new URL(window.location.href);
    if (url.searchParams.has('edit') || url.searchParams.has('server')) {
        url.searchParams.delete('edit');
        url.searchParams.delete('server');
        history.replaceState({}, '', url.toString());
    }
});

@if ($preselectServerId)
(function() {
    function openCreateModal() {
        const select = document.getElementById('new-server');
        if (select) {
            select.value = '{{ $preselectServerId }}';
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('newScheduleModal')).show();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', openCreateModal);
    } else {
        openCreateModal();
    }
})();
@endif

var editScheduleEvents = {};
var currentEditScheduleId = null;
var currentEditServerLabel = null;
var currentEditServerName = null;
var editConfirmedProduction = '0';

@if ($editSchedule)
(function() {
    const sch = @json($editSchedule);
    function openEditModal() {
        document.getElementById('edit-schedule-name').value         = sch.name;
        document.getElementById('edit-schedule-server').textContent = sch.server_name;
        currentEditScheduleId   = sch.id;
        currentEditServerLabel  = sch.server_label ?? 'NONE';
        currentEditServerName   = sch.server_name;
        editConfirmedProduction = '0';
        ['Mo','Di','Mi','Do','Fr','Sa','So'].forEach(d => {
            document.getElementById('edit-events-' + d).innerHTML = '';
        });
        editScheduleEvents = {};
        ['Mo','Di','Mi','Do','Fr','Sa','So'].forEach(d => {
            document.getElementById('edit-events-' + d).innerHTML = '';
        });
        (sch.events || []).forEach(ev => {
            const type = String(ev.type ?? '').toUpperCase();
            if (!editScheduleEvents[ev.day]) editScheduleEvents[ev.day] = [];
            const index = editScheduleEvents[ev.day].length;
            editScheduleEvents[ev.day].push({ type, time: ev.time });
            renderEditEvent(ev.day, index, type, ev.time);
        });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editScheduleModal')).show();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', openEditModal);
    } else {
        openEditModal();
    }
})();
@endif

document.getElementById('editScheduleModal').addEventListener('hidden.bs.modal', () => {
    editConfirmedProduction = '0';
    const url = new URL(window.location.href);
    if (url.searchParams.has('edit') || url.searchParams.has('server')) {
        url.searchParams.delete('edit');
        url.searchParams.delete('server');
        history.replaceState({}, '', url.toString());
        htmx.ajax('GET', url.toString(), { target: '#schedules-container', swap: 'innerHTML', headers: { 'HX-Target': 'schedules-container' } });
    }
});

document.getElementById('editScheduleModal').addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    if (e.target.tagName === 'BUTTON') return; // let a focused button handle its own Enter

    const addForm = e.target.closest('.add-event-form');
    if (addForm) {
        // Enter inside a day's "add event" form confirms that event instead of saving.
        const day = addForm.closest('[data-edit-day]')?.dataset.editDay;
        if (day) {
            e.preventDefault();
            addEditEvent(day);
            // The sub-form (and its focused input) is now hidden, which would drop
            // focus to <body>; move it to Save so the next Enter saves directly.
            document.getElementById('edit-schedule-submit').focus();
        }
        return;
    }

    e.preventDefault();
    document.getElementById('edit-schedule-submit').click();
});

function showEditAddEvent(day) {
    document.getElementById('edit-form-' + day).classList.remove('d-none');
}
function hideEditAddEvent(day) {
    document.getElementById('edit-form-' + day).classList.add('d-none');
}
function addEditEvent(day) {
    const type = document.getElementById('edit-type-' + day).value;
    const input = document.getElementById('edit-time-' + day);
    snapTime(input);
    const time = input.value;
    if (!time) return;
    if (rejectIfConflicting(editScheduleEvents[day], type, time)) return;
    if (!editScheduleEvents[day]) editScheduleEvents[day] = [];
    const index = editScheduleEvents[day].length;
    editScheduleEvents[day].push({ type, time });
    renderEditEvent(day, index, type, time);
    hideEditAddEvent(day);
}
function removeEditEvent(day, index, el) {
    editScheduleEvents[day].splice(index, 1);
    el.closest('div').remove();
}
function renderEditEvent(day, index, type, time) {
    const container = document.getElementById('edit-events-' + day);
    if (!container) return;
    const isStart = type === 'START' || type === 'start';
    const eventClass = isStart ? 'schedule-event-start' : 'schedule-event-stop';
    const label = isStart ? 'Starten' : 'Stoppen';
    const el = document.createElement('div');
    el.className = `rounded px-2 py-1 text-white d-flex justify-content-between align-items-center w-100 ${eventClass}`;
    el.style.cssText = 'font-size:0.72rem';
    el.innerHTML = `<span>${time} ${label}</span><span style="cursor:pointer" onclick="removeEditEvent('${day}', ${index}, this)">×</span>`;
    container.appendChild(el);
}

document.getElementById('editScheduleModal').addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    if (!btn) return;
    document.getElementById('edit-schedule-name').value         = btn.dataset.scheduleName;
    document.getElementById('edit-schedule-server').textContent = btn.dataset.scheduleServer;

    currentEditScheduleId   = btn.dataset.scheduleId;
    currentEditServerLabel  = btn.dataset.serverLabel ?? 'NONE';
    currentEditServerName   = btn.dataset.scheduleServer;
    editConfirmedProduction = '0';

    editScheduleEvents = {};
    ['Mo','Di','Mi','Do','Fr','Sa','So'].forEach(d => {
        document.getElementById('edit-events-' + d).innerHTML = '';
        hideEditAddEvent(d);
    });

    JSON.parse(btn.dataset.scheduleEvents || '[]').forEach(ev => {
        const type = String(ev.type ?? '').toUpperCase();
        if (!editScheduleEvents[ev.day]) editScheduleEvents[ev.day] = [];
        const index = editScheduleEvents[ev.day].length;
        editScheduleEvents[ev.day].push({ type, time: ev.time });
        renderEditEvent(ev.day, index, type, ev.time);
    });
});

document.getElementById('edit-schedule-submit').addEventListener('click', function () {
    if (!currentEditScheduleId) {
        showToast('Kein Zeitplan ausgewählt.', 'danger');
        return;
    }

    const groups = {};
    for (const [dayLabel, events] of Object.entries(editScheduleEvents)) {
        const weekdayName = dayLabelToWeekday[dayLabel];
        if (!weekdayName) continue;
        for (const ev of events) {
            const key = ev.type + '|' + ev.time;
            if (!groups[key]) groups[key] = { type: ev.type, time: ev.time, days: [] };
            groups[key].days.push(weekdayName);
        }
    }

    const groupList = Object.values(groups);
    if (groupList.length === 0) {
        showToast('Bitte mindestens einen Eintrag hinzufügen.', 'danger');
        return;
    }

    if (currentEditServerLabel === 'PRODUCTION' && editConfirmedProduction !== '1') {
        document.getElementById('confirm-production-server-name').textContent = currentEditServerName ?? '';
        document.getElementById('confirm-production-action-label').textContent = 'aktualisieren';
        pendingProductionSubmit = () => {
            editConfirmedProduction = '1';
            document.getElementById('edit-schedule-submit').click();
        };
        new bootstrap.Modal(document.getElementById('confirmProductionScheduleModal')).show();
        return;
    }

    const values = {
        _token: document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        confirmed_production: editConfirmedProduction,
        name: document.getElementById('edit-schedule-name').value,
    };
    groupList.forEach((group, i) => {
        values[`actions[${i}][type]`] = group.type;
        values[`actions[${i}][time]`] = group.time;
        group.days.forEach((d, j) => {
            values[`actions[${i}][days][${j}]`] = d;
        });
    });

    function closeOnSuccess(e) {
        if (e.detail.successful) {
            bootstrap.Modal.getInstance(document.getElementById('editScheduleModal'))?.hide();
        }
        document.removeEventListener('htmx:afterRequest', closeOnSuccess);
    }
    document.addEventListener('htmx:afterRequest', closeOnSuccess);

    window._collapseRestoreAfterSwap = [...document.querySelectorAll('.collapse.show')].map(el => el.id);

    htmx.ajax('PUT', '/servers/' + currentEditScheduleId + '/server-actions', {
        source: this,
        values,
        target: '#schedules-container',
        swap: 'innerHTML',
    });
});

document.getElementById('deleteScheduleModal').addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    document.getElementById('delete-schedule-name').textContent = btn.dataset.scheduleName;
    const deleteBtn = document.getElementById('deleteScheduleBtn');
    deleteBtn.setAttribute('hx-delete', '/servers/' + btn.dataset.scheduleId + '/server-actions');
    htmx.process(deleteBtn);
});
</script>
@endpush
