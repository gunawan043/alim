@php
    $layout = $layout ?? [];
    $pageMarginTop = (float) ($layout['margin_top'] ?? 8);
    $pageMarginRight = (float) ($layout['margin_right'] ?? 10);
    $pageMarginBottom = (float) ($layout['margin_bottom'] ?? 14);
    $pageMarginLeft = (float) ($layout['margin_left'] ?? 10);
    $bodyFontSize = (float) ($layout['font_size'] ?? 11);
    $titleFontSize = (float) ($layout['title_font_size'] ?? 13);
    $tableFontSize = (float) ($layout['table_font_size'] ?? 11);
    $bodyLineHeight = (float) ($layout['line_height'] ?? 1.15);
    $showKop = (bool) ($layout['show_kop'] ?? true);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Dokumen')</title>
    <style>
        @page { margin: {{ $pageMarginTop }}mm {{ $pageMarginRight }}mm {{ $pageMarginBottom }}mm {{ $pageMarginLeft }}mm; }

        * { box-sizing: border-box; }

        body {
            font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif;
            font-size: {{ $bodyFontSize }}pt;
            color: #111;
            line-height: {{ $bodyLineHeight }};
        }

        /* ── Kop identitas satuan pendidikan ─────────────────────── */
        .kop { border-bottom: 2.5px solid #000; padding-bottom: 6px; margin-bottom: 4px; }
        .kop-table { width: 100%; border-collapse: collapse; }
        .kop-table td { border: none; padding: 0 4px; vertical-align: middle; }
        .kop-logo { width: 60px; text-align: center; }
        .kop-logo img { width: 55px; }
        .kop-image { display: block; width: 100%; max-height: 34mm; object-fit: contain; }
        .kop-text { text-align: center; }
        .kop-text .nama { font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: .3px; }
        .kop-text .sub { font-size: 10.5px; color: #222; }
        .kop-text .npsn { font-size: 10px; color: #333; }

        .doc-title {
            text-align: center;
            font-size: {{ $titleFontSize }}pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 6px 0 2px;
            text-decoration: underline;
        }
        .doc-subtitle { text-align: center; font-size: 11px; margin-bottom: 10px; color: #333; }

        /* ── Tabel umum ──────────────────────────────────────────── */
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data th, table.data td { border: 1px solid #444; padding: 3px 5px; vertical-align: top; font-size: {{ $tableFontSize }}pt; }
        table.data th { background: #ececec; font-size: {{ $tableFontSize }}pt; text-align: center; }
        table.data td { font-size: {{ $tableFontSize }}pt; }
        table.data tr { page-break-inside: avoid; }
        table.data td.center { text-align: center; }
        table.data td.right { text-align: right; }
        table.data tfoot td { background: #f5f5f5; font-weight: bold; }

        /* ── Tabel informasi (tanpa garis) ───────────────────────── */
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.info td { border: none; padding: 1.5px 4px; font-size: 11pt; vertical-align: top; }
        table.info td.label { width: 150px; color: #333; }
        table.info td.colon { width: 10px; }

        .section-title {
            background: #e8e8e8;
            border: 1px solid #444;
            border-bottom: none;
            padding: 4px 6px;
            font-weight: bold;
            font-size: 11pt;
            margin-top: 10px;
            text-transform: uppercase;
        }
        .section-body { border: 0; padding: 2px 0; margin-bottom: 0; }
        .section-body table.inside { width: 100%; border-collapse: collapse; }
        .section-body table.inside td { border: none; padding: 2px 4px; vertical-align: top; font-size: 11pt; }
        .section-body table.inside td.label { width: 165px; font-weight: bold; }
        .section-body p { margin: 0 0 4px; }
        .section-body p:last-child { margin-bottom: 0; }

        /* ── Tanda tangan ────────────────────────────────────────── */
        table.sign { width: 100%; border-collapse: collapse; margin-top: 26px; page-break-inside: avoid; }
        table.sign td { border: none; text-align: center; font-size: 11pt; vertical-align: bottom; padding: 0 8px; }
        table.sign .space { height: 62px; }
        table.sign .name { font-weight: bold; text-decoration: underline; }
        table.sign .nip { font-size: 9px; color: #333; }
        .signature-box { position: relative; display: inline-block; width: 42mm; height: 24mm; margin: 4mm auto 0; }
        .signature-box .stamp-img { position: absolute; left: 0; top: 0; width: 26mm; height: 26mm; object-fit: contain; opacity: .85; }
        .signature-box .ttd-img { position: absolute; left: 6mm; top: 2mm; width: 34mm; height: 18mm; object-fit: contain; }

        .muted { color: #555; }
        .small { font-size: 10px; }
        .summary-box { border: 1px solid #444; padding: 6px 8px; margin-bottom: 10px; font-size: 10px; }
        .summary-box strong { font-size: 11px; }
    </style>
</head>
<body>

@if($showKop)
    @include('cetak._kop', ['school' => $school ?? null])
@endif

<div class="doc-title">@yield('doc_title', 'Dokumen')</div>
<div class="doc-subtitle">@yield('doc_subtitle', '')</div>

@yield('content')

</body>
</html>
