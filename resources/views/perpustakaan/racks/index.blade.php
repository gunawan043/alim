@extends('layouts.master')
@section('title', 'Manajemen Rak')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('perpustakaan._styles')
    @component('components.breadcrumb') @slot('li_1') Perpustakaan @endslot @slot('li_2') Master Koleksi @endslot @slot('title') Manajemen Rak @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted small">Rak Terdaftar</div><div class="fs-3 fw-bold">{{ $shelves->count() }}</div><span class="text-muted small">Lokasi rak pada unit {{ $unit->name }}</span></div></div></div>
        <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted small">Eksemplar di Rak</div><div class="fs-3 fw-bold">{{ $shelves->sum('total') }}</div><span class="text-muted small">Eksemplar dengan lokasi terisi</span></div></div></div>
        <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted small">Belum Berlokasi</div><div class="fs-3 fw-bold text-warning">{{ $unassigned }}</div><span class="text-muted small">Perlu ditempatkan ke rak</span></div></div></div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-layout-grid-line text-primary me-1"></i>Rak &amp; Lokasi Koleksi</h5><p class="text-muted mb-0 small">Lokasi rak menjadi acuan stok opname dan pencarian fisik.</p></div>
            <form method="GET" class="d-flex gap-2"><input type="hidden" name="unit_id" value="{{ $unit->id }}"><select name="shelf" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">Semua rak</option>@foreach($shelves as $shelf)<option value="{{ $shelf->shelf_location }}" @selected(request('shelf')===$shelf->shelf_location)>{{ $shelf->shelf_location }} ({{ $shelf->total }})</option>@endforeach</select></form>
        </div>
        <div class="px-3 py-2 bg-light border-bottom d-flex flex-wrap gap-2">
            @forelse($shelves as $shelf)
                <a href="{{ route('user.perpustakaan.racks.index',['userId'=>$userId,'unit_id'=>$unit->id,'shelf'=>$shelf->shelf_location]) }}" class="btn btn-sm {{ request('shelf')===$shelf->shelf_location ? 'btn-primary' : 'btn-outline-primary' }}"><i class="ri-archive-2-line me-1"></i>{{ $shelf->shelf_location }} <span class="badge bg-white text-dark ms-1">{{ $shelf->total }}</span></a>
            @empty
                <span class="text-muted small">Belum ada rak. Tambahkan lokasi melalui tombol pindah rak pada eksemplar.</span>
            @endforelse
        </div>
        <div class="table-responsive"><table class="table table-hover library-table align-middle mb-0"><thead class="table-light"><tr><th>Kode</th><th>Judul</th><th>Rak</th><th>Kondisi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
        @forelse($items as $item)
            <tr><td><code>{{ $item->inventory_code }}</code></td><td><div class="fw-medium">{{ $item->title?->title ?? '—' }}</div><div class="text-muted small">{{ $item->title?->author }}</div></td><td>{{ $item->shelf_location ?: '—' }}</td><td>{{ ucfirst(str_replace('_',' ',$item->condition)) }}</td><td><span class="badge bg-light text-dark">{{ ucfirst(str_replace('_',' ',$item->status)) }}</span></td><td class="text-end"><button class="btn btn-sm btn-soft-primary" data-item="{{ json_encode(['id'=>$item->id,'title'=>$item->title?->title,'code'=>$item->inventory_code,'shelf'=>$item->shelf_location], JSON_HEX_APOS|JSON_HEX_QUOT) }}" onclick="openMove(this)"><i class="ri-drag-move-2-line me-1"></i>Pindah Rak</button></td></tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-5">Belum ada eksemplar pada filter ini.</td></tr>
        @endforelse
        </tbody></table></div>
    </div>

    <div class="modal fade library-modal" id="moveModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" id="moveForm" action="">@csrf<div class="modal-header bg-primary-subtle"><h5 class="modal-title">Pindah Lokasi Rak</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><div class="fw-semibold" id="moveTitle"></div><div class="text-muted small" id="moveCode"></div></div><label class="form-label">Lokasi Rak Baru</label><input name="shelf_location" id="moveShelf" class="form-control" list="shelfList" required><datalist id="shelfList">@foreach($shelves as $shelf)<option value="{{ $shelf->shelf_location }}">@endforeach</datalist></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div></form></div></div></div>
@endsection
@section('script')
<script>
const moveUrl=@json(route('user.perpustakaan.racks.move',['userId'=>$userId,'id'=>'__ID__']));
function openMove(btn){const d=JSON.parse(btn.dataset.item);document.getElementById('moveForm').action=moveUrl.replace('__ID__',d.id);document.getElementById('moveTitle').textContent=d.title||'—';document.getElementById('moveCode').textContent=d.code||'';document.getElementById('moveShelf').value=d.shelf||'';new bootstrap.Modal(document.getElementById('moveModal')).show();}
</script>
@endsection
