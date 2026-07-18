<!DOCTYPE html>
<html lang="id">
<head>
    @php
        $brandTitle = filled($siteSetting?->site_name) ? $siteSetting->site_name : config('branding.title');
        $brandSubtitle = filled($siteSetting?->site_tagline) ? $siteSetting->site_tagline : config('branding.subtitle');
        $brandSubtitle = str_replace('Universitas gunadarma', 'Universitas Gunadarma', $brandSubtitle);
        $pageTitle = $title ?? $brandTitle;
        $browserTitle = $pageTitle === $brandTitle ? $brandTitle : $pageTitle.' • '.$brandTitle;
        $routeDescription = collect(config('branding.pages', []))
            ->get(request()->route()?->getName(), [])['description'] ?? null;
        $pageDescription = $metaDescription
            ?? (request()->routeIs('events.show') && isset($event) ? Str::limit(strip_tags($event->description), 155) : null)
            ?? $routeDescription
            ?? $brandSubtitle;
        $isSensitivePage = request()->routeIs('tickets.verify', 'password.reset');
        $canonicalUrl = $isSensitivePage ? null : url()->current();
        $pageImage = $metaImage
            ?? (isset($event) && $event->image_url ? $event->image_url : null)
            ?? (isset($article) && $article->thumbnail_url ? $article->thumbnail_url : null)
            ?? asset(config('branding.logo'));
        $openGraphType = isset($article) ? 'article' : 'website';
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $browserTitle }}</title>
    <meta name="application-name" content="{{ $brandTitle }}">
    <meta name="description" content="{{ $pageDescription }}">
    @if ($canonicalUrl)
        <link rel="canonical" href="{{ $canonicalUrl }}">
    @else
        <meta name="robots" content="noindex, nofollow, noarchive">
    @endif
    <meta property="og:locale" content="id_ID">
    <meta property="og:type" content="{{ $openGraphType }}">
    <meta property="og:site_name" content="{{ $brandTitle }}">
    <meta property="og:title" content="{{ $browserTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    @if ($canonicalUrl)
        <meta property="og:url" content="{{ $canonicalUrl }}">
    @endif
    <meta property="og:image" content="{{ $pageImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $browserTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $pageImage }}">
    <meta name="how-to-order-title" content="Cara Registrasi • {{ $brandTitle }}">
    <meta name="how-to-order-description" content="Panduan langkah demi langkah untuk memilih event, melakukan registrasi, dan menyelesaikan pembayaran.">
    <link rel="icon" type="image/svg+xml" href="{{ asset(config('branding.favicon')) }}">
    <script>
        (() => {
            const mode = localStorage.getItem('pdug-theme-mode') || 'system';
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = mode === 'system' ? (prefersDark ? 'dark' : 'light') : mode;
            document.documentElement.dataset.themeMode = mode;
            document.documentElement.dataset.theme = theme;
            document.documentElement.style.colorScheme = theme;
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="{{ request()->routeIs('admin.*') ? 'legacy-admin' : 'public-site' }}">
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>
    <header class="topbar">
        <div class="topbar-shell">
        <a class="brand" href="{{ route('events.index') }}">
            <img class="brand-mark brand-logo" src="{{ asset(config('branding.logo')) }}" alt="Logo {{ $brandTitle }}">
            <span>
                <strong>{{ $brandTitle }}</strong>
                <small>{{ $brandSubtitle }}</small>
            </span>
        </a>
        <button class="mobile-menu-toggle" type="button" data-mobile-menu-toggle aria-expanded="false" aria-controls="primary-navigation" aria-label="Buka menu navigasi">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path class="menu-icon-open" d="M4 7h16M4 12h16M4 17h16"></path>
                <path class="menu-icon-close" d="m6 6 12 12M18 6 6 18"></path>
            </svg>
        </button>
        <div class="topbar-right" id="primary-navigation" data-mobile-menu>
            <nav class="main-nav" aria-label="Navigasi utama">
                <a class="mobile-home-link{{ request()->routeIs('events.index') ? ' is-active' : '' }}" href="{{ route('events.index') }}" @if (request()->routeIs('events.index')) aria-current="page" @endif>Home</a>
                <a class="desktop-event-link{{ request()->routeIs('events.index', 'events.show') ? ' is-active' : '' }}" href="{{ route('events.index') }}" @if (request()->routeIs('events.index', 'events.show')) aria-current="page" @endif>Event</a>
                <button class="nav-button" type="button" data-how-open>Cara Daftar</button>
                <a class="{{ request()->routeIs('profile.pdug', 'articles.*') ? 'is-active' : '' }}" href="{{ route('profile.pdug') }}" @if (request()->routeIs('profile.pdug', 'articles.*')) aria-current="page" @endif>Tentang PDUG</a>
            </nav>

            <div class="header-actions">
                @auth
                    @if (auth()->user()->role === 'admin')
                        <a class="nav-button" href="{{ route('filament.admin.pages.dashboard') }}">Dashboard</a>
                    @endif
                    <div class="account-menu" data-account-menu>
                        <button class="nav-button account-trigger authenticated-account-trigger" type="button" data-account-toggle aria-expanded="false" aria-haspopup="true" aria-label="Buka menu akun {{ auth()->user()->name }}">
                            <span class="header-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim(auth()->user()->name), 0, 1)) ?: '?' }}</span>
                            <span class="account-trigger-label">
                                <span>{{ auth()->user()->name }}</span>
                            </span>
                            <svg class="account-trigger-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="account-popover" data-account-popover>
                            <a href="{{ route('tickets.index') }}">Tiket Saya</a>
                            <a href="{{ route('participant.profile') }}">Profil</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit">Logout</button>
                            </form>
                            @include('layouts.partials.theme-switcher')
                        </div>
                    </div>
                @else
                    <div class="account-menu guest-account-menu" data-account-menu>
                        <button class="nav-button account-trigger login-menu-trigger{{ request()->routeIs('login', 'register') ? ' is-active' : '' }}" type="button" data-account-toggle aria-expanded="false" aria-haspopup="true">
                            <span class="account-trigger-label">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"></path></svg>
                                <span>Login</span>
                            </span>
                            <svg class="account-trigger-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                        </button>
                        <div class="account-popover" data-account-popover>
                            <a href="{{ route('login') }}">Login</a>
                            @include('layouts.partials.theme-switcher')
                        </div>
                    </div>
                @endauth

            </div>
        </div>
        </div>
    </header>

    <div class="modal-backdrop" data-how-modal aria-hidden="true">
        <section class="how-modal" role="dialog" aria-modal="true" aria-labelledby="how-modal-title" aria-describedby="how-modal-description" tabindex="-1">
            <div class="modal-heading">
                <div>
                    <p class="eyebrow">Cara Registrasi</p>
                    <h2 id="how-modal-title">Cara Registrasi Event</h2>
                    <p class="modal-description" id="how-modal-description">Ikuti langkah berikut untuk mendaftar dan mendapatkan tiket event PD Gunadarma.</p>
                </div>
                <button class="modal-close" type="button" data-how-close aria-label="Tutup">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg>
                </button>
            </div>
            <div class="step-grid">
                <article>
                    <div class="step-heading"><span>1</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"></path></svg></div>
                    <h3>Masuk atau Daftar</h3>
                    <p>Masuk menggunakan akun Anda. Jika belum memiliki akun, lakukan pendaftaran terlebih dahulu.</p>
                </article>
                <article>
                    <div class="step-heading"><span>2</span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 11h18"></path></svg></div>
                    <h3>Pilih Event</h3>
                    <p>Pilih event yang masih membuka pendaftaran, lalu periksa jadwal, lokasi, kuota, dan biaya event.</p>
                </article>
                <article>
                    <div class="step-heading"><span>3</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6M8 13h8M8 17h6"></path></svg></div>
                    <h3>Isi Data Registrasi</h3>
                    <p>Lengkapi data pada formulir registrasi, lalu periksa kembali sebelum dikirim.</p>
                </article>
                <article>
                    <div class="step-heading"><span>4</span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"></rect><path d="M2 10h20M16 15h2"></path></svg></div>
                    <h3>Selesaikan Registrasi</h3>
                    <p>Untuk event berbayar, selesaikan pembayaran melalui Midtrans. Untuk event gratis, registrasi akan langsung dikonfirmasi.</p>
                </article>
                <article class="step-card-ticket">
                    <div class="step-heading"><span>5</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9a3 3 0 0 0 0 6v4h18v-4a3 3 0 0 0 0-6V5H3Z"></path><path d="M13 5v14"></path></svg></div>
                    <h3>Dapatkan Tiket</h3>
                    <p>Setelah registrasi berhasil, tiket beserta QR Code dapat dilihat melalui menu Tiket Saya.</p>
                </article>
            </div>
        </section>
    </div>

    <main id="main-content" tabindex="-1">
        @if (session('status') && ! request()->routeIs('password.request'))
            <div class="notice toast-notice" role="status" data-toast>{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>

    @php
        $configuredFooterEmail = $siteSetting?->contact_email;
        $footerEmail = filled($configuredFooterEmail) && ! str_ends_with(strtolower($configuredFooterEmail), '.test')
            ? $configuredFooterEmail
            : 'pdgunadarmates@gmail.com';
        $configuredFooterPhone = $siteSetting?->contact_phone;
        $footerPhoneNumber = \App\Models\SiteSetting::whatsappNumber($configuredFooterPhone) ?? '6281234567890';
        $footerPhone = \App\Models\SiteSetting::whatsappNumber($configuredFooterPhone)
            ? $configuredFooterPhone
            : '+62 812-3456-7890';
        $configuredFooterAddress = $siteSetting?->contact_address;
        $footerAddress = filled($configuredFooterAddress) && strtolower(trim($configuredFooterAddress)) !== 'kampus gunadarma'
            ? $configuredFooterAddress
            : 'Universitas Gunadarma, Depok';
    @endphp

    <footer class="site-footer">
        <div class="footer-top">
            <div class="footer-identity">
                <a class="footer-logo" href="{{ route('events.index') }}" aria-label="Beranda {{ $brandTitle }}">
                    <img class="brand-mark brand-logo" src="{{ asset(config('branding.logo')) }}" alt="">
                </a>
                <div class="footer-brand-content">
                    <strong>{{ $brandTitle }}</strong>
                    <p>{{ $brandSubtitle }}</p>
                    <div class="footer-contact" aria-label="Informasi kontak PDUG">
                        <div class="footer-contact-item">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg>
                            <div><span class="footer-contact-label">Email</span><a class="footer-contact-value" href="mailto:{{ $footerEmail }}">{{ $footerEmail }}</a></div>
                        </div>
                        <div class="footer-contact-item">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92Z"></path></svg>
                            <div><span class="footer-contact-label">WhatsApp</span><a class="footer-contact-value" href="https://wa.me/{{ $footerPhoneNumber }}" target="_blank" rel="noopener">{{ $footerPhone }}</a></div>
                        </div>
                        <div class="footer-contact-item">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <div><span class="footer-contact-label">Lokasi</span><span class="footer-contact-value">{{ $footerAddress }}</span></div>
                        </div>
                        <div class="footer-contact-item service-hours">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                            <div><span class="footer-contact-label">Jam Layanan</span><span class="footer-contact-value"><span>Senin-Jumat</span><span>09.00-17.00 WIB</span></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-links">
                <section>
                    <h2>Resources</h2>
                    <a href="https://www.gunadarma.ac.id">Gunadarma</a>
                    <a href="{{ route('events.index') }}">Event</a>
                    <a href="{{ route('profile.pdug') }}">About Us</a>
                    <a href="{{ route('articles.index') }}">Artikel</a>
                </section>
                <section>
                    <h2>Follow Us</h2>
                    <a href="https://www.instagram.com/pdgunadarma_official?igsh=bzY3cDQ4bXVzcXVq">Instagram</a>
                    <a href="https://youtube.com/@pdgunadarma?si=VC8uLeqAqK_C-8a4">Youtube</a>
                    <a href="https://vt.tiktok.com/ZSC45vqwj/">TikTok</a>
                </section>
            </div>
        </div>

        <div class="footer-bottom">
            <p><span>&copy; {{ now()->year }} PDUG&trade;.</span> <span>All Rights Reserved.</span></p>
        </div>
    </footer>
</body>
</html>
