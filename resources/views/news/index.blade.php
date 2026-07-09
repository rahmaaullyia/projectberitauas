@extends('layouts.app')
@section('title', 'Beranda')

@section('content')
<div class="container py-4">

    {{-- ─── Category Filter ───────────────────────────────────────────────────── --}}
    <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
        <a href="{{ route('home') }}"
           class="btn btn-sm rounded-pill {{ $activeCategory === 'semua' ? 'btn-dark' : 'btn-outline-secondary' }}">
            Semua
        </a>
        @foreach(['teknologi'=>'Teknologi','olahraga'=>'Olahraga','bisnis'=>'Bisnis','kesehatan'=>'Kesehatan','hiburan'=>'Hiburan','sains'=>'Sains'] as $slug => $label)
        <a href="{{ route('home', ['category' => $slug]) }}"
           class="btn btn-sm rounded-pill {{ $activeCategory === $slug ? 'btn-dark' : 'btn-outline-secondary' }}">
            {{ $label }}
        </a>
        @endforeach

        @auth
        <a href="{{ route('news.feed') }}" class="btn btn-sm rounded-pill btn-outline-danger ms-auto">
            <i class="bi bi-rss me-1"></i> Feed Saya
        </a>
        @endauth
    </div>

    {{-- ─── Kategori Favorit Banner (Mhs 1) ─────────────────────────────────── --}}
    @auth
        @if(!empty($favoriteCategories))
        <div class="alert alert-light border d-flex align-items-center gap-2 mb-4 py-2">
            <i class="bi bi-heart-fill text-danger"></i>
            <span class="small">Favorit kamu: </span>
            @foreach($favoriteCategories as $fav)
            <a href="{{ route('news.category', $fav) }}" class="category-pill cat-{{ $fav }}">{{ ucfirst($fav) }}</a>
            @endforeach
            <a href="{{ route('profile.edit') }}" class="ms-auto small text-muted">Ubah <i class="bi bi-pencil"></i></a>
        </div>
        @endif
    @endauth

    {{-- ─── Hero Section ───────────────────────────────────────────────────────── --}}
    @if(!empty($headlines))
    <div class="row g-3 mb-4">
        {{-- Main headline --}}
        @if(isset($headlines[0]))
        <div class="col-lg-7">
            <a href="{{ route('news.show', $headlines[0]['id']) }}?category={{ $headlines[0]['category'] }}"
               class="text-decoration-none">
                <div class="hero-card">
                    <img src="{{ $headlines[0]['urlToImage'] ?? 'https://placehold.co/700x380/1a1a2e/ffffff?text=BeritaKini' }}"
                         alt="{{ $headlines[0]['title'] }}"
                         onerror="this.src='https://placehold.co/700x380/1a1a2e/ffffff?text=BeritaKini'">
                    <div class="overlay">
                        <span class="category-pill cat-{{ $headlines[0]['category'] }} mb-2 d-inline-block">
                            {{ ucfirst($headlines[0]['category']) }}
                        </span>
                        <h2>{{ $headlines[0]['title'] }}</h2>
                        <small class="opacity-75">
                            {{ $headlines[0]['source']['name'] ?? '' }} •
                            {{ \Carbon\Carbon::parse($headlines[0]['publishedAt'])->diffForHumans() }}
                        </small>
                    </div>
                </div>
            </a>
        </div>
        @endif

        {{-- Side headlines --}}
        <div class="col-lg-5">
            <div class="row g-3 h-100">
                @foreach(array_slice($headlines, 1, 2) as $headline)
                <div class="col-12">
                    <a href="{{ route('news.show', $headline['id']) }}?category={{ $headline['category'] }}"
                       class="text-decoration-none">
                        <div class="hero-card" style="height:180px">
                            <img src="{{ $headline['urlToImage'] ?? 'https://placehold.co/400x180/1a1a2e/fff?text=BeritaKini' }}"
                                 alt="{{ $headline['title'] }}"
                                 onerror="this.src='https://placehold.co/400x180/1a1a2e/fff?text=BeritaKini'">
                            <div class="overlay">
                                <span class="category-pill cat-{{ $headline['category'] }} mb-1 d-inline-block">{{ ucfirst($headline['category']) }}</span>
                                <h2 style="font-size:1rem">{{ Str::limit($headline['title'], 80) }}</h2>
                            </div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ─── Berita Lainnya ─────────────────────────────────────────────────────── --}}
    @if(!empty($restNews))
    <h5 class="fw-bold mb-3 border-start border-3 border-danger ps-3">Berita Terkini</h5>
    <div class="row g-4">
        @foreach($restNews as $article)
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <div class="card news-card h-100">
                <a href="{{ route('news.show', $article['id']) }}?category={{ $article['category'] }}">
                    <img src="{{ $article['urlToImage'] ?? 'https://placehold.co/400x200/eee/999?text=No+Image' }}"
                         class="card-img-top"
                         alt="{{ $article['title'] }}"
                         onerror="this.src='https://placehold.co/400x200/eee/999?text=No+Image'">
                </a>
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="category-pill cat-{{ $article['category'] }}">{{ ucfirst($article['category']) }}</span>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($article['publishedAt'])->diffForHumans() }}</small>
                    </div>
                    <a href="{{ route('news.show', $article['id']) }}?category={{ $article['category'] }}" class="text-decoration-none text-dark">
                        <h6 class="card-title">{{ $article['title'] }}</h6>
                    </a>
                    <p class="card-text small flex-grow-1">{{ $article['description'] }}</p>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">{{ $article['source']['name'] ?? 'Sumber tidak diketahui' }}</small>
                        @auth
                        <form action="{{ route('news.save', $article['id']) }}" method="POST">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary border-0 p-1" title="Simpan">
                                <i class="bi bi-bookmark"></i>
                            </button>
                        </form>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-5">
        <i class="bi bi-newspaper" style="font-size:3rem;color:#ccc"></i>
        <p class="mt-2 text-muted">tidak ada berita untuk ditampilkan.</p>
    </div>
    @endif

</div>
@endsection
