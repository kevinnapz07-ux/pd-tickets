@extends('layouts.app', ['title' => 'Daftar Akun'])

@section('content')
    <section class="auth-panel">
        <div>
            <h1 class="auth-title">Daftar Akun</h1>
        </div>

        @if ($errors->any())
            <div class="error-box">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <label>Nama Lengkap
                <input name="name" value="{{ old('name') }}" required>
            </label>
            <label>Email
                <input type="email" name="email" value="{{ old('email') }}" required>
                <small>Gunakan alamat Gmail yang masih aktif.</small>
            </label>
            <label>Password
                <span class="password-field">
                    <input type="password" name="password" required data-password-input>
                    <button type="button" data-password-toggle aria-label="Lihat password" title="Lihat password">
                        <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a3 3 0 0 0 4.2 4.2M9.9 4.4A10.4 10.4 0 0 1 12 4c6.5 0 10 8 10 8a18.7 18.7 0 0 1-3.1 4.4M6.7 6.7C3.6 8.8 2 12 2 12s3.5 8 10 8a10.8 10.8 0 0 0 4.3-.9"></path></svg>
                    </button>
                </span>
            </label>
            <label>Konfirmasi Password
                <span class="password-field">
                    <input type="password" name="password_confirmation" required data-password-input>
                    <button type="button" data-password-toggle aria-label="Lihat password" title="Lihat password">
                        <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a3 3 0 0 0 4.2 4.2M9.9 4.4A10.4 10.4 0 0 1 12 4c6.5 0 10 8 10 8a18.7 18.7 0 0 1-3.1 4.4M6.7 6.7C3.6 8.8 2 12 2 12s3.5 8 10 8a10.8 10.8 0 0 0 4.3-.9"></path></svg>
                    </button>
                </span>
            </label>
            <button class="button button-full" type="submit">Buat Akun</button>
            <a class="link-button button-full" href="{{ route('login') }}">Sudah Punya Akun</a>
        </form>
    </section>
@endsection
