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
    $student = $participant->student;
    $master = $master ?? false;

    // ── Tata letak kop & identitas ──────────────────────────────────────
    // Area konten dimulai di x=18mm agar tidak menimpa penanda sudut (x 8-14mm)
    // maupun QR (x ≥ 170mm, y 12-36mm).
    $contentLeft = 18.0;
    $contentWidth = 145.0;

    // ── Slot vertikal kop (tidak boleh bertumpuk) ───────────────────────
    //   sekolah (nama+kop) → judul → meta → identitas → petunjuk
    //   seluruhnya harus selesai sebelum area jawaban (header_height_mm).
    if ($kopBase64 ?? null) {
        $kopTop = 10.0;
        $titleTop = round($kopTop + (float) ($kopHeightMm ?? 18.0) + 1.5, 2);
    } else {
        $kopTop = null;
        $schoolTop = 14.0;
        $titleTop = ($logoBase64 ?? null) ? 31.0 : 27.0;
    }

    $metaTop = round($titleTop + 5.0, 2);
    $ruleTop = round($titleTop + 9.2, 2);
    $identityTop = round($titleTop + 11.0, 2);
    $instructionTop = round($titleTop + 15.6, 2);

    $address = trim(implode(' · ', array_filter([
        $school?->address,
        $school?->city?->name,
        $school?->phone ? 'Telp. '.$school->phone : null,
    ])));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>{{ $sheet->sheet_code }}{{ $master ? '-MASTER' : '' }}</title>
<style>
    {!! \App\Services\Ljk\LjkPdfFonts::faceCss() !!}
    {!! $arabicFaceCss ?? '' !!}
    .ar { font-family: {!! $arabicStack ?? "'DejaVu Sans', sans-serif" !!}; direction: rtl; unicode-bidi: embed; }
    .ar-block { text-align: right; }
    @page { margin: 0; size: {{ $paper['width_mm'] }}mm {{ $paper['height_mm'] }}mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: {!! \App\Services\Ljk\LjkPdfFonts::stack() !!}; color: #111; }
    .page { position: relative; width: {{ $paper['width_mm'] }}mm; height: {{ $paper['height_mm'] }}mm; }
    .abs { position: absolute; }
    .fiducial { background: #000; }
    .qr { border: 0; }
    .strip-cell { background: #000; }
    .question-number { font-size: 7pt; color: #333; }
    .option-label { font-size: 5.5pt; color: #555; text-align: center; }
    .option-box { border: 0.45mm solid #000; }
    .manual-area { border: 0.3mm dashed #777; }
    .kop-img { width: {{ $contentWidth }}mm; }
    .school-name { font-size: 12pt; font-weight: bold; text-align: center; }
    .school-address { font-size: 7pt; color: #333; text-align: center; }
    .logo { width: 17mm; height: 17mm; }
    .rule { border-top: 0.5mm solid #000; height: 0; }
    .doc-title { font-size: 10.5pt; font-weight: bold; text-align: center; letter-spacing: 0.4pt; }
    .meta { font-size: 7.5pt; text-align: center; color: #222; }
    .identity { font-size: 8.5pt; }
    .identity .line { border-bottom: 0.25mm solid #333; display: inline-block; min-width: 52mm; }
    .instructions { font-size: 6.5pt; color: #333; line-height: 1.3; }
    .small { font-size: 5.5pt; color: #666; }
    .master-badge { font-size: 7pt; font-weight: bold; color: #b45309; border: 0.3mm solid #b45309; padding: 0.6mm 1.6mm; }
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

    {{-- KOP LEMBAGA --}}
    @if($kopBase64)
        <img class="abs kop-img" src="{{ $kopBase64 }}"
             style="left: {{ $contentLeft }}mm; top: {{ $kopTop }}mm; height: {{ $kopHeightMm }}mm;">
    @elseif($logoBase64)
        <img class="abs logo" src="{{ $logoBase64 }}" style="left: {{ $contentLeft }}mm; top: 13mm;">
        <div class="abs" style="left: {{ $contentLeft + 20 }}mm; top: {{ $schoolTop }}mm; width: {{ $contentWidth - 20 }}mm;">
            <div class="school-name" style="text-align: left;">{{ $school?->name ?? 'ALIM' }}</div>
            <div class="school-address" style="text-align: left;">{{ $address ?: 'Lembar Jawaban Komputer (LJK)' }}</div>
        </div>
    @else
        <div class="abs" style="left: {{ $contentLeft }}mm; top: {{ $schoolTop }}mm; width: {{ $contentWidth }}mm;">
            <div class="school-name">{{ $school?->name ?? 'ALIM' }}</div>
            <div class="school-address">{{ $address ?: 'Lembar Jawaban Komputer (LJK)' }}</div>
        </div>
    @endif

    {{-- JUDUL & IDENTITAS (slot vertikal terpisah) --}}
    <div class="abs doc-title" style="left: {{ $contentLeft }}mm; top: {{ $titleTop }}mm; width: {{ $contentWidth }}mm;">
        LEMBAR JAWABAN KOMPUTER (LJK)
    </div>

    <div class="abs meta" style="left: {{ $contentLeft }}mm; top: {{ $metaTop }}mm; width: {{ $contentWidth }}mm;">
        {!! \App\Services\Ljk\LjkArabicText::toHtml($exam->judul, false) !!} &middot; {{ $exam->subject?->name ?? '-' }} &middot; {{ $exam->studyGroup?->name ?? '-' }}
        &middot; {{ strtoupper($exam->jenis_ujian) }} &middot; Sem. {{ ucfirst($exam->semester) }}
        @if($exam->tanggal) &middot; {{ $exam->tanggal->format('d/m/Y') }} @endif
        @if($master) &nbsp; <span class="master-badge">MASTER FOTOKOPI</span> @endif
    </div>

    <div class="abs rule" style="left: {{ $contentLeft }}mm; top: {{ $ruleTop }}mm; width: {{ $contentWidth }}mm;"></div>

    <div class="abs identity" style="left: {{ $contentLeft }}mm; top: {{ $identityTop }}mm; width: {{ $contentWidth }}mm;">
        @if($master)
            Nama: <span class="line"></span> &nbsp;&nbsp; No. Peserta: <span class="line" style="min-width: 26mm;"></span>
        @else
            Nama: <strong>{!! \App\Services\Ljk\LjkArabicText::toHtml($student?->name, false) !!}</strong>
            &nbsp;&nbsp; No. Peserta: <strong>{{ $participant->nomor_peserta }}</strong>
            &nbsp;&nbsp; Kode: <strong>{{ $sheet->sheet_code }}</strong>
        @endif
    </div>

    <div class="abs instructions" style="left: {{ $contentLeft }}mm; top: {{ $instructionTop }}mm; width: {{ $contentWidth }}mm;">
        <strong>Petunjuk:</strong> Pilih <strong>satu</strong> jawaban dengan membuat tanda silang (X) di dalam kotak
        menggunakan <strong>pulpen hitam atau biru</strong>. Hindari mencoret kotak lain. Jumlah nomor: {{ count($geometry['questions']) }}.
        @if(($exam->skor_config['manual_count'] ?? 0) > 0)
            Soal uraian/isian dikerjakan di area tulis dan dinilai manual oleh guru.
        @endif
        @if(! empty($wacanaRanges))
            <br><strong>Wacana/soal cerita:</strong> soal nomor {{ implode(', ', $wacanaRanges) }}.
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

        <div class="abs question-number" style="left: {{ $question['x_mm'] }}mm; top: {{ $question['y_mm'] + 0.6 }}mm;">
            {{ str_pad((string) $question['nomor'], 2, '0', STR_PAD_LEFT) }}.
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
                     style="left: {{ $option['x_mm'] }}mm; top: {{ $option['y_mm'] - 2.3 }}mm; width: {{ $option['w_mm'] }}mm;">
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
            Master fotokopi — salin sebanyak kebutuhan, isi identitas dengan pulpen.
            @if($masterNote ?? null) <br><strong>{{ $masterNote }}</strong> @endif
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
