<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Izin - {{ $permit->student?->name ?? 'Santri' }}</title>
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
            font-size: 14pt;
            font-weight: 700;
            letter-spacing: 3px;
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
        <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
           style="margin-left:8px;padding:6px 16px;font-size:12px;text-decoration:none;color:#333;">
            Kembali
        </a>
    </div>

    <div class="thermal-wrapper print-preview">

        <div class="card-header" style="margin-top:10px">
            <div class="dormitory-name">PONPES ABU HURAIRAH MATARAM</div>
            <div class="logo-sub" style="font-size:7px;">Jl. Majapahit No.54B, Punia, Kec. Sekarbela, Kota Mataram, Nusa Tenggara Bar. 83115</div>
        </div>
        

        {{-- Header --}}
        <div class="card-header">
            <div class="dormitory-name">{{ $dormitory->name ?? 'Asrama Santri' }}</div>
            <div class="title">KARTU IZIN PULANG SANTRI</div>
        </div>

        {{-- Body --}}
        <div class="info-body">
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
                {{-- Row 1: Nama --}}
                <tr>
                    <td class="label-col">Nama</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ $permit->student?->name ?? '—' }}</td>
                </tr>
                {{-- Row 2: Kamar + Kelas (double value col) --}}
                <tr>
                    <td class="label-col">Kamar</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ $permit->room?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label-col">Kls</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ $kelas ?: '—' }}</td>
                </tr>

                <tr><td colspan="3"><hr class="section-divider" style="border:none;border-top:1px dashed #000;margin:2px 0;"></td></tr>

                {{-- Row 3: Tgl Pulang --}}
                <tr>
                    <td class="label-col">Pulang</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ $permit->departure_datetime ? $permit->departure_datetime->format('d/m/Y H:i') : '—' }}</td>
                </tr>
                {{-- Row 4: Tgl Kembali --}}
                <tr>
                    <td class="label-col">Kembali</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ $permit->expected_return_datetime ? $permit->expected_return_datetime->format('d/m/Y H:i') : '—' }}</td>
                </tr>

                <tr><td colspan="3"><hr class="section-divider" style="border:none;border-top:1px dashed #000;margin:2px 0;"></td></tr>

                {{-- Row 5: Penjemput --}}
                <tr>
                    <td class="label-col">Penjemput</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ $permit->companion_name ?? '—' }}</td>
                </tr>
                {{-- Row 6: Hubungan + Telepon --}}
                @if($permit->companion_relation || $permit->companion_phone)
                <tr>
                    <td class="label-col">Hub.</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ $permit->companion_relation ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label-col">Telp</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ $permit->companion_phone ?? '—' }}</td>
                </tr>
                @endif
                {{-- Row 7: Tujuan --}}
                <tr>
                    <td class="label-col">Tujuan</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ $permit->destination ?? '—' }}</td>
                </tr>
            </table>

            {{-- QR Code --}}
            <div class="qr-section">
                @php
                    $token = $permit->scan_token ?: $permit->getOrCreateScanToken();
                    $qrData = $token;
                    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrData);
                @endphp
                <img src="{{ $qrUrl }}" alt="QR">
            </div>

            <hr class="section-divider" style="border:none;border-top:1px dashed #000;margin:3px 4px;">

            {{-- Dicetak --}}
            <table class="info-table">
                <tr>
                    <td class="label-col">Dicetak</td>
                    <td class="sep-col">:</td>
                    <td class="value-col">{{ \Carbon\Carbon::parse($now)->format('d/m/Y H:i') }}</td>
                </tr>
            </table>
        </div>

        {{-- Footer --}}
        <div class="card-footer">
            <div class="footer-row">
                <!-- <span>
                    Status:
                    <span class="status-badge">
                        @if($permit->status === 'pending') Menunggu
                        @elseif($permit->status === 'approved') Disetujui
                        @elseif($permit->status === 'returned') Sudah Kembali Ke Asrama
                        @elseif($permit->status === 'rejected') Ditolak
                        @elseif($permit->status === 'overdue') Terlambat
                        @else {{ ucfirst($permit->status ?? '—') }}
                        @endif
                    </span>
                </span> -->
                @if($permit->is_emergency)
                    <span class="status-badge">DARURAT</span>
                @endif
            </div>

        </div>
        <hr class="section-divider mt-3" style="border:none;border-top:1px dashed #000;margin:3px 4px;">
        {{-- Logo Alim --}}
        <div class="logo-area">
            <img src="{{ URL::asset('build/images/alim-dark-name.png') }}" alt="" height="42">
            <!-- <div class="logo-sub">Academic Learning & Information Management</div> -->
        </div>
    </div>
</body>
</html>
