@include('partials.server-status-badge', ['serverId' => $serverId, 'rawStatus' => $rawStatus, 'expecting' => $expecting, 'attempt' => $attempt])
@include('partials.server-toggle-button', ['serverId' => $serverId, 'rawStatus' => $rawStatus, 'expecting' => $expecting, 'oob' => true])
