@extends('layouts.app', ['title' => 'Reset Password'])

@section('content')
    <section class="auth-panel">
        <div>
            <p class="eyebrow">Akses Akun</p>
            <h1>Reset Password</h1>
            <p>Buat password baru untuk akun Anda. Gunakan minimal 8 karakter agar akun tetap aman.</p>
        </div>

        @if ($errors->any())
            <div class="error-box">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label>Email
                <input type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required>
            </label>
            <label>Password Baru
                <input type="password" name="password" autocomplete="new-password" minlength="8" required>
            </label>
            <label>Konfirmasi Password Baru
                <input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
            </label>
            <button class="button button-full" type="submit">Simpan Password Baru</button>
        </form>
    </section>
@endsection
