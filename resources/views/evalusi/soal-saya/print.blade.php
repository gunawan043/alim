<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $judul ?? 'Naskah Soal' }}</title>
    <style>
        @page { size: A4 portrait; margin: 15mm 15mm 15mm 18mm; }
        body { font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.45; color: #111; }
        .kop { text-align: center; margin-bottom: 8mm; }
        .kop img { max-width: 100%; max-height: 22mm; object-fit: contain; }
        .school { font-size: 14pt; font-weight: bold; text-transform: uppercase; }
        .address { font-size: 9pt; color: #444; }
        h2 { text-align: center; font-size: 12pt; margin: 4mm 0 1mm; text-transform: uppercase; }
        .meta { text-align: center; font-size: 9.5pt; margin-bottom: 6mm; color: #444; }
        .question { margin: 0 0 4mm; page-break-inside: avoid; }
        .question-text { text-align: justify; }
        .options { margin: 1.5mm 0 0 8mm; }
        .option { margin: 0.8mm 0; }
        .option-label { display: inline-block; min-width: 7mm; }
        .key { margin-top: 8mm; border-top: 1px solid #999; padding-top: 4mm; page-break-before: auto; }
        .key h3 { font-size: 11pt; margin: 0 0 2mm; }
        .key-row { margin: 1mm 0; }
        .pembahasan { font-size: 9.5pt; color: #333; margin: 1mm 0 3mm 6mm; }
    </style>
</head>
<body>
@if($kop)
    <div class="kop"><img src="{{ $kop }}" alt="Kop Sekolah"></div>
@else
    <div class="kop">
        <div class="school">{{ $school->name }}</div>
        <div class="address">{{ trim(implode(' · ', array_filter([$school->address, $school->city?->display_name, $school->phone ? 'Telp. '.$school->phone : null]))) }}</div>
    </div>
@endif

<h2>{{ $judul ?? 'NASKAH SOAL' }}</h2>
<div class="meta">
    @php $first = $questions->first(); @endphp
    {{ $first?->bankSoal?->subject?->name ?? '-' }}
    @if($first?->bankSoal?->gradeLevel?->name) · {{ $first->bankSoal->gradeLevel->name }}@endif
</div>

@foreach($questions as $index => $soal)
    <div class="question">
        <div class="question-text"><strong>{{ $index + 1 }}.</strong> {!! nl2br(e(strip_tags($soal->pertanyaan))) !!}</div>
        @if($soal->options->isNotEmpty())
            <div class="options">
                @foreach($soal->options as $option)
                    <div class="option"><span class="option-label">{{ $option->label }}.</span>{{ $option->teks_opsi }}</div>
                @endforeach
            </div>
        @endif
    </div>
@endforeach

@if($withKey)
    <div class="key">
        <h3>KUNCI JAWABAN</h3>
        @foreach($questions as $index => $soal)
            <div class="key-row"><strong>{{ $index + 1 }}.</strong> {{ $soal->options->firstWhere('is_correct', true)?->label ?? '—' }}</div>
            @if($soal->pembahasan)
                <div class="pembahasan">{{ $soal->pembahasan }}</div>
            @endif
        @endforeach
    </div>
@endif
</body>
</html>
