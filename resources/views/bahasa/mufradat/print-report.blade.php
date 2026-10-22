<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Evaluasi Mufradat — {{ $record->title }}</title>
    @include('bahasa._styles')
</head>
<body class="lang-print p-4">
    <div class="text-center mb-3">
        <h4 class="mb-1">LAPORAN EVALUASI HAFALAN MUFRADAT</h4>
        <div>Departemen Bahasa · PAH Mataram</div>
    </div>

    <table class="mb-3" style="border:0">
        <tr style="border:0">
            <td style="border:0;width:120px">Periode</td><td style="border:0">: {{ $record->title }}</td>
            <td style="border:0;width:120px">Tahun Ajaran</td><td style="border:0">: {{ $record->academicYear?->name ?? '-' }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width:38px">No</th>
                <th>Nama Santri</th>
                <th style="width:12%">Kelas</th>
                <th style="width:14%">Jumlah Mufradat</th>
                <th style="width:12%">Nilai Rata-rata</th>
                <th>Rencana Tindak Lanjut</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row['student']?->name ?? '-' }}</td>
                    <td>{{ $row['student']?->currentClassHistory?->studyGroup?->name ?? '-' }}</td>
                    <td class="text-center">{{ $row['total_words'] }}</td>
                    <td class="text-center">{{ number_format($row['average'], 1, ',', '.') }}%</td>
                    <td>{{ $row['follow_up'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-3" style="font-size:12px">
        <div><strong>Nama Mustami':</strong> ______________________________</div>
        <div class="mt-2">Tindakan: ☐ Pengulangan Hafalan &nbsp; ☐ Pembinaan Intensif &nbsp; ☐ Tidak Ada</div>
    </div>

    <div class="row mt-5" style="font-size:12px">
        <div class="col-6 text-center">
            <div>Kepala Departemen Bahasa</div>
            <div style="height:70px"></div>
            <div>__________________________</div>
        </div>
        <div class="col-6 text-center">
            <div>Mustami'</div>
            <div style="height:70px"></div>
            <div>__________________________</div>
        </div>
    </div>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">Cetak</button>
    </div>
</body>
</html>
