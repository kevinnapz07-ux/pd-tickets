<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\SiteSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        SiteSetting::query()->firstOrCreate([], [
            'site_name' => 'PD Gunadarma',
            'site_tagline' => 'Event Registration',
            'hero_title' => 'PDUG',
            'hero_subtitle' => 'Temukan seminar, workshop, dan kegiatan akademik PD Gunadarma. Registrasi peserta dan pembayaran diproses dalam satu alur yang mudah dilacak.',
            'contact_email' => null,
            'contact_phone' => null,
            'contact_address' => null,
            'registration_section_title' => 'Data Registrasi',
            'registration_section_description' => 'Pilih kategori peserta agar form menampilkan data yang sesuai.',
        ]);

        Event::query()->updateOrCreate(['slug' => 'seminar-nasional-digital-business-2026'], [
            'title' => 'Seminar Nasional Digital Business 2026',
            'slug' => 'seminar-nasional-digital-business-2026',
            'description' => 'Seminar PD Gunadarma untuk membahas strategi bisnis digital, pengembangan produk, dan kesiapan mahasiswa menghadapi industri teknologi.',
            'speaker' => 'Dr. Andika Pratama',
            'location' => 'Auditorium Kampus D Gunadarma',
            'starts_at' => now()->addWeeks(2)->setTime(9, 0),
            'ends_at' => now()->addWeeks(2)->setTime(13, 0),
            'quota' => 250,
            'price' => 75000,
        ]);

        Event::query()->updateOrCreate(['slug' => 'workshop-laravel-payment-gateway'], [
            'title' => 'Workshop Laravel Payment Gateway',
            'slug' => 'workshop-laravel-payment-gateway',
            'description' => 'Workshop teknis membangun sistem registrasi event, checkout, dan integrasi webhook payment gateway menggunakan Laravel.',
            'speaker' => 'Tim Developer PD Gunadarma',
            'location' => 'Laboratorium Internet Kampus E',
            'starts_at' => now()->addWeeks(4)->setTime(10, 0),
            'ends_at' => now()->addWeeks(4)->setTime(15, 30),
            'quota' => 80,
            'price' => 125000,
        ]);

        Event::query()->updateOrCreate(['slug' => 'career-talk-alumni-gunadarma'], [
            'title' => 'Career Talk Alumni Gunadarma',
            'slug' => 'career-talk-alumni-gunadarma',
            'description' => 'Sesi berbagi pengalaman alumni tentang persiapan karier, portofolio, wawancara kerja, dan jejaring profesional.',
            'speaker' => 'Ikatan Alumni Gunadarma',
            'location' => 'Ruang Seminar Kampus H',
            'starts_at' => now()->addWeeks(5)->setTime(13, 0),
            'ends_at' => now()->addWeeks(5)->setTime(16, 0),
            'quota' => 150,
            'price' => 0,
        ]);

        Event::query()->updateOrCreate(['slug' => 'retreat-ukm-kerohanian-kristen-gunadarma-2026'], [
            'title' => 'Retreat UKM Kerohanian Kristen Gunadarma 2026',
            'slug' => 'retreat-ukm-kerohanian-kristen-gunadarma-2026',
            'description' => 'Informasi awal kegiatan retreat UKM Kerohanian Kristen Universitas Gunadarma. Detail pendaftaran akan diumumkan saat periode registrasi dibuka.',
            'speaker' => 'Panitia UKM Kerohanian Kristen',
            'location' => 'Kampus Gunadarma',
            'starts_at' => now()->addWeeks(8)->setTime(7, 0),
            'ends_at' => now()->addWeeks(8)->setTime(17, 0),
            'quota' => 110,
            'price' => 0,
            'registration_is_open' => false,
        ]);
    }
}
