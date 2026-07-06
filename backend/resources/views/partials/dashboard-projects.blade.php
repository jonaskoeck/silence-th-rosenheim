@forelse ($projects as $project)
<div class="border-bottom">
    <div class="px-3 py-2 bg-light d-flex flex-column">
        <span class="fw-semibold small">{{ $project['name'] }}</span>
        <span class="text-muted font-monospace" style="font-size:0.7rem">{{ $project['open_stack_project_id'] }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 dash-table" style="table-layout:fixed">
            <colgroup><col style="width:54%"><col style="width:23%"><col style="width:23%"></colgroup>
            <thead class="table-light">
                <tr><th>Name</th><th>Status</th><th>Typ</th></tr>
            </thead>
            <tbody>
                @forelse ($project['servers'] as $srv)
                @php($isLazy = ! array_key_exists('raw_status', $srv))
                <tr>
                    <td><div class="fw-semibold small">{{ $srv['name'] }}</div></td>
                    <td>
                        <span id="srv-status-{{ $srv['id'] }}" @if ($isLazy) class="placeholder-glow" @endif>
                            @if ($isLazy)
                            <span class="placeholder rounded-pill" style="width:4.5rem;height:1.275rem;display:inline-block"></span>
                            @else
                            @include('partials.server-status-badge', ['serverId' => $srv['id'], 'rawStatus' => $srv['raw_status'], 'expecting' => $srv['expecting'] ?? null])
                            @endif
                        </span>
                    </td>
                    <td>
                        @if ($srv['label'] === 'production')
                        <span class="badge text-bg-danger rounded-pill">Produktiv</span>
                        @elseif ($srv['label'] === 'test')
                        <span class="badge text-bg-warning rounded-pill">Test</span>
                        @elseif ($srv['label'] === 'development')
                        <span class="badge label-dev rounded-pill">Entwicklung</span>
                        @else
                        <span class="badge text-bg-secondary rounded-pill">Unkategorisiert</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center text-muted small py-3">Keine Server in diesem Projekt.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="text-center text-muted py-5">Keine Projekte vorhanden.</div>
@endforelse
