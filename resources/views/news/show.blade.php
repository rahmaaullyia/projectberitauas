@extends('layouts.app')
@section('title', Str::limit($article['title'], 60))

@section('content')
<div class="container py-4">
    <div class="row g-4">

        {{-- ─── Artikel Utama ───────────────────────────────────────────────────── --}}
        <div class="col-lg-8">
            {{-- Breadcrumb --}}
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('news.category', $article['category']) }}">{{ ucfirst($article['category']) }}</a></li>
                    <li class="breadcrumb-item active">{{ Str::limit($article['title'], 40) }}</li>
                </ol>
            </nav>

            {{-- Category & Meta --}}
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="category-pill cat-{{ $article['category'] }}">{{ ucfirst($article['category']) }}</span>
                <small class="text-muted">
                    {{ \Carbon\Carbon::parse($article['publishedAt'])->translatedFormat('d F Y, H:i') }} WIB
                </small>
            </div>

            {{-- Title --}}
            <h1 class="h3 fw-bold mb-3" style="font-family:'Playfair Display',serif;line-height:1.4">
                {{ $article['title'] }}
            </h1>

            {{-- Author & Source --}}
            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                <div>
                    <div class="fw-semibold small">{{ $article['author'] ?? 'Redaksi' }}</div>
                    <div class="text-muted" style="font-size:.8rem">{{ $article['source']['name'] ?? 'Sumber tidak diketahui' }}</div>
                </div>

                {{-- Save button (Mhs 1) --}}
                <div class="ms-auto">
                    @auth
                        @if($isSaved)
                        <form action="{{ route('news.unsave', $id) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-warning">
                                <i class="bi bi-bookmark-fill me-1"></i> Tersimpan
                            </button>
                        </form>
                        @else
                        <form action="{{ route('news.save', $id) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-bookmark me-1"></i> Simpan
                            </button>
                        </form>
                        @endif
                    @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-bookmark me-1"></i> Simpan
                    </a>
                    @endauth

                    <a href="{{ $article['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary ms-1">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Sumber
                    </a>
                </div>
            </div>

            {{-- Gambar --}}
            @if(!empty($article['urlToImage']))
            <figure class="mb-4">
                <img src="{{ $article['urlToImage'] }}"
                     class="img-fluid rounded-3 w-100"
                     style="max-height:420px;object-fit:cover"
                     alt="{{ $article['title'] }}"
                     onerror="this.style.display='none'">
                <figcaption class="text-muted small mt-1 text-center">{{ $article['source']['name'] ?? '' }}</figcaption>
            </figure>
            @endif

            {{-- Konten --}}
            <div class="article-body" style="font-size:1.05rem;line-height:1.9;color:#222">
                <p class="lead fw-medium">{{ $article['description'] }}</p>
                <p>{{ $article['content'] ?? 'Untuk membaca artikel lengkap, silakan kunjungi sumber berita.' }}</p>
                <div class="alert alert-light border-start border-4 border-primary mt-4">
                    <i class="bi bi-info-circle me-2"></i>
                    Untuk membaca artikel lengkap, kunjungi sumber asli:
                    <a href="{{ $article['url'] }}" target="_blank" class="fw-semibold">{{ $article['source']['name'] ?? 'Sumber' }}</a>
                </div>
            </div>

            {{-- ─── Komentar (Mhs 3) ───────────────────────────────────────────── --}}
            <div class="mt-5">
                <h5 class="fw-bold mb-4 border-start border-3 border-danger ps-3">
                    Komentar <span class="badge bg-secondary">{{ $comments->count() }}</span>
                </h5>

                {{-- Form komentar --}}
                @auth
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex gap-3">
                            <img src="{{ auth()->user()->avatar }}" width="40" height="40" class="rounded-circle flex-shrink-0">
                            <form action="{{ route('comments.store', $id) }}" method="POST" class="flex-grow-1">
                                @csrf
                                <textarea name="body" rows="3" class="form-control mb-2 @error('body') is-invalid @enderror"
                                          placeholder="Tulis komentar kamu...">{{ old('body') }}</textarea>
                                @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <button class="btn btn-sm btn-danger">
                                    <i class="bi bi-send me-1"></i> Kirim Komentar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @else
                <div class="alert alert-light border text-center mb-4">
                    <a href="{{ route('login') }}" class="fw-semibold">Masuk</a> untuk meninggalkan komentar.
                </div>
                @endauth

                {{-- Daftar komentar --}}
                @forelse($comments as $comment)
                <div class="d-flex gap-3 mb-4">
                    <img src="{{ $comment->user->avatar }}" width="40" height="40" class="rounded-circle flex-shrink-0">
                    <div class="flex-grow-1">
                        <div class="bg-light rounded-3 p-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold small">{{ $comment->user->name }}</span>
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                                    @if(auth()->check() && (auth()->id() === $comment->user_id || auth()->user()->is_admin))
                                    <form action="{{ route('comments.destroy', $comment->id) }}" method="POST"
                                          onsubmit="return confirm('Hapus komentar ini?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-link btn-sm text-danger p-0" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                            <p class="mb-0 small">{{ $comment->body }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-chat-square" style="font-size:2rem"></i>
                    <p class="mt-2">Belum ada komentar. Jadilah yang pertama!</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- ─── Sidebar: Berita Terkait ─────────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="sticky-top" style="top:80px">
                <h6 class="fw-bold mb-3 border-start border-3 border-danger ps-3">Berita Terkait</h6>
                @forelse($related as $item)
                <a href="{{ route('news.show', $item['id']) }}?category={{ $item['category'] }}"
                   class="text-decoration-none">
                    <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                        <img src="{{ $item['urlToImage'] ?? 'https://placehold.co/80x60/eee/999?text=Berita' }}"
                             width="80" height="60" class="rounded-2 flex-shrink-0"
                             style="object-fit:cover"
                             onerror="this.src='https://placehold.co/80x60/eee/999?text=Berita'">
                        <div>
                            <p class="small fw-semibold text-dark mb-1" style="line-height:1.3">{{ Str::limit($item['title'], 70) }}</p>
                            <small class="text-muted">{{ \Carbon\Carbon::parse($item['publishedAt'])->diffForHumans() }}</small>
                        </div>
                    </div>
                </a>
                @empty
                <p class="text-muted small">Tidak ada berita terkait.</p>
                @endforelse

                {{-- Link ke kategori --}}
                <a href="{{ route('news.category', $article['category']) }}" class="btn btn-sm btn-outline-dark w-100 mt-2">
                    Lebih banyak dari {{ ucfirst($article['category']) }} <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
