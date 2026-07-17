<?php

namespace App\Models;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'registration_code',
        'verification_token',
        'name',
        'email',
        'participant_type',
        'phone',
        'student_id',
        'campus_area',
        'class_year',
        'study_program',
        'faculty',
        'payment_status',
        'registration_status',
        'checked_in_at',
        'checked_in_by',
        'notes',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'checked_in_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    protected static function booted(): void
    {
        static::creating(function (Registration $registration): void {
            if (! $registration->registration_code) {
                do {
                    $registration->registration_code = 'PDUG-'.now()->year.'-'.Str::upper(Str::random(6));
                } while (static::where('registration_code', $registration->registration_code)->exists());
            }

            if (! $registration->verification_token) {
                do {
                    $registration->verification_token = Str::random(64);
                } while (static::where('verification_token', $registration->verification_token)->exists());
            }
        });
    }

    public static function transactionStatusLabels(): array
    {
        return [
            'pending' => 'Menunggu Pembayaran',
            'paid' => 'Berhasil',
            'failed' => 'Gagal',
            'expired' => 'Kedaluwarsa',
            'cancelled' => 'Dibatalkan',
            'refunded' => 'Dikembalikan (Refund)',
        ];
    }

    public static function registrationStatusLabels(): array
    {
        return [
            'registered' => 'Terdaftar',
            'checked_in' => 'Check-in',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ];
    }

    public function transactionStatusLabel(): string
    {
        if ($this->event?->price <= 0) {
            return 'Tidak perlu pembayaran';
        }

        return self::transactionStatusLabels()[$this->payment_status] ?? ucfirst((string) $this->payment_status);
    }

    public function registrationStatusLabel(): string
    {
        return self::registrationStatusLabels()[$this->registration_status] ?? 'Belum terdaftar';
    }

    public function isCheckInReady(): bool
    {
        return $this->payment_status === 'paid'
            && in_array($this->registration_status, ['registered', 'checked_in'], true);
    }

    public function checkInUrl(): string
    {
        return $this->verificationUrl();
    }

    public function verificationUrl(): string
    {
        return route('tickets.verify', ['token' => $this->verification_token]);
    }

    public function qrCodeDataUri(): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'outputBase64' => true,
            'scale' => 8,
            'quietzoneSize' => 4,
            'svgAddXmlHeader' => false,
        ]);

        return (new QRCode($options))->render($this->checkInUrl());
    }
}
