@extends('layouts.print')
@section('title') SK {{ $decree->decree_number }} @endsection

@section('content')
@php
    $ps = $printSettings ?? [];
    $marginTop = (float) ($ps['margin_top'] ?? 15);
    $marginRight = (float) ($ps['margin_right'] ?? 15);
    $marginBottom = (float) ($ps['margin_bottom'] ?? 15);
    $marginLeft = (float) ($ps['margin_left'] ?? 20);
    $titleFont = (float) ($ps['title_font_size'] ?? 13);
    $infoFont = (float) ($ps['info_font_size'] ?? 10);
    $tableFont = (float) ($ps['table_font_size'] ?? 9);
    $lineHeight = (float) ($ps['line_height'] ?? 1.25);
    $orientation = ($ps['orientation'] ?? 'landscape') === 'portrait' ? 'portrait' : 'landscape';
    $paperCss = match (strtolower((string) ($ps['paper_size'] ?? 'a4'))) {
        'folio' => '215mm 330mm',
        'letter' => 'Letter',
        default => 'A4',
    };
    $city = trim((string) ($ps['city'] ?? '')) ?: 'Mataram';
@endphp
<style>
    @page { margin: {{ $marginTop }}mm {{ $marginRight }}mm {{ $marginBottom }}mm {{ $marginLeft }}mm; size: {{ $paperCss }} {{ $orientation }}; }
    body { font-family: 'Times New Roman', serif; font-size: {{ $infoFont }}pt; line-height: {{ $lineHeight }}; margin: 0; padding: 0; }
    h1 { font-size: {{ $titleFont }}pt; font-weight: bold; text-decoration: underline; margin: 0 0 6px 0; text-align: center; }
    .info-line { font-size: {{ $infoFont }}pt; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; font-size: {{ $tableFont }}pt; line-height: {{ $lineHeight }}; }
    th, td { border: 0.5px solid #000; padding: 3px 4px; vertical-align: middle; }
    th { background: #eee; font-weight: bold; text-align: center; }
    .fw-bold { font-weight: bold; }
    .sign-area { margin-top: 20px; text-align: right; width: 220px; float: right; }
</style>

@if(! empty($documentMedia['kop']))
    <div style="text-align:center; margin-bottom:4mm;">
        <img src="{{ $documentMedia['kop'] }}" alt="Kop Sekolah" style="max-width:100%; max-height:20mm; object-fit:contain;">
    </div>
@endif

<h1>LAMPIRAN I. SURAT KEPUTUSAN KEPALA {{ strtoupper($decree->school?->name ?? 'SEKOLAH') }}</h1>
<div class="info-line">
    <strong>NOMOR&nbsp;&nbsp;&nbsp;&nbsp;:</strong> {{ $decree->decree_number }}<br>
    <strong>TANGGAL :</strong> {{ $decree->issued_date?->translatedFormat('d F Y') ?? '-' }}<br>
    <strong>TENTANG :</strong> {{ strtoupper($decree->title) }}
</div>

<table>
    <thead>
        <tr>
            <th rowspan="2" style="width:22px;">No</th>
            <th rowspan="2" style="width:130px;">Nama / Mapel / Tugas Tambahan</th>
            @foreach($sortedGrades as $grade)
                <th colspan="{{ count($grade['groups']) }}" class="text-center fw-bold"
                    style="border-bottom:0.5px solid #000;">
                    {{ $grade['name'] }}
                </th>
            @endforeach
            <th rowspan="2" class="text-center" style="width:40px;">Seb.<br>Jam</th>
            <th rowspan="2" class="text-center" style="width:50px;">Tugas<br>Lain2</th>
            <th rowspan="2" class="text-center" style="width:35px;">Jml<br>Jam</th>
        </tr>
        <tr>
            @foreach($sortedGrades as $grade)
                @foreach($grade['groups'] as $sg)
                    <th class="text-center" style="width:26px;">{{ $sg['name'] }}</th>
                @endforeach
            @endforeach
        </tr>
    </thead>
    <tbody>
        @php $teacherCount = 0; @endphp
        @forelse($matrixRows as $row)
            @php $teacherCount++; @endphp
            <tr style="font-weight:bold;">
                <td class="text-center" style="width:22px;">{{ $teacherCount }}</td>
                <td style="background:#f4f4f4;">
                    {{ $row['teacher_name'] }}
                    <br><span style="font-weight:normal; font-size:9px;">{{ $row['position'] }}</span>
                </td>
                @foreach($sortedGrades as $grade)
                    @foreach($grade['groups'] as $sg)
                        <td class="text-center" style="background:#f4f4f4;"></td>
                    @endforeach
                @endforeach
                <td class="text-center fw-bold bg-success-subtle">{{ (int) $row['sebaran'] }}</td>
                <td class="text-center" style="font-size:9px; line-height:1.4;">
                    @forelse($row['tasks'] as $task)
                        <div>{{ $task['name'] }} ({{ (int) $task['weekly_hours'] }}JP)</div>
                    @empty — @endforelse
                </td>
                <td class="text-center fw-bold bg-primary-subtle">{{ (int) $row['total_hours'] }}</td>
            </tr>
            @foreach($row['subjects'] as $subjectRow)
                <tr>
                    <td class="text-center" style="width:22px;"></td>
                    <td style="padding-left:12px;">{{ $subjectRow['subject_name'] }}</td>
                    @foreach($sortedGrades as $grade)
                        @foreach($grade['groups'] as $sg)
                            <td class="text-center">
                                @if(!empty($subjectRow['hours'][$sg['id']]))
                                    {{ (int) $subjectRow['hours'][$sg['id']] }}
                                @endif
                            </td>
                        @endforeach
                    @endforeach
                    <td class="text-center fw-bold bg-success-subtle">{{ (int) $subjectRow['total'] }}</td>
                    <td></td>
                    <td></td>
                </tr>
            @endforeach
        @empty
            <tr>
                <td colspan="{{ 2 + $studyGroups->count() + 3 }}" class="text-center">Tidak ada data penugasan.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="sign-area">
    <div style="font-size:10px;">{{ $city }}, {{ $decree->issued_date?->translatedFormat('d F Y') ?? now()->translatedFormat('d F Y') }}</div>
    @if(($documentMedia['show_ttd_ksp'] ?? true) || ($documentMedia['show_stamp'] ?? true))
        @php
            $sigW = max((float) ($documentMedia['ttd_width'] ?? 30), (float) ($documentMedia['stamp_width'] ?? 30));
            $sigH = max(
                (float) ($documentMedia['ttd_margin'] ?? 2) + (float) ($documentMedia['ttd_height'] ?? 15),
                (float) ($documentMedia['stamp_margin'] ?? 2) + (float) ($documentMedia['stamp_height'] ?? 30)
            );
        @endphp
        <div style="position:relative;width:{{ $sigW }}mm;height:{{ $sigH }}mm;margin:2mm 0 2mm auto;">
            @if(($documentMedia['show_stamp'] ?? true) && ! empty($documentMedia['stamp']))
                <img src="{{ $documentMedia['stamp'] }}" alt="Stempel" style="position:absolute;left:0;top:{{ (float) ($documentMedia['stamp_margin'] ?? 2) }}mm;width:{{ (float) ($documentMedia['stamp_width'] ?? 30) }}mm;height:{{ (float) ($documentMedia['stamp_height'] ?? 30) }}mm;object-fit:contain;opacity:.9;">
            @endif
            @if(($documentMedia['show_ttd_ksp'] ?? true) && ! empty($documentMedia['ttd_ksp']))
                <img src="{{ $documentMedia['ttd_ksp'] }}" alt="TTD KSP" style="position:absolute;left:0;top:{{ (float) ($documentMedia['ttd_margin'] ?? 2) }}mm;width:{{ (float) ($documentMedia['ttd_width'] ?? 30) }}mm;height:{{ (float) ($documentMedia['ttd_height'] ?? 15) }}mm;object-fit:contain;">
            @endif
        </div>
    @else
        <div style="height:40px;"></div>
    @endif
    <div class="fw-bold">{{ $principal['display_name'] ?? $decree->signer?->name ?? '____________________' }}</div>
    <div style="font-size:9px;">{{ $decree->signed_position ?? '' }}</div>
    @if(! empty($principal['nupy']))
        <div style="font-size:9px;">NUPY. {{ $principal['nupy'] }}</div>
    @endif
</div>

@include('documents._qr-footer', ['verification' => $verification ?? null])
@endsection
