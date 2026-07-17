<?php

namespace App\Filament\Pages;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    protected string $view = 'filament.pages.dashboard';

    protected function getViewData(): array
    {
        return [
            'events' => Event::withCount(['registrations', 'paidRegistrations'])->orderBy('starts_at')->get(),
            'registrations' => Registration::with(['event', 'payment'])->latest()->take(15)->get(),
            'stats' => [
                'events' => Event::count(),
                'users' => User::count(),
                'registrations' => Registration::count(),
                'paid' => Registration::where('payment_status', 'paid')->count(),
                'pending' => Registration::where('payment_status', 'pending')->count(),
                'checked_in' => Registration::where('registration_status', 'checked_in')->count(),
            ],
        ];
    }
}
