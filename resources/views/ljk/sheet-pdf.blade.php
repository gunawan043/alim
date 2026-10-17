@php
    use App\Services\Ljk\Omr\StripCodec;

    $pageData = null;
    foreach ($geometry['pages'] as $p) {
        if ((int) $p['index'] === (int) $page) { $pageData = $p; break; }
    }
    $pageData ??= $geometry['pages'][0];
    $mapping = collect($participant->mappingItems())->keyBy('no');
    $paper = $geometry['paper'];
    $school = $school ?? $exam->school;
    $schoolName = $schoolName ?? $school?->name ?? 'ALIM';
    $student = $participant->student;
    $master = $master ?? false;

    // ── Tata letak header LJK (mengikuti format lembar soal) ─────────────
    // Area konten dimulai di x=14.99mm (margin kiri soal), tidak menimpa
    // penanda sudut (x 8–14mm) maupun QR (x ≥ 170mm).
    $contentLeft = 14.99;
    $qrLeft = (float) ($pageData['qr']['x_mm'] ?? 170.0);
    $contentWidth = max(90.0, $qrLeft - 2.0 - $contentLeft);

    // Lebar alur teks (judul, karakter, instruksi) = lebar grid jawaban,
    // sehingga tepi kanan sejajar dengan kolom kotak (tabel identitas tetap
    // lebih sempit karena menghindari QR di kanan atas).
    $flowWidth = (float) config('ljk.template.paper.width_mm', 210.0)
        - $contentLeft
        - (float) config('ljk.template.margin_mm.right', 11.0);

    $fontSize = $fontSize ?? 9.6;
    $headerMm = $headerMm ?? 58.0;

    $hasKop = ! empty($kopBase64);
    $kopW = $kopWidthMm ?? 145.0;
    $kopH = (float) ($kopHeightMm ?? 0.0);
    $kopTop = 5.1;
    $kopLeft = round($contentLeft + max(0.0, ($contentWidth - $kopW) / 2), 2);
    $y = $hasKop ? round($kopTop + $kopH + 2.0, 2) : 8.0;

    $titleY = $y;
    $line2Y = round($y + 5.8, 2);
    // Baris meta ujian dihapus; tabel identitas langsung di bawah judul.
    $tableY = round($y + 11.5, 2);

    // Tabel identitas 3 baris; cadangan tinggi aman untuk sel yang membungkus.
    $tableH = max(18.0, count($identityRows ?? []) * 8.0);
    $karakterY = round($tableY + $tableH + 2.0, 2);

    // Penanaman karakter hanya bila ruang header (termasuk petunjuk) cukup.
    // Pada mode Arab, instruksi Arab menambah satu baris; blok karakter
    // dilewati agar tidak menimpa baris jawaban pertama.
    $arabLine = ($hasArabic ?? false) && ($instruksiArab ?? '') !== '';
    $karakterFits = ! empty($karakter) && ! $arabLine && ($karakterY + 30.0) <= ($headerMm - 1.0);
    $instructionY = $karakterFits
        ? round($karakterY + 18.4, 2)
        : round($tableY + $tableH + 2.4, 2);

    $line1 = $judulLjk[0] ?? 'LEMBAR JAWABAN KOMPUTER (LJK)';
    $line2 = $judulLjk[1] ?? '';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>{{ $master ? 'LJK' : $sheet->sheet_code }}</title>
<style>
    {!! \App\Services\Ljk\LjkPdfFonts::faceCss() !!}
    {!! $arabicFaceCss ?? '' !!}
    .ar { font-family: {!! $arabicStack ?? "'DejaVu Sans', sans-serif" !!}; direction: rtl; unicode-bidi: embed; font-style: normal; }
    .ar-block { display: block; text-align: right; }
    @page { margin: 0; size: {{ $paper['width_mm'] }}mm {{ $paper['height_mm'] }}mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: {!! \App\Services\Ljk\LjkPdfFonts::stack() !!}; color: #111; }
    .page { position: relative; width: {{ $paper['width_mm'] }}mm; height: {{ $paper['height_mm'] }}mm; }
    .abs { position: absolute; }
    .fiducial { background: #000; }
    .qr { border: 0; }
    .strip-cell { background: #000; }
    .question-number { font-size: 7pt; color: #333; }
    .option-label { font-size: 5.5pt; color: #555; text-align: center; line-height: 1; }
    .option-box { border: 0.45mm solid #000; }
    .manual-area { border: 0.3mm dashed #777; }
    .kop-img { display: block; }
    .doc-title { font-size: {{ $fontSize + 1.5 }}pt; font-weight: bold; text-align: center; letter-spacing: 0.4pt; }
    .title-sub { font-size: {{ $fontSize + 0.5 }}pt; font-weight: bold; text-align: center; }
    .meta { font-size: {{ max(6.5, $fontSize - 1.2) }}pt; text-align: center; color: #222; }

    /* Tabel identitas — sama dengan lembar soal (maks 3 baris, 2 pasang/baris) */
    table.identitas {
        border-collapse: collapse;
        table-layout: fixed;
        width: 100%;
        font-family: {!! \App\Services\Ljk\LjkPdfFonts::stack() !!};
    }
    table.identitas td {
        border: 0.3mm solid #000;
        padding: 0.35mm 1.2mm;
        vertical-align: middle;
        font-family: {!! \App\Services\Ljk\LjkPdfFonts::stack() !!};
        font-size: {{ max(7.5, $fontSize - 1.6) }}pt;
        line-height: 1.15;
    }
    table.identitas td.label { width: 20%; }
    table.identitas td.value { width: 30%; }
    .nama-line { display: inline-block; width: 100%; border-bottom: 0.3mm dotted #000; }

    .karakter-title {
        font-family: {!! \App\Services\Ljk\LjkPdfFonts::stack() !!};
        font-size: {{ max(6.5, $fontSize - 1) }}pt;
        font-weight: bold;
    }
    ol.karakter {
        margin: 0;
        padding: 0 0 0 5mm;
        font-family: {!! \App\Services\Ljk\LjkPdfFonts::stack() !!};
        font-size: {{ max(6.5, $fontSize - 1.2) }}pt;
    }
    ol.karakter li { margin-bottom: 0.2mm; }

    .instructions {
        font-family: {!! \App\Services\Ljk\LjkPdfFonts::stack() !!};
        font-size: {{ max(6.5, $fontSize - 1.4) }}pt;
        color: #222;
        line-height: 1.3;
    }
    .small { font-size: 5.5pt; color: #666; }
</style>
</head>
<body>
<div class="page">

    {{-- Penanda geometris --}}
    @foreach($pageData['fiducials'] as $fid)
        <div class="abs fiducial" style="left: {{ $fid['x_mm'] }}mm; top: {{ $fid['y_mm'] }}mm; width: {{ $fid['size_mm'] }}mm; height: {{ $fid['size_mm'] }}mm;"></div>
    @endforeach

    {{-- QR identitas mesin (tanpa kunci jawaban) — tidak ada pada lembar master --}}
    @if(! $master && $qrDataUri)
        <img class="abs qr" src="{{ $qrDataUri }}"
             style="left: {{ $pageData['qr']['x_mm'] }}mm; top: {{ $pageData['qr']['y_mm'] }}mm; width: {{ $pageData['qr']['size_mm'] }}mm; height: {{ $pageData['qr']['size_mm'] }}mm;">
    @endif

    {{-- Data strip mesin — tidak ada pada lembar master --}}
    @foreach($stripBits as $i => $bit)
        @if($bit === 1)
            <div class="abs strip-cell"
                 style="left: {{ $pageData['strip']['x_mm'] + $i * $pageData['strip']['cell_mm'] }}mm; top: {{ $pageData['strip']['y_mm'] }}mm; width: {{ $pageData['strip']['cell_mm'] }}mm; height: {{ $pageData['strip']['cell_mm'] }}mm;"></div>
        @endif
    @endforeach

    {{-- KOP LEMBAGA — hanya gambar kop dari pengaturan sekolah --}}
    @if($hasKop)
        <img class="abs kop-img" src="{{ $kopBase64 }}"
             style="left: {{ $kopLeft }}mm; top: {{ $kopTop }}mm; width: {{ $kopW }}mm; height: {{ $kopH }}mm;">
    @endif

    {{-- JUDUL (2 baris, seperti lembar soal) --}}
    <div class="abs doc-title" style="left: {{ $contentLeft }}mm; top: {{ $titleY }}mm; width: {{ $flowWidth }}mm;">
        {{ $line1 }}
    </div>
    @if($line2 !== '')
        <div class="abs title-sub" style="left: {{ $contentLeft }}mm; top: {{ $line2Y }}mm; width: {{ $flowWidth }}mm;">
            {{ $line2 }}
        </div>
    @endif

    {{-- TABEL IDENTITAS (3 baris × 2 pasang) --}}
    <div class="abs" style="left: {{ $contentLeft }}mm; top: {{ $tableY }}mm; width: {{ $contentWidth }}mm;">
        <table class="identitas">
            @foreach($identityRows ?? [] as $row)
                <tr>
                    @foreach($row as [$label, $value])
                        <td class="label">{{ $label }}</td>
                        <td class="value">
                            @if($master)
                                {{-- Master fotokopi generik: seluruh isian identitas kosong. --}}
                                <span class="nama-line">&nbsp;</span>
                            @elseif($label === 'Nama Peserta Didik')
                                <strong>{!! \App\Services\Ljk\LjkArabicText::toHtml($value, false) !!}</strong>
                            @else
                                {!! \App\Services\Ljk\LjkArabicText::toHtml($value, false) !!}
                            @endif
                        </td>
                    @endforeach
                    @for($i = count($row); $i < 2; $i++)
                        <td class="label">&nbsp;</td>
                        <td class="value">&nbsp;</td>
                    @endfor
                </tr>
            @endforeach
        </table>
    </div>

    {{-- PENANAMAN KARAKTER (bila ruang header cukup) --}}
    @if($karakterFits)
        <div class="abs karakter-title" style="left: {{ $contentLeft }}mm; top: {{ $karakterY }}mm; width: {{ $flowWidth }}mm;">
            PENANAMAN KARAKTER:
        </div>
        <ol class="abs karakter" style="left: {{ $contentLeft }}mm; top: {{ $karakterY + 4.2 }}mm; width: {{ $flowWidth }}mm;">
            @foreach($karakter as $butir)
                <li>{!! \App\Services\Ljk\LjkArabicText::toHtml($butir) !!}</li>
            @endforeach
        </ol>
    @endif

    {{-- PETUNJUK --}}
    <div class="abs instructions" style="left: {{ $contentLeft }}mm; top: {{ $instructionY }}mm; width: {{ $flowWidth }}mm;">
        <strong>A.</strong> {{ $instruksiLjk }}
        @if(! $master)
            Jumlah nomor: {{ count($geometry['questions']) }}.
            @if(($exam->skor_config['manual_count'] ?? 0) > 0)
                Soal uraian/isian dinilai manual oleh guru.
            @endif
            @if(! empty($wacanaRanges))
                <br><strong>Wacana/soal cerita:</strong> soal nomor
                @if($arabAngka ?? false)<span class="ar">{{ implode(', ', $wacanaRanges) }}</span>@else{{ implode(', ', $wacanaRanges) }}@endif.
            @endif
        @endif
        @if(($hasArabic ?? false) && ($instruksiArab ?? '') !== '')
            <br><span class="ar-block"><span class="ar">{!! \App\Services\Ljk\LjkArabicText::toHtml($instruksiArab, false) !!}</span></span>
        @endif
    </div>

    {{-- Butir soal --}}
    @foreach($pageData['questions'] as $question)
        @php
            $map = $mapping->get($question['nomor']);
            $labels = $map['opsi'] ?? [];
        @endphp

        @php
            $nomorTeks = str_pad((string) $question['nomor'], 2, '0', STR_PAD_LEFT);
            if ($arabAngka ?? false) {
                $nomorTeks = strtr($nomorTeks, ['0'=>'٠','1'=>'١','2'=>'٢','3'=>'٣','4'=>'٤','5'=>'٥','6'=>'٦','7'=>'٧','8'=>'٨','9'=>'٩']);
            }
        @endphp
        <div class="abs question-number" style="left: {{ $question['x_mm'] }}mm; top: {{ $question['y_mm'] + 0.6 }}mm;">
            @if($arabAngka ?? false)<span class="ar">{{ $nomorTeks }}</span>@else{{ $nomorTeks }}@endif.
        </div>

        @if($question['manual'] ?? false)
            <div class="abs manual-area"
                 style="left: {{ $question['x_mm'] + 9 }}mm; top: {{ $question['y_mm'] }}mm; width: {{ ($question['w_mm'] ?? 60) - 10 }}mm; height: {{ $question['h_mm'] ?? 12 }}mm;"></div>
        @else
            @foreach($question['options'] as $i => $option)
                @php
                    $isArabicQ = in_array((int) $question['nomor'], (array) ($arabicNumbers ?? []), true);
                    $printed = $labels[$i] ?? $option['label'];
                    if ($isArabicQ) {
                        $printed = ($hurufArab[$i] ?? $printed);
                    }
                @endphp
                <div class="abs option-label"
                     style="left: {{ $option['x_mm'] }}mm; top: {{ $option['y_mm'] - 2.8 }}mm; width: {{ $option['w_mm'] }}mm;">
                    @if($isArabicQ)<span class="ar">{{ $printed }}</span>@else{{ $printed }}@endif
                </div>
                <div class="abs option-box"
                     style="left: {{ $option['x_mm'] }}mm; top: {{ $option['y_mm'] }}mm; width: {{ $option['w_mm'] }}mm; height: {{ $option['h_mm'] }}mm;"></div>
            @endforeach
        @endif
    @endforeach

    {{-- Footer --}}
    <div class="abs small" style="left: {{ $contentLeft }}mm; top: {{ $paper['height_mm'] - 12.5 }}mm; width: {{ $contentWidth + 25 }}mm;">
        @if($master)
            LJK
        @else
            {{ $sheet->sheet_code }}
        @endif
        &middot; {{ $template->code }} v{{ $template->version }}
        &middot; Halaman {{ $page }} dari {{ count($geometry['pages']) }}
        @if(! $master)
            &middot; Diterbitkan {{ $sheet->generated_at?->format('d/m/Y H:i') ?? '-' }}
        @endif
    </div>
</div>
</body>
</html>
