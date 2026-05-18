@php
$navItems = [
    ['route' => 'dashboard',  'icon' => 'speedometer2',  'label' => 'Dashboard'],
    ['route' => 'servers',    'icon' => 'server',         'label' => 'Projekte & Server'],
    ['route' => 'inventory',  'icon' => 'arrow-repeat',   'label' => 'Inventarisierungs Läufe'],
    ['route' => 'schedules',  'icon' => 'clock-history',  'label' => 'Zeitpläne'],
];
@endphp
<ul class="nav flex-column gap-1 align-items-center w-100">
    @foreach ($navItems as $item)
    <li class="nav-item w-100">
        <a class="nav-link d-flex align-items-center justify-content-center sidebar-icon-link
            {{ request()->routeIs($item['route']) ? 'active' : '' }}"
           href="{{ route($item['route']) }}"
           title="{{ $item['label'] }}"
           data-bs-toggle="tooltip"
           data-bs-placement="right"
           hx-get="{{ route($item['route']) }}"
           hx-target="#main-content"
           hx-swap="innerHTML"
           hx-push-url="true">
            <i class="bi bi-{{ $item['icon'] }} fs-5"></i>
        </a>
    </li>
    @endforeach
</ul>
