@extends('layouts.app')

@section('title', 'Inventarisierung')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center page-header">
        <h1 class="h4 fw-bold mb-0">Inventarisierung</h1>
        <form method="POST" action="{{ route('inventory.run') }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-arrow-repeat me-1"></i>Jetzt inventarisieren
            </button>
        </form>
    </div>

    @if (session('success'))
    <div class="alert alert-success">Inventarisierung erfolgreich abgeschlossen.</div>
    @endif

    @if (session('error'))
    <div class="alert alert-danger">Inventarisierung fehlgeschlagen.</div>
    @endif

    @forelse ($projects as $project)
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="fw-semibold mb-0">{{ $project->name ?: $project->open_stack_project_id }}</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>OpenStack ID</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($project->servers as $server)
                    <tr>
                        <td>{{ $server->name }}</td>
                        <td class="text-muted font-monospace small">{{ $server->open_stack_server_id }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="text-center text-muted py-3">Keine Server — bitte inventarisieren.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @empty
    <div class="text-center text-muted py-5">Keine Projekte vorhanden.</div>
    @endforelse

</div>
@endsection
