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
    .ar { font-family: {!! $arabicStack !!}; direction: rtl; unicode-bidi: embed; }
    .ar-block { text-align: right; }

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
    .q-no { font-weight: bold; }
    table.opts {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin: 0.1em 0 0 1.4em;
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

    {{-- ── KOP ─────────────────────────────────────────────────────────── --}}
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
                    <div class="wacana-caption">
                        Wacana untuk soal nomor {{ $block['dari'] }}{{ $block['sampai'] !== $block['dari'] ? '–'.$block['sampai'] : '' }}.
                    </div>
                @endif
            </div>
        @else
            <div class="q">
                <div>
                    <span class="q-no">{{ $block['nomor'] }}.</span>
                    <span class="q-text">{!! LjkArabicText::toHtml($block['pertanyaan']) !!}</span>
                </div>

                @if(! empty($block['opsi']))
                    <table class="opts">
                        <tr>
                            @for($i = 0; $i < $maxOptions; $i++)
                                @php $opsi = $block['opsi'][$i] ?? null; @endphp
                                <td style="width: {{ $colWidth }}%;">
                                    @if($opsi)
                                        {{-- Huruf pilihan: Arab (ا، ب، ج، د) memakai font Arab;
                                             Latin tetap memakai font naskah. --}}
                                        <span class="letter">{!! LjkArabicText::toHtml($opsi['display_label'] ?? $opsi['label'], false) !!}.</span>
                                        {!! LjkArabicText::toHtml($opsi['teks'], false) !!}
                                    @else
                                        &nbsp;
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    </table>
                @endif
            </div>
        @endif

    @endforeach

    @if(empty($blocks))
        <div class="q"><em>Paket soal belum memiliki butir.</em></div>
    @endif

</body>
</html>
