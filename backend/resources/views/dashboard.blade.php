@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center page-header">
        <div>
            <h1 class="h4 fw-bold mb-0">Dashboard</h1>
        </div>
        <span class="text-muted small font-monospace">
            <i class="bi bi-arrow-clockwise me-1"></i>
            <span>Letzte Inventarisierung: {{ $lastInventory ? $lastInventory->start_time->format('d.m.Y H:i') . ' Uhr' : '—' }}</span>
        </span>
    </div>

    <div id="dashboard-content">
        @include('partials.dashboard-content', [
            'projects' => $projects,
            'schedules' => $schedules,
            'total' => $total,
            'running' => $running,
            'stopped' => $stopped,
        ])
    </div>

</div>
@endsection
