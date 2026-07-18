<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $events = Event::query()
            ->where('is_published', true)
            ->where('registration_is_open', true)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('speaker', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->withCount(['registrations', 'paidRegistrations'])
            ->orderBy('starts_at')
            ->get();

        $upcomingEvents = Event::query()
            ->where('is_published', true)
            ->where('registration_is_open', false)
            ->where('starts_at', '>=', now()->startOfDay())
            ->withCount(['registrations', 'paidRegistrations'])
            ->orderBy('starts_at')
            ->limit(3)
            ->get();

        return view('events.index', compact('events', 'upcomingEvents', 'search'));
    }

    public function show(Event $event): View
    {
        abort_unless($event->is_published, 404);

        $event->loadCount(['registrations', 'paidRegistrations']);

        return view('events.show', compact('event'));
    }

    public function profile(): View
    {
        $latestArticles = Article::published()
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('profile.pdug', compact('latestArticles'));
    }

    public function actors(): View
    {
        return view('actors.index');
    }
}
