@extends('layouts.app')
@section('title', 'Berita Tersimpan')
@section('content')
<div class="container py-4">
    <h4 class="fw-bold mb-4"><i class="bi bi-bookmark-fill text-warning me-2"></i>Berita Tersimpan</h4>
    <div class="row g-4">
        @forelse($savedArticles as $article)
        <div class="col-sm-6 col-lg-4">
            <div class="card news-card h-100">
                <a href="{{ route('news.show', $article['id']) }}?category={{ $article['category'] }}">
                    <img src="{{ $article['urlToImage'] ?? 'https://placehold.co/400x200/eee/999' }}"
                         class="card-img-top" style="height:180px;object-fit:cover"
                         onerror="this.src='https://placehold.co/400x200/eee/999'">
                </a>
                <div class="card-body d-flex flex-column">
                    <span class="category-pill cat-{{ $article['category'] }} mb-2 d-inline-block">{{ ucfirst($article['category']) }}</span>
                    <a href="{{ route('news.show', $article['id']) }}?category={{ $article['category'] }}" class="text-decoration-none text-dark flex-grow-1">
                        <h6 class="card-title">{{ $article['title'] }}</h6>
                    </a>
                    <form action="{{ route('news.unsave', $article['id']) }}" method="POST" class="mt-2">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger w-100">
                            <i class="bi bi-bookmark-x me-1"></i> Hapus dari Simpanan
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5">
            <i class="bi bi-bookmark" style="font-size:3rem;color:#ccc"></i>
            <p class="mt-3 text-muted">Belum ada berita yang kamu simpan.</p>
            <a href="{{ route('home') }}" class="btn btn-outline-dark">Jelajahi Berita</a>
        </div>
        @endforelse
    </div>
</div>
@endsection
