<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Buku Induk Inventaris Aset</title>
<style>
    body { font-family: Arial, sans-serif; font-size: 8px; color: #111; }
    h2 { text-align: center; font-size: 14px; margin: 0 0 2px; }
    .sub { text-align: center; font-size: 9px; color: #555; margin-bottom: 10px; }
    .meta { margin-bottom: 8px; font-size: 9px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #444; padding: 3px 4px; vertical-align: top; }
    th { background: #e8eef8; font-size: 8px; text-align: center; }
    tr:nth-child(even) td { background: #fafafa; }
    .text-end { text-align: right; }
    .text-center { text-align: center; }
    .footer { margin-top: 14px; font-size: 8px; color: #555; }
</style>
</head>
<body>
<h2>BUKU INDUK INVENTARIS ASET</h2>
<div class="sub">{{ config('app.name') }} — dicetak {{ now()->format('d/m/Y H:i') }}</div>
<div class="meta">
    Satuan Kerja: <strong>{{ $workUnit?->name ?? 'Semua Satuan Kerja' }}</strong>
</div>

<table>
    <thead>
        <tr>
            <th style="width:22px">No</th>
            <th>Satuan Kerja</th>
            <th>Ruangan</th>
            <th>Jenis Barang</th>
            <th>Merk</th>
            <th>Tgl Pembukuan / Pembelian</th>
            <th>No Urut Satker</th>
            <th>Kode Aset</th>
            <th>Kuantitas</th>
            <th>Satuan</th>
            <th>Sumber Perolehan</th>
            <th>Keadaan Aset</th>
            <th>Harga Perolehan</th>
            <th>Masa Pakai</th>
            <th>Harga Penyusutan</th>
            <th>Penanggung Jawab</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
    @forelse($assets as $index => $asset)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td>{{ $asset->workUnit?->name ?? '-' }}</td>
            <td>{{ $asset->room?->building?->building_name ? $asset->room->building->building_name.' — ' : '' }}{{ $asset->room?->room_name ?? '-' }}</td>
            <td>{{ $asset->asset_name }}<br><span style="color:#666">{{ $asset->category?->inventory_group ? $asset->category->inventory_group.' · ' : '' }}{{ $asset->category?->name }}</span></td>
            <td>{{ $asset->brand ?? '-' }}</td>
            <td>{{ $asset->acquisition_date?->format('d/m/Y') ?? $asset->acquisition_year ?? '-' }}</td>
            <td class="text-center">{{ $asset->work_unit_sequence ?? '-' }}</td>
            <td>{{ $asset->asset_code }}</td>
            <td class="text-center">{{ number_format($asset->quantity) }}</td>
            <td>{{ $asset->unit }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', $asset->acquisition_source ?? '-')) }}</td>
            <td class="text-center">{{ $asset->kir_condition }}</td>
            <td class="text-end">{{ $asset->acquisition_price ? number_format($asset->acquisition_price, 0, ',', '.') : '-' }}</td>
            <td class="text-center">{{ $asset->masa_pakai ?: '-' }} th</td>
            <td class="text-end">{{ $asset->harga_penyusutan ? number_format($asset->harga_penyusutan, 0, ',', '.') : '-' }}</td>
            <td>{{ $asset->room?->responsibleUser?->name ?? $asset->workUnit?->primarySarprasOfficer?->user?->name ?? '-' }}</td>
            <td>{{ $asset->notes ?? '' }}</td>
        </tr>
    @empty
        <tr><td colspan="17" class="text-center">Tidak ada data inventaris.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="footer">Jumlah baris: {{ $assets->count() }} · Total kuantitas: {{ number_format($assets->sum('quantity')) }} · Total perolehan: Rp {{ number_format($assets->sum('acquisition_price'), 0, ',', '.') }}</div>
</body>
</html>
