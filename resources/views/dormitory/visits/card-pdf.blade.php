<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Kunjungan - {{ $visit?->visitor_name ?? 'Kunjungan' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page { margin: 0; }

        body {
            width: 58mm;
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace;
            font-size: 7.1pt;
            line-height: 1.2;
            color: #000;
            background: #fff;
        }

        .card {
            width: 55mm;
            padding: 1.5mm;
            margin: 0 auto;
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
            font-size: 6pt;
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
        .info-table .label-col {
            width: 32%;
            white-space: nowrap;
            padding-right: 1mm;
        }
        .info-table .sep-col {
            width: 3mm;
            text-align: center;
            white-space: nowrap;
        }
        .info-table .value-col {
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

        .footer-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .status-badge {
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
            <div class="title">KARTU KUNJUNGAN SANTRI</div>
        </div>

        @php
            $studentName = $visit->student?->name ?? '—';
            $nameLength = mb_strlen($studentName);
            $nameSize = $nameLength <= 18 ? '12pt' : ($nameLength <= 24 ? '10.5pt' : ($nameLength <= 30 ? '9pt' : '7.8pt'));
        @endphp
        <div class="student-name" style="font-size:{{ $nameSize }}">{{ $studentName }}</div>

        <table class="info-table">
            <tr>
                <td class="label-col">Tamu</td>
                <td class="sep-col">:</td>
                <td class="value-col">{{ $visit->visitor_name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label-col">No. KTP</td>
                <td class="sep-col">:</td>
                <td class="value-col">{{ $visit->visitor_id_number ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label-col">Hub.</td>
                <td class="sep-col">:</td>
                <td class="value-col">{{ $visit->visitor_relationship ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label-col">Telp</td>
                <td class="sep-col">:</td>
                <td class="value-col">{{ $visit->visitor_phone ?? '—' }}</td>
            </tr>

            <tr><td colspan="3"><hr class="section-divider"></td></tr>

            <tr>
                <td class="label-col">Kamar</td>
                <td class="sep-col">:</td>
                <td class="value-col">{{ $visit->room?->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label-col">Kelas</td>
                <td class="sep-col">:</td>
                <td class="value-col">{{ $visit->student?->entry_grade_level ?? '—' }}</td>
            </tr>
        </table>

        <div class="qr-section">
            @php
                $token = $visit->makeCardToken();
                $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(200)
                    ->margin(2)
                    ->generate($token);
            @endphp
            <img src="data:image/svg+xml;base64,{{ base64_encode((string) $qrImage) }}" alt="QR">
        </div>

        <hr class="section-divider" style="margin:2mm 1mm">

        <table class="info-table">
            <tr>
                <td class="label-col">Dicetak</td>
                <td class="sep-col">:</td>
                <td class="value-col">{{ $now->format('d/m/Y H:i') }}</td>
            </tr>
        </table>

        <div style="border-top:1px solid #000; padding:2mm 1mm; margin-top:1mm">
            <div class="footer-row">
                @if($visit->is_special_permission)
                    <span class="status-badge">IZIN KHUSUS</span>
                @endif
            </div>
            <div class="footer-tagline">Berkarakter dengan Ilmu, Amal, dan Adab</div>
        </div>

    </div>
</body>
</html>
