<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'QR Kelas' }}</title>
    <style>
        @page { size: A6 portrait; margin: 0; }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0;
            width: 105mm; height: 148mm;
        }
        body {
            font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif;
            font-weight: bold;
            color: #ffffff;
        }
        .page {
            position: relative;
            width: 105mm;
            height: 148mm;
            background-image: url('{{ $backgroundData }}');
            background-size: 105mm 148mm;
            background-position: top left;
            background-repeat: no-repeat;
            overflow: hidden;
            page-break-after: always;
        }
        .page.last { page-break-after: auto; }

        /* Kotak putih pada template hanya untuk QR. */
        .qr-panel {
            position: absolute;
            top: 59.5mm;
            left: 23.5mm;
            width: 58mm;
            height: 58mm;
        }
        .qr-panel img { width: 58mm; height: 58mm; display: block; }

        /* Seluruh teks berada di area biru, di luar kotak putih. */
        .class-name {
            position: absolute;
            top: 15mm;
            left: 10mm;
            width: 85mm;
            text-align: center;
            font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif;
            font-size: 15pt;
            font-weight: bold;
            line-height: 1.08;
            text-transform: uppercase;
            color: #ffffff;
        }
        .school-name {
            position: absolute;
            top: 27mm;
            left: 10mm;
            width: 85mm;
            text-align: center;
            font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif;
            font-size: 8pt;
            font-weight: bold;
            line-height: 1.2;
            color: #e0e7ff;
        }
        .meta {
            position: absolute;
            top: 33mm;
            left: 12mm;
            width: 81mm;
            border-collapse: collapse;
            font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif;
            font-size: 7.4pt;
            font-weight: bold;
            color: #ffffff;
        }
        .meta td { padding: .35mm 0; vertical-align: top; }
        .meta td:first-child { width: 48%; color: #dbeafe; }
        .meta td:last-child { text-align: right; }

        /* Pengecualian: kalimat petunjuk tidak memakai Cambria Bold. */
        .instruction {
            position: absolute;
            top: 43mm;
            left: 12mm;
            width: 81mm;
            text-align: center;
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 6.3pt;
            font-style: italic;
            font-weight: normal;
            line-height: 1.3;
            color: #e0e7ff;
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
        <div class="qr-panel">
            <img src="{{ $qrData }}" alt="QR Code {{ $className }}">
        </div>

        <div class="class-name">{{ $className }}</div>
        <div class="school-name">{{ $studyGroup->school?->name ?? '' }}</div>

        <table class="meta">
            <tr>
                <td>WALI KELAS</td>
                <td>{{ $studyGroup->homeroomTeacher?->name ?? '—' }}</td>
            </tr>
            <tr>
                <td>TAHUN AJARAN</td>
                <td>{{ $token->academicYear?->name ?? '—' }}</td>
            </tr>
        </table>

        <div class="instruction">
            Pindai QR melalui menu Scan QR Kehadiran<br>
            untuk mencatat absensi masuk dan keluar kelas.
        </div>
    </div>
@endforeach
</body>
</html>
