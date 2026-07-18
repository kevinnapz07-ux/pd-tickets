<?php

namespace App\Filament\Resources\SiteSettings\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SiteSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('site_name')
                    ->label('Nama Website')
                    ->required()
                    ->default('PD Gunadarma'),
                TextInput::make('site_tagline')
                    ->label('Tagline')
                    ->required()
                    ->default('Event Registration'),
                TextInput::make('hero_title')
                    ->label('Judul Hero')
                    ->required()
                    ->default('PDUG'),
                Textarea::make('hero_subtitle')
                    ->label('Deskripsi Hero')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('contact_email')
                    ->label('Email Kontak')
                    ->email(),
                TextInput::make('contact_phone')
                    ->label('Nomor WhatsApp')
                    ->tel()
                    ->regex('/^(?:\\+62|62|0)8[0-9 .-]{7,16}$/')
                    ->helperText('Gunakan nomor WhatsApp Indonesia, misalnya 082199773846 atau +62 821-9977-3846.'),
                TextInput::make('contact_address')
                    ->label('Alamat'),
            ]);
    }
}
