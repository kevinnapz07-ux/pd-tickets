@extends('layouts.app', ['title' => 'Scanner Tiket'])

@section('content')
    <section class="ticket-scanner-page">
        <div class="ticket-scanner-heading">
            <p class="eyebrow">Administrasi Event</p>
            <h1>Scanner QR Tiket</h1>
            <p>Arahkan kamera belakang ke QR tiket atau masukkan kode tiket secara manual.</p>
        </div>

        @if ($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
        @if (session('status'))<div class="success-box">{{ session('status') }}</div>@endif

        <div class="ticket-scanner-grid">
            <div id="ticket-qr-reader" data-ticket-scanner data-check-in-url="{{ route('admin.tickets.checkin') }}"></div>
            <form method="POST" action="{{ route('admin.tickets.checkin') }}" data-ticket-checkin-form>
                @csrf
                <label>URL QR atau Kode Tiket
                    <input name="ticket_reference" value="{{ old('ticket_reference') }}" placeholder="PDUG-2026-A8F29K" required data-ticket-reference>
                </label>
                <button class="button button-full" type="submit">Check-in Peserta</button>
            </form>
        </div>
    </section>
@endsection
