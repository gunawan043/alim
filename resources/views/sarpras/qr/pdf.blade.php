<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'QR Code Aset' }}</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 210mm;
            height: 297mm;
        }

        body {
            font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif;
            font-weight: bold;
            color: #ffffff;
        }

        .page {
            position: relative;
            width: 210mm;
            height: 297mm;

            background-image: url('{{ $backgroundData }}');
            background-size: 210mm 297mm;
            background-position: top left;
            background-repeat: no-repeat;

            overflow: hidden;
            page-break-after: always;
        }

        .page.last {
            page-break-after: auto;
        }

        .school-name {
            position: absolute;
            top: 32mm;
            left: 10mm;
            width: 190mm;

            text-align: center;
            text-transform: uppercase;
            font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif;
            font-size: 20pt;
            font-weight: bold;
            line-height: 1.15;
            letter-spacing: .4px;
            color: #e0e7ff;
        }

        .document-title {
            position: absolute;
            top: 44mm;
            left: 10mm;
            width: 190mm;

            text-align: center;
            text-transform: uppercase;
            white-space: nowrap;
            font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif;
            font-weight: bold;
            line-height: 1.05;
            letter-spacing: .5px;
            color: #ffffff;
        }

        .asset-name {
            position: absolute;
            top: 59mm;
            left: 10mm;
            width: 190mm;

            text-align: center;
            text-transform: uppercase;
            font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif;
            font-weight: bold;
            line-height: 1.1;
            color: #ffffff;
            word-wrap: break-word;
        }

        .asset-meta {
            position: absolute;
            top: 76mm;
            left: 10mm;
            width: 190mm;

            text-align: center;
            font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif;
            font-size: 12.5pt;
            font-weight: bold;
            line-height: 1.15;
        }

        .asset-meta-pill {
            display: inline-block;
            padding: 1.7mm 8mm;
            background-color: #ffffff;
            border: 0.4mm solid #dbeafe;
            border-radius: 4mm;
            white-space: nowrap;
            color: #0b2a7a;
        }

        .asset-meta .label {
            color: #1e40af;
        }

        .asset-meta .value {
            color: #0b2a7a;
        }

        .instruction {
            position: absolute;
            top: 89mm;
            left: 15mm;
            width: 180mm;

            text-align: center;
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 11.5pt;
            font-style: italic;
            font-weight: normal;
            line-height: 1.15;
            color: #e0e7ff;
        }

        .qr-panel {
            position: absolute;
            top: 104.7mm;
            left: 29mm;

            width: 152mm;
            height: 152mm;
        }

        .qr-panel img {
            display: block;
            width: 152mm;
            height: 152mm;
        }

        @media print {
            html,
            body {
                width: 210mm;
                height: auto;
                margin: 0;
                padding: 0;
            }

            .page {
                width: 210mm;
                height: 297mm;

                page-break-after: always;
                break-after: page;

                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .page.last {
                page-break-after: auto;
                break-after: auto;
            }
        }
    </style>
</head>
<body>
@foreach($labels as $label)
    @php
        $code = $label['code'] ?? '—';
        $name = $label['name'] ?? '—';
        $titleText = 'QR CODE ASET '.$code;
        $titleLength = strlen($titleText);
        $titleSize = $titleLength <= 22 ? 36 : ($titleLength <= 30 ? 30 : ($titleLength <= 38 ? 26 : 22));
        $nameText = strtoupper($name);
        $nameLength = strlen($nameText);
        $nameSize = $nameLength <= 30 ? 19 : ($nameLength <= 45 ? 16 : 14);
    @endphp

    <div class="page {{ $loop->last ? 'last' : '' }}">
        <div class="school-name">
            {{ $label['school'] ?? '' }}
        </div>

        <div class="document-title" style="font-size: {{ $titleSize }}pt;">
            {{ $titleText }}
        </div>

        <div class="asset-name" style="font-size: {{ $nameSize }}pt;">
            {{ $nameText }}
        </div>

        <div class="asset-meta">
            <span class="asset-meta-pill">
                <span class="label">RUANG</span> :
                <span class="value">{{ $label['room'] ?? '—' }}</span>
                &nbsp;|&nbsp;
                <span class="label">TAHUN</span> :
                <span class="value">{{ $label['year'] ?? '—' }}</span>
            </span>
        </div>

        <div class="instruction">
            Pindai QR untuk melihat detail aset<br>
            dan mencatat audit barang.
        </div>

        <div class="qr-panel">
            <img src="{{ $label['qr'] ?? '' }}" alt="QR Code Aset {{ $code }}">
        </div>
    </div>
@endforeach
</body>
</html>
