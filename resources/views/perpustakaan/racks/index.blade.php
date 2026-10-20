@extends('layouts.master')
@section('title', 'Manajemen Rak')
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $quickBase = ['userId' => $userId, 'unit_id' => $unit->id];
        $filterAction = route('user.perpustakaan.racks.index');
        $filterHidden = ['unit_id' => $unit->id];
        $shelfOptions = ['' => 'Semua rak'];
        foreach ($shelves as $shelf) { $shelfOptions[$shelf->shelf_location] = $shelf->shelf_location.' ('.$shelf->total.')'; }
        $filterFields = [
            ['name' => 'shelf', 'label' => 'Lokasi Rak', 'type' => 'select', 'options' => $shelfOptions],
        ];
        $quickFilters = [['label' => 'Semua Rak', 'url' => route('user.perpustakaan.racks.index', $quickBase), 'icon' => 'ri-layout-grid-line', 'active' => ! request('shelf')]];
        foreach ($shelves as $shelf) {
            $quickFilters[] = ['label' => $shelf->shelf_location.' ('.$shelf->total.')', 'url' => route('user.perpustakaan.racks.index', $quickBase + ['shelf' => $shelf->shelf_location]), 'icon' => 'ri-archive-2-line', 'active' => request('shelf') === $shelf->shelf_location];
        }
    @endphp
    @include('perpustakaan._styles')
    @component('components.breadcrumb') @slot('li_1') Perpustakaan @endslot @slot('li_2') Master Koleksi @endslot @slot('title') Manajemen Rak @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-2 mb-3 library-stats">
        @foreach([['Rak Terdaftar', $shelves->count(), 'ri-layout-grid-line', 'primary', 'Lokasi rak unit '.$unit->name], ['Eksemplar di Rak', $shelves->sum('total'), 'ri-stack-line', 'info', 'Eksemplar dengan lokasi terisi'], ['Belum Berlokasi', $unassigned, 'ri-map-pin-line', 'warning', 'Perlu ditempatkan ke rak']] as [$label, $value, $icon, $color, $hint])
            @include('perpustakaan._stat-card', ['label' => $label, 'value' => $value, 'icon' => $icon, 'color' => $color, 'hint' => $hint])
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-layout-grid-line text-primary me-1"></i>Rak &amp; Lokasi Koleksi</h5><p class="text-muted mb-0 small">Lokasi rak menjadi acuan stok opname dan pencarian fisik.</p></div>
            <div class="d-flex gap-2"><form method="GET" class="d-flex gap-2"><input type="hidden" name="unit_id" value="{{ $unit->id }}"><select name="shelf" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">Semua rak</option>@foreach($shelves as $shelf)<option value="{{ $shelf->shelf_location }}" @selected(request('shelf')===$shelf->shelf_location)>{{ $shelf->shelf_location }} ({{ $shelf->total }})</option>@endforeach</select></form><button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#libraryFilterModal"><i class="bx bx-filter-alt me-1"></i>Filter Lanjutan</button></div>
        </div>
        @include('perpustakaan._quick-filters')
        <div class="table-container"><table class="table table-hover library-table align-middle mb-0 table-freeze"><thead class="table-light"><tr><th>Kode</th><th>Judul</th><th>Rak</th><th>Kondisi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
        @forelse($items as $item)
            <tr><td><code>{{ $item->inventory_code }}</code></td><td><div class="fw-medium">{{ $item->title?->title ?? '—' }}</div><div class="text-muted small">{{ $item->title?->author }}</div></td><td>{{ $item->shelf_location ?: '—' }}</td><td>{{ ucfirst(str_replace('_',' ',$item->condition)) }}</td><td><span class="badge bg-light text-dark">{{ ucfirst(str_replace('_',' ',$item->status)) }}</span></td><td class="text-end"><button class="btn btn-sm btn-soft-primary" data-item="{{ json_encode(['id'=>$item->id,'title'=>$item->title?->title,'code'=>$item->inventory_code,'shelf'=>$item->shelf_location], JSON_HEX_APOS|JSON_HEX_QUOT) }}" onclick="openMove(this)"><i class="ri-drag-move-2-line me-1"></i>Pindah Rak</button></td></tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-5">Belum ada eksemplar pada filter ini.</td></tr>
        @endforelse
        </tbody></table></div>
    </div>

    <div class="modal fade library-modal" id="moveModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" id="moveForm" action="">@csrf<div class="modal-header bg-primary-subtle"><h5 class="modal-title">Pindah Lokasi Rak</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><div class="fw-semibold" id="moveTitle"></div><div class="text-muted small" id="moveCode"></div></div><label class="form-label">Lokasi Rak Baru</label><input name="shelf_location" id="moveShelf" class="form-control" list="shelfList" required><datalist id="shelfList">@foreach($shelves as $shelf)<option value="{{ $shelf->shelf_location }}">@endforeach</datalist></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div></form></div></div></div>
    @include('perpustakaan._filter-modal')
@endsection
@section('script')
<script>
const moveUrl=@json(route('user.perpustakaan.racks.move',['userId'=>$userId,'id'=>'__ID__']));
function openMove(btn){const d=JSON.parse(btn.dataset.item);document.getElementById('moveForm').action=moveUrl.replace('__ID__',d.id);document.getElementById('moveTitle').textContent=d.title||'—';document.getElementById('moveCode').textContent=d.code||'';document.getElementById('moveShelf').value=d.shelf||'';new bootstrap.Modal(document.getElementById('moveModal')).show();}
</script>
@endsection
