<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; font-size: 10px; }
        h1 { font-size: 18px; color: #005981; margin: 0 0 4px; }
        .meta { color: #64748b; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #005981; color: #fff; text-align: left; padding: 7px; }
        td { border-bottom: 1px solid #e2e8f0; padding: 6px 7px; vertical-align: top; }
        tr:nth-child(even) td { background: #f8fafc; }
        .footer { margin-top: 16px; color: #64748b; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">ALIM — Sistem Informasi Manajemen Pendidikan · Dicetak {{ now()->format('d/m/Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($headers) }}" style="text-align:center;padding:18px;color:#64748b">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Dicetak otomatis oleh ALIM. © {{ date('Y') }} ALIM.</div>
</body>
</html>
