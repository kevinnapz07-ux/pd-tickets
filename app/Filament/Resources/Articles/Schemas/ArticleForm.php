<?php

namespace App\Filament\Resources\Articles\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Judul')
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, callable $set): void {
                    $title = trim((string) $state);
                    if ($title !== '') {
                        $set('slug', Str::slug($title) ?: 'artikel-'.substr(md5($title), 0, 8));
                    }
                })
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            FileUpload::make('thumbnail_path')
                ->label('Thumbnail')
                ->disk('public')
                ->directory('articles')
                ->image()
                ->imagePreviewHeight('240')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(2048)
                ->downloadable()
                ->openable()
                ->deletable()
                ->helperText('JPG, JPEG, PNG, atau WebP. Maksimal 2 MB.')
                ->columnSpanFull(),
            Textarea::make('summary')
                ->label('Ringkasan')
                ->rows(3)
                ->required()
                ->maxLength(500)
                ->columnSpanFull(),
            RichEditor::make('content')
                ->label('Isi Artikel')
                ->required()
                ->helperText('Gunakan heading, daftar, tautan, dan format teks untuk menyusun artikel.')
                ->columnSpanFull(),
            Toggle::make('is_published')
                ->label('Publish artikel')
                ->default(false),
            DateTimePicker::make('published_at')
                ->label('Tanggal Publikasi')
                ->seconds(false)
                ->minutesStep(5)
                ->helperText('Kosongkan untuk memakai waktu saat artikel pertama kali dipublish.'),
            TextInput::make('seo_title')
                ->label('SEO Title')
                ->maxLength(60)
                ->helperText('Opsional. Maksimal 60 karakter.'),
            Textarea::make('meta_description')
                ->label('Meta Description')
                ->rows(3)
                ->maxLength(160)
                ->helperText('Opsional. Maksimal 160 karakter.')
                ->columnSpanFull(),
        ]);
    }
}
