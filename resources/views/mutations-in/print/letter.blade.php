<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Surat Rekomendasi Mutasi Masuk — {{ $mutation->student_name }}</title>
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
    .letter-meta .label { width: 160px; }
    .letter-meta .colon { width: 20px; }
    .content { text-align: justify; margin-bottom: 12px; font-size: 12pt; }
    .content-indent { padding-left: 2em; }
    .data-table { margin: 15px 0 20px 0; border-collapse: collapse; width: 100%; }
    .data-table td { padding: 4px 8px; vertical-align: top; font-size: 11pt; }
    .data-table td:first-child { width: 180px; }
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
                <td class="label">Nomor</td><td class="colon">:</td>
                <td>{{ $mutation->letter_number ?: '-' }}/REK/{{ $mutation->recommendation_year ?: date('Y') }}</td>
            </tr>
            <tr>
                <td class="label">Lampiran</td><td class="colon">:</td>
                <td>-</td>
            </tr>
            <tr>
                <td class="label">Perihal</td><td class="colon">:</td>
                <td><strong>Surat Rekomendasi Mutasi Masuk</strong></td>
            </tr>
        </table>
    </div>

    <div class="content">
        Kepada Yth.<br>
        {{ $mutation->origin_school_name ? 'Kepala ' . $mutation->origin_school_name : 'Kepala Sekolah Asal' }}<br>
        Di Tempat
    </div>

    <div class="content">
        Dengan hormat,<br>
        Melalui surat ini, kami memberikan rekomendasi mutasi masuk bagi siswa berikut:
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
        <tr><td>11. Sekolah Asal</td><td>:</td><td>{{ $mutation->origin_school_name ?: '-' }}</td></tr>
        <tr><td>12. Alamat Sekolah Asal</td><td>:</td><td>{{ $mutation->origin_school_address ? $mutation->origin_school_address . ($mutation->origin_school_city ? ', ' . $mutation->origin_school_city : '') : '-' }}</td></tr>
        <tr><td>13. Kelas Tujuan</td><td>:</td><td>{{ $mutation->student_current_class ?: '-' }}</td></tr>
        <tr><td>14. Sekolah Tujuan</td><td>:</td><td>{{ $mutation->school?->name ?: '-' }}</td></tr>
    </table>

    <div class="content">
        Siswa tersebut di atas telah diterima di {{ $mutation->school?->name ?: '[Nama Sekolah]' }}
        @if($mutation->established_date)
            pada tanggal {{ $mutation->established_date->format('d F Y') }}
        @endif.
    </div>

    @if($mutation->reason)
        <div class="content">
            <strong>Keterangan:</strong><br>
            <span class="content-indent">{{ $mutation->reason }}</span>
        </div>
    @endif

    <div class="content">
        Demikian rekomendasi ini diberikan, atas perhatian dan kerja sama yang baik kami ucapkan terima kasih.
    </div>

    <table class="signature-table">
        <tr>
            <td style="width:55%"></td>
            <td style="width:45%" class="signature-right">
                <div class="signature-city">{{ $mutation->established_city ?: 'Mataram' }}, {{ $mutation->established_date ? $mutation->established_date->format('d F Y') : now()->format('d F Y') }}</div>
                <div class="signature-title">{{ $mutation->head_title ?: 'Kepala Sekolah' }}</div>
                <div class="signature-name">{{ $mutation->head_name ?: '-' }}</div>
                <div class="signature-nip">NUPY. {{ $mutation->head_nupy ?: '-' }}</div>
            </td>
        </tr>
    </table>

    <div class="note">
        <strong>Tembusan:</strong><br>
        1. Orang Tua/Wali terkait<br>
        2. {{ $mutation->origin_school_name ?: 'Sekolah Asal' }}<br>
        3. Arsip
    </div>
</div>
</body>
</html>
