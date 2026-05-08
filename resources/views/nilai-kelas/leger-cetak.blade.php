<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Leger Nilai STS — {{ $studyGroup->name ?? '' }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; color: #000; background: #fff; padding: 15mm; }
        @media print {
            body { padding: 10mm; }
            .no-print { display: none !important; }
            @page { size: A4 landscape; margin: 10mm; }
        }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #000; padding-bottom: 8px; }
        .header h2 { font-size: 14px; margin-bottom: 4px; }
        .header p { font-size: 10px; color: #555; }
        .info-row { display: flex; gap: 20px; margin-bottom: 10px; font-size: 10px; }
        .info-row span { color: #555; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 15px; }
        th, td { border: 1px solid #333; padding: 3px 5px; text-align: center; }
        th { background: #e0e0e0; font-weight: 600; }
        td.name { text-align: left; white-space: nowrap; font-weight: 500; }
        td.empty { color: #aaa; }
        td.aggregate { background: #f0f0f0; font-weight: 600; }
        td.sub { background: #e8f5e9; color: #1b5e20; }
        td.below { background: #ffebee; color: #c62828; }
        .footer { font-size: 9px; color: #888; margin-top: 10px; text-align: right; }
        .btn-print { background: #1976d2; color: #fff; border: none; padding: 8px 20px; font-size: 13px; border-radius: 4px; cursor: pointer; margin-bottom: 15px; }
        .btn-print:hover { background: #1565c0; }
        .predikat { font-weight: 600; }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom:15px;">
    <button class="btn-print" onclick="window.print()">
        <i class="ri-printer-line"></i> Cetak Leger
    </button>
    <button class="btn-print" onclick="window.close()" style="background:#666;margin-left:8px;">
        Tutup
    </button>
</div>

<div class="header">
    <h2>LEGER NILAI SUMATIF TENGAH SEMESTER (STS)</h2>
    <p>{{ $studyGroup->school?->name ?? '' }} — {{ $studyGroup->name }} — TA {{ $selectedAy?->name ?? '' }} Semester {{ ucfirst($selectedSem) }}</p>
</div>

<div class="info-row">
    <div>Tahun Ajaran: <strong>{{ $selectedAy?->name ?? '-' }}</strong></div>
    <div>Semester: <strong>{{ ucfirst($selectedSem) }}</strong></div>
    <div>Kelas: <strong>{{ $studyGroup->name }}</strong></div>
    <div>Wali Kelas: <strong>{{ $studyGroup->homeroomTeacher?->name ?? '-' }}</strong></div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:25px;">No</th>
            <th style="width:55px;">NIS</th>
            <th class="name" style="width:150px;">Nama Santri</th>
            @foreach($subjectMap as $subject)
                <th style="width:50px;background:#f5f5f5;" title="{{ $subject->name }}">
                    {{ $subject->code ?? Str::limit($subject->name, 8) }}
                </th>
            @endforeach
            <th class="aggregate" style="width:45px;">Jml</th>
            <th class="aggregate" style="width:45px;">Rata</th>
            <th style="width:35px;">Rank</th>
            <th style="width:70px;">Predikat</th>
            <th style="width:30px;background:#e8f5e9;">S</th>
            <th style="width:30px;background:#fff8e1;">I</th>
            <th style="width:30px;background:#ffebee;">A</th>
        </tr>
        <tr>
            <th colspan="3" class="name" style="background:#f0f0f0;font-size:9px;text-align:center;">KKM</th>
            @foreach($subjectMap as $subject)
                @php $book = $bookMap[$subject->id] ?? null; @endphp
                <th style="background:#f0f0f0;font-weight:700;">{{ $book?->kktp?->kkm_score ?? '—' }}</th>
            @endforeach
            <th class="aggregate" colspan="7"></th>
        </tr>
    </thead>
    <tbody>
        @php $rank = 1; @endphp
        @forelse($students as $idx => $history)
            @php
                $student = $history->student;
                $sid = $history->student_id;
                $avgVal = $legerAggMap[$sid] ?? null;
                $rankVal = $rankMap[$sid] ?? null;
                $pres = $presensiMap[$sid] ?? null;
                $jumlahSts = 0; $countMapel = 0;
                foreach ($subjectMap as $subject) {
                    $book = $bookMap[$subject->id] ?? null;
                    if (!$book) continue;
                    $n = $nilaiMap[$sid][$book->id] ?? null;
                    if ($n && $n->sts !== null) { $jumlahSts += $n->sts; $countMapel++; }
                }
                if ($avgVal === null) $predikat = '—';
                elseif ($avgVal >= 95) $predikat = "Mumtaz Murtafi'";
                elseif ($avgVal >= 90) $predikat = 'Mumtaz';
                elseif ($avgVal >= 85) $predikat = 'Jayyid Jiddan';
                elseif ($avgVal >= 80) $predikat = 'Jayyid';
                elseif ($avgVal >= 75) $predikat = 'Maqbul';
                else $predikat = 'Roosib';
            @endphp
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $student->nis ?? '-' }}</td>
                <td class="name">{{ $student->name }}</td>
                @foreach($subjectMap as $subject)
                    @php
                        $book = $bookMap[$subject->id] ?? null;
                        $n = $book ? ($nilaiMap[$sid][$book->id] ?? null) : null;
                        $kkm = $book?->kktp?->kkm_score ?? 75;
                        $stsVal = $n?->sts ?? null;
                    @endphp
                    @if($stsVal !== null)
                        <td class="{{ $stsVal < $kkm ? 'below' : '' }}">
                            {{ number_format($stsVal, 0) }}
                        </td>
                    @else
                        <td class="empty">—</td>
                    @endif
                @endforeach
                <td class="aggregate">{{ $jumlahSts > 0 ? number_format($jumlahSts, 1) : '—' }}</td>
                <td class="aggregate">{{ $avgVal !== null ? number_format($avgVal, 1) : '—' }}</td>
                <td class="aggregate">{{ $rankVal ?? '—' }}</td>
                <td class="predikat">{{ $predikat }}</td>
                <td class="sub">{{ $pres['s'] ?? '—' }}</td>
                <td style="background:#fff8e1;">{{ $pres['i'] ?? '—' }}</td>
                <td style="background:#ffebee;">{{ $pres['a'] ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ $subjectMap->count() + 10 }}" style="text-align:center;color:#888;">Tidak ada data.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} — {{ $studyGroup->school?->name ?? '' }}
</div>

</body>
</html>
