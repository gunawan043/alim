<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan AM Nasyath Syahri</title>
    @include('bahasa._styles')
</head>
<body class="lang-print p-4">
    <div class="text-center mb-3">
        <h4 class="mb-1">LAPORAN KEGIATAN AM NASYATH SYAHRI</h4>
        <div>Departemen Bahasa · PAH Mataram</div>
    </div>

    <table class="mb-3" style="border:0">
        <tr style="border:0"><td style="border:0;width:130px">Nama Kegiatan</td><td style="border:0">: {{ $record->title }}</td><td style="border:0;width:130px">Bulan</td><td style="border:0">: {{ $record->period_month?->format('m/Y') }}</td></tr>
        <tr style="border:0"><td style="border:0">Jenis</td><td style="border:0">: {{ $record->activity_type_label }}</td><td style="border:0">Tanggal</td><td style="border:0">: {{ $record->activity_date?->format('d/m/Y') ?? '-' }}</td></tr>
        <tr style="border:0"><td style="border:0">Tema</td><td style="border:0">: {{ $record->theme ?: '-' }}</td><td style="border:0">Tempat</td><td style="border:0">: {{ $record->place ?: '-' }}</td></tr>
        <tr style="border:0"><td style="border:0">Pelaksana</td><td style="border:0">: {{ ucfirst($record->executor_type) }} {{ $record->executor_name ? '· '.$record->executor_name : '' }}</td><td style="border:0">Peserta</td><td style="border:0">: {{ $record->participants_count }} orang</td></tr>
    </table>

    <table>
        <tr><th style="width:150px">Panitia</th><td>{{ $record->committee ?: '-' }}</td></tr>
        <tr><th>MC</th><td>{{ $record->mc_name ?: '-' }}</td></tr>
        <tr><th>Catatan Partisipasi</th><td>{{ $record->participation_notes ?: '-' }}</td></tr>
        <tr><th>Laporan</th><td style="min-height:100px">{{ $record->report ?: '-' }}</td></tr>
    </table>

    <div class="row mt-5" style="font-size:12px">
        <div class="col-6 text-center"><div>Kepala Departemen Bahasa</div><div style="height:70px"></div><div>__________________________</div></div>
        <div class="col-6 text-center"><div>Mataram, {{ now()->format('d/m/Y') }}</div><div>Qismul Lughah</div><div style="height:70px"></div><div>__________________________</div></div>
    </div>
    <div class="text-center mt-4 no-print"><button onclick="window.print()" class="btn btn-primary">Cetak</button></div>
</body>
</html>
