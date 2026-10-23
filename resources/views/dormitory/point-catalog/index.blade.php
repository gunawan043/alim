@extends('layouts.master')
@section('title', 'Katalog Poin Asrama')
@section('content')
@component('components.breadcrumb')
    @slot('li_1') Asrama @endslot
    @slot('li_2') <a href="{{ route('user.asrama.show', ['userId'=>$userId,'asramaUuid'=>$dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
    @slot('title') Katalog Poin @endslot
@endcomponent
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="row">
<div class="col-xl-3"><div class="card stat-card"><div class="card-body py-3"><p class="stat-label mb-0">Aturan Ambang</p><h3 class="stat-value">{{ $rules->count() }}</h3></div></div></div>
<div class="col-xl-3"><div class="card stat-card"><div class="card-body py-3"><p class="stat-label mb-0">Katalog Aktif</p><h3 class="stat-value">{{ $catalogs->total() }}</h3></div></div></div>
<div class="col-xl-6"><div class="card"><div class="card-body py-3 small text-muted"><i class="ri-information-line me-1"></i>Semua asrama dapat melihat katalog. Hanya Superadmin yang dapat mengubah.</div></div></div>
</div>
<div class="card">
<div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between gap-2">
<form method="GET" class="d-flex gap-2">
<input type="text" name="search" class="form-control form-control-sm" placeholder="Cari kode/nama..." value="{{ request('search') }}">
<select name="kind" class="form-select form-select-sm"><option value="">Semua jenis</option><option value="violation" @selected(request('kind')==='violation')>Pelanggaran</option><option value="reward" @selected(request('kind')==='reward')>Reward</option></select>
<button class="btn btn-primary btn-sm"><i class="ri-search-line"></i></button>
</form>
@if($canManage)<button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#catalogModal"><i class="ri-add-line me-1"></i>Tambah Katalog</button>@endif
</div>
<div class="card-body">
<div class="table-responsive"><table class="table table-hover align-middle table-freeze mb-0">
<thead><tr><th>Kode</th><th>Jenis</th><th>Nama</th><th>Kategori</th><th class="text-center">Poin</th><th class="text-center">Status</th><th class="text-center">Aksi</th></tr></thead>
<tbody>
@forelse($catalogs as $catalog)
<tr><td><code>{{ $catalog->code }}</code></td><td><span class="badge bg-{{ $catalog->kind==='violation'?'danger':'success' }}-subtle text-{{ $catalog->kind==='violation'?'danger':'success' }}">{{ $catalog->kind==='violation'?'Pelanggaran':'Reward' }}</span></td><td class="fw-semibold">{{ $catalog->name }}</td><td>{{ $catalog->category }}</td><td class="text-center">{{ $catalog->points_min }}{{ $catalog->points_max && $catalog->points_max != $catalog->points_min ? '–'.$catalog->points_max : '' }}</td><td class="text-center"><span class="badge bg-{{ $catalog->is_active?'success':'secondary' }}-subtle text-{{ $catalog->is_active?'success':'secondary' }}">{{ $catalog->is_active?'Aktif':'Nonaktif' }}</span></td><td class="text-center">@if($canManage)<form method="POST" action="{{ route('user.asrama.point-catalog.toggle', ['userId'=>$userId,'asramaUuid'=>$dormitory->id,'catalogUuid'=>$catalog->id]) }}">@csrf<button class="btn btn-sm btn-outline-warning"><i class="ri-toggle-line"></i></button></form>@else<span class="text-muted small">Lihat</span>@endif</td></tr>
@empty<tr><td colspan="7" class="text-center text-muted py-5">Belum ada katalog poin.</td></tr>@endforelse
</tbody></table></div>
<div class="mt-3">{{ $catalogs->links() }}</div>
</div></div>
@if($canManage)
<div class="modal fade" id="catalogModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('user.asrama.point-catalog.store', ['userId'=>$userId,'asramaUuid'=>$dormitory->id]) }}">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Katalog Poin</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3">
<div class="col-md-3"><label class="form-label">Jenis</label><select name="kind" class="form-select" required><option value="violation">Pelanggaran</option><option value="reward">Reward</option></select></div>
<div class="col-md-3"><label class="form-label">Kode</label><input name="code" class="form-control" required maxlength="30"></div>
<div class="col-md-6"><label class="form-label">Nama</label><input name="name" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Kategori</label><input name="category" class="form-control" required></div>
<div class="col-md-2"><label class="form-label">Poin Min</label><input type="number" name="points_min" class="form-control" min="0" required></div>
<div class="col-md-2"><label class="form-label">Poin Max</label><input type="number" name="points_max" class="form-control" min="0"></div>
<div class="col-md-4"><label class="form-label">Severity/Sub</label><input name="severity" class="form-control"></div>
<div class="col-12"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="2"></textarea></div>
<div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_permanent" value="1" id="permanent"><label class="form-check-label" for="permanent">Poin permanen (tidak reset)</label></div></div>
</div></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div></form></div></div></div>
@endif
@endsection
