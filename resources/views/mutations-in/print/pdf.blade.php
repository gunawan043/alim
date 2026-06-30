<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Rekomendasi — {{ $mutation->student_name }}</title>
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
            font-size: 11pt;
            line-height: 1.3;
            color: #000;
            background: #fff;
            width: 210mm;
        }

        .page {
            padding: 18mm 25mm 0mm 25mm;
        }

        .header {
            text-align: center;
            padding-bottom: 8px;
            margin-bottom: 0px;
            margin-top: -45px
        }

        .letter-title {
            text-align: center;
            font-size: 16pt;
            text-decoration: underline;
        }

        .letter-number {
            text-align: center;
            font-size: 11pt;
            margin-top: -3px;
            margin-bottom: 15px;
        }

        .body-text {
            font-size: 11pt;
            margin-top: 15px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .data-table td {
            padding: 2px 0;
            font-size: 11pt;
            vertical-align: top;
        }

        .data-table td:first-child {
            width: 160px;
        }

        .data-table td:nth-child(2) {
            width: 15px;
        }

        .req-list {
            margin-left: 40px;
            margin-top: 8px;
            font-size: 11pt;
            margin-bottom: 8px;
        }

        .signature-table {
            width: 100%;
            margin-top: 30px;  
        }

        .signature-table td {
            vertical-align: top;
        }

        .sig-left {
            width: 60%;
        }

        .sig-right {
            width: 40%;
            text-align: left;
        }

        .sig-city {
            font-size: 11pt;
            margin-top: 20px;
            margin-bottom: 2px;
        }

        .sig-city-hijri {
            font-size: 11pt;
        }

        .sig-title {
            font-size: 11pt;
            margin-bottom: 36px;
        }

        .sig-name {
            font-size: 11pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 3px;
        }

        .sig-nip {
            font-size: 10pt;
        }

        .note {
            font-size: 11pt;
            margin-top: 10px;
            padding: 8px;
        }

        .footer-info {
            font-size: 9pt;
            margin-top: 15px;
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

        <div class="letter-title">SURAT REKOMENDASI</div>

        {{-- Judul --}}
        <div class="letter-number">Nomor : {{ $mutation->letter_number ?: '………' }}</div>

        {{-- Pembuka --}}
        <div class="body-text" style="margin-bottom: 10px">Yang bertandatangan di bawah ini, Kepala
        {{ $school->name ?? '[Nama Lembaga]' }} menerangkan bahwa:</div>

        {{-- Data Santri --}}
        <table class="data-table">
            <tr>
                <td>Nama</td>
                <td>:</td>
                <td>{{ $mutation->student_name ?: '-' }}</td>
            </tr>
            <tr>
                <td>Tempat, Tanggal Lahir</td>
                <td>:</td>
                <td>{{ $mutation->student_birth_place ?: '-' }}{{ $mutation->student_birth_date ? ', ' . $mutation->student_birth_date->format('d F Y') : '' }}
                </td>
            </tr>
            <tr>
                <td>Sekolah Asal</td>
                <td>:</td>
                <td>{{ $mutation->student_previous_school ?: '-' }}</td>
            </tr>
            <tr>
                <td>Kelas di sekolah asal</td>
                <td>:</td>
                <td>{{ $mutation->student_previous_class ?: '-' }}</td>
            </tr>
            <tr>
                <td>Jenis Kelamin</td>
                <td>:</td>
                <td>{{ $mutation->gender_text }}</td>
            </tr>
            <tr>
                <td>Agama</td>
                <td>:</td>
                <td>{{ $mutation->student_religion ?: 'Islam' }}</td>
            </tr>
        </table>

        {{-- Data Orang Tua --}}
        <div class="body-text">Anak dari orang tua</div>
        <table class="data-table">
            <tr>
                <td>Bapak</td>
                <td>:</td>
                <td>{{ $mutation->father_name ?: '-' }}</td>
            </tr>
            <tr>
                <td>Ibu</td>
                <td>:</td>
                <td>{{ $mutation->mother_name ?: '-' }}</td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td>:</td>
                <td>{{ $mutation->parent_address ?: '-' }}</td>
            </tr>
            <tr>
                <td>No. HP</td>
                <td>:</td>
                <td>{{ $mutation->parent_phone ?: '-' }}</td>
            </tr>
        </table>

        {{-- Isi --}}
        <div class="body-text">Berdasarkan hasil seleksi, calon siswa yang disebutkan di atas dinyatakan diterima di
            kelas {{ $mutation->accepted_class ?: '…. ' }} pada semester
            {{ $mutation->accepted_semester ?: '……' }} tahun ajaran
            {{ $mutation->accepted_academic_year ?: '…………' }}, dengan melengkapi persyaratan sebagai
            berikut:</div>

        <ol class="req-list">
            <li>Menyerahkan surat keterangan pindah dari sekolah asal (termasuk mutasi dapodik/emis).</li>
            <li>Menyerahkan foto copy Ijazah dan SKHUN Sekolah sebelumnya.</li>
            <li>Menyerahkan foto copy Akta Kelahiran dan Kartu Keluarga masing-masing 1 lembar.</li>
            <li>Membayar uang daftar ulang di Bendahara Pondok.</li>
            <li>Membayar IBS bulan pertama.</li>
            <li>Sanggup mentaati peraturan yang berlaku di Pondok Pesantren Abu Hurairah Mataram.</li>
        </ol>

        <div class="body-text">Demikian surat rekomendasi ini diberikan untuk dipergunakan sebagaimana mestinya.</div>

        {{-- Tanda Tangan --}}
        <table class="signature-table">
            <tr>
                <td class="sig-left"></td>
                <td class="sig-right">
                    <table style="width:100%;">
                        <tr>
                            <td style="width:65px" class="sig-city">
                                {{ $mutation->established_city ?: $school->city ?? 'Kota' }},</td>
                            <td class="sig-city" style="text-decoration: underline;">
                                {{ $mutation->established_date ? $mutation->established_date->format('d F Y') : now()->format('d F Y') }}
                                M</td>
                        </tr>
                        @if ($mutation->hijri_date)
                           <tr style="line-height: 0.9">
                                <td></td>
                                <td class="sig-city-hijri">{{ $mutation->hijri_date }}</td>
                            </tr>
                        @endif
                    </table>
                    <div class="sig-title">{{ $mutation->head_title ?: 'Kepala Sekolah' }}</div>
                    <div class="sig-name" style="padding-top: 40px">{{ $mutation->head_name ?: '-' }}</div>
                    <div class="sig-nip">NUPY. {{ $mutation->head_nupy ?: '-' }}</div>
                </td>
            </tr>
        </table>

        {{-- Tembusan --}}
        <div class="note">
            Tembusan:<br>
            <ol class="req-list">
                <li>Wakil Mudir Bidang Akademik dan Pengasuhan Ponpes Abu Hurairah Mataram</li>
                <li>Kepala Keuangan Ponpes Abu Hurairah Mataram</li>
                <li>Orang Tua/Wali Santri</li>
                <li>Pertinggal</li>
            </ol>
        </div>

        {{-- Footer info --}}
        <div class="footer-info">
            <table style="width:100%;border-collapse:collapse;">
                <tr>
                    <td style="width:33%;font-size:10pt;">PAH-KSP-FORM-23-01</td>
                    <td style="width:33%;text-align:center;font-size:10pt;">Rev.01/10 September 2025</td>
                    <td style="width:33%;text-align:right;font-size:10pt;">Hal. 1 dari 1</td>
                </tr>
            </table>
        </div>

    </div>
</body>

</html>
