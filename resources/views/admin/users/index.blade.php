@extends('layouts.app', ['title' => 'Kelola Pengguna'])

@section('content')
    <section class="dashboard">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>Kelola Pengguna</h1>
            </div>
        </div>

        @include('admin.partials.nav')

        <form class="filter-bar users-filter" method="GET" action="{{ route('admin.users.index') }}">
            <input name="search" value="{{ request('search') }}" placeholder="Cari nama atau email">
            <select name="role">
                <option value="">Semua Role</option>
                <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                <option value="peserta" @selected(request('role') === 'peserta')>Peserta</option>
            </select>
            <button class="button" type="submit">Filter</button>
            <a class="link-button" href="{{ route('admin.users.index') }}">Reset</a>
        </form>

        <section class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registrasi Event</th>
                        <th>Tanggal Daftar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td><strong>{{ $user->name }}</strong></td>
                            <td>{{ $user->email }}</td>
                            <td><span class="role-badge role-{{ $user->role }}">{{ ucfirst($user->role) }}</span></td>
                            <td>{{ $registrationCounts[$user->email] ?? 0 }}</td>
                            <td>{{ $user->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">Belum ada user yang sesuai filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $users->links() }}
        </section>
    </section>
@endsection
