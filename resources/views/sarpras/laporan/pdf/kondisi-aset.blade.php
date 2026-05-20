<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Kondisi Aset — {{ date('d/m/Y') }}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Arial', sans-serif; font-size: 11px; color: #222; }
.header { text-align: center; border-bottom: 2px solid #333; padding: 16px; margin-bottom: 16px; }
.header h2 { font-size: 16px; font-weight: 700; }
.header p { font-size: 10px; color: #666; }
.summary { display: flex; gap: 8px; justify-content: center; margin-bottom: 16px; flex-wrap: wrap; }
.summary-item { border: 1px solid #ddd; padding: 8px 16px; border-radius: 4px; text-align: center; min-width: 100px; }
.summary-item strong { display: block; font-size: 18px; }
.summary-item span { font-size: 9px; color: #666; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #333; padding: 5px 7px; }
th { background: #f0f0f0; font-weight: 700; font-size: 10px; }
tr:nth-child(even) { background: #fafafa; }
.badge { padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: 700; color: white; }
.baik { background: #4caf50; }
.rusak_ringan { background: #ff9800; }
.rusak_sedang { background: #f57c00; }
.rusak_berat { background: #e53935; }
.hilang { background: #9e9e9e; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.footer { margin-top: 16px; padding: 8px 16px; font-size: 9px; color: #666; border-top: 1px solid #ddd; display: flex; justify-content: space-between; }
</style>
</head>
<body>
<div class="header">
    <h2>LAPORAN KONDISI ASET</h2>
    <p>{{ config('app.name') ?? 'ALIM PUSTIK' }} — Dicetak {{ date('d F Y, H:i') }}</p>
</div>

<div class="summary">
    <div class="summary-item"><strong class="baik">{{ $summary['baik'] }}</strong><span>Baik</span></div>
    <div class="summary-item"><strong class="rusak_ringan">{{ $summary['rusak_ringan'] }}</strong><span>Rusak Ringan</span></div>
    <div class="summary-item"><strong class="rusak_sedang">{{ $summary['rusak_sedang'] }}</strong><span>Rusak Sedang</span></div>
    <div class="summary-item"><strong class="rusak_berat">{{ $summary['rusak_berat'] }}</strong><span>Rusak Berat</span></div>
    <div class="summary-item"><strong class="hilang">{{ $summary['hilang'] }}</strong><span>Hilang</span></div>
    <div class="summary-item"><strong>{{ $summary['total'] }}</strong><span>Total</span></div>
</div>

<table>
    <thead>
        <tr>
            <th class="text-center" style="width:25px">#</th>
            <th>Nama Aset</th>
            <th>Kode</th>
            <th>Kategori</th>
            <th>Ruang</th>
            <th>Gedung</th>
            <th class="text-center">Kondisi</th>
        </tr>
    </thead>
    <tbody>
        @forelse($assets as $a)
        <tr>
            <td class="text-center">{{ $loop->iteration }}</td>
            <td><strong>{{ $a->asset_name }}</strong></td>
            <td>{{ $a->asset_code ?? '-' }}</td>
            <td>{{ $a->category?->name ?? '-' }}</td>
            <td>{{ $a->room?->room_name ?? '-' }}</td>
            <td>{{ $a->room?->building?->building_name ?? '-' }}</td>
            <td class="text-center">
                <span class="badge {{ $a->condition }}">{{ ucfirst(str_replace('_',' ', $a->condition)) }}</span>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center">Tidak ada data aset.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    <span>Dicetak oleh: {{ auth()->check() ? auth()->user()->name : 'System' }}</span>
    <span>Halaman 1 dari 1</span>
    <span>{{ now()->format('d/m/Y H:i') }}</span>
</div>
</body>
</html>