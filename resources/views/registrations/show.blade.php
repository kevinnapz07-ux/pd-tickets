@extends('layouts.app', ['title' => 'Registrasi '.$registration->registration_code])

@push('head')
    @if ($registration->payment_status === 'pending' && $registration->payment && config('services.midtrans.client_key'))
        <script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    @endif
@endpush

@section('content')
    @php
        $categoryLabel = collect($registration->event->registrationCategories())->firstWhere('key', $registration->participant_type)['label'] ?? Str::headline(str_replace('_', ' ', $registration->participant_type));
        $customFields = collect($registration->custom_fields ?? []);
        $gender = $customFields->get('gender');
        $domicile = $customFields->get('domicile');
        $hasCheckedIn = $registration->checked_in_at !== null || $registration->registration_status === 'checked_in';
        $transactionStatusClass = match ($registration->payment_status) {
            'pending' => 'is-pending',
            'paid' => 'is-paid',
            default => in_array($registration->payment_status, ['expired', 'failed', 'cancelled', 'refunded'], true) ? 'is-error' : 'is-ready',
        };
    @endphp

    <section class="detail registration-detail-layout">
        <article class="detail-main registration-detail-main">
            <h1>{{ $registration->event->title }}</h1>
            <p class="event-description detail-description">{{ $registration->event->description }}</p>

            <dl class="info-list">
                <div><dt>Pembicara</dt><dd>{{ $registration->event->speaker ?? '-' }}</dd></div>
                <div><dt>Lokasi</dt><dd>{{ $registration->event->location }}</dd></div>
                <div><dt>Waktu</dt><dd>{{ $registration->event->starts_at->format('H:i') }}{{ $registration->event->ends_at ? ' - '.$registration->event->ends_at->format('H:i') : '' }} WIB</dd></div>
                <div><dt>Biaya</dt><dd>{{ $registration->event->price > 0 ? 'Rp '.number_format($registration->event->price, 0, ',', '.') : 'Gratis' }}</dd></div>
                <div><dt>Tanggal</dt><dd>{{ $registration->event->starts_at->translatedFormat('d F Y') }}</dd></div>
            </dl>

            <div class="map-panel">
                <h2>Maps Lokasi</h2>
                <iframe
                    title="Peta {{ $registration->event->location }}"
                    src="https://www.google.com/maps?q={{ urlencode($registration->event->location.' Gunadarma') }}&output=embed"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </article>

        <aside class="form-panel registration-detail-panel">
            <p class="eyebrow">Kode Registrasi</p>
            <h1>{{ $registration->registration_code }}</h1>

            <dl class="registration-summary" aria-label="Ringkasan registrasi">
                <div class="registration-summary-event">
                    <dt>Event</dt>
                    <dd>{{ $registration->event->title }}</dd>
                </div>
                <div>
                    <dt>Nama</dt>
                    <dd>{{ $registration->name }}</dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd>
                        <span class="compact-status {{ $transactionStatusClass }}">
                            <span aria-hidden="true">●</span>
                            {{ $registration->transactionStatusLabel() }}
                        </span>
                    </dd>
                </div>
            </dl>

            @if (session('payment_error'))
                <div class="error-box">{{ session('payment_error') }}</div>
            @endif

            @if ($registration->isCheckInReady())
                <section class="qr-checkin compact-qr-checkin" id="ticket-qr">
                    <div>
                        <p class="eyebrow">QR Check-in</p>
                        <h2>Tunjukkan saat hadir</h2>
                    </div>
                    <img src="{{ $registration->qrCodeDataUri() }}" alt="QR Check-in {{ $registration->registration_code }}">
                </section>
            @endif

            <section class="participant-detail-accordion" data-participant-accordion>
                <button type="button" aria-expanded="false" aria-controls="participant-detail-panel-{{ $registration->id }}" data-accordion-toggle>
                    <span>Informasi Registrasi</span>
                    <span class="accordion-chevron" aria-hidden="true">⌄</span>
                </button>
                <div id="participant-detail-panel-{{ $registration->id }}" data-accordion-panel hidden>
                    <dl class="participant-detail-grid">
                        <div class="participant-email"><dt>Email</dt><dd>{{ $registration->email }}</dd></div>
                        <div><dt>No. HP</dt><dd>{{ $registration->phone }}</dd></div>
                        <div><dt>Kategori</dt><dd>{{ $categoryLabel }}</dd></div>
                        <div><dt>Domisili</dt><dd>{{ $domicile ?: '-' }}</dd></div>
                        <div><dt>Jenis Kelamin</dt><dd>{{ $gender ? Str::headline(str_replace('_', ' ', $gender)) : '-' }}</dd></div>
                        <div><dt>Nominal</dt><dd>{{ $registration->event->price > 0 ? 'Rp '.number_format($registration->event->price, 0, ',', '.') : 'Gratis' }}</dd></div>
                        @if ($registration->participant_type === 'mahasiswa_gunadarma')
                            <div><dt>NPM</dt><dd>{{ $registration->student_id }}</dd></div>
                            <div><dt>Area Kampus</dt><dd>{{ ucfirst($registration->campus_area) }}</dd></div>
                            <div><dt>Angkatan</dt><dd>{{ $registration->class_year }}</dd></div>
                            <div><dt>Program Studi</dt><dd>{{ $registration->study_program }}</dd></div>
                        @endif
                        @foreach ($customFields->except(['gender', 'domicile']) as $field => $value)
                            <div><dt>{{ \App\Models\Event::registrationFieldLabel($field) }}</dt><dd>{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                </div>
            </section>

            @if ($registration->payment_status === 'pending' && $registration->payment?->snap_token)
                <div class="payment-actions payment-primary-action">
                    <button class="button" id="pay-button" type="button">Lanjut ke Pembayaran</button>
                </div>

                <div class="payment-confirm-backdrop" id="payment-confirm-backdrop" hidden>
                    <section class="payment-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="payment-confirm-title">
                        <div class="payment-confirm-heading">
                            <div>
                                <p class="eyebrow">Konfirmasi Pembayaran</p>
                                <h2 id="payment-confirm-title">Periksa Detail Transaksi</h2>
                            </div>
                            <button class="payment-confirm-close" id="payment-confirm-close" type="button" aria-label="Tutup konfirmasi pembayaran">X</button>
                        </div>
                        <dl class="payment-confirm-details">
                            <div class="payment-detail-event"><dt>Event</dt><dd>{{ $registration->event->title }}</dd></div>
                            <div><dt>Tanggal</dt><dd>{{ $registration->event->starts_at->translatedFormat('d F Y') }}</dd></div>
                            <div><dt>Waktu</dt><dd>{{ $registration->event->starts_at->format('H.i') }}{{ $registration->event->ends_at ? '–'.$registration->event->ends_at->format('H.i') : '' }} WIB</dd></div>
                            <div class="payment-detail-location"><dt>Lokasi</dt><dd>{{ $registration->event->location }}</dd></div>
                            <div class="payment-detail-amount"><dt>Nominal</dt><dd>Rp{{ number_format($registration->event->price, 0, ',', '.') }}</dd></div>
                        </dl>
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
                                <h2 id="payment-status-title">Status Belum Dapat Diperiksa</h2>
                            </div>
                            <button class="payment-confirm-close" id="payment-status-close" type="button" aria-label="Tutup status pembayaran">X</button>
                        </div>
                        <p class="payment-confirm-note" id="payment-status-message" role="status">Kami belum dapat memverifikasi pembayaran saat ini. Silakan coba kembali beberapa saat lagi.</p>
                        <div class="payment-confirm-actions">
                            <button class="button" id="payment-status-ok" type="button">Tutup</button>
                        </div>
                    </section>
                </div>
            @elseif ($registration->payment_status === 'pending' && $registration->payment)
                <div class="payment-actions">
                    <form method="POST" action="{{ route('registrations.payment.initialize', $registration) }}" id="payment-initialize-form">
                        @csrf
                        <button class="button" type="submit" id="payment-initialize-button">Siapkan dan Lanjutkan Pembayaran</button>
                    </form>
                </div>
                <p class="error-box payment-inline-message" id="payment-initialize-message" role="status" hidden></p>
            @elseif (in_array($registration->payment_status, ['expired', 'failed', 'cancelled'], true))
                <div class="payment-actions">
                    <form method="POST" action="{{ route('registrations.payment.retry', $registration) }}" data-disable-submit>
                        @csrf
                        <button class="button" type="submit">{{ $registration->payment_status === 'expired' ? 'Buat Pembayaran Baru' : 'Bayar Lagi' }}</button>
                    </form>
                    <a class="link-button" href="{{ route('tickets.index') }}">Kembali ke Tiket Saya</a>
                </div>
            @elseif ($registration->isCheckInReady())
                <div class="payment-actions">
                    <a class="link-button" href="{{ route('events.index') }}">Kembali ke Beranda</a>
                </div>
            @endif
        </aside>
    </section>

    @if ($registration->payment?->snap_token)
        <script>
            const payButton = document.getElementById('pay-button');
            const confirmBackdrop = document.getElementById('payment-confirm-backdrop');
            const confirmClose = document.getElementById('payment-confirm-close');
            const confirmCancel = document.getElementById('payment-confirm-cancel');
            const confirmSubmit = document.getElementById('payment-confirm-submit');
            const statusBackdrop = document.getElementById('payment-status-backdrop');
            const statusClose = document.getElementById('payment-status-close');
            const statusOk = document.getElementById('payment-status-ok');
            const statusTitle = document.getElementById('payment-status-title');
            const statusMessage = document.getElementById('payment-status-message');
            const redirectUrl = @json($registration->payment->redirect_url);
            let paymentProcessing = false;

            const showStatus = (title, message) => {
                statusTitle.textContent = title;
                statusMessage.textContent = message;
                statusBackdrop.hidden = false;
                document.body.classList.add('modal-open');
                statusOk?.focus();
            };

            const syncPaymentStatus = async () => {
                showStatus('Memverifikasi Pembayaran', 'Memverifikasi pembayaran...');

                try {
                    const response = await fetch(@json(route('registrations.payment.status', $registration)), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                    });

                    if (! response.ok) {
                        throw new Error('Payment status verification failed.');
                    }

                    window.location.reload();
                } catch (_) {
                    paymentProcessing = false;
                    confirmSubmit.disabled = false;
                    confirmSubmit.textContent = 'Bayar Sekarang';
                    showStatus(
                        'Status Belum Dapat Diperiksa',
                        'Kami belum dapat memverifikasi pembayaran saat ini. Silakan coba kembali beberapa saat lagi.',
                    );
                }
            };

            const closeConfirmation = (restorePayButton = true) => {
                if (! confirmBackdrop) {
                    return;
                }

                confirmBackdrop.hidden = true;
                document.body.classList.remove('modal-open');
                if (restorePayButton) {
                    payButton.disabled = false;
                    payButton?.focus();
                }
            };

            const closeStatus = () => {
                if (! statusBackdrop) {
                    return;
                }

                statusBackdrop.hidden = true;
                document.body.classList.remove('modal-open');
                payButton?.focus();
            };

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

                payButton.disabled = true;
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
                if (paymentProcessing) return;

                paymentProcessing = true;
                confirmSubmit.disabled = true;
                confirmSubmit.textContent = 'Memproses...';
                closeConfirmation(false);

                if (window.snap) {
                    window.snap.pay(@json($registration->payment->snap_token), {
                        onSuccess: syncPaymentStatus,
                        onPending: syncPaymentStatus,
                        onError: syncPaymentStatus,
                        onClose: () => {
                            paymentProcessing = false;
                            confirmSubmit.disabled = false;
                            confirmSubmit.textContent = 'Bayar Sekarang';
                            payButton.disabled = false;
                        },
                    });
                    return;
                }

                window.location.assign(redirectUrl);
            });
        </script>
    @elseif ($registration->payment_status === 'pending' && $registration->payment)
        <script>
            const initializeForm = document.getElementById('payment-initialize-form');
            const initializeButton = document.getElementById('payment-initialize-button');
            const initializeMessage = document.getElementById('payment-initialize-message');

            initializeForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                initializeButton.disabled = true;
                initializeButton.textContent = 'Menyiapkan pembayaran...';
                initializeMessage.hidden = true;

                try {
                    const response = await fetch(initializeForm.action, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: new FormData(initializeForm),
                    });
                    const contentType = response.headers.get('content-type') ?? '';
                    const result = contentType.includes('application/json')
                        ? await response.json()
                        : null;

                    if (! response.ok) {
                        const fallbackMessage = response.status === 419
                            ? 'Sesi Anda sudah berakhir. Muat ulang halaman, login kembali, lalu coba lagi.'
                            : response.status >= 500
                                ? 'Server pembayaran sedang mengalami gangguan. Silakan coba kembali setelah konfigurasi diperiksa admin.'
                                : 'Pembayaran belum dapat disiapkan.';

                        throw new Error(result?.message ?? fallbackMessage);
                    }

                    if (! result) {
                        if (response.redirected && new URL(response.url).pathname === '/login') {
                            throw new Error('Sesi Anda sudah berakhir. Silakan login kembali untuk melanjutkan pembayaran.');
                        }

                        // A successful HTML redirect means the non-JavaScript fallback
                        // completed. Reload to read the Snap token saved by the server.
                        window.location.reload();
                        return;
                    }

                    if (window.snap && result.snap_token) {
                        window.snap.pay(result.snap_token, {
                            onSuccess: () => window.location.reload(),
                            onPending: () => window.location.reload(),
                            onError: () => window.location.reload(),
                            onClose: () => window.location.reload(),
                        });
                        return;
                    }

                    if (result.redirect_url) {
                        window.location.assign(result.redirect_url);
                        return;
                    }

                    window.location.reload();
                } catch (error) {
                    initializeMessage.textContent = error.message ?? 'Pembayaran belum dapat disiapkan. Silakan coba kembali.';
                    initializeMessage.hidden = false;
                    initializeButton.disabled = false;
                    initializeButton.textContent = 'Siapkan dan Lanjutkan Pembayaran';
                }
            });
        </script>
    @endif

    @if ($registration->payment_status === 'pending')
        <div class="payment-confirm-backdrop" id="payment-success-backdrop" hidden>
            <section class="payment-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="payment-success-title">
                <div class="payment-success-icon" aria-hidden="true">✓</div>
                <p class="eyebrow">Tiket Aktif</p>
                <h2 id="payment-success-title">Pembayaran Berhasil</h2>
                <p>Pembayaran kamu telah diterima. Tiket sudah diterbitkan dan dapat digunakan.</p>
                <p><strong>{{ $registration->event->title }}</strong><br>{{ $registration->registration_code }}</p>
                <div class="payment-confirm-actions">
                    <a class="button" id="payment-success-ticket" href="{{ route('registrations.show', $registration) }}#ticket-qr">Lihat Tiket</a>
                    <a class="link-button" href="{{ route('events.index') }}">Kembali ke Beranda</a>
                </div>
            </section>
        </div>
        <script>
            (() => {
                let stopped = false;
                const poll = async () => {
                    if (stopped || document.hidden) return;
                    try {
                        const response = await fetch(@json(route('registrations.payment.state', $registration)), {
                            headers: {'Accept': 'application/json'}
                        });
                        if (!response.ok) return;
                        const result = await response.json();
                        if (result.status === 'paid') {
                            stopped = true;
                            const modal = document.getElementById('payment-success-backdrop');
                            const ticket = document.getElementById('payment-success-ticket');
                            if (ticket && result.ticket_url) ticket.href = result.ticket_url + '#ticket-qr';
                            if (modal) {
                                modal.hidden = false;
                                document.body.classList.add('modal-open');
                            }
                        } else if (['expired', 'failed', 'cancelled'].includes(result.status)) {
                            stopped = true;
                            window.location.reload();
                        }
                    } catch (_) {}
                };
                window.setInterval(poll, 7000);
            })();
        </script>
    @endif
@endsection
