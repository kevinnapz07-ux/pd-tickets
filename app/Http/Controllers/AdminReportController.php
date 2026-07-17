<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('admin.reports.index', $this->reportData($request));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeAdmin($request);

        $data = $this->reportData($request);
        $selectedEvent = $data['selectedEvent'];
        $fileName = 'laporan-registrasi-'.($selectedEvent ? Str::slug($selectedEvent->title) : 'semua-event').'.xls';

        return response()->streamDownload(function () use ($data): void {
            echo '<!doctype html><html><head><meta charset="UTF-8">';
            echo '<style>
                body { font-family: Arial, sans-serif; color: #111827; }
                table { border-collapse: collapse; width: 100%; table-layout: fixed; }
                th, td { border: 1px solid #d9d9d9; padding: 7px 9px; font-size: 12px; vertical-align: top; mso-number-format:"\@"; }
                .title-row th { background: #4f3b82; color: #ffffff; font-size: 16px; text-align: left; padding: 10px; }
                .meta-row th { background: #ede7f6; color: #2f2454; text-align: left; font-weight: 700; width: 180px; }
                .meta-row td { background: #faf7ff; }
                .section-row th { background: #6b4ca0; color: #ffffff; text-align: left; font-size: 13px; padding: 9px; }
                .event-meta th { background: #f3eefb; color: #3a2a63; text-align: left; width: 180px; }
                .event-meta td { background: #fbf9ff; }
                .header-row th { background: #5b3f8f; color: #ffffff; font-weight: 700; text-align: left; }
                .sub-note { display: block; font-size: 10px; font-style: italic; color: #eee7ff; font-weight: 400; }
                .zebra td { background: #f8f8fb; }
                .empty td { color: #6b7280; font-style: italic; }
                .spacer td { border: 0; height: 12px; padding: 0; }
                .center { text-align: center; }
                .nowrap { white-space: nowrap; }
            </style>';
            echo '</head><body><table>';

            echo '<tr class="title-row"><th colspan="10">Laporan Registrasi Event PD Gunadarma</th></tr>';
            echo '<tr class="meta-row"><th>Event</th><td colspan="9">'.e($data['selectedEvent']?->title ?? 'Semua Event').'</td></tr>';
            echo '<tr class="meta-row"><th>Dicetak Pada</th><td colspan="9">'.e(now()->format('d/m/Y H:i').' WIB').'</td></tr>';
            echo '<tr class="spacer"><td colspan="10"></td></tr>';
            echo '<tr class="section-row"><th colspan="10">Ringkasan Kehadiran</th></tr>';
            echo '<tr class="header-row"><th>Terdaftar</th><th>Check-in</th><th>Selesai</th><th>Dibatalkan</th><th colspan="6"></th></tr>';
            echo '<tr><td>'.e($data['attendanceStats']['registered']).'</td><td>'.e($data['attendanceStats']['checked_in']).'</td><td>'.e($data['attendanceStats']['completed']).'</td><td>'.e($data['attendanceStats']['cancelled']).'</td><td colspan="6"></td></tr>';
            echo '<tr class="spacer"><td colspan="10"></td></tr>';

            foreach ($data['eventReports'] as $eventReport) {
                $event = $eventReport['event'];
                $items = $eventReport['registrations'];

                echo '<tr class="section-row"><th colspan="10">'.e($event->title).'</th></tr>';
                echo '<tr class="event-meta"><th>Lokasi</th><td colspan="9">'.e($event->location).'</td></tr>';
                echo '<tr class="event-meta"><th>Tanggal</th><td colspan="9">'.e($event->starts_at->format('d/m/Y H:i').' WIB').'</td></tr>';
                echo '<tr class="event-meta"><th>Jenis Pendaftaran</th><td colspan="9">'.e($event->price > 0 ? 'Berbayar' : 'Gratis').'</td></tr>';
                echo '<tr class="event-meta"><th>Kuota Terisi</th><td colspan="9">'.e($items->where('registration_status', '!=', 'cancelled')->count().'/'.$event->quota).'</td></tr>';
                echo '<tr class="event-meta"><th>Total Check-in</th><td colspan="9">'.e($items->where('registration_status', 'checked_in')->count()).'</td></tr>';
                echo '<tr class="header-row">
                    <th class="center nowrap">No</th>
                    <th>Kode Registrasi</th>
                    <th>Nama Lengkap</th>
                    <th>Email</th>
                    <th>No. HP</th>
                    <th>Kategori Peserta</th>
                    <th>Data Tambahan</th>
                    <th>Status Transaksi</th>
                    <th>Status Pendaftaran</th>
                    <th>Waktu Check-in</th>
                </tr>';

                if ($items->isEmpty()) {
                    echo '<tr class="empty"><td colspan="10">Tidak ada data laporan untuk event ini.</td></tr>';
                    echo '<tr class="spacer"><td colspan="10"></td></tr>';

                    continue;
                }

                foreach ($items->values() as $index => $registration) {
                    $categoryLabel = collect($registration->event->registrationCategories())
                        ->firstWhere('key', $registration->participant_type)['label']
                        ?? Str::headline(str_replace('_', ' ', (string) $registration->participant_type));

                    $customFields = collect($registration->custom_fields ?? [])
                        ->map(fn ($value, string $field): string => e(Event::registrationFieldLabel($field)).': '.e($value))
                        ->implode('<br>');

                    $rowClass = $index % 2 === 1 ? ' class="zebra"' : '';

                    echo '<tr'.$rowClass.'>';
                    echo '<td class="center">'.e($index + 1).'</td>';
                    echo '<td>'.e($registration->registration_code).'</td>';
                    echo '<td>'.e($registration->name).'</td>';
                    echo '<td>'.e($registration->email).'</td>';
                    echo '<td>'.e($registration->phone).'</td>';
                    echo '<td>'.e($categoryLabel).'</td>';
                    echo '<td>'.($customFields ?: '-').'</td>';
                    echo '<td>'.e($registration->event->price > 0 ? $registration->transactionStatusLabel() : '-').'</td>';
                    echo '<td>'.e($registration->registrationStatusLabel()).'</td>';
                    echo '<td>'.e($registration->checked_in_at?->format('d/m/Y H:i') ?? '-').'</td>';
                    echo '</tr>';
                }

                echo '<tr class="spacer"><td colspan="10"></td></tr>';
            }

            echo '</table></body></html>';
        }, $fileName, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function reportData(Request $request): array
    {
        $registrations = Registration::with(['event', 'payment'])
            ->when($request->query('event_id'), fn ($query, $eventId) => $query->where('event_id', $eventId))
            ->when($request->query('transaction_status'), fn ($query, $status) => $query->where('payment_status', $status))
            ->when($request->query('registration_status'), fn ($query, $status) => $query->where('registration_status', $status))
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
            'transactionStatuses' => Registration::transactionStatusLabels(),
            'registrationStatuses' => Registration::registrationStatusLabels(),
            'attendanceStats' => $attendanceStats,
        ];
    }

    private function eventReports(Collection $registrations, ?Event $selectedEvent): Collection
    {
        if ($selectedEvent && $registrations->isEmpty()) {
            return collect([
                [
                    'event' => $selectedEvent,
                    'registrations' => collect(),
                ],
            ]);
        }

        return $registrations
            ->groupBy('event_id')
            ->map(fn (Collection $items): array => [
                'event' => $items->first()->event,
                'registrations' => $items,
            ])
            ->values();
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }
}
