@php
    $school = $sk->school;
    $schoolName = $school?->kop_nama ?: ($school?->name ?: 'Satuan Pendidikan');
    $semesterLabel = $sk->semester === 1 ? 'GANJIL' : 'GENAP';
    $tahunAjaran = $sk->academicYear?->name ?? '-';
    $menimbang = $sk->menimbang ?: 'Bahwa dalam upaya memperlancar penilaian kinerja guru dalam pengembangan karir guru sebagai tenaga profesional pada '.$schoolName.' pada semester '.$semesterLabel.' tahun ajaran '.$tahunAjaran.', maka dipandang perlu menetapkan tim supervisi pembelajaran dengan Surat Keputusan.';
    $mengingat = $sk->mengingat ?: "Undang-Undang Republik Indonesia Nomor 20 Tahun 2003 tentang Sistem Pendidikan Nasional\nPeraturan Pemerintah Nomor 19 Tahun 2015 tentang Standar Nasional Pendidikan\nProgram Kerja ".$schoolName."\nKalender Pendidikan ".$schoolName;
    $roles = ['ksp' => 'KSP', 'wksp' => 'WKSP', 'guru' => 'Perwakilan Guru'];
    $kspName = $sk->anggota->firstWhere('peran', 'ksp')?->gtk?->name;

    $kopBase64 = null;
    if ($school?->kop_path) {
        $kopAbs = storage_path('app/public/'.$school->kop_path);
        if (file_exists($kopAbs)) {
            $ext = strtolower(pathinfo($kopAbs, PATHINFO_EXTENSION) ?: 'png');
            $mime = in_array($ext, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/png';
            $kopBase64 = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($kopAbs));
        }
    }

    $logoBase64 = null;
    if ($school?->logo_path) {
        $logoAbs = storage_path('app/public/'.$school->logo_path);
        if (file_exists($logoAbs)) {
            $ext = strtolower(pathinfo($logoAbs, PATHINFO_EXTENSION) ?: 'png');
            $mime = in_array($ext, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/png';
            $logoBase64 = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($logoAbs));
        }
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>SK Tim Supervisi — {{ $sk->nomor }}</title>
    <style>
        @page { size: A4; margin: 8mm 20mm 18mm 22mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Cambria', 'Caladea', 'DejaVu Serif', serif; font-size: 11pt; line-height: 1.15; color: #111; }
        .center { text-align: center; }
        .kop { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 12px; }
        .kop-image { display: block; width: 100%; max-height: 34mm; object-fit: contain; margin: 0 auto; }
        .kop .nama { font-size: 16pt; font-weight: bold; text-transform: uppercase; }
        .kop .sub { font-size: 10pt; }
        .title { text-align: center; font-weight: bold; text-transform: uppercase; margin: 18px 0 2px; }
        .nomor { text-align: center; margin-bottom: 18px; }
        .tentang { text-align: center; font-weight: bold; text-transform: uppercase; margin: 14px 0 2px; }
        .periode { text-align: center; font-weight: bold; text-transform: uppercase; margin-bottom: 18px; }
        h4 { font-weight: bold; text-transform: uppercase; margin: 14px 0 4px; }
        p { margin: 0 0 8px; text-align: justify; }
        ol, ul { margin: 0 0 10px 22px; padding: 0; }
        li { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        table.members th, table.members td { border: 1px solid #333; padding: 6px 8px; vertical-align: top; }
        table.members th { background: #eee; text-align: center; }
        .signature { width: 100%; margin-top: 30px; page-break-inside: avoid; }
        .signature td { width: 50%; vertical-align: top; }
        .signature .space { height: 70px; }
        .signature .name { font-weight: bold; text-decoration: underline; }
        .tembusan { margin-top: 22px; font-size: 10pt; }
        .page-break { page-break-before: always; }
        .toolbar { position: fixed; top: 10px; right: 10px; z-index: 10; }
        .toolbar button { padding: 8px 14px; border: 0; border-radius: 6px; background: #0d6efd; color: #fff; cursor: pointer; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">Cetak SK</button></div>

<div class="kop">
    @if($kopBase64)
        <img src="{{ $kopBase64 }}" alt="Kop {{ $schoolName }}" class="kop-image">
    @else
        @if($logoBase64)<img src="{{ $logoBase64 }}" alt="Logo" style="max-height:22mm;margin-bottom:4px;">@endif
        <div class="nama">{{ $school?->kop_nama ?: $schoolName }}</div>
        @if($school?->kop_alamat ?: $school?->address)<div class="sub">{{ $school?->kop_alamat ?: $school->address }}</div>@endif
        @if($school?->kop_telp ?: $school?->phone ?: $school?->kop_email ?: $school?->email)
            <div class="sub">
                {{ $school?->kop_telp ?: $school?->phone }}
                @if(($school?->kop_telp ?: $school?->phone) && ($school?->kop_email ?: $school?->email)) · @endif
                {{ $school?->kop_email ?: $school?->email }}
            </div>
        @endif
        @if($school?->kop_npsn ?: $school?->npsn)<div class="sub">NPSN: {{ $school?->kop_npsn ?: $school->npsn }}</div>@endif
    @endif
</div>

<div class="title">KEPUTUSAN KEPALA {{ strtoupper($schoolName) }}</div>
<div class="nomor">NOMOR: {{ $sk->nomor }}</div>
<div class="tentang">TENTANG</div>
<div class="tentang">PEMBENTUKAN TIM SUPERVISI PEMBELAJARAN</div>
<div class="periode">SEMESTER {{ $semesterLabel }} TAHUN AJARAN {{ strtoupper($tahunAjaran) }}</div>

<div class="center" style="font-weight:bold; text-transform:uppercase; margin-bottom:12px;">KEPALA {{ strtoupper($schoolName) }}</div>

<h4>Menimbang:</h4>
<p>{{ $menimbang }}</p>

<h4>Mengingat:</h4>
<ol>
    @foreach(preg_split('/\r\n|\r|\n/', $mengingat) as $item)
        @if(trim($item) !== '')<li>{{ trim($item) }}</li>@endif
    @endforeach
</ol>

<h4>Memutuskan</h4>
<h4>Menetapkan:</h4>
<p><strong>KEPUTUSAN KEPALA {{ strtoupper($schoolName) }} TENTANG TIM SUPERVISI PEMBELAJARAN PADA SEMESTER {{ $semesterLabel }} TAHUN AJARAN {{ strtoupper($tahunAjaran) }}.</strong></p>
<p><strong>Kesatu:</strong> Menunjuk dan menetapkan nama-nama yang tercantum dalam Surat Keputusan ini sebagai tim supervisi pembelajaran.</p>
<p><strong>Kedua:</strong> Keputusan ini mulai berlaku sejak tanggal ditetapkan.</p>

<table class="signature">
    <tr>
        <td></td>
        <td class="center">
            Ditetapkan di Mataram<br>
            Pada tanggal {{ $sk->tanggal?->translatedFormat('d F Y') ?? '-' }}<br><br>
            Kepala Satuan Pendidikan<br>
            <div class="space"></div>
            <div class="name">{{ $kspName ?? '______________________________' }}</div>
        </td>
    </tr>
</table>

<div class="tembusan">
    <strong>Tembusan kepada Yth.:</strong>
    <ol>
        <li>Wadir Akademik dan Pengasuhan</li>
        <li>Yang bersangkutan</li>
        <li>Tertinggal</li>
    </ol>
</div>

<div class="page-break"></div>

<div class="title">LAMPIRAN KEPUTUSAN KEPALA {{ strtoupper($schoolName) }}</div>
<div class="nomor">NOMOR: {{ $sk->nomor }}</div>
<div class="nomor">TANGGAL: {{ $sk->tanggal?->translatedFormat('d F Y') ?? '-' }}</div>
<div class="tentang">TENTANG</div>
<div class="tentang">TIM SUPERVISI PEMBELAJARAN</div>
<div class="periode">SEMESTER {{ $semesterLabel }} TAHUN AJARAN {{ strtoupper($tahunAjaran) }}</div>

<table class="members">
    <thead><tr><th style="width:50px">NO</th><th>NAMA</th><th style="width:230px">JABATAN</th></tr></thead>
    <tbody>
    @forelse($sk->anggota as $index => $member)
        <tr>
            <td class="center">{{ $index + 1 }}.</td>
            <td>{{ $member->gtk?->name ?? '-' }}</td>
            <td>{{ $roles[$member->peran] ?? $member->peran }}{{ $member->gtk?->latestEmployment?->jabatan ? ' / '.$member->gtk->latestEmployment->jabatan : '' }}</td>
        </tr>
    @empty
        <tr><td colspan="3" class="center">Belum ada anggota tim.</td></tr>
    @endforelse
    </tbody>
</table>

<table class="signature">
    <tr>
        <td></td>
        <td class="center">
            Mataram, {{ $sk->tanggal?->translatedFormat('d F Y') ?? '-' }}<br>
            Kepala Satuan Pendidikan<br>
            <div class="space"></div>
            <div class="name">{{ $kspName ?? '______________________________' }}</div>
        </td>
    </tr>
</table>
</body>
</html>
