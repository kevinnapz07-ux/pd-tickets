@extends('layouts.app', ['title' => 'Beranda'])

@section('content')
    @auth
        <div class="homepage-welcome">Selamat datang, {{ auth()->user()->name }}</div>
    @endauth

    <section class="hero">
        <div class="hero-copy">
            <h1>{{ $siteSetting?->hero_title ?? 'PDUG' }}</h1>
            <p>{{ $siteSetting?->hero_subtitle ?? 'Temukan seminar, workshop, dan kegiatan akademik PD Gunadarma. Registrasi dan pembayaran diproses dalam satu alur yang mudah dilacak.' }}</p>
        </div>
    </section>

    <section class="section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Pendaftaran Dibuka</p>
                <h2>Event Tersedia</h2>
            </div>
            <form class="search-form" method="GET" action="{{ route('events.index') }}">
                <input name="search" value="{{ $search }}" placeholder="Cari event, lokasi, pembicara">
                <button class="button" type="submit">Search</button>
                @if ($search !== '')
                    <a class="link-button" href="{{ route('events.index') }}">Reset</a>
                @endif
            </form>
        </div>

        <div class="event-grid public-event-rail" aria-label="Daftar event tersedia">
            @forelse ($events as $event)
                <article class="event-card public-event-card" data-reveal>
                    <div class="event-card-visual" aria-hidden="true">
                        @if ($event->image_url)
                            <img src="{{ $event->image_url }}" alt="" loading="lazy">
                        @else
                            <img src="{{ asset('images/event-placeholder.svg') }}" alt="" loading="lazy">
                        @endif
                        <div class="event-date">
                            <strong>{{ $event->starts_at->format('d') }}</strong>
                            <span>{{ $event->starts_at->translatedFormat('M') }}</span>
                        </div>
                    </div>
                    <div class="event-card-content">
                        <p class="event-meta">{{ $event->location }}</p>
                        <h3>{{ $event->title }}</h3>
                        <p class="event-description event-description-preview">{{ Str::limit($event->description, 180) }}</p>
                        <div class="event-footer">
                            <span>{{ $event->paid_registrations_count }}/{{ $event->quota }} orang</span>
                            <span>{{ $event->price > 0 ? 'Rp '.number_format($event->price, 0, ',', '.') : 'Gratis' }}</span>
                        </div>
                        <a class="button" href="{{ route('events.show', ['event' => $event->slug ?: $event->id]) }}">Lihat Detail</a>
                    </div>
                </article>
            @empty
                <p class="empty">Belum ada event yang dipublikasikan.</p>
            @endforelse
        </div>
    </section>

    @if ($upcomingEvents->isNotEmpty())
        <section class="upcoming-section">
            <div class="section-heading">
                <div>
                    <h2>Event Mendatang</h2>
                </div>
            </div>

            <div class="upcoming-list">
                @foreach ($upcomingEvents as $upcomingEvent)
                    <article class="upcoming-card" data-reveal>
                        <div class="upcoming-poster">
                            @if ($upcomingEvent->image_url)
                                <img src="{{ $upcomingEvent->image_url }}" alt="Poster {{ $upcomingEvent->title }}" loading="lazy">
                            @else
                                <img src="{{ asset('images/event-placeholder.svg') }}" alt="" loading="lazy">
                            @endif
                        </div>
                        <div class="upcoming-date">
                            <strong>{{ $upcomingEvent->starts_at->format('d') }}</strong>
                            <span>{{ $upcomingEvent->starts_at->translatedFormat('M Y') }}</span>
                        </div>
                        <div class="upcoming-content">
                            <p class="event-meta">{{ $upcomingEvent->location }} • {{ $upcomingEvent->starts_at->format('H:i') }} WIB</p>
                            <h3>{{ $upcomingEvent->title }}</h3>
                            <p class="event-description">{{ $upcomingEvent->description }}</p>
                            <div class="event-footer">
                                <span>Kuota {{ $upcomingEvent->quota }} kursi</span>
                                <span>{{ $upcomingEvent->price > 0 ? 'Rp '.number_format($upcomingEvent->price, 0, ',', '.') : 'Gratis' }}</span>
                            </div>
                        </div>
                        <span class="status-pill">Pendaftaran Segera Dibuka</span>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endsection
