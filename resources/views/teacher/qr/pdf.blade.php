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
            top: 30mm;
            left: 15mm;
            width: 180mm;

            text-align: center;
            text-transform: uppercase;
            font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif;
            font-size: 14pt;
            font-weight: bold;
            line-height: 1.2;
            letter-spacing: .4px;
            color: #e0e7ff;
        }

        .header {
            position: absolute;
            top: 40mm;
            left: 10mm;
            width: 190mm;

            text-align: center;
        }

        .document-title {
            font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif;
            font-size: 19pt;
            font-weight: bold;
            line-height: 1.2;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #ffffff;
        }

        .class-name {
            margin-top: 2.5mm;

            font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif;
            font-size: 28pt;
            font-weight: bold;
            line-height: 1.05;
            text-transform: uppercase;
            color: #ffffff;
            word-wrap: break-word;
        }

        .meta {
            position: absolute;
            top: 72mm;
            left: 25mm;
            width: 160mm;

            border-collapse: collapse;
            font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif;
            font-size: 11.5pt;
            font-weight: bold;
            line-height: 1.3;
            color: #ffffff;
        }

        .meta td {
            padding: 1mm 0;
            vertical-align: middle;
        }

        .meta td:first-child {
            width: 42%;
            color: #dbeafe;
        }

        .meta td:nth-child(2) {
            width: 5%;
            text-align: center;
            color: #dbeafe;
        }

        .meta td:last-child {
            width: 53%;
            text-align: left;
        }

        .instruction {
            position: absolute;
            top: 90.5mm;
            left: 20mm;
            width: 170mm;

            text-align: center;
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            font-style: italic;
            font-weight: normal;
            line-height: 1.4;
            color: #e0e7ff;
        }

        /* Kotak putih template: x = 22,6–187,3 mm; y = 103,7–257,8 mm */
        .qr-panel {
            position: absolute;
            top: 114.7mm;
            left: 39mm;

            width: 132mm;
            height: 132mm;
        }

        .qr-panel img {
            display: block;
            width: 132mm;
            height: 132mm;
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
    @endphp

    <div class="page {{ $loop->last ? 'last' : '' }}">
        <div class="school-name">
            {{ $studyGroup->school?->name ?? '' }}
        </div>

        <div class="header">
            <div class="document-title">QR Code Kehadiran Kelas</div>
            <div class="class-name">{{ $className }}</div>
        </div>

        <table class="meta">
            <tr>
                <td>WALI KELAS</td>
                <td>:</td>
                <td>{{ $studyGroup->homeroomTeacher?->name ?? '—' }}</td>
            </tr>
            <tr>
                <td>TAHUN AJARAN</td>
                <td>:</td>
                <td>{{ $token->academicYear?->name ?? '—' }}</td>
            </tr>
        </table>

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
