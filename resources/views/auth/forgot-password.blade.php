@extends('layouts.app', ['title' => 'Lupa Password'])

@section('content')
    <section class="auth-panel">
        <div>
            <h1>Lupa Password</h1>
        </div>

        @if (session('status'))
            <div class="notice toast-notice" role="status" data-toast>
                Jika alamat email tersebut terdaftar, link reset telah dikirim ke email tersebut. Silakan periksa Inbox atau Spam.
            </div>
        @endif

        @if ($errors->any())
            <div class="error-box">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" data-password-reset-request>
            @csrf
            <label>Email
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
            </label>
            <button class="button button-full" type="submit" data-reset-submit>
                <span data-reset-submit-label>Kirim Link Reset Password</span>
                <span class="button-loading" aria-hidden="true"></span>
            </button>
            <a class="link-button button-full" href="{{ route('login') }}">Kembali ke Login</a>
        </form>
    </section>
@endsection
