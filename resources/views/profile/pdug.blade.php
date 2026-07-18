@extends('layouts.app', ['title' => 'About Us'])

@section('content')
    <section class="profile-hero about-hero">
        <div>
            <h1>About Us</h1>
            <p>Mengenal lebih dekat Persekutuan Doa Universitas Gunadarma.</p>
        </div>
    </section>

    <section class="about-story">
        <div>
            <p class="eyebrow">Tentang PDUG</p>
            <h2>Cerita Kami</h2>
        </div>
        <div class="about-story-copy">
            <p>Persekutuan Doa Universitas Gunadarma (PDUG) merupakan organisasi kerohanian Kristen yang menjadi wadah bagi mahasiswa untuk bertumbuh dalam iman, membangun kebersamaan, dan melayani Tuhan.</p>
            <p>Melalui persekutuan, ibadah, pemuridan, doa, dan berbagai kegiatan pelayanan, PDUG hadir untuk membantu mahasiswa mengenal Kristus lebih dalam serta menjadi berkat di lingkungan kampus.</p>
            <p>Website ini digunakan untuk menyampaikan informasi kegiatan dan memudahkan mahasiswa dalam melakukan registrasi event PDUG secara praktis dan terintegrasi.</p>
        </div>
    </section>

    <section class="about-identity">
        <div class="profile-grid about-grid">
            <article>
                <p class="eyebrow">Visi Global</p>
                <h2>Gunadarma dibakar habis oleh api kemuliaan-Nya.</h2>
            </article>
            <article>
                <p class="eyebrow">Visi Generasi</p>
                <h2>The Army of God</h2>
                <small>Yoel 2:1–11</small>
            </article>
        </div>
    </section>

    <section class="about-programs">
        <div class="section-heading">
            <div>
                <h2>Kegiatan Kami</h2>
            </div>
        </div>
        <div class="about-program-grid">
            <article><span>01</span><h3>Persekutuan & Ibadah</h3><p>Ruang untuk beribadah, berdoa, dan mengalami pertumbuhan rohani bersama.</p></article>
            <article><span>02</span><h3>Pemuridan</h3><p>Pendampingan yang menolong mahasiswa mengenal firman dan hidup sebagai murid Kristus.</p></article>
            <article><span>03</span><h3>Pelayanan Kampus</h3><p>Kesempatan untuk melayani, membangun komunitas, dan menjadi berkat di lingkungan kampus.</p></article>
        </div>
    </section>

    <section class="latest-articles">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Kabar & Renungan</p>
                <h2>Artikel Terbaru</h2>
            </div>
            <a class="link-button" href="{{ route('articles.index') }}">Lihat Semua Artikel</a>
        </div>
        <div class="article-grid">
            @forelse ($latestArticles as $article)
                @include('articles.partials.card', ['article' => $article])
            @empty
                <div class="article-empty">Belum ada artikel yang dipublikasikan. Nantikan kabar, renungan, dan informasi kegiatan terbaru dari PDUG.</div>
            @endforelse
        </div>
    </section>

    @php
        $configuredEmail = $siteSetting?->contact_email;
        $contactEmail = filled($configuredEmail) && ! str_ends_with(strtolower($configuredEmail), '.test')
            ? $configuredEmail
            : 'pdgunadarmates@gmail.com';
        $whatsappNumber = \App\Models\SiteSetting::whatsappNumber($siteSetting?->contact_phone);
        $contactAddress = filled($siteSetting?->contact_address) ? $siteSetting->contact_address : null;
    @endphp

    <section class="about-contact-us">
        <div class="contact-simple-hero">
            <h2>Ada yang bisa kami bantu?</h2>
            <div class="contact-simple-copy">
                <p>Hubungi kami melalui media sosial resmi PDUG atau ikuti akun kami untuk mendapatkan informasi event, pelayanan, dan berbagai kegiatan.</p>
                @if ($whatsappNumber)
                    <a class="contact-simple-button" href="https://wa.me/{{ $whatsappNumber }}?text={{ rawurlencode('Halo PDUG, saya ingin bertanya mengenai kegiatan atau event.') }}" target="_blank" rel="noopener">Hubungi Kami</a>
                @else
                    <a class="contact-simple-button" href="mailto:{{ $contactEmail }}">Hubungi Kami</a>
                @endif
            </div>
        </div>

        <nav class="contact-social-links" aria-label="Media sosial PDUG">
            @if ($whatsappNumber)
                <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" rel="noopener" aria-label="WhatsApp PDUG"><svg viewBox="0 0 24 24"><path d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.4-4A8 8 0 1 1 20 11.5Z"></path><path d="M9 8.5c.4 2.4 2.1 4.1 4.5 4.8l1.2-1.1 1.8.8"></path></svg></a>
            @endif
            <a href="mailto:{{ $contactEmail }}" aria-label="Email PDUG"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m4 7 8 6 8-6"></path></svg></a>
            <a href="https://www.instagram.com/pdgunadarma_official?igsh=bzY3cDQ4bXVzcXVq" target="_blank" rel="noopener" aria-label="Instagram PDUG"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r="1"></circle></svg></a>
            <a href="https://youtube.com/@pdgunadarma?si=VC8uLeqAqK_C-8a4" target="_blank" rel="noopener" aria-label="YouTube PDUG"><svg viewBox="0 0 24 24"><path d="M21 12c0 3-.4 5-1 5.6-.7.7-3.2 1-8 1s-7.3-.3-8-1C3.4 17 3 15 3 12s.4-5 1-5.6c.7-.7 3.2-1 8-1s7.3.3 8 1c.6.6 1 2.6 1 5.6Z"></path><path d="m10 9 5 3-5 3V9Z"></path></svg></a>
            <a href="https://vt.tiktok.com/ZSC45vqwj/" target="_blank" rel="noopener" aria-label="TikTok PDUG"><svg viewBox="0 0 24 24"><path d="M14 4v10.5a4.5 4.5 0 1 1-3-4.2"></path><path d="M14 4c.5 2.5 2 4 4.5 4.5"></path></svg></a>
            @if ($contactAddress)
                <a href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($contactAddress) }}" target="_blank" rel="noopener" aria-label="Lokasi PDUG"><svg viewBox="0 0 24 24"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg></a>
            @endif
        </nav>
    </section>
@endsection
