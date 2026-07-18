@extends('layouts.app', ['title' => $event->title])

@section('content')
    <section class="detail">
        <article class="detail-main">
            <div class="event-detail-poster">
                @if ($event->image_url)
                    <img src="{{ $event->image_url }}" alt="Poster {{ $event->title }}" loading="lazy">
                @else
                    <img src="{{ asset('images/event-placeholder.svg') }}" alt="Poster event belum tersedia" loading="lazy">
                @endif
            </div>
            <h1>{{ $event->title }}</h1>
            <p class="event-description detail-description">{{ $event->description }}</p>

            <dl class="info-list">
                <div><dt>Pembicara</dt><dd>{{ $event->speaker ?? '-' }}</dd></div>
                <div><dt>Lokasi</dt><dd>{{ $event->location }}</dd></div>
                <div><dt>Waktu</dt><dd>{{ $event->starts_at->format('H:i') }}{{ $event->ends_at ? ' - '.$event->ends_at->format('H:i') : '' }} WIB</dd></div>
                <div><dt>Biaya</dt><dd>{{ $event->price > 0 ? 'Rp '.number_format($event->price, 0, ',', '.') : 'Gratis' }}</dd></div>
                <div><dt>Tanggal</dt><dd>{{ $event->starts_at->translatedFormat('d F Y') }}</dd></div>
            </dl>

            <div class="map-panel">
                <h2>Maps Lokasi</h2>
                <iframe
                    title="Peta {{ $event->location }}"
                    src="https://www.google.com/maps?q={{ urlencode($event->location.' Gunadarma') }}&output=embed"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </article>

        <aside class="form-panel">
            @if (! $event->registration_is_open)
                <div class="login-prompt">
                    <p>Pendaftaran event ini belum dibuka. Silakan pantau halaman event untuk informasi pembukaan registrasi.</p>
                </div>
            @else
            @guest
                <div class="login-prompt">
                    <p>Silakan login untuk melakukan registrasi.</p>
                    <a class="button button-full" href="{{ route('login', ['redirect' => request()->fullUrl()]) }}">Login</a>
                </div>
            @else
                @if (auth()->user()->role !== 'peserta')
                    <div class="error-box">Akun admin hanya dapat melihat dashboard. Gunakan akun pengguna untuk registrasi event.</div>
                @endif

                @if ($errors->any())
                    <div class="error-box">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                @if (auth()->user()->role === 'peserta')
                    @php
                        $categories = $event->registrationCategories();
                        $selectedCategory = old('participant_type', $categories[0]['key'] ?? 'umum');
                        $fieldDefinitions = \App\Models\Event::registrationFieldDefinitions();
                    @endphp
                    <form method="POST" action="{{ route('registrations.store', $event) }}" data-registration-confirm>
                        @csrf
                        <div class="registration-section-heading">
                            <p class="eyebrow">Registrasi</p>
                        </div>
                        <label>Kategori Registrasi
                            <select name="participant_type" required data-participant-type data-confirm-label="Kategori Registrasi">
                                @foreach ($categories as $category)
                                    <option value="{{ $category['key'] }}" @selected($selectedCategory === $category['key'])>{{ $category['label'] }}</option>
                                @endforeach
                            </select>
                        </label>
                        @foreach ($categories as $category)
                            <div class="category-fields" data-registration-category="{{ $category['key'] }}" aria-hidden="{{ $selectedCategory === $category['key'] ? 'false' : 'true' }}">
                                @foreach (($category['fields'] ?? []) as $field)
                                    @php
                                        $definition = $fieldDefinitions[$field] ?? [
                                            'label' => \App\Models\Event::registrationFieldLabel($field),
                                            'type' => 'text',
                                            'required' => true,
                                        ];
                                        $fieldName = array_key_exists($field, $fieldDefinitions) ? $field : $field;
                                        $oldValue = old($fieldName, $field === 'name' ? auth()->user()->name : ($field === 'email' ? auth()->user()->email : null));
                                        $isActive = $selectedCategory === $category['key'];
                                    @endphp

                                    <label>{{ $definition['label'] }}
                                        @if (($definition['type'] ?? 'text') === 'select')
                                            <select name="{{ $fieldName }}" data-category-field data-confirm-label="{{ $definition['label'] }}" data-required="{{ ($definition['required'] ?? true) ? 'true' : 'false' }}" @disabled(! $isActive)>
                                                <option value="">Pilih {{ strtolower($definition['label']) }}</option>
                                                @foreach (($definition['options'] ?? []) as $value => $label)
                                                    <option value="{{ $value }}" @selected($oldValue === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @elseif (($definition['type'] ?? 'text') === 'textarea')
                                            <textarea name="{{ $fieldName }}" rows="3" data-category-field data-confirm-label="{{ $definition['label'] }}" data-required="{{ ($definition['required'] ?? true) ? 'true' : 'false' }}" @disabled(! $isActive)>{{ $oldValue }}</textarea>
                                        @else
                                            <input type="{{ $definition['type'] ?? 'text' }}" name="{{ $fieldName }}" value="{{ $oldValue }}" placeholder="{{ $definition['placeholder'] ?? '' }}" data-category-field data-confirm-label="{{ $definition['label'] }}" data-required="{{ ($definition['required'] ?? true) ? 'true' : 'false' }}" @disabled(! $isActive)>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                        <button class="button button-full" type="submit">Daftar Sekarang</button>
                    </form>

                    <div class="registration-confirm-backdrop" data-registration-confirm-modal aria-hidden="true">
                        <section class="registration-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="registration-confirm-title" tabindex="-1">
                            <div class="registration-confirm-heading">
                                <div>
                                    <p class="eyebrow">Konfirmasi Registrasi</p>
                                    <h2 id="registration-confirm-title">Apakah data sudah sesuai?</h2>
                                </div>
                                <button class="registration-confirm-close" type="button" data-registration-confirm-close aria-label="Tutup konfirmasi">×</button>
                            </div>
                            <p class="registration-confirm-note">Periksa kembali data registrasi sebelum dikirim.</p>
                            <dl class="registration-confirm-summary" data-registration-confirm-summary></dl>
                            <div class="registration-confirm-actions">
                                <button class="link-button" type="button" data-registration-confirm-close>Periksa Kembali</button>
                                <button class="button" type="button" data-registration-confirm-submit>Ya, Daftar Sekarang</button>
                            </div>
                        </section>
                    </div>
                @endif
            @endguest
            @endif
        </aside>
    </section>
@endsection
