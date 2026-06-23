@extends('layouts.admin')
@section('title', 'Dashboard Admin')

@section('content')
<div class="row g-4 mb-4">

    {{-- Stat Cards --}}
    <div class="col-md-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div class="h4 mb-0 fw-bold">{{ $stats['total_users'] }}</div>
                    <div class="small text-muted">Total User</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-person-check-fill"></i>
                </div>
                <div>
                    <div class="h4 mb-0 fw-bold">{{ $stats['active_users'] }}</div>
                    <div class="small text-muted">User Aktif</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-person-x-fill"></i>
                </div>
                <div>
                    <div class="h4 mb-0 fw-bold">{{ $stats['inactive_users'] }}</div>
                    <div class="small text-muted">User Nonaktif</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-chat-dots-fill"></i>
                </div>
                <div>
                    <div class="h4 mb-0 fw-bold">{{ $stats['total_comments'] }}</div>
                    <div class="small text-muted">Total Komentar</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- Recent Users --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">User Terbaru</h6>
                <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-dark">Lihat Semua</a>
            </div>
            <div class="card-body">
                @foreach($stats['recent_users'] as $user)
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="{{ $user->avatar }}" width="36" height="36" class="rounded-circle">
                    <div class="flex-grow-1">
                        <div class="fw-semibold small">{{ $user->name }}</div>
                        <div class="text-muted" style="font-size:.78rem">{{ $user->email }}</div>
                    </div>
                    <div>
                        @if($user->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-danger">Nonaktif</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Recent Comments --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Komentar Terbaru</h6>
                <a href="{{ route('admin.comments') }}" class="btn btn-sm btn-outline-dark">Lihat Semua</a>
            </div>
            <div class="card-body">
                @forelse($stats['recent_comments'] as $comment)
                <div class="d-flex gap-2 mb-3 pb-3 border-bottom">
                    <img src="{{ $comment->user->avatar }}" width="32" height="32" class="rounded-circle flex-shrink-0">
                    <div>
                        <div class="fw-semibold small">{{ $comment->user->name }}</div>
                        <p class="mb-0 small text-muted">{{ Str::limit($comment->body, 80) }}</p>
                        <small class="text-muted" style="font-size:.75rem">{{ $comment->created_at->diffForHumans() }}</small>
                    </div>
                </div>
                @empty
                <p class="text-muted small">Belum ada komentar.</p>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection