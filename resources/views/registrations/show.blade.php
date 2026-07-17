@extends('layouts.app', ['title' => $registration->event->price > 0 && $registration->payment_status !== 'paid' ? 'Pembayaran' : 'Tiket Saya'])

@push('head')
    @if ($registration->payment?->snap_token && config('services.midtrans.client_key'))
        <script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    @endif
@endpush

@section('content')
    <section class="receipt">
        <div class="receipt-card">
            <p class="eyebrow">Kode Registrasi</p>
            <h1>{{ $registration->registration_code }}</h1>
            <p>{{ $registration->name }} terdaftar untuk <strong>{{ $registration->event->title }}</strong>.</p>

            @if (session('payment_error'))
                <div class="error-box">{{ session('payment_error') }}</div>
            @endif

            <section class="registration-event-summary">
                <div>
                    <p class="eyebrow">Informasi Event</p>
                    <h2>{{ $registration->event->title }}</h2>
                    <p>{{ Str::limit($registration->event->description, 180) }}</p>
                </div>
                <dl class="event-summary-grid">
                    <div><dt>Tanggal</dt><dd>{{ $registration->event->starts_at->translatedFormat('l, d F Y') }}</dd></div>
                    <div><dt>Waktu</dt><dd>{{ $registration->event->starts_at->format('H:i') }}{{ $registration->event->ends_at ? ' - '.$registration->event->ends_at->format('H:i') : '' }} WIB</dd></div>
                    <div><dt>Lokasi</dt><dd>{{ $registration->event->location }}</dd></div>
                    <div><dt>Jenis Pendaftaran</dt><dd>{{ $registration->event->price > 0 ? 'Berbayar' : 'Gratis' }}</dd></div>
                </dl>
            </section>

            <dl class="info-list">
                @if ($registration->event->price > 0)
                    <div><dt>Status Transaksi</dt><dd><span class="status status-{{ $registration->payment_status }}">{{ $registration->transactionStatusLabel() }}</span></dd></div>
                @endif
                <div><dt>Status Pendaftaran</dt><dd><span class="status status-{{ $registration->registration_status ?? 'pending' }}">{{ $registration->registrationStatusLabel() }}</span></dd></div>
                <div><dt>Email</dt><dd>{{ $registration->email }}</dd></div>
                @php
                    $categoryLabel = collect($registration->event->registrationCategories())->firstWhere('key', $registration->participant_type)['label'] ?? Str::headline(str_replace('_', ' ', $registration->participant_type));
                @endphp
                <div><dt>Kategori</dt><dd>{{ $categoryLabel }}</dd></div>
                <div><dt>No. HP</dt><dd>{{ $registration->phone }}</dd></div>
                @if ($registration->participant_type === 'mahasiswa_gunadarma')
                    <div><dt>NPM</dt><dd>{{ $registration->student_id }}</dd></div>
                    <div><dt>Area Kampus</dt><dd>{{ ucfirst($registration->campus_area) }}</dd></div>
                    <div><dt>Angkatan</dt><dd>{{ $registration->class_year }}</dd></div>
                    <div><dt>Program Studi</dt><dd>{{ $registration->study_program }}</dd></div>
                @endif
                @foreach (($registration->custom_fields ?? []) as $field => $value)
                    <div><dt>{{ \App\Models\Event::registrationFieldLabel($field) }}</dt><dd>{{ $value }}</dd></div>
                @endforeach
                <div><dt>Nominal</dt><dd>{{ $registration->event->price > 0 ? 'Rp '.number_format($registration->event->price, 0, ',', '.') : 'Gratis' }}</dd></div>
                @if ($registration->event->price > 0)
                    <div><dt>Order ID</dt><dd>{{ $registration->payment?->order_id ?? '-' }}</dd></div>
                @endif
            </dl>

            @if ($registration->isCheckInReady())
                <section class="qr-checkin">
                    <div>
                        <p class="eyebrow">QR Check-in</p>
                        <h2>Tunjukkan saat hadir</h2>
                    </div>
                    <img src="{{ $registration->qrCodeDataUri() }}" alt="QR Check-in {{ $registration->registration_code }}">
                    <a class="link-button" href="{{ $registration->verificationUrl() }}">Lihat Detail Tiket</a>
                </section>
            @endif

            @if ($registration->payment_status === 'pending' && $registration->payment?->snap_token)
                <div class="payment-actions">
                    <button class="button" id="pay-button" type="button">Lanjut ke Pembayaran</button>
                    <form method="POST" action="{{ route('registrations.payment.status', $registration) }}" id="payment-status-form">
                        @csrf
                        <button class="link-button" type="submit">Cek Status Pembayaran</button>
                    </form>
                </div>

                <div class="payment-confirm-backdrop" id="payment-confirm-backdrop" hidden>
                    <section class="payment-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="payment-confirm-title">
                        <div class="payment-confirm-heading">
                            <div>
                                <p class="eyebrow">Konfirmasi Pembayaran</p>
                                <h2 id="payment-confirm-title">Periksa detail transaksi</h2>
                            </div>
                            <button class="payment-confirm-close" id="payment-confirm-close" type="button" aria-label="Tutup konfirmasi pembayaran">X</button>
                        </div>
                        <dl class="payment-confirm-details">
                            <div><dt>Event</dt><dd>{{ $registration->event->title }}</dd></div>
                            <div><dt>Nominal</dt><dd>Rp {{ number_format($registration->event->price, 0, ',', '.') }}</dd></div>
                            <div><dt>Order ID</dt><dd>{{ $registration->payment->order_id }}</dd></div>
                        </dl>
                        <p class="payment-confirm-note">Setelah pembayaran, status akan diperiksa otomatis. Anda juga dapat menggunakan tombol Cek Status Pembayaran.</p>
                        <div class="payment-confirm-actions">
                            <button class="link-button" id="payment-confirm-cancel" type="button">Batal</button>
                            <button class="button" id="payment-confirm-submit" type="button">Bayar Sekarang</button>
                        </div>
                    </section>
                </div>

                <div class="payment-confirm-backdrop" id="payment-status-backdrop" hidden>
                    <section class="payment-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="payment-status-title">
                        <div class="payment-confirm-heading">
                            <div>
                                <p class="eyebrow">Status Pembayaran</p>
                                <h2 id="payment-status-title">Hasil pemeriksaan</h2>
                            </div>
                            <button class="payment-confirm-close" id="payment-status-close" type="button" aria-label="Tutup status pembayaran">X</button>
                        </div>
                        <p class="payment-confirm-note" id="payment-status-message" role="status"></p>
                        <div class="payment-confirm-actions">
                            <button class="button" id="payment-status-ok" type="button">Tutup</button>
                        </div>
                    </section>
                </div>
            @elseif ($registration->payment_status === 'pending' && $registration->payment)
                <div class="payment-actions">
                    <form method="POST" action="{{ route('registrations.payment.initialize', $registration) }}">
                        @csrf
                        <button class="button" type="submit">Coba Siapkan Pembayaran Lagi</button>
                    </form>
                </div>
            @endif
        </div>
    </section>

    @if ($registration->payment?->snap_token)
        <script>
            const payButton = document.getElementById('pay-button');
            const confirmBackdrop = document.getElementById('payment-confirm-backdrop');
            const confirmClose = document.getElementById('payment-confirm-close');
            const confirmCancel = document.getElementById('payment-confirm-cancel');
            const confirmSubmit = document.getElementById('payment-confirm-submit');
            const statusForm = document.getElementById('payment-status-form');
            const statusButton = statusForm?.querySelector('button');
            const statusBackdrop = document.getElementById('payment-status-backdrop');
            const statusClose = document.getElementById('payment-status-close');
            const statusOk = document.getElementById('payment-status-ok');
            const statusMessage = document.getElementById('payment-status-message');
            const redirectUrl = @json($registration->payment->redirect_url);

            const syncPaymentStatus = async () => {
                try {
                    await fetch(@json(route('registrations.payment.status', $registration)), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                    });
                } finally {
                    window.location.reload();
                }
            };

            const closeConfirmation = () => {
                if (! confirmBackdrop) {
                    return;
                }

                confirmBackdrop.hidden = true;
                document.body.classList.remove('modal-open');
                payButton?.focus();
            };

            const closeStatus = () => {
                if (! statusBackdrop) {
                    return;
                }

                statusBackdrop.hidden = true;
                document.body.classList.remove('modal-open');
                window.location.reload();
            };

            statusForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                statusButton.disabled = true;
                statusButton.textContent = 'Memeriksa...';

                try {
                    const response = await fetch(statusForm.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                    });
                    const result = await response.json();

                    statusMessage.textContent = result.message ?? 'Status pembayaran berhasil diperiksa.';
                } catch (error) {
                    statusMessage.textContent = 'Status pembayaran belum dapat diperiksa. Silakan coba kembali.';
                } finally {
                    statusButton.disabled = false;
                    statusButton.textContent = 'Cek Status Pembayaran';
                    statusBackdrop.hidden = false;
                    document.body.classList.add('modal-open');
                    statusOk?.focus();
                }
            });

            statusClose?.addEventListener('click', closeStatus);
            statusOk?.addEventListener('click', closeStatus);
            statusBackdrop?.addEventListener('click', (event) => {
                if (event.target === statusBackdrop) {
                    closeStatus();
                }
            });

            payButton?.addEventListener('click', () => {
                if (! confirmBackdrop) {
                    return;
                }

                confirmBackdrop.hidden = false;
                document.body.classList.add('modal-open');
                confirmSubmit?.focus();
            });

            confirmClose?.addEventListener('click', closeConfirmation);
            confirmCancel?.addEventListener('click', closeConfirmation);
            confirmBackdrop?.addEventListener('click', (event) => {
                if (event.target === confirmBackdrop) {
                    closeConfirmation();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && confirmBackdrop && ! confirmBackdrop.hidden) {
                    closeConfirmation();
                }

                if (event.key === 'Escape' && statusBackdrop && ! statusBackdrop.hidden) {
                    closeStatus();
                }
            });

            confirmSubmit?.addEventListener('click', () => {
                closeConfirmation();

                if (window.snap) {
                    window.snap.pay(@json($registration->payment->snap_token), {
                        onSuccess: syncPaymentStatus,
                        onPending: syncPaymentStatus,
                        onError: syncPaymentStatus,
                    });
                    return;
                }

                window.location.assign(redirectUrl);
            });
        </script>
    @endif
@endsection
