<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Kartu Izin — {{ $dormitory->name ?? 'Asrama Santri' }}</title>
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
            line-height: 1.3;
        }

        .thermal-wrapper {
            width: 58mm;
            margin: 0 auto;
            background: #fff;
            page-break-inside: avoid;
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
            padding: 4px 0;
            border-bottom: 1px dashed #000;
            margin-bottom: 3px;
        }
        .logo-area .logo-text {
            font-size: 14pt;
            font-weight: 700;
            letter-spacing: 3px;
        }
        .logo-area .logo-sub {
            font-size: 6pt;
            color: #555;
        }

        /* Header */
        .card-header {
            text-align: center;
            padding: 4px 5px;
            border-bottom: 1px solid #000;
            margin-bottom: 3px;
        }
        .card-header .title {
            font-size: 9pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Body info */
        .info-body {
            padding: 0 5px;
        }
        .info-row {
            display: flex;
            margin-bottom: 1px;
        }
        .info-row .label {
            flex-shrink: 0;
            width: 30mm;
            font-size: 8pt;
        }
        .info-row .sep {
            width: 3mm;
            text-align: center;
            flex-shrink: 0;
        }
        .info-row .value {
            flex: 1;
            font-size: 8pt;
            word-break: break-word;
        }
        .info-row .value.full {
            display: block;
            margin-left: 33mm;
        }

        /* Divider */
        .dashed-line {
            border: none;
            border-top: 1px dashed #000;
            margin: 4px 0;
        }

        /* QR section */
        .qr-section {
            text-align: center;
            padding: 4px 0;
        }
        .qr-section img {
            width: 42mm;
            height: 42mm;
        }

        /* Footer */
        .card-footer {
            border-top: 1px solid #000;
            padding: 4px 5px;
            font-size: 7pt;
        }
        .footer-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .status-badge {
            display: inline-block;
            padding: 1px 5px;
            border: 1px solid #000;
            font-size: 7pt;
        }

        /* Bulk grid */
        .bulk-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 4mm;
            justify-content: center;
            padding: 10px;
        }

        @media print {
            body { background: none; padding: 0; margin: 0; }
            .no-print-area { display: none !important; }
            .thermal-wrapper { margin: 0; box-shadow: none; }
            .bulk-grid { gap: 3mm; }
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

    <div class="bulk-grid">
        @forelse($permits as $permit)
            @php
                $token = $permit->scan_token ?: $permit->getOrCreateScanToken();
                $qrData = $token;
                $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrData);

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

            <div class="thermal-wrapper">
                {{-- Logo Alim --}}
                <div class="logo-area">
                    <div class="logo-text">ALIM</div>
                    <div class="logo-sub">Academic Learning & Information Management</div>
                </div>

                {{-- Header --}}
                <div class="card-header">
                    <div>{{ $dormitory->name ?? 'Asrama Santri' }}</div>
                    <div class="title" style="margin-top:1px;">KARTU IZIN PULANG SANTRI</div>
                </div>

                {{-- Body --}}
                <div class="info-body">
                    <div class="info-row">
                        <span class="label">Nama</span>
                        <span class="sep">:</span>
                        <span class="value full">{{ $permit->student?->name ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Kamar</span>
                        <span class="sep">:</span>
                        <span class="value">{{ $permit->room?->name ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Kelas</span>
                        <span class="sep">:</span>
                        <span class="value">{{ $kelas ?: '—' }}</span>
                    </div>

                    <hr class="dashed-line">

                    <div class="info-row">
                        <span class="label">Tgl Pulang</span>
                        <span class="sep">:</span>
                        <span class="value">{{ $permit->departure_datetime ? $permit->departure_datetime->format('d/m/Y') : '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Tgl Kembali</span>
                        <span class="sep">:</span>
                        <span class="value">{{ $permit->expected_return_datetime ? $permit->expected_return_datetime->format('d/m/Y') : '—' }}</span>
                    </div>

                    <hr class="dashed-line">

                    <div class="info-row">
                        <span class="label">Penjemput</span>
                        <span class="sep">:</span>
                        <span class="value full">{{ $permit->companion_name ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Tujuan</span>
                        <span class="sep">:</span>
                        <span class="value full">{{ $permit->destination ?? '—' }}</span>
                    </div>

                    {{-- QR Code --}}
                    <div class="qr-section">
                        <img src="{{ $qrUrl }}" alt="QR">
                    </div>

                    <hr class="dashed-line">

                    <div class="info-row">
                        <span class="label">Dicetak</span>
                        <span class="sep">:</span>
                        <span class="value">{{ \Carbon\Carbon::parse($now)->format('d/m/Y H:i') }}</span>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="card-footer">
                    <div class="footer-row">
                        <span>
                            Status:
                            <span class="status-badge">
                                @if($permit->status === 'pending') Menunggu Persetujuan
                                @elseif($permit->status === 'approved') Disetujui / Menunggu Penjemputan
                                @elseif($permit->status === 'picked_up') Sudah Dijemput (Sedang Pulang)
                                @elseif($permit->status === 'returned') Sudah Kembali ke Asrama
                                @elseif($permit->status === 'rejected') Ditolak
                                @elseif($permit->status === 'overdue') Telat Pulang
                                @else {{ ucfirst($permit->status ?? '—') }}
                                @endif
                            </span>
                        </span>
                        @if($permit->is_emergency)
                            <span class="status-badge">DARURAT</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5 col-12">
                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                <p class="mt-3 text-muted mb-1" style="font-size:1.1rem;">Tidak Ada Kartu yang Perlu Dicetak</p>
                <small class="text-muted">Pilih filter untuk menampilkan kartu yang tersedia.</small>
            </div>
        @endforence
    </div>
</body>
</html>
