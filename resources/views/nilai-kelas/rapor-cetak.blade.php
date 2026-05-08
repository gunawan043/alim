<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rapor — {{ $santri[array_key_first($santri)]['Nama'] ?? '' }}</title>
    <style>
        @page {
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
            line-height: 1.4;
            color: #000;
        }

        /* ── Kop Surat ── */
        .kop-surat {
            width: 100%;
            display: block;
        }

        /* ── Page wrapper per siswa ── */
        .page {
            padding: 20px 40px 40px 40px;
            position: relative;
        }

        /* ── Judul ── */
        .judul {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin: 12px 0 14px 0;
            text-transform: uppercase;
            text-decoration: underline;
        }

        /* ── Info grid ── */
        .info-grid {
            width: 100%;
            border: none;
            border-collapse: collapse;
        }
        .info-grid td {
            border: none;
            padding: 2px 4px;
            font-size: 10pt;
        }
        .info-grid td.lbl {
            font-weight: bold;
            width: 12%;
        }
        .info-grid td.colon {
            width: 2%;
            font-weight: bold;
        }
        .info-grid td.val {
            width: 36%;
            font-weight: bold;
        }

        /* ── Tabel Nilai ── */
        table.nilai {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.nilai th,
        table.nilai td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 10pt;
        }
        table.nilai th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        table.nilai td.mapel {
            text-align: left;
            width: 40%;
        }
        table.nilai td.no {
            text-align: center;
            width: 5%;
        }
        table.nilai td.kkm {
            text-align: center;
            width: 8%;
        }
        table.nilai td.nilai-col {
            text-align: center;
            width: 8%;
        }
        table.nilai td.ket-col {
            text-align: center;
            width: 20%;
        }

        /* ── Ringkasan ── */
        .ringkasan-box {
            width: 100%;
            border: 1px solid #000;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .ringkasan-box td {
            border: 1px solid #000;
            padding: 4px 8px;
            font-size: 10pt;
            font-weight: bold;
        }
        .ringkasan-box td.lbl {
            text-align: left;
            width: 28%;
        }
        .ringkasan-box td.colon {
            text-align: center;
            width: 3%;
        }
        .ringkasan-box td.val {
            text-align: center;
            width: 10%;
        }
        .ringkasan-box td.pred-label {
            text-align: center;
            width: 20%;
        }
        .ringkasan-box td.empty {
            border: none;
            width: 5%;
        }
        .ringkasan-box td.pred-val {
            border: none;
            font-size: 16pt;
            font-weight: bold;
        }

        /* ── Catatan ── */
        .catatan-box {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .catatan-box td {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 10pt;
            vertical-align: top;
        }
        .catatan-box td.hdr {
            font-weight: bold;
            background: #f0f0f0;
        }
        .catatan-box td.lbl-col {
            width: 40%;
        }

        /* ── TTD ── */
        .ttd-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .ttd-grid td {
            border: none;
            font-size: 9pt;
            text-align: center;
            vertical-align: bottom;
            padding: 4px 8px;
        }
        .ttd-grid td.with-gap {
            padding-top: 60px;
        }
        .ttd-grid td.center-col {
            text-align: center;
        }
        .ttd-grid td.ks-row {
            border: none;
            text-align: center;
            padding-top: 20px;
        }

        /* ── Footer bar ── */
        .footer-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #000;
            padding: 4px 40px;
            display: table;
            width: 100%;
        }
        .footer-bar td {
            border: none;
            font-size: 8pt;
            display: table-cell;
        }
        .footer-bar td.left { text-align: left; width: 30%; }
        .footer-bar td.center { text-align: center; width: 40%; }
        .footer-bar td.right { text-align: right; width: 30%; }

        /* ── Page break ── */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

@foreach($santri as $key => $data)
<div class="page{{ !$loop->last ? ' page-break' : '' }}">

    {{-- Kop Surat --}}
    @if($kopBase64)
    <img src="{{ $kopBase64 }}" class="kop-surat" alt="Kop Surat">
    @endif

    {{-- Judul --}}
    <div class="judul">Laporan Hasil Belajar Tengah Semester</div>

    {{-- Info Santri --}}
    <table class="info-grid">
        <tr>
            <td class="lbl">Nama</td>
            <td class="colon">:</td>
            <td class="val">{{ $data['Nama'] ?? '-' }}</td>
            <td class="lbl">Semester</td>
            <td class="colon">:</td>
            <td class="val">{{ $data['Semester'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="lbl">Kelas</td>
            <td class="colon">:</td>
            <td class="val">{{ $data['Kelas'] ?? '-' }}</td>
            <td class="lbl">Tahun Ajaran</td>
            <td class="colon">:</td>
            <td class="val">{{ $data['TahunAjaran'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="lbl">NIS / NISN</td>
            <td class="colon">:</td>
            <td class="val" colspan="3">{{ $data['NIS'] ?? '-' }} / {{ $data['NISN'] ?? '-' }}</td>
        </tr>
    </table>

    {{-- Tabel Nilai --}}
    <table class="nilai">
        <thead>
            <tr>
                <th class="no">No</th>
                <th class="mapel">Mata Pelajaran</th>
                <th class="kkm">KKTP</th>
                <th class="nilai-col">Nilai</th>
                <th class="ket-col">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['Mapel'] as $row)
            <tr>
                <td class="no">{{ $row['no'] }}</td>
                <td class="mapel">{{ $row['mapel'] }}</td>
                <td class="kkm">{{ $row['kkm'] }}</td>
                <td class="nilai-col">{{ $row['nilai'] }}</td>
                <td class="ket-col">{{ $row['keterangan'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Ringkasan --}}
    <table class="ringkasan-box">
        <tr>
            <td class="lbl" style="text-align:left; padding-left:16px;">Jumlah Nilai</td>
            <td class="colon">:</td>
            <td class="val">{{ $data['Jumlah'] ?? '-' }}</td>
            <td class="empty"></td>
            <td class="empty"></td>
            <td class="empty"></td>
            <td class="pred-label" style="text-align:center;">Predikat</td>
            <td class="colon">:</td>
            <td class="pred-val">{{ $data['Predikat'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="lbl" style="text-align:left; padding-left:16px;">Nilai Rata-Rata</td>
            <td class="colon">:</td>
            <td class="val">{{ $data['Rata'] ?? '-' }}</td>
            <td class="empty" colspan="6"></td>
        </tr>
    </table>

    {{-- Catatan --}}
    <table class="catatan-box">
        <tr>
            <td class="hdr lbl-col">Catatan Kehadiran</td>
            <td class="hdr">Catatan Wali Kelas</td>
        </tr>
        <tr>
            <td class="lbl-col">
                1.&nbsp; Sakit &nbsp;: {{ $data['Sakit'] ?? '-' }} Hari<br>
                2.&nbsp; Izin &nbsp;&nbsp;: {{ $data['Izin'] ?? '-' }} Hari<br>
                3.&nbsp; Alpa &nbsp;&nbsp;: {{ $data['Alpa'] ?? '-' }} Hari
            </td>
            <td>
                Tingkatkan terus prestasinya, bertakwalah kepada Allah,
                jaga salat 5 waktu, dan berbaktilah kepada orang tua.
            </td>
        </tr>
    </table>

    {{-- TTD --}}
    <table class="ttd-grid">
        <tr>
            <td class="with-gap" style="width:30%;">
                <br><br><br><br><br>
                ________________________
                <br>Orang Tua / Wali
            </td>
            <td class="with-gap" style="width:30%;">
                <br><br><br><br><br>
                ________________________
                <br>Wali Kelas
            </td>
            <td class="with-gap center-col" style="width:40%;">
                {{ $studyGroup->school?->city ?? 'Mataram' }}, {{ now()->translatedFormat('d F Y') }}
                <br><br><br><br><br>
                ________________________
                <br><strong>{{ $studyGroup->homeroomTeacher?->name ?? '................................' }}</strong>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="ks-row">
                <br>
                Mengetahui,&nbsp;&nbsp;Kepala Sekolah
                <br><br><br><br><br>
                ________________________
                <br><strong>{{ $studyGroup->school?->principal_name ?? '................................' }}</strong>
                <br>NIP. {{ $studyGroup->school?->principal_nip ?? '...................' }}
            </td>
        </tr>
    </table>

</div>
@endforeach

{{-- Footer bar (di luar page agar muncul di semua halaman) --}}
<div class="footer-bar">
    <table style="width:100%; border-collapse:collapse;">
    <tr>
        <td class="left">PAH-KSP-FORM-14-02</td>
        <td class="center">REV.01 / 10 September 2025</td>
        <td class="right">Halaman 1 dari 1</td>
    </tr>
    </table>
</div>

</body>
</html>
