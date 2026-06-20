@extends('layouts.app')
@section('title', 'Masuk')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold"><span style="color:var(--accent)">Berita</span>Kini</h2>
                        <p class="text-muted small">Masuk untuk melanjutkan</p>
                    </div>

                    <form action="{{ route('login') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-medium small">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                   class="form-control @error('email') is-invalid @enderror"
                                   placeholder="email@example.com" required autofocus>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium small">Password</label>
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="••••••••" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="remember" id="remember">
                            <label class="form-check-label small" for="remember">Ingat saya</label>
                        </div>

                        <button type="submit" class="btn w-100 fw-semibold"
                                style="background:var(--primary);color:#fff;border-radius:10px;padding:10px">
                            Masuk
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <small class="text-muted">Belum punya akun?
                            <a href="{{ route('register') }}" class="fw-semibold text-decoration-none" style="color:var(--accent)">Daftar sekarang</a>
                        </small>
                    </div>

                    {{-- Demo accounts --}}
                    <div class="mt-4 p-3 bg-light rounded-3">
                        <p class="small fw-semibold mb-2 text-muted">Akun Demo:</p>
                        <div class="small">
                            <div><code>admin@beritakini.id</code> / <code>password</code> (Admin)</div>
                            <div><code>budi@example.com</code> / <code>password</code> (User)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
