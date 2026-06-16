<span id="srv-status-{{ $serverId }}" hx-swap-oob="outerHTML">@include('partials.server-status-badge', ['serverId' => $serverId, 'rawStatus' => $rawStatus, 'expecting' => $expecting])</span>
@include('partials.server-toggle-button', ['serverId' => $serverId, 'rawStatus' => $rawStatus, 'expecting' => $expecting, 'oob' => true])
