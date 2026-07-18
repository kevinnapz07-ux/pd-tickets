<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Nama Event')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set): void {
                        $title = trim((string) $state);

                        if ($title === '') {
                            return;
                        }

                        $set('slug', Str::slug($title) ?: 'event-'.substr(md5($title), 0, 8));
                    })
                    ->required(),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required(),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('image_path')
                    ->label('Foto / Poster Event')
                    ->disk('public')
                    ->directory('events')
                    ->image()
                    ->imagePreviewHeight('240')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->downloadable()
                    ->openable()
                    ->deletable()
                    ->helperText('JPG, JPEG, PNG, atau WebP. Maksimal 2 MB.')
                    ->columnSpanFull(),
                TextInput::make('speaker')
                    ->label('Pembicara'),
                TextInput::make('location')
                    ->label('Lokasi')
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->label('Mulai')
                    ->format('Y-m-d H:i')
                    ->seconds(false)
                    ->minutesStep(5)
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->label('Selesai')
                    ->format('Y-m-d H:i')
                    ->seconds(false)
                    ->minutesStep(5),
                TextInput::make('quota')
                    ->label('Kuota')
                    ->required()
                    ->numeric(),
                TextInput::make('price')
                    ->label('Harga')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),
                Toggle::make('is_published')
                    ->label('Publish event')
                    ->default(true)
                    ->required(),
                Select::make('registration_is_open')
                    ->label('Status Pendaftaran')
                    ->options([
                        true => 'Pendaftaran dibuka',
                        false => 'Segera dibuka',
                    ])
                    ->default(true)
                    ->required()
                    ->helperText('Pilih Segera dibuka agar event tampil sebagai Upcoming Event dan belum bisa didaftari.'),
            ]);
    }
}
