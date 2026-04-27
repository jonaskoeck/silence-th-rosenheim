<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'silence!') | TH Rosenheim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('assets/css/custom.css') }}" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm" style="z-index:1040; border-bottom:3px solid #F29400">
    <div class="container-fluid px-3">
        <a class="navbar-brand d-flex align-items-center gap-2 text-dark" href="{{ route('dashboard') }}">
            <img src="{{ asset('assets/logo.png') }}" alt="TH Rosenheim" style="height:38px; width:auto">
        </a>

        <button class="navbar-toggler border-0 ms-auto me-2" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="d-none d-lg-flex align-items-center gap-3">
            <span class="text-muted small font-monospace">
                {{ str_replace(' (RZ)', '', session('user_displayname', 'Mitarbeiter')) }}
            </span>
            <a href="{{ route('logout') }}" class="btn btn-sm" style="border-color:#F29400; color:#F29400">
                <i class="bi bi-box-arrow-right me-1"></i>Abmelden
            </a>
        </div>
    </div>
</nav>

<div class="offcanvas offcanvas-start bg-dark text-white" id="mobileSidebar" style="width:240px">
    <div class="offcanvas-header border-bottom border-secondary">
        <h6 class="offcanvas-title text-white mb-0 d-flex align-items-center gap-2">
            <img src="{{ asset('assets/logo.png') }}" alt="TH Rosenheim" style="height:28px; width:auto">
            silence!
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-2">
        @include('includes.sidebar')
        <hr class="border-secondary">
        <div class="px-2">
            <p class="text-muted small mb-1">
                <i class="bi bi-person-circle me-1"></i>
                {{ session('user_displayname', 'RZ Mitarbeiter') }}
            </p>
            <a href="{{ route('logout') }}" class="btn btn-outline-light btn-sm w-100">
                <i class="bi bi-box-arrow-right me-1"></i>Abmelden
            </a>
        </div>
    </div>
</div>

<div class="d-flex" style="margin-top:56px; min-height:calc(100vh - 56px)">

    <nav class="d-none d-lg-flex flex-column bg-white flex-shrink-0 align-items-center border-end"
         style="width:80px; position:sticky; top:56px; height:calc(100vh - 56px); overflow-y:auto">
        <div class="py-3 w-100">
            @include('includes.sidebar')
        </div>
    </nav>

    <main class="flex-grow-1 p-4" style="min-width:0">
        @yield('content')
    </main>
</div>

<footer class="bg-white border-top text-center text-muted py-2" style="font-size:0.8rem">
    silence! &copy; {{ date('Y') }} | Technische Hochschule Rosenheim, Rechenzentrum
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });
</script>
@stack('scripts')
</body>
</html>
