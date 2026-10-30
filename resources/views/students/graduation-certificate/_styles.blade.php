@php
    $paperCss = match ($setting->paper_size) {
        'folio' => '215mm 330mm',
        'letter' => '216mm 279mm',
        default => 'A4',
    };
    $paperWidth = match ($setting->paper_size) {
        'folio' => '215mm',
        'letter' => '216mm',
        default => '210mm',
    };
@endphp
<style>
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
        font-family: '{{ $setting->font_family }}', 'Times New Roman', Times, serif;
        font-size: {{ $setting->font_size }}pt;
        line-height: {{ $setting->line_height }};
        color: #000;
        background: #fff;
        width: {{ $paperWidth }};
    }

    .page {
        padding: {{ $setting->margin_top }}mm {{ $setting->margin_right }}mm {{ $setting->margin_bottom }}mm {{ $setting->margin_left }}mm;
    }

    .page-break {
        page-break-after: always;
    }

    .kop {
        text-align: center;
        border-bottom: 2px solid #000;
        padding-bottom: 6px;
        margin-bottom: 14px;
    }

    .kop .nama {
        font-size: 15pt;
        font-weight: bold;
        text-transform: uppercase;
    }

    .kop .alamat {
        font-size: 9pt;
    }

    .judul {
        text-align: center;
        font-size: 14pt;
        font-weight: bold;
        text-decoration: underline;
        letter-spacing: .5px;
    }

    .nomor {
        text-align: center;
        margin-bottom: 16px;
    }

    p {
        text-align: justify;
        margin-bottom: 8px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table.identitas td {
        vertical-align: top;
    }

    table.data td {
        padding: 2px 4px;
        vertical-align: top;
    }

    table.data td.label {
        width: 42%;
    }

    table.data td.pemisah {
        width: 3%;
    }

    .foto {
        text-align: center;
        padding-left: 10px;
    }

    .foto img {
        width: 30mm;
        height: 40mm;
        border: 1px solid #000;
    }

    .foto .kosong {
        width: 30mm;
        height: 40mm;
        border: 1px solid #000;
        display: inline-block;
    }

    table.nilai {
        margin-top: 8px;
        border: 1px solid #000;
    }

    table.nilai th,
    table.nilai td {
        border: 1px solid #000;
        padding: 3px 6px;
        vertical-align: middle;
    }

    table.nilai th {
        text-align: center;
        background: #eee;
    }

    table.nilai tr.grup td {
        font-weight: bold;
        background: #f5f5f5;
    }

    .tengah {
        text-align: center;
    }

    .kanan {
        text-align: right;
    }

    table.ttd {
        margin-top: 24px;
        width: 100%;
    }

    table.ttd td {
        vertical-align: top;
    }

    table.ttd td.kolom {
        width: 45%;
        text-align: center;
    }

    .ttd .ruang {
        height: 60px;
    }

    .ttd .nama {
        font-weight: bold;
        text-decoration: underline;
    }
</style>
