<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Pindah Sekolah</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.2;
            color: #000;
            background: #fff;
            width: 210mm;
        }

        .page {
            padding: 20mm 25mm 0mm 25mm;
        }

        /* Header / Kop Surat */
        .header {
            text-align: center;
            padding-bottom: 10px;
            margin-bottom: 0px;
            margin-top: -50px
        }

        .institution-name {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .institution-address {
            font-size: 10pt;
            margin-top: 3px;
        }

        .institution-contact {
            font-size: 9pt;
            margin-top: 2px;
        }

        /* Judul */
        .letter-title {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
        }

        .letter-number {
            text-align: center;
            font-size: 12pt;
            margin-bottom: 25px;
        }

        /* Tabel data sekolah */
        .school-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .school-table td {
            font-size: 12pt;
            padding: 2px 0;
        }

        .school-table td:first-child {
            width: 130px;
        }

        .school-table td:nth-child(2) {
            width: 15px;
        }

        /* Paragraf pembuka */
        .opening {
            font-size: 12pt;
            margin-bottom: 12px;
        }

        /* Tabel data santri */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .data-table td {
            padding: 3px 0;
            font-size: 12pt;
            vertical-align: top;
        }

        .data-table td:first-child {
            width: 160px;
        }

        .data-table td:nth-child(2) {
            width: 15px;
        }

        /* Body paragraphs */
        .body-text {
            font-size: 12pt;
            margin-bottom: 12px;
        }

        /* Tanda Tangan */
        .signature-table {
            width: 100%;
            margin-top: 38px;
        }

        .signature-table td {
            vertical-align: top;
        }

        .sig-left {
            width: 60%;
        }

        .sig-right {
            width: 40%;
            text-align: center;
        }

        .sig-city {
            font-size: 12pt;
            margin-bottom: 4px;
        }

        .sig-city-hijri {
            font-size: 12pt;
        }

        .sig-title {
            font-size: 12pt;
            margin-bottom: 44px;
        }

        .sig-name {
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 3px;
        }

        .sig-nip {
            font-size: 10pt;
        }

        .sig-hijri {
            font-size: 10pt;
            margin-top: 4px;
        }

        /* Tembusan */
        .note {
            font-size: 10pt;
            margin-top: 20px;
            padding: 8px;
            border: 1px solid #ccc;
            background: #f9f9f9;
        }

        .footer-info {
            font-size: 12pt;
            padding-top: 20px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="page">

        {{-- Header / Kop Surat --}}
        <div class="header">
            @if ($school->kop_path && $school->kopsis_active)
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $school->kop_path))) }}"
                    alt="Kop Surat" style="max-width:100%;max-height:130px;object-fit:contain;">
            @endif
        </div>

        {{-- Judul Surat --}}
        <div class="letter-title">SURAT KETERANGAN MUTASI KELUAR</div>
        <div class="letter-number">Nomor:{{ $mutation->letter_number ?: '………' }}</div>

        {{-- Tanda Tangan Kepala Sekolah --}}
        <div class="opening">Yang bertanda tangan di bawah ini, Kepala Sekolah:</div>

        {{-- Tabel Data Sekolah --}}
        <table class="data-table">
            <tr>
                <td>Nama Sekolah</td>
                <td>:</td>
                <td>{{ $school->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Alamat Sekolah</td>
                <td>:</td>
                <td>{{ $school->address ?? '-' }}</td>
            </tr>
            <tr>
                <td>No. Telepon</td>
                <td>:</td>
                <td>{{ $school->phone ?? '-' }}</td>
            </tr>
            <tr>
                <td colspan="3">

                </td>
            </tr>
        </table>

        {{-- Pemberitahuan --}}
        <div class="body-text">Dengan ini menyatakan bahwa:</div>

        {{-- Tabel Data Santri --}}
        <table class="data-table">
            <tr>
                <td>Nama Siswa</td>
                <td>:</td>
                <td><strong>{{ $mutation->student_name ?: '-' }}</strong></td>
            </tr>
            <tr>
                <td>Tempat, Tanggal Lahir</td>
                <td>:</td>
                <td>{{ $mutation->student_birth_place ?: '-' }}{{ $mutation->student_birth_date ? ', ' . $mutation->student_birth_date->format('d F Y') : '' }}
                </td>
            </tr>
            <tr>
                <td>NISN / NIS</td>
                <td>:</td>
                <td>{{ $mutation->student_nisn ?: '-' }} / {{ $mutation->student_nis ?: '-' }}</td>
            </tr>
            <tr>
                <td>Jenis Kelamin</td>
                <td>:</td>
                <td>{{ $mutation->gender_text }}</td>
            </tr>
            <tr>
                <td>Kelas</td>
                <td>:</td>
                <td>{{ $mutation->student_current_class ?: '-' }}</td>
            </tr>
            <tr>
                <td>Nama Orang Tua/Wali</td>
                <td>:</td>
                <td>{{ $mutation->parent_name ?: '-' }}</td>
            </tr>
            <tr>
                <td>Pekerjaan Orang Tua</td>
                <td>:</td>
                <td>{{ $mutation->parent_occupation ?: '-' }}</td>
            </tr>
            <tr>
                <td>Alamat Orang Tua</td>
                <td>:</td>
                <td>{{ $mutation->parent_address ?: '-' }}</td>
            </tr>
        </table>

        {{-- Isi berdasarkan jenis PD Keluar --}}
        @if ($mutation->out_type === 'mutation')
            <div class="body-text">Bahwa siswa tersebut di atas mengajukan permohonan pindah sekolah ke:</div>
            <table class="data-table">
                <tr>
                    <td>Nama Sekolah Tujuan</td>
                    <td>:</td>
                    <td>{{ $mutation->destination_school_name ?: '-' }}</td>
                </tr>
                <tr>
                    <td>Alamat Sekolah Tujuan</td>
                    <td>:</td>
                    <td>{{ $mutation->destination_school_address ?: '-' }}</td>
                </tr>
                <tr>
                    <td>Alasan Pindah</td>
                    <td>:</td>
                    <td>{{ $mutation->reason ?: '-' }}</td>
                </tr>
            </table>
        @elseif($mutation->out_type === 'graduation')
            <div class="body-text">Bahwa siswa tersebut di atas telah <strong>menyelesaikan pendidikan</strong> dan
                dinyatakan:</div>
            <div class="body-text"><strong>LULUS</strong> dari jenjang pendidikan yang ditempuh.</div>
        @elseif($mutation->out_type === 'dropout')
            <div class="body-text">Bahwa siswa tersebut di atas <strong>tidak dapat melanjutkan</strong> pendidikan
                dengan alasan:</div>
            <div class="body-text">{{ $mutation->reason ?: '-' }}</div>
        @endif

        {{-- Penutup --}}
        <div class="body-text">Demikian surat keterangan pindah sekolah ini dibuat untuk dapat digunakan sebagaimana
            mestinya.</div>

        {{-- Tanda Tangan --}}
        <table class="signature-table">
            <tr>
                <td class="sig-left"></td>
                <td class="sig-right">
                    <table style="width:100%;margin-bottom:60px;">
                        <tr>
                            <td style="width:65px" class="sig-city">{{ $mutation->established_city ?: $school->city ?? 'Kota' }},</td>
                            <td class="sig-city" style="text-decoration: underline;">{{ $mutation->established_date ? $mutation->established_date->format('d F Y') : now()->format('d F Y') }} M</td>
                        </tr>
                        @if ($mutation->hijri_date)
                        <tr style="line-height: 0.9">
                            <td></td>
                            <td class="sig-city-hijri">{{ $mutation->hijri_date }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td colspan="2" class="sig-title">{{ $mutation->head_title ?: 'Kepala Sekolah' }}</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="sig-name" style="padding-top: 70px">{{ $mutation->head_name ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="sig-nip">NUPY. {{ $mutation->head_nip ?: '-' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- Footer info --}}
        <div class="footer-info" style="width:100%;">
            <table style="width:100%;border-collapse:collapse;">
                <tr>
                    <td style="width:33%;font-size:11pt;">PAH-KSP-FORM-23-01</td>
                    <td style="width:33%;text-align:center;font-size:11pt;">Rev.01/10 September 2025</td>
                    <td style="width:33%;text-align:right;font-size:11pt;">Hal. 1 dari 1</td>
                </tr>
            </table>
        </div>

    </div>
</body>

</html>
