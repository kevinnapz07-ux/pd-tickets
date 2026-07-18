@extends('layouts.app', ['title' => isset($ticketsOnly) ? 'Tiket Saya' : 'Registrasi Saya'])

@section('content')
    <section class="my-registrations">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Akun Peserta</p>
                <h1>{{ isset($ticketsOnly) ? 'Tiket Saya' : 'Registrasi Saya' }}</h1>
                <p>{{ isset($ticketsOnly) ? 'Tiket aktif yang siap digunakan.' : 'Pantau pendaftaran dan lanjutkan pembayaran dari satu tempat.' }}</p>
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
                    <article class="registration-card">
                        <div class="registration-card-heading">
                            <div><p class="eyebrow">{{ $registration->registration_code }}</p><h2>{{ $registration->event->title }}</h2></div>
                            <span class="status status-{{ $registration->payment_status }}">{{ $registration->transactionStatusLabel() }}</span>
                        </div>
                        <dl class="registration-card-meta">
                            <div><dt>Tanggal</dt><dd>{{ $registration->event->starts_at->translatedFormat('d M Y, H:i') }} WIB</dd></div>
                            <div><dt>Lokasi</dt><dd>{{ $registration->event->location }}</dd></div>
                            <div><dt>Pendaftaran</dt><dd>{{ $registration->registrationStatusLabel() }}</dd></div>
                            <div><dt>Nominal</dt><dd>{{ $registration->event->price > 0 ? 'Rp '.number_format($registration->event->price, 0, ',', '.') : 'Gratis' }}</dd></div>
                            <div><dt>Terdaftar</dt><dd>{{ $registration->created_at->translatedFormat('d M Y, H:i') }}</dd></div>
                        </dl>
                        <div class="registration-card-actions">
                            @if ($registration->isCheckInReady())
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
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
