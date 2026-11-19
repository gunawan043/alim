<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>QR Kelas — {{ $studyGroup->full_name ?? $studyGroup->name }}</title>
    <style>
        @page { size: A6 portrait; margin: 0; }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0;
            width: 105mm; height: 148mm;
        }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            color: #0f172a;
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
        }

        /* Area putih pada template: ± 12mm–93mm, 53mm–128mm */
        .qr {
            position: absolute;
            top: 54mm;
            left: 31.5mm;
            width: 42mm;
            height: 42mm;
        }
        .qr img { width: 42mm; height: 42mm; display: block; }

        .class-name {
            position: absolute;
            top: 97.5mm;
            left: 12mm;
            width: 81mm;
            text-align: center;
            font-size: 13pt;
            font-weight: 700;
            line-height: 1.15;
            color: #0f172a;
        }
        .school-name {
            position: absolute;
            top: 102.5mm;
            left: 12mm;
            width: 81mm;
            text-align: center;
            font-size: 7pt;
            color: #475569;
        }
        .meta {
            position: absolute;
            top: 106.5mm;
            left: 17mm;
            width: 71mm;
            border-collapse: collapse;
            font-size: 6.5pt;
            color: #334155;
        }
        .meta td { padding: .45mm 0; text-align: left; }
        .meta td:last-child { text-align: right; font-weight: 700; color: #0f172a; }
        .instruction {
            position: absolute;
            top: 115.2mm;
            left: 17mm;
            width: 71mm;
            text-align: center;
            font-size: 5.8pt;
            line-height: 1.3;
            color: #475569;
        }
        .instruction strong { color: #0b46f5; }
    </style>
</head>
<body>
<div class="page">
    <div class="qr">
        <img src="{{ $qrData }}" alt="QR Code {{ $studyGroup->full_name ?? $studyGroup->name }}">
    </div>

    <div class="class-name">{{ $studyGroup->full_name ?? $studyGroup->name }}</div>
    <div class="school-name">{{ $studyGroup->school?->name ?? '' }}</div>

    <table class="meta">
        <tr>
            <td>Wali Kelas</td>
            <td>{{ $studyGroup->homeroomTeacher?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td>Tahun Ajaran</td>
            <td>{{ $token->academicYear?->name ?? '—' }}</td>
        </tr>
    </table>

    <div class="instruction">
        Pindai QR melalui menu <strong>Scan QR Kehadiran</strong><br>
        untuk mencatat absensi masuk dan keluar kelas.
    </div>
</div>
</body>
</html>
