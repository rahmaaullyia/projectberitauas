<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BeritaHariIni') - Portal Berita Indonesia</title>

    {{-- Bootstrap 5 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- DataTables --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #1a1a2e;
            --accent:  #e94560;
            --light-bg: #f8f9fa;
        }
        body { font-family: 'Inter', sans-serif; background: var(--light-bg); }

        /* Navbar */
        .navbar-brand span { font-family: 'Playfair Display', serif; color: var(--accent); }
        .navbar { background: var(--primary) !important; box-shadow: 0 2px 8px rgba(0,0,0,.3); }
        .navbar .nav-link { color: #ccc !important; transition: color .2s; }
        .navbar .nav-link:hover, .navbar .nav-link.active { color: #fff !important; }

        /* Category pills */
        .category-pill {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .cat-teknologi { background: #dbeafe; color: #1d4ed8; }
        .cat-olahraga  { background: #dcfce7; color: #166534; }
        .cat-bisnis    { background: #fef9c3; color: #854d0e; }
        .cat-kesehatan { background: #fce7f3; color: #9d174d; }
        .cat-hiburan   { background: #ede9fe; color: #6d28d9; }
        .cat-sains     { background: #e0f2fe; color: #0369a1; }

        /* News card */
        .news-card { border: none; box-shadow: 0 1px 4px rgba(0,0,0,.08); border-radius: 12px; transition: transform .2s, box-shadow .2s; overflow: hidden; }
        .news-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.14); }
        .news-card img { height: 200px; object-fit: cover; }
        .news-card .card-title { font-size: 1rem; font-weight: 600; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .news-card .card-text { font-size: .88rem; color: #555; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

        /* Hero card */
        .hero-card { position: relative; border-radius: 16px; overflow: hidden; height: 380px; }
        .hero-card img { width: 100%; height: 100%; object-fit: cover; }
        .hero-card .overlay { position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,.85)); padding: 24px; color: #fff; }
        .hero-card .overlay h2 { font-family: 'Playfair Display', serif; font-size: 1.5rem; }

        /* Alert */
        .alert { border-radius: 10px; }

        /* Footer */
        footer { background: var(--primary); color: #aaa; }
        footer a { color: #ccc; text-decoration: none; }
        footer a:hover { color: #fff; }

        /* Admin sidebar */
        .admin-sidebar { min-height: calc(100vh - 56px); background: var(--primary); }
        .admin-sidebar .nav-link { color: #ccc; padding: 10px 20px; border-radius: 8px; margin: 2px 8px; }
        .admin-sidebar .nav-link:hover, .admin-sidebar .nav-link.active { background: rgba(233,69,96,.2); color: #fff; }
        .admin-sidebar .nav-link i { width: 20px; }

        @media (max-width: 767px) {
            .hero-card { height: 240px; }
            .hero-card .overlay h2 { font-size: 1.1rem; }
        }
    </style>
    @stack('styles')
</head>
<body>

{{-- ─── Navbar ──────────────────────────────────────────────────────────────── --}}
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('home') }}">
            <span>Berita</span><span style="color:#fff">HariIni</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            {{-- Search --}}
            <form class="d-flex mx-auto my-2 my-lg-0" style="width:320px" action="{{ route('news.search') }}" method="GET">
                <div class="input-group">
                    <input class="form-control form-control-sm" type="search" name="q" placeholder="Cari berita..." value="{{ request('q') }}">
                    <button class="btn btn-sm" style="background:var(--accent);color:#fff" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>

            {{-- Nav Links --}}
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                @foreach(['teknologi'=>'Teknologi','olahraga'=>'Olahraga','bisnis'=>'Bisnis','kesehatan'=>'Kesehatan'] as $slug=>$label)
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('news.category') && request()->route('category') === $slug ? 'active' : '' }}"
                       href="{{ route('news.category', $slug) }}">{{ $label }}</a>
                </li>
                @endforeach

                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#" data-bs-toggle="dropdown">
                            <img src="{{ auth()->user()->avatar }}" width="28" height="28" class="rounded-circle">
                            {{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('news.feed') }}"><i class="bi bi-rss me-2"></i>Feed Saya</a></li>
                            <li><a class="dropdown-item" href="{{ route('news.saved') }}"><i class="bi bi-bookmark me-2"></i>Tersimpan</a></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Profil</a></li>
                            @if(auth()->user()->is_admin)
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="{{ route('admin.dashboard') }}"><i class="bi bi-shield me-2"></i>Admin Panel</a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Masuk</a></li>
                    <li class="nav-item">
                        <a class="btn btn-sm ms-1" style="background:var(--accent);color:#fff;border-radius:20px;padding:5px 16px" href="{{ route('register') }}">Daftar</a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

{{-- ─── Flash Messages ───────────────────────────────────────────────────────── --}}
@if(session('success') || session('error') || session('info'))
<div class="container mt-3">
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('info'))<div class="alert alert-info alert-dismissible fade show"><i class="bi bi-info-circle me-2"></i>{{ session('info') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
</div>
@endif

{{-- ─── Content ──────────────────────────────────────────────────────────────── --}}
@yield('content')

{{-- ─── Footer ───────────────────────────────────────────────────────────────── --}}
<footer class="mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-3">
                <h5 class="text-white fw-bold mb-2"><span style="color:var(--accent)">Berita</span>Kini</h5>
                <p class="small">Portal agregator berita nasional Indonesia. Baca berita dari berbagai kategori di satu tempat.</p>
            </div>
            <div class="col-md-4 mb-3">
                <h6 class="text-white fw-semibold">Kategori</h6>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    @foreach(['teknologi','olahraga','bisnis','kesehatan','hiburan','sains'] as $cat)
                    <a href="{{ route('news.category', $cat) }}" class="small">{{ ucfirst($cat) }}</a>
                    @endforeach
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <h6 class="text-white fw-semibold">Tentang BeritaKini</h6>
                <p class="small mb-1">Portal berita Indonesia yang menyajikan berita terbaru berdasarkan topik pilihan pengguna.</p>
                <p class="small">© 2026 BeritaKini. All Rights Reserved.</p>
            </div>
        </div>
        <hr style="border-color:#333">
        <p class="small text-center mb-0">&copy; {{ date('Y') }} BeritaKini. Proyek Kelompok Pemrograman Web.</p>
    </div>
</footer>

{{-- Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
@stack('scripts')
</body>
</html>
