<?php

namespace App\Filament\Resources\Registrations\Tables;

use App\Models\Registration;
use App\Services\TicketCheckInService;
use App\Services\TicketStatusService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class RegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event.title')
                    ->label('Event')
                    ->searchable(),
                TextColumn::make('registration_code')
                    ->label('Ticket Code')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Peserta')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('participant_type')
                    ->label('Kategori')
                    ->formatStateUsing(fn (string $state): string => $state === 'mahasiswa_gunadarma' ? 'Mahasiswa UG' : 'Umum')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'mahasiswa_gunadarma' ? 'info' : 'gray')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('No. HP')
                    ->searchable(),
                TextColumn::make('student_id')
                    ->label('NPM')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('campus_area')
                    ->label('Area Kampus')
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : '-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('class_year')
                    ->label('Angkatan')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('study_program')
                    ->label('Program Studi')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('payment_status')
                    ->label('Status Transaksi')
                    ->formatStateUsing(fn (string $state): string => Registration::transactionStatusLabels()[$state] ?? ucfirst($state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'failed', 'cancelled' => 'danger',
                        'expired', 'refunded' => 'gray',
                        default => 'warning',
                    })
                    ->searchable(),
                TextColumn::make('registration_status')
                    ->label('Status Pendaftaran')
                    ->formatStateUsing(fn (?string $state): string => $state ? (Registration::registrationStatusLabels()[$state] ?? ucfirst($state)) : 'Belum terdaftar')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'registered' => 'success',
                        'checked_in' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'warning',
                    })
                    ->searchable(),
                TextColumn::make('checked_in_at')
                    ->label('Waktu Check-in')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('checkedInBy.name')
                    ->label('Check-in Oleh')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Waktu Mendaftar')
                    ->dateTime('d M Y, H:i')
                    ->description(fn (Registration $record): string => $record->created_at?->diffForHumans() ?? '-')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event_id')
                    ->label('Event')
                    ->relationship('event', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('payment_status')
                    ->label('Status Transaksi')
                    ->options(Registration::transactionStatusLabels()),
                SelectFilter::make('registration_status')
                    ->label('Status Pendaftaran')
                    ->options(Registration::registrationStatusLabels()),
                SelectFilter::make('ticket_status')
                    ->label('Status Tiket')
                    ->options([
                        'valid' => 'Valid',
                        'pending' => 'Menunggu Pembayaran',
                        'used' => 'Sudah Digunakan',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'valid' => $query->where('payment_status', 'paid')->whereIn('registration_status', ['registered', 'completed'])->whereNull('checked_in_at'),
                            'pending' => $query->where(fn (Builder $query) => $query->where('payment_status', '!=', 'paid')->orWhereNull('registration_status')),
                            'used' => $query->where(fn (Builder $query) => $query->whereNotNull('checked_in_at')->orWhere('registration_status', 'checked_in')),
                            'cancelled' => $query->where('registration_status', 'cancelled'),
                            default => $query,
                        };
                    }),
            ])
            ->groups([
                Group::make('event.title')
                    ->label('Event')
                    ->collapsible(),
            ])
            ->defaultGroup('event.title')
            ->groupingDirectionSettingHidden()
            ->defaultSort('created_at', 'asc')
            ->recordActions([
                Action::make('view_ticket')
                    ->label('Lihat Tiket')
                    ->icon('heroicon-o-qr-code')
                    ->url(fn (Registration $record): string => $record->verificationUrl())
                    ->openUrlInNewTab(),
                Action::make('check_in')
                    ->label('Check-in')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Registration $record): bool => app(TicketStatusService::class)->status($record) === 'valid')
                    ->requiresConfirmation()
                    ->modalHeading('Check-in Peserta')
                    ->modalDescription(fn (Registration $record): string => 'Konfirmasi check-in untuk '.$record->name.' dengan tiket '.$record->registration_code.'.')
                    ->action(function (Registration $record): void {
                        try {
                            app(TicketCheckInService::class)->checkIn($record, auth()->user());
                            Notification::make()->title('Check-in berhasil')->success()->send();
                        } catch (RuntimeException $exception) {
                            Notification::make()->title('Check-in ditolak')->body($exception->getMessage())->danger()->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
