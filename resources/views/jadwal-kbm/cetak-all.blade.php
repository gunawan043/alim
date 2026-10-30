<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Matriks Jadwal Semua Rombel</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #1e293b; margin: 10px; font-size: 10px; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h2 { margin: 4px 0 3px; font-size: 16px; }
        .header p { margin: 0; font-size: 9px; color: #475569; }
        .kop-surat { display: block; width: 100%; max-height: 30mm; object-fit: contain; margin: 0 auto 3px; }
        table { width: 100%; border-collapse: collapse; }
        .table-responsive { overflow: visible !important; }
        .schedule-matrix-table,
        .matrix-teachers table { font-size: 8px; table-layout: fixed; }
        .schedule-matrix-table th,
        .schedule-matrix-table td,
        .matrix-teachers th,
        .matrix-teachers td { border: 1px solid #64748b; padding: 2px 3px; vertical-align: middle; }
        .schedule-matrix-table th,
        .matrix-teachers th { background: #e2e8f0; text-align: center; }
        .table-light { background: #e2e8f0; }
        .table-secondary { background: #f1f5f9; }
        .text-center { text-align: center; }
        .text-muted { color: #64748b; }
        .text-nowrap { white-space: nowrap; }
        .fw-semibold { font-weight: 600; }
        .small { font-size: 8px; }
        .subject-code { display: block; font-weight: 600; line-height: 1.1; }
        .teacher-code { display: block; color: #64748b; font-size: 7px; line-height: 1.1; }
        .matrix-day-start td { border-top: 2px solid #334155 !important; }
        .matrix-cell { text-align: center; padding: 1px !important; }
        .matrix-conflict { background: #fef3c7 !important; }
        .matrix-unavailable { background: #fee2e2 !important; }
        .matrix-layout { display: table; width: 100%; table-layout: fixed; }
        .matrix-schedule, .matrix-teachers { display: table-cell; vertical-align: top; }
        .matrix-schedule { width: 74%; padding-right: 5px; }
        .matrix-teachers { width: 26%; }
        .teacher-subject { display: flex; justify-content: space-between; gap: 3px; border-top: 1px solid #cbd5e1; margin-top: 2px; padding-top: 2px; }
        .teacher-hours { font-weight: 600; white-space: nowrap; }
        .teacher-total { text-align: right; color: #64748b; margin-top: 2px; }
        .signature-row { width: 100%; margin-top: 18px; }
        .signature-row td { border: 0; text-align: center; width: 50%; padding-top: 4px; }
        .signature-stack { position: relative; width: 46mm; height: 27mm; margin: 2px auto; }
        .signature-stack img { position: absolute; left: 0; right: 0; margin: 0 auto; }
        .stamp-image { width: 27mm; height: 27mm; top: 0; opacity: .88; }
        .ttd-image { width: 38mm; height: 19mm; top: 4mm; }
        .signature-line { width: 52mm; margin: 0 auto; border-top: 1px solid #1e293b; }
        .signature-name { font-weight: 600; margin-top: 2px; }
        .no-print { text-align: right; margin-bottom: 8px; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 5mm; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 16px;cursor:pointer;">🖨️ Cetak</button>
        <button onclick="window.close()" style="padding:8px 16px;cursor:pointer;">Tutup</button>
    </div>

    <div class="header">
        @if($kopBase64)
            <img src="{{ $kopBase64 }}" class="kop-surat" alt="Kop Sekolah">
        @else
            <p>{{ $school?->name ?? config('app.name') }}</p>
        @endif

        <h2>JADWAL KEGIATAN BELAJAR MENGAJAR SELURUH ROMBEL</h2>
        <p>Tahun Ajaran {{ $activeAy->name ?? '-' }} ({{ ucfirst($activeAy->semester ?? '-') }})</p>
    </div>

    @if($studyGroups->isEmpty())
        <p class="text-center text-muted">Belum ada rombel aktif pada sekolah ini.</p>
    @else
        @include('jadwal-kbm._matrix', ['studyGroups' => $studyGroups, 'matrixData' => $matrixData])
    @endif

    <table class="signature-row">
        <tr>
            <td></td>
            <td>
                Mengetahui,<br>
                Kepala Satuan Pendidikan
                <div class="signature-stack">
                    @if($stampBase64)
                        <img src="{{ $stampBase64 }}" class="stamp-image" alt="Stempel Sekolah">
                    @endif
                    @if($ttdKspBase64)
                        <img src="{{ $ttdKspBase64 }}" class="ttd-image" alt="TTD Kepala Sekolah">
                    @endif
                </div>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $school?->principal_full_name ?? '__________________________' }}</div>
                <div>NUPY. {{ $school?->principal_nupy_display ?? '........................' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
