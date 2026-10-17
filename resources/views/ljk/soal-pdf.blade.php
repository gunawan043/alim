@php
    use App\Services\Ljk\LjkArabicText;

    /** @var array<int, string> $judul */
    $line1 = $judul[0] ?? '';
    $line2 = $judul[1] ?? '';
    $maxOptions = max(2, min(5, (int) ($maxOptions ?? 4)));
    $colWidth = round(100 / $maxOptions, 4);

    // Spasi: antar baris "single" (1.0); antar soal 1.15 (≈ +15% satu baris).
    $lineHeight = (float) ($lineHeight ?? 1.0);
    $questionGapPt = max(0.0, ((float) ($questionGap ?? 1.15)) - 1.0) * (float) $fontSize;

    $paper = $paper ?? ['width_mm' => 210.0, 'height_mm' => 297.0, 'label' => 'A4'];
    $marginMm = $marginMm ?? ['top' => 5.08, 'bottom' => 2.54, 'left' => 14.99, 'right' => 16.0];
    $letterhead = $letterhead ?? [
        'schoolName' => 'ALIM', 'address' => '',
        'kopBase64' => null, 'kopHeightMm' => 0.0, 'logoBase64' => null,
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Soal {{ $line1 ?: 'Ujian' }}</title>
<style>
    {!! $faceCss !!}
    {!! $arabicFaceCss !!}

    @page {
        margin: {{ $marginMm['top'] }}mm {{ $marginMm['right'] }}mm {{ $marginMm['bottom'] }}mm {{ $marginMm['left'] }}mm;
        size: {{ $paper['width_mm'] }}mm {{ $paper['height_mm'] }}mm;
    }
    /* Catatan penting (Dompdf): jangan menetapkan margin nol pada elemen
       html/universal — aturan itu menimpa margin @page sehingga isi cetak
       mepet ke tepi kertas. Reset margin hanya pada elemen isi di bawah. */
    * { padding: 0; box-sizing: border-box; }
    body, div, p, ol, ul, li, table, thead, tbody, tr, th, td, span, em, strong, br { margin: 0; }
    body { margin: 0; }
    body {
        font-family: {!! $fontStack !!};
        font-size: {{ $fontSize }}pt;
        line-height: {{ $lineHeight }};
        color: #000;
    }

    /* Teks Arab: bentuk presentasi + urutan visual sudah disiapkan di PHP. */
    .ar { font-family: {!! $arabicStack !!}; direction: rtl; unicode-bidi: embed; font-style: normal; }
    .ar-block { text-align: right; }

    /* Kop lembaga — HANYA gambar kop yang diunggah di pengaturan sekolah */
    .kop { text-align: center; }
    .kop-image { width: 100%; display: block; margin: 0 auto; }
    .kop-rule { border-top: 0.5mm solid #000; margin: 1.2mm 0 1.6mm; }

    .title { text-align: center; font-weight: bold; font-size: {{ $fontSize }}pt; }
    .title-sub { text-align: center; font-weight: bold; font-size: {{ $fontSize }}pt; margin-top: 0.2em; }

    /* Tabel identitas (maks 3 baris, 2 pasang label/nilai per baris) */
    table.identitas {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0.6em;
        table-layout: fixed;
    }
    table.identitas td {
        border: 0.3mm solid #000;
        padding: 0.35mm 1.2mm;
        vertical-align: middle;
        font-size: {{ $fontSize }}pt;
    }
    table.identitas td.label { width: 21%; }
    table.identitas td.value { width: 29%; }
    .nama-line { display: inline-block; width: 100%; border-bottom: 0.3mm dotted #000; }

    .karakter-title { font-weight: bold; margin-top: 0.5em; }
    ol.karakter { margin: 0.1em 0 0 1.6em; padding: 0; }
    ol.karakter li { margin-bottom: 0; }

    .instruksi { margin-top: 0.6em; }
    .instruksi-arab { margin-top: 0.2em; }

    /* Wacana / soal cerita */
    .wacana { margin-top: {{ $questionGapPt }}pt; page-break-inside: avoid; }
    .wacana-title { font-weight: bold; }
    .wacana-text {
        margin-top: 0.2em;
        padding: 1.2mm 2mm;
        border-left: 0.6mm solid #555;
        background: #f5f5f5;
        text-align: justify;
    }
    .wacana-caption { margin-top: 0.1em; font-style: italic; }

    /* Butir soal — opsi dalam kolom sejajar (A..D selalu pada posisi sama) */
    .q { margin-top: {{ $questionGapPt }}pt; page-break-inside: avoid; }

    /* Nomor soal + isi (teks DAN opsi) berada dalam kolom yang sama,
       sehingga teks soal sejajar persis dengan huruf pilihan A, dan baris
       lanjutan teks menjorok sejajar teks (hanging indent). */
    table.qwrap { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.qwrap td { vertical-align: top; padding: 0; }
    table.qwrap td.qno { width: 4.5%; font-weight: normal; }
    table.qwrap td.qbody { width: 95.5%; }
    table.opts {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin: 0.1em 0 0 0;
    }
    table.opts td {
        vertical-align: top;
        padding: 0 1.5mm 0 0;
        font-size: {{ $fontSize }}pt;
        line-height: {{ $lineHeight }};
    }
    table.opts td .letter { font-weight: normal; }

    .paper-note { font-size: {{ max(6.0, $fontSize - 4) }}pt; color: #666; }
</style>
</head>
<body>

    {{-- ── KOP LEMBAGA ───────────────────────────────────────────────────
         Hanya memakai gambar kop yang diunggah di Pengaturan Sekolah.
         Bila belum ada, area kop dikosongkan (tidak memakai teks). --}}
    @if(! empty($letterhead['kopBase64']))
        <div class="kop">
            <img class="kop-image" src="{{ $letterhead['kopBase64'] }}"
                 style="width: {{ $letterhead['kopWidthMm'] ?? 145 }}mm; height: {{ $letterhead['kopHeightMm'] ?? 18 }}mm;">
            <div class="kop-rule"></div>
        </div>
    @endif

    {{-- ── JUDUL UJIAN ─────────────────────────────────────────────────── --}}
    <div class="title">{{ $line1 }}</div>
    @if($line2 !== '')
        <div class="title-sub">{{ $line2 }}</div>
    @endif

    {{-- Identitas: tabel maksimal 3 baris ─────────────────────────────── --}}
    <table class="identitas">
        @foreach($identityRows as $row)
            <tr>
                @foreach($row as [$label, $value])
                    <td class="label">{{ $label }}</td>
                    <td class="value">
                        @if($label === 'Nama Peserta Didik')
                            <span class="nama-line">&nbsp;</span>
                        @else
                            {!! LjkArabicText::toHtml($value, false) !!}
                        @endif
                    </td>
                @endforeach

                {{-- Sel kosong bila baris hanya berisi satu pasang --}}
                @for($i = count($row); $i < 2; $i++)
                    <td class="label">&nbsp;</td>
                    <td class="value">&nbsp;</td>
                @endfor
            </tr>
        @endforeach
    </table>

    {{-- ── PENANAMAN KARAKTER ──────────────────────────────────────────── --}}
    @if(! empty($karakter))
        <div class="karakter-title">PENANAMAN KARAKTER :</div>
        <ol class="karakter">
            @foreach($karakter as $butir)
                <li>{!! LjkArabicText::toHtml($butir) !!}</li>
            @endforeach
        </ol>
    @endif

    {{-- ── INSTRUKSI ───────────────────────────────────────────────────── --}}
    @if($instruksi !== '')
        <div class="instruksi"><strong>A.</strong> {{ $instruksi }}</div>
        @if(($hasArabic ?? false) && ($instruksiArab ?? '') !== '')
            <div class="instruksi-arab ar-block"><span class="ar">{!! LjkArabicText::toHtml($instruksiArab, false) !!}</span></div>
        @endif
    @endif

    {{-- ── WACANA & SOAL ───────────────────────────────────────────────── --}}
    @foreach(($blocks ?? []) as $block)

        @if($block['type'] === 'wacana')
            <div class="wacana">
                @if(! empty($block['judul']))
                    <div class="wacana-title">{!! LjkArabicText::toHtml($block['judul']) !!}</div>
                @endif
                <div class="wacana-text {{ ($block['rtl'] ?? false) ? 'ar-block' : '' }}">
                    {!! LjkArabicText::toHtml($block['teks']) !!}
                </div>
                @if(! empty($block['sumber']))
                    <div class="wacana-caption paper-note">Sumber: {!! LjkArabicText::toHtml($block['sumber']) !!}</div>
                @endif
                @if($block['dari'] !== null)
                    @php
                        $dari = $block['dari_tampil'] ?? $block['dari'];
                        $sampai = $block['sampai_tampil'] ?? $block['sampai'];
                    @endphp
                    <div class="wacana-caption">Wacana untuk soal nomor @if($arabAngka ?? false)<span class="ar">{{ $dari }}{{ $sampai !== $dari ? '–'.$sampai : '' }}</span>@else{{ $dari }}{{ $sampai !== $dari ? '–'.$sampai : '' }}@endif.</div>
                @endif
            </div>
        @else
            @php $rtl = (bool) ($block['rtl'] ?? false); @endphp
            <div class="q">
                {{-- Satu tabel: nomor + kolom isi (teks soal DAN opsi ada di kolom
                     yang sama) → teks soal sejajar dengan huruf pilihan A. --}}
                <table class="qwrap">
                    <tr>
                        @if($rtl)
                            <td class="qbody" style="text-align: right;">
                                <div>{!! LjkArabicText::toHtml($block['pertanyaan']) !!}</div>
                                @include('ljk.partials.soal-opsi', ['block' => $block, 'rtl' => true, 'maxOptions' => $maxOptions, 'colWidth' => $colWidth])
                            </td>
                            <td class="qno" style="text-align: right;">
                                @if($arabAngka ?? false)<span class="ar">{{ $block['nomor_tampil'] ?? ($block['nomor'].'.') }}</span>@else{{ $block['nomor_tampil'] ?? ($block['nomor'].'.') }}@endif
                            </td>
                        @else
                            <td class="qno">
                                @if($arabAngka ?? false)<span class="ar">{{ $block['nomor_tampil'] ?? ($block['nomor'].'.') }}</span>@else{{ $block['nomor_tampil'] ?? ($block['nomor'].'.') }}@endif
                            </td>
                            <td class="qbody">
                                <div>{!! LjkArabicText::toHtml($block['pertanyaan']) !!}</div>
                                @include('ljk.partials.soal-opsi', ['block' => $block, 'rtl' => false, 'maxOptions' => $maxOptions, 'colWidth' => $colWidth])
                            </td>
                        @endif
                    </tr>
                </table>
            </div>
        @endif

    @endforeach

    @if(empty($blocks))
        <div class="q"><em>Paket soal belum memiliki butir.</em></div>
    @endif

</body>
</html>
