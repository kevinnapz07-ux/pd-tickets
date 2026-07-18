@extends('layouts.app', ['title' => isset($ticketsOnly) ? 'Tiket Saya' : 'Registrasi Saya'])

@section('content')
    <section class="my-registrations {{ isset($ticketsOnly) ? 'ticket-list-page' : '' }}">
        <div class="section-heading">
            <div>
                @unless (isset($ticketsOnly))
                    <p class="eyebrow">Akun</p>
                @endunless
                <h1>{{ isset($ticketsOnly) ? 'Tiket Saya' : 'Registrasi Saya' }}</h1>
                <p>{{ isset($ticketsOnly) ? 'Semua tiket event Anda tersedia di sini. Tampilkan QR Code saat akan melakukan check-in.' : 'Pantau pendaftaran dan lanjutkan pembayaran dari satu tempat.' }}</p>
            </div>
        </div>
        @if ($registrations->isEmpty())
            <div class="registration-empty">
                <h2>{{ isset($ticketsOnly) ? 'Belum Ada Tiket' : 'Belum Ada Registrasi' }}</h2>
                <p>{{ isset($ticketsOnly) ? 'Tiket akan muncul setelah registrasi dan pembayaran berhasil.' : 'Kamu belum mendaftar event apa pun.' }}</p>
                <a class="button" href="{{ route('events.index') }}">Lihat Event</a>
            </div>
        @else
            <div class="registration-card-grid">
                @foreach ($registrations as $registration)
                    @php($hasCheckedIn = $registration->checked_in_at !== null || $registration->registration_status === 'checked_in')
                    <article class="registration-card">
                        <div class="registration-card-heading">
                            <div><p class="eyebrow">{{ $registration->registration_code }}</p><h2>{{ $registration->event->title }}</h2></div>
                            @if (isset($ticketsOnly))
                                @php($ticketReady = $registration->isCheckInReady())
                                <span class="ticket-checkin-status {{ $hasCheckedIn ? 'is-checked-in' : ($ticketReady ? 'is-waiting' : 'is-pending') }}">
                                    {{ $hasCheckedIn ? 'Sudah Check-in' : ($ticketReady ? 'Siap Check-in' : $registration->transactionStatusLabel()) }}
                                </span>
                            @else
                                <span class="status status-{{ $registration->payment_status }}">{{ $registration->transactionStatusLabel() }}</span>
                            @endif
                        </div>
                        <dl class="registration-card-meta">
                            <div><dt>Tanggal dan Waktu</dt><dd>{{ $registration->event->starts_at->translatedFormat('d M Y, H:i') }} WIB</dd></div>
                            <div><dt>Lokasi</dt><dd>{{ $registration->event->location }}</dd></div>
                            <div><dt>Nominal</dt><dd>{{ $registration->event->price > 0 ? 'Rp '.number_format($registration->event->price, 0, ',', '.') : 'Gratis' }}</dd></div>
                            @unless (isset($ticketsOnly))
                                <div><dt>Pendaftaran</dt><dd>{{ $registration->registrationStatusLabel() }}</dd></div>
                                <div><dt>Terdaftar</dt><dd>{{ $registration->created_at->translatedFormat('d M Y, H:i') }}</dd></div>
                            @endunless
                            @if (isset($ticketsOnly) && $hasCheckedIn && $registration->checked_in_at)
                                <div><dt>Waktu Check-in</dt><dd>{{ $registration->checked_in_at->translatedFormat('d M Y, H:i') }} WIB</dd></div>
                            @endif
                        </dl>
                        <div class="registration-card-actions">
                            @if (isset($ticketsOnly) && $ticketReady && ! $hasCheckedIn)
                                <button class="button" type="button" data-ticket-modal-open="ticket-qr-modal-{{ $registration->id }}" aria-controls="ticket-qr-modal-{{ $registration->id }}">Tampilkan QR</button>
                            @elseif (isset($ticketsOnly) && $hasCheckedIn)
                                <button class="link-button ticket-status-button" type="button" data-ticket-modal-open="ticket-status-modal-{{ $registration->id }}" aria-controls="ticket-status-modal-{{ $registration->id }}">Lihat Status</button>
                            @elseif (isset($ticketsOnly) && $registration->payment_status === 'pending')
                                <a class="button" href="{{ route('registrations.show', $registration) }}">Lanjutkan Pembayaran</a>
                            @elseif (isset($ticketsOnly) && in_array($registration->payment_status, ['expired', 'failed', 'cancelled'], true))
                                <form method="POST" action="{{ route('registrations.payment.retry', $registration) }}" data-disable-submit>
                                    @csrf
                                    <button class="button" type="submit">{{ $registration->payment_status === 'expired' ? 'Buat Pembayaran Baru' : 'Bayar Lagi' }}</button>
                                </form>
                            @elseif ($registration->isCheckInReady())
                                <a class="button" href="{{ route('registrations.show', $registration) }}">Lihat Tiket</a>
                            @elseif ($registration->payment_status === 'pending')
                                <a class="button" href="{{ route('registrations.show', $registration) }}">Lanjutkan Pembayaran</a>
                                <form method="POST" action="{{ route('registrations.payment.status', $registration) }}">@csrf<button class="link-button" type="submit">Cek Status</button></form>
                            @elseif (in_array($registration->payment_status, ['expired', 'failed', 'cancelled'], true))
                                <form method="POST" action="{{ route('registrations.payment.retry', $registration) }}" data-disable-submit>
                                    @csrf
                                    <button class="button" type="submit">{{ $registration->payment_status === 'expired' ? 'Buat Pembayaran Baru' : 'Bayar Lagi' }}</button>
                                </form>
                            @else
                                <a class="link-button" href="{{ route('registrations.show', $registration) }}">Lihat Detail Registrasi</a>
                            @endif
                            @if (isset($ticketsOnly))
                                <a class="link-button ticket-detail-link" href="{{ route('registrations.show', $registration) }}">Lihat Detail</a>
                            @endif
                        </div>
                    </article>

                    @if (isset($ticketsOnly) && $ticketReady && ! $hasCheckedIn)
                        <div class="ticket-modal-backdrop" id="ticket-qr-modal-{{ $registration->id }}" data-ticket-modal aria-hidden="true">
                            <section class="ticket-modal" role="dialog" aria-modal="true" aria-labelledby="ticket-qr-title-{{ $registration->id }}" tabindex="-1">
                                <button class="ticket-modal-close" type="button" data-ticket-modal-close aria-label="Tutup modal">×</button>
                                <p class="eyebrow">Tiket {{ $registration->registration_code }}</p>
                                <h2 id="ticket-qr-title-{{ $registration->id }}">QR Check-in</h2>
                                <p class="ticket-modal-event">{{ $registration->event->title }}</p>
                                <code class="ticket-modal-code">{{ $registration->registration_code }}</code>
                                <img class="ticket-modal-qr" src="{{ $registration->qrCodeDataUri() }}" alt="QR Check-in {{ $registration->registration_code }}">
                                <p class="ticket-modal-note">Tunjukkan QR ini kepada panitia saat hadir.</p>
                                <button class="button ticket-modal-dismiss" type="button" data-ticket-modal-close>Tutup</button>
                            </section>
                        </div>
                    @elseif (isset($ticketsOnly) && $hasCheckedIn)
                        <div class="ticket-modal-backdrop" id="ticket-status-modal-{{ $registration->id }}" data-ticket-modal aria-hidden="true">
                            <section class="ticket-modal ticket-status-modal" role="dialog" aria-modal="true" aria-labelledby="ticket-status-title-{{ $registration->id }}" tabindex="-1">
                                <button class="ticket-modal-close" type="button" data-ticket-modal-close aria-label="Tutup modal">×</button>
                                <div class="ticket-modal-success-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"></path></svg>
                                </div>
                                <h2 id="ticket-status-title-{{ $registration->id }}">Check-in Berhasil</h2>
                                <p class="ticket-modal-note">Tiket ini sudah digunakan.</p>
                                <dl class="ticket-modal-details">
                                    <div><dt>Event</dt><dd>{{ $registration->event->title }}</dd></div>
                                    <div><dt>Kode Tiket</dt><dd>{{ $registration->registration_code }}</dd></div>
                                    <div><dt>Waktu Check-in</dt><dd>{{ $registration->checked_in_at?->translatedFormat('d M Y, H:i') ?? '-' }}{{ $registration->checked_in_at ? ' WIB' : '' }}</dd></div>
                                </dl>
                                <button class="button ticket-modal-dismiss" type="button" data-ticket-modal-close>Tutup</button>
                            </section>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </section>
@endsection
