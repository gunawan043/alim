@extends('layouts.sarpras')

@section('title', 'Stok Rendah')

@section('content')
<div class="card border-warning">
    <div class="card-header bg-warning">
        <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Sparepart dengan Stok Rendah</h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">Sparepart yang saat ini berada di bawah minimum stock level.</p>
        <table class="table table-hover align-middle">
            <thead class="table-warning">
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Saat Ini</th>
                    <th>Minimum</th>
                    <th>Reorder</th>
                    <th>Satuan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr class="{{ $item->stock <= ($item->stock - $item->qty_reserved) ? 'table-danger' : '' }}">
                        <td><code>{{ $item->sparepart_code }}</code></td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->category?->name ?? '-' }}</td>
                        <td>
                            <strong class="{{ $item->qty_on_hand <= $item->reorder_level ? 'text-danger' : 'text-warning' }}">
                                {{ $item->qty_on_hand }}
                            </strong>
                        </td>
                        <td>{{ $item->min_stock_level }}</td>
                        <td>{{ $item->reorder_level }}</td>
                        <td>{{ $item->uom }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-success py-4">Semua sparepart dalam batas aman.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection