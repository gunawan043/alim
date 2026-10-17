<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Izin - {{ $permit->student?->name ?? 'Santri' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7.5pt;
            line-height: 1.3;
            color: #000;
            background: #fff;
        }

        .card {
            width: 58mm;
            padding: 2mm;
            margin: 0 auto;
        }

        .section-divider {
            border: none;
            border-top: 1px dashed #000;
            margin: 2mm 0;
        }

        .header {
            text-align: center;
            padding: 2mm 1mm;
            border-bottom: 1.5px solid #000;
            margin-bottom: 2mm;
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
            padding: 2mm 0;
        }
        .qr-section img {
            width: 38mm;
            height: 38mm;
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
    <div class="card">

        <div class="header" style="margin-top:3mm">
            <div class="school">PONPES ABU HURAIRAH MATARAM</div>
            <div class="dormitory">{{ $dormitory->name ?? 'Asrama Santri' }}</div>
            <div class="title">KARTU IZIN PULANG SANTRI</div>
        </div>

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

        <table class="info-table">
            <tr>
                <td class="label">Nama</td>
                <td class="sep">:</td>
                <td class="value">{{ $permit->student?->name ?? '—' }}</td>
            </tr>
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
        </table>

        <div class="qr-section">
            @php
                $token = $permit->scan_token ?: $permit->getOrCreateScanToken();
                $qrPayload = json_encode($permit->qrPayload() ?? ['token' => $token]);
                $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(200)
                    ->margin(2)
                    ->generate($qrPayload);
            @endphp
            <img src="data:image/png;base64,{{ base64_encode($qrImage) }}" alt="QR">
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
        </div>

        <div class="logo-area">
            <img src="{{ URL::asset('build/images/alim-dark-name.png') }}" alt="ALIM">
        </div>

    </div>
</body>
</html>
