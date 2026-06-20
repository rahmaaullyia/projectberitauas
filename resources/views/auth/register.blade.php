@extends('layouts.app')
@section('title', 'Daftar')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold"><span style="color:var(--accent)">Berita</span>Kini</h2>
                        <p class="text-muted small">Buat akun baru</p>
                    </div>

                    <form action="{{ route('register') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-medium small">Nama Lengkap</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="Nama kamu" required autofocus>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium small">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                   class="form-control @error('email') is-invalid @enderror"
                                   placeholder="email@example.com" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium small">Password</label>
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Min. 8 karakter" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-medium small">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation"
                                   class="form-control"
                                   placeholder="Ulangi password" required>
                        </div>

                        <button type="submit" class="btn w-100 fw-semibold"
                                style="background:var(--accent);color:#fff;border-radius:10px;padding:10px">
                            Daftar Sekarang
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <small class="text-muted">Sudah punya akun?
                            <a href="{{ route('login') }}" class="fw-semibold text-decoration-none" style="color:var(--accent)">Masuk</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
