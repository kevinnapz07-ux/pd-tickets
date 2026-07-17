<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Pengguna')
                ->modalHeading('Tambah Pengguna')
                ->modalDescription('Buat akun peserta atau admin baru. Berikan akses admin hanya kepada pengelola yang dipercaya.')
                ->successNotificationTitle('Pengguna berhasil ditambahkan'),
        ];
    }
}
