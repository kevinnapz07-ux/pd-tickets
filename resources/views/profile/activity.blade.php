@extends('layouts.app', ['title' => 'Tiket Saya'])

@section('content')
    <section class="profile-info participant-history">
        <div>
            <p class="eyebrow">Aktivitas</p>
            <h1>Event yang Diikuti{{ $registrations->isEmpty() ? ' : -' : '' }}</h1>
        </div>

        @if ($errors->has('registration'))
            <div class="error-box">{{ $errors->first('registration') }}</div>
        @endif

        @if ($registrations->isNotEmpty())
            <div class="managed-event-list">
                @foreach ($registrations as $registration)
                    @php
                        $event = $registration->event;
                        $isPaidEvent = $event->price > 0;
                        $whatsappMessage = rawurlencode(
                            'Halo Pengurus PDUG, saya '.$registration->name.
                            ' ingin konfirmasi registrasi event '.$event->title.
                            ' dengan kode '.$registration->registration_code.'.'
                        );
                    @endphp

                    <article class="managed-event-card">
                        <div class="managed-event-date">
                            <strong>{{ $event->starts_at->translatedFormat('d') }}</strong>
                            <span>{{ $event->starts_at->translatedFormat('M') }}</span>
                        </div>
                        <div class="managed-event-main">
                            <div class="managed-event-heading">
                                <div>
                                    <h3>{{ $event->title }}</h3>
                                    <p>{{ $event->location }} · {{ $event->starts_at->translatedFormat('H:i') }} WIB</p>
                                </div>
                                <span class="status status-{{ $registration->registration_status ?? 'pending' }}">{{ $registration->registrationStatusLabel() }}</span>
                            </div>
                            <dl class="managed-event-meta">
                                <div><dt>Kode</dt><dd>{{ $registration->registration_code }}</dd></div>
                                <div><dt>Email Registrasi</dt><dd>{{ $registration->email }}</dd></div>
                                <div class="fee-meta"><dt class="sr-only">Biaya</dt><dd>{{ $isPaidEvent ? 'Rp '.number_format($event->price, 0, ',', '.') : 'Gratis' }}</dd></div>
                                <div><dt>Kontak</dt><dd>{{ $registration->phone }}</dd></div>
                            </dl>
                            <div class="managed-event-actions">
                                <a class="link-button" href="{{ route('registrations.show', $registration) }}">Lihat Detail</a>

                                @if ($isPaidEvent)
                                    <a class="button whatsapp-button" href="https://wa.me/628123456789?text={{ $whatsappMessage }}" target="_blank" rel="noopener">Konfirmasi ke Pengurus</a>
                                @else
                                    <form method="POST" action="{{ route('participant.registrations.cancel', $registration) }}" onsubmit="return confirm('Batalkan registrasi event gratis ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="danger-button" type="submit">Cancel Event</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
