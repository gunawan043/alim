<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Kunjungan - {{ $visit?->visitor_name ?? 'Kunjungan' }}</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }

        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

        body {
            background: #f0f2f5;
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 0;
            color: #000;
            font-size: 8pt;
            line-height: 1.25;
        }

        .thermal-wrapper {
            width: 58mm;
            margin: 0 auto;
            background: #fff;
            padding: 2px 2.5mm;
        }

        /* Print controls */
        .no-print-area {
            text-align: center;
            padding: 15px;
        }
        .no-print-area button {
            padding: 6px 16px;
            font-size: 12px;
        }

        /* Logo */
        .logo-area {
            text-align: center;
            padding: 3px 0;
            border-bottom: 1px dashed #000;
            margin-bottom: 3px;
        }
        .logo-area .logo-text {
            font-size:9pt;
            font-weight: 700;
        }
        .logo-area .logo-sub {
            font-size: 5pt;
            margin-top: -10px;
            color: #555;
        }

        /* Header */
        .card-header {
            text-align: center;
            padding: 3px 5px;
            border-bottom: 1px solid #000;
            margin-bottom: 3px;
        }
        .card-header .title {
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .card-header .dormitory-name {
            font-size: 7pt;
            font-weight: 600;
            margin-bottom: 1px;
        }

        /* Table-based info — compact for 58mm */
        .info-body table.info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
            margin-left: 1px;
            margin-right: 1px;
        }
        .info-table td {
            vertical-align: top;
            padding: 0;
            line-height: 1.3;
        }
        .info-table .label-col {
            width: 35%;
            white-space: nowrap;
            padding-right: 1px;
        }
        .info-table .sep-col {
            width: 3mm;
            text-align: center;
            white-space: nowrap;
        }
        .info-table .value-col {
            width: auto;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        .info-table .sep-col {
            padding: 0 1px;
        }

        /* Section divider */
        .section-divider {
            border: none;
            border-top: 1px dashed #000;
            margin: 3px 4px;
        }

        /* QR section */
        .qr-section {
            text-align: center;
            padding: 3px 0;
        }
        .qr-section img {
            width: 40mm;
            height: 40mm;
        }

        /* Footer table */
        .footer-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .status-badge {
            display: inline-block;
            padding: 0 3px;
            border: 1px solid #000;
            font-size: 6pt;
        }

        /* Screen preview shadow */
        .print-preview {
            box-shadow: 0 2px 12px rgba(0,0,0,0.12);
        }

        @media print {
            body {
                background: none;
                padding: 0;
                margin: 0;
            }
            .no-print-area {
                display: none !important;
            }
            .thermal-wrapper {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    {{-- Print controls --}}
    <div class="no-print-area no-print">
        <button onclick="window.print()" style="padding:6px 16px;font-size:12px;">
            <i class="ri-printer-line"></i> Cetak Kartu
        </button>
        <a href="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
           style="margin-left:8px;padding:6px 16px;font-size:12px;text-decoration:none;color:#333;">
            Kembali
        </a>
    </div>

    <div class="thermal-wrapper print-preview">

        {{-- Logo Area --}}
        <div class="logo-area">
            <div class="logo-text">PONPES ABU HURAIRAH MATARAM</div> <br>
            <div class="logo-sub" style="font-size:7px;">Jl. Majapahit No.54B, Punia, Kec. Sekarbela, Kota Mataram, Nusa Tenggara Bar. 83115</div>
        </div>

        {{-- Header --}}
        <div class="card-header">
            <div class="dormitory-name">{{ $dormitory->name ?? 'Asrama Santri' }}</div>
            <div class="title">KARTU KUNJUNGAN SANTRI</div>
        </div>

        {{-- Body --}}
        <div class="info-body">
            <table class="info-table">
                {{-- Row 1: Nama Kunjungan --}}
                <tr>
                    <td class="label-col">Nama</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ $visit->visitor_name ?? '—' }}</td>
                </tr>
                {{-- Row 2: ID / No. KTP --}}
                <tr>
                    <td class="label-col">No. KTP</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ $visit->visitor_id_number ?? '—' }}</td>
                </tr>
                {{-- Row 3: Hubungan --}}
                <tr>
                    <td class="label-col">Hub.</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ $visit->visitor_relationship ?? '—' }}</td>
                </tr>
                {{-- Row 4: Telepon --}}
                <tr>
                    <td class="label-col">Telp</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ $visit->visitor_phone ?? '—' }}</td>
                </tr>

                <tr><td colspan="3"><hr class="section-divider" style="border:none;border-top:1px dashed #000;margin:2px 0;"></td></tr>

                {{-- Row 5: Santri --}}
                <tr>
                    <td class="label-col">Santri</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ $visit->student?->name ?? '—' }}</td>
                </tr>
                {{-- Row 6: Kamar --}}
                <tr>
                    <td class="label-col">Kamar</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ $visit->room?->name ?? '—' }}</td>
                </tr>

                {{-- Row 7: Kelas Santri --}}
                <tr>
                    <td class="label-col">Kelas</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ $visit->student?->entry_grade_level ?? '—' }}</td>
                </tr>
            </table>

            {{-- QR Code --}}
            <div class="qr-section">
                @php
                    $token = $visit->scan_token ?? null;
                    if (! $token) {
                        $raw = (string) $visit->id;
                        $sig = hash_hmac('sha256', $raw, config('app.key'));
                        $token = base64_encode($raw.'::'.$sig); // similar to getOrCreateScanToken
                    }
                    $qrData = base64_encode($token); // double encode like permit
                    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrData);
                @endphp
                <img src="{{ $qrUrl }}" alt="QR">
            </div>

            <hr class="section-divider" style="border:none;border-top:1px dashed #000;margin:3px 4px;">

            {{-- Tanggal Cetak --}}
            <table class="info-table">
                <tr>
                    <td class="label-col">Dicetak</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</td>
                </tr>
            </table>
        </div>

        {{-- Footer --}}
        <div class="card-footer">
            <div class="footer-row">
                @if($visit->is_special_permission)
                    <span class="status-badge">IZIN KHUSUS</span>
                @endif
            </div>
        </div>

        {{-- Logo Alim --}}
        <div class="logo-area" style="margin-top:6px;">
            <img src="{{ URL::asset('build/images/alim-dark-name.png') }}" alt="" height="42">
        </div>
    </div>
</body>
</html>