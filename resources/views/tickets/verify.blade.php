@extends('layouts.app', ['title' => $registration ? 'Tiket Event' : 'Tiket Tidak Valid'])

@section('content')
    @php
        $presentation = match ($ticketStatus) {
            'valid' => ['Tiket Siap Digunakan', 'Tiket terdaftar pada sistem dan siap digunakan untuk check-in.', 'valid', 'ticket'],
            'pending' => ['Menunggu Pembayaran', 'Tiket belum aktif karena pembayaran atau registrasi belum selesai.', 'pending', 'pending'],
            'payment_failed' => ['Pembayaran Gagal', 'Tiket belum aktif karena pembayaran tidak berhasil.', 'invalid', 'invalid'],
            'payment_expired' => ['Pembayaran Kedaluwarsa', 'Batas waktu pembayaran tiket telah berakhir.', 'invalid', 'invalid'],
            'payment_cancelled' => ['Pembayaran Dibatalkan', 'Pembayaran tiket telah dibatalkan dan tiket belum aktif.', 'cancelled', 'cancelled'],
            'refunded' => ['Pembayaran Dikembalikan', 'Pembayaran telah dikembalikan sehingga tiket tidak dapat digunakan.', 'cancelled', 'cancelled'],
            'used' => ['Tiket Sudah Digunakan', 'Tiket ini sudah tercatat melakukan check-in.', 'used', 'check'],
            'cancelled' => ['Tiket Dibatalkan', 'Tiket ini telah dibatalkan dan tidak dapat digunakan.', 'cancelled', 'cancelled'],
            default => ['Tiket Tidak Valid', 'Token tidak ditemukan atau QR Code tidak dikenali.', 'invalid', 'invalid'],
        };
    @endphp
    <section class="ticket-verification ticket-verification-{{ $presentation[2] }}">
        <div class="ticket-verification-card">
            <div class="ticket-status-icon" aria-hidden="true">
                @if ($presentation[3] === 'ticket')
                    <svg data-ticket-ready-icon viewBox="0 0 24 24"><path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a3 3 0 0 0 0 6v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a3 3 0 0 0 0-6V7Z"></path><path d="M13 5v2m0 3v4m0 3v2"></path></svg>
                @elseif ($presentation[3] === 'check')
                    <svg data-ticket-checked-icon viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"></path></svg>
                @elseif ($presentation[3] === 'pending')
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                @elseif ($presentation[3] === 'cancelled')
                    <svg viewBox="0 0 24 24"><path d="m7 7 10 10M17 7 7 17"></path></svg>
                @else
                    <span>!</span>
                @endif
            </div>
            <p class="eyebrow">PD Gunadarma · Verifikasi Tiket</p>
            <h1>{{ $presentation[0] }}</h1>
            <p>{{ $presentation[1] }}</p>

            @if ($registration)
                <dl class="ticket-public-details">
                    <div><dt>Kode Tiket</dt><dd>{{ $registration->registration_code }}</dd></div>
                    <div><dt>Nama Peserta</dt><dd>{{ $registration->name }}</dd></div>
                    <div><dt>Event</dt><dd>{{ $registration->event->title }}</dd></div>
                    <div><dt>Tanggal</dt><dd>{{ $registration->event->starts_at->translatedFormat('l, d F Y') }}</dd></div>
                    <div><dt>Waktu</dt><dd>{{ $registration->event->starts_at->format('H:i') }}{{ $registration->event->ends_at ? ' - '.$registration->event->ends_at->format('H:i') : '' }} WIB</dd></div>
                    <div><dt>Lokasi</dt><dd>{{ $registration->event->location }}</dd></div>
                    <div><dt>Kategori</dt><dd>{{ Str::headline(str_replace('_', ' ', $registration->participant_type)) }}</dd></div>
                    <div><dt>Status Pembayaran</dt><dd>{{ $registration->transactionStatusLabel() }}</dd></div>
                    @if ($ticketStatus === 'used')
                        <div><dt>Status Check-in</dt><dd>Sudah check-in</dd></div>
                    @endif
                    @if ($ticketStatus === 'used' && $registration->checked_in_at)
                        <div><dt>Waktu Check-in</dt><dd>{{ $registration->checked_in_at->translatedFormat('d F Y, H:i') }} WIB</dd></div>
                    @endif
                </dl>
            @endif
        </div>
    </section>
@endsection
