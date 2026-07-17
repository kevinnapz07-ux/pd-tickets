<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Registration;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => $state === 'admin' ? 'info' : 'success')
                    ->searchable(),
                TextColumn::make('registrations_count')
                    ->label('Registrasi Event')
                    ->state(fn ($record): int => Registration::where('email', $record->email)->count()),
                TextColumn::make('created_at')
                    ->label('Waktu Bergabung')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'admin' => 'Admin',
                        'peserta' => 'Peserta',
                    ]),
            ])
            ->defaultSort('created_at', 'asc')
            ->recordActions([
                ActionGroup::make([
                    Action::make('edit_user')
                        ->label('Edit')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->modalHeading('Edit Pengguna')
                        ->schema([
                            TextInput::make('name')
                                ->label('Nama')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                        ])
                        ->fillForm(fn (User $record): array => [
                            'name' => $record->name,
                            'email' => $record->email,
                        ])
                        ->action(function (User $record, array $data): void {
                            $record->update($data);

                            Notification::make()
                                ->title('Data pengguna berhasil diperbarui')
                                ->success()
                                ->send();
                        }),
                    Action::make('change_role')
                        ->label('Ubah Role')
                        ->icon(Heroicon::OutlinedShieldCheck)
                        ->modalHeading('Ubah Role Pengguna')
                        ->modalDescription('Admin memiliki akses penuh ke panel pengelolaan. Peserta hanya dapat menggunakan fitur registrasi event.')
                        ->schema([
                            Radio::make('role')
                                ->label('Role')
                                ->options([
                                    'admin' => 'Admin',
                                    'peserta' => 'Peserta',
                                ])
                                ->descriptions([
                                    'admin' => 'Dapat mengelola event, pengguna, transaksi, laporan, dan website.',
                                    'peserta' => 'Dapat melihat dan mendaftar event sebagai peserta.',
                                ])
                                ->required(),
                        ])
                        ->fillForm(fn (User $record): array => ['role' => $record->role])
                        ->action(function (User $record, array $data): void {
                            if ($record->is(auth()->user()) && $data['role'] !== 'admin') {
                                Notification::make()
                                    ->title('Role tidak dapat diubah')
                                    ->body('Anda tidak dapat menurunkan role akun sendiri menjadi Peserta.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            if ($record->role === 'admin'
                                && $data['role'] !== 'admin'
                                && User::query()->where('role', 'admin')->count() <= 1) {
                                Notification::make()
                                    ->title('Minimal satu Admin diperlukan')
                                    ->body('Role tidak diubah karena akun ini adalah satu-satunya Admin.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $record->update(['role' => $data['role']]);

                            Notification::make()
                                ->title('Role pengguna berhasil diperbarui')
                                ->success()
                                ->send();
                        }),
                    Action::make('reset_password')
                        ->label('Reset Password')
                        ->icon(Heroicon::OutlinedKey)
                        ->modalHeading('Reset Password Pengguna')
                        ->modalDescription('Masukkan password baru dan konfirmasikan sebelum menyimpan.')
                        ->schema([
                            TextInput::make('password')
                                ->label('Password Baru')
                                ->password()
                                ->revealable()
                                ->required()
                                ->minLength(8)
                                ->confirmed(),
                            TextInput::make('password_confirmation')
                                ->label('Konfirmasi Password Baru')
                                ->password()
                                ->revealable()
                                ->required()
                                ->dehydrated(false),
                        ])
                        ->action(function (User $record, array $data): void {
                            $record->update(['password' => $data['password']]);

                            Notification::make()
                                ->title('Password pengguna berhasil direset')
                                ->success()
                                ->send();
                        }),
                    Action::make('delete_user')
                        ->label('Hapus')
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Pengguna')
                        ->modalDescription('Akun yang dihapus tidak dapat dipulihkan. Pastikan tindakan ini sudah benar.')
                        ->hidden(fn (User $record): bool => $record->is(auth()->user()))
                        ->action(function (User $record): void {
                            if ($record->role === 'admin'
                                && User::query()->where('role', 'admin')->count() <= 1) {
                                Notification::make()
                                    ->title('Admin terakhir tidak dapat dihapus')
                                    ->body('Sistem harus selalu memiliki minimal satu akun Admin.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $record->delete();

                            Notification::make()
                                ->title('Pengguna berhasil dihapus')
                                ->success()
                                ->send();
                        }),
                ])
                    ->label('Aksi'),
            ]);
    }
}
