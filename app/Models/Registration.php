<?php

namespace App\Models;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
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
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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
                    $prefix = static::eventCode($registration->event_id);
                    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
                    $suffix = collect(range(1, 6))
                        ->map(fn (): string => $alphabet[random_int(0, strlen($alphabet) - 1)])
                        ->implode('');
                    $registration->registration_code = $prefix.'-'.$suffix;
                } while (static::where('registration_code', $registration->registration_code)->exists());
            }

            if (! $registration->verification_token) {
                do {
                    $registration->verification_token = Str::random(64);
                } while (static::where('verification_token', $registration->verification_token)->exists());
            }
        });
    }

    private static function eventCode(?int $eventId): string
    {
        $title = (string) DB::table('events')->where('id', $eventId)->value('title');
        $words = preg_split('/[^A-Za-z0-9]+/', Str::ascii($title), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $prefix = count($words) > 1
            ? collect($words)->take(4)->map(fn (string $word): string => $word[0])->implode('')
            : substr($words[0] ?? 'PDG', 0, 4);

        return Str::upper($prefix ?: 'PDG');
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
