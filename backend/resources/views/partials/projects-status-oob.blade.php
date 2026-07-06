@foreach ($projects as $project)
@foreach ($project['servers'] as $srv)
<span id="srv-status-{{ $srv['id'] }}" hx-swap-oob="outerHTML">@include('partials.server-status-badge', ['serverId' => $srv['id'], 'rawStatus' => $srv['raw_status'], 'expecting' => $srv['expecting'] ?? null])</span>
@include('partials.server-toggle-button', ['serverId' => $srv['id'], 'rawStatus' => $srv['raw_status'], 'expecting' => $srv['expecting'] ?? null, 'oob' => true])
@endforeach
@endforeach
@if (! empty($nextStatusUrl))
{{-- Chain-load the next chunk of projects; replaces itself with the next trigger. --}}
<span hx-get="{{ $nextStatusUrl }}" hx-trigger="load" hx-swap="outerHTML"></span>
@endif
