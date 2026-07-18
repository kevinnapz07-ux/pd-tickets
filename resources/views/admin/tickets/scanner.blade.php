@extends('layouts.app', ['title' => 'Scanner Tiket'])

@section('content')
    <section class="ticket-scanner-page">
        <div class="ticket-scanner-heading">
            <h1>Scanner QR Tiket</h1>
        </div>

        @if ($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
        @if (session('status'))<div class="success-box">{{ session('status') }}</div>@endif

        <div class="ticket-scanner-grid">
            <div class="ticket-scanner-stage">
                <div id="ticket-qr-reader" data-ticket-scanner></div>
                <div class="ticket-scanner-loading" data-ticket-scanner-loading hidden>
                    <span class="ticket-scanner-spinner" aria-hidden="true"></span>
                    <strong>Memverifikasi tiket...</strong>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.tickets.checkin') }}" data-ticket-checkin-form>
                @csrf
                <label>Kode Tiket Manual
                    <input name="ticket_reference" value="{{ old('ticket_reference') }}" placeholder="IRJ-A8F29K" required data-ticket-reference>
                </label>
                <button class="button button-full" type="submit" data-ticket-submit>Check-in</button>
            </form>
        </div>

        <div class="ticket-scanner-result-backdrop" data-ticket-result hidden>
            <section class="ticket-scanner-result" role="dialog" aria-modal="true" aria-labelledby="ticket-result-title">
                <div class="ticket-scanner-result-icon" data-ticket-result-icon aria-hidden="true"></div>
                <h2 id="ticket-result-title" data-ticket-result-title></h2>
                <p data-ticket-result-message></p>
                <dl data-ticket-result-details hidden>
                    <div><dt>Nama Peserta</dt><dd data-ticket-participant></dd></div>
                    <div><dt>Nama Event</dt><dd data-ticket-event></dd></div>
                    <div><dt>Kode Tiket</dt><dd data-ticket-code></dd></div>
                    <div><dt>Waktu Check-in</dt><dd data-ticket-time></dd></div>
                </dl>
                <button class="button button-full" type="button" data-ticket-scan-again>Scan Lagi</button>
            </section>
        </div>
    </section>
@endsection
