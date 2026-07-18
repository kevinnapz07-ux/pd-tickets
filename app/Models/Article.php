<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'thumbnail_path',
        'summary',
        'content',
        'is_published',
        'published_at',
        'seo_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Article $article): void {
            if (! $article->exists || $article->isDirty('slug')) {
                $article->slug = static::uniqueSlug($article->slug ?: $article->title, $article->getKey());
            }

            if ($article->is_published && ! $article->published_at) {
                $article->published_at = now();
            }
        });

        static::updated(function (Article $article): void {
            if ($article->wasChanged('thumbnail_path') && filled($article->getOriginal('thumbnail_path'))) {
                Storage::disk('public')->delete($article->getOriginal('thumbnail_path'));
            }
        });

        static::deleted(function (Article $article): void {
            if (filled($article->thumbnail_path)) {
                Storage::disk('public')->delete($article->thumbnail_path);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return filled($this->thumbnail_path) ? Storage::disk('public')->url($this->thumbnail_path) : null;
    }

    public static function uniqueSlug(string $value, int|string|null $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'artikel-'.substr(md5($value), 0, 8);
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
