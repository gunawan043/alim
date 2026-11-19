
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

        html, body {
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

        /* =====================================
           HALAMAN A4
        ===================================== */

        .page {
            position: relative;
            width: 210mm;
            height: 297mm;

            background-image: url('{{ $backgroundData }}');
            background-size: 210mm 297mm;
            background-position: center;
            background-repeat: no-repeat;

            overflow: hidden;
            page-break-after: always;
        }

        .page.last {
            page-break-after: auto;
        }

        /* =====================================
           JUDUL QR CODE
        ===================================== */

        .header {
            position: absolute;
            top: 20mm;
            left: 15mm;
            width: 180mm;

            text-align: center;
        }

        .document-title {
            font-size: 22pt;
            font-weight: bold;
            line-height: 1.3;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #ffffff;
        }

        /* =====================================
           IDENTITAS SEKOLAH
        ===================================== */

        .school-name {
            position: absolute;
            top: 42mm;
            left: 20mm;
            width: 170mm;

            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            line-height: 1.3;
            color: #e0e7ff;
        }

        /* =====================================
           INFORMASI ADMINISTRASI KELAS
        ===================================== */

        .meta {
            position: absolute;
            top: 58mm;
            left: 42mm;
            width: 126mm;

            border-collapse: collapse;
            font-size: 11pt;
            font-weight: bold;
            color: #ffffff;
        }

        .meta td {
            padding: 2mm 0;
            vertical-align: middle;
        }

        .meta td:first-child {
            width: 42%;
            color: #dbeafe;
        }

        .meta td:nth-child(2) {
            width: 5%;
            text-align: center;
        }

        .meta td:last-child {
            width: 53%;
            text-align: left;
        }

        /* =====================================
           PETUNJUK PENGGUNAAN
        ===================================== */

        .instruction {
            position: absolute;
            top: 84mm;
            left: 30mm;
            width: 150mm;

            text-align: center;
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 9pt;
            font-weight: normal;
            line-height: 1.5;
            color: #e0e7ff;
        }

        .instruction strong {
            font-weight: bold;
            color: #ffffff;
        }

        /* =====================================
           QR CODE
           Ukuran keseluruhan: 150 x 150 mm
        ===================================== */

        .qr-panel {
            position: absolute;
            top: 105mm;
            left: 30mm;

            width: 150mm;
            height: 150mm;

            padding: 3mm;
            background: #ffffff;
            border-radius: 3mm;
        }

        .qr-panel img {
            display: block;
            width: 144mm;
            height: 144mm;
        }

        /* =====================================
           KETERANGAN QR CODE
        ===================================== */

        .scan-caption {
            position: absolute;
            top: 258mm;
            left: 20mm;
            width: 170mm;

            text-align: center;
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 9pt;
            font-weight: bold;
            line-height: 1.5;
            color: #ffffff;
        }

        /* =====================================
           FOOTER ALIM
        ===================================== */

        .footer {
            position: absolute;
            bottom: 8mm;
            left: 20mm;
            width: 170mm;

            text-align: center;
            color: #ffffff;
        }

        .footer-line {
            width: 65mm;
            margin: 0 auto 3mm;
            border-top: 0.4mm solid rgba(255,255,255,.7);
        }

        .footer-title {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 9pt;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .footer-subtitle {
            margin-top: 1.5mm;
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 7pt;
            font-weight: normal;
            letter-spacing: .5px;
            color: #e0e7ff;
        }

        /* =====================================
           PENGATURAN CETAK
        ===================================== */

        @media print {
            html, body {
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

        $className = strtoupper(
            $studyGroup->full_name ?? $studyGroup->name
        );
    @endphp

    <div class="page {{ $loop->last ? 'last' : '' }}">

        <!-- =====================================
             JUDUL DOKUMEN
        ====================================== -->

        <div class="header">
            <div class="document-title">
                QR CODE KEHADIRAN KELAS {{ $className }}
            </div>
        </div>


        <!-- =====================================
             NAMA SEKOLAH
        ====================================== -->

        <div class="school-name">
            {{ $studyGroup->school?->name ?? '' }}
        </div>


        <!-- =====================================
             INFORMASI KELAS
        ====================================== -->

        <table class="meta">

            <tr>
                <td>WALI KELAS</td>
                <td>:</td>
                <td>
                    {{ $studyGroup->homeroomTeacher?->name ?? '—' }}
                </td>
            </tr>

            <tr>
                <td>TAHUN AJARAN</td>
                <td>:</td>
                <td>
                    {{ $token->academicYear?->name ?? '—' }}
                </td>
            </tr>

        </table>


        <!-- =====================================
             PETUNJUK PENGGUNAAN
        ====================================== -->

        <div class="instruction">

            Pindai QR Code berikut melalui menu
            <strong>Scan QR Kehadiran</strong>
            pada aplikasi ALIM untuk mencatat
            kehadiran masuk dan keluar kelas.

        </div>


        <!-- =====================================
             QR CODE
        ====================================== -->

        <div class="qr-panel">

            <img
                src="{{ $qrData }}"
                alt="QR Code Kehadiran Kelas {{ $className }}"
            >

        </div>

    </div>

@endforeach

</body>
</html>
