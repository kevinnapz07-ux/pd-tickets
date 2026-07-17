<?php

namespace App\Filament\Resources\Registrations\Schemas;

use App\Models\Registration;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RegistrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->label('Event')
                    ->relationship('event', 'title')
                    ->required(),
                TextInput::make('registration_code')
                    ->label('Kode Registrasi')
                    ->required(),
                TextInput::make('name')
                    ->label('Nama Peserta')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
                Select::make('participant_type')
                    ->label('Kategori Peserta')
                    ->options([
                        'umum' => 'Umum',
                        'mahasiswa_gunadarma' => 'Mahasiswa Universitas Gunadarma',
                    ])
                    ->required()
                    ->default('umum'),
                TextInput::make('phone')
                    ->label('No. HP (WhatsApp)')
                    ->tel()
                    ->required(),
                TextInput::make('student_id')
                    ->label('NPM'),
                Select::make('campus_area')
                    ->label('Area Kampus')
                    ->options([
                        'depok' => 'Depok',
                        'kalimalang' => 'Kalimalang',
                        'karawaci' => 'Karawaci',
                        'cengkareng' => 'Cengkareng',
                        'salemba' => 'Salemba',
                    ]),
                TextInput::make('class_year')
                    ->label('Angkatan'),
                TextInput::make('study_program')
                    ->label('Program Studi'),
                Select::make('payment_status')
                    ->label('Status Transaksi')
                    ->options(Registration::transactionStatusLabels())
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Status resmi hanya diperbarui otomatis melalui webhook Midtrans yang tervalidasi.'),
                Select::make('registration_status')
                    ->label('Status Pendaftaran')
                    ->options(Registration::registrationStatusLabels()),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }
}
