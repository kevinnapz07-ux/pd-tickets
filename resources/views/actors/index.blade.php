@extends('layouts.app', ['title' => 'Aktor Sistem'])

@section('content')
    <section class="profile-hero actor-hero">
        <div>
            <p class="eyebrow">Aktor Sistem</p>
            <h1>Pengunjung, Pengguna, dan Admin PD Gunadarma</h1>
            <p>Halaman ini menjelaskan siapa saja pengguna sistem dan fitur yang dapat mereka akses dalam aplikasi informasi dan registrasi event.</p>
        </div>
    </section>

    <section class="actor-grid">
        <article>
            <span>01</span>
            <h2>Pengunjung</h2>
            <p>Pengunjung dapat melihat informasi event, mencari agenda, membaca profil PDUG, melihat upcoming event, dan membuka detail event termasuk maps lokasi.</p>
        </article>
        <article>
            <span>02</span>
            <h2>Pengguna</h2>
            <p>Pengguna dapat login melalui halaman yang sama, mengisi form registrasi event, menerima kode registrasi, dan melanjutkan pembayaran untuk event berbayar.</p>
        </article>
        <article>
            <span>03</span>
            <h2>Admin PD Gunadarma</h2>
            <p>Admin dapat mengelola event, memantau registrasi, mengubah status pembayaran, mencetak laporan, dan mengatur informasi website.</p>
        </article>
    </section>

    <section class="actor-flow">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Alur Akses</p>
                <h2>Ringkasan Hak Akses</h2>
            </div>
        </div>
        <table>
            <thead>
                <tr><th>Fitur</th><th>Pengunjung</th><th>Pengguna</th><th>Admin</th></tr>
            </thead>
            <tbody>
                <tr><td>Lihat event dan profil</td><td>Ya</td><td>Ya</td><td>Ya</td></tr>
                <tr><td>Registrasi event</td><td>Login dahulu</td><td>Ya</td><td>Tidak</td></tr>
                <tr><td>Kelola event</td><td>Tidak</td><td>Tidak</td><td>Ya</td></tr>
                <tr><td>Kelola registrasi</td><td>Tidak</td><td>Tidak</td><td>Ya</td></tr>
                <tr><td>Cetak laporan</td><td>Tidak</td><td>Tidak</td><td>Ya</td></tr>
                <tr><td>Kelola website</td><td>Tidak</td><td>Tidak</td><td>Ya</td></tr>
            </tbody>
        </table>
    </section>
@endsection
