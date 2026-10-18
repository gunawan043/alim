@extends('layouts.master')
@section('title') Stok Mati @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.sparepart.index') }}">Sparepart</a> @endslot
        @slot('title') Stok Mati
    @endcomponent

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center"><div><h5 class="card-title mb-0"><i class="ri-archive-2-line me-2 text-secondary"></i>Dead Stock</h5><p class="text-muted small mb-0">Tidak ada pergerakan stok dalam 6 bulan terakhir.</p></div><a href="{{ route('sarpras.sparepart.index') }}" class="btn btn-light btn-sm">Kembali</a></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Stok</th><th>Nilai</th><th>Gudang</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($items as $item)
                <tr>
                    <td><code>{{ $item->part_number }}</code></td>
                    <td><div class="fw-medium">{{ $item->name }}</div>@if($item->brand)<small class="text-muted">{{ $item->brand }}</small>@endif</td>
                    <td class="small">{{ $item->category?->name ?? '-' }}</td>
                    <td>{{ number_format((float) $item->stock, 2, ',', '.') }} <small class="text-muted">{{ $item->unit?->symbol }}</small></td>
                    <td>Rp {{ number_format((float) $item->stock * (float) $item->unit_price, 0, ',', '.') }}</td>
                    <td class="small">{{ $item->warehouse?->name ?? '-' }}</td>
                    <td class="text-end"><a href="{{ route('sarpras.sparepart.show', ['id' => $item]) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                </tr>
            @empty
                <x-empty-state colspan="7" title="Tidak ada dead stock" message="Semua sparepart memiliki pergerakan dalam 6 bulan terakhir." />
            @endforelse
        </tbody></table></div></div>
    </div>
@endsection
