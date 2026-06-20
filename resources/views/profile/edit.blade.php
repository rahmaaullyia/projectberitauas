@extends('layouts.app')
@section('title', 'Edit Profil')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            {{-- ─── Edit Profil ─────────────────────────────────────────────────── --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4 border-start border-3 border-danger ps-3">Edit Profil</h5>

                    <div class="text-center mb-4">
                        <img src="{{ $user->avatar }}" width="80" height="80" class="rounded-circle mb-2">
                        <div class="fw-semibold">{{ $user->name }}</div>
                        <div class="text-muted small">{{ $user->email }}</div>
                    </div>

                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf @method('PATCH')

                        <div class="mb-3">
                            <label class="form-label small fw-medium">Nama Lengkap</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                   class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-medium">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                   class="form-control @error('email') is-invalid @enderror" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <hr class="my-4">
                        <p class="small text-muted mb-3">Kosongkan jika tidak ingin mengubah password</p>

                        <div class="mb-3">
                            <label class="form-label small fw-medium">Password Saat Ini</label>
                            <input type="password" name="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   placeholder="Password lama">
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-medium">Password Baru</label>
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Min. 8 karakter">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-medium">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation"
                                   class="form-control" placeholder="Ulangi password baru">
                        </div>

                        <button type="submit" class="btn btn-dark w-100">
                            <i class="bi bi-check-circle me-1"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>

            {{-- ─── Kategori Favorit (Mhs 1: Sistem Kategori Favorit) ──────────── --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-2 border-start border-3 border-warning ps-3">Kategori Favorit</h5>
                    <p class="text-muted small mb-4">Pilih kategori yang ingin muncul di Feed Saya. Minimal 1 kategori.</p>

                    <form action="{{ route('profile.favorites') }}" method="POST">
                        @csrf @method('PATCH')
                        @error('categories')<div class="alert alert-danger small py-2">{{ $message }}</div>@enderror

                        <div class="row g-3 mb-4">
                            @foreach($availableCategories as $slug => $label)
                            <div class="col-6 col-md-4">
                                <label class="d-block cursor-pointer">
                                    <input type="checkbox" name="categories[]" value="{{ $slug }}"
                                           class="d-none category-check"
                                           {{ in_array($slug, $favoriteCategories) ? 'checked' : '' }}>
                                    <div class="category-choice border rounded-3 p-3 text-center h-100 {{ in_array($slug, $favoriteCategories) ? 'selected' : '' }}"
                                         style="cursor:pointer;transition:all .2s">
                                        <div class="mb-1">
                                            @php $icons = ['teknologi'=>'cpu','olahraga'=>'trophy','bisnis'=>'graph-up','kesehatan'=>'heart-pulse','hiburan'=>'music-note','sains'=>'flask'] @endphp
                                            <i class="bi bi-{{ $icons[$slug] ?? 'newspaper' }}" style="font-size:1.5rem"></i>
                                        </div>
                                        <div class="fw-semibold small">{{ $label }}</div>
                                    </div>
                                </label>
                            </div>
                            @endforeach
                        </div>

                        <button type="submit" class="btn w-100 fw-semibold" style="background:var(--accent);color:#fff">
                            <i class="bi bi-heart me-1"></i> Simpan Kategori Favorit
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.category-choice { background: #f8f9fa; border-color: #dee2e6 !important; }
.category-choice.selected { background: #1a1a2e !important; color: #fff; border-color: #1a1a2e !important; }
.category-choice:hover { border-color: #1a1a2e !important; }
</style>

@push('scripts')
<script>
document.querySelectorAll('.category-check').forEach(checkbox => {
    checkbox.addEventListener('change', function () {
        this.closest('label').querySelector('.category-choice').classList.toggle('selected', this.checked);
    });
});
</script>
@endpush
@endsection
