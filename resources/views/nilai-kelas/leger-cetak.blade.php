<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Leger Nilai {{ strtoupper($jenis ?? 'sts') }} — {{ $studyGroup->name ?? '' }}</title>
    <style>
        {!! \App\Services\Ljk\LjkPdfFonts::arabicFaceCss() !!}

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
        th, td { border: 1px solid #333; padding: 1px 2px; text-align: center; }
        th { font-size: 6.4px; line-height: 1.05; white-space: normal; word-wrap: break-word; overflow: hidden; background: #e0e0e0; font-weight: 600; }
        /* Baris data tinggi seragam; header dibiarkan menyesuaikan isinya. */
        .leger-table tbody tr { height: 14px; }
        .leger-table tbody td { height: 14px; padding: 0 2px; white-space: nowrap; overflow: hidden; vertical-align: middle; }
        td.name { text-align: left; font-weight: 500; text-overflow: ellipsis; }
        td.empty { color: #aaa; }
        td.aggregate { background: #f0f0f0; font-weight: 600; }
        td.sub { background: #e8f5e9; color: #1b5e20; }
        td.below { background: #ffebee; color: #c62828; }
        td.comp { background: #fafafa; color: #333; }
        /* Baris KKTP/Bobot dipadatkan. */
        .row-kktp th, .row-bobot th, .row-komponen th { font-size: 6px; line-height: 1; padding: 0 2px; height: 11px; }
        .row-komponen th { background: #f3f3f3; font-weight: 500; color: #444; }
        .footer { font-size: 9px; color: #888; margin-top: 10px; text-align: right; }
        .btn-print { background: #1976d2; color: #fff; border: none; padding: 8px 20px; font-size: 13px; border-radius: 4px; cursor: pointer; margin-bottom: 15px; }
        .btn-print:hover { background: #1565c0; }
        .predikat { font-weight: 600; }

        /* Catatan per predikat: tabel terpisah agar kolom nilai tetap ringkas. */
        .notes-section { margin-top: 14px; page-break-inside: auto; }
        .notes-section h3 { font-size: 10px; margin-bottom: 4px; }
        table.notes-table { table-layout: auto; font-size: 8px; }
        table.notes-table th, table.notes-table td {
            height: auto;
            padding: 2px 4px;
            white-space: normal;
            overflow: visible;
            vertical-align: top;
        }
        table.notes-table td.note { text-align: left; }
        table.notes-table td.note-ar {
            text-align: right;
            direction: rtl;
            font-family: {!! \App\Services\Ljk\LjkPdfFonts::arabicStack() !!};
        }
        table.notes-table td.name { text-align: left; }
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
    $componentColumns = $componentColumns ?? [];
    $componentValues = $componentValues ?? [];
    $tahfidzColumns = $tahfidzColumns ?? [];
    $tahfidzValues = $tahfidzValues ?? [];
    $tahfidzWeights = $tahfidzWeights ?? [];
    $predicateNotes = $predicateNotes ?? [];
    $predicateNotesAr = $predicateNotesAr ?? [];

    // Hitung slot kolom: 1 kolom nilai akhir + kolom komponen (bila ada).
    // Mapel tahfidz memakai sub-kolom Ziyadah & Rabt, bukan komponen mapel umum.
    $subjectSlots = [];
    $slotCount = 0;
    $hasAnyComponent = false;

    foreach ($subjectMap as $subject) {
        $cols = $componentColumns[$subject->id][$jenis] ?? [];
        $subjectSlots[$subject->id] = $cols;
        $slotCount += 1 + count($cols);
        if ($cols !== []) {
            $hasAnyComponent = true;
        }

        if (! empty($tahfidzColumns[$subject->id])) {
            $slotCount += count($tahfidzColumns[$subject->id]);
        }
    }

    $hasAnyTahfidz = ! empty($tahfidzColumns);

    // Lebar kolom memakai persentase agar tabel selalu muat satu halaman landscape.
    $fixedPct = $isSas ? 37.8 : 34.2;
    $slotPct = round((100 - $fixedPct) / max(1, $slotCount), 4);
    $aggColspan = $isSas ? 9 : 8;

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

<table class="leger-table">
    <thead>
        <tr>
            <th style="width:2.2%;">No</th>
            <th style="width:4%;">NIS</th>
            <th class="name" style="width:9.6%;">Nama Santri</th>
            @foreach($subjectMap as $subject)
                @php
                    $cols = $subjectSlots[$subject->id];
                    $thfCols = $tahfidzColumns[$subject->id] ?? [];
                @endphp
                <th colspan="{{ 1 + count($cols) + count($thfCols) }}" style="width:{{ round($slotPct * (1 + count($cols) + count($thfCols)), 3) }}%;background:#f5f5f5;" title="{{ $subject->name }}">
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
        @if($hasAnyComponent || $hasAnyTahfidz)
            <tr class="row-komponen">
                <th colspan="3"></th>
                @foreach($subjectMap as $subject)
                    @php
                        $cols = $subjectSlots[$subject->id];
                        $thfCols = $tahfidzColumns[$subject->id] ?? [];
                    @endphp
                    @if($cols === [] && $thfCols === [])
                        <th></th>
                    @else
                        @foreach($cols as $col)
                            <th title="Komponen {{ strtoupper($jenis) }} — {{ $col['label'] }}">{{ $col['label'] }}</th>
                        @endforeach
                        @foreach($thfCols as $label)
                            <th title="Rata-rata {{ $label }} dari buku halaqah">{{ $label }}</th>
                        @endforeach
                        <th title="Nilai akhir {{ strtoupper($jenis) }}{{ $thfCols !== [] ? ' (NR Final tahfidz)' : ' (rata-rata komponen)' }}">Nilai</th>
                    @endif
                @endforeach
                <th colspan="{{ $aggColspan }}"></th>
            </tr>
        @endif
        <tr class="row-kktp">
            <th colspan="3" class="name" style="background:#f0f0f0;text-align:center;">KKTP</th>
            @foreach($subjectMap as $subject)
                @php $book = $bookMap[$subject->id] ?? null; @endphp
                <th colspan="{{ 1 + count($subjectSlots[$subject->id]) + count($tahfidzColumns[$subject->id] ?? []) }}" style="background:#f0f0f0;font-weight:700;">{{ \App\Support\AcademicNilai::kkmLabel($book) }}</th>
            @endforeach
            <th class="aggregate" colspan="{{ $aggColspan }}"></th>
        </tr>
        @if($isSas)
            <tr class="row-bobot">
                <th colspan="3" class="name" style="background:#eef7ee;text-align:center;">Bobot</th>
                @foreach($subjectMap as $subject)
                    @php $thfWeights = $tahfidzWeights[$subject->id] ?? null; @endphp
                    <th colspan="{{ 1 + count($subjectSlots[$subject->id]) + count($tahfidzColumns[$subject->id] ?? []) }}" style="background:#eef7ee;font-weight:700;">
                        @if($thfWeights)
                            Z{{ $thfWeights['ziyadah'] }}·R{{ $thfWeights['rabt'] }}·S{{ $thfWeights['sts'] }}·A{{ $thfWeights['sas'] }}
                        @else
                            {{ $bobotOf($subject->id) }}
                        @endif
                    </th>
                @endforeach
                <th class="aggregate" colspan="{{ $aggColspan }}"></th>
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
                        $cols = $subjectSlots[$subject->id];
                        $compVals = $book ? ($componentValues[$sid][$book->id][$jenis] ?? []) : [];
                    @endphp
                    @foreach($cols as $col)
                        @php $compVal = $compVals[$col['id']] ?? null; @endphp
                        <td class="comp">{{ $compVal !== null ? number_format((float) $compVal, 0) : '—' }}</td>
                    @endforeach
                    @foreach(array_keys($tahfidzColumns[$subject->id] ?? []) as $thfKey)
                        @php $thfVal = $tahfidzValues[$sid][$subject->id][$thfKey] ?? null; @endphp
                        <td class="comp">{{ $thfVal !== null ? number_format((float) $thfVal, 0) : '—' }}</td>
                    @endforeach
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
            <tr><td colspan="{{ $slotCount + 11 + ($isSas ? 1 : 0) }}" style="text-align:center;color:#888;">Tidak ada data.</td></tr>
        @endforelse
    </tbody>
</table>

@if($predicateNotes !== [] || $predicateNotesAr !== [])
    <div class="notes-section">
        <h3>Catatan Rapor per Predikat</h3>
        <table class="notes-table">
            <thead>
                <tr>
                    <th style="width:4%;">No</th>
                    <th style="width:8%;">NIS</th>
                    <th style="width:20%;">Nama Santri</th>
                    <th style="width:10%;">Predikat</th>
                    <th>Catatan</th>
                    <th>Catatan (Arab)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $idx => $history)
                    @php
                        $student = $history->student;
                        $sid = $history->student_id;
                        $avgVal = $legerAggMap[$sid] ?? null;
                        $pred = $avgVal === null ? null : \App\Support\AcademicNilai::predikat((float) $avgVal);
                        $note = $pred ? ($predicateNotes[$pred] ?? null) : null;
                        $noteAr = $pred ? ($predicateNotesAr[$pred] ?? null) : null;
                        $note = \App\Support\NoteTemplate::render($note, $student->name);
                        $noteAr = \App\Support\NoteTemplate::render($noteAr, $student->name_ar ?: $student->name);
                    @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $student->nis ?? '-' }}</td>
                        <td class="name">{{ $student->name }}</td>
                        <td>{{ $pred ?? '—' }}</td>
                        <td class="note">{{ $note ?? '—' }}</td>
                        <td class="note-ar">{!! $noteAr !== null ? \App\Support\ArabicText::toHtml($noteAr, false) : '—' !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="footer">
    Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} — {{ $studyGroup->school?->name ?? '' }}
</div>

</body>
</html>
