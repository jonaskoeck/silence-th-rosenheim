@extends('layouts.app')

@section('title', 'Inventarisierungsläufe')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center page-header">
        <div>
            <h1 class="h4 fw-bold mb-0">Inventarisierungsläufe</h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="POST" action="{{ route('inventory.run') }}"
                  hx-post="{{ route('inventory.run') }}"
                  hx-target="#inventory-runs"
                  hx-swap="innerHTML">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-arrow-repeat me-1"></i>Alle Projekte inventarisieren
                </button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-clock-history me-2 text-primary"></i>Inventarisierungen
            </h6>
        </div>
        <div class="table-responsive" id="inventory-runs">
            @include('partials.inventory-runs', ['runs' => $runs])
        </div>
    </div>

</div>
@endsection
