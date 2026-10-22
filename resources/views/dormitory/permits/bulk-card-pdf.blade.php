<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Kartu Izin — {{ $dormitory->name ?? 'Asrama Santri' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page { margin: 0; }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7.2pt;
            line-height: 1.22;
            color: #000;
            background: #fff;
        }

        .card {
            width: 58mm;
            padding: 2mm;
            page-break-after: always;
        }
        .card:last-child {
            page-break-after: avoid;
        }

        .section-divider {
            border: none;
            border-top: 1px dashed #000;
            margin: 1.25mm 0;
        }

        .logo-top {
            text-align: center;
            padding: 0 0 0.8mm;
        }
        .logo-top img {
            height: 13mm;
        }
        .student-name {
            text-align: center;
            font-weight: 700;
            line-height: 1.1;
            white-space: nowrap;
            letter-spacing: 0.2px;
            margin: 0.8mm 0 1.6mm;
            padding-bottom: 1mm;
            border-bottom: 1px dashed #000;
        }
        .header {
            text-align: center;
            padding: 1.1mm 1mm 1.3mm;
            border-bottom: 1.2px solid #000;
            margin-bottom: 1.35mm;
        }
        .header .school {
            font-size: 6.5pt;
            font-weight: 600;
            color: #444;
            margin-bottom: 0.5mm;
        }
        .header .dormitory {
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        .header .title {
            font-family: 'Courier New', Courier, monospace;
            font-size: 8.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 1mm;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            vertical-align: top;
            padding: 0;
        }
        .info-table .label {
            width: 32%;
            white-space: nowrap;
            padding-right: 1mm;
        }
        .info-table .sep {
            width: 3mm;
            text-align: center;
            white-space: nowrap;
        }
        .info-table .value {
            word-break: break-word;
        }

        .qr-section {
            text-align: center;
            padding: 1.2mm 0;
        }
        .qr-section img {
            width: 34mm;
            height: 34mm;
        }

        .footer {
            border-top: 1px solid #000;
            padding: 2mm 1mm;
            margin-top: 1mm;
        }
        .footer-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .badge {
            display: inline-block;
            padding: 0.5mm 2mm;
            border: 1px solid #000;
            font-size: 6pt;
            font-weight: 700;
        }

        .footer-tagline {
            text-align: center;
            font-size: 6.3pt;
            font-style: italic;
            letter-spacing: 0.1px;
            margin-top: 1mm;
        }

        .logo-area {
            text-align: center;
            padding: 2mm 0;
            border-top: 1px dashed #000;
            margin-top: 2mm;
        }
        .logo-area img {
            height: 10mm;
        }
    </style>
</head>
<body>
    @forelse($permits as $permit)
        @php
            $classHistory = $permit->student?->currentClassHistory;
            $kelas = '';
            if ($classHistory && $classHistory->studyGroup) {
                $sg = $classHistory->studyGroup;
                $parts = [];
                if ($sg->gradeLevel?->name) $parts[] = $sg->gradeLevel->name;
                if ($sg->name) $parts[] = $sg->name;
                $kelas = implode(' ', $parts) ?: '—';
            } elseif ($classHistory && $classHistory->gradeLevel) {
                $parts = [];
                if ($classHistory->gradeLevel?->name) $parts[] = $classHistory->gradeLevel->name;
                if ($classHistory->name) $parts[] = $classHistory->name;
                $kelas = implode(' ', $parts) ?: '—';
            }
        @endphp

        <div class="card">
        @php
            $logoPath = public_path('build/images/alim-dark-name.png');
            $logoData = is_file($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;
        @endphp
        @if($logoData)
            <div class="logo-top"><img src="{{ $logoData }}" alt="ALIM"></div>
        @endif
            <div class="header">
                <div class="school">PONPES ABU HURAIRAH MATARAM</div>
                <div class="dormitory">{{ $dormitory->name ?? 'Asrama Santri' }}</div>
                <div class="title">KARTU IZIN PULANG SANTRI</div>
            </div>

            @php
                $studentName = $permit->student?->name ?? '—';
                $nameLength = mb_strlen($studentName);
                $nameSize = $nameLength <= 18 ? '12pt' : ($nameLength <= 24 ? '10.5pt' : ($nameLength <= 30 ? '9pt' : '7.8pt'));
            @endphp
            <div class="student-name" style="font-size:{{ $nameSize }}">{{ $studentName }}</div>

            <table class="info-table">
                <tr>
                    <td class="label">Kamar</td>
                    <td class="sep">:</td>
                    <td class="value">{{ $permit->room?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Kls</td>
                    <td class="sep">:</td>
                    <td class="value">{{ $kelas ?: '—' }}</td>
                </tr>
                <tr><td colspan="3"><hr class="section-divider"></td></tr>
                <tr>
                    <td class="label">Pulang</td>
                    <td class="sep">:</td>
                    <td class="value">{{ $permit->departure_datetime ? $permit->departure_datetime->format('d/m/Y H:i') : '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Kembali</td>
                    <td class="sep">:</td>
                    <td class="value">{{ $permit->expected_return_datetime ? $permit->expected_return_datetime->format('d/m/Y H:i') : '—' }}</td>
                </tr>
                <tr><td colspan="3"><hr class="section-divider"></td></tr>
                <tr>
                    <td class="label">Penjemput</td>
                    <td class="sep">:</td>
                    <td class="value">{{ $permit->companion_name ?? '—' }}</td>
                </tr>
                @if($permit->companion_relation || $permit->companion_phone)
                <tr>
                    <td class="label">Hub.</td>
                    <td class="sep">:</td>
                    <td class="value">{{ $permit->companion_relation ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Telp</td>
                    <td class="sep">:</td>
                    <td class="value">{{ $permit->companion_phone ?? '—' }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Tujuan</td>
                    <td class="sep">:</td>
                    <td class="value">{{ $permit->destination ?? '—' }}</td>
                </tr>
                @if($permit->pickup_authorization_status === 'pending')
                <tr>
                    <td colspan="3" style="padding-top:1mm;font-size:6.2pt;font-weight:700;border-top:1px dashed #000;">
                        PENJEMPUT BELUM DIVERIFIKASI — SCAN KELUAR DIBLOKIR
                    </td>
                </tr>
                @elseif($permit->pickup_authorization_status === 'rejected')
                <tr>
                    <td colspan="3" style="padding-top:1mm;font-size:6.2pt;font-weight:700;border-top:1px dashed #000;">
                        PENJEMPUT DITOLAK — KARTU TIDAK BERLAKU
                    </td>
                </tr>
                @endif
            </table>

            <div class="qr-section">
                @php
                    $token = $permit->scan_token ?: $permit->getOrCreateScanToken();
                    $qrPayload = json_encode($permit->qrPayload() ?? ['token' => $token]);
                    $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                        ->size(200)
                        ->margin(2)
                        ->generate($qrPayload);
                @endphp
                <img src="data:image/svg+xml;base64,{{ base64_encode((string) $qrImage) }}" alt="QR">
            </div>

            <hr class="section-divider" style="margin:2mm 1mm">

            <table class="info-table">
                <tr>
                    <td class="label">Dicetak</td>
                    <td class="sep">:</td>
                    <td class="value">{{ $now->format('d/m/Y H:i') }}</td>
                </tr>
            </table>

            <div class="footer">
                <div class="footer-row">
                    @if($permit->is_emergency)
                        <span class="badge">DARURAT</span>
                    @endif
                </div>
                <div class="footer-tagline">Berkarakter dengan Ilmu, Amal, dan Adab</div>
            </div>

        </div>
    @empty
        <p>Tidak ada izin yang memenuhi filter.</p>
    @endforelse
</body>
</html>
