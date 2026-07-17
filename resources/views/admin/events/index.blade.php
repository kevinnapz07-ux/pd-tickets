@extends('layouts.app', ['title' => 'Kelola Event'])

@section('content')
    <section class="dashboard">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>Kelola Event</h1>
            </div>
            <a class="button" href="{{ route('admin.events.create') }}">Tambah Event</a>
        </div>

        @include('admin.partials.nav')

        @if ($errors->any())
            <div class="error-box">{{ $errors->first() }}</div>
        @endif

        <section class="table-section">
            <table>
                <thead>
                    <tr><th>Event</th><th>Jadwal</th><th>Jenis Pendaftaran</th><th>Kuota</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($events as $event)
                        <tr>
                            <td>
                                <strong>{{ $event->title }}</strong><br>
                                <span>{{ $event->location }}</span>
                            </td>
                            <td>{{ $event->starts_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $event->price > 0 ? 'Berbayar - Rp '.number_format($event->price, 0, ',', '.') : 'Gratis' }}</td>
                            <td>{{ $event->paid_registrations_count }}/{{ $event->quota }}</td>
                            <td>
                                <span>{{ $event->is_published ? 'Publish' : 'Draft' }}</span><br>
                                <small>{{ $event->registration_is_open ? 'Pendaftaran dibuka' : 'Segera dibuka' }}</small>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="link-button" href="{{ route('admin.events.edit', $event) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Hapus event ini?')">
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
        </section>
    </section>
@endsection
