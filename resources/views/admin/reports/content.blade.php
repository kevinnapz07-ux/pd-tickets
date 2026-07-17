    @php
        $selectedTransactionStatus = request('transaction_status')
            ? ($transactionStatuses[request('transaction_status')] ?? Str::headline(request('transaction_status')))
            : 'Semua Status Pembayaran';
        $selectedRegistrationStatus = request('registration_status')
            ? ($registrationStatuses[request('registration_status')] ?? Str::headline(request('registration_status')))
            : 'Semua Status Pendaftaran';
    @endphp

    <section class="dashboard report-page">
        <form class="filter-bar report-filter-card no-print" method="GET" action="{{ route('filament.admin.pages.laporan') }}">
            <label class="report-filter-field">
                <span>Event</span>
                <select name="event_id">
                    <option value="">Semua Event</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}" @selected((string) request('event_id') === (string) $event->id)>{{ $event->title }}</option>
                    @endforeach
                </select>
            </label>
            <label class="report-filter-field">
                <span>Status Pembayaran</span>
                <select name="transaction_status">
                    <option value="">Semua Status Pembayaran</option>
                    @foreach ($transactionStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('transaction_status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="report-filter-field">
                <span>Status Pendaftaran</span>
                <select name="registration_status">
                    <option value="">Semua Status Pendaftaran</option>
                    @foreach ($registrationStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('registration_status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div class="report-filter-actions">
                <button class="button" type="submit">Tampilkan</button>
                <a class="link-button" href="{{ route('filament.admin.pages.laporan') }}">Reset Filter</a>
                <a class="link-button report-print-button" href="{{ route('admin.reports.export', request()->query()) }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6z" /></svg>
                    Cetak
                </a>
            </div>
        </form>

        <section class="print-header report-metadata">
            <div>
                <p class="eyebrow">Laporan Per Event</p>
                <h2>Laporan Registrasi Event PD Gunadarma</h2>
            </div>
            <dl>
                <div><dt>Event</dt><dd>{{ $selectedEvent ? 'Event: '.$selectedEvent->title : 'Semua Event' }}</dd></div>
                <div><dt>Pembayaran</dt><dd>{{ $selectedTransactionStatus }}</dd></div>
                <div><dt>Pendaftaran</dt><dd>{{ $selectedRegistrationStatus }}</dd></div>
                <div><dt>Dicetak</dt><dd>{{ now()->format('d/m/Y H:i') }} WIB</dd></div>
                <div><dt>Admin</dt><dd>{{ auth()->user()->name }}</dd></div>
            </dl>
        </section>

        <section class="report-summary">
            <div class="report-section-heading">
                <div><p class="eyebrow">Ringkasan</p><h2>Laporan Kehadiran</h2></div>
                <p>{{ $registrations->count() }} data sesuai filter saat ini.</p>
            </div>
            <div class="stat-grid report-stat-grid">
                @foreach ([
                    ['key' => 'registered', 'label' => 'Terdaftar', 'icon' => 'user'],
                    ['key' => 'checked_in', 'label' => 'Check-in', 'icon' => 'check'],
                    ['key' => 'completed', 'label' => 'Selesai', 'icon' => 'calendar'],
                    ['key' => 'cancelled', 'label' => 'Dibatalkan', 'icon' => 'x'],
                ] as $stat)
                    <div>
                        <span class="report-stat-icon" aria-hidden="true">
                            @if ($stat['icon'] === 'check')
                                <svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6" /></svg>
                            @elseif ($stat['icon'] === 'calendar')
                                <svg viewBox="0 0 24 24"><path d="M6 2v4m12-4v4M3 9h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z" /></svg>
                            @elseif ($stat['icon'] === 'x')
                                <svg viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18" /></svg>
                            @else
                                <svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" /></svg>
                            @endif
                        </span>
                        <span>{{ $stat['label'] }}</span>
                        <strong>{{ $attendanceStats[$stat['key']] }}</strong>
                    </div>
                @endforeach
            </div>
        </section>

        @forelse ($eventReports as $eventReport)
            @php
                $event = $eventReport['event'];
                $items = $eventReport['registrations'];
                $activeParticipants = $items->where('registration_status', '!=', 'cancelled')->count();
            @endphp
            <section class="table-section report-table event-report-block">
                <div class="event-report-header">
                    <div class="event-report-title">
                        <div class="event-report-title-row">
                            <p class="eyebrow">Event</p>
                            <span class="report-status {{ $event->registration_is_open ? 'report-status-success' : 'report-status-muted' }}">
                                {{ $event->registration_is_open ? 'Registrasi Dibuka' : 'Registrasi Ditutup' }}
                            </span>
                        </div>
                        <h2>{{ $event->title }}</h2>
                        <p>{{ $event->location }}</p>
                    </div>
                    <dl>
                        <div><dt>Tanggal</dt><dd>{{ $event->starts_at->translatedFormat('d M Y') }}</dd></div>
                        <div><dt>Waktu</dt><dd>{{ $event->starts_at->format('H:i') }} WIB</dd></div>
                        <div><dt>Jenis</dt><dd>{{ $event->price > 0 ? 'Berbayar' : 'Gratis' }}</dd></div>
                        <div><dt>Peserta</dt><dd>{{ $activeParticipants }}/{{ $event->quota }}</dd></div>
                        <div><dt>Check-in</dt><dd>{{ $items->where('registration_status', 'checked_in')->count() }}</dd></div>
                    </dl>
                </div>

                @if ($items->isEmpty())
                    <div class="report-empty-state">
                        <span aria-hidden="true">0</span>
                        <h3>Belum ada data peserta</h3>
                        <p>Tidak ada registrasi yang sesuai dengan filter untuk event ini.</p>
                    </div>
                @else
                    <div class="report-table-wrap">
                        <table>
                            <thead>
                                <tr><th>No</th><th>Kode</th><th>Nama</th><th>Email</th><th>No. HP</th><th>Jenis Kelamin</th><th>Domisili</th><th>Kategori</th><th>Data Tambahan</th><th>Transaksi</th><th>Pendaftaran</th><th>Kehadiran</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $registration)
                                    @php
                                        $categoryLabel = collect($registration->event->registrationCategories())->firstWhere('key', $registration->participant_type)['label'] ?? Str::headline(str_replace('_', ' ', $registration->participant_type));
                                        $paymentState = $registration->event->price <= 0 ? 'muted' : match ($registration->payment_status) {
                                            'paid' => 'success',
                                            'pending' => 'warning',
                                            'failed', 'expired', 'cancelled', 'refunded' => 'danger',
                                            default => 'muted',
                                        };
                                        $registrationState = match ($registration->registration_status) {
                                            'checked_in', 'completed' => 'success',
                                            'registered' => 'info',
                                            'cancelled' => 'danger',
                                            default => 'warning',
                                        };
                                        $customFields = collect($registration->custom_fields ?? []);
                                        $gender = $customFields->get('gender');
                                        $domicile = $customFields->get('domicile');
                                        $additionalFields = $customFields->except(['gender', 'domicile']);
                                    @endphp
                                    <tr>
                                        <td data-label="No">{{ $loop->iteration }}</td>
                                        <td data-label="Kode"><strong>{{ $registration->registration_code }}</strong></td>
                                        <td data-label="Nama">{{ $registration->name }}</td>
                                        <td data-label="Email">{{ $registration->email }}</td>
                                        <td data-label="No. HP">{{ $registration->phone ?: '-' }}</td>
                                        <td data-label="Jenis Kelamin">{{ $gender ? Str::headline(str_replace('_', ' ', $gender)) : '-' }}</td>
                                        <td data-label="Domisili">{{ $domicile ?: '-' }}</td>
                                        <td data-label="Kategori">{{ $categoryLabel }}</td>
                                        <td data-label="Data Tambahan">
                                            @forelse ($additionalFields as $field => $value)
                                                <span class="report-custom-field"><strong>{{ \App\Models\Event::registrationFieldLabel($field) }}</strong>{{ $value }}</span>
                                            @empty
                                                -
                                            @endforelse
                                        </td>
                                        <td data-label="Transaksi"><span class="report-status report-status-{{ $paymentState }}">{{ $registration->event->price > 0 ? $registration->transactionStatusLabel() : 'Gratis' }}</span></td>
                                        <td data-label="Pendaftaran"><span class="report-status report-status-{{ $registrationState }}">{{ $registration->registrationStatusLabel() }}</span></td>
                                        <td data-label="Kehadiran">
                                            <span class="report-status report-status-{{ $registration->checked_in_at ? 'success' : 'muted' }}">{{ $registration->checked_in_at ? 'Sudah Check-in' : 'Belum Check-in' }}</span>
                                            @if ($registration->checked_in_at)<small>{{ $registration->checked_in_at->format('d/m/Y H:i') }}</small>@endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @empty
            <section class="table-section report-table event-report-block report-empty-state">
                <span aria-hidden="true">0</span>
                <h3>Belum ada data laporan</h3>
                <p>Ubah filter atau tambahkan registrasi peserta untuk menampilkan laporan.</p>
            </section>
        @endforelse
    </section>
