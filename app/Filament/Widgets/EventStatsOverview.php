<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use App\Models\Registration;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EventStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Event', Event::count())
                ->description('Total event tersimpan'),
            Stat::make('Registrasi', Registration::count())
                ->description('Total peserta terdaftar'),
            Stat::make('Transaksi Berhasil', Registration::where('payment_status', 'paid')->count())
                ->description('Pembayaran berhasil'),
            Stat::make('Menunggu Pembayaran', Registration::where('payment_status', 'pending')->count())
                ->description('Menunggu pembayaran'),
            Stat::make('Check-in', Registration::where('registration_status', 'checked_in')->count())
                ->description('Peserta hadir'),
        ];
    }
}
