<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'QR Code Kehadiran Kelas' }}</title>

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

        /* Area biru di atas kotak putih: y = 0–103,6 mm */
        .school-name {
            position: absolute;
            top: 29mm;
            left: 10mm;
            width: 190mm;

            text-align: center;
            text-transform: uppercase;
            font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif;
            font-size: 14pt;
            font-weight: bold;
            line-height: 1.2;
            letter-spacing: .4px;
            color: #e0e7ff;
        }

        .document-title {
            position: absolute;
            top: 42mm;
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

        .academic-year {
            position: absolute;
            top: 68mm;
            left: 20mm;
            width: 170mm;

            text-align: center;
            font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif;
            font-size: 14pt;
            font-weight: bold;
            line-height: 1.2;
            color: #ffffff;
        }

        .academic-year .label {
            color: #dbeafe;
        }

        .instruction {
            position: absolute;
            top: 84mm;
            left: 15mm;
            width: 180mm;

            text-align: center;
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 12pt;
            font-style: italic;
            font-weight: normal;
            line-height: 1.15;
            color: #e0e7ff;
        }

        .instruction strong {
            font-weight: bold;
            color: #ffffff;
        }

        /* Kotak putih template: x = 22,6–187,3 mm; y = 103,7–257,8 mm */
        .qr-panel {
            position: absolute;
            top: 108.2mm;
            left: 32.5mm;

            width: 145mm;
            height: 145mm;
        }

        .qr-panel img {
            display: block;
            width: 145mm;
            height: 145mm;
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
@foreach($cards as $card)
    @php
        $studyGroup = $card['studyGroup'];
        $token = $card['token'];
        $qrData = $card['qrData'];
        $className = strtoupper($studyGroup->full_name ?? $studyGroup->name);
        $titleText = 'QR CODE KELAS '.$className;
        $titleLength = strlen($titleText);
        $titleSize = $titleLength <= 20 ? 36 : ($titleLength <= 28 ? 30 : ($titleLength <= 36 ? 26 : 22));
    @endphp

    <div class="page {{ $loop->last ? 'last' : '' }}">
        <div class="school-name">
            {{ $studyGroup->school?->name ?? '' }}
        </div>

        <div class="document-title" style="font-size: {{ $titleSize }}pt;">
            {{ $titleText }}
        </div>

        <div class="academic-year">
            <span class="label">TAHUN AJARAN</span> :
            <span class="value">{{ $token->academicYear?->name ?? '—' }}</span>
        </div>

        <div class="instruction">
            Pindai QR melalui menu <strong>Scan QR Kehadiran</strong><br>
            untuk mencatat absensi masuk dan keluar kelas.
        </div>

        <div class="qr-panel">
            <img src="{{ $qrData }}" alt="QR Code Kehadiran Kelas {{ $className }}">
        </div>
    </div>
@endforeach
</body>
</html>
