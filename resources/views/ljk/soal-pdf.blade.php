@php
    /** @var array<string, mixed> $judul */
    $line1 = $judul[0] ?? '';
    $line2 = $judul[1] ?? '';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Soal {{ $line1 ?: 'Ujian' }}</title>
<style>
    {!! $faceCss !!}

    @page { margin: 18mm 16mm 16mm 20mm; size: A4 portrait; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: {!! $fontStack !!};
        font-size: {{ $fontSize }}pt;
        color: #000;
        line-height: 1.35;
    }
    .title { text-align: center; font-weight: bold; font-size: {{ $fontSize + 1.5 }}pt; }
    .title-sub { text-align: center; font-weight: bold; font-size: {{ $fontSize + 0.5 }}pt; margin-top: 1mm; }

    table.info { width: 100%; border-collapse: collapse; margin-top: 4mm; }
    table.info td { vertical-align: top; padding: 0.6mm 0; font-size: {{ $fontSize }}pt; }
    table.info td.label { width: 42mm; padding-right: 3mm; }
    table.info td.value { }
    .nama-line { display: inline-block; min-width: 95mm; border-bottom: 0.3mm dotted #000; }

    .rule { border-top: 0.4mm solid #000; margin: 3mm 0 2mm; }

    .karakter-title { font-weight: bold; margin-top: 2mm; }
    ol.karakter { margin: 1mm 0 0 6mm; padding: 0; }
    ol.karakter li { margin-bottom: 0.6mm; }

    .instruksi { margin-top: 3mm; }
    .instruksi strong { font-weight: bold; }

    .q { margin-top: 3.2mm; page-break-inside: avoid; }
    .q-head { }
    .q-no { font-weight: bold; }
    .q-text { }
    .opts { margin: 1mm 0 0 6mm; }
    .opt { display: inline-block; margin: 0 6mm 0.8mm 0; vertical-align: top; }
    .opt .letter { font-weight: bold; }

    .footer-note { margin-top: 6mm; font-size: {{ $fontSize - 2 }}pt; color: #333; text-align: center; }
</style>
</head>
<body>

    {{-- ── KOP ─────────────────────────────────────────────────────────── --}}
    <div class="title">{{ $line1 }}</div>
    @if($line2 !== '')
        <div class="title-sub">{{ $line2 }}</div>
    @endif

    <table class="info">
        <tr>
            <td class="label">Mata Pelajaran</td>
            <td class="value">: {{ $mataPelajaran }}</td>
        </tr>
        <tr>
            <td class="label">Hari/Tanggal</td>
            <td class="value">: {{ $hariTanggal ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kelas</td>
            <td class="value">: {{ $kelas ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Waktu</td>
            <td class="value">: {{ $waktu ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Guru Pengampu</td>
            <td class="value">: {{ $guruPengampu ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Nama Peserta Didik</td>
            <td class="value">: <span class="nama-line">&nbsp;</span></td>
        </tr>
    </table>

    <div class="rule"></div>

    {{-- ── PENANAMAN KARAKTER ──────────────────────────────────────────── --}}
    @if(! empty($karakter))
        <div class="karakter-title">PENANAMAN KARAKTER :</div>
        <ol class="karakter">
            @foreach($karakter as $butir)
                <li>{{ $butir }}</li>
            @endforeach
        </ol>
    @endif

    {{-- ── INSTRUKSI ───────────────────────────────────────────────────── --}}
    @if($instruksi !== '')
        <div class="instruksi"><strong>A.</strong> {{ $instruksi }}</div>
    @endif

    {{-- ── SOAL (tanpa bobot/poin, tanpa kunci) ────────────────────────── --}}
    @foreach($soal as $butir)
        <div class="q">
            <div class="q-head">
                <span class="q-no">{{ $butir['nomor'] }}.</span>
                <span class="q-text">{!! nl2br(e($butir['pertanyaan'])) !!}</span>
            </div>

            @if(! empty($butir['opsi']))
                <div class="opts">
                    @foreach($butir['opsi'] as $opsi)
                        <span class="opt"><span class="letter">{{ $opsi['label'] }}.</span> {{ $opsi['teks'] }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach

    @if(empty($soal))
        <div class="q"><em>Paket soal belum memiliki butir.</em></div>
    @endif

</body>
</html>
