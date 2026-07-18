<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'image_path',
        'speaker',
        'location',
        'starts_at',
        'ends_at',
        'quota',
        'price',
        'is_published',
        'registration_is_open',
        'registration_form_schema',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
            'registration_is_open' => 'boolean',
            'registration_form_schema' => 'array',
            'price' => 'integer',
            'quota' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Event $event): void {
            if (! $event->slug) {
                $event->slug = static::slugBase($event->title);
            }
        });

        static::updated(function (Event $event): void {
            if ($event->wasChanged('image_path') && filled($event->getOriginal('image_path'))) {
                Storage::disk('public')->delete($event->getOriginal('image_path'));
            }
        });

        static::deleted(function (Event $event): void {
            if (filled($event->image_path)) {
                Storage::disk('public')->delete($event->image_path);
            }
        });
    }

    public function getImageUrlAttribute(): ?string
    {
        return filled($this->image_path) ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function slugBase(string $title): string
    {
        return Str::slug($title) ?: 'event-'.substr(md5($title), 0, 8);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function paidRegistrations(): HasMany
    {
        return $this->registrations()
            ->where('payment_status', 'paid')
            ->where(fn ($query) => $query->whereNull('registration_status')->orWhere('registration_status', '!=', 'cancelled'));
    }

    public function getAvailableSeatsAttribute(): int
    {
        return max(0, $this->quota - $this->paidRegistrations()->count());
    }

    public static function registrationFieldDefinitions(): array
    {
        return [
            'name' => ['label' => 'Nama Lengkap', 'type' => 'text', 'required' => true, 'placeholder' => 'Masukkan nama lengkap'],
            'email' => ['label' => 'Email Gmail', 'type' => 'email', 'required' => true, 'placeholder' => 'nama@gmail.com'],
            'phone' => ['label' => 'No. HP (WhatsApp)', 'type' => 'tel', 'required' => true, 'placeholder' => 'Contoh: 081234567890'],
            'gender' => [
                'label' => 'Jenis Kelamin',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'laki_laki' => 'Laki-laki',
                    'perempuan' => 'Perempuan',
                ],
            ],
            'domicile' => ['label' => 'Domisili', 'type' => 'text', 'required' => true, 'placeholder' => 'Contoh: Depok'],
            'student_id' => ['label' => 'NPM', 'type' => 'text', 'required' => true, 'placeholder' => 'Masukkan NPM'],
            'campus_area' => [
                'label' => 'Area Kampus',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'depok' => 'Depok',
                    'kalimalang' => 'Kalimalang',
                    'karawaci' => 'Karawaci',
                    'cengkareng' => 'Cengkareng',
                    'salemba' => 'Salemba',
                ],
            ],
            'class_year' => ['label' => 'Angkatan', 'type' => 'text', 'required' => true, 'placeholder' => 'Contoh: 2024'],
            'study_program' => ['label' => 'Program Studi', 'type' => 'text', 'required' => true, 'placeholder' => 'Contoh: Sistem Informasi'],
            'faculty' => ['label' => 'Fakultas/Unit', 'type' => 'text', 'required' => false, 'placeholder' => 'Contoh: Fakultas Ilmu Komputer'],
            'notes' => ['label' => 'Catatan', 'type' => 'textarea', 'required' => false, 'placeholder' => 'Tambahkan catatan bila diperlukan'],
        ];
    }

    public static function defaultRegistrationCategories(): array
    {
        return [
            [
                'key' => 'umum',
                'label' => 'Umum',
                'fields' => ['name', 'email', 'phone', 'gender', 'domicile'],
            ],
            [
                'key' => 'mahasiswa_gunadarma',
                'label' => 'Mahasiswa Universitas Gunadarma',
                'fields' => ['name', 'email', 'phone', 'student_id', 'campus_area', 'class_year', 'study_program'],
            ],
        ];
    }

    public function registrationCategories(): array
    {
        return $this->registration_form_schema ?: self::defaultRegistrationCategories();
    }

    public static function parseRegistrationSchemaText(?string $schemaText): ?array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim((string) $schemaText));
        $categories = [];

        foreach ($lines as $line) {
            if (trim($line) === '' || ! str_contains($line, ':')) {
                continue;
            }

            [$label, $fieldsText] = array_map('trim', explode(':', $line, 2));

            if ($label === '') {
                continue;
            }

            $fields = collect(explode(',', $fieldsText))
                ->map(fn (string $field): string => Str::slug(trim($field), '_'))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $categories[] = [
                'key' => Str::slug($label, '_') ?: 'kategori_'.(count($categories) + 1),
                'label' => $label,
                'fields' => $fields ?: ['name', 'email', 'phone'],
            ];
        }

        return $categories === [] ? null : $categories;
    }

    public static function parseRegistrationSchemaBuilder(?array $schemaBuilder): ?array
    {
        $categories = [];

        foreach (($schemaBuilder['categories'] ?? []) as $categoryKey => $category) {
            if (! isset($category['enabled'])) {
                continue;
            }

            $label = trim((string) ($category['label'] ?? Str::headline(str_replace('_', ' ', (string) $categoryKey))));

            if ($label === '') {
                continue;
            }

            $fields = collect($category['fields'] ?? [])
                ->map(fn (string $field): string => Str::slug(trim($field), '_'))
                ->filter()
                ->values();

            $customFieldInput = $category['custom_fields'] ?? [];
            $customFieldValues = is_array($customFieldInput)
                ? $customFieldInput
                : explode(',', (string) $customFieldInput);

            $customFields = collect($customFieldValues)
                ->map(fn (string $field): string => Str::slug(trim($field), '_'))
                ->filter();

            $allFields = $fields
                ->merge($customFields)
                ->unique()
                ->values()
                ->all();

            $categories[] = [
                'key' => Str::slug((string) $categoryKey, '_') ?: Str::slug($label, '_'),
                'label' => $label,
                'fields' => $allFields ?: ['name', 'email', 'phone'],
            ];
        }

        return $categories === [] ? null : $categories;
    }

    public function registrationSchemaText(): string
    {
        return collect($this->registrationCategories())
            ->map(fn (array $category): string => ($category['label'] ?? $category['key']).': '.implode(',', $category['fields'] ?? []))
            ->implode("\n");
    }

    public static function registrationFieldLabel(string $field): string
    {
        return self::registrationFieldDefinitions()[$field]['label'] ?? Str::headline(str_replace('_', ' ', $field));
    }
}
