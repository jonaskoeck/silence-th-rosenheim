@if(request()->header('HX-Request'))
@yield('content')
@stack('scripts')
@else
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'silence!') | TH Rosenheim</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('assets/css/custom.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="bg-light" data-bs-theme="light">

<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm" style="z-index:1040; border-bottom:3px solid #F29400">
    <div class="container-fluid px-3">
        <a class="navbar-brand d-flex align-items-center gap-2 text-dark" href="{{ route('dashboard') }}">
            <img src="{{ asset('assets/logo.png') }}" alt="TH Rosenheim" style="height:38px; width:auto">
        </a>

        <button class="navbar-toggler border-0 ms-auto me-2" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="d-none d-lg-flex align-items-center gap-3">
            <span class="text-muted small font-monospace">
                {{ str_replace(' (RZ)', '', session('user_displayname', 'Mitarbeiter')) }}
            </span>
            <button class="btn btn-sm" style="border-color:#F29400; color:#F29400" title="Einstellungen"
                    data-bs-toggle="offcanvas" data-bs-target="#settingsOffcanvas">
                <i class="bi bi-gear-fill"></i>
            </button>
            <a href="{{ route('logout') }}" class="btn btn-sm" style="border-color:#F29400; color:#F29400">
                <i class="bi bi-box-arrow-right me-1"></i>Abmelden
            </a>
        </div>
    </div>
</nav>

<div class="offcanvas offcanvas-start bg-white" id="mobileSidebar" style="width:240px">
    <div class="offcanvas-header border-bottom">
        <h6 class="offcanvas-title mb-0 d-flex align-items-center gap-2">
            <img src="{{ asset('assets/logo.png') }}" alt="TH Rosenheim" style="height:28px; width:auto">
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-2">
        @include('includes.sidebar')
        <hr>
        <div class="px-2">
            <p class="text-muted small mb-1">
                {{ str_replace(' (RZ)', '', session('user_displayname', 'Mitarbeiter')) }}
            </p>
            <a href="{{ route('logout') }}" class="btn btn-outline-secondary btn-sm w-100">
                <i class="bi bi-box-arrow-right me-1"></i>Abmelden
            </a>
        </div>
    </div>
</div>

<div class="d-flex" style="margin-top:56px; min-height:calc(100vh - 56px)">

    <nav class="d-none d-lg-flex flex-column bg-white flex-shrink-0 align-items-center border-end"
         style="width:80px; position:sticky; top:56px; height:calc(100vh - 56px); overflow-y:auto">
        <div class="py-3 w-100">
            @include('includes.sidebar')
        </div>
    </nav>

    <main id="main-content" class="flex-grow-1 p-4" style="min-width:0">
        @yield('content')
    </main>
</div>

<div class="offcanvas offcanvas-end" id="settingsOffcanvas" style="width:320px" tabindex="-1">
    <div class="offcanvas-header border-bottom">
        <h6 class="offcanvas-title fw-semibold d-flex align-items-center gap-2">
            <i class="bi bi-gear-fill" style="color:#F29400"></i> Einstellungen
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">

        <div class="px-4 py-3 border-bottom">
            <p class="text-muted small fw-semibold text-uppercase mb-3" style="font-size:0.7rem;letter-spacing:.05em">Darstellung</p>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="small fw-semibold">Dark Mode</div>
                    <div class="text-muted" style="font-size:0.75rem">Dunkles Farbschema aktivieren</div>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input settings-toggle" type="checkbox" id="darkModeToggle" role="switch" style="width:2.5em;height:1.3em;cursor:pointer">
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="small fw-semibold">Farbblindenmodus</div>
                    <div class="text-muted" style="font-size:0.75rem">Farben für Rot-Grün-Schwäche anpassen</div>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input settings-toggle" type="checkbox" id="colorBlindToggle" role="switch" style="width:2.5em;height:1.3em;cursor:pointer">
                </div>
            </div>
        </div>

        <div class="px-4 py-3 border-bottom">
            <p class="text-muted small fw-semibold text-uppercase mb-3" style="font-size:0.7rem;letter-spacing:.05em">System (Backend-Cron)</p>
            <div class="mb-3">
                <label class="form-label small fw-semibold" for="schedulePollIntervalSelect">Zeitplan-Auslösung</label>
                <select class="form-select form-select-sm mt-2" id="schedulePollIntervalSelect"
                        data-url="{{ route('settings.schedule-poll-interval') }}">
                    @foreach ($allowedSchedulePollIntervals as $minutes)
                        <option value="{{ $minutes }}" @selected($schedulePollIntervalMinutes === $minutes)>
                            Alle {{ $minutes }} Minute{{ $minutes === 1 ? '' : 'n' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-0">
                <label class="form-label small fw-semibold" for="inventoryIntervalSelect">Inventarisierung</label>
                <select class="form-select form-select-sm mt-2" id="inventoryIntervalSelect"
                        data-url="{{ route('settings.inventory-interval') }}">
                    @foreach ($allowedInventoryIntervals as $minutes)
                        <option value="{{ $minutes }}" @selected($inventoryIntervalMinutes === $minutes)>
                            Alle {{ $minutes }} Minuten
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

    </div>
</div>

<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1100"></div>

<footer class="bg-white border-top text-center text-muted py-2" style="font-size:0.8rem">
    Technische Hochschule Rosenheim
</footer>

@stack('scripts')
<script>
    // Dark Mode: gespeicherten Zustand aus localStorage wiederherstellen
    const toggle = document.getElementById('darkModeToggle');
    const saved = localStorage.getItem('theme') || 'light';
    document.body.setAttribute('data-bs-theme', saved);
    if (saved === 'dark') {
        document.body.classList.replace('bg-light', 'bg-dark');
        toggle.checked = true;
    }
    toggle.addEventListener('change', () => {
        const next = toggle.checked ? 'dark' : 'light';
        document.body.setAttribute('data-bs-theme', next);
        document.body.classList.replace(
            next === 'dark' ? 'bg-light' : 'bg-dark',
            next === 'dark' ? 'bg-dark' : 'bg-light'
        );
        localStorage.setItem('theme', next);
    });

    // Farbblindenmodus: setzt ein data-Attribut auf dem body, das per CSS
    // die schwer unterscheidbaren Rot/Grün-Töne durch Blau/Orange ersetzt.
    // Wird ebenfalls in localStorage gespeichert und bleibt so erhalten.
    const cbToggle = document.getElementById('colorBlindToggle');
    if (localStorage.getItem('colorblind') === 'true') {
        document.body.setAttribute('data-colorblind', 'true');
        cbToggle.checked = true;
    }
    cbToggle.addEventListener('change', () => {
        if (cbToggle.checked) {
            document.body.setAttribute('data-colorblind', 'true');
        } else {
            document.body.removeAttribute('data-colorblind');
        }
        localStorage.setItem('colorblind', cbToggle.checked);
    });

    // Settings-Dropdowns: schicken den gewählten Wert per PUT an die jeweilige Route.
    // payloadKey bestimmt den Body-Key (seconds oder minutes) und onSuccess kann den
    // globalen Meta-Tag aktualisieren bzw. ein Event feuern.
    async function persistSetting(select, payloadKey, errorLabel, onSuccess) {
        const value = parseInt(select.value, 10);
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        try {
            const response = await fetch(select.dataset.url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ [payloadKey]: value }),
            });
            if (!response.ok) throw new Error('Speichern fehlgeschlagen');
            if (typeof onSuccess === 'function') onSuccess(value);
        } catch (e) {
            if (typeof showToast === 'function') {
                showToast(errorLabel + ' konnte nicht gespeichert werden.', 'danger');
            }
        }
    }

    const scheduleSelect = document.getElementById('schedulePollIntervalSelect');
    if (scheduleSelect) {
        scheduleSelect.addEventListener('change', () => persistSetting(
            scheduleSelect, 'minutes', 'Zeitplan-Intervall',
        ));
    }

    const inventorySelect = document.getElementById('inventoryIntervalSelect');
    if (inventorySelect) {
        inventorySelect.addEventListener('change', () => persistSetting(
            inventorySelect, 'minutes', 'Inventory-Intervall',
        ));
    }
</script>
</body>
</html>
@endif
