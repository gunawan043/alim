<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Buku Peminjaman Barang</title>
<style>
body{font-family:Arial,sans-serif;font-size:8px;color:#111}h2{text-align:center;font-size:14px;margin:0 0 3px}.sub{text-align:center;font-size:9px;color:#555;margin-bottom:10px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #444;padding:3px 4px;vertical-align:top}th{background:#e8eef8;text-align:center;font-size:8px}tr:nth-child(even) td{background:#fafafa}.text-center{text-align:center}
</style></head><body>
<h2>BUKU PEMINJAMAN BARANG</h2><div class="sub">{{ config('app.name') }} — dicetak {{ now()->format('d/m/Y H:i') }}</div>
<table><thead><tr><th>No</th><th>Nama Peminjam</th><th>Divisi</th><th>Nama Barang</th><th>Kode Barang</th><th>Kondisi Saat Peminjaman</th><th>Hari/Tanggal</th><th>Batas Peminjaman</th><th>Paraf Peminjaman</th><th>Kondisi Saat Kembali</th><th>Hari/Tanggal Kembali</th><th>Paraf Pengembalian</th></tr></thead><tbody>
@forelse($loans as $i => $loan)
<tr>
<td class="text-center">{{ $i+1 }}</td><td>{{ $loan->borrower?->name ?? '-' }}</td><td>{{ $loan->borrower?->gtkWorkUnits?->first()?->workUnit?->name ?? '-' }}</td><td>{{ $loan->asset?->asset_name ?? '-' }}</td><td>{{ $loan->asset?->asset_code ?? '-' }}</td><td>{{ ucfirst(str_replace('_',' ', $loan->condition_on_loan ?? '-')) }}</td><td>{{ $loan->loan_date?->translatedFormat('l, d/m/Y') ?? '-' }}</td><td>{{ $loan->expected_return_date?->translatedFormat('l, d/m/Y') ?? '-' }}</td><td>{{ $loan->approver?->name ?? '-' }}</td><td>{{ ucfirst(str_replace('_',' ', $loan->condition_on_return ?? '-')) }}</td><td>{{ $loan->actual_return_date?->translatedFormat('l, d/m/Y') ?? '-' }}</td><td>{{ $loan->returnedToUser?->name ?? '-' }}</td>
</tr>
@empty<tr><td colspan="12" class="text-center">Belum ada data.</td></tr>@endforelse
</tbody></table>
</body></html>
