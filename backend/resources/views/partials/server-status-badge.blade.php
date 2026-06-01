@php
    /** @var int $serverId */
    /** @var string|null $rawStatus */
    /** @var string|null $expecting */
    /** @var int $attempt */
    $expecting ??= null;
    $attempt ??= 0;
    $maxAttempts = 45; // ~90s bei 2s Polling-Intervall — danach geben wir auf

    if ($attempt >= $maxAttempts) {
        $expecting = null;
    }

    $state = match (true) {
        $rawStatus === null => 'unknown',
        $expecting === 'ACTIVE'  && $rawStatus !== 'ACTIVE'  => 'starting',
        $expecting === 'SHUTOFF' && $rawStatus !== 'SHUTOFF' => 'stopping',
        $rawStatus === 'ACTIVE'  => 'active',
        $rawStatus === 'SHUTOFF' => 'shutoff',
        in_array($rawStatus, ['SHUTDOWN', 'POWERING_OFF', 'STOPPED'], true) => 'stopping',
        default => 'starting',
    };

    [$badgeClass, $badgeLabel] = match ($state) {
        'active'   => ['success',   'Laufend'],
        'shutoff'  => ['secondary', 'Gestoppt'],
        'starting' => ['info',      'Startet…'],
        'stopping' => ['warning',   'Stoppt…'],
        'unknown'  => ['danger',    'Unbekannt'],
    };

    $shouldPoll = in_array($state, ['starting', 'stopping'], true);

    if ($shouldPoll) {
        $query = ['attempt' => $attempt + 1];
        if ($expecting !== null) {
            $query['expecting'] = $expecting;
        }
        $pollUrl = route('servers.status', $serverId).'?'.http_build_query($query);
    }
@endphp
@if ($shouldPoll)
<span class="badge text-bg-{{ $badgeClass }} rounded-pill"
      title="{{ $rawStatus }}"
      hx-get="{{ $pollUrl }}"
      hx-trigger="every 2s"
      hx-swap="outerHTML">{{ $badgeLabel }}</span>
@else
<span class="badge text-bg-{{ $badgeClass }} rounded-pill">{{ $badgeLabel }}</span>
@endif
