@extends('layouts.master')
@section('title') Aset Saya @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">Sarana Prasarana</a> @endslot
        @slot('title') Aset Saya
    @endcomponent

    <x-sarpras-stats :items="[
        ['label'=>'Total Aset','value'=>$stats['total'],'tone'=>'primary','icon'=>'ri-archive-2-line'],
        ['label'=>'Kondisi Baik','value'=>$stats['good'],'tone'=>'success','icon'=>'ri-checkbox-circle-line'],
        ['label'=>'Perlu Perhatian','value'=>$stats['damaged'],'tone'=>'warning','icon'=>'ri-error-warning-line'],
        ['label'=>'Nilai Perolehan','value'=>'Rp '.number_format($stats['value'],0,',','.'),'tone'=>'info','icon'=>'ri-money-dollar-circle-line'],
    ]" />

    <x-sarpras-filter-card :action="route('sarpras.user.aset.index', ['userId' => $userId])" :reset="route('sarpras.user.aset.index', ['userId' => $userId])">
        <div class="col-md-4"><label class="form-label small fw-semibold">Cari Aset</label><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama / kode / merk…"></div>
        <div class="col-md-3"><label class="form-label small fw-semibold">Kategori</label><select name="category_id" class="form-select"><option value="">Semua</option>@foreach($categories as $category)<option value="{{ $category->id }}" {{ request('category_id') === $category->id ? 'selected' : '' }}>{{ $category->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label small fw-semibold">Kondisi</label><select name="condition" class="form-select"><option value="">Semua</option>@foreach(['baik'=>'Baik','rusak_ringan'=>'Rusak Ringan','rusak_sedang'=>'Rusak Sedang','rusak_berat'=>'Rusak Berat'] as $key=>$label)<option value="{{ $key }}" {{ request('condition') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
    </x-sarpras-filter-card>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-archive-2-line me-2 text-primary"></i>Daftar Aset</h5><p class="text-muted small mb-0">Aset yang menjadi tanggung jawab Anda atau unit kerja.</p></div>
            <div class="hstack gap-2"><a href="{{ route('sarpras.user.aset.import', ['userId' => $userId]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-upload-cloud-2-line me-1"></i>Import</a><a href="{{ route('sarpras.user.aset.create', ['userId' => $userId]) }}" class="btn btn-sm btn-success"><i class="ri-add-line me-1"></i>Tambah Aset</a></div>
        </div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>No</th><th>Nama Aset</th><th>Kode</th><th>Kategori</th><th>Ruang</th><th>Kondisi</th><th class="text-end">Nilai</th><th class="text-end">Aksi</th></tr></thead><tbody>
            @forelse($allAssets as $i => $asset)
                @php $tone = ['baik'=>'success','rusak_ringan'=>'warning','rusak_sedang'=>'warning','rusak_berat'=>'danger','hilang'=>'secondary'][$asset->condition] ?? 'secondary'; @endphp
                <tr>
                    <td>{{ $allAssets->firstItem() + $i }}</td>
                    <td><a href="{{ route('sarpras.user.aset.show', ['userId' => $userId, 'id' => $asset->id]) }}" class="fw-semibold link-primary">{{ $asset->asset_name }}</a>@if($asset->brand)<div class="small text-muted">{{ $asset->brand }}{{ $asset->model ? ' — '.$asset->model : '' }}</div>@endif</td>
                    <td><code class="small">{{ $asset->asset_code ?? '-' }}</code></td>
                    <td><span class="badge bg-info-subtle text-info">{{ $asset->category?->name ?? '-' }}</span></td>
                    <td class="small">{{ $asset->room?->room_name ?? '-' }}@if($asset->room?->building)<div class="text-muted">{{ $asset->room->building->building_name }}</div>@endif</td>
                    <td><span class="badge bg-{{ $tone }}-subtle text-{{ $tone }}">{{ ucfirst(str_replace('_',' ',$asset->condition)) }}</span></td>
                    <td class="text-end small">{{ $asset->acquisition_price ? number_format($asset->acquisition_price,0,',','.') : '-' }}</td>
                    <td class="text-end"><div class="d-inline-flex gap-1"><a href="{{ route('sarpras.user.aset.show', ['userId' => $userId, 'id' => $asset->id]) }}" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line"></i></a><a href="{{ route('sarpras.user.aset.edit', ['userId' => $userId, 'id' => $asset->id]) }}" class="btn btn-sm btn-outline-warning"><i class="ri-pencil-line"></i></a><form action="{{ route('sarpras.user.aset.destroy', ['userId' => $userId, 'id' => $asset->id]) }}" method="POST" data-confirm="Hapus aset ini?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button></form></div></td>
                </tr>
            @empty
                <x-empty-state colspan="8" title="Belum ada aset" message="Tambahkan aset atau import dari Excel untuk memulai."><a href="{{ route('sarpras.user.aset.create', ['userId' => $userId]) }}" class="btn btn-success mt-3"><i class="ri-add-line me-1"></i>Tambah Aset</a></x-empty-state>
            @endforelse
        </tbody></table></div></div>
        @if($allAssets->hasPages())<div class="card-footer">@include('shared._pagination', ['paginator' => $allAssets])</div>@endif
    </div>
@endsection
