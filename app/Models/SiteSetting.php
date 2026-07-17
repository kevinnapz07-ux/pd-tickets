<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'site_name',
        'site_tagline',
        'hero_title',
        'hero_subtitle',
        'contact_email',
        'contact_phone',
        'contact_address',
        'announcement',
        'registration_section_title',
        'registration_section_description',
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'site_name' => config('branding.title', 'PD Gunadarma Event'),
            'site_tagline' => config('branding.subtitle', 'Informasi dan Registrasi Event'),
            'hero_title' => 'PDUG',
            'hero_subtitle' => 'Temukan seminar, workshop, dan kegiatan akademik PD Gunadarma. Registrasi peserta dan pembayaran diproses dalam satu alur yang mudah dilacak.',
            'contact_email' => null,
            'contact_phone' => null,
            'contact_address' => null,
            'announcement' => null,
            'registration_section_title' => 'Data Registrasi',
            'registration_section_description' => 'Pilih kategori peserta agar form menampilkan data yang sesuai.',
        ]);
    }

    /**
     * Convert an Indonesian WhatsApp number entered by an administrator to the
     * international digits-only format required by wa.me.
     */
    public static function whatsappNumber(?string $phone): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($number, '0')) {
            $number = '62'.substr($number, 1);
        } elseif (str_starts_with($number, '8')) {
            $number = '62'.$number;
        }

        return preg_match('/^628\d{7,13}$/', $number) ? $number : null;
    }
}
