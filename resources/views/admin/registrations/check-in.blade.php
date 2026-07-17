@extends('layouts.app', ['title' => 'QR Check-in'])

@section('content')
    <section class="dashboard">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>QR Check-in</h1>
            </div>
            <a class="link-button" href="{{ route('admin.registrations.index') }}">Kembali</a>
        </div>

        @include('admin.partials.nav')

        @if ($errors->has('registration'))
            <div class="error-box">{{ $errors->first('registration') }}</div>
        @endif

        <section class="receipt-card checkin-card">
            <div>
                <p class="eyebrow">Kode Registrasi</p>
                <h1>{{ $registration->registration_code }}</h1>
                <p>{{ $registration->name }} - {{ $registration->event->title }}</p>
            </div>

            <dl class="info-list">
                <div><dt>Email</dt><dd>{{ $registration->email }}</dd></div>
                <div><dt>No. HP</dt><dd>{{ $registration->phone }}</dd></div>
                <div><dt>Status Transaksi</dt><dd><span class="status status-{{ $registration->payment_status }}">{{ $registration->transactionStatusLabel() }}</span></dd></div>
                <div><dt>Status Pendaftaran</dt><dd><span class="status status-{{ $registration->registration_status ?? 'pending' }}">{{ $registration->registrationStatusLabel() }}</span></dd></div>
                <div><dt>Check-in</dt><dd>{{ $registration->checked_in_at?->translatedFormat('d F Y H:i') ?? '-' }}</dd></div>
            </dl>

            <form method="POST" action="{{ route('admin.registrations.checkin', $registration) }}">
                @csrf
                @method('PATCH')
                <button class="button" type="submit" @disabled(! $registration->isCheckInReady())>Catat Check-in</button>
            </form>
        </section>
    </section>
@endsection
