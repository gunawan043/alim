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

    // ── Gaya tambahan (opsional; LJK & paket tidak mengirimnya) ──────────
    $judulStyle = $judulStyle ?? ['size' => $fontSizeBase ?? $fontSize, 'bold' => true, 'lineHeight' => 1.2, 'align' => 'center'];
    $tahunStyle = $tahunStyle ?? $judulStyle;
    $identitas = $identitas ?? ['show' => true, 'border' => true, 'padding' => 1.2, 'size' => $fontSizeBase ?? $fontSize, 'labelPeserta' => 'Nama Peserta Didik'];
    $karakterStyle = $karakterStyle ?? ['title' => 'PENANAMAN KARAKTER :', 'size' => $fontSizeBase ?? $fontSize, 'bold' => false, 'lineHeight' => 1.0, 'marker' => 'numbered'];
    $instruksiStyle = $instruksiStyle ?? ['show' => true, 'size' => $fontSizeBase ?? $fontSize, 'bold' => false, 'italic' => false, 'spacing' => 1.0];
    $showKop = (bool) ($showKop ?? true);
    $kopWidthPct = (float) ($kopWidthPct ?? 100);
    $kopRule = (bool) ($kopRule ?? true);
    $optionIndent = (float) ($optionIndent ?? 2);

    $marker = ['numbered' => 'decimal', 'bullet' => 'disc', 'dash' => 'none'][$karakterStyle['marker']] ?? 'decimal';
    // Template khusus naskah Arab: selalu RTL.
    $rtlExam = true;
    $examBahasa = 'arab';
@endphp
<!DOCTYPE html>
{{--
    JANGAN menambahkan dir="rtl" (atau direction: rtl pada body/html).

    Seluruh teks Arab disusun ke URUTAN VISUAL (kiri→kanan) oleh
    LjkArabicText sebelum masuk ke HTML: kata-kata dibalik, bentuk presentasi
    dipilih, dan tanda baca diletakkan di sisi yang benar. Karena itu HTML ini
    adalah dokumen LTR yang KEBETULAN berisi glyph Arab dalam urutan cetak.

    Menambahkan dir="rtl" membuat bidi Dompdf membaliknya SEKALI LAGI:
    tanda tanya ؟ kembali ke kanan, «» bertukar, penanda opsi ".ا" berubah
    jadi "ا.", dan harakat terlepas dari hurufnya. Perataan kanan dilakukan
    eksplisit lewat text-align: right pada tiap blok.
--}}
<html lang="ar">
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

    /* Teks Arab: bentuk presentasi + urutan visual sudah disiapkan di PHP.
       JANGAN memakai direction: rtl di sini. PHP sudah membalik urutan kata
       menjadi urutan visual (kiri→kanan), jadi bidi Dompdf akan membaliknya
       SEKALI LAGI: tanda tanya ؟ yang sudah diletakkan di ujung kiri malah
       kembali ke kanan, dan kurung «» ikut terbalik. Rata kanan cukup. */
    .ar { font-family: {!! $arabicStack !!}; font-style: normal; }
    /* Angka Arab tetap dibaca kiri-ke-kanan; tanpa ini titik nomor (٢٥.)
       berpindah ke kiri menjadi (.٢٥). */
    .ar-number { direction: ltr; unicode-bidi: isolate; }
    .ar-block { text-align: right; }
    /* Tanda kurung dan isi Latin di tengah kalimat Arab ("(X)"): digambar
       apa adanya kiri→kanan, tanpa dibalik susunan RTL. */
    .ar-block .ltr, .ar .ltr { font-family: {!! $fontStack !!}; direction: ltr; unicode-bidi: isolate; }
    .ltr-group { unicode-bidi: isolate; }
    /* Nomor soal: angka Arab + titik dari font Latin agar titik tidak
       terbaca sebagai angka nol (١٠). */
    .qno-number { direction: ltr; unicode-bidi: isolate; white-space: nowrap; }
    .qno-number .num-dot, .karakter-no .num-dot { font-family: {!! $fontStack !!}; }
    .karakter-no { display: inline-block; direction: ltr; unicode-bidi: isolate; margin-left: 1mm; white-space: nowrap; }

    /* Kop lembaga — HANYA gambar kop yang diunggah di pengaturan sekolah */
    .kop { text-align: center; }
    .kop-image { display: block; margin: 0 auto; }
    .kop-rule { border-top: 0.5mm solid #000; margin: 1.2mm 0 1.6mm; }

    .title {
        text-align: {{ $judulStyle['align'] }};
        font-weight: {{ $judulStyle['bold'] ? 'bold' : 'normal' }};
        font-size: {{ $fontSize * $judulStyle['size'] / max(1, $fontSizeBase ?? $fontSize) }}pt;
        line-height: {{ $judulStyle['lineHeight'] }};
    }
    .title-sub {
        text-align: {{ $judulStyle['align'] }};
        font-weight: {{ $tahunStyle['bold'] ? 'bold' : 'normal' }};
        font-size: {{ $fontSize * $tahunStyle['size'] / max(1, $fontSizeBase ?? $fontSize) }}pt;
        line-height: {{ $tahunStyle['lineHeight'] }};
        margin-top: 0.2em;
    }

    /* Tabel identitas (maks 3 baris, 2 pasang label/nilai per baris) */
    table.identitas {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0.6em;
        table-layout: fixed;
    }
    table.identitas td {
        border: {{ $identitas['border'] ? '0.3mm solid #000' : 'none' }};
        padding: 0.35mm {{ $identitas['padding'] }}mm;
        vertical-align: middle;
        font-size: {{ $fontSize * $identitas['size'] / max(1, $fontSizeBase ?? $fontSize) }}pt;
    }
    table.identitas td.label { width: 21%; }
    table.identitas td.value { width: 29%; }
    /* RTL: nilainya rata kanan agar sejajar dengan label Arab di kanannya. */
    table.identitas.rtl-exam td.label { text-align: right; }
    table.identitas.rtl-exam td.value { text-align: right; }
    .nama-line { display: inline-block; width: 100%; border-bottom: 0.3mm dotted #000; }

    .karakter-title {
        font-weight: bold;
        margin-top: 0.5em;
        font-size: {{ $fontSize * $karakterStyle['size'] / max(1, $fontSizeBase ?? $fontSize) }}pt;
    }
    /* Naskah Arab: judul penanaman karakter rata KANAN. Dompdf tidak
       menerapkan `dir="rtl"`/`direction` pada blok, jadi perataan harus
       eksplisit — tanpa ini judul menempel ke kiri halaman. */
    @if($rtlExam)
        .karakter-title { text-align: right; }
    @endif
    ol.karakter {
        margin: 0.1em 0 0 1.6em;
        padding: 0;
        list-style-type: {{ $marker }};
        font-size: {{ $fontSize * $karakterStyle['size'] / max(1, $fontSizeBase ?? $fontSize) }}pt;
        line-height: {{ $karakterStyle['lineHeight'] }};
        font-weight: {{ $karakterStyle['bold'] ? 'bold' : 'normal' }};
    }
    {{-- Mode "dash": Dompdf tidak punya list-style dash; tandai manual. --}}
    @if($karakterStyle['marker'] === 'dash')
        ol.karakter { list-style: none; margin-left: 0.4em; }
    @endif
    ol.karakter li { margin-bottom: 0; }
    /* Daftar karakter pada naskah Arab: penanda di kanan, teks mengalir RTL. */
    /* Teks Arab sudah rata kanan lewat marksToHtml(); direction: rtl akan
       membalik urutan inline anak (nomor lompat ke kiri). */
    ol.karakter.rtl { text-align: right; margin: 0.1em 1.6em 0 0; }
    @if($rtlExam && $karakterStyle['marker'] === 'numbered')
        ol.karakter.rtl { list-style-type: arabic-indic; }
    @endif

    .instruksi {
        margin-top: 0.6em;
        font-size: {{ $fontSize * $instruksiStyle['size'] / max(1, $fontSizeBase ?? $fontSize) }}pt;
        line-height: {{ $instruksiStyle['spacing'] }};
        font-weight: {{ $instruksiStyle['bold'] ? 'bold' : 'normal' }};
        font-style: {{ $instruksiStyle['italic'] ? 'italic' : 'normal' }};
    }
    .instruksi-arab { margin-top: 0.2em; text-align: right; }

    /* Naskah Arab: SEMUA teks paragraf rata kanan.
       Dompdf tidak mewarisi `dir="rtl"`/`direction` dari <html>, jadi setiap
       blok teks harus diberi perataan eksplisit — tanpa ini judul, instruksi,
       dan daftar karakter menempel ke kiri halaman. */
    .karakter-title, .karakter li, .instruksi-arab, .wacana-title,
    .wacana-text, .wacana-caption { text-align: right; }

    /* Wacana / soal cerita — naskah Arab selalu rata KANAN, bukan justify
       (justify merenggangkan spasi dan menyulitkan baca Arab). */
    .wacana { margin-top: {{ $questionGapPt }}pt; page-break-inside: avoid; text-align: right; }
    .wacana-title { font-weight: bold; text-align: right; }
    .wacana-text {
        margin-top: 0.2em;
        padding: 1.2mm 2mm;
        border-right: 0.6mm solid #555;
        background: #f5f5f5;
        text-align: right;
        line-height: {{ $lineHeight * 1.25 }};
    }
    .wacana-caption { margin-top: 0.1em; font-style: italic; text-align: right; }

    /* Butir soal — opsi dalam kolom sejajar (A..D selalu pada posisi sama) */
    .q { margin-top: {{ $questionGapPt }}pt; page-break-inside: avoid; }

    /* Nomor soal + isi (teks DAN opsi) berada dalam kolom yang sama,
       sehingga teks soal sejajar persis dengan huruf pilihan A, dan baris
       lanjutan teks menjorok sejajar teks (hanging indent). */
    table.qwrap { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.qwrap td { vertical-align: top; padding: 0; }
    table.qwrap td.qno { width: 4.5%; font-weight: normal; }
    table.qwrap td.qbody { width: 95.5%; }

    /* RTL = CERMIN dari LTR. Tabel tetap dibangun kiri-ke-kanan, jadi kolom
       nomor yang semula di kiri dipindah ke kanan dengan lebar yang SAMA
       (4.5%). Tanpa aturan ini nomor di RTL mendapat kolom lebar sel isi. */
    table.qwrap.rtl td.qno { text-align: right; }
    table.qwrap.rtl td.qbody { text-align: right; }
    table.opts {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin: 0.1em 0 0 0;
    }
    table.opts td {
        vertical-align: top;
        padding: 0 {{ $optionIndent }}mm 0 0;
        font-size: {{ $fontSize }}pt;
        line-height: {{ $lineHeight }};
    }
    /* Jarak antar kolom untuk naskah (LTR maupun RTL) diatur lewat inline
       style di partial opsi, karena padding harus ikut diperhitungkan
       dalam lebar sel agar kolom tidak meluber. */
    table.opts td .letter { font-weight: normal; }

    /* Penanda huruf Arab (ا/ب/ج/د). Dompdf mengabaikan inline-block pada
       span, jadi lebar tetap tidak bisa dipakai untuk merapikan kolom.
       Yang dijaga sebagai gantinya:
       - marker menempel pada teks jawaban (spasi normal), tidak melayang;
       - titik (num-dot) memakai font Latin agar tidak terbaca "٠";
       - margin kanan memberi jarak seragam ke kolom berikutnya. */
    table.opts td .letter-ar {
        margin-right: 1.5mm;
        white-space: nowrap;
    }
    table.opts td .letter-ar .marker-dot {
        font-family: {!! $fontStack !!};
        margin-left: 0.2mm;
    }

    /* Tata letak otomatis: lebar kolom TETAP dan identik, tiap pilihan
       tepat 1 baris. Lebar mm ditetapkan di partial opsi. */
    table.opts.opts-auto td { white-space: nowrap; }

    /* ── Grid kolom label + isi (struktur dua kolom per opsi) ─────────────
       Setiap opsi = <td class="col-label"> + <td class="col-isi">. Lebar
       keduanya DITULIS EKSPLISIT dalam mm oleh partial, sehingga:
         - semua label berdiri di satu garis vertikal;
         - awal teks setiap opsi mulai pada x yang sama;
         - jarak label→isi konsisten apa pun panjang teksnya.

       `padding: 0` WAJIB menimpa aturan `table.opts td` di atas: Dompdf
       menambahkan padding DI LUAR `width`, jadi padding warisan akan
       melebarkan setiap sel dan menggeser seluruh kolom ke kanan. */
    table.opts.opts-grid td {
        padding: 0;
        vertical-align: top;
    }
    table.opts.opts-grid td.col-label {
        white-space: nowrap;
    }
    table.opts.opts-grid td .letter-ar {
        margin-right: 0;
    }

    .paper-note { font-size: {{ max(6.0, $fontSize - 4) }}pt; color: #666; }
</style>
</head>
<body>

    {{-- ── KOP LEMBAGA ───────────────────────────────────────────────────
         Hanya memakai gambar kop yang diunggah di Pengaturan Sekolah.
         Bila belum ada, area kop dikosongkan (tidak memakai teks). --}}
    @if($showKop && ! empty($letterhead['kopBase64']))
        @php
            // Lebar kop mengikuti persentase area margin, tetapi TINGGI tetap
            // dibatasi agar kop tidak menghabiskan halaman. Bila tinggi
            // melebihi batas, lebar ikut menyusut (proporsi terjaga).
            // Pakai rasio ASLI gambar (kopAspect), bukan rasio dari lebar/tinggi
            // yang sudah dibatasi, agar skala kop tetap benar.
            $kopAspect = (float) ($letterhead['kopAspect'] ?? 0);
            if ($kopAspect <= 0) {
                $kopAspect = (float) ($letterhead['kopHeightMm'] ?? 0) / max(0.0001, (float) ($letterhead['kopWidthMm'] ?? 145));
            }
            $areaWidthMm = $paper['width_mm'] - $marginMm['left'] - $marginMm['right'];
            $kopWidthMm = $areaWidthMm * ($kopWidthPct / 100);
            // Naskah soal boleh mengirim batas tinggi sendiri (kop selebar
            // margin tetap proporsional). Paket/LJK memakai batas bawaan.
            $kopMaxHeightMm = (float) ($kopMaxHeightMm ?? config('ljk.template.max_kop_height_mm', 20.0));

            $kopHeightMm = $kopWidthMm * $kopAspect;
            if ($kopAspect > 0 && $kopHeightMm > $kopMaxHeightMm) {
                $kopWidthMm = round($kopMaxHeightMm / $kopAspect, 2);
                $kopHeightMm = $kopMaxHeightMm;
            }
        @endphp
        <div class="kop">
            <img class="kop-image" src="{{ $letterhead['kopBase64'] }}"
                 style="width: {{ round($kopWidthMm, 2) }}mm; height: {{ round($kopHeightMm, 2) }}mm;">
            @if($kopRule)
                <div class="kop-rule"></div>
            @endif
        </div>
    @endif

    {{-- ── JUDUL UJIAN ─────────────────────────────────────────────────── --}}
    {{-- Judul dilewatkan LjkArabicText agar teks Arab benar-benar di-shape;
         tanpa ini huruf Arab tampil sebagai kotak pada Dompdf. --}}
    <div class="title">{!! LjkArabicText::marksToHtml($line1) !!}</div>
    @if($line2 !== '')
        <div class="title-sub">{!! LjkArabicText::marksToHtml($line2) !!}</div>
    @endif
    @if(($tanggalArab ?? '') !== '')
        {{-- Naskah Arab: tanggal Hijriah + Masehi, rata kanan.
             LjkArabicText::toHtmlRtl() menyusun urutan visual RTL untuk Dompdf;
             deret angka kini dijaga tetap urut oleh reverseClusters(). --}}
        <div class="title-sub" style="text-align: right;">{!! LjkArabicText::marksToHtml($tanggalArab) !!}</div>
    @endif

    {{-- Identitas: tabel maksimal 3 baris ─────────────────────────────── --}}
    @if($identitas['show'] && ! empty($identityRows))
    <table class="identitas {{ $rtlExam ? 'rtl-exam' : '' }}">
        @foreach($identityRows as $row)
            @php
                // Cermin urutan pasangan DAN urutan label/nilai: pada RTL
                // pasangan paling kanan adalah pasangan pertama.
                $pasangan = $rtlExam ? array_reverse($row) : $row;
            @endphp
            <tr>
                @foreach($pasangan as [$label, $value])
                    @if($rtlExam)
                        {{-- RTL: nilai di kiri, label di kanan. Tabel tetap
                             dibangun kiri-ke-kanan oleh Dompdf; pasangan sudah
                             dicermin di atas. --}}
                        <td class="value">
                            @if($label === (config('ljk.soal.identitas_arab.peserta') ?? ''))
                                <span class="nama-line">&nbsp;</span>
                            @else
                                {!! LjkArabicText::marksToHtml($value) !!}
                            @endif
                        </td>
                        <td class="label">{!! LjkArabicText::marksToHtml($label) !!}</td>
                    @else
                        <td class="label">{{ $label }}</td>
                        <td class="value">
                            @if($label === $identitas['labelPeserta'])
                                <span class="nama-line">&nbsp;</span>
                            @else
                                {!! LjkArabicText::marksToHtml($value) !!}
                            @endif
                        </td>
                    @endif
                @endforeach

                {{-- Sel kosong bila baris hanya berisi satu pasang --}}
                @for($i = count($row); $i < 2; $i++)
                    <td class="label">&nbsp;</td>
                    <td class="value">&nbsp;</td>
                @endfor
            </tr>
        @endforeach
    </table>
    @endif

    {{-- ── PENANAMAN KARAKTER ──────────────────────────────────────────── --}}
    @if(! empty($karakter))
        <div class="karakter-title">{!! LjkArabicText::marksToHtml($karakterStyle['title']) !!}</div>
        @if($rtlExam && $karakterStyle['marker'] === 'numbered')
            {{-- Penanda daftar Arab: angka Arab + titik Latin, di kanan.
                 Margin kanan 1.6em memberi ruang agar penanda tidak keluar
                 area cetak (tanpa margin, nomor terpotong di tepi halaman). --}}
            <ol class="karakter rtl" style="list-style: none; margin: 0.1em 1.6em 0 0;">
                @foreach($karakter as $butir)
                    @php
                        $nomorKarakter = strtr((string) $loop->iteration, [
                            '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
                            '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
                        ]);
                    @endphp
                    <li>
                        {{-- Urutan elemen mengikuti bidi: teks Arab ditulis lebih
                             dulu, baru penanda nomor — sehingga nomor berada di
                             KANAN baris dan teks mengalir ke kiri.
                             Titik nomor memakai font Latin dan ditulis SETELAH
                             angka agar tampil "١." (bila ditulis sebelum angka,
                             titik terbaca "٠" sehingga muncul ".١"). --}}
                        {!! LjkArabicText::marksToHtml($butir) !!}
                        {{-- Label nomor diisolasi LTR: Dompdf tidak menghitung
                             bidi, jadi urutan elemen di dalam span ini = urutan
                             cetak. Angka lalu titik → tampil "١." di tepi kanan. --}}
                        <span class="karakter-no" style="direction: ltr; unicode-bidi: isolate;"><span class="ar ar-number">{{ $nomorKarakter }}</span><span class="num-dot">.</span></span>
                    </li>
                @endforeach
            </ol>
        @else
            <ol class="karakter {{ $rtlExam ? 'rtl' : '' }}">
                @foreach($karakter as $butir)
                    <li>
                        @if($karakterStyle['marker'] === 'dash'){{ '– ' }}@endif
                        {!! LjkArabicText::marksToHtml($butir) !!}
                    </li>
                @endforeach
            </ol>
        @endif
    @endif

    {{-- ── INSTRUKSI ───────────────────────────────────────────────────── --}}
    {{-- Naskah Arab memakai instruksi Arab saja; instruksi Latin bawaan
         hanya untuk naskah Latin. --}}
    @if($instruksiStyle['show'])
        @if($rtlExam)
            @if(($instruksiArab ?? '') !== '')
                <div class="instruksi-arab ar-block">{!! LjkArabicText::instructionsToHtml($instruksiArab) !!}</div>
            @elseif($instruksi !== '')
                <div class="instruksi"><strong>A.</strong> {{ $instruksi }}</div>
            @endif
        @elseif($instruksi !== '')
            <div class="instruksi"><strong>A.</strong> {{ $instruksi }}</div>
        @endif
    @endif

    {{-- ── WACANA & SOAL ───────────────────────────────────────────────── --}}
    @foreach(($blocks ?? []) as $block)

        @if($block['type'] === 'wacana')
            <div class="wacana">
                @if(! empty($block['judul']))
                    <div class="wacana-title">{!! LjkArabicText::marksToHtml($block['judul']) !!}</div>
                @endif
                <div class="wacana-text {{ ($block['rtl'] ?? false) ? 'ar-block' : '' }}">
                    @php $wacanaHtml = (string) ($block['teks'] ?? ''); @endphp
                    {!! strip_tags($wacanaHtml) !== $wacanaHtml ? $wacanaHtml : LjkArabicText::marksToHtml($wacanaHtml) !!}
                </div>
                @if(! empty($block['sumber']))
                    <div class="wacana-caption paper-note">Sumber: {!! LjkArabicText::marksToHtml($block['sumber']) !!}</div>
                @endif
                @if($block['dari'] !== null)
                    @php
                        $dari = $block['dari_tampil'] ?? $block['dari'];
                        $sampai = $block['sampai_tampil'] ?? $block['sampai'];
                    @endphp
                    @if($rtlExam)
                        @php
                            $captionArab = $dari === $sampai
                                ? "النَّصُّ لِلسُّؤَالِ رَقْمِ {$dari}."
                                : "النَّصُّ لِلْأَسْئِلَةِ مِنْ رَقْمِ {$dari} إِلَى {$sampai}.";
                        @endphp
                        <div class="wacana-caption ar-block"><span class="ar">{!! e(LjkArabicText::shape($captionArab)) !!}</span></div>
                    @else
                        <div class="wacana-caption">Wacana untuk soal nomor @if($arabAngka ?? false)<span class="ar ar-number">{{ $dari }}{{ $sampai !== $dari ? '–'.$sampai : '' }}</span>@else{{ $dari }}{{ $sampai !== $dari ? '–'.$sampai : '' }}@endif.</div>
                    @endif
                @endif
            </div>
        @else
            @php $rtl = true; @endphp
            <div class="q">
                {{-- Satu tabel: nomor + kolom isi (teks soal DAN opsi ada di kolom
                     yang sama) → teks soal sejajar dengan huruf pilihan A. --}}
                <table class="qwrap {{ $rtl ? 'rtl' : '' }}">
                    <tr>
                        @if($rtl)
                            {{-- RTL: CETAKAN CERMIN dari baris LTR. Dompdf selalu
                                 menyusun sel kiri-ke-kanan, jadi sel isi ditaruh
                                 lebih dulu selebar 95.5% lalu sel nomor 4.5%.
                                 Hasilnya nomor di kanan, teks rata kanan, dan
                                 lebar kolom identik dengan versi Latin. --}}
                            <td class="qbody" style="width: 95.5%; text-align: right;">
                                <div>{!! LjkArabicText::marksToHtml($block['pertanyaan']) !!}</div>
                                @if(! empty($block['gambar_path']))
                                    <div style="text-align:center;margin:2mm 0;"><img src="{{ \Illuminate\Support\Facades\Storage::url($block['gambar_path']) }}" style="max-width:80mm;max-height:50mm;"><div class="paper-note">{{ $block['gambar_keterangan'] ?? '' }}</div></div>
                                @endif
                                @include('ljk.arab.soal-opsi', ['block' => $block, 'rtl' => true, 'maxOptions' => $maxOptions, 'colWidth' => $colWidth, 'opsiWidthMm' => $opsiWidthMm ?? null, 'opsiIndentMm' => $optionIndent ?? null])
                            </td>
                            <td class="qno" style="width: 4.5%; text-align: right;">
                                @if($arabAngka ?? false)@php $nomorSoal = rtrim((string) ($block['nomor_tampil'] ?? ($block['nomor'].'.')), '.'); @endphp<span class="qno-number"><span class="num-dot">.</span><span class="ar ar-number">{{ $nomorSoal }}</span></span>@else{{ $block['nomor_tampil'] ?? ($block['nomor'].'.') }}@endif
                            </td>
                        @else
                            <td class="qno">
                                @if($arabAngka ?? false)@php $nomorSoal = rtrim((string) ($block['nomor_tampil'] ?? ($block['nomor'].'.')), '.'); @endphp<span class="qno-number"><span class="num-dot">.</span><span class="ar ar-number">{{ $nomorSoal }}</span></span>@else{{ $block['nomor_tampil'] ?? ($block['nomor'].'.') }}@endif
                            </td>
                            <td class="qbody">
                                <div>{!! LjkArabicText::marksToHtml($block['pertanyaan']) !!}</div>
                                @if(! empty($block['gambar_path']))
                                    <div style="text-align:center;margin:2mm 0;"><img src="{{ \Illuminate\Support\Facades\Storage::url($block['gambar_path']) }}" style="max-width:80mm;max-height:50mm;"><div class="paper-note">{{ $block['gambar_keterangan'] ?? '' }}</div></div>
                                @endif
                                @include('ljk.arab.soal-opsi', ['block' => $block, 'rtl' => false, 'maxOptions' => $maxOptions, 'colWidth' => $colWidth, 'opsiWidthMm' => $opsiWidthMm ?? null, 'opsiIndentMm' => $optionIndent ?? null])
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
