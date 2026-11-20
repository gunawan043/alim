@php
    $bahasa = $bahasa ?? 'id';
    $isArabic = $bahasa === 'ar';
    $labels = $labels ?? [];
    $label = fn (string $key) => $labels[$key] ?? $key;
    $text = fn (?string $value) => $isArabic
        ? new \Illuminate\Support\HtmlString(\App\Support\ArabicText::toHtml((string) $value, false))
        : e((string) $value);
    $ket = fn (?string $value) => $isArabic
        ? ([
            'Terlampaui' => 'متجاوز',
            'Tercapai' => 'محقق',
            'Belum Tuntas' => 'غير مكتمل',
        ][$value] ?? $value)
        : $value;
    $pred = fn (?string $value) => $isArabic
        ? ([
            "Mumtaz Murtafi'" => 'ممتاز مرتفع',
            'Mumtaz' => 'ممتاز',
            'Jayyid Jiddan' => 'جيد جدًا',
            'Jayyid' => 'جيد',
            'Maqbul' => 'مقبول',
            'Roosib' => 'راسب',
        ][$value] ?? $value)
        : $value;
    // Angka (nilai, KKTP, rata-rata, tanggal, dsb.) otomatis jadi angka Arab-Indik.
    $num = fn ($value) => $isArabic ? \App\Support\ArabicText::digits((string) $value) : (string) $value;
    $semesterText = fn (?string $value) => $isArabic
        ? $label(strtolower((string) $value) === 'genap' ? 'semester_genap' : 'semester_ganjil')
        : ucfirst((string) $value);
    $cityText = fn (?string $value) => $isArabic
        ? \App\Support\ArabicText::cityName((string) $value)
        : (string) $value;
    $printDate = $distributionDate ?? now();
    $printCity = $distributionCity ?: ($studyGroup->school?->city?->display_name ?? 'Mataram');
    $hijriDate = $isArabic
        ? (\App\Support\ArabicText::formatHijriDate($printDate) ?: \App\Support\ArabicText::formatDate($printDate))
        : '';
    $layout = array_merge(\App\Models\RaporDistributionSetting::defaultLayout(), $layout ?? []);
    // Kolom Bobot & Jumlah hanya untuk rapor SAS (nilai akhir).
    $showBobot = (($santri[array_key_first($santri)]['Jenis'] ?? 'sts') === 'sas');
    $borderWidth = (float) ($layout['table_border_width'] ?? 0.3);
    $boldHeader = (bool) ($layout['bold_table_header'] ?? true);
    $boldSubject = (bool) ($layout['bold_subject'] ?? true);
    $paperSize = $layout['paper_size'] ?? 'a4';
    $paperCss = match ($paperSize) {
        'folio' => '215mm 330mm',
        'letter' => 'letter',
        default => 'A4',
    };
@endphp
<!DOCTYPE html>
<html lang="{{ $isArabic ? 'ar' : 'id' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>Rapor — {{ $santri[array_key_first($santri)]['NamaAr'] ?? $santri[array_key_first($santri)]['Nama'] ?? '' }}</title>
    <style>
        {!! \App\Services\Ljk\LjkPdfFonts::arabicFaceCss() !!}

        @page {
            size: {{ $paperCss }};
            margin: 0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: {{ $layout['font_size'] }}pt;
            line-height: {{ $layout['line_height'] }};
            color: #000;
        }

        body.ar {
            font-family: {!! \App\Services\Ljk\LjkPdfFonts::arabicStack() !!};
            direction: rtl;
            /* Layout Arab dibuat padat agar STS/SAS (termasuk 18+ mapel) muat satu halaman. */
            font-size: 8.5pt;
            line-height: 1.12;
        }

        body.ar .page {
            padding: 5mm 10mm 7mm 10mm;
        }

        body.ar .kop-surat {
            height: 18mm !important;
        }

        body.ar .judul {
            font-size: 11pt;
            line-height: 1.1;
            margin: 2px 0 4px;
        }

        body.ar .info-grid td {
            padding: 0.5px 2px;
            font-size: 8pt;
            line-height: 1.1;
        }

        body.ar table.nilai {
            margin-top: 3px;
        }

        body.ar table.nilai th,
        body.ar table.nilai td {
            padding: 1px 3px;
            font-size: 7.5pt;
            line-height: 1.05;
        }

        body.ar .ringkasan-box {
            margin-top: 3px;
        }

        body.ar .ringkasan-box td {
            padding: 1px 4px;
            font-size: 7.5pt;
            line-height: 1.1;
        }

        body.ar .ringkasan-box td.pred-val {
            font-size: 10pt;
        }

        body.ar .catatan-box {
            margin-top: 3px;
        }

        body.ar .catatan-box td {
            padding: 2px 4px;
            font-size: 7.5pt;
            line-height: 1.1;
        }

        body.ar .ttd-grid {
            margin-top: 4px;
        }

        body.ar .ttd-grid td {
            padding: 1px 3px;
            font-size: 7.5pt;
            line-height: 1.1;
        }

        body.ar .ttd-sig-area {
            height: 6mm !important;
        }

        body.ar .signature-stack {
            width: 18mm !important;
            height: 7mm !important;
        }

        body.ar .signature-stack .stamp-image,
        body.ar .signature-stack .ttd-image,
        body.ar .signature-image {
            max-width: 18mm;
            max-height: 7mm;
        }

        body.ar .ttd-title,
        body.ar .ttd-name,
        body.ar .ttd-nip {
            font-size: 7.5pt;
            line-height: 1.1;
        }

        body.ar .footer-bar {
            padding-top: 1px;
            padding-bottom: 1px;
        }

        /* RTL: tabel, urutan kolom, dan perataan teks mengikuti arah Arab. */
        body.ar .ringkasan-box,
        body.ar .catatan-box,
        body.ar .ttd-grid,
        body.ar .footer-bar {
            direction: rtl;
        }
        body.ar table.nilai th,
        body.ar table.nilai td,
        body.ar .info-grid td,
        body.ar .ringkasan-box td,
        body.ar .catatan-box td {
            text-align: right;
        }
        body.ar table.nilai td.no,
        body.ar table.nilai td.kkm,
        body.ar table.nilai td.nilai-col,
        body.ar table.nilai td.ket-col,
        body.ar .ringkasan-box td.colon,
        body.ar .ringkasan-box td.val {
            text-align: center;
        }
        body.ar .info-grid td.colon {
            text-align: center;
        }

        .ar {
            font-family: {!! \App\Services\Ljk\LjkPdfFonts::arabicStack() !!};
            direction: rtl;
            unicode-bidi: embed;
            font-style: normal;
        }

        /* ── Kop Surat ── */
        .kop-surat {
            width: 100%;
            display: block;
            object-fit: contain;
        }

        .signature-block {
            display: inline-flex;
            align-items: flex-end;
            justify-content: center;
            vertical-align: bottom;
        }
        .signature-stack {
            position: relative;
            display: inline-block;
            vertical-align: bottom;
        }
        .signature-image {
            display: block;
            object-fit: contain;
        }
        .signature-stack .stamp-image {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
            opacity: 0.9;
            object-fit: contain;
        }
        .signature-stack .ttd-image {
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            z-index: 2;
            object-fit: contain;
        }
        .signature-line {
            letter-spacing: 1px;
            white-space: nowrap;
        }

        /* ── Page wrapper per siswa ── */
        .page {
            padding: {{ $layout['margin_top'] }}mm {{ $layout['margin_right'] }}mm {{ $layout['margin_bottom'] }}mm {{ $layout['margin_left'] }}mm;
            position: relative;
        }

        /* ── Judul ── */
        .judul {
            text-align: center;
            font-size: {{ $layout['title_font_size'] }}pt;
            font-weight: bold;
            margin: 12px 0 14px 0;
            text-transform: uppercase;
            text-decoration: underline;
        }

        /* ── Info grid ── */
        .info-grid {
            width: 100%;
            border: none;
            border-collapse: collapse;
        }
        .info-grid td {
            border: none;
            padding: 3px 4px;
            font-size: {{ $layout['info_font_size'] }}pt;
            line-height: {{ $layout['line_height'] }};
            vertical-align: middle;
        }
        .info-grid td.lbl {
            font-weight: bold;
            width: 13%;
            white-space: nowrap;
        }
        .info-grid td.colon {
            width: 2%;
            font-weight: bold;
            text-align: center;
        }
        .info-grid td.val {
            width: 35%;
            font-weight: bold;
            white-space: nowrap;
        }

        /* ── Tabel Nilai ── */
        table.nilai {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.nilai th,
        table.nilai td {
            border: {{ $borderWidth }}mm solid #000;
            padding: {{ $layout['table_row_padding'] ?? 0.5 }}mm 6px;
            font-size: {{ $layout['table_font_size'] }}pt;
            line-height: {{ $layout['table_line_height'] ?? 1.05 }};
        }
        table.nilai th {
            background: #f0f0f0;
            font-weight: {{ $boldHeader ? 'bold' : 'normal' }};
            text-align: center;
            vertical-align: middle;
        }
        table.nilai td.mapel {
            text-align: left;
            width: 40%;
            font-weight: {{ $boldSubject ? 'bold' : 'normal' }};
        }
        table.nilai td.no {
            text-align: center;
            width: 5%;
        }
        table.nilai td.kkm {
            text-align: center;
            width: 8%;
        }
        table.nilai td.nilai-col {
            text-align: center;
            width: 8%;
        }
        table.nilai td.bobot-col {
            text-align: center;
            width: 8%;
        }
        table.nilai td.jumlah-col {
            text-align: center;
            width: 12%;
        }
        table.nilai.with-bobot td.mapel {
            width: 28%;
        }
        table.nilai.with-bobot td.ket-col {
            width: 16%;
        }
        table.nilai td.ket-col {
            text-align: center;
            width: 20%;
        }

        /* ── Ringkasan ── */
        .ringkasan-box {
            width: 100%;
            border: 1px solid #000;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .ringkasan-box td {
            border: {{ $borderWidth }}mm solid #000;
            padding: 3px 8px;
            font-size: {{ $layout['table_font_size'] }}pt;
            font-weight: bold;
        }
        .ringkasan-box td.lbl {
            text-align: left;
            width: 28%;
        }
        .ringkasan-box td.colon {
            text-align: center;
            width: 3%;
        }
        .ringkasan-box td.val {
            text-align: center;
            width: 10%;
        }
        .ringkasan-box td.pred-label {
            text-align: center;
            width: 20%;
        }
        .ringkasan-box td.empty {
            border: none;
            width: 5%;
        }
        .ringkasan-box td.pred-val {
            border: none;
            font-size: 16pt;
            font-weight: bold;
        }

        /* ── Catatan ── */
        .catatan-box {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .catatan-box td {
            border: {{ $borderWidth }}mm solid #000;
            padding: 4px 8px;
            font-size: {{ $layout['table_font_size'] }}pt;
            vertical-align: top;
        }
        .catatan-box td.hdr {
            font-weight: bold;
            background: #f0f0f0;
        }
        .catatan-box td.lbl-col {
            width: 40%;
        }
        .catatan-box .catatan-umum {
            margin-top: 3px;
            padding-top: 2px;
            border-top: 1px dotted #94a3b8;
            font-size: {{ max(6.5, (float) $layout['table_font_size'] - 1.5) }}pt;
            line-height: 1.15;
        }

        /* ── TTD ── */
        .ttd-grid {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .ttd-grid td {
            border: none;
            font-size: {{ $layout['footer_font_size'] + 1 }}pt;
            text-align: center;
            vertical-align: bottom;
            padding: 4px 8px;
        }
        .ttd-grid td.ttd-date {
            text-align: right;
            padding: 0 8px 14px 8px;
            font-size: {{ $layout['footer_font_size'] + 1 }}pt;
        }
        .ttd-grid td.ttd-col {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
        }
        .ttd-title {
            font-size: {{ $layout['footer_font_size'] + 1 }}pt;
            line-height: 1.3;
            min-height: 1.3em;
        }
        .ttd-sig-area {
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }
        .ttd-name {
            font-size: {{ $layout['footer_font_size'] + 1 }}pt;
            line-height: 1.15;
        }
        .ttd-nip {
            font-size: {{ $layout['footer_font_size'] + 1 }}pt;
            line-height: 1.15;
            margin-top: 0;
        }
        .ttd-name + .ttd-nip {
            margin-top: -0.5mm;
        }

        /* ── Footer bar ── */
        .footer-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #000;
            padding: 4px {{ $layout['margin_right'] }}mm 4px {{ $layout['margin_left'] }}mm;
            display: table;
            width: 100%;
        }
        .footer-bar td {
            border: none;
            font-size: {{ $layout['footer_font_size'] }}pt;
            display: table-cell;
        }
        .footer-bar td.left { text-align: left; width: 30%; }
        .footer-bar td.center { text-align: center; width: 40%; }
        .footer-bar td.right { text-align: right; width: 30%; }

        /* ── Page break ── */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body class="{{ $isArabic ? 'ar' : '' }}">

@foreach($santri as $key => $data)
<div class="page{{ !$loop->last ? ' page-break' : '' }}">

    {{-- Kop Surat --}}
    @if(($layout['show_kop'] ?? true) && $kopBase64)
    <img src="{{ $kopBase64 }}" class="kop-surat" style="height: {{ $layout['kop_height'] }}mm;" alt="Kop Surat">
    @endif

    {{-- Judul --}}
    <div class="judul">{!! $text($label(($data['Jenis'] ?? 'sts') === 'sas' ? 'title_sas' : 'title_sts')) !!}</div>

    {{-- Info Santri --}}
    <table class="info-grid">
        @if($isArabic)
            <tr>
                <td class="val">{!! $text($semesterText($data['Semester'] ?? '-')) !!}</td>
                <td class="colon">:</td>
                <td class="lbl">{!! $text($label('semester')) !!}</td>
                <td class="val">{!! $text(($isArabic && ! empty($data['NamaAr'])) ? $data['NamaAr'] : ($data['Nama'] ?? '-')) !!}</td>
                <td class="colon">:</td>
                <td class="lbl">{!! $text($label('nama')) !!}</td>
            </tr>
            <tr>
                <td class="val">{{ $num($data['TahunAjaran'] ?? '-') }}</td>
                <td class="colon">:</td>
                <td class="lbl">{!! $text($label('tahun_ajaran')) !!}</td>
                <td class="val">{!! $text($data['Kelas'] ?? '-') !!}</td>
                <td class="colon">:</td>
                <td class="lbl">{!! $text($label('kelas')) !!}</td>
            </tr>
            <tr>
                <td class="val" colspan="4">{{ $num($data['NIS'] ?? '-') }} / {{ $num($data['NISN'] ?? '-') }}</td>
                <td class="colon">:</td>
                <td class="lbl">{!! $text($label('nis_nisn')) !!}</td>
            </tr>
        @else
            <tr>
                <td class="lbl">{!! $text($label('nama')) !!}</td>
                <td class="colon">:</td>
                <td class="val">{!! $text($data['Nama'] ?? '-') !!}</td>
                <td class="lbl">{!! $text($label('semester')) !!}</td>
                <td class="colon">:</td>
                <td class="val">{{ ucfirst((string) ($data['Semester'] ?? '-')) }}</td>
            </tr>
            <tr>
                <td class="lbl">{!! $text($label('kelas')) !!}</td>
                <td class="colon">:</td>
                <td class="val">{!! $text($data['Kelas'] ?? '-') !!}</td>
                <td class="lbl">{!! $text($label('tahun_ajaran')) !!}</td>
                <td class="colon">:</td>
                <td class="val">{{ $data['TahunAjaran'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">{!! $text($label('nis_nisn')) !!}</td>
                <td class="colon">:</td>
                <td class="val" colspan="4">{{ $data['NIS'] ?? '-' }} / {{ $data['NISN'] ?? '-' }}</td>
            </tr>
        @endif
    </table>

    {{-- Tabel Nilai: urutan kolom dibalik eksplisit untuk RTL. --}}
    <table class="nilai {{ $showBobot ? 'with-bobot' : '' }}">
        <thead>
            <tr>
                @if($isArabic)
                    <th class="ket-col">{!! $text($label('keterangan')) !!}</th>
                    @if($showBobot)
                        <th class="jumlah-col">{!! $text($label('jumlah_bobot_nilai')) !!}</th>
                    @endif
                    <th class="nilai-col">{!! $text($label('nilai')) !!}</th>
                    @if($showBobot)
                        <th class="bobot-col">{!! $text($label('bobot')) !!}</th>
                    @endif
                    <th class="kkm">{!! $text($label('kktp')) !!}</th>
                    <th class="mapel">{!! $text($label('mapel')) !!}</th>
                    <th class="no">{!! $text($label('no')) !!}</th>
                @else
                    <th class="no">{!! $text($label('no')) !!}</th>
                    <th class="mapel">{!! $text($label('mapel')) !!}</th>
                    <th class="kkm">{!! $text($label('kktp')) !!}</th>
                    @if($showBobot)
                        <th class="bobot-col">{!! $text($label('bobot')) !!}</th>
                    @endif
                    <th class="nilai-col">{!! $text($label('nilai')) !!}</th>
                    @if($showBobot)
                        <th class="jumlah-col">{!! $text($label('jumlah_bobot_nilai')) !!}</th>
                    @endif
                    <th class="ket-col">{!! $text($label('keterangan')) !!}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($data['Mapel'] as $row)
            <tr>
                @if($isArabic)
                    <td class="ket-col">{!! $text($ket($row['keterangan'])) !!}</td>
                    @if($showBobot)
                        <td class="jumlah-col">{{ $num($row['jumlah'] ?? '') }}</td>
                    @endif
                    <td class="nilai-col">{{ $num($row['nilai']) }}</td>
                    @if($showBobot)
                        <td class="bobot-col">{{ $num($row['bobot'] ?? '') }}</td>
                    @endif
                    <td class="kkm">{{ $num($row['kkm']) }}</td>
                    <td class="mapel">{!! $text(($isArabic && ! empty($row['mapel_ar'])) ? $row['mapel_ar'] : $row['mapel']) !!}</td>
                    <td class="no">{{ $num($row['no']) }}</td>
                @else
                    <td class="no">{{ $num($row['no']) }}</td>
                    <td class="mapel">{!! $text(($isArabic && ! empty($row['mapel_ar'])) ? $row['mapel_ar'] : $row['mapel']) !!}</td>
                    <td class="kkm">{{ $num($row['kkm']) }}</td>
                    @if($showBobot)
                        <td class="bobot-col">{{ $num($row['bobot'] ?? '') }}</td>
                    @endif
                    <td class="nilai-col">{{ $num($row['nilai']) }}</td>
                    @if($showBobot)
                        <td class="jumlah-col">{{ $num($row['jumlah'] ?? '') }}</td>
                    @endif
                    <td class="ket-col">{!! $text($ket($row['keterangan'])) !!}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Ringkasan --}}
    <table class="ringkasan-box" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
        <tr>
            <td class="lbl" style="text-align:left; padding-left:16px;">{!! $text($label($showBobot ? 'total_bobot' : 'jumlah_nilai')) !!}</td>
            <td class="colon">:</td>
            <td class="val">{{ $num($showBobot ? ($data['JumlahBobot'] ?? '-') : ($data['Jumlah'] ?? '-')) }}</td>
            <td class="empty"></td>
            <td class="empty"></td>
            <td class="empty"></td>
            <td class="pred-label" style="text-align:center;">{!! $text($label('predikat')) !!}</td>
            <td class="colon">:</td>
            <td class="pred-val">{!! $text($pred($data['Predikat'] ?? '-')) !!}</td>
        </tr>
        @if($showBobot)
            <tr>
                <td class="lbl" style="text-align:left; padding-left:16px;">{!! $text($label('nilai_tertimbang')) !!}</td>
                <td class="colon">:</td>
                <td class="val">{{ $num($data['JumlahTertimbang'] ?? '-') }}</td>
                <td class="empty" colspan="6"></td>
            </tr>
        @endif
        <tr>
            <td class="lbl" style="text-align:left; padding-left:16px;">{!! $text($label('rata_rata')) !!}</td>
            <td class="colon">:</td>
            <td class="val">{{ $num($data['Rata'] ?? '-') }}</td>
            <td class="empty" colspan="6"></td>
        </tr>
    </table>

    {{-- Catatan --}}
    <table class="catatan-box" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
        @if($isArabic)
            <tr>
                <td class="hdr">{!! $text($label('catatan_wali')) !!}</td>
                <td class="hdr lbl-col">{!! $text($label('catatan_kehadiran')) !!}</td>
            </tr>
            <tr>
                <td>
                    @if(! empty($data['CatatanWali']))
                        {!! \App\Support\ArabicText::toHtml($data['CatatanWali']) !!}
                    @elseif(! empty($data['CatatanPredikat']))
                        {!! \App\Support\ArabicText::toHtml($data['CatatanPredikat']) !!}
                    @else
                        <span style="color:#94a3b8;">—</span>
                    @endif
                    @if(filled($distributionNote ?? null))
                        <div class="catatan-umum">
                            <strong>{!! $text($label('catatan_umum')) !!}:</strong> {!! $text($distributionNote) !!}
                        </div>
                    @endif
                </td>
                <td class="lbl-col">
                    {!! $num(1) !!}.&nbsp; {!! $text($label('sakit')) !!} &nbsp;: {{ $num($data['Sakit'] ?? '-') }} {!! $text($label('hari')) !!}<br>
                    {!! $num(2) !!}.&nbsp; {!! $text($label('izin')) !!} &nbsp;&nbsp;: {{ $num($data['Izin'] ?? '-') }} {!! $text($label('hari')) !!}<br>
                    {!! $num(3) !!}.&nbsp; {!! $text($label('alpa')) !!} &nbsp;&nbsp;: {{ $num($data['Alpa'] ?? '-') }} {!! $text($label('hari')) !!}
                </td>
            </tr>
        @else
            <tr>
                <td class="hdr lbl-col">{!! $text($label('catatan_kehadiran')) !!}</td>
                <td class="hdr">{!! $text($label('catatan_wali')) !!}</td>
            </tr>
            <tr>
                <td class="lbl-col">
                    1.&nbsp; {!! $text($label('sakit')) !!} &nbsp;: {{ $num($data['Sakit'] ?? '-') }} {!! $text($label('hari')) !!}<br>
                    2.&nbsp; {!! $text($label('izin')) !!} &nbsp;&nbsp;: {{ $num($data['Izin'] ?? '-') }} {!! $text($label('hari')) !!}<br>
                    3.&nbsp; {!! $text($label('alpa')) !!} &nbsp;&nbsp;: {{ $num($data['Alpa'] ?? '-') }} {!! $text($label('hari')) !!}
                </td>
                <td>
                    @if(! empty($data['CatatanWali']))
                        {!! nl2br(e($data['CatatanWali'])) !!}
                    @elseif(! empty($data['CatatanPredikat']))
                        {!! nl2br(e($data['CatatanPredikat'])) !!}
                    @else
                        <span style="color:#94a3b8;">—</span>
                    @endif
                    @if(filled($distributionNote ?? null))
                        <div class="catatan-umum">
                            <strong>{!! $text($label('catatan_umum')) !!}:</strong> {!! $text($distributionNote) !!}
                        </div>
                    @endif
                </td>
            </tr>
        @endif
    </table>

    {{-- TTD: Orang Tua/Wali, Wali Kelas, Kepala Sekolah sejajar dalam satu baris --}}
    @php
        // Ruang tanda tangan mengikuti gambar yang benar-benar ada; bila belum
        // ada TTD/stempel, cukup 10mm agar tidak menyisakan ruang kosong.
        $sigAreaHeight = max(
            ! empty($ttdWaliBase64) ? (float) ($layout['ttd_wali_height'] ?? 0) : 0,
            ! empty($ttdKspBase64) ? (float) ($layout['ttd_ksp_height'] ?? 0) : 0,
            ! empty($stampBase64) ? (float) ($layout['stamp_height'] ?? 0) : 0,
        ) ?: 10.0;
        $waliNupy = $studyGroup->homeroomTeacher?->employment?->nupy
            ?? $studyGroup->homeroomTeacher?->gtkProfile?->nupy;
    @endphp
    <table class="ttd-grid" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
        <tr>
            <td colspan="3" class="ttd-date" style="text-align: {{ $isArabic ? 'center' : 'right' }};">
                @if($isArabic)
                    {!! $text($hijriDate) !!}، {!! $text($cityText($printCity)) !!}
                @else
                    {!! $text($cityText($printCity)) !!}, {{ $printDate->translatedFormat('d F Y') }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="ttd-col">
                <div class="ttd-title">{!! $text($label('orang_tua')) !!}</div>
                <div class="ttd-sig-area" style="height: {{ $sigAreaHeight }}mm;"></div>
                <div class="signature-line">____________________</div>
                <div class="ttd-name">&nbsp;</div>
            </td>
            <td class="ttd-col">
                <div class="ttd-title">{!! $text($label('wali_kelas')) !!}</div>
                <div class="ttd-sig-area" style="height: {{ $sigAreaHeight }}mm;">
                    @if(! ($hideTtdStamp ?? false) && ($layout['show_ttd_wali'] ?? true) && ! empty($ttdWaliBase64))
                        <img src="{{ $ttdWaliBase64 }}" class="signature-image"
                             style="width: {{ $layout['ttd_wali_width'] }}mm; height: {{ $layout['ttd_wali_height'] }}mm;"
                             alt="TTD Wali Kelas">
                    @endif
                </div>
                <div class="signature-line">____________________</div>
                <div class="ttd-name"><strong>{!! $text(($isArabic && ! empty($waliNameAr)) ? $waliNameAr : ($studyGroup->homeroomTeacher?->name ?? '................................')) !!}</strong></div>
                <div class="ttd-nip">{!! $text($label('nip')) !!} {{ $num($waliNupy ?? '...................') }}</div>
            </td>
            <td class="ttd-col">
                <div class="ttd-title">{!! $text($label('mengetahui_kepala')) !!}</div>
                <div class="ttd-sig-area" style="height: {{ $sigAreaHeight }}mm;">
                    <div class="signature-stack"
                         style="width: {{ max((float) $layout['ttd_ksp_width'], (float) $layout['stamp_width']) }}mm; height: {{ max((float) $layout['ttd_ksp_height'], (float) $layout['stamp_height']) }}mm;">
                        @if(! ($hideTtdStamp ?? false) && ($layout['show_stamp'] ?? true) && ! empty($stampBase64))
                            <img src="{{ $stampBase64 }}" class="stamp-image"
                                 style="width: {{ $layout['stamp_width'] }}mm; height: {{ $layout['stamp_height'] }}mm;"
                                 alt="Stempel Sekolah">
                        @endif
                        @if(! ($hideTtdStamp ?? false) && ($layout['show_ttd_ksp'] ?? true) && ! empty($ttdKspBase64))
                            <img src="{{ $ttdKspBase64 }}" class="ttd-image"
                                 style="width: {{ $layout['ttd_ksp_width'] }}mm; height: {{ $layout['ttd_ksp_height'] }}mm;"
                                 alt="TTD Kepala Sekolah">
                        @endif
                    </div>
                </div>
                <div class="signature-line">____________________</div>
                <div class="ttd-name"><strong>{!! $text(($isArabic && ! empty($kspNameAr)) ? $kspNameAr : ($principal['display_name'] ?? $studyGroup->school?->principal_full_name ?? '................................')) !!}</strong></div>
                <div class="ttd-nip">{!! $text($label('nip')) !!} {{ $num($principal['nupy'] ?? $studyGroup->school?->principal_nupy_display ?? '...................') }}</div>
            </td>
        </tr>
    </table>

    @if($layout['show_qr'] ?? true)
        @include('documents._qr-footer', ['verification' => $verificationByStudent[$key] ?? null])
    @endif

</div>
@endforeach

{{-- Footer bar (di luar page agar muncul di semua halaman) --}}
<div class="footer-bar">
    <table style="width:100%; border-collapse:collapse;">
    <tr>
        <td class="left">{{ ($layout['footer_left'] ?? null) ?: 'PAH-KSP-FORM-14-02' }}</td>
        <td class="center">{{ ($layout['footer_center'] ?? null) ?: 'REV.01 / 10 September 2025' }}</td>
        <td class="right">
            @if($isArabic)
                {!! $text($label('halaman')) !!} {{ $num(1) }} {!! $text($label('dari')) !!} {{ $num(1) }}
            @else
                Halaman 1 dari 1
            @endif
        </td>
    </tr>
    </table>
</div>

</body>
</html>
