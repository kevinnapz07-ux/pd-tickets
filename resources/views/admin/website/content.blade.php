    <section class="dashboard">
        @if ($errors->any())
            <div class="error-box">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form class="admin-form" method="POST" action="{{ route('admin.website.update') }}" data-website-settings-form>
            @csrf
            @method('PATCH')
            <nav class="cms-tabs" role="tablist" aria-label="Bagian pengaturan website">
                <button type="button" role="tab" aria-selected="true" aria-controls="cms-panel-identity" data-cms-tab="identity">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11 12 3l9 8v10h-6v-6H9v6H3Z" /></svg><span>Identitas Website</span>
                </button>
                <button type="button" role="tab" aria-selected="false" aria-controls="cms-panel-home" data-cms-tab="home">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2" /><path d="m3 15 5-5 4 4 3-3 6 6" /></svg><span>Halaman Utama</span>
                </button>
                <button type="button" role="tab" aria-selected="false" aria-controls="cms-panel-contact" data-cms-tab="contact">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 2.1 4.18 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92Z" /></svg><span>Kontak Publik</span>
                </button>
                <button type="button" role="tab" aria-selected="false" aria-controls="cms-panel-registration" data-cms-tab="registration">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h14v18H5zM8 7h8M8 11h8M8 15h5" /></svg><span>Registrasi Event</span>
                </button>
                <button type="button" role="tab" aria-selected="false" aria-controls="cms-panel-footer" data-cms-tab="footer">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h18v14H3zM3 15h18M7 18h.01M10 18h.01" /></svg><span>Footer Website</span>
                </button>
            </nav>
            <label class="cms-tab-select-label" for="cms-tab-select">Bagian Pengaturan</label>
            <select class="cms-tab-select" id="cms-tab-select" data-cms-tab-select>
                <option value="identity">Identitas Website</option>
                <option value="home">Halaman Utama</option>
                <option value="contact">Kontak Publik</option>
                <option value="registration">Registrasi Event</option>
                <option value="footer">Footer Website</option>
            </select>

            <section class="cms-tab-panel" id="cms-panel-identity" role="tabpanel" data-cms-panel="identity">
            <div class="admin-form-section website-settings-section">
                <div class="admin-form-section-heading">
                    <div>
                        <p class="eyebrow">Identitas Website</p>
                        <h2>Informasi Utama</h2>
                        <p class="field-help">Nama dan tagline digunakan pada identitas website serta navigasi publik.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <label>Nama Website <span class="required-indicator">*</span>
                        <input name="site_name" value="{{ old('site_name', $setting->site_name) }}" maxlength="100" data-preview-source="site-name" required>
                        <small class="field-help">Nama yang ditampilkan pada navbar dan footer.</small>
                        <small class="character-counter" data-character-counter></small>
                    </label>
                    <label>Tagline <span class="required-indicator">*</span>
                        <input name="site_tagline" value="{{ old('site_tagline', $setting->site_tagline) }}" maxlength="120" data-preview-source="site-tagline" required>
                        <small class="field-help">Kalimat singkat yang muncul di bawah nama website.</small>
                        <small class="character-counter" data-character-counter></small>
                    </label>
                </div>
                <aside class="cms-preview identity-preview" aria-label="Preview identitas website">
                    <p class="eyebrow">Preview Navbar</p>
                    <div><img class="brand-mark brand-logo" src="{{ asset(config('branding.logo')) }}" alt=""><span><strong data-preview-target="site-name"></strong><small data-preview-target="site-tagline"></small></span></div>
                </aside>
            </div>
            </section>

            <section class="cms-tab-panel" id="cms-panel-home" role="tabpanel" data-cms-panel="home" hidden>
            <div class="admin-form-section website-settings-section">
                <div class="admin-form-section-heading">
                    <div>
                        <p class="eyebrow">Halaman Utama</p>
                        <h2>Konten Hero</h2>
                        <p class="field-help">Teks utama yang pertama kali dilihat pengunjung pada halaman event.</p>
                    </div>
                </div>
                <label>Judul Hero <span class="required-indicator">*</span>
                    <input name="hero_title" value="{{ old('hero_title', $setting->hero_title) }}" maxlength="120" data-preview-source="hero-title" required>
                    <small class="field-help">Judul utama pada area pembuka halaman event.</small>
                    <small class="character-counter" data-character-counter></small>
                </label>
                <label>Deskripsi Hero <span class="required-indicator">*</span>
                    <textarea name="hero_subtitle" rows="4" data-preview-source="hero-subtitle" required>{{ old('hero_subtitle', $setting->hero_subtitle) }}</textarea>
                    <small class="field-help">Ringkasan singkat mengenai event dan layanan website.</small>
                    <small class="character-counter" data-character-counter></small>
                </label>
                <aside class="cms-preview hero-preview" aria-label="Preview hero halaman utama">
                    <p class="eyebrow">Preview Hero</p><h3 data-preview-target="hero-title"></h3><p data-preview-target="hero-subtitle"></p>
                </aside>
            </div>
            </section>

            <section class="cms-tab-panel" id="cms-panel-contact" role="tabpanel" data-cms-panel="contact" hidden>
            <div class="admin-form-section website-settings-section">
                <div class="admin-form-section-heading">
                    <div>
                        <p class="eyebrow">Kontak Publik</p>
                        <h2>Informasi Kontak PDUG</h2>
                        <p class="field-help">Informasi ini ditampilkan pada halaman About Us dan footer website.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <label>Email <span class="required-indicator">*</span>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $setting->contact_email) }}" maxlength="120" placeholder="nama@organisasi.org" data-preview-source="contact-email" required>
                        <small class="field-help">Alamat email yang digunakan sebagai kontak resmi.</small>
                    </label>
                    <label>Nomor WhatsApp <span class="required-indicator">*</span>
                        <input name="contact_phone" value="{{ old('contact_phone', $setting->contact_phone) }}" maxlength="40" placeholder="+62 812-3456-7890" data-preview-source="contact-phone" required>
                        <small class="field-help">Nomor layanan yang dapat dihubungi oleh peserta.</small>
                    </label>
                </div>
                <label>Lokasi <span class="required-indicator">*</span>
                    <input name="contact_address" value="{{ old('contact_address', $setting->contact_address) }}" maxlength="180" placeholder="Kampus Universitas Gunadarma" data-preview-source="contact-address" required>
                    <small class="field-help">Lokasi singkat yang ditampilkan pada informasi publik.</small>
                </label>
                <aside class="cms-preview contact-preview" aria-label="Preview kontak publik">
                    <p class="eyebrow">Preview Kontak</p>
                    <dl><div><dt>Email</dt><dd data-preview-target="contact-email"></dd></div><div><dt>WhatsApp</dt><dd data-preview-target="contact-phone"></dd></div><div><dt>Lokasi</dt><dd data-preview-target="contact-address"></dd></div></dl>
                </aside>
            </div>
            </section>

            <section class="cms-tab-panel" id="cms-panel-footer" role="tabpanel" data-cms-panel="footer" hidden>
                <div class="admin-form-section website-settings-section">
                    <div class="admin-form-section-heading"><div><p class="eyebrow">Footer Website</p><h2>Preview Footer</h2><p class="field-help">Footer tidak memiliki field terpisah. Seluruh informasinya otomatis mengikuti data utama agar tidak perlu diisi dua kali.</p></div></div>
                    <div class="footer-data-sources" aria-label="Sumber data footer">
                        <article>
                            <div><span class="footer-source-icon" aria-hidden="true">I</span><div><strong>Identitas Website</strong><p>Nama website dan tagline.</p></div></div>
                            <button class="link-button" type="button" data-cms-open-tab="identity">Edit identitas</button>
                        </article>
                        <article>
                            <div><span class="footer-source-icon" aria-hidden="true">K</span><div><strong>Kontak Publik</strong><p>Email, WhatsApp, dan lokasi.</p></div></div>
                            <button class="link-button" type="button" data-cms-open-tab="contact">Edit kontak</button>
                        </article>
                    </div>
                    <aside class="cms-preview footer-preview" aria-label="Preview footer website">
                        <div class="footer-preview-label"><span>Pratinjau</span><small>Data tersinkron otomatis</small></div>
                        <div class="footer-preview-brand"><img class="brand-mark brand-logo" src="{{ asset(config('branding.logo')) }}" alt=""><div><strong data-preview-target="site-name"></strong><small data-preview-target="site-tagline"></small></div></div>
                        <dl><div><dt>Email <span>Dari Kontak Publik</span></dt><dd data-preview-target="contact-email"></dd></div><div><dt>WhatsApp <span>Dari Kontak Publik</span></dt><dd data-preview-target="contact-phone"></dd></div><div><dt>Lokasi <span>Dari Kontak Publik</span></dt><dd data-preview-target="contact-address"></dd></div></dl>
                        <p>&copy; {{ now()->year }} PDUG&trade;. All Rights Reserved.</p>
                    </aside>
                </div>
            </section>
            <div class="website-save-bar">
                <p data-form-change-status>Semua perubahan yang tersimpan akan diterapkan ke website.</p>
                <div><button class="link-button" type="reset" data-settings-reset>Reset</button><button class="button" type="submit" data-settings-submit><span>Simpan Perubahan</span><span class="button-loading" aria-hidden="true"></span></button></div>
            </div>

            <section class="cms-tab-panel" id="cms-panel-registration" role="tabpanel" data-cms-panel="registration" hidden>
            <div class="admin-form-section">
                @php
                    $fieldDefinitions = \App\Models\Event::registrationFieldDefinitions();
                    $defaultCategoryOptions = collect(\App\Models\Event::defaultRegistrationCategories())
                        ->push([
                            'key' => 'kategori_tambahan_1',
                            'label' => 'Kategori Tambahan 1',
                            'fields' => ['name', 'email', 'phone'],
                        ])
                        ->push([
                            'key' => 'kategori_tambahan_2',
                            'label' => 'Kategori Tambahan 2',
                            'fields' => ['name', 'email', 'phone'],
                        ])
                        ->keyBy('key');
                @endphp
                <div>
                    <p class="eyebrow">Pengaturan Form Registrasi Event<span class="sr-only"> - Kategori Peserta per Event</span></p>
                    <h2>Konfigurasi Form Registrasi</h2>
                    <p class="field-help">Atur kategori dan tentukan informasi yang wajib diisi pada formulir registrasi setiap event.</p>
                </div>
                <div class="schema-overview" aria-label="Ringkasan konfigurasi event">
                    <span><strong>{{ $events->count() }}</strong> event tersedia</span>
                    <span>Maksimal <strong>4</strong> kategori per event</span>
                </div>
                <div class="event-schema-list">
                    @foreach ($events as $event)
                        @php
                            $currentCategories = collect($event->registrationCategories())->keyBy('key');
                            $categoryOptions = $defaultCategoryOptions->merge($currentCategories);
                        @endphp
                        <details class="event-schema-card" @if ($loop->first) open @endif>
                            <summary class="event-schema-heading">
                                <div>
                                    <strong>{{ $event->title }}</strong>
                                    <span>{{ $event->starts_at->translatedFormat('d M Y') }} - {{ $event->registration_is_open ? 'Registrasi dibuka' : 'Segera dibuka' }}</span>
                                </div>
                                <span class="event-schema-summary-action" aria-hidden="true">Buka Pengaturan</span>
                            </summary>
                            <div class="event-schema-body">
                                <div class="schema-content-heading is-compact">
                                    <h3>Kategori Peserta</h3>
                                    <p>Pilih kategori untuk membuka pengaturannya. Pilih kembali untuk menutup.</p>
                                </div>
                                <div class="schema-category-tabs" role="tablist" aria-label="Kategori peserta {{ $event->title }}">
                                @foreach ($categoryOptions as $categoryKey => $category)
                                    @php
                                        $isAdditionalCategory = str_starts_with($categoryKey, 'kategori_tambahan_');
                                        $isCategoryEnabled = $currentCategories->has($categoryKey);
                                    @endphp
                                    <button
                                        class="schema-category-tab {{ $isAdditionalCategory ? 'is-additional' : '' }}"
                                        type="button"
                                        role="tab"
                                        aria-selected="false"
                                        aria-controls="schema-category-{{ $event->id }}-{{ $categoryKey }}"
                                        data-schema-category-tab
                                        @if ($isAdditionalCategory) data-additional-category @endif
                                        @if ($isAdditionalCategory && ! $isCategoryEnabled) hidden @endif
                                    >
                                        <span
                                            class="schema-category-status {{ $isCategoryEnabled ? 'is-enabled' : '' }}"
                                            data-schema-category-status
                                            aria-hidden="true"
                                        ></span>
                                        <span data-schema-category-label>{{ $category['label'] }}</span>
                                        <span class="sr-only">{{ $isCategoryEnabled ? 'aktif' : 'nonaktif' }}</span>
                                    </button>
                                    @if ($isAdditionalCategory)
                                        <button
                                            class="schema-remove-category"
                                            type="button"
                                            aria-controls="schema-category-{{ $event->id }}-{{ $categoryKey }}"
                                            aria-label="Batalkan {{ $category['label'] }}"
                                            title="Batalkan kategori tambahan"
                                            data-remove-category
                                            @if (! $isCategoryEnabled) hidden @endif
                                        >
                                            <span aria-hidden="true">X</span>
                                        </button>
                                    @endif
                                @endforeach
                                <button
                                    class="schema-add-category"
                                    type="button"
                                    data-add-category
                                    @if ($currentCategories->keys()->filter(fn ($key) => str_starts_with($key, 'kategori_tambahan_'))->count() >= 2) hidden @endif
                                >
                                    <span aria-hidden="true">+</span>
                                    Tambah Kategori
                                </button>
                                </div>
                                <div class="schema-category-list">
                                @foreach ($categoryOptions as $categoryKey => $category)
                                    @php
                                        $selectedFields = collect($category['fields'] ?? []);
                                        $customFields = $selectedFields
                                            ->reject(fn ($field) => array_key_exists($field, $fieldDefinitions))
                                            ->values();
                                    @endphp
                                    <article
                                        class="schema-category-card"
                                        id="schema-category-{{ $event->id }}-{{ $categoryKey }}"
                                        role="tabpanel"
                                        hidden
                                        data-schema-category-panel
                                    >
                                        <div class="schema-panel-heading">
                                            <div>
                                                <p class="eyebrow">Kategori yang Sedang Diedit</p>
                                                <h3 data-schema-panel-title>{{ $category['label'] }}</h3>
                                                <p class="schema-category-status-help">Status menentukan apakah kategori ini tersedia pada form registrasi peserta.</p>
                                            </div>
                                            <label class="schema-category-toggle" title="Ubah status kategori">
                                                <input
                                                    type="checkbox"
                                                    name="event_registration_builder[{{ $event->id }}][categories][{{ $categoryKey }}][enabled]"
                                                    value="1"
                                                    @checked($currentCategories->has($categoryKey))
                                                >
                                                <span data-category-toggle-label>{{ $currentCategories->has($categoryKey) ? 'Kategori Aktif' : 'Kategori Nonaktif' }}</span>
                                            </label>
                                        </div>
                                        <label class="schema-category-name">Nama kategori
                                            <input
                                                name="event_registration_builder[{{ $event->id }}][categories][{{ $categoryKey }}][label]"
                                                value="{{ $category['label'] }}"
                                            >
                                        </label>
                                        <div class="schema-content-heading">
                                            <h4>Field Registrasi</h4>
                                            <p>Pilih data yang perlu dilengkapi peserta pada kategori ini.</p>
                                        </div>
                                        <div class="schema-field-buttons">
                                            @foreach ($fieldDefinitions as $field => $definition)
                                                <label class="schema-field-button">
                                                    <input
                                                        type="checkbox"
                                                        name="event_registration_builder[{{ $event->id }}][categories][{{ $categoryKey }}][fields][]"
                                                        value="{{ $field }}"
                                                        data-preview-label="{{ $definition['label'] }}"
                                                        data-primary-field="{{ in_array($field, ['name', 'email', 'phone'], true) ? 'true' : 'false' }}"
                                                        @checked($selectedFields->contains($field))
                                                    >
                                                    <span>
                                                        {{ $definition['label'] }}
                                                        @if (in_array($field, ['name', 'email', 'phone'], true))
                                                            <small>WAJIB</small>
                                                        @endif
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <div class="schema-custom-field">
                                            <div class="schema-custom-field-heading">
                                                <div>
                                                    <strong>Field Tambahan<span class="sr-only"> - Data tambahan</span></strong>
                                                    <p>Tambahkan pertanyaan khusus yang hanya diperlukan pada kategori ini.</p>
                                                </div>
                                                <button
                                                    type="button"
                                                    data-add-custom-field
                                                    data-field-name="event_registration_builder[{{ $event->id }}][categories][{{ $categoryKey }}][custom_fields][]"
                                                    aria-label="Tambah data tambahan"
                                                    title="Tambah data tambahan"
                                                >
                                                    <span aria-hidden="true">+</span>
                                                    Tambah Field
                                                </button>
                                            </div>
                                            <div class="schema-custom-field-list" data-custom-field-list>
                                                @foreach ($customFields as $customField)
                                                    <div class="schema-custom-field-row">
                                                        <input
                                                            name="event_registration_builder[{{ $event->id }}][categories][{{ $categoryKey }}][custom_fields][]"
                                                            value="{{ $customField }}"
                                                            placeholder="Contoh: asal_gereja"
                                                            data-custom-preview-field
                                                        >
                                                        <button type="button" data-remove-custom-field>Hapus</button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <section class="schema-form-preview" data-form-preview aria-label="Preview Form Registrasi">
                                            <div class="schema-content-heading">
                                                <h4>Preview Form Registrasi</h4>
                                                <p>Pratinjau tampilan field untuk peserta.</p>
                                            </div>
                                            <div class="schema-preview-fields" data-preview-fields></div>
                                        </section>
                                    </article>
                                @endforeach
                                </div>
                            </div>
                        </details>
                    @endforeach
                </div>
            </section>
            </section>

        </form>
    </section>
