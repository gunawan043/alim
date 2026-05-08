<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Poin Pelanggaran</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; margin: 20px; color: #222; }
        h1 { font-size: 18px; text-align: center; margin-bottom: 4px; color: #111; }
        .subtitle { text-align: center; color: #666; font-size: 11px; margin-bottom: 20px; }
        .meta { margin-bottom: 20px; }
        .meta td { padding: 2px 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f06548; color: #fff; padding: 6px 8px; text-align: left; font-size: 11px; }
        td { padding: 5px 8px; border-bottom: 1px solid #eee; vertical-align: top; }
        tr:nth-child(even) td { background: #fafafa; }
        .text-center { text-align: center; }
        .badge-red { background: #f06548; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
        .footer { margin-top: 30px; font-size: 10px; color: #999; text-align: right; }
        .summary-box { background: #f5f5f5; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .summary-box table { margin-bottom: 0; }
        .summary-box td { padding: 4px 12px; border: none; }
        .summary-box td:first-child { font-weight: bold; }
    </style>
</head>
<body>

<h1>DAFTAR POIN PELANGGARAN</h1>
<p class="subtitle">Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB</p>

<div class="summary-box">
    <table>
        <tr>
            <td>Total Pelanggaran:</td>
            <td><strong>{{ $violations->count() }}</strong></td>
            <td>Total Poin:</td>
            <td><strong class="badge-red">{{ $totalPoints }}</strong></td>
        </tr>
    </table>
</div>

<table>
    <thead>
        <tr>
            <th style="width:4%">No</th>
            <th style="width:10%">Tanggal</th>
            <th style="width:22%">Nama Siswa</th>
            <th style="width:15%">Rombel</th>
            <th style="width:22%">Jenis Pelanggaran</th>
            <th style="width:5%" class="text-center">Poin</th>
            <th style="width:22%">Tindakan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($violations as $i => $v)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $v->violation_date->format('d/m/Y') }}</td>
                <td>{{ $v->student?->name ?? '-' }}<br><small style="color:#888">{{ $v->student?->nisn ?? '' }}</small></td>
                <td>{{ $v->studyGroup?->full_name ?? '-' }}</td>
                <td>{{ $v->violation_type }}</td>
                <td class="text-center"><span class="badge-red">{{ $v->points }}</span></td>
                <td>{{ $v->action_taken ?: '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center" style="padding:20px; color:#999;">Tidak ada data pelanggaran.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    ALIM PUSTIK — Sistem Informasi Akademik Madrasah
</div>

</body>
</html>
