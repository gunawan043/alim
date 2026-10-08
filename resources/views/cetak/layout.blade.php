<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Dokumen')</title>
    <style>
        @page { margin: 13mm 10mm 16mm 10mm; }

        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #111;
            line-height: 1.35;
        }

        /* ── Kop identitas satuan pendidikan ─────────────────────── */
        .kop { border-bottom: 2.5px solid #000; padding-bottom: 6px; margin-bottom: 4px; }
        .kop-table { width: 100%; border-collapse: collapse; }
        .kop-table td { border: none; padding: 0 4px; vertical-align: middle; }
        .kop-logo { width: 60px; text-align: center; }
        .kop-logo img { width: 55px; }
        .kop-text { text-align: center; }
        .kop-text .nama { font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: .3px; }
        .kop-text .sub { font-size: 9.5px; color: #222; }
        .kop-text .npsn { font-size: 9px; color: #333; }

        .doc-title {
            text-align: center;
            font-size: 12.5px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0 2px;
            text-decoration: underline;
        }
        .doc-subtitle { text-align: center; font-size: 10px; margin-bottom: 10px; color: #333; }

        /* ── Tabel umum ──────────────────────────────────────────── */
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data th, table.data td { border: 1px solid #444; padding: 3px 5px; vertical-align: top; }
        table.data th { background: #ececec; font-size: 9.5px; text-align: center; }
        table.data td { font-size: 9.5px; }
        table.data tr { page-break-inside: avoid; }
        table.data td.center { text-align: center; }
        table.data td.right { text-align: right; }
        table.data tfoot td { background: #f5f5f5; font-weight: bold; }

        /* ── Tabel informasi (tanpa garis) ───────────────────────── */
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.info td { border: none; padding: 1.5px 4px; font-size: 10px; vertical-align: top; }
        table.info td.label { width: 150px; color: #333; }
        table.info td.colon { width: 10px; }

        .section-title {
            background: #e8e8e8;
            border: 1px solid #444;
            border-bottom: none;
            padding: 4px 6px;
            font-weight: bold;
            font-size: 10.5px;
            margin-top: 10px;
            text-transform: uppercase;
        }
        .section-body { border: 1px solid #444; padding: 6px 8px; margin-bottom: 0; }
        .section-body table.inside { width: 100%; border-collapse: collapse; }
        .section-body table.inside td { border: none; padding: 2px 4px; vertical-align: top; font-size: 9.5px; }
        .section-body table.inside td.label { width: 165px; font-weight: bold; }
        .section-body p { margin: 0 0 4px; }
        .section-body p:last-child { margin-bottom: 0; }

        /* ── Tanda tangan ────────────────────────────────────────── */
        table.sign { width: 100%; border-collapse: collapse; margin-top: 26px; page-break-inside: avoid; }
        table.sign td { border: none; text-align: center; font-size: 10px; vertical-align: bottom; padding: 0 8px; }
        table.sign .space { height: 62px; }
        table.sign .name { font-weight: bold; text-decoration: underline; }
        table.sign .nip { font-size: 9px; color: #333; }

        .muted { color: #555; }
        .small { font-size: 9px; }
        .summary-box { border: 1px solid #444; padding: 6px 8px; margin-bottom: 10px; font-size: 10px; }
        .summary-box strong { font-size: 11px; }
    </style>
</head>
<body>

@include('cetak._kop', ['school' => $school ?? null])

<div class="doc-title">@yield('doc_title', 'Dokumen')</div>
<div class="doc-subtitle">@yield('doc_subtitle', '')</div>

@yield('content')

</body>
</html>
