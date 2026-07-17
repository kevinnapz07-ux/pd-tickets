@extends('layouts.app', ['title' => $event->exists ? 'Edit Event' : 'Tambah Event'])

@section('content')
    <section class="dashboard">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>{{ $event->exists ? 'Edit Event' : 'Tambah Event' }}</h1>
            </div>
            <a class="link-button" href="{{ route('admin.events.index') }}">Kembali</a>
        </div>

        @include('admin.partials.nav')

        @if ($errors->any())
            <div class="error-box">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form class="admin-form" method="POST" action="{{ $event->exists ? route('admin.events.update', $event) : route('admin.events.store') }}">
            @csrf
            @if ($event->exists)
                @method('PUT')
            @endif
            @php
                $registrationStatus = old('registration_status', $event->exists && ! $event->registration_is_open ? 'upcoming' : 'open');
                $pricingType = old('pricing_type', ($event->price ?? 0) > 0 ? 'paid' : 'free');
            @endphp

            <label>Nama Event
                <input name="title" value="{{ old('title', $event->title) }}" required>
            </label>
            <label>Deskripsi
                <textarea name="description" rows="10" required>{{ old('description', $event->description) }}</textarea>
                <span class="field-help">Enter/baris baru yang dibuat di sini akan ikut tampil di halaman event.</span>
            </label>
            <div class="form-grid">
                <label>Pembicara
                    <input name="speaker" value="{{ old('speaker', $event->speaker) }}">
                </label>
                <label>Lokasi
                    <input name="location" value="{{ old('location', $event->location) }}" required>
                </label>
                <label>Mulai
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}" required>
                </label>
                <label>Selesai
                    <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i')) }}">
                </label>
                <label>Kuota
                    <input type="number" min="1" name="quota" value="{{ old('quota', $event->quota ?? 50) }}" required>
                </label>
                <div class="pricing-field" data-pricing-field>
                    <label>Kategori Jenis Pendaftaran
                        <select name="pricing_type" required data-pricing-type>
                            <option value="free" @selected($pricingType === 'free')>Gratis</option>
                            <option value="paid" @selected($pricingType === 'paid')>Berbayar</option>
                        </select>
                    </label>
                    <label data-price-wrapper>Nominal Pendaftaran
                        <input type="number" min="1" name="price" value="{{ old('price', $event->price > 0 ? $event->price : null) }}" placeholder="Contoh: 30000" data-price-input>
                    </label>
                </div>
                <label>Status Pendaftaran
                    <select name="registration_status" required>
                        <option value="open" @selected($registrationStatus === 'open')>Pendaftaran dibuka</option>
                        <option value="upcoming" @selected($registrationStatus === 'upcoming')>Segera dibuka</option>
                    </select>
                    <span class="field-help">Pilih Segera dibuka agar event tampil sebagai Upcoming Event dan belum bisa didaftari.</span>
                </label>
            </div>
            <label class="check-label">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $event->exists ? $event->is_published : true))>
                <span>Publish event</span>
            </label>
            <button class="button" type="submit">{{ $event->exists ? 'Simpan Perubahan' : 'Buat Event' }}</button>
        </form>
    </section>
@endsection
