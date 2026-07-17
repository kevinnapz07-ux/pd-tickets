<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminEventController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        $events = Event::withCount(['registrations', 'paidRegistrations'])
            ->orderByDesc('starts_at')
            ->get();

        return view('admin.events.index', compact('events'));
    }

    public function create(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('admin.events.form', ['event' => new Event]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $this->withStatusFields($request, $this->validatedData($request));
        $data['slug'] = $this->uniqueSlug($data['title']);

        Event::create($data);

        return redirect()->route('admin.events.index')->with('status', 'Event berhasil dibuat.');
    }

    public function edit(Request $request, Event $event): View
    {
        $this->authorizeAdmin($request);

        return view('admin.events.form', compact('event'));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $this->withStatusFields($request, $this->validatedData($request));
        $data['slug'] = $this->uniqueSlug($data['title'], $event->id);

        $event->update($data);

        return redirect()->route('admin.events.index')->with('status', 'Event berhasil diperbarui.');
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $event->delete();

        return redirect()->route('admin.events.index')->with('status', 'Event dan seluruh aktivitas pesertanya berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string'],
            'speaker' => ['nullable', 'string', 'max:120'],
            'location' => ['required', 'string', 'max:160'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'quota' => ['required', 'integer', 'min:1'],
            'pricing_type' => ['required', 'in:free,paid'],
            'price' => ['nullable', 'required_if:pricing_type,paid', 'integer', 'min:1'],
            'registration_status' => ['nullable', 'in:open,upcoming'],
        ]);
    }

    private function withStatusFields(Request $request, array $data): array
    {
        $registrationStatus = $data['registration_status']
            ?? ($request->boolean('registration_is_open', true) ? 'open' : 'upcoming');

        unset($data['registration_status']);

        $data['price'] = $data['pricing_type'] === 'paid' ? (int) $data['price'] : 0;
        unset($data['pricing_type']);

        $data['is_published'] = $request->boolean('is_published');
        $data['registration_is_open'] = $registrationStatus === 'open';

        return $data;
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Event::slugBase($title);
        $slug = $base;
        $counter = 2;

        while (Event::where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }
}
