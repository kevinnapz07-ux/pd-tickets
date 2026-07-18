<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Registrasi Event PD Gunadarma</title>
    <style>
        @page { margin: 18mm 10mm 14mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #172033;
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 7px;
            line-height: 1.35;
        }
        .report-page {
            page-break-after: always;
        }
        .report-page:last-child {
            page-break-after: auto;
        }
        .document-header {
            width: 100%;
            margin-bottom: 8px;
            border-collapse: collapse;
        }
        .document-header td {
            vertical-align: middle;
        }
        .brand-cell {
            width: 55%;
        }
        .brand-lockup {
            display: table;
            width: 100%;
        }
        .brand-logo,
        .brand-copy {
            display: table-cell;
            vertical-align: middle;
        }
        .brand-logo {
            width: 42px;
        }
        .brand-logo img {
            display: block;
            width: 34px;
            height: 34px;
        }
        .brand-copy strong {
            display: block;
            color: #14213d;
            font-size: 14px;
            line-height: 1.15;
        }
        .brand-copy span {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 7.5px;
        }
        .document-title {
            width: 45%;
            text-align: right;
        }
        .document-title span {
            display: block;
            color: #2563eb;
            font-size: 7px;
            font-weight: bold;
            letter-spacing: .8px;
            text-transform: uppercase;
        }
        .document-title h1 {
            margin: 3px 0 0;
            color: #14213d;
            font-size: 15px;
            line-height: 1.2;
        }
        .accent-line {
            height: 3px;
            margin-bottom: 9px;
            border-radius: 2px;
            background: #2563eb;
        }
        .event-heading {
            margin-bottom: 8px;
            padding: 9px 10px;
            border: 1px solid #dbe5f1;
            border-radius: 6px;
            background: #f7faff;
        }
        .event-heading h2 {
            margin: 0 0 3px;
            color: #14213d;
            font-size: 12px;
            line-height: 1.2;
        }
        .event-heading p {
            margin: 0;
            color: #64748b;
            font-size: 7.5px;
        }
        .metadata,
        .summary {
            width: 100%;
            margin-bottom: 8px;
            border-collapse: separate;
            border-spacing: 4px 0;
        }
        .metadata {
            margin-left: -4px;
            margin-right: -4px;
        }
        .metadata td {
            width: 16.66%;
            padding: 6px 7px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            vertical-align: top;
        }
        .metadata span,
        .summary span {
            display: block;
            margin-bottom: 2px;
            color: #64748b;
            font-size: 6px;
            font-weight: bold;
            letter-spacing: .25px;
            text-transform: uppercase;
        }
        .metadata strong {
            display: block;
            color: #172033;
            font-size: 7.5px;
            line-height: 1.3;
        }
        .summary {
            margin-left: -4px;
            margin-right: -4px;
        }
        .summary td {
            width: 33.33%;
            padding: 7px 9px;
            border-radius: 4px;
            background: #14213d;
            color: #fff;
        }
        .summary span {
            color: #bfdbfe;
        }
        .summary strong {
            display: block;
            font-size: 12px;
            line-height: 1;
        }
        table.participants {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .participants thead {
            display: table-header-group;
        }
        .participants tr {
            page-break-inside: avoid;
        }
        .participants th {
            padding: 5px 3px;
            border: 1px solid #1d4ed8;
            background: #2563eb;
            color: #fff;
            font-size: 5.8px;
            line-height: 1.2;
            text-align: left;
            vertical-align: middle;
        }
        .participants td {
            padding: 5px 3px;
            border: 1px solid #dbe5f1;
            color: #273449;
            font-size: 5.9px;
            line-height: 1.28;
            overflow-wrap: break-word;
            vertical-align: top;
        }
        .participants tbody tr:nth-child(even) td {
            background: #f8fafc;
        }
        .participants .number {
            text-align: center;
        }
        .participants .code {
            color: #1d4ed8;
            font-weight: bold;
        }
        .participants .status {
            font-weight: bold;
        }
        .participants .empty {
            padding: 14px;
            color: #64748b;
            text-align: center;
        }
        .footer-note {
            position: fixed;
            right: 0;
            bottom: -8mm;
            left: 0;
            padding-top: 5px;
            border-top: 1px solid #dbe5f1;
            color: #64748b;
            font-size: 6.5px;
        }
        .w-no { width: 3%; }
        .w-code { width: 9%; }
        .w-name { width: 10%; }
        .w-email { width: 14%; }
        .w-phone { width: 8%; }
        .w-gender { width: 8%; }
        .w-domicile { width: 7%; }
        .w-category { width: 10%; }
        .w-payment { width: 12%; }
        .w-registration { width: 9%; }
        .w-checkin { width: 10%; }
    </style>
</head>
<body>
    <div class="footer-note">Dihasilkan secara otomatis oleh Sistem PD Gunadarma Event.</div>

    @forelse ($eventReports as $eventReport)
        @php
            $event = $eventReport['event'];
            $rows = $eventReport['rows'];
            $stats = $eventReport['stats'];
        @endphp
        <section class="report-page">
            <table class="document-header">
                <tr>
                    <td class="brand-cell">
                        <div class="brand-lockup">
                            <div class="brand-logo">
                                @if ($logoDataUri)
                                    <img src="{{ $logoDataUri }}" alt="">
                                @endif
                            </div>
                            <div class="brand-copy">
                                <strong>PD Gunadarma Event</strong>
                                <span>Dokumen resmi administrasi event</span>
                            </div>
                        </div>
                    </td>
                    <td class="document-title">
                        <span>Laporan Resmi</span>
                        <h1>Laporan Registrasi Event</h1>
                    </td>
                </tr>
            </table>
            <div class="accent-line"></div>

            <div class="event-heading">
                <h2>{{ $event->title }}</h2>
                <p>{{ $event->starts_at->locale('id')->translatedFormat('l, d F Y') }} | {{ $event->starts_at->format('H:i') }} WIB | {{ $event->location }}</p>
            </div>

            <table class="metadata">
                <tr>
                    <td><span>Tanggal Event</span><strong>{{ $event->starts_at->locale('id')->translatedFormat('d F Y') }}</strong></td>
                    <td><span>Lokasi</span><strong>{{ $event->location }}</strong></td>
                    <td><span>Jenis Event</span><strong>{{ $event->price > 0 ? 'Berbayar' : 'Gratis' }}</strong></td>
                    <td><span>Kuota Event</span><strong>{{ $event->quota }}</strong></td>
                    <td><span>Tanggal Cetak</span><strong>{{ $printedAt->format('d/m/Y H:i') }} WIB</strong></td>
                    <td><span>Dicetak Oleh</span><strong>{{ $printedBy }}</strong></td>
                </tr>
            </table>

            <table class="summary">
                <tr>
                    <td><span>Total Registrasi</span><strong>{{ $stats['total_registrations'] }}</strong></td>
                    <td><span>Total Pembayaran Berhasil</span><strong>{{ $stats['total_paid'] }}</strong></td>
                    <td><span>Total Check-in</span><strong>{{ $stats['total_check_in'] }}</strong></td>
                </tr>
            </table>

            <table class="participants">
                <thead>
                    <tr>
                        <th class="w-no number">No</th>
                        <th class="w-code">Kode Registrasi</th>
                        <th class="w-name">Nama Peserta</th>
                        <th class="w-email">Email</th>
                        <th class="w-phone">Nomor HP</th>
                        <th class="w-gender">Jenis Kelamin</th>
                        <th class="w-domicile">Domisili</th>
                        <th class="w-category">Kategori Peserta</th>
                        <th class="w-payment">Status Pembayaran</th>
                        <th class="w-registration">Status Registrasi</th>
                        <th class="w-checkin">Status Check-in</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="number">{{ $loop->iteration }}</td>
                            <td class="code">{{ $row['code'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['email'] }}</td>
                            <td>{{ $row['phone'] }}</td>
                            <td>{{ $row['gender'] }}</td>
                            <td>{{ $row['domicile'] }}</td>
                            <td>{{ $row['category'] }}</td>
                            <td class="status">{{ $row['payment_status'] }}</td>
                            <td class="status">{{ $row['registration_status'] }}</td>
                            <td class="status">
                                {{ $row['check_in_status'] }}
                                @if ($row['checked_in_at'])
                                    <br>{{ $row['checked_in_at'] }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty" colspan="11">Belum ada data registrasi untuk event ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    @empty
        <section class="report-page">
            <table class="document-header">
                <tr><td class="brand-cell"><div class="brand-copy"><strong>PD Gunadarma Event</strong></div></td><td class="document-title"><h1>Laporan Registrasi Event</h1></td></tr>
            </table>
            <div class="accent-line"></div>
            <div class="event-heading"><h2>Belum ada data laporan</h2><p>Ubah filter atau tambahkan registrasi untuk menghasilkan laporan.</p></div>
        </section>
    @endforelse
</body>
</html>
