@php
    /** @var int $serverId */
    /** @var string|null $rawStatus */
    /** @var string|null $expecting */
    /** @var bool $oob */
    $expecting ??= null;
    $oob ??= false;

    $state = match (true) {
        $rawStatus === null => 'unknown',
        $expecting === 'ACTIVE'  && $rawStatus !== 'ACTIVE'  => 'starting',
        $expecting === 'SHUTOFF' && $rawStatus !== 'SHUTOFF' => 'stopping',
        $rawStatus === 'ACTIVE'  => 'active',
        $rawStatus === 'SHUTOFF' => 'shutoff',
        in_array($rawStatus, ['SHUTDOWN', 'POWERING_OFF', 'STOPPED'], true) => 'stopping',
        default => 'starting',
    };
@endphp
@if ($state === 'active')
<button id="server-toggle-{{ $serverId }}"
        class="btn btn-sm btn-outline-danger"
        title="Stoppen"
        data-bs-toggle="tooltip"
        hx-post="{{ route('servers.stop', $serverId) }}"
        hx-target="#projects-container"
        hx-swap="innerHTML"
        hx-on::before-request="window._collapseRestoreAfterSwap=[...document.querySelectorAll('.collapse.show')].map(el=>el.id)"
        @if ($oob) hx-swap-oob="true" @endif>
    <i class="bi bi-stop-fill"></i>
</button>
@elseif ($state === 'shutoff')
<button id="server-toggle-{{ $serverId }}"
        class="btn btn-sm btn-outline-success"
        title="Starten"
        data-bs-toggle="tooltip"
        hx-post="{{ route('servers.start', $serverId) }}"
        hx-target="#projects-container"
        hx-swap="innerHTML"
        hx-on::before-request="window._collapseRestoreAfterSwap=[...document.querySelectorAll('.collapse.show')].map(el=>el.id)"
        @if ($oob) hx-swap-oob="true" @endif>
    <i class="bi bi-play-fill"></i>
</button>
@else
<button id="server-toggle-{{ $serverId }}"
        class="btn btn-sm btn-outline-secondary"
        disabled
        title="{{ $state === 'unknown' ? 'Status nicht verfügbar' : 'Aktion läuft…' }}"
        @if ($oob) hx-swap-oob="true" @endif>
    <span class="spinner-border spinner-border-sm" style="width:0.75em;height:0.75em"></span>
</button>
@endif
