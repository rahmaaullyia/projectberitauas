@extends('layouts.app')
@section('title', ucfirst($activeCategory))

@section('content')
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <span class="category-pill cat-{{ $activeCategory }} mb-2 d-inline-block">{{ ucfirst($activeCategory) }}</span>
            <h2 class="fw-bold mb-0">Berita {{ ucfirst($activeCategory) }}</h2>
            <small class="text-muted">{{ number_format($totalResults) }} artikel ditemukan</small>
        </div>
    </div>

    <div class="row g-4">
        @forelse($articles as $article)
        <div class="col-sm-6 col-lg-4">
            <div class="card news-card h-100">
                <a href="{{ route('news.show', $article['id']) }}?category={{ $article['category'] }}">
                    <img src="{{ $article['urlToImage'] ?? 'https://placehold.co/400x200/eee/999?text=No+Image' }}"
                         class="card-img-top" style="height:200px;object-fit:cover"
                         onerror="this.src='https://placehold.co/400x200/eee/999?text=No+Image'"
                         alt="{{ $article['title'] }}">
                </a>
                <div class="card-body d-flex flex-column">
                    <small class="text-muted mb-2">
                        {{ $article['source']['name'] ?? '' }} •
                        {{ \Carbon\Carbon::parse($article['publishedAt'])->diffForHumans() }}
                    </small>
                    <a href="{{ route('news.show', $article['id']) }}?category={{ $article['category'] }}" class="text-decoration-none text-dark">
                        <h6 class="card-title fw-semibold">{{ $article['title'] }}</h6>
                    </a>
                    <p class="card-text small text-muted flex-grow-1">{{ Str::limit($article['description'], 120) }}</p>
                    <div class="d-flex justify-content-between mt-2">
                        <a href="{{ route('news.show', $article['id']) }}?category={{ $article['category'] }}" class="btn btn-sm btn-outline-dark">Baca</a>
                        @auth
                        <form action="{{ route('news.save', $article['id']) }}" method="POST">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary border-0"><i class="bi bi-bookmark"></i></button>
                        </form>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <i class="bi bi-newspaper" style="font-size:3rem;color:#ccc"></i>
            <p class="mt-3 text-muted">Tidak ada berita untuk kategori ini saat ini.</p>
        </div>
        @endforelse
    </div>

    {{-- Pagination sederhana --}}
    @if($totalResults > 12)
    <div class="d-flex justify-content-center gap-2 mt-5">
        @if($page > 1)
        <a href="{{ route('news.category', $activeCategory) }}?page={{ $page - 1 }}" class="btn btn-outline-secondary">
            <i class="bi bi-chevron-left"></i> Sebelumnya
        </a>
        @endif
        <span class="btn btn-light disabled">Halaman {{ $page }}</span>
        @if($page * 12 < $totalResults)
        <a href="{{ route('news.category', $activeCategory) }}?page={{ $page + 1 }}" class="btn btn-outline-dark">
            Selanjutnya <i class="bi bi-chevron-right"></i>
        </a>
        @endif
    </div>
    @endif
</div>
@endsection