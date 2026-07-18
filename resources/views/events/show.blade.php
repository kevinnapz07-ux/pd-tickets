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
                    <form class="public-registration-form" method="POST" action="{{ route('registrations.store', $event) }}" data-registration-confirm novalidate>
                        @csrf
                        <div class="registration-section-heading">
                            <p class="eyebrow">Registrasi</p>
                        </div>
                        <label class="registration-field" for="participant_type">
                            <span class="registration-label">Kategori Registrasi <span class="required-mark" aria-hidden="true">*</span></span>
                            <select id="participant_type" name="participant_type" required data-participant-type data-confirm-label="Kategori Registrasi" @error('participant_type') aria-invalid="true" aria-describedby="participant_type-error" @enderror>
                                @foreach ($categories as $category)
                                    <option value="{{ $category['key'] }}" @selected($selectedCategory === $category['key'])>{{ $category['label'] }}</option>
                                @endforeach
                            </select>
                            @error('participant_type') <small class="field-error" id="participant_type-error">{{ $message }}</small> @enderror
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
                                        $isRequired = (bool) ($definition['required'] ?? true);
                                        $inputType = $definition['type'] ?? 'text';
                                        $fieldId = 'registration_'.Str::slug($category['key'].'_'.$fieldName, '_');
                                        $errorId = $fieldId.'_error';
                                        $inputMode = match ($field) {
                                            'phone' => 'tel',
                                            'student_id', 'class_year' => 'numeric',
                                            'email' => 'email',
                                            default => null,
                                        };
                                        $pattern = match ($field) {
                                            'email' => '[A-Za-z0-9._%+\-]+@gmail\.com',
                                            'phone' => '(?:\+62|62|0)8[1-9][0-9]{7,11}',
                                            'class_year' => '[0-9]{4}',
                                            default => null,
                                        };
                                    @endphp

                                    <label class="registration-field" for="{{ $fieldId }}">
                                        <span class="registration-label">{{ $definition['label'] }} @if ($isRequired)<span class="required-mark" aria-hidden="true">*</span>@else<small>(opsional)</small>@endif</span>
                                        @if ($inputType === 'select')
                                            <select id="{{ $fieldId }}" name="{{ $fieldName }}" data-category-field data-confirm-label="{{ $definition['label'] }}" data-required="{{ $isRequired ? 'true' : 'false' }}" @error($fieldName) aria-invalid="true" aria-describedby="{{ $errorId }}" @enderror @disabled(! $isActive)>
                                                <option value="">Pilih {{ strtolower($definition['label']) }}</option>
                                                @foreach (($definition['options'] ?? []) as $value => $label)
                                                    <option value="{{ $value }}" @selected($oldValue === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($inputType === 'textarea')
                                            <textarea id="{{ $fieldId }}" name="{{ $fieldName }}" rows="3" placeholder="{{ $definition['placeholder'] ?? '' }}" data-category-field data-confirm-label="{{ $definition['label'] }}" data-required="{{ $isRequired ? 'true' : 'false' }}" @error($fieldName) aria-invalid="true" aria-describedby="{{ $errorId }}" @enderror @disabled(! $isActive)>{{ $oldValue }}</textarea>
                                        @else
                                            <input id="{{ $fieldId }}" type="{{ $inputType }}" name="{{ $fieldName }}" value="{{ $oldValue }}" placeholder="{{ $definition['placeholder'] ?? '' }}" @if ($inputMode) inputmode="{{ $inputMode }}" @endif @if ($pattern) pattern="{{ $pattern }}" @endif @if ($field === 'email') autocomplete="email" data-gmail-field @elseif ($field === 'phone') autocomplete="tel" data-indonesian-phone @elseif ($field === 'name') autocomplete="name" @endif data-category-field data-confirm-label="{{ $definition['label'] }}" data-required="{{ $isRequired ? 'true' : 'false' }}" @error($fieldName) aria-invalid="true" aria-describedby="{{ $errorId }}" @enderror @disabled(! $isActive)>
                                        @endif
                                        @error($fieldName) <small class="field-error" id="{{ $errorId }}">{{ $message }}</small> @enderror
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                        <button class="button button-full registration-submit" type="submit" data-registration-submit>
                            <span data-registration-submit-label>Daftar Sekarang</span>
                            <span class="button-spinner" aria-hidden="true"></span>
                        </button>
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
