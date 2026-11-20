<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Leger Nilai {{ strtoupper($jenis ?? 'sts') }} — {{ $studyGroup->name ?? '' }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 9px; color: #000; background: #fff; padding: 10mm; }
        @media print {
            body { padding: 8mm; }
            .no-print { display: none !important; }
            @page { size: A4 landscape; margin: 8mm; }
        }
        .header { text-align: center; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 6px; }
        .header h2 { font-size: 13px; margin-bottom: 3px; }
        .header p { font-size: 9px; color: #555; }
        .info-row { display: flex; gap: 20px; margin-bottom: 8px; font-size: 9px; }
        .info-row span { color: #555; }
        table { width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 8.5px; margin-bottom: 12px; }
        th, td { border: 1px solid #333; padding: 2px 2px; text-align: center; }
        th { font-size: 6.6px; line-height: 1.15; white-space: normal; word-wrap: break-word; overflow: hidden; }
        th { background: #e0e0e0; font-weight: 600; }
        td.name { text-align: left; font-weight: 500; word-wrap: break-word; }
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
    <h2>LEGER NILAI {{ ($jenis ?? 'sts') === 'sas' ? 'AKHIR SEMESTER (SAS)' : 'SUMATIF TENGAH SEMESTER (STS)' }}</h2>
    <p>{{ $studyGroup->school?->name ?? '' }} — {{ $studyGroup->name }} — TA {{ $selectedAy?->name ?? '' }} Semester {{ ucfirst($selectedSem) }}</p>
</div>

<div class="info-row">
    <div>Tahun Ajaran: <strong>{{ $selectedAy?->name ?? '-' }}</strong></div>
    <div>Semester: <strong>{{ ucfirst($selectedSem) }}</strong></div>
    <div>Kelas: <strong>{{ $studyGroup->name }}</strong></div>
    <div>Wali Kelas: <strong>{{ $studyGroup->homeroomTeacher?->name ?? '-' }}</strong></div>
</div>

@php
    $isSas = ($jenis ?? 'sts') === 'sas';
    // Lebar kolom memakai persentase agar tabel selalu muat satu halaman landscape.
    $fixedPct = $isSas ? 37.8 : 34.2;
    $subjPct = round((100 - $fixedPct) / max(1, $subjectMap->count()), 3);
    // Bobot per mapel: override manual, jatuh ke JP bila belum diatur.
    $bobotOf = function ($subjectId) use ($bobotMap) {
        $v = $bobotMap[$subjectId] ?? null;
        if ($v === null) {
            return '—';
        }
        $v = (float) $v;

        return fmod($v, 1.0) === 0.0
            ? (string) (int) $v
            : rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    };
@endphp

<table>
    <thead>
        <tr>
            <th style="width:2.2%;">No</th>
            <th style="width:4%;">NIS</th>
            <th class="name" style="width:9.6%;">Nama Santri</th>
            @foreach($subjectMap as $subject)
                <th style="width:{{ $subjPct }}%;background:#f5f5f5;" title="{{ $subject->name }}">
                    {{ \App\Support\AcademicNilai::shortSubjectName($subject->name, 20) }}
                </th>
            @endforeach
            <th class="aggregate" style="width:2.6%;">Jml Juz</th>
            <th class="aggregate" style="width:2.6%;">Jml</th>
            @if($isSas)
                <th class="aggregate" style="width:3.2%;" title="Jumlah (Bobot × Nilai)">Jumlah</th>
            @endif
            <th class="aggregate" style="width:2.6%;">Rata</th>
            <th style="width:2.4%;">Rank</th>
            <th style="width:3.8%;">Predikat</th>
            <th style="width:1.6%;background:#e8f5e9;">S</th>
            <th style="width:1.6%;background:#fff8e1;">I</th>
            <th style="width:1.6%;background:#ffebee;">A</th>
        </tr>
        <tr>
            <th colspan="3" class="name" style="background:#f0f0f0;font-size:9px;text-align:center;">KKTP</th>
            @foreach($subjectMap as $subject)
                @php $book = $bookMap[$subject->id] ?? null; @endphp
                <th style="background:#f0f0f0;font-weight:700;">{{ \App\Support\AcademicNilai::kkmLabel($book) }}</th>
            @endforeach
            <th class="aggregate" colspan="{{ $isSas ? 9 : 8 }}"></th>
        </tr>
        @if($isSas)
            <tr>
                <th colspan="3" class="name" style="background:#eef7ee;font-size:9px;text-align:center;">Bobot</th>
                @foreach($subjectMap as $subject)
                    <th style="background:#eef7ee;font-weight:700;">{{ $bobotOf($subject->id) }}</th>
                @endforeach
                <th class="aggregate" colspan="9"></th>
            </tr>
        @endif
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
                    $val = $nilaiResolved[$sid][$book->id] ?? null;
                    if ($val !== null) { $jumlahSts += $val; $countMapel++; }
                }
                if ($avgVal === null) $predikat = '—';
                else $predikat = \App\Support\AcademicNilai::predikat((float) $avgVal);
            @endphp
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $student->nis ?? '-' }}</td>
                <td class="name">{{ $student->name }}</td>
                @foreach($subjectMap as $subject)
                    @php
                        $book = $bookMap[$subject->id] ?? null;
                        $kkm = \App\Support\AcademicNilai::kkm($book);
                        $stsVal = $nilaiResolved[$sid][$book->id] ?? null;
                    @endphp
                    @if($stsVal !== null)
                        <td class="{{ $stsVal < $kkm ? 'below' : '' }}">
                            {{ number_format($stsVal, 0) }}
                        </td>
                    @else
                        <td class="empty">—</td>
                    @endif
                @endforeach
                <td class="aggregate">{{ $jumlahJuzMap[$sid] ?? 0 }}</td>
                <td class="aggregate">{{ $jumlahSts > 0 ? number_format($jumlahSts, 1) : '—' }}</td>
                @if($isSas)
                    <td class="aggregate">{{ isset($legerJumlahMap[$sid]) ? number_format($legerJumlahMap[$sid], 1) : '—' }}</td>
                @endif
                <td class="aggregate">{{ $avgVal !== null ? number_format($avgVal, 1) : '—' }}</td>
                <td class="aggregate">{{ $rankVal ?? '—' }}</td>
                <td class="predikat">{{ $predikat }}</td>
                <td class="sub">{{ $pres['s'] ?? '—' }}</td>
                <td style="background:#fff8e1;">{{ $pres['i'] ?? '—' }}</td>
                <td style="background:#ffebee;">{{ $pres['a'] ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ $subjectMap->count() + 11 + ($isSas ? 1 : 0) }}" style="text-align:center;color:#888;">Tidak ada data.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} — {{ $studyGroup->school?->name ?? '' }}
</div>

</body>
</html>
