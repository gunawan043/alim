@extends('layouts.sarpras')

@section('title', 'Manajemen Sparepart')

@section('content')
<div class="card">
    <div class="card-header bg-gradient-primary text-white d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Inventori Sparepart</h5>
        <div>
            <a href="{{ route('sarpras.sparepart.low-stock') }}" class="btn btn-warning btn-sm">
                <i class="bi bi-exclamation-triangle me-1"></i>Stok Rendah
            </a>
            <a href="{{ route('sarpras.sparepart.dead-stock') }}" class="btn btn-outline-light btn-sm me-1">
                Dead Stock
            </a>
            <a href="{{ route('sarpras.sparepart.create') }}" class="btn btn-light btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Tambah Sparepart
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 mb-3">
            <div class="col-md-3">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari kode / nama / barcode">
            </div>
            <div class="col-md-2">
                <select name="category_id" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="warehouse_id" class="form-select">
                    <option value="">Semua Gudang</option>
                    @foreach ($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                            {{ $wh->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="low_stock" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('low_stock') == '1' ? 'selected' : '' }}>Stok Rendah</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Harga</th>
                        <th>Rmin/Rpt</th>
                        <th>Gudang</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($spareparts as $sp)
                        @php $needsReorder = $sp->needsReorder(); @endphp
                        <tr class="{{ $sp->isLowStock() ? 'table-warning' : '' }}">
                            <td><code>{{ $sp->part_number }}</code></td>
                            <td>
                                {{ $sp->name }}
                                @if($sp->brand)
                                <br><small class="text-muted">{{ $sp->brand }}</small>
                                @endif
                            </td>
                            <td>{{ $sp->category?->name ?? '-' }}</td>
                            <td>
                                <div>{{ number_format((float)$sp->stock, 2, ',', '.') }} {{ $sp->unit?->symbol ?? '' }}</div>
                            </td>
                            <td>Rp {{ number_format((float) $sp->unit_price, 0, ',', '.') }}</td>
                            <td>
                                <small>Min: {{ $sp->min_stock }}<br>Rpt: {{ $sp->reorder_point }}</small>
                            </td>
                            <td>{{ $sp->warehouse?->name ?? '-' }}</td>
                            <td>
                                @if ($sp->isLowStock())
                                    <span class="badge bg-warning">Stok Rendah</span>
                                @elseif ($needsReorder)
                                    <span class="badge bg-info">Butuh Reorder</span>
                                @else
                                    <span class="badge bg-success">Aman</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('sarpras.sparepart.show', $sp) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center py-4">Tidak ada data sparepart</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $spareparts->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection