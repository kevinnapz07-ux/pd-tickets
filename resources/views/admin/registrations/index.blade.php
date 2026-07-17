@extends('layouts.app', ['title' => 'Transaksi'])

@section('content')
    <section class="dashboard">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>Transaksi</h1>
            </div>
        </div>

        @include('admin.partials.nav')

        <form class="filter-bar" method="GET" action="{{ route('admin.registrations.index') }}">
            <input name="search" value="{{ request('search') }}" placeholder="Cari nama, email, kode">
            <select name="event_id">
                <option value="">Semua Event</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected((string) request('event_id') === (string) $event->id)>{{ $event->title }}</option>
                @endforeach
            </select>
            <select name="transaction_status">
                <option value="">Semua Status Transaksi</option>
                @foreach ($transactionStatuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('transaction_status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="registration_status">
                <option value="">Semua Status Pendaftaran</option>
                @foreach ($registrationStatuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('registration_status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="button" type="submit">Filter</button>
            <a class="link-button" href="{{ route('admin.registrations.index') }}">Reset</a>
        </form>

        <section class="table-section">
            <table>
                <thead>
                    <tr><th>Kode</th><th>Peserta</th><th>Kategori</th><th>Event</th><th>Status Transaksi</th><th>Status Pendaftaran</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($registrations as $registration)
                        <tr>
                            <td>{{ $registration->registration_code }}</td>
                            <td>
                                <strong>{{ $registration->name }}</strong><br>
                                <span>{{ $registration->email }} • {{ $registration->phone }}</span>
                            </td>
                            <td>
                                @php
                                    $categoryLabel = collect($registration->event->registrationCategories())->firstWhere('key', $registration->participant_type)['label'] ?? Str::headline(str_replace('_', ' ', $registration->participant_type));
                                @endphp
                                {{ $categoryLabel }}
                                @if ($registration->participant_type === 'mahasiswa_gunadarma')
                                    <br><span>{{ ucfirst($registration->campus_area) }} • {{ $registration->class_year }} • {{ $registration->study_program }}</span>
                                @endif
                            </td>
                            <td>{{ $registration->event->title }}</td>
                            <td><span class="status status-{{ $registration->payment_status }}">{{ $registration->transactionStatusLabel() }}</span></td>
                            <td><span class="status status-{{ $registration->registration_status ?? 'pending' }}">{{ $registration->registrationStatusLabel() }}</span></td>
                            <td>
                                <div class="table-actions">
                                    <form method="POST" action="{{ route('admin.registrations.status', $registration) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="registration_status">
                                            <option value="">Belum terdaftar</option>
                                            @foreach ($registrationStatuses as $value => $label)
                                                <option value="{{ $value }}" @selected($registration->registration_status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button class="button" type="submit">Update Pendaftaran</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.registrations.checkin', $registration) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="button" type="submit" @disabled(! $registration->isCheckInReady())>Check-in</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.registrations.destroy', $registration) }}" onsubmit="return confirm('Hapus peserta ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="danger-button" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $registrations->links() }}
        </section>
    </section>
@endsection
