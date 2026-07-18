@extends('layouts.app', ['title' => 'Profil Saya'])

@section('content')
    @php
        $profilePhoto = $user->profile_photo_url ?? $user->avatar_url ?? null;
        $userInitial = mb_strtoupper(mb_substr(trim($user->name), 0, 1));
    @endphp

    <section class="profile-hero participant-hero">
        <div class="participant-hero-content">
            <div class="participant-avatar" aria-hidden="true">
                @if ($profilePhoto)
                    <img src="{{ $profilePhoto }}" alt="">
                @else
                    <span>{{ $userInitial ?: '?' }}</span>
                @endif
            </div>
            <h1>{{ $user->name }}</h1>
            <p class="participant-hero-subtitle">Kelola informasi akun, tiket, dan riwayat pendaftaran event Anda.</p>
            <p class="participant-member-since">Member sejak {{ $user->created_at?->translatedFormat('F Y') ?? '-' }}</p>
        </div>
    </section>

    <section class="participant-profile participant-profile-layout">
        <aside class="participant-profile-menu" aria-label="Menu profil">
            <a class="is-active" href="#data-diri"><span aria-hidden="true">👤</span> Data Diri</a>
            <a href="{{ route('tickets.index') }}"><span aria-hidden="true">🎟</span> Tiket Saya</a>
            <a href="{{ route('participant.activity') }}"><span aria-hidden="true">↻</span> Riwayat Pembayaran</a>
            <a href="#ubah-password"><span aria-hidden="true">🔒</span> Ubah Password</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"><span aria-hidden="true">↪</span> Keluar</button>
            </form>
        </aside>

        <div class="participant-profile-content">
        <article class="profile-info participant-card" id="data-diri" data-reveal>
            <div>
                <p class="eyebrow">Informasi Akun</p>
            </div>
            <dl class="info-list account-info-list">
                <div><dt>Nama Lengkap</dt><dd>{{ $user->name }}</dd></div>
                <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                <div><dt>Tanggal Bergabung</dt><dd>{{ $user->created_at?->translatedFormat('d F Y') ?? '-' }}</dd></div>
            </dl>
        </article>

        <details class="profile-info participant-card profile-password-card" id="ubah-password" data-reveal @if ($errors->hasAny(['current_password', 'password']) || session('status')) open @endif>
            <summary class="profile-password-summary">
                <span>Ganti Password</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
            </summary>

            <div class="profile-password-content">
                <p>Gunakan password minimal 8 karakter.</p>

                @if (session('status'))
                    <div class="success-box" role="status">{{ session('status') }}</div>
                @endif

                <form class="profile-password-form" method="POST" action="{{ route('participant.password.update') }}">
                @csrf
                @method('PATCH')

                <label>Password Saat Ini
                    <span class="password-field">
                        <input type="password" name="current_password" autocomplete="current-password" required data-password-input>
                        <button type="button" data-password-toggle aria-label="Lihat password saat ini" title="Lihat password">
                            <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a3 3 0 0 0 4.2 4.2M9.9 4.4A10.4 10.4 0 0 1 12 4c6.5 0 10 8 10 8a18.7 18.7 0 0 1-3.1 4.4M6.7 6.7C3.6 8.8 2 12 2 12s3.5 8 10 8a10.8 10.8 0 0 0 4.3-.9"></path></svg>
                        </button>
                    </span>
                    @error('current_password') <small class="field-error">{{ $message }}</small> @enderror
                </label>

                <label>Password Baru
                    <span class="password-field">
                        <input type="password" name="password" autocomplete="new-password" minlength="8" required data-password-input>
                        <button type="button" data-password-toggle aria-label="Lihat password baru" title="Lihat password">
                            <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a3 3 0 0 0 4.2 4.2M9.9 4.4A10.4 10.4 0 0 1 12 4c6.5 0 10 8 10 8a18.7 18.7 0 0 1-3.1 4.4M6.7 6.7C3.6 8.8 2 12 2 12s3.5 8 10 8a10.8 10.8 0 0 0 4.3-.9"></path></svg>
                        </button>
                    </span>
                    @error('password') <small class="field-error">{{ $message }}</small> @enderror
                </label>

                <label>Konfirmasi Password Baru
                    <span class="password-field">
                        <input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required data-password-input>
                        <button type="button" data-password-toggle aria-label="Lihat konfirmasi password baru" title="Lihat password">
                            <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a3 3 0 0 0 4.2 4.2M9.9 4.4A10.4 10.4 0 0 1 12 4c6.5 0 10 8 10 8a18.7 18.7 0 0 1-3.1 4.4M6.7 6.7C3.6 8.8 2 12 2 12s3.5 8 10 8a10.8 10.8 0 0 0 4.3-.9"></path></svg>
                        </button>
                    </span>
                </label>

                    <button class="button profile-password-submit" type="submit">Simpan Password Baru</button>
                </form>
            </div>
        </details>
        </div>
    </section>

@endsection
