@extends('layouts.sarpras')

@section('title', 'Stok Mati / Dead Stock')

@section('content')
<div class="card border-secondary">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="bi bi-archive me-2"></i>Dead Stock Monitor</h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">Sparepart dengan 0 pergerakan stok dalam 90 hari terakhir.</p>
        <table class="table table-hover align-middle">
            <thead class="table-secondary">
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Saat Ini</th>
                    <th>Min Level</th>
                    <th>Last Movement</th>
                    <th>Budget Terkunci</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td><code>{{ $item->sparepart_code }}</code></td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->qty_on_hand }}</td>
                        <td>{{ $item->min_stock_level }}</td>
                        <td>{{ optional($item->last_movement_date)->format('d M Y') ?? 'Never' }}</td>
                        <td>Rp {{ number_format($item->unit_price * $item->qty_on_hand, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-success py-4">Tidak ada dead stock.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection