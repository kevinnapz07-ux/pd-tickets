<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $events = Event::withCount(['registrations', 'paidRegistrations'])
            ->orderBy('starts_at')
            ->get();

        $registrations = Registration::with(['event', 'payment'])
            ->latest()
            ->take(15)
            ->get();

        $stats = [
            'events' => Event::count(),
            'users' => User::count(),
            'registrations' => Registration::count(),
            'paid' => Registration::where('payment_status', 'paid')->count(),
            'pending' => Registration::where('payment_status', 'pending')->count(),
            'checked_in' => Registration::where('registration_status', 'checked_in')->count(),
        ];

        return view('admin.dashboard', compact('events', 'registrations', 'stats'));
    }
}
