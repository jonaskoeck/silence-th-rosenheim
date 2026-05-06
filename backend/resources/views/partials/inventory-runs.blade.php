<table class="table table-hover align-middle mb-0">
    <thead class="table-light">
        <tr>
            <th>ID</th>
            <th>Startzeit</th>
            <th>Endzeit</th>
            <th>Auslöser</th>
            <th>Fehler</th>
            <th>Neue Server</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($runs as $run)
        <tr>
            <td class="text-muted small font-monospace">{{ $run->id }}</td>
            <td class="small">{{ $run->start_time?->format('d.m.Y H:i') ?? '—' }}</td>
            <td class="small">{{ $run->end_time?->format('d.m.Y H:i') ?? '—' }}</td>
            <td>
                @if ($run->triggered_automatically)
                    <span class="badge text-bg-secondary rounded-pill">Automatisch</span>
                @else
                    <span class="badge text-bg-primary rounded-pill">Manuell</span>
                @endif
            </td>
            <td>
                @if ($run->had_errors)
                    <span class="badge text-bg-danger rounded-pill">Ja</span>
                @else
                    <span class="badge text-bg-success rounded-pill">Nein</span>
                @endif
            </td>
            <td>
                @if ($run->found_new_servers && $run->discoveredServers->isNotEmpty())
                    <div class="d-flex flex-wrap gap-1">
                        @foreach ($run->discoveredServers as $server)
                            <span class="badge text-bg-success rounded-pill">{{ $server->name }}</span>
                        @endforeach
                    </div>
                @else
                    <span class="text-muted small">—</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center text-muted small py-4">
                Keine Inventarisierungen vorhanden.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
