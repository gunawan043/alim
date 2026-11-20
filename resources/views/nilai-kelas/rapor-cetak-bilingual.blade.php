@php
    /*
     * Rapor bilingual — satu lembar berisi dua bahasa.
     * Kolom kiri: Indonesia (LTR). Kolom kanan: Arab (RTL).
     *
     * Data berasal dari RaporPdfService::buildSantriData(), sehingga angka,
     * nama mapel, dan catatan tetap satu sumber dengan rapor satu bahasa.
     */
    $labelsId = $labelsId ?? [];
    $labelsAr = $labelsAr ?? [];
    $li = fn (string $key) => $labelsId[$key] ?? $key;
    $la = fn (string $key) => $labelsAr[$key] ?? $key;
    // Header Arab versi pendek agar tabel separuh halaman tidak melebar.
    $laShort = [
        'mapel' => 'المادة',
        'kktp' => 'الأدنى',
        'jumlah_bobot_nilai' => 'الجملة',
        'keterangan' => 'الحكم',
    ];

    $ar = fn (?string $value) => new \Illuminate\Support\HtmlString(
        \App\Support\ArabicText::toHtml((string) $value, false)
    );
    $arNum = fn ($value) => \App\Support\ArabicText::digits((string) $value);
    $ketAr = fn (?string $value) => [
        'Terlampaui' => 'متجاوز',
        'Tercapai' => 'محقق',
        'Belum Tuntas' => 'غير مكتمل',
    ][$value] ?? $value;
    $predAr = fn (?string $value) => [
        "Mumtaz Murtafi'" => 'ممتاز مرتفع',
        'Mumtaz' => 'ممتاز',
        'Jayyid Jiddan' => 'جيد جدًا',
        'Jayyid' => 'جيد',
        'Maqbul' => 'مقبول',
        'Roosib' => 'راسب',
    ][$value] ?? $value;
    $semesterAr = fn (?string $value) => strtolower((string) $value) === 'genap'
        ? $la('semester_genap')
        : $la('semester_ganjil');

    $layout = array_merge(\App\Models\RaporDistributionSetting::defaultLayout(), $layout ?? []);
    $paperSize = $layout['paper_size'] ?? 'a4';
    $paperCss = match ($paperSize) {
        'folio' => '215mm 330mm',
        'letter' => 'letter',
        default => 'A4',
    };
    $printDate = $distributionDate ?? now();
    $printCity = $distributionCity ?: ($studyGroup->school?->city?->display_name ?? 'Mataram');
    // Hijri::parse() mengubah locale objek tanggal yang dilewatkan, jadi setiap
    // format memakai salinan sendiri agar tanggal Masehi tidak ikut berubah.
    $printDateLatin = \Carbon\Carbon::parse($printDate)->locale('id')->translatedFormat('d F Y');
    $hijriDate = \App\Support\ArabicText::formatHijriDate(\Carbon\Carbon::parse($printDate))
        ?: \App\Support\ArabicText::formatDate(\Carbon\Carbon::parse($printDate));
    $hijriDateLatin = \App\Support\ArabicText::formatHijriDateLatin(\Carbon\Carbon::parse($printDate));
    $printDateAr = \App\Support\ArabicText::formatDate(\Carbon\Carbon::parse($printDate));
    $waliNupy = $studyGroup->homeroomTeacher?->employment?->nupy
        ?? $studyGroup->homeroomTeacher?->gtkProfile?->nupy;

    // Tinggi baris tabel nilai: 5.8pt dikonversi ke mm (1pt = 0.3528mm)
    // lalu dikali line-height + padding atas-bawah.
    $tableRowPad = (float) ($layout['table_row_padding'] ?? 0.5);
    $tableLineHeight = (float) ($layout['table_line_height'] ?? 1.05);
    $rowHeight = round(5.8 * 0.3528 * $tableLineHeight + 2 * $tableRowPad, 2);
    $borderWidth = (float) ($layout['table_border_width'] ?? 0.3);
    $boldHeader = (bool) ($layout['bold_table_header'] ?? true);
    $boldSubject = (bool) ($layout['bold_subject'] ?? true);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rapor Bilingual — {{ $santri[array_key_first($santri)]['Nama'] ?? '' }}</title>
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
            font-size: 8pt;
            line-height: 1.2;
            color: #000;
        }

        .ar {
            font-family: {!! \App\Services\Ljk\LjkPdfFonts::arabicStack() !!};
            direction: rtl;
            unicode-bidi: embed;
        }

        .page {
            padding: {{ $layout['margin_top'] }}mm {{ $layout['margin_right'] }}mm {{ $layout['margin_bottom'] }}mm {{ $layout['margin_left'] }}mm;
            position: relative;
        }

        /* Hanya antar-santri; halaman terakhir tidak boleh menambah halaman kosong. */
        .page-break {
            page-break-after: always;
        }

        .kop {
            display: block;
            width: 100%;
            object-fit: contain;
            margin-bottom: 1.4mm;
        }

        .kop-fallback {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 1.5mm;
            margin-bottom: 2mm;
        }

        .kop-fallback .school {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .kop-fallback .unit {
            font-size: 9pt;
            font-weight: bold;
        }

        .title {
            text-align: center;
            margin: 0.8mm 0 1.2mm;
            line-height: 1.2;
        }

        .title .ar-title {
            font-family: {!! \App\Services\Ljk\LjkPdfFonts::arabicStack() !!};
            direction: rtl;
            font-size: 11pt;
            font-weight: bold;
        }

        .title .id-title {
            font-size: 10.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        table.ident {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 1mm;
            font-size: 6.4pt;
            line-height: 1.05;
        }

        table.ident td {
            padding: 0.15mm 0;
            vertical-align: top;
        }

        table.ident td.lbl { width: 14%; }
        table.ident td.colon { width: 2%; text-align: center; }
        table.ident td.val { width: 33%; font-weight: bold; }

        table.ident td.lbl-ar {
            width: 14%;
            text-align: right;
            direction: rtl;
            font-family: {!! \App\Services\Ljk\LjkPdfFonts::arabicStack() !!};
        }

        table.ident td.val-ar {
            width: 33%;
            text-align: right;
            direction: rtl;
            font-family: {!! \App\Services\Ljk\LjkPdfFonts::arabicStack() !!};
            font-weight: bold;
        }

        /* Satu tabel memuat dua bahasa: kolom kiri Indonesia, kanan Arab.
           Dipisah satu kolom tipis (spacer) agar tidak ada garis ganda. */
        table.dual {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.dual td.spacer,
        table.dual th.spacer {
            border: none;
            padding: 0;
            width: 2%;
        }

        /* Sel angka Arab (٠١٢…) butuh font Arab, bukan Times. */
        table.dual td.num-ar {
            font-family: {!! \App\Services\Ljk\LjkPdfFonts::arabicStack() !!};
        }

        table.nilai {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 5.4pt;
            line-height: {{ $layout['table_line_height'] ?? 1.05 }};
        }

        table.nilai th,
        table.nilai td {
            border: {{ $borderWidth }}mm solid #000;
            padding: {{ $layout['table_row_padding'] ?? 0.5 }}mm 0.5mm;
            vertical-align: middle;
        }

        table.nilai th {
            text-align: center;
            font-weight: {{ $boldHeader ? 'bold' : 'normal' }};
            line-height: 1.05;
            padding: {{ ($layout['table_row_padding'] ?? 0.5) + 0.25 }}mm 0.5mm;
            height: 6.5mm;
        }

        /* Tinggi baris dihitung dari line-height & padding agar bisa diatur;
           dikunci eksplisit supaya kiri & kanan tetap sejajar. */
        table.nilai tbody td {
            height: {{ $rowHeight }}mm;
        }

        table.nilai tbody tr {
            height: {{ $rowHeight }}mm;
        }

        table.nilai td.center {
            text-align: center;
        }

        table.nilai td.subject {
            text-align: left;
            font-weight: {{ $boldSubject ? 'bold' : 'normal' }};
            word-wrap: break-word;
        }

        table.nilai td.arabic-cell {
            direction: rtl;
            text-align: right;
            font-family: {!! \App\Services\Ljk\LjkPdfFonts::arabicStack() !!};
            font-weight: {{ $boldSubject ? 'bold' : 'normal' }};
            line-height: 1.05;
        }

        table.nilai th.arabic-cell {
            text-align: center;
        }

        table.nilai tr.summary td {
            font-weight: bold;
            text-align: center;
        }

        table.nilai tr.summary td.summary-label {
            text-align: left;
        }

        table.nilai tr.summary td.summary-label-ar {
            direction: rtl;
            text-align: right;
            font-family: {!! \App\Services\Ljk\LjkPdfFonts::arabicStack() !!};
        }

        table.section {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 5.6pt;
            line-height: 1.05;
            margin-top: 0.5mm;
        }

        table.section th,
        table.section td {
            border: {{ $borderWidth }}mm solid #000;
            padding: 0.45mm 0.8mm;
            vertical-align: middle;
        }

        table.section th {
            text-align: center;
            font-weight: {{ $boldHeader ? 'bold' : 'normal' }};
        }

        table.section tbody td {
            height: 3.2mm;
        }

        table.section td.notes {
            vertical-align: top;
            line-height: 1.2;
            text-align: justify;
        }

        table.section td.catatan-umum-cell {
            font-size: 5.4pt;
            line-height: 1.15;
            padding: 0.5mm 0.8mm;
            text-align: left;
            vertical-align: middle;
        }

        table.section td.catatan-umum-cell .cu-label { font-weight: bold; }

        table.section td.catatan-umum-ar {
            direction: rtl;
            text-align: right;
            font-family: {!! \App\Services\Ljk\LjkPdfFonts::arabicStack() !!};
            font-size: 5.6pt;
        }

        table.section td.catatan-umum-ar .cu-latin {
            font-family: 'Times New Roman', Times, serif;
            font-size: 5.4pt;
        }

        table.section td.notes-ar {
            vertical-align: top;
            line-height: 1.2;
            direction: rtl;
            text-align: right;
            font-family: {!! \App\Services\Ljk\LjkPdfFonts::arabicStack() !!};
        }

        .issued {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 0.4mm;
            font-size: 6pt;
            line-height: 1.1;
        }

        .issued > tbody > tr > td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        .issued td.issued-id { text-align: left; }

        .issued td.issued-ar {
            text-align: right;
            font-family: {!! \App\Services\Ljk\LjkPdfFonts::arabicStack() !!};
        }

        .issued-inner {
            border-collapse: collapse;
            width: 100%;
        }

        .issued-inner td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .issued-inner td.lbl { white-space: nowrap; width: 1%; }
        .issued-inner td.sep { width: 1%; padding: 0 1mm; }

        .issued .issued-sub { padding-right: 12mm; }
        .issued td.issued-ar .issued-sub { display: inline-block; padding-right: 12mm; }

        .num-ar {
            font-family: {!! \App\Services\Ljk\LjkPdfFonts::arabicStack() !!};
        }

        .approval {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 0.5mm;
            font-size: 6.2pt;
            line-height: 1.15;
        }
        .approval td {
            width: 33.33%;
            vertical-align: top;
            padding: 0.8mm;
            text-align: center;
        }

        .approval .role {
            font-weight: bold;
            line-height: 1.3;
        }

        .approval .role-ar {
            font-family: {!! \App\Services\Ljk\LjkPdfFonts::arabicStack() !!};
            direction: rtl;
            line-height: 1.3;
        }

        .sig-area {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            position: relative;
        }

        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            line-height: 1.2;
        }

        .sig-detail {
            font-size: 6pt;
            line-height: 1.2;
        }

        .footer-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #000;
            padding: 2px {{ $layout['margin_right'] }}mm 2px {{ $layout['margin_left'] }}mm;
            font-size: {{ max(5.5, (float) $layout['footer_font_size']) }}pt;
        }

        .footer-bar table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-bar td {
            border: none;
        }

        .footer-bar td.center { text-align: center; }
        .footer-bar td.right { text-align: right; }
    </style>
</head>
<body>

@foreach($santri as $key => $data)
    @php
        $isSas = ($data['Jenis'] ?? 'sts') === 'sas';
        $sick = $data['Sakit'] ?? 0;
        $izin = $data['Izin'] ?? 0;
        $alpa = $data['Alpa'] ?? 0;
    @endphp
    <div class="page{{ $loop->last ? '' : ' page-break' }}">

        {{-- Kop surat --}}
        @if(($layout['show_kop'] ?? true) && $kopBase64)
            <img src="{{ $kopBase64 }}" class="kop" style="height: {{ $layout['kop_height'] }}mm;" alt="Kop Surat">
        @else
            <div class="kop-fallback">
                <div class="school">{{ $studyGroup->school?->name ?? 'PONPES ABU HURAIRAH MATARAM' }}</div>
                <div class="unit">{{ $studyGroup->school?->level_text ?? '' }}</div>
            </div>
        @endif

        {{-- Judul dua bahasa --}}
        <div class="title">
            <div class="ar-title">{!! $ar($la($isSas ? 'title_sas' : 'title_sts')) !!}</div>
            <div class="id-title">{{ $li($isSas ? 'title_sas' : 'title_sts') }}</div>
        </div>

        {{-- Identitas: satu tabel, kiri Indonesia, kanan Arab --}}
        <table class="ident dual">
            <tbody>
                <tr>
                    <td class="lbl">{{ $li('nama') }}</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $data['Nama'] ?? '—' }}</td>
                    <td class="spacer"></td>
                    <td class="val-ar">{!! $ar($data['NamaAr'] ?? $data['Nama'] ?? '—') !!}</td>
                    <td class="colon">:</td>
                    <td class="lbl-ar">{!! $ar($la('nama')) !!}</td>
                </tr>
                <tr>
                    <td class="lbl">{{ $li('kelas') }}</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $data['Kelas'] ?? '—' }}</td>
                    <td class="spacer"></td>
                    <td class="val-ar">{!! $ar($data['KelasAr'] ?? $data['Kelas'] ?? '—') !!}</td>
                    <td class="colon">:</td>
                    <td class="lbl-ar">{!! $ar($la('kelas')) !!}</td>
                </tr>
                <tr>
                    <td class="lbl">{{ $li('nis_nisn') }}</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $data['NIS'] ?? '—' }} / {{ $data['NISN'] ?? '—' }}</td>
                    <td class="spacer"></td>
                    <td class="val-ar">{{ $arNum($data['NIS'] ?? '—') }} / {{ $arNum($data['NISN'] ?? '—') }}</td>
                    <td class="colon">:</td>
                    <td class="lbl-ar">{!! $ar($la('nis_nisn')) !!}</td>
                </tr>
                <tr>
                    <td class="lbl">{{ $li('semester') }}</td>
                    <td class="colon">:</td>
                    <td class="val">{{ ucfirst((string) ($data['Semester'] ?? '—')) }}</td>
                    <td class="spacer"></td>
                    <td class="val-ar">{!! $ar($semesterAr($data['Semester'] ?? '')) !!}</td>
                    <td class="colon">:</td>
                    <td class="lbl-ar">{!! $ar($la('semester')) !!}</td>
                </tr>
                <tr>
                    <td class="lbl">{{ $li('tahun_ajaran') }}</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $data['TahunAjaran'] ?? '—' }}</td>
                    <td class="spacer"></td>
                    <td class="val-ar">{{ $arNum($data['TahunAjaran'] ?? '—') }}</td>
                    <td class="colon">:</td>
                    <td class="lbl-ar">{!! $ar($la('tahun_ajaran')) !!}</td>
                </tr>
            </tbody>
        </table>

        {{-- Tabel nilai: satu tabel, separuh kiri Indonesia, separuh kanan Arab.
             Digabung agar baris kedua bahasa pasti sejajar. --}}
        @php
            $wn = $isSas
                ? ['no' => 2.5, 'mapel' => 15.5, 'kktp' => 4, 'bobot' => 4, 'nilai' => 4, 'jumlah' => 6.5, 'ket' => 12.5]
                : ['no' => 2.5, 'mapel' => 20.5, 'kktp' => 4.5, 'nilai' => 5, 'ket' => 16.5];
            $w = fn (string $k) => $wn[$k] . '%';
            $halfSpan = $isSas ? 7 : 5;
        @endphp
        <table class="nilai dual">
            <thead>
                <tr>
                    <th style="width:{{ $w('no') }}">{{ $li('no') }}</th>
                    <th style="width:{{ $w('mapel') }}">{{ $li('mapel') }}</th>
                    <th style="width:{{ $w('kktp') }}">{{ $li('kktp') }}</th>
                    @if($isSas)
                        <th style="width:{{ $w('bobot') }}">{{ $li('bobot') }}</th>
                        <th style="width:{{ $w('nilai') }}">{{ $li('nilai') }}</th>
                        <th style="width:{{ $w('jumlah') }}">{{ $li('jumlah_bobot_nilai') }}</th>
                    @else
                        <th style="width:{{ $w('nilai') }}">{{ $li('nilai') }}</th>
                    @endif
                    <th style="width:{{ $w('ket') }}">{{ $li('keterangan') }}</th>

                    <th class="spacer"></th>

                    <th style="width:{{ $w('ket') }}">{!! $ar($laShort['keterangan']) !!}</th>
                    @if($isSas)
                        <th style="width:{{ $w('jumlah') }}">{!! $ar($laShort['jumlah_bobot_nilai']) !!}</th>
                        <th style="width:{{ $w('nilai') }}">{!! $ar($la('nilai')) !!}</th>
                        <th style="width:{{ $w('bobot') }}">{!! $ar($la('bobot')) !!}</th>
                    @else
                        <th style="width:{{ $w('nilai') }}">{!! $ar($la('nilai')) !!}</th>
                    @endif
                    <th style="width:{{ $w('kktp') }}">{!! $ar($laShort['kktp']) !!}</th>
                    <th style="width:{{ $w('mapel') }}">{!! $ar($laShort['mapel']) !!}</th>
                    <th style="width:{{ $w('no') }}">{!! $ar($la('no')) !!}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['Mapel'] as $row)
                    <tr>
                        <td class="center">{{ $row['no'] }}</td>
                        <td class="subject">{{ $row['mapel'] }}</td>
                        <td class="center">{{ $row['kkm'] }}</td>
                        @if($isSas)
                            <td class="center">{{ $row['bobot'] ?? '' }}</td>
                            <td class="center">{{ $row['nilai'] }}</td>
                            <td class="center">{{ $row['jumlah'] ?? '' }}</td>
                        @else
                            <td class="center">{{ $row['nilai'] }}</td>
                        @endif
                        <td class="center">{{ $row['keterangan'] }}</td>

                        <td class="spacer"></td>

                        <td class="arabic-cell">{!! $ar($ketAr($row['keterangan'])) !!}</td>
                        @if($isSas)
                            <td class="center num-ar">{{ $arNum($row['jumlah'] ?? '') }}</td>
                            <td class="center num-ar">{{ $arNum($row['nilai']) }}</td>
                            <td class="center num-ar">{{ $arNum($row['bobot'] ?? '') }}</td>
                        @else
                            <td class="center num-ar">{{ $arNum($row['nilai']) }}</td>
                        @endif
                        <td class="center num-ar">{{ $arNum($row['kkm']) }}</td>
                        <td class="arabic-cell">{!! $ar($row['mapel_ar'] ?? $row['mapel']) !!}</td>
                        <td class="center num-ar">{{ $arNum($row['no']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $halfSpan }}" class="center">Belum ada data nilai</td>
                        <td class="spacer"></td>
                        <td colspan="{{ $halfSpan }}" class="center">لا توجد بيانات</td>
                    </tr>
                @endforelse

                @if($isSas)
                    <tr class="summary">
                        <td colspan="3" class="summary-label">{{ $li('total_bobot') }}</td>
                        <td>{{ $data['JumlahBobot'] ?? '-' }}</td>
                        <td colspan="2">{{ $data['JumlahTertimbang'] ?? '-' }}</td>
                        <td></td>
                        <td class="spacer"></td>
                        <td></td>
                        <td colspan="2" class="num-ar">{{ $arNum($data['JumlahTertimbang'] ?? '-') }}</td>
                        <td class="num-ar">{{ $arNum($data['JumlahBobot'] ?? '-') }}</td>
                        <td colspan="3" class="summary-label-ar">{!! $ar($la('total_bobot')) !!}</td>
                    </tr>
                @else
                    <tr class="summary">
                        <td colspan="3" class="summary-label">{{ $li('jumlah_nilai') }}</td>
                        <td>{{ $data['Jumlah'] ?? '-' }}</td>
                        <td></td>
                        <td class="spacer"></td>
                        <td></td>
                        <td class="num-ar">{{ $arNum($data['Jumlah'] ?? '-') }}</td>
                        <td colspan="3" class="summary-label-ar">{!! $ar($la('jumlah_nilai')) !!}</td>
                    </tr>
                @endif
                <tr class="summary">
                    <td colspan="3" class="summary-label">{{ $li('rata_rata') }}</td>
                    <td colspan="{{ $isSas ? 4 : 2 }}">{{ $data['Rata'] ?? '-' }}</td>
                    <td class="spacer"></td>
                    <td colspan="{{ $isSas ? 4 : 2 }}" class="num-ar">{{ $arNum($data['Rata'] ?? '-') }}</td>
                    <td colspan="3" class="summary-label-ar">{!! $ar($la('rata_rata')) !!}</td>
                </tr>
                <tr class="summary">
                    <td colspan="3" class="summary-label">{{ $li('predikat') }}</td>
                    <td colspan="{{ $isSas ? 4 : 2 }}">{{ $data['Predikat'] ?? '—' }}</td>
                    <td class="spacer"></td>
                    <td colspan="{{ $isSas ? 4 : 2 }}" class="arabic-cell">{!! $ar($predAr($data['Predikat'] ?? '—')) !!}</td>
                    <td colspan="3" class="summary-label-ar">{!! $ar($la('predikat')) !!}</td>
                </tr>
            </tbody>
        </table>

        {{-- Kehadiran & catatan: satu tabel, kiri Indonesia, kanan Arab --}}
        <table class="section dual">
            <thead>
                <tr>
                    <th style="width:15%">{{ $li('catatan_kehadiran') }}</th>
                    <th style="width:10%">{{ $li('hari') }}</th>
                    <th style="width:23.5%">{{ $li('catatan_wali') }}</th>
                    <th class="spacer"></th>
                    <th style="width:23.5%">{!! $ar($la('catatan_wali')) !!}</th>
                    <th style="width:10%">{!! $ar($la('hari')) !!}</th>
                    <th style="width:15%">{!! $ar($la('catatan_kehadiran')) !!}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="center">1. {{ $li('sakit') }}</td>
                    <td class="center">{{ $sick }}</td>
                    <td rowspan="3" class="notes">
                        @if(filled($data['CatatanWali'] ?? null))
                            {{ $data['CatatanWali'] }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="spacer" rowspan="3"></td>
                    <td rowspan="3" class="notes-ar">
                        @if(! empty($data['CatatanWali']))
                            {!! \App\Support\ArabicText::toHtml($data['CatatanWali']) !!}
                        @else
                            —
                        @endif
                    </td>
                    <td class="center num-ar">{{ $arNum($sick) }}</td>
                    <td class="center">{!! $ar($la('sakit')) !!}</td>
                </tr>
                <tr>
                    <td class="center">2. {{ $li('izin') }}</td>
                    <td class="center">{{ $izin }}</td>
                    <td class="center num-ar">{{ $arNum($izin) }}</td>
                    <td class="center">{!! $ar($la('izin')) !!}</td>
                </tr>
                <tr>
                    <td class="center">3. {{ $li('alpa') }}</td>
                    <td class="center">{{ $alpa }}</td>
                    <td class="center num-ar">{{ $arNum($alpa) }}</td>
                    <td class="center">{!! $ar($la('alpa')) !!}</td>
                </tr>
                @if(filled($distributionNote ?? null))
                    <tr>
                        <td colspan="3" class="catatan-umum-cell">
                            <span class="cu-label">{{ $li('catatan_umum') }}:</span> {{ $distributionNote }}
                        </td>
                        <td class="spacer"></td>
                        <td colspan="3" class="catatan-umum-cell catatan-umum-ar">
                            <span class="cu-label">{!! $ar($la('catatan_umum')) !!}:</span>
                            {{-- Catatan ditulis dalam bahasa Indonesia; tampilkan apa adanya
                                 (bukan di-shaping Arab) agar tetap terbaca. --}}
                            <span class="cu-latin">{{ $distributionNote }}</span>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- Diberikan di & tanggal: diletakkan sebelum blok tanda tangan --}}
        <table class="issued">
            <tr>
                <td class="issued-id">
                    <table class="issued-inner">
                        <tr>
                            <td class="lbl">{{ $li('diberikan_di') }}</td>
                            <td class="sep">:</td>
                            <td>{{ $printCity }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">{{ $li('tanggal') }}</td>
                            <td class="sep">:</td>
                            <td>
                                {{ $printDateLatin }} M<br>
                                {{ $hijriDateLatin }}
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="issued-ar">
                    <div>{!! $ar($la('diberikan_di').' : '.\App\Support\ArabicText::cityName($printCity)) !!}</div>
                    <div>{!! $ar($la('tanggal').' : '.$printDateAr.' مـ') !!}</div>
                    <div class="issued-sub">{!! $ar($hijriDate) !!}</div>
                </td>
            </tr>
        </table>

        {{-- Pengesahan --}}
        @php
            // Ruang tanda tangan mengikuti gambar yang benar-benar ada; bila
            // belum ada TTD/stempel, cukup 10mm agar tidak menyisakan ruang kosong.
            $sigAreaHeight = min(16.0, max(
                ! empty($ttdWaliBase64) ? (float) ($layout['ttd_wali_height'] ?? 0) : 0,
                ! empty($ttdKspBase64) ? (float) ($layout['ttd_ksp_height'] ?? 0) : 0,
                ! empty($stampBase64) ? (float) ($layout['stamp_height'] ?? 0) : 0,
            )) ?: 9.0;
        @endphp
        <table class="approval">
            <tr>
                <td>
                    <div class="role">{{ $li('orang_tua') }}</div>
                    <div class="role-ar">{!! $ar($la('orang_tua')) !!}</div>
                    <div class="sig-area" style="height: {{ $sigAreaHeight }}mm;"></div>
                    <div class="sig-name">____________________</div>
                    <div class="sig-detail">&nbsp;</div>
                </td>
                <td>
                    <div class="role">{{ $li('wali_kelas') }}</div>
                    <div class="role-ar">{!! $ar($la('wali_kelas')) !!}</div>
                    <div class="sig-area" style="height: {{ $sigAreaHeight }}mm;">
                        @if(! ($hideTtdStamp ?? false) && ($layout['show_ttd_wali'] ?? true) && ! empty($ttdWaliBase64))
                            <img src="{{ $ttdWaliBase64 }}"
                                 style="width: {{ $layout['ttd_wali_width'] }}mm; height: {{ $layout['ttd_wali_height'] }}mm; object-fit: contain;"
                                 alt="TTD Wali Kelas">
                        @endif
                    </div>
                    <div class="sig-name">{{ $studyGroup->homeroomTeacher?->name ?? '................................' }}</div>
                    <div class="sig-detail">{{ $li('nip') }} {{ $waliNupy ?? '...................' }}</div>
                </td>
                <td>
                    <div class="role">{{ $li('mengetahui_kepala') }}</div>
                    <div class="role-ar">{!! $ar($la('mengetahui_kepala')) !!}</div>
                    <div class="sig-area" style="height: {{ $sigAreaHeight }}mm;">
                        {{-- Wrapper ini sengaja absolute agar tinggi stempel/TTD
                             tidak menambah tinggi baris (sig-area sudah menyediakan ruang). --}}
                        <div style="position: absolute; left: 0; top: 0; width: 100%; height: 100%;">
                            @if(! ($hideTtdStamp ?? false) && ($layout['show_stamp'] ?? true) && ! empty($stampBase64))
                                <img src="{{ $stampBase64 }}"
                                     style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:{{ $layout['stamp_width'] }}mm;height:{{ $layout['stamp_height'] }}mm;object-fit:contain;opacity:.9;"
                                     alt="Stempel Sekolah">
                            @endif
                            @if(! ($hideTtdStamp ?? false) && ($layout['show_ttd_ksp'] ?? true) && ! empty($ttdKspBase64))
                                <img src="{{ $ttdKspBase64 }}"
                                     style="position:absolute;left:50%;bottom:0;transform:translateX(-50%);width:{{ $layout['ttd_ksp_width'] }}mm;height:{{ $layout['ttd_ksp_height'] }}mm;object-fit:contain;"
                                     alt="TTD Kepala Sekolah">
                            @endif
                        </div>
                    </div>
                    <div class="sig-name">{{ $principal['display_name'] ?? $studyGroup->school?->principal_full_name ?? '................................' }}</div>
                    <div class="sig-detail">{{ $li('nip') }} {{ $principal['nupy'] ?? $studyGroup->school?->principal_nupy_display ?? '...................' }}</div>
                </td>
            </tr>
        </table>

        {{-- Pengesahan --}}
        @if($layout['show_qr'] ?? false)
            @include('documents._qr-footer', ['verification' => $verificationByStudent[$key] ?? null])
        @endif
    </div>
@endforeach

<div class="footer-bar">
    <table>
        <tr>
            <td>{{ ($layout['footer_left'] ?? null) ?: 'PAH-KSP-FORM-14-04' }}</td>
            <td class="center">{{ ($layout['footer_center'] ?? null) ?: 'REV.01 / 10 September 2025' }}</td>
            <td class="right">Halaman 1 dari 1</td>
        </tr>
    </table>
</div>

</body>
</html>
