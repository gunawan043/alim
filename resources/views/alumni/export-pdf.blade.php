<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Data Alumni</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #222; }
        h2 { text-align: center; margin: 0 0 2px; }
        .sub { text-align: center; color: #666; margin-bottom: 12px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 4px 6px; text-align: left; }
        th { background: #f1f5f9; font-size: 10px; }
        tr:nth-child(even) td { background: #fafafa; }
        .muted { color: #777; }
    </style>
</head>
<body>
    <h2>Data Alumni</h2>
    <div class="sub">Dicetak {{ $date }} · {{ $alumni->count() }} data</div>

    <table>
        <thead>
            <tr>
                <th style="width:26px">No</th>
                <th>Nama</th>
                <th>NISN</th>
                <th>Satuan Pendidikan</th>
                <th>Tahun Lulus</th>
                <th>No. Ijazah</th>
                <th>Status Tracer</th>
            </tr>
        </thead>
        <tbody>
            @forelse($alumni as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->student?->name ?? '-' }}</td>
                    <td>{{ $row->student?->nisn ?? '-' }}</td>
                    <td>{{ $row->school?->name ?? '-' }}</td>
                    <td>{{ $row->graduation_year ?? '-' }}</td>
                    <td>{{ $row->graduation_certificate_number ?? '-' }}</td>
                    <td>{{ ucfirst($row->tracer_status ?? 'pending') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted" style="text-align:center">Belum ada data alumni.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
