<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Kartu Inventaris Ruangan</title>
<style>
    body { font-family: Arial, sans-serif; font-size: 9px; color: #111; }
    h2 { text-align: center; font-size: 15px; margin: 0 0 2px; }
    .sub { text-align: center; font-size: 9px; color: #555; margin-bottom: 12px; }
    .room { margin-bottom: 16px; page-break-inside: avoid; }
    .room-head { background: #e8eef8; border: 1px solid #444; padding: 6px 8px; }
    .room-head strong { font-size: 11px; }
    .meta { font-size: 8px; color: #444; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #444; padding: 3px 5px; vertical-align: top; }
    th { background: #f0f0f0; text-align: center; font-size: 8px; }
    .text-center { text-align: center; }
    .text-end { text-align: right; }
    .sign { margin-top: 24px; display: flex; justify-content: space-between; font-size: 9px; }
</style>
</head>
<body>
<h2>KARTU INVENTARIS RUANGAN (KIR)</h2>
<div class="sub">{{ config('app.name') }} — {{ $workUnit?->name ?? 'Semua Satuan Kerja' }} — dicetak {{ now()->format('d/m/Y H:i') }}</div>

@forelse($rooms as $room)
    <div class="room">
        <div class="room-head">
            <strong>LOKASI: {{ $room->building?->building_name ?? '-' }} — {{ $room->room_name }}</strong>
            <div class="meta">Satuan Kerja: {{ $room->workUnit?->name ?? '-' }} · PIC Ruang: {{ $room->responsibleUser?->name ?? $room->workUnit?->primarySarprasOfficer?->user?->name ?? '-' }}</div>
        </div>
        <table>
            <thead><tr><th style="width:25px">No Urut</th><th>Jenis Barang / Nama Barang</th><th>Merk</th><th>Tahun Pembelian</th><th>No Kode Barang</th><th>Jumlah / Register</th><th>Baik (B)</th><th>Kurang Baik (KB)</th><th>Rusak Berat (RB)</th><th>Keterangan</th></tr></thead>
            <tbody>
            @forelse($room->groupedAssets as $index => $group)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $group['asset']->asset_name }}<br><span style="color:#666">{{ $group['asset']->category?->inventory_group ? $group['asset']->category->inventory_group.' · ' : '' }}{{ $group['asset']->category?->name }}</span></td>
                    <td>{{ $group['asset']->brand ?? '-' }}</td>
                    <td class="text-center">{{ $group['asset']->acquisition_year ?? $group['asset']->acquisition_date?->format('Y') ?? '-' }}</td>
                    <td>{{ $group['asset']->asset_code }}</td>
                    <td class="text-end">{{ number_format($group['quantity']) }} {{ $group['asset']->unit }}</td>
                    <td class="text-center">{{ number_format($group['baik']) }}</td>
                    <td class="text-center">{{ number_format($group['kurang_baik']) }}</td>
                    <td class="text-center">{{ number_format($group['rusak_berat']) }}</td>
                    <td>{{ $group['notes'] ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center">Belum ada aset pada ruang ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@empty
    <p class="text-center">Tidak ada ruang pada filter ini.</p>
@endforelse

<div class="sign">
    <div>Mengetahui,<br>Kepala Unit Rumah Tangga<br><br><br>__________________________</div>
    <div>Petugas Inventaris,<br><br><br><br>__________________________</div>
</div>
</body>
</html>
