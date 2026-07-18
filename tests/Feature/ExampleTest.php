<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminReportController;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_listing_returns_a_successful_response(): void
    {
        Event::create([
            'title' => 'Workshop Laravel',
            'description' => 'Belajar membangun aplikasi event dengan Laravel.',
            'speaker' => 'PD Gunadarma',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 40,
            'price' => 0,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Workshop Laravel');
        $response->assertSee('Login');
        $response->assertSee('PD Gunadarma Event');
        $response->assertSee('Informasi dan Registrasi Event');
        $response->assertSee('<title>Beranda • PD Gunadarma Event</title>', false);
        $response->assertSee('Website resmi registrasi event Persekutuan Doa Universitas Gunadarma.');
        $response->assertSee('images/pd-gunadarma-event.svg');
        $response->assertSee('About Us');
        $response->assertSee('Follow Us');
        $response->assertSee('pdgunadarmates@gmail.com');
        $response->assertSee('+62 812-3456-7890');
        $response->assertSee('Universitas Gunadarma, Depok');
        $response->assertSee('Jam Layanan');
        $response->assertSee('Cara Registrasi Event');
        $response->assertSee('Cara Daftar');
        $response->assertSee('UKM Kerohanian Universitas Gunadarma');
        $response->assertDontSee('class="button hero-cta"', false);
        $response->assertSee('>Cari</button>', false);
        $response->assertSee('Tanggal');
        $response->assertSee('Kuota');
        $response->assertSee('Masuk atau Daftar');
        $response->assertSee('Isi Data Registrasi');
        $response->assertSee('Selesaikan Registrasi');
        $response->assertSee('Dapatkan Tiket');
        $response->assertSee('aria-label="Tutup"', false);
        $response->assertSee('Senin-Jumat');
        $response->assertSee('09.00-17.00 WIB');
        $response->assertDontSee('<small>UG</small>', false);
        $response->assertDontSee('Kursi Tersedia per Event');
        $response->assertDontSee('Daftar Peserta');
        $response->assertDontSee('Buat Akun Peserta');
        $response->assertDontSee('Pengumuman');
    }

    public function test_homepage_event_card_keeps_title_and_cleans_description_prefix(): void
    {
        Event::create([
            'title' => 'Ibadah Raya Jumat',
            'description' => " '', Deskripsi event yang bersih.",
            'speaker' => 'PD Gunadarma',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'quota' => 80,
            'price' => 15000,
        ]);

        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee('<h3>Ibadah Raya Jumat</h3>', false)
            ->assertSee('Deskripsi event yang bersih.')
            ->assertDontSee(" '', Deskripsi event yang bersih.")
            ->assertSee('public-event-rail is-single', false);
    }

    public function test_about_us_page_is_concise_and_hides_dummy_contact_data(): void
    {
        $response = $this->get(route('profile.pdug'));

        $response->assertOk();
        $response->assertSee('About Us');
        $response->assertSee('Cerita Kami');
        $response->assertSee('Visi Global');
        $response->assertSee('Visi Generasi');
        $response->assertSee('Gunadarma dibakar habis oleh api kemuliaan-Nya.');
        $response->assertSee('The Army of God');
        $response->assertSee('Kegiatan Kami');
        $response->assertSee('Nantikan informasi kegiatan terbaru dari PDUG.');
        $response->assertDontSee('Kabar &amp; Renungan', false);
        $response->assertDontSee('Misi');
        $response->assertDontSee('Bertumbuh, Bersekutu, dan Melayani');
        $response->assertSee('Ada yang bisa kami bantu?');
        $response->assertSee('Hubungi Kami');
        $response->assertSee('Email PDUG');
        $response->assertSee('Instagram PDUG');
        $response->assertDontSee('Kirim Pesan');
        $response->assertDontSee('Pilih topik');
        $response->assertDontSee('pdug@gunadarma.test');
        $response->assertDontSee('021-0000-0000');
        $response->assertSee('pdgunadarmates@gmail.com');
        $response->assertSee('+62 812-3456-7890');
    }

    public function test_configured_public_contact_is_rendered_on_about_page_and_footer(): void
    {
        SiteSetting::current()->update([
            'site_tagline' => 'UKM Kerohanian Universitas gunadarma',
            'contact_email' => 'halo@pdug.org',
            'contact_phone' => '+62 811-2222-3333',
            'contact_address' => 'Kampus D Universitas Gunadarma',
        ]);

        $aboutResponse = $this->get(route('profile.pdug'));
        $homeResponse = $this->get(route('events.index'));

        $aboutResponse->assertOk();
        $aboutResponse->assertSee('halo@pdug.org');
        $aboutResponse->assertSee('+62 811-2222-3333');
        $aboutResponse->assertSee('Kampus D Universitas Gunadarma');
        $homeResponse->assertOk();
        $homeResponse->assertSee('halo@pdug.org');
        $homeResponse->assertSee('+62 811-2222-3333');
        $homeResponse->assertSee('Kampus D Universitas Gunadarma');
        $homeResponse->assertSee('UKM Kerohanian Universitas Gunadarma');
        $homeResponse->assertDontSee('UKM Kerohanian Universitas gunadarma');
    }

    public function test_upcoming_events_are_separate_information_only_items(): void
    {
        Event::create([
            'title' => 'Event Aktif Test',
            'description' => 'Event yang sudah dapat didaftari.',
            'speaker' => 'PD Gunadarma',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 40,
            'price' => 0,
            'registration_is_open' => true,
        ]);

        Event::create([
            'title' => 'Event Segera Dibuka',
            'description' => "Event yang baru berupa informasi awal.\nBaris kedua tetap rapi.",
            'speaker' => 'PD Gunadarma',
            'location' => 'Kampus E',
            'starts_at' => now()->addWeeks(3),
            'ends_at' => now()->addWeeks(3)->addHours(2),
            'quota' => 80,
            'price' => 0,
            'registration_is_open' => false,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeInOrder(['Event Tersedia', 'Event Aktif Test', 'Event Mendatang', 'Event Segera Dibuka']);
        $response->assertSee('Pendaftaran Dibuka');
        $response->assertSee('class="event-description"', false);
        $response->assertSee("Event yang baru berupa informasi awal.\nBaris kedua tetap rapi.");
        $response->assertSee('Pendaftaran Segera Dibuka');
    }

    public function test_login_page_is_single_entry_for_admin_and_participant(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Login');
        $response->assertSee('Daftar akun');
        $response->assertSee('Lupa password?');
        $response->assertSee('data-password-toggle', false);
        $response->assertDontSee('Jenis Akun');
        $response->assertDontSee('Masuk Akun');
        $response->assertDontSee('Masuk menggunakan email dan password');
        $response->assertDontSee('Akun Demo');
    }

    public function test_register_page_uses_simple_title_and_password_toggles(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('<h1 class="auth-title">Daftar Akun</h1>', false);
        $response->assertSee('<title>Daftar Akun • PD Gunadarma Event</title>', false);
        $response->assertSee('data-password-toggle', false);
        $response->assertDontSee('Daftar Peserta');
    }

    public function test_participant_can_register_an_account(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Peserta Baru',
            'email' => 'peserta.baru@gmail.com',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ]);

        $response->assertRedirect(route('events.index'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'peserta.baru@gmail.com',
            'role' => 'peserta',
        ]);
    }

    public function test_registration_only_accepts_gmail_addresses(): void
    {
        $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Peserta Non Gmail',
            'email' => 'peserta@example.com',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertRedirect(route('register'))
            ->assertSessionHasErrors(['email' => 'Gunakan alamat Gmail yang valid.']);

        $this->assertDatabaseMissing('users', ['email' => 'peserta@example.com']);
    }

    public function test_free_event_registration_is_marked_as_paid(): void
    {
        $participant = User::forceCreate([
            'name' => 'Peserta Test',
            'email' => 'peserta-test@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        $event = Event::create([
            'title' => 'Career Talk',
            'description' => 'Sesi karier untuk mahasiswa Gunadarma.',
            'speaker' => 'Alumni Gunadarma',
            'location' => 'Kampus H',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 80,
            'price' => 0,
        ]);

        $response = $this->actingAs($participant)->post(route('registrations.store', $event), [
            'name' => 'Kevin Nugraha',
            'email' => 'kevin.registration@gmail.com',
            'participant_type' => 'mahasiswa_gunadarma',
            'phone' => '081234567890',
            'student_id' => '50426000',
            'campus_area' => 'depok',
            'class_year' => '2024',
            'study_program' => 'Sistem Informasi',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('registrations', [
            'email' => 'kevin.registration@gmail.com',
            'user_id' => $participant->id,
            'participant_type' => 'mahasiswa_gunadarma',
            'campus_area' => 'depok',
            'class_year' => '2024',
            'study_program' => 'Sistem Informasi',
            'payment_status' => 'paid',
            'registration_status' => 'registered',
        ]);

        $registration = Registration::where('email', 'kevin.registration@gmail.com')->first();

        $this->get(route('registrations.show', $registration))
            ->assertOk()
            ->assertSee('registration-detail-layout', false)
            ->assertSee('Career Talk')
            ->assertSee('Kampus H')
            ->assertSee('Maps Lokasi')
            ->assertSee('<dt>Tanggal</dt>', false)
            ->assertSee('Gratis')
            ->assertSee('QR Check-in')
            ->assertDontSee('Lunas')
            ->assertDontSee('Terdaftar')
            ->assertDontSee('Siap Check-in')
            ->assertDontSee('Status Transaksi')
            ->assertDontSee('Order ID');
    }

    public function test_general_participant_registration_stores_gender_and_domicile(): void
    {
        $participant = User::forceCreate([
            'name' => 'Peserta Umum',
            'email' => 'peserta-umum@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        $event = Event::create([
            'title' => 'Ibadah Umum',
            'description' => 'Event untuk peserta umum.',
            'speaker' => 'PD Gunadarma',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 80,
            'price' => 0,
        ]);

        $response = $this->actingAs($participant)->post(route('registrations.store', $event), [
            'participant_type' => 'umum',
            'name' => 'Peserta Umum',
            'email' => 'umum.registration@gmail.com',
            'phone' => '081234567890',
            'gender' => 'laki_laki',
            'domicile' => 'Depok',
        ]);

        $response->assertRedirect();

        $registration = Registration::where('email', 'umum.registration@gmail.com')->first();

        $this->assertSame('laki_laki', $registration->custom_fields['gender']);
        $this->assertSame('Depok', $registration->custom_fields['domicile']);
    }

    public function test_registration_details_require_authentication(): void
    {
        $event = Event::create([
            'title' => 'Event Privat',
            'description' => 'Detail registrasi tidak boleh terbuka untuk publik.',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'quota' => 40,
            'price' => 0,
        ]);

        $registration = Registration::create([
            'event_id' => $event->id,
            'registration_code' => 'PDG-PRIVATE-001',
            'name' => 'Peserta Privat',
            'email' => 'private@example.com',
            'phone' => '081234567890',
            'payment_status' => 'paid',
            'registration_status' => 'registered',
        ]);

        $this->get(route('registrations.show', $registration))
            ->assertRedirect(route('login'));
    }

    public function test_registration_details_are_limited_to_owner_and_admin(): void
    {
        $owner = User::factory()->create(['role' => 'peserta']);
        $otherParticipant = User::factory()->create(['role' => 'peserta']);
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::create([
            'title' => 'Event Pemilik',
            'description' => 'Detail hanya untuk pemilik dan admin.',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'quota' => 40,
            'price' => 0,
        ]);
        $registration = Registration::create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'registration_code' => 'PDG-OWNER-001',
            'name' => $owner->name,
            'email' => $owner->email,
            'phone' => '081234567890',
            'payment_status' => 'paid',
            'registration_status' => 'registered',
        ]);

        $this->actingAs($owner)
            ->get(route('registrations.show', $registration))
            ->assertOk();

        $this->actingAs($otherParticipant)
            ->get(route('registrations.show', $registration))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('registrations.show', $registration))
            ->assertOk();
    }

    public function test_registration_is_blocked_when_event_is_not_open_yet(): void
    {
        $participant = User::forceCreate([
            'name' => 'Peserta Test',
            'email' => 'peserta-upcoming@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        $event = Event::create([
            'title' => 'Upcoming Info',
            'description' => 'Event belum dibuka.',
            'speaker' => 'PD Gunadarma',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeeks(2),
            'ends_at' => now()->addWeeks(2)->addHours(2),
            'quota' => 40,
            'price' => 0,
            'registration_is_open' => false,
        ]);

        $response = $this->actingAs($participant)->post(route('registrations.store', $event), [
            'name' => 'Kevin Nugraha',
            'email' => 'kevin-upcoming@example.com',
            'phone' => '081234567890',
        ]);

        $response->assertSessionHasErrors('event');
        $this->assertDatabaseMissing('registrations', [
            'email' => 'kevin-upcoming@example.com',
        ]);
    }

    public function test_event_registration_form_has_participant_categories(): void
    {
        $participant = User::forceCreate([
            'name' => 'Peserta Form',
            'email' => 'peserta-form@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        $event = Event::create([
            'title' => 'Form Kategori',
            'description' => 'Event cek kategori.',
            'speaker' => 'PD Gunadarma',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 40,
            'price' => 0,
        ]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Silakan login untuk melanjutkan. Registrasi event tersedia untuk akun peserta.')
            ->assertDontSee('Silakan login sebagai peserta untuk melakukan registrasi event.');

        $response = $this->actingAs($participant)->get(route('events.show', $event));

        $response->assertOk();
        $response->assertSee('Kategori Registrasi');
        $response->assertSee('Umum');
        $response->assertSee('Mahasiswa Universitas Gunadarma');
        $response->assertSee('No. HP (WhatsApp)');
        $response->assertSee('Jenis Kelamin');
        $response->assertSee('Domisili');
        $response->assertSee('Area Kampus');
        $response->assertSee('Angkatan');
        $response->assertSee('Program Studi');
        $response->assertSee('Registrasi');
        $response->assertSee('data-registration-confirm', false);
        $response->assertSee('Apakah data sudah sesuai?');
        $response->assertSee('Periksa Kembali');
        $response->assertSee('Ya, Daftar Sekarang');
        $response->assertDontSee('Pilih kategori peserta agar form menampilkan data yang sesuai.');
        $response->assertSee('<dt>Tanggal</dt>', false);
        $response->assertSee($event->starts_at->translatedFormat('d F Y'));
        $response->assertDontSee('<dt>Kuota</dt>', false);
        $response->assertDontSee('<p class="eyebrow">'.$event->starts_at->translatedFormat('l, d F Y').'</p>', false);
        $response->assertDontSee('<h2>Registrasi Peserta</h2>', false);
    }

    public function test_admin_can_configure_event_registration_categories_from_website_settings(): void
    {
        $admin = User::forceCreate([
            'name' => 'Admin Schema',
            'email' => 'admin-schema@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $participant = User::forceCreate([
            'name' => 'Peserta Schema',
            'email' => 'peserta-schema@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        SiteSetting::current();

        $event = Event::create([
            'title' => 'Event Schema',
            'description' => 'Event dengan schema custom.',
            'speaker' => 'PD Gunadarma',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 40,
            'price' => 0,
        ]);

        $editResponse = $this->actingAs($admin)->get(route('admin.website.edit'));

        $editResponse->assertOk();
        $editResponse->assertSee('Kategori Peserta per Event');
        $editResponse->assertSee('Kategori Tambahan 1');
        $editResponse->assertSee('No. HP (WhatsApp)');
        $editResponse->assertSee('Data tambahan');
        $editResponse->assertSee('Tambah Field');
        $editResponse->assertDontSee('Judul Bagian');
        $editResponse->assertDontSee('Deskripsi Bagian');
        $editResponse->assertDontSee('registration_section_title', false);
        $editResponse->assertDontSee('registration_section_description', false);

        $response = $this->actingAs($admin)->patch(route('admin.website.update'), [
            'site_name' => 'PD Gunadarma',
            'site_tagline' => 'Event Registration',
            'hero_title' => 'PDUG',
            'hero_subtitle' => 'Hero subtitle.',
            'contact_email' => 'admin@pdug.test',
            'contact_phone' => '082199773846',
            'contact_address' => 'Kampus Gunadarma',
            'event_registration_builder' => [
                $event->id => [
                    'categories' => [
                        'kategori_tambahan_1' => [
                            'enabled' => '1',
                            'label' => 'Jemaat',
                            'fields' => ['name', 'email', 'phone'],
                            'custom_fields' => ['asal_gereja'],
                        ],
                        'mahasiswa_gunadarma' => [
                            'enabled' => '1',
                            'label' => 'Mahasiswa UG',
                            'fields' => ['name', 'email', 'phone', 'student_id', 'campus_area'],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();

        $formResponse = $this->actingAs($participant)->get(route('events.show', $event->fresh()));

        $formResponse->assertOk();
        $formResponse->assertSee('Registrasi');
        $formResponse->assertDontSee('Data Peserta Custom');
        $formResponse->assertDontSee('Isi sesuai kategori.');
        $formResponse->assertSee('Jemaat');
        $formResponse->assertSee('Asal Gereja');

        $registrationResponse = $this->actingAs($participant)->post(route('registrations.store', $event), [
            'participant_type' => 'kategori_tambahan_1',
            'name' => 'Peserta Schema',
            'email' => 'schema.registration@gmail.com',
            'phone' => '081234567890',
            'asal_gereja' => 'GKI Test',
        ]);

        $registrationResponse->assertRedirect();
        $this->assertDatabaseHas('registrations', [
            'event_id' => $event->id,
            'email' => 'schema.registration@gmail.com',
            'participant_type' => 'kategori_tambahan_1',
        ]);
        $this->assertSame('GKI Test', Registration::where('email', 'schema.registration@gmail.com')->first()->custom_fields['asal_gereja']);
    }

    public function test_participant_cannot_access_admin_dashboard(): void
    {
        $participant = User::forceCreate([
            'name' => 'Peserta Test',
            'email' => 'peserta-dashboard@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($participant)->get(route('filament.admin.pages.dashboard'));

        $response->assertRedirect(route('events.index'));
    }

    public function test_admin_can_access_filament_panel(): void
    {
        $admin = User::forceCreate([
            'name' => 'Admin Filament',
            'email' => 'admin-filament@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Kelola Event');
        $response->assertSee('Kelola Pengguna');
        $response->assertSee('Transaksi');
        $response->assertSee('Dashboard Admin PDUG');
        $response->assertSee('Laporan');
        $response->assertSee('Pengaturan Website');
        $response->assertSee('PD Gunadarma Event');
        $response->assertSee('Pusat Administrasi');
        $response->assertSee('images/pd-gunadarma-event.svg');
        $response->assertSee('PD Gunadarma Event | Pusat Administrasi');
        $response->assertSee('pdug-admin-brand-collapsed', false);
        $response->assertDontSee('Kelola Users');
        $response->assertDontSee('Kelola Website Custom');
    }

    public function test_participant_cannot_access_filament_panel(): void
    {
        $participant = User::forceCreate([
            'name' => 'Peserta Filament',
            'email' => 'peserta-filament@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($participant)->get('/admin');

        $response->assertRedirect(route('events.index'));
    }

    public function test_admin_login_through_shared_login_opens_admin_dashboard(): void
    {
        $admin = User::forceCreate([
            'name' => 'Admin Test',
            'email' => 'admin-test@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'admin-test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('filament.admin.pages.dashboard'));
        $response->assertSessionHas('status');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_participant_login_can_return_to_event_detail(): void
    {
        User::forceCreate([
            'name' => 'Peserta Test',
            'email' => 'peserta-login@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        $event = Event::create([
            'title' => 'Workshop Registrasi',
            'description' => 'Workshop untuk peserta.',
            'speaker' => 'PD Gunadarma',
            'location' => 'Kampus E',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 40,
            'price' => 0,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'peserta-login@example.com',
            'password' => 'password',
            'redirect' => route('events.show', $event),
        ]);

        $response->assertRedirect(route('events.show', $event));
        $response->assertSessionHas('status', 'Login berhasil. Selamat datang, Peserta Test.');
    }

    public function test_participant_homepage_shows_welcome_in_hero(): void
    {
        $participant = User::forceCreate([
            'name' => 'Peserta Hero',
            'email' => 'peserta-hero@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        Event::create([
            'title' => 'Event Hero',
            'description' => 'Event untuk cek hero.',
            'speaker' => 'PD Gunadarma',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 40,
            'price' => 0,
        ]);

        $this->actingAs($participant)
            ->get(route('events.index'))
            ->assertOk()
            ->assertSeeInOrder(['Selamat datang kembali,', 'Peserta Hero', 'PDUG']);
    }

    public function test_login_rejects_external_redirect(): void
    {
        User::forceCreate([
            'name' => 'Peserta Test',
            'email' => 'peserta-safe@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'peserta-safe@example.com',
            'password' => 'password',
            'redirect' => 'https://example.com/ambil-alih',
        ]);

        $response->assertRedirect(route('events.index'));
    }

    public function test_logged_in_participant_sees_name_and_profile_menu(): void
    {
        $participant = User::forceCreate([
            'name' => 'Peserta Menu',
            'email' => 'peserta-menu@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($participant)->get(route('events.index'));

        $response->assertOk();
        $response->assertSee('Peserta Menu');
        $response->assertSee('Profil');
        $response->assertDontSee('Profil Peserta');
        $response->assertSee(route('participant.profile'));
        $response->assertSee(route('tickets.index'));
        $response->assertDontSee(route('participant.activity'));
        $response->assertDontSee(route('registrations.index'));
        $response->assertSee('Logout');
    }

    public function test_participant_can_view_profile_with_registration_history(): void
    {
        $participant = User::forceCreate([
            'name' => 'Peserta Profil',
            'email' => 'peserta-profil@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        $event = Event::create([
            'title' => 'Seminar Profil Peserta',
            'description' => 'Event untuk riwayat profil.',
            'speaker' => 'PDUG',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 40,
            'price' => 0,
        ]);

        Registration::create([
            'event_id' => $event->id,
            'registration_code' => 'PDG-PROFIL-001',
            'name' => 'Peserta Profil',
            'email' => 'peserta-profil@example.com',
            'phone' => '081234567890',
            'payment_status' => 'paid',
            'registration_status' => 'registered',
        ]);

        $response = $this->actingAs($participant)->get(route('participant.profile'));

        $response->assertOk();
        $response->assertSee('<h1>Peserta Profil</h1>', false);
        $response->assertDontSee('Kelola informasi akun, tiket, dan riwayat pendaftaran event Anda.');
        $response->assertDontSee('Member sejak');
        $response->assertSee('Informasi Akun');
        $response->assertSee('Ubah Password');
        $response->assertSee('name="current_password"', false);
        $response->assertDontSee('Ganti Password');
        $response->assertSee('data-profile-tabs', false);
        $response->assertSee('data-profile-panel="data-diri"', false);
        $response->assertSee('data-profile-panel="ubah-password"', false);
        $response->assertSee('id="profile-panel-ubah-password"', false);
        $response->assertSee('Nama Lengkap');
        $response->assertSee('Tanggal Bergabung');
        $response->assertSee('Peserta Profil');
        $response->assertDontSee('<dt>Biaya</dt>', false);
        $response->assertDontSee('<dt>Transaksi</dt>', false);
        $response->assertDontSee('Informasi Peserta');
        $response->assertDontSee('<dt>Role</dt>', false);

        $activityResponse = $this->actingAs($participant)->get(route('participant.activity'));

        $activityResponse->assertOk();
        $activityResponse->assertSee('Aktivitas');
        $activityResponse->assertSee('Event yang Diikuti');
        $activityResponse->assertSee('Seminar Profil Peserta');
        $activityResponse->assertSee('PDG-PROFIL-001');
        $activityResponse->assertSee('Terdaftar');
        $activityResponse->assertSee('Gratis');
    }

    public function test_participant_profile_shows_registration_created_with_different_form_email(): void
    {
        $participant = User::forceCreate([
            'name' => 'Peserta Email Akun',
            'email' => 'akun-peserta@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        $event = Event::create([
            'title' => 'Event Email Berbeda',
            'description' => 'Event untuk cek relasi akun.',
            'speaker' => 'PDUG',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 40,
            'price' => 0,
        ]);

        $registrationResponse = $this->actingAs($participant)->post(route('registrations.store', $event), [
            'participant_type' => 'umum',
            'name' => 'Peserta Email Akun',
            'email' => 'email.form.berbeda@gmail.com',
            'phone' => '081234567890',
            'gender' => 'perempuan',
            'domicile' => 'Depok',
        ]);

        $registrationResponse->assertRedirect();
        $this->assertDatabaseHas('registrations', [
            'event_id' => $event->id,
            'user_id' => $participant->id,
            'email' => 'email.form.berbeda@gmail.com',
            'registration_status' => 'registered',
        ]);

        $this->actingAs($participant)
            ->get(route('participant.activity'))
            ->assertOk()
            ->assertSee('Event Email Berbeda')
            ->assertSee('email.form.berbeda@gmail.com')
            ->assertDontSee('Event yang Diikuti : -');
    }

    public function test_participant_profile_empty_activity_uses_dash(): void
    {
        $participant = User::forceCreate([
            'name' => 'Peserta Kosong',
            'email' => 'peserta-kosong@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($participant)->get(route('participant.activity'));

        $response->assertOk();
        $response->assertSee('Event yang Diikuti : -');
        $response->assertDontSee('Belum ada registrasi event dengan email akun ini.');
        $response->assertDontSee('Ringkasan Registrasi');
        $response->assertDontSee('Kelola Event');
    }

    public function test_finished_event_is_removed_from_participant_activity_without_deleting_history(): void
    {
        $participant = User::factory()->create(['role' => 'peserta']);
        $event = Event::create([
            'title' => 'Event Sudah Selesai',
            'description' => 'Event yang tidak lagi tampil dalam aktivitas.',
            'location' => 'Kampus D',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
            'quota' => 40,
            'price' => 0,
        ]);
        $registration = Registration::create([
            'event_id' => $event->id,
            'user_id' => $participant->id,
            'registration_code' => 'PDG-SELESAI-001',
            'name' => $participant->name,
            'email' => $participant->email,
            'phone' => '081234567890',
            'payment_status' => 'paid',
            'registration_status' => 'completed',
        ]);

        $this->actingAs($participant)
            ->get(route('participant.activity'))
            ->assertOk()
            ->assertSee('Event yang Diikuti : -')
            ->assertDontSee('Event Sudah Selesai');

        $this->assertDatabaseHas('registrations', ['id' => $registration->id]);
    }

    public function test_admin_deleting_event_also_deletes_participant_activity_and_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $participant = User::factory()->create(['role' => 'peserta']);
        $event = Event::create([
            'title' => 'Event Akan Dihapus',
            'description' => 'Event beserta aktivitas peserta akan dihapus.',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 40,
            'price' => 75000,
        ]);
        $registration = Registration::create([
            'event_id' => $event->id,
            'user_id' => $participant->id,
            'registration_code' => 'PDG-HAPUS-001',
            'name' => $participant->name,
            'email' => $participant->email,
            'phone' => '081234567890',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::create([
            'registration_id' => $registration->id,
            'order_id' => 'PDG-HAPUS-ORDER-001',
            'amount' => 75000,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.events.destroy', $event))
            ->assertRedirect(route('admin.events.index'))
            ->assertSessionHas('status', 'Event dan seluruh aktivitas pesertanya berhasil dihapus.');

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
        $this->assertDatabaseMissing('registrations', ['id' => $registration->id]);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_participant_can_cancel_free_event_from_profile(): void
    {
        $participant = User::forceCreate([
            'name' => 'Peserta Gratis',
            'email' => 'peserta-gratis@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        $event = Event::create([
            'title' => 'Event Gratis Cancel',
            'description' => 'Event gratis untuk dibatalkan.',
            'speaker' => 'PDUG',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 40,
            'price' => 0,
        ]);

        $registration = Registration::create([
            'event_id' => $event->id,
            'registration_code' => 'PDG-GRATIS-001',
            'name' => 'Peserta Gratis',
            'email' => 'peserta-gratis@example.com',
            'phone' => '081234567890',
            'payment_status' => 'paid',
            'registration_status' => 'registered',
        ]);

        $response = $this->actingAs($participant)->delete(route('participant.registrations.cancel', $registration));

        $response->assertRedirect(route('tickets.index'));
        $response->assertSessionHas('status', 'Registrasi event Event Gratis Cancel berhasil dibatalkan.');
        $this->assertDatabaseHas('registrations', [
            'registration_code' => 'PDG-GRATIS-001',
            'registration_status' => 'cancelled',
        ]);
    }

    public function test_paid_event_profile_action_points_to_committee_whatsapp(): void
    {
        $participant = User::forceCreate([
            'name' => 'Peserta Bayar',
            'email' => 'peserta-bayar@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        $event = Event::create([
            'title' => 'Event Berbayar Konfirmasi',
            'description' => 'Event berbayar untuk konfirmasi.',
            'speaker' => 'PDUG',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 40,
            'price' => 150000,
        ]);

        $registration = Registration::create([
            'event_id' => $event->id,
            'registration_code' => 'PDG-BAYAR-001',
            'name' => 'Peserta Bayar',
            'email' => 'peserta-bayar@example.com',
            'phone' => '081234567890',
            'payment_status' => 'pending',
        ]);

        $profileResponse = $this->actingAs($participant)->get(route('participant.activity'));

        $profileResponse->assertOk();
        $profileResponse->assertSee('Konfirmasi ke Pengurus');
        $profileResponse->assertSee('https://wa.me/628123456789', false);
        $profileResponse->assertDontSee('<dt>Transaksi</dt>', false);

        $this->get(route('registrations.show', $registration))
            ->assertOk()
            ->assertDontSee('Status Transaksi')
            ->assertSee('Menunggu Pembayaran');

        $cancelResponse = $this->actingAs($participant)->delete(route('participant.registrations.cancel', $registration));

        $cancelResponse->assertSessionHasErrors('registration');
        $this->assertDatabaseHas('registrations', [
            'registration_code' => 'PDG-BAYAR-001',
        ]);
    }

    public function test_participant_can_reset_password(): void
    {
        Notification::fake();
        $participant = User::forceCreate([
            'name' => 'Peserta Reset',
            'email' => 'peserta-reset@example.com',
            'password' => 'password-lama',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'peserta-reset@example.com',
        ]);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('status', 'Jika alamat email tersebut terdaftar, kami telah mengirimkan tautan untuk mengatur ulang password. Silakan periksa Inbox maupun folder Spam.');
        $response->assertSessionMissing('reset_link');
        Notification::assertSentTo($participant, ResetPasswordNotification::class);
    }

    public function test_events_can_be_searched(): void
    {
        Event::create([
            'title' => 'Seminar Bisnis Digital',
            'description' => 'Seminar umum.',
            'speaker' => 'PD Gunadarma',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 40,
            'price' => 0,
        ]);

        Event::create([
            'title' => 'Workshop Laravel',
            'description' => 'Workshop teknis.',
            'speaker' => 'PD Gunadarma',
            'location' => 'Laboratorium Internet',
            'starts_at' => now()->addWeeks(2),
            'ends_at' => now()->addWeeks(2)->addHours(2),
            'quota' => 40,
            'price' => 0,
        ]);

        $response = $this->get(route('events.index', ['search' => 'Laravel']));

        $response->assertOk();
        $response->assertSee('Workshop Laravel');
        $response->assertDontSee('Seminar Bisnis Digital');
    }

    public function test_actor_page_is_available(): void
    {
        $response = $this->get(route('actors.index'));

        $response->assertOk();
        $response->assertSee('Pengunjung');
        $response->assertSee('Pengguna');
        $response->assertSee('Admin PD Gunadarma');
        $response->assertDontSee('Peserta');
    }

    public function test_admin_can_create_event(): void
    {
        $admin = User::forceCreate([
            'name' => 'Admin Test',
            'email' => 'admin-create@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.events.store'), [
            'title' => 'Pelatihan UI UX',
            'description' => 'Pelatihan desain produk digital.',
            'speaker' => 'Tim PDUG',
            'location' => 'Kampus J',
            'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addWeek()->addHours(2)->format('Y-m-d H:i:s'),
            'quota' => 60,
            'pricing_type' => 'paid',
            'price' => 50000,
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('admin.events.index'));
        $this->assertDatabaseHas('events', [
            'title' => 'Pelatihan UI UX',
            'slug' => 'pelatihan-ui-ux',
            'registration_is_open' => true,
        ]);
    }

    public function test_admin_can_create_upcoming_event_status(): void
    {
        $admin = User::forceCreate([
            'name' => 'Admin Test',
            'email' => 'admin-upcoming@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $formResponse = $this->actingAs($admin)->get(route('admin.events.create'));

        $formResponse->assertOk();
        $formResponse->assertSee('Status Pendaftaran');
        $formResponse->assertSee('Segera dibuka');
        $formResponse->assertSee('Kategori Jenis Pendaftaran');
        $formResponse->assertSee('Enter/baris baru yang dibuat di sini akan ikut tampil di halaman event.');

        $response = $this->actingAs($admin)->post(route('admin.events.store'), [
            'title' => 'Info Event Upcoming',
            'description' => 'Informasi event yang pendaftarannya belum dibuka.',
            'speaker' => 'Tim PDUG',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeeks(3)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addWeeks(3)->addHours(2)->format('Y-m-d H:i:s'),
            'quota' => 90,
            'pricing_type' => 'free',
            'is_published' => '1',
            'registration_status' => 'upcoming',
        ]);

        $response->assertRedirect(route('admin.events.index'));
        $this->assertDatabaseHas('events', [
            'title' => 'Info Event Upcoming',
            'registration_is_open' => false,
        ]);
    }

    public function test_admin_can_view_registered_users_page(): void
    {
        $admin = User::forceCreate([
            'name' => 'Admin Users',
            'email' => 'admin-users@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        User::forceCreate([
            'name' => 'User Terdaftar',
            'email' => 'user-terdaftar@example.com',
            'password' => 'password',
            'role' => 'peserta',
            'email_verified_at' => now(),
        ]);

        $event = Event::create([
            'title' => 'Event User',
            'description' => 'Event untuk cek users.',
            'speaker' => 'PDUG',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 40,
            'price' => 0,
        ]);

        Registration::create([
            'event_id' => $event->id,
            'registration_code' => 'PDG-USER-001',
            'name' => 'User Terdaftar',
            'email' => 'user-terdaftar@example.com',
            'phone' => '081234567890',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Kelola Pengguna');
        $response->assertSee('/admin/users', false);
        $response->assertDontSee('Dashboard Filament');
        $response->assertSee('User Terdaftar');
        $response->assertSee('user-terdaftar@example.com');
        $response->assertSee('Peserta');
        $response->assertSee('1');
    }

    public function test_admin_can_update_registration_status(): void
    {
        $admin = User::forceCreate([
            'name' => 'Admin Test',
            'email' => 'admin-status@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $event = Event::create([
            'title' => 'Seminar Status',
            'description' => 'Seminar status peserta.',
            'speaker' => 'PDUG',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 40,
            'price' => 0,
        ]);

        $registration = Registration::create([
            'event_id' => $event->id,
            'registration_code' => 'PDG-TEST-001',
            'name' => 'Peserta Status',
            'email' => 'status@example.com',
            'phone' => '081234567890',
            'payment_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.registrations.status', $registration), [
            'payment_status' => 'paid',
            'registration_status' => 'registered',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'payment_status' => 'pending',
            'registration_status' => 'registered',
        ]);
    }

    public function test_admin_can_check_in_registered_participant_from_qr_page(): void
    {
        $admin = User::forceCreate([
            'name' => 'Admin Checkin',
            'email' => 'admin-checkin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $event = Event::create([
            'title' => 'Seminar Checkin',
            'description' => 'Seminar untuk check-in.',
            'speaker' => 'PDUG',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 40,
            'price' => 0,
        ]);

        $registration = Registration::create([
            'event_id' => $event->id,
            'registration_code' => 'PDG-CHECK-001',
            'name' => 'Peserta Checkin',
            'email' => 'checkin@example.com',
            'phone' => '081234567890',
            'payment_status' => 'paid',
            'registration_status' => 'registered',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.registrations.checkin.show', $registration->registration_code))
            ->assertOk()
            ->assertSee('QR Check-in')
            ->assertSee('Peserta Checkin');

        $this->actingAs($admin)
            ->patch(route('admin.registrations.checkin', $registration))
            ->assertRedirect();

        $this->assertDatabaseHas('registrations', [
            'registration_code' => 'PDG-CHECK-001',
            'registration_status' => 'checked_in',
        ]);
    }

    public function test_admin_can_view_report_page(): void
    {
        $admin = User::forceCreate([
            'name' => 'Admin Test',
            'email' => 'admin-report@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $freeEvent = Event::create([
            'title' => 'Event Laporan Gratis',
            'description' => 'Event gratis untuk laporan.',
            'speaker' => 'PDUG',
            'location' => 'Kampus D',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(2),
            'quota' => 40,
            'price' => 0,
        ]);

        $paidEvent = Event::create([
            'title' => 'Event Laporan Berbayar',
            'description' => 'Event berbayar untuk laporan.',
            'speaker' => 'PDUG',
            'location' => 'Kampus E',
            'starts_at' => now()->addWeeks(2),
            'ends_at' => now()->addWeeks(2)->addHours(2),
            'quota' => 80,
            'price' => 150000,
        ]);

        Registration::create([
            'event_id' => $freeEvent->id,
            'registration_code' => 'PDG-REPORT-001',
            'name' => 'Peserta Gratis Laporan',
            'email' => 'gratis-laporan@example.com',
            'phone' => '081234567890',
            'participant_type' => 'umum',
            'payment_status' => 'paid',
            'registration_status' => 'registered',
            'custom_fields' => [
                'gender' => 'laki_laki',
                'domicile' => 'depok',
            ],
        ]);

        Registration::create([
            'event_id' => $paidEvent->id,
            'registration_code' => 'PDG-REPORT-002',
            'name' => 'Peserta Bayar Laporan',
            'email' => 'bayar-laporan@example.com',
            'phone' => '081298765432',
            'participant_type' => 'umum',
            'payment_status' => 'paid',
            'registration_status' => 'checked_in',
            'checked_in_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('filament.admin.pages.laporan'));

        $response->assertOk();
        $response->assertSee('Laporan');
        $response->assertSee('Unduh PDF');
        $response->assertSee(route('admin.reports.pdf'), false);
        $response->assertDontSee('Excel');
        $response->assertDontSee('Data Tambahan');
        $response->assertSee('Laporan Per Event');
        $response->assertSee('Laporan Registrasi Event PD Gunadarma');
        $response->assertSee('Event Laporan Gratis');
        $response->assertSee('Event Laporan Berbayar');
        $response->assertSee('PDG-REPORT-001');
        $response->assertSee('Jenis Kelamin');
        $response->assertSee('Domisili');
        $response->assertSee('Kategori Peserta');
        $response->assertSee('Laki-laki');
        $response->assertSee('Depok');
        $response->assertSee('Status Pembayaran');
        $response->assertSee('Status Registrasi');
        $response->assertSee('Status Check-in');
        $response->assertSee('Terdaftar');
        $response->assertDontSee('Belum terdaftar');
        $response->assertSee('Gratis');
        $response->assertSee('Check-in');

        $filteredResponse = $this->actingAs($admin)->get(route('filament.admin.pages.laporan', [
            'event_id' => $freeEvent->id,
        ]));

        $filteredResponse->assertOk();
        $filteredResponse->assertSee('Event: Event Laporan Gratis');
        $filteredResponse->assertSee('PDG-REPORT-001');
        $filteredResponse->assertDontSee('PDG-REPORT-002');

        $freeReportRequest = Request::create('/admin/laporan', 'GET', ['event_id' => $freeEvent->id]);
        $freeReportRequest->setUserResolver(fn (): User => $admin);
        $freeReport = app(AdminReportController::class)->reportData($freeReportRequest)['eventReports']->first();

        $this->assertSame([
            'total_registrations' => 1,
            'total_paid' => 0,
            'total_check_in' => 0,
        ], $freeReport['stats']);
        $this->assertSame('Laki-laki', $freeReport['rows']->first()['gender']);
        $this->assertSame('Depok', $freeReport['rows']->first()['domicile']);
        $this->assertSame('Terdaftar', $freeReport['rows']->first()['registration_status']);
        $this->assertSame('Belum Check-in', $freeReport['rows']->first()['check_in_status']);

        $paidReportRequest = Request::create('/admin/laporan', 'GET', ['event_id' => $paidEvent->id]);
        $paidReportRequest->setUserResolver(fn (): User => $admin);
        $paidReport = app(AdminReportController::class)->reportData($paidReportRequest)['eventReports']->first();

        $this->assertSame([
            'total_registrations' => 1,
            'total_paid' => 1,
            'total_check_in' => 1,
        ], $paidReport['stats']);
        $this->assertSame('Berhasil', $paidReport['rows']->first()['payment_status']);
        $this->assertSame('Terdaftar', $paidReport['rows']->first()['registration_status']);
        $this->assertSame('Sudah Check-in', $paidReport['rows']->first()['check_in_status']);

        $exportResponse = $this->actingAs($admin)->get(route('admin.reports.pdf', [
            'event_id' => $freeEvent->id,
        ]));

        $exportResponse->assertOk();
        $exportResponse->assertDownload('laporan-registrasi-event-laporan-gratis.pdf');
        $this->assertSame('application/pdf', $exportResponse->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF-', $exportResponse->getContent());
    }

    public function test_admin_can_update_website_settings(): void
    {
        $admin = User::forceCreate([
            'name' => 'Admin Test',
            'email' => 'admin-website@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        SiteSetting::current();

        $response = $this->actingAs($admin)->patch(route('admin.website.update'), [
            'site_name' => 'PDUG Event Center',
            'site_tagline' => 'Informasi Event Kampus',
            'hero_title' => 'Agenda PDUG',
            'hero_subtitle' => 'Pusat informasi agenda PDUG.',
            'contact_email' => 'admin@pdug.test',
            'contact_phone' => '082199773846',
            'contact_address' => 'Kampus Gunadarma',
            'registration_section_title' => 'Nilai lama yang tidak relevan',
            'registration_section_description' => 'Deskripsi lama yang tidak relevan.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('site_settings', [
            'site_name' => 'PDUG Event Center',
            'hero_title' => 'Agenda PDUG',
            'registration_section_title' => 'Data Registrasi',
            'registration_section_description' => 'Pilih kategori peserta agar form menampilkan data yang sesuai.',
        ]);

        $publicResponse = $this->get(route('events.index'));

        $publicResponse->assertOk();
        $publicResponse->assertSee('<title>Beranda • PDUG Event Center</title>', false);
        $publicResponse->assertSee('<strong>PDUG Event Center</strong>', false);
        $publicResponse->assertSee('<small>Informasi Event Kampus</small>', false);
        $publicResponse->assertSee('<h1>Agenda PDUG</h1>', false);
        $publicResponse->assertSee('Pusat informasi agenda PDUG.');

        $this->get(route('profile.pdug'))
            ->assertOk()
            ->assertSee('https://wa.me/6282199773846', false)
            ->assertDontSee('https://wa.me/82199773846', false);
    }
}
