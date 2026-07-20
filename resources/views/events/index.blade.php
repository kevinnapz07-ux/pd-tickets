@extends('layouts.app', ['title' => 'Beranda'])

@section('content')
    @php
        $heroSubtitle = trim((string) ($siteSetting?->hero_subtitle ?? ''));
        if ($heroSubtitle === '' || str_starts_with($heroSubtitle, 'Temukan seminar, workshop, dan kegiatan akademik PD Gunadarma.')) {
            $heroSubtitle = 'UKM Kerohanian Universitas Gunadarma';
        }
    @endphp

    @auth
        <p class="homepage-welcome">Selamat datang kembali, <strong>{{ auth()->user()->name }}</strong></p>
    @endauth

    <section
        class="hero {{ $siteSetting?->hero_image ? 'has-background-image' : '' }}"
        @if ($siteSetting?->hero_image)
            style="background-image: url('{{ asset('storage/'.$siteSetting->hero_image) }}');"
        @endif
    >
        <div class="hero-copy">
            <h1>{{ $siteSetting?->hero_title ?? 'PDUG' }}</h1>
            <p>{{ $heroSubtitle }}</p>
        </div>
    </section>

    <section class="section event-catalog-section" id="event-tersedia">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Pendaftaran Dibuka</p>
                <h2>Event Tersedia</h2>
            </div>
            <form class="search-form" method="GET" action="{{ route('events.index') }}">
                <input name="search" value="{{ $search }}" placeholder="Cari event...">
                <button class="button" type="submit">Cari</button>
                @if ($search !== '')
                    <a class="link-button" href="{{ route('events.index') }}">Reset</a>
                @endif
            </form>
        </div>

        <div class="event-grid public-event-rail{{ $events->count() === 1 ? ' is-single' : '' }}" aria-label="Daftar event tersedia">
            @forelse ($events as $event)
                @php
                    $eventTitle = trim(strip_tags((string) $event->title));
                    $eventDescription = preg_replace('/^[\s\'",]+/u', '', trim(strip_tags((string) $event->description)));
                    $compactPrice = 'Gratis';

                    if ($event->price > 0) {
                        if ($event->price >= 1000000) {
                            $compactPrice = rtrim(rtrim(number_format($event->price / 1000000, 2, '.', ''), '0'), '.').'M';
                        } else {
                            $compactPrice = number_format($event->price / 1000, 0, '.', '').'K';
                        }
                    }
                @endphp
                <article class="event-card public-event-card" data-reveal>
                    <div class="event-card-visual" aria-hidden="true">
                        @if ($event->image_url)
                            <img src="{{ $event->image_url }}" alt="" loading="lazy" decoding="async" data-image-fallback="{{ asset('images/event-placeholder.svg') }}">
                        @else
                            <img src="{{ asset('images/event-placeholder.svg') }}" alt="" loading="lazy">
                        @endif
                        <div class="event-date">
                            <strong>{{ $event->starts_at->format('d') }}</strong>
                            <span>{{ $event->starts_at->translatedFormat('M') }}</span>
                        </div>
                    </div>
                    <div class="event-card-content">
                        <h3>{{ $eventTitle }}</h3>
                        <p class="event-description event-description-preview">{{ Str::limit($eventDescription, 170) }}</p>
                        <dl class="event-card-meta-list">
                            <div>
                                <dt>Lokasi</dt>
                                <dd>{{ $event->location }}</dd>
                            </div>
                            <div>
                                <dt>Tanggal</dt>
                                <dd>{{ $event->starts_at->translatedFormat('d M Y') }}, {{ $event->starts_at->format('H:i') }} WIB</dd>
                            </div>
                            <div>
                                <dt>Kuota</dt>
                                <dd>{{ $event->paid_registrations_count }}/{{ $event->quota }} peserta</dd>
                            </div>
                            <div>
                                <dt>HTM</dt>
                                <dd>{{ $compactPrice }}</dd>
                            </div>
                        </dl>
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
                <article class="upcoming-card upcoming-card-simple" data-reveal>
                    <div class="upcoming-poster">
                        @if ($upcomingEvent->image_url)
                            <img
                                src="{{ $upcomingEvent->image_url }}"
                                alt="Poster {{ $upcomingEvent->title }}"
                                loading="lazy"
                                decoding="async"
                                data-image-fallback="{{ asset('images/event-placeholder.svg') }}"
                            >
                        @else
                            <img
                                src="{{ asset('images/event-placeholder.svg') }}"
                                alt=""
                                loading="lazy"
                            >
                        @endif
                    </div>

                    <div
                        class="upcoming-content upcoming-simple-content">
                            <p class="upcoming-registration-status">
                                Pendaftaran Segera Dibuka
                            </p>

                            <h3>{{ $upcomingEvent->title }}</h3>

                            <p class="event-description">
                                {{ $upcomingEvent->description }}
                            </p>

                            <button
                                type="button"
                                class="upcoming-read-more"
                                aria-expanded="false"
                            >
                                <span class="upcoming-read-more-label">
                                    Baca selengkapnya
                                </span>

                                <span class="upcoming-read-more-icon" aria-hidden="true">
                                    ↓
                                </span>
                            </button>
                        </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
@endsection