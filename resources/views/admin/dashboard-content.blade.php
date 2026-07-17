    <section class="dashboard">
        <header class="admin-hub-hero">
            <div class="admin-hub-copy">
                <p class="eyebrow">Dashboard Admin PDUG</p>
                <h1>Selamat datang, {{ auth()->user()->name }}</h1>
                <p>Kelola agenda, pengguna, transaksi, laporan, dan informasi website dari satu tempat.</p>
            </div>
            <div class="admin-hub-actions">
                <a class="button" href="{{ route('admin.tickets.scanner') }}">Buka Scanner Tiket</a>
                <span class="admin-hub-status"><i aria-hidden="true"></i>Sistem aktif</span>
            </div>
        </header>

        <div class="admin-section-heading">
            <div>
                <p class="eyebrow">Ringkasan Sistem</p>
                <h2>Statistik utama</h2>
            </div>
            <p>Data terkini dari seluruh aktivitas platform.</p>
        </div>

        <div class="stat-grid">
            <div><span>Event</span><strong>{{ $stats['events'] }}</strong></div>
            <div><span>Pengguna</span><strong>{{ $stats['users'] }}</strong></div>
            <div><span>Registrasi</span><strong>{{ $stats['registrations'] }}</strong></div>
            <div><span>Transaksi Berhasil</span><strong>{{ $stats['paid'] }}</strong></div>
            <div><span>Menunggu Pembayaran</span><strong>{{ $stats['pending'] }}</strong></div>
            <div><span>Check-in</span><strong>{{ $stats['checked_in'] }}</strong></div>
        </div>

        <section class="table-section">
            <div class="admin-table-heading">
                <div>
                    <p class="eyebrow">Ringkasan</p>
                    <h2>Event</h2>
                </div>
            </div>
            <table>
                <thead><tr><th>Nama Event</th><th>Tanggal</th><th>Kuota</th><th>Terbayar</th></tr></thead>
                <tbody>
                    @foreach ($events as $event)
                        <tr>
                            <td>{{ $event->title }}</td>
                            <td>{{ $event->starts_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $event->quota }}</td>
                            <td>{{ $event->paid_registrations_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="table-section">
            <div class="admin-table-heading">
                <div>
                    <p class="eyebrow">Aktivitas Terbaru</p>
                    <h2>Registrasi peserta</h2>
                </div>
            </div>
            <table>
                <thead><tr><th>Kode</th><th>Peserta</th><th>Event</th><th>Transaksi</th><th>Pendaftaran</th><th>Order</th></tr></thead>
                <tbody>
                    @foreach ($registrations as $registration)
                        <tr>
                            <td>{{ $registration->registration_code }}</td>
                            <td>{{ $registration->name }}</td>
                            <td>{{ $registration->event->title }}</td>
                            <td><span class="status status-{{ $registration->payment_status }}">{{ $registration->transactionStatusLabel() }}</span></td>
                            <td><span class="status status-{{ $registration->registration_status ?? 'pending' }}">{{ $registration->registrationStatusLabel() }}</span></td>
                            <td>{{ $registration->payment?->order_id ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </section>
