<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Jadwal — {{ $studyGroup->full_name ?? $studyGroup->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #1e293b; margin: 24px; }
        .header { text-align: center; margin-bottom: 18px; }
        .header h2 { margin: 0 0 4px; font-size: 18px; }
        .header p { margin: 0; font-size: 12px; color: #475569; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 18px; }
        th, td { border: 1px solid #94a3b8; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; text-align: center; }
        td.center { text-align: center; }
        .day-title { background: #e2e8f0; font-weight: bold; }
        .meta { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 12px; }
        .footer { margin-top: 36px; display: flex; justify-content: space-between; font-size: 12px; }
        .signature { width: 220px; text-align: center; }
        .signature .space { height: 64px; }
        .no-print { text-align: right; margin-bottom: 12px; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 8mm; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 16px;cursor:pointer;">🖨️ Cetak</button>
        <button onclick="window.close()" style="padding:8px 16px;cursor:pointer;">Tutup</button>
    </div>

    <div class="header">
        <h2>JADWAL KEGIATAN BELAJAR MENGAJAR</h2>
        <p>{{ $studyGroup->school->name ?? config('app.name') }}</p>
        <p>{{ $studyGroup->full_name ?? $studyGroup->name }} — Tahun Ajaran {{ $activeAy->name ?? '-' }} ({{ ucfirst($activeAy->semester ?? '-') }})</p>
    </div>

    <div class="meta">
        <span>Wali Kelas: <strong>{{ $studyGroup->homeroomTeacher?->name ?? '-' }}</strong></span>
        <span>Total slot: <strong>{{ $jadwals->flatten()->count() }}</strong></span>
    </div>

    @foreach($days as $dayNumber => $dayName)
        @php $dayJadwals = $jadwals[$dayNumber] ?? collect(); @endphp
        @if($dayJadwals->isNotEmpty())
            <table>
                <thead>
                    <tr><th colspan="5" class="day-title">{{ strtoupper($dayName) }}</th></tr>
                    <tr>
                        <th style="width:56px">Jam</th>
                        <th style="width:110px">Waktu</th>
                        <th>Mata Pelajaran</th>
                        <th>Guru</th>
                        <th style="width:90px">Ruang</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dayJadwals as $jadwal)
                        <tr>
                            <td class="center">{{ $jadwal->slot_index }}</td>
                            <td class="center">{{ substr($jadwal->start_time, 0, 5) }}–{{ substr($jadwal->end_time, 0, 5) }}</td>
                            <td>{{ $jadwal->subject?->name ?? '-' }}</td>
                            <td>{{ $jadwal->teacher?->name ?? '-' }}</td>
                            <td class="center">{{ $jadwal->room ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    @if($jadwals->isEmpty())
        <p style="text-align:center;color:#64748b;">Belum ada jadwal untuk rombel ini.</p>
    @endif

    <div class="footer">
        <div class="signature">
            <div>Mengetahui,</div>
            <div>Kepala Satuan Pendidikan</div>
            <div class="space"></div>
            <div>__________________________</div>
        </div>
        <div class="signature">
            <div>Wali Kelas</div>
            <div>&nbsp;</div>
            <div class="space"></div>
            <div>{{ $studyGroup->homeroomTeacher?->name ?? '__________________________' }}</div>
        </div>
    </div>
</body>
</html>
