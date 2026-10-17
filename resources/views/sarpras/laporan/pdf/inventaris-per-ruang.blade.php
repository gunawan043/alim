<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Inventaris Per Ruang — {{ date('d/m/Y') }}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Arial', sans-serif; font-size: 12px; color: #222; }
.header { text-align: center; border-bottom: 2px solid #333; padding: 16px; margin-bottom: 20px; }
.header h2 { font-size: 16px; font-weight: 700; }
.header p { font-size: 10px; color: #666; }
.info-row { display: flex; justify-content: space-between; margin-bottom: 16px; padding: 0 16px; }
.info-box { border: 1px solid #ddd; padding: 8px 12px; border-radius: 4px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #333; padding: 6px 8px; }
th { background: #f0f0f0; font-weight: 700; font-size: 11px; text-align: left; }
tr:nth-child(even) { background: #fafafa; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.footer { margin-top: 20px; padding: 12px 16px; font-size: 10px; color: #666; border-top: 1px solid #ddd; display: flex; justify-content: space-between; }
.badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 10px; }
.badge-ruang { background: #e3f2fd; color: #1565c0; }
.badge-gedung { background: #f3e5f5; color: #7b1fa2; }
</style>
</head>
<body>
<div class="header">
    <h2>LAPORAN INVENTARIS PER RUANG</h2>
    <p>{{ config('app.name') ?? 'ALIM Alim' }} — Dicetak {{ date('d F Y, H:i') }}</p>
</div>

<div class="info-row">
    <div class="info-box">
        <strong>Total Ruangan:</strong> {{ $rooms->count() }}<br>
        <strong>Total Aset:</strong> {{ number_format($rooms->sum(fn($r) => $r->assets_count)) }}
    </div>
    <div class="info-box">
        <strong>Total Nilai Perolehan:</strong><br>
        Rp {{ number_format($rooms->sum(fn($r) => $r->assets->sum('acquisition_price')), 0, ',', '.') }}
    </div>
</div>

<table>
    <thead>
        <tr>
            <th class="text-center" style="width:30px">#</th>
            <th>Nama Ruang</th>
            <th>Gedung</th>
            <th>Tipe</th>
            <th>Lantai</th>
            <th class="text-center">Jumlah Aset</th>
            <th class="text-right">Total Nilai (Rp)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rooms as $room)
        <tr>
            <td class="text-center">{{ $loop->iteration }}</td>
            <td><strong>{{ $room->room_name }}</strong></td>
            <td>{{ $room->building?->building_name ?? '-' }}</td>
            <td>
                <span class="badge badge-ruang">{{ ucfirst($room->room_type ?? '-') }}</span>
            </td>
            <td>{{ $room->floor ?? '-' }}</td>
            <td class="text-center">{{ $room->assets_count }}</td>
            <td class="text-right">{{ number_format($room->assets->sum('acquisition_price'), 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center">Tidak ada data ruangan.</td>
        </tr>
        @endforelse
    </tbody>
    @if($rooms->isNotEmpty())
    <tfoot style="background:#f5f5f5;font-weight:700">
        <tr>
            <td colspan="5" class="text-right">Total Keseluruhan:</td>
            <td class="text-center">{{ $rooms->sum(fn($r) => $r->assets_count) }}</td>
            <td class="text-right">Rp {{ number_format($rooms->sum(fn($r) => $r->assets->sum('acquisition_price')), 0, ',', '.') }}</td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="footer">
    <span>Dicetak oleh: {{ auth()->check() ? auth()->user()->name : 'System' }}</span>
    <span>Halaman 1 dari 1</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>