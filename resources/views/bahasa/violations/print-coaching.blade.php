<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Pembinaan Bahasa</title>
    @include('bahasa._styles')
</head>
<body class="lang-print p-4">
    <div class="text-center mb-3">
        <h4 class="mb-1">LAPORAN PEMBINAAN BAHASA</h4>
        <div>Departemen Bahasa · PAH Mataram</div>
    </div>

    <table class="mb-3" style="border:0">
        <tr style="border:0"><td style="border:0;width:120px">Nama Pelanggar</td><td style="border:0">: {{ $record->student?->name ?? '-' }}</td><td style="border:0;width:120px">Kelas</td><td style="border:0">: {{ $record->class_name ?: '-' }}</td></tr>
        <tr style="border:0"><td style="border:0">Pelanggaran</td><td style="border:0">: {{ $record->violation_type }}</td><td style="border:0">Poin</td><td style="border:0">: {{ $record->points }}</td></tr>
    </table>

    <table>
        <thead><tr><th style="width:38px">No</th><th>Kategori</th><th>Tindakan Pembinaan</th><th>Tanggal</th><th>Pembina</th><th>Catatan</th></tr></thead>
        <tbody>
            @forelse($record->coachings as $index => $coaching)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $coaching->category ?: '-' }}</td>
                    <td>{{ $coaching->action ?: '-' }}</td>
                    <td>{{ $coaching->coaching_date?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $coaching->coach?->name ?? '-' }}</td>
                    <td>{{ $coaching->notes ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">Belum ada pembinaan.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="row mt-5" style="font-size:12px">
        <div class="col-6 text-center"><div>Kepala Departemen Bahasa</div><div style="height:70px"></div><div>__________________________</div></div>
        <div class="col-6 text-center"><div>Pembina</div><div style="height:70px"></div><div>__________________________</div></div>
    </div>
    <div class="text-center mt-4 no-print"><button onclick="window.print()" class="btn btn-primary">Cetak</button></div>
</body>
</html>
