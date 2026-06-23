<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin - @yield('title', 'Dashboard') | BeritaKini</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        :root { --primary: #1a1a2e; --accent: #e94560; }
        .sidebar { min-height: 100vh; background: var(--primary); width: 240px; position: fixed; top: 0; left: 0; z-index: 100; padding-top: 60px; }
        .sidebar .brand { position: fixed; top: 0; left: 0; width: 240px; background: var(--primary); padding: 14px 20px; border-bottom: 1px solid #333; }
        .sidebar .nav-link { color: #aaa; padding: 10px 20px; border-radius: 8px; margin: 2px 8px; transition: all .2s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(233,69,96,.15); color: #fff; }
        .sidebar .nav-link i { width: 20px; margin-right: 8px; }
        .main-content { margin-left: 240px; padding: 80px 30px 30px; }
        .topbar { position: fixed; top: 0; left: 240px; right: 0; background: #fff; border-bottom: 1px solid #eee; padding: 12px 30px; z-index: 99; display: flex; align-items: center; justify-content: space-between; }
        .stat-card { border: none; border-radius: 12px; overflow: hidden; }
        .stat-card .icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
    </style>
    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<div class="sidebar">
    <div class="brand">
        <a href="{{ route('home') }}" class="text-decoration-none">
            <span style="font-size:1.2rem;font-weight:700;color:var(--accent)">Berita</span><span style="color:#fff;font-weight:700">Kini</span>
            <span class="badge bg-danger ms-1 small">Admin</span>
        </a>
    </div>
    <nav class="nav flex-column mt-2">
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="{{ route('admin.users') }}" class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Manajemen User
        </a>
        <a href="{{ route('admin.comments') }}" class="nav-link {{ request()->routeIs('admin.comments*') ? 'active' : '' }}">
            <i class="bi bi-chat-dots"></i> Manajemen Komentar
        </a>
        <hr style="border-color:#333;margin:8px 12px">
        <a href="{{ route('home') }}" class="nav-link">
            <i class="bi bi-globe"></i> Lihat Website
        </a>
        <form action="{{ route('logout') }}" method="POST" class="px-2">
            @csrf
            <button class="nav-link w-100 text-start border-0 bg-transparent text-danger" type="submit">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </button>
        </form>
    </nav>
</div>

{{-- Topbar --}}
<div class="topbar">
    <h6 class="mb-0 fw-semibold text-dark">@yield('title', 'Dashboard')</h6>
    <div class="d-flex align-items-center gap-2">
        <img src="{{ auth()->user()->avatar }}" width="32" height="32" class="rounded-circle">
        <span class="small fw-medium">{{ auth()->user()->name }}</span>
    </div>
</div>

{{-- Main Content --}}
<div class="main-content">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
@stack('scripts')
</body>
</html>
