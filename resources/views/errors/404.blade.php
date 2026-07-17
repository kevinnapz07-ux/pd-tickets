@extends('layouts.app', [
    'title' => 'Halaman Tidak Ditemukan',
    'metaDescription' => 'Halaman yang Anda cari tidak tersedia atau telah dipindahkan.',
])

@section('content')
    <section class="auth-panel">
        <div>
            <p class="eyebrow">404</p>
            <h1 class="auth-title">Halaman Tidak Ditemukan</h1>
            <p>Halaman yang Anda cari tidak tersedia atau telah dipindahkan.</p>
        </div>
        <a class="button button-full" href="{{ route('events.index') }}">Kembali ke Beranda</a>
    </section>
@endsection
