<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Surat Keterangan Pindah — {{ $mutation->student_name }}</title>
<style>
    @page { size: A4; margin: 2cm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.5; color: #000; background: #fff; }
    .page { max-width: 210mm; margin: 0 auto; padding: 2cm; }
    .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
    .header .institution-name { font-size: 16pt; font-weight: bold; text-transform: uppercase; }
    .header .institution-address { font-size: 10pt; margin-top: 2px; }
    .header .institution-contact { font-size: 9pt; }
    .letter-meta { margin-bottom: 20px; }
    .letter-meta table { border-collapse: collapse; }
    .letter-meta td { padding: 2px 4px; font-size: 11pt; }
    .letter-meta .label { width: 130px; }
    .letter-meta .colon { width: 20px; }
    .content { text-align: justify; margin-bottom: 12px; font-size: 12pt; }
    .content-indent { padding-left: 2em; }
    .data-table { margin: 15px 0 20px 0; border-collapse: collapse; width: 100%; }
    .data-table td { padding: 4px 8px; vertical-align: top; font-size: 11pt; }
    .data-table td:first-child { width: 160px; }
    .data-table td:nth-child(2) { width: 20px; }
    .signature { margin-top: 40px; }
    .signature-table { width: 100%; }
    .signature-table td { vertical-align: top; }
    .signature-right { text-align: center; }
    .signature-city { font-size: 11pt; margin-bottom: 5px; }
    .signature-title { font-size: 11pt; margin-bottom: 40px; }
    .signature-name { font-size: 12pt; font-weight: bold; text-decoration: underline; margin-bottom: 3px; }
    .signature-nip { font-size: 10pt; }
    .note { font-size: 9pt; margin-top: 15px; padding: 8px; border: 1px solid #ccc; background: #f9f9f9; }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="institution-name">{{ $mutation->institution_name ?: ($mutation->school?->name ?? 'PondokPesantren') }}</div>
        @if($mutation->institution_address)
            <div class="institution-address">{{ $mutation->institution_address }}</div>
        @endif
        @if($mutation->institution_phone || $mutation->institution_email)
            <div class="institution-contact">
                {{ $mutation->institution_phone ? 'Telp: ' . $mutation->institution_phone : '' }}
                {{ $mutation->institution_email ? ' | Email: ' . $mutation->institution_email : '' }}
            </div>
        @endif
    </div>

    <div class="letter-meta">
        <table>
            <tr>
                <td class="label">Lampiran</td><td class="colon">:</td>
                <td>{{ $mutation->letter_attachment ? '1 (satu) lembar' : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Nomor</td><td class="colon">:</td>
                <td>{{ $mutation->letter_number ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Perihal</td><td class="colon">:</td>
                <td><strong>Surat Keterangan Pindah Sekolah</strong></td>
            </tr>
        </table>
    </div>

    <div class="content">
        Kepada Yth.<br>
        Kepala {{ $mutation->destination_school_name ?: '[Nama Sekolah Tujuan]' }}<br>
        Di Tempat
    </div>

    <div class="content">
        Dengan hormat,<br>
        Melalui surat ini, kami sampaikan bahwa berdasarkan permohonan dari orang tua/wali siswa, dengan ini kami memberikan keterangan bahwa:
    </div>

    <table class="data-table">
        <tr><td>1. Nama Lengkap</td><td>:</td><td><strong>{{ $mutation->student_name ?: '-' }}</strong></td></tr>
        <tr><td>2. NISN</td><td>:</td><td>{{ $mutation->student_nisn ?: '-' }}</td></tr>
        <tr><td>3. NIS</td><td>:</td><td>{{ $mutation->student_nis ?: '-' }}</td></tr>
        <tr><td>4. Jenis Kelamin</td><td>:</td><td>{{ $mutation->gender_text }}</td></tr>
        <tr><td>5. Tempat, Tanggal Lahir</td><td>:</td><td>{{ $mutation->student_birth_place ?: '-' }}{{ $mutation->student_birth_date ? ', ' . $mutation->student_birth_date->format('d F Y') : '' }}</td></tr>
        <tr><td>6. Alamat</td><td>:</td><td>{{ $mutation->student_address ?: '-' }}</td></tr>
        <tr><td>7. Nama Orang Tua/Wali</td><td>:</td><td>{{ $mutation->parent_name ?: '-' }}</td></tr>
        <tr><td>8. Pekerjaan Orang Tua</td><td>:</td><td>{{ $mutation->parent_occupation ?: '-' }}</td></tr>
        <tr><td>9. Alamat Orang Tua</td><td>:</td><td>{{ $mutation->parent_address ?: '-' }}</td></tr>
        <tr><td>10. No. HP Orang Tua</td><td>:</td><td>{{ $mutation->parent_phone ?: '-' }}</td></tr>
        <tr><td>11. Asal Sekolah</td><td>:</td><td>{{ $mutation->student_previous_school ?: ($mutation->school?->name ?? '-') }}</td></tr>
        <tr><td>12. Kelas</td><td>:</td><td>{{ $mutation->student_current_class ?: '-' }}</td></tr>
        <tr><td>13. Sekolah Tujuan</td><td>:</td><td>{{ $mutation->destination_school_name ?: '-' }}</td></tr>
        <tr><td>14. Alamat Sekolah Tujuan</td><td>:</td><td>{{ $mutation->destination_school_address ? $mutation->destination_school_address . ($mutation->destination_school_city ? ', ' . $mutation->destination_school_city : '') : '-' }}</td></tr>
    </table>

    <div class="content">
        <strong>Alasan Pindah:</strong><br>
        <span class="content-indent">{{ $mutation->reason ?: '-' }}</span>
    </div>

    <div class="content">
        Demikian surat keterangan ini dibuat dengan sebenarnya, untuk dapat dipergunakan sebagaimana mestinya.
    </div>

    <table class="signature-table">
        <tr>
            <td style="width:55%"></td>
            <td style="width:45%" class="signature-right">
                <div class="signature-city">{{ $mutation->established_city ?: 'Mataram' }}, {{ $mutation->established_date ? $mutation->established_date->format('d F Y') : now()->format('d F Y') }}</div>
                <div class="signature-title">{{ $mutation->head_title ?: 'Kepala Sekolah' }}</div>
                <div class="signature-name">{{ $mutation->head_name ?: '-' }}</div>
                <div class="signature-nip">NUPY. {{ $mutation->head_nupy ?: '-' }}</div>
                @if($mutation->hijri_date)
                    <div style="font-size:10pt;margin-top:4px">{{ $mutation->hijri_date }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="note">
        <strong>Tembusan:</strong><br>
        1. Orang Tua/Wali terkait<br>
        2. {{ $mutation->destination_school_name ?: 'Sekolah Tujuan' }}<br>
        3. Arsip
    </div>
</div>
</body>
</html>
