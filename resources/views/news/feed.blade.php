@extends('layouts.app')
@section('title', 'Feed Saya')
@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h2 class="fw-bold">Feed Saya</h2>
        <p class="text-muted">Berita dari kategori favorit kamu:
            @foreach($favoriteCategories as $cat)
                <span class="category-pill cat-{{ $cat }}">{{ ucfirst($cat) }}</span>
            @endforeach
            <a href="{{ route('profile.edit') }}" class="ms-2 small text-muted">Ubah <i class="bi bi-pencil"></i></a>
        </p>
    </div>
    <div class="row g-4">
        @forelse($articles as $article)
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <div class="card news-card h-100">
                <a href="{{ route('news.show', $article['id']) }}?category={{ $article['category'] }}">
                    <img src="{{ $article['urlToImage'] ?? 'https://placehold.co/400x200/eee/999' }}"
                         class="card-img-top" style="height:180px;object-fit:cover"
                         onerror="this.src='https://placehold.co/400x200/eee/999'">
                </a>
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="category-pill cat-{{ $article['category'] }}">{{ ucfirst($article['category']) }}</span>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($article['publishedAt'])->diffForHumans() }}</small>
                    </div>
                    <a href="{{ route('news.show', $article['id']) }}?category={{ $article['category'] }}" class="text-decoration-none text-dark">
                        <h6 class="card-title">{{ $article['title'] }}</h6>
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <p class="text-muted">Tidak ada berita dari kategori favorit kamu.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
