@extends('layouts.master')
@section('title') Inventori Sparepart @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Inventori Sparepart
    @endcomponent

    <x-sarpras-stats :items="[
        ['label'=>'Total Sparepart','value'=>$stats['total'],'tone'=>'primary','icon'=>'ri-tools-line'],
        ['label'=>'Stok Rendah','value'=>$stats['low'],'tone'=>'warning','icon'=>'ri-alert-line','href'=>route('sarpras.sparepart.low-stock')],
        ['label'=>'Dead Stock','value'=>$stats['dead'],'tone'=>'secondary','icon'=>'ri-archive-2-line','href'=>route('sarpras.sparepart.dead-stock')],
        ['label'=>'Nilai Persediaan','value'=>'Rp '.number_format($stats['value'],0,',','.'),'tone'=>'info','icon'=>'ri-money-dollar-circle-line'],
    ]" />

    <x-sarpras-filter-card :action="route('sarpras.sparepart.index')" :reset="route('sarpras.sparepart.index')">
        <div class="col-md-4"><label class="form-label small fw-semibold">Cari</label><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Part number / nama / barcode"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Kategori</label><select name="category_id" class="form-select"><option value="">Semua</option>@foreach($categories as $cat)<option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Gudang</label><select name="warehouse_id" class="form-select"><option value="">Semua</option>@foreach($warehouses as $wh)<option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>@endforeach</select></div>
    </x-sarpras-filter-card>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-tools-line me-2 text-primary"></i>Daftar Sparepart</h5><p class="text-muted small mb-0">Stok, harga, dan status reorder sparepart.</p></div>
            <div class="d-flex gap-2"><a href="{{ route('sarpras.sparepart.low-stock') }}" class="btn btn-sm btn-outline-warning"><i class="ri-alert-line me-1"></i>Stok Rendah</a><a href="{{ route('sarpras.sparepart.create') }}" class="btn btn-sm btn-success"><i class="ri-add-line me-1"></i>Tambah</a></div>
        </div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>No</th><th>Part Number</th><th>Nama</th><th>Kategori</th><th class="text-end">Stok</th><th class="text-end">Min / Reorder</th><th class="text-end">Harga</th><th>Gudang</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($spareparts as $i => $sp)
                @php $low = (float) $sp->stock <= (float) $sp->reorder_point; @endphp
                <tr class="{{ $low ? 'table-warning' : '' }}">
                    <td>{{ $spareparts->firstItem() + $i }}</td>
                    <td><code>{{ $sp->part_number }}</code></td>
                    <td><div class="fw-medium">{{ $sp->name }}</div>@if($sp->brand)<small class="text-muted">{{ $sp->brand }}</small>@endif</td>
                    <td class="small">{{ $sp->category?->name ?? '-' }}</td>
                    <td class="text-end">{{ number_format((float) $sp->stock, 2, ',', '.') }} <small class="text-muted">{{ $sp->unit?->symbol }}</small></td>
                    <td class="text-end small">{{ number_format((float) $sp->min_stock, 2, ',', '.') }} / {{ number_format((float) $sp->reorder_point, 2, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format((float) $sp->unit_price, 0, ',', '.') }}</td>
                    <td class="small">{{ $sp->warehouse?->name ?? '-' }}</td>
                    <td>@if($low)<span class="badge bg-warning-subtle text-warning">Stok Rendah</span>@else<span class="badge bg-success-subtle text-success">Aman</span>@endif</td>
                    <td class="text-end"><div class="d-inline-flex gap-1"><a href="{{ route('sarpras.sparepart.show', ['id' => $sp]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a><a href="{{ route('sarpras.sparepart.edit', ['id' => $sp]) }}" class="btn btn-sm btn-outline-warning"><i class="ri-pencil-line"></i></a></div></td>
                </tr>
            @empty
                <x-empty-state colspan="10" title="Belum ada sparepart" message="Tambahkan sparepart untuk mulai mengelola persediaan."><a href="{{ route('sarpras.sparepart.create') }}" class="btn btn-success mt-3"><i class="ri-add-line me-1"></i>Tambah Sparepart</a></x-empty-state>
            @endforelse
        </tbody></table></div></div>
        @if($spareparts->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $spareparts])</div>@endif
    </div>
@endsection
