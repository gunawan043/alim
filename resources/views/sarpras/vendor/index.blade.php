@extends('layouts.master')
@section('title') Vendor & Mitra @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sarana Prasarana @endslot
        @slot('title') Vendor &amp; Mitra
    @endcomponent

    <x-sarpras-stats :items="[
        ['label'=>'Total Vendor','value'=>$stats['total'],'tone'=>'primary','icon'=>'ri-shop-line'],
        ['label'=>'Vendor Aktif','value'=>$stats['active'],'tone'=>'success','icon'=>'ri-checkbox-circle-line'],
        ['label'=>'Blacklist','value'=>$stats['blacklist'],'tone'=>'danger','icon'=>'ri-forbid-line'],
        ['label'=>'Rating Rata-rata','value'=>$stats['rating'],'tone'=>'warning','icon'=>'ri-star-line'],
    ]" />

    <x-sarpras-filter-card :action="route('sarpras.vendor.index')" :reset="route('sarpras.vendor.index')">
        <div class="col-md-4"><label class="form-label small fw-semibold">Cari</label><input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nama / kode / NPWP…"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Semua</option>@foreach(['active'=>'Aktif','inactive'=>'Tidak Aktif','blacklist'=>'Blacklist'] as $k=>$v)<option value="{{ $k }}" {{ request('status')===$k?'selected':'' }}>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Kategori</label><select name="category_id" class="form-select"><option value="">Semua</option>@foreach($categories as $cat)<option value="{{ $cat->id }}" {{ request('category_id')==$cat->id?'selected':'' }}>{{ $cat->name }}</option>@endforeach</select></div>
    </x-sarpras-filter-card>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-shop-line me-2 text-primary"></i>Vendor &amp; Mitra Eksternal</h5><p class="text-muted small mb-0">Data kontak, status kerja sama, dan rating vendor.</p></div>
            <div class="d-flex gap-2"><a href="{{ route('sarpras.vendor.rank') }}" class="btn btn-sm btn-outline-warning"><i class="ri-trophy-line me-1"></i>Ranking</a><a href="{{ route('sarpras.vendor.create') }}" class="btn btn-sm btn-success"><i class="ri-add-line me-1"></i>Tambah Vendor</a></div>
        </div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>No</th><th>Kode</th><th>Nama Vendor</th><th>Kategori</th><th>Kontak</th><th>Status</th><th class="text-center">Rating</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($vendors as $i => $vendor)
                @php $statusColor = ['active'=>'success','inactive'=>'secondary','blacklist'=>'danger'][$vendor->status] ?? 'info'; @endphp
                <tr>
                    <td>{{ $vendors->firstItem() + $i }}</td>
                    <td><code>{{ $vendor->vendor_code ?? '-' }}</code></td>
                    <td><div class="fw-medium">{{ $vendor->name }}</div><small class="text-muted">{{ $vendor->vendor_type }}</small></td>
                    <td class="small">{{ $vendor->category?->name ?? '-' }}</td>
                    <td class="small">{{ $vendor->phone ?? '-' }}<div class="text-muted">{{ $vendor->email }}</div></td>
                    <td><span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">{{ ucfirst($vendor->status) }}</span></td>
                    <td class="text-center">@if($vendor->rating_count > 0)<span class="text-warning">★ {{ number_format($vendor->rating_avg,1) }}</span><div class="small text-muted">{{ $vendor->rating_count }} ulasan</div>@else - @endif</td>
                    <td class="text-end"><div class="d-inline-flex gap-1"><a href="{{ route('sarpras.vendor.show', ['id' => $vendor->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a><a href="{{ route('sarpras.vendor.edit', ['id' => $vendor->id]) }}" class="btn btn-sm btn-outline-warning"><i class="ri-pencil-line"></i></a></div></td>
                </tr>
            @empty
                <x-empty-state colspan="8" title="Belum ada vendor" message="Tambahkan vendor untuk mulai mengelola pengadaan dan kerja sama."><a href="{{ route('sarpras.vendor.create') }}" class="btn btn-success mt-3"><i class="ri-add-line me-1"></i>Tambah Vendor</a></x-empty-state>
            @endforelse
        </tbody></table></div></div>
        @if($vendors->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $vendors])</div>@endif
    </div>
@endsection
