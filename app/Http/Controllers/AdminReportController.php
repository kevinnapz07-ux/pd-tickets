<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AdminReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('admin.reports.index', $this->reportData($request));
    }

    public function pdf(Request $request): Response
    {
        $this->authorizeAdmin($request);

        $data = $this->reportData($request);
        $selectedEvent = $data['selectedEvent'];
        $fileName = 'laporan-registrasi-'.($selectedEvent ? Str::slug($selectedEvent->title) : 'semua-event').'.pdf';
        $logoPath = public_path(config('branding.logo'));
        $data['logoDataUri'] = is_file($logoPath)
            ? 'data:image/svg+xml;base64,'.base64_encode((string) file_get_contents($logoPath))
            : null;

        $pdf = Pdf::loadView('admin.reports.pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOption('isRemoteEnabled', false);
        $pdf->render();

        $canvas = $pdf->getDomPDF()->getCanvas();
        $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(
            690,
            570,
            'Halaman {PAGE_NUM} dari {PAGE_COUNT}  |  '.$data['printedAt']->format('d/m/Y H:i').' WIB',
            $font,
            7,
            [0.39, 0.45, 0.55],
        );

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    public function reportData(Request $request): array
    {
        $registrations = Registration::with(['event', 'payment'])
            ->when($request->query('event_id'), fn ($query, $eventId) => $query->where('event_id', $eventId))
            ->when($request->query('transaction_status'), function ($query, $status) {
                return $status === 'failed'
                    ? $query->whereIn('payment_status', ['failed', 'cancelled'])
                    : $query->where('payment_status', $status);
            })
            ->when($request->query('registration_status'), function ($query, $status) {
                return $status === 'registered'
                    ? $query->where('registration_status', '!=', 'cancelled')
                    : $query->where('registration_status', $status);
            })
            ->orderBy('created_at')
            ->get();

        $events = Event::orderBy('title')->get();
        $selectedEvent = $request->query('event_id') ? $events->firstWhere('id', (int) $request->query('event_id')) : null;
        $eventReports = $this->eventReports($registrations, $selectedEvent);
        $attendanceStats = [
            'registered' => $registrations->where('registration_status', 'registered')->count(),
            'checked_in' => $registrations->where('registration_status', 'checked_in')->count(),
            'completed' => $registrations->where('registration_status', 'completed')->count(),
            'cancelled' => $registrations->where('registration_status', 'cancelled')->count(),
        ];

        return [
            'registrations' => $registrations,
            'eventReports' => $eventReports,
            'selectedEvent' => $selectedEvent,
            'events' => $events,
            'transactionStatuses' => [
                'pending' => 'Menunggu Pembayaran',
                'paid' => 'Berhasil',
                'failed' => 'Gagal',
                'expired' => 'Kedaluwarsa',
                'refunded' => 'Refund',
            ],
            'registrationStatuses' => [
                'registered' => 'Terdaftar',
                'cancelled' => 'Dibatalkan',
            ],
            'attendanceStats' => $attendanceStats,
            'printedAt' => now(),
            'printedBy' => $request->user()?->name ?? 'Admin',
        ];
    }

    private function eventReports(Collection $registrations, ?Event $selectedEvent): Collection
    {
        if ($selectedEvent && $registrations->isEmpty()) {
            return collect([$this->eventReport($selectedEvent, collect())]);
        }

        return $registrations
            ->groupBy('event_id')
            ->map(fn (Collection $items): array => $this->eventReport($items->first()->event, $items))
            ->values();
    }

    private function eventReport(Event $event, Collection $registrations): array
    {
        $activeRegistrations = $registrations->where('registration_status', '!=', 'cancelled');

        return [
            'event' => $event,
            'registrations' => $registrations,
            'rows' => $registrations->values()->map(fn (Registration $registration): array => $this->registrationRow($registration)),
            'stats' => [
                'total_registrations' => $activeRegistrations->count(),
                'total_paid' => $event->price > 0
                    ? $activeRegistrations->where('payment_status', 'paid')->count()
                    : 0,
                'total_check_in' => $activeRegistrations
                    ->filter(fn (Registration $registration): bool => $this->hasCheckedIn($registration))
                    ->count(),
            ],
        ];
    }

    private function registrationRow(Registration $registration): array
    {
        $customFields = collect($registration->custom_fields ?? []);
        $gender = (string) $customFields->get('gender', '');
        $genderOptions = Event::registrationFieldDefinitions()['gender']['options'] ?? [];
        $categoryLabel = collect($registration->event->registrationCategories())
            ->firstWhere('key', $registration->participant_type)['label']
            ?? Str::headline(str_replace('_', ' ', (string) $registration->participant_type));

        return [
            'registration' => $registration,
            'code' => $registration->registration_code,
            'name' => $registration->name,
            'email' => $registration->email,
            'phone' => $registration->phone ?: '-',
            'gender' => $gender === '' ? '-' : ($genderOptions[$gender] ?? Str::headline(str_replace('_', ' ', $gender))),
            'domicile' => filled($customFields->get('domicile'))
                ? Str::title(Str::lower((string) $customFields->get('domicile')))
                : '-',
            'category' => $categoryLabel,
            'payment_status' => $this->paymentStatusLabel($registration),
            'registration_status' => $registration->registration_status === 'cancelled' ? 'Dibatalkan' : 'Terdaftar',
            'check_in_status' => $this->hasCheckedIn($registration) ? 'Sudah Check-in' : 'Belum Check-in',
            'checked_in_at' => $registration->checked_in_at
                ? $registration->checked_in_at->format('d/m/Y H:i').' WIB'
                : null,
        ];
    }

    private function paymentStatusLabel(Registration $registration): string
    {
        if ($registration->event->price <= 0) {
            return 'Gratis';
        }

        return match ($registration->payment_status) {
            'paid' => 'Berhasil',
            'pending' => 'Menunggu Pembayaran',
            'expired' => 'Kedaluwarsa',
            'refunded' => 'Refund',
            'failed', 'cancelled' => 'Gagal',
            default => 'Menunggu Pembayaran',
        };
    }

    private function hasCheckedIn(Registration $registration): bool
    {
        return $registration->checked_in_at !== null
            || in_array($registration->registration_status, ['checked_in', 'completed'], true);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }
}
