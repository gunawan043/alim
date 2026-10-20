@extends('layouts.master')
@section('title', 'Genre Buku')
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $filterAction = route('user.perpustakaan.genres.index');
        $filterFields = [
            ['name' => 'search', 'label' => 'Pencarian', 'type' => 'text', 'placeholder' => 'Nama atau kode genre'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['' => 'Semua status', 'active' => 'Aktif', 'inactive' => 'Nonaktif']],
        ];
        $quickFilters = [
            ['label' => 'Semua', 'url' => route('user.perpustakaan.genres.index', ['userId' => $userId]), 'icon' => 'ri-list-check', 'active' => ! request('status')],
            ['label' => 'Aktif', 'url' => route('user.perpustakaan.genres.index', ['userId' => $userId, 'status' => 'active']), 'icon' => 'ri-checkbox-circle-line', 'active' => request('status') === 'active'],
            ['label' => 'Nonaktif', 'url' => route('user.perpustakaan.genres.index', ['userId' => $userId, 'status' => 'inactive']), 'icon' => 'ri-close-circle-line', 'active' => request('status') === 'inactive'],
        ];
    @endphp
    @include('perpustakaan._styles')
    @component('components.breadcrumb') @slot('li_1') Perpustakaan @endslot @slot('li_2') Master Koleksi @endslot @slot('title') Genre Buku @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="row g-2 mb-3 library-stats">
        @foreach([['Total Genre', $stats['total'], 'ri-price-tag-3-line', 'primary', 'Kategori koleksi'], ['Aktif', $stats['active'], 'ri-checkbox-circle-line', 'success', 'Dipakai pada katalog'], ['Nonaktif', $stats['inactive'], 'ri-close-circle-line', 'secondary', 'Tidak ditampilkan sebagai pilihan']] as [$label, $value, $icon, $color, $hint])
            @include('perpustakaan._stat-card', ['label' => $label, 'value' => $value, 'icon' => $icon, 'color' => $color, 'hint' => $hint])
        @endforeach
    </div>
    <div class="card"><div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center"><div><h5 class="card-title mb-0"><i class="ri-price-tag-3-line text-primary me-1"></i>Genre Buku</h5><p class="text-muted mb-0 small">Genre dipakai pada katalog, usulan pengadaan, dan laporan.</p></div><div class="d-flex gap-2"><button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#libraryFilterModal"><i class="bx bx-filter-alt me-1"></i>Filter Lanjutan</button><a href="{{ route('user.perpustakaan.imports.genres.form', ['userId'=>$userId]) }}" class="btn btn-success btn-sm"><i class="ri-upload-cloud-2-line me-1"></i>Import</a><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#genreModal" onclick="openGenreCreate()"><i class="ri-add-line me-1"></i>Tambah Genre</button></div></div>
        @include('perpustakaan._quick-filters')
        <div class="table-container"><table class="table table-hover library-table align-middle mb-0 table-freeze"><thead class="table-light"><tr><th>Nama</th><th>Kode</th><th>Deskripsi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
        @forelse($genres as $genre)
            <tr><td class="fw-medium">{{ $genre->name }}</td><td><code>{{ $genre->code ?? '—' }}</code></td><td class="small text-muted">{{ $genre->description ?: '—' }}</td><td><span class="badge {{ $genre->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $genre->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="text-end"><div class="btn-group btn-group-sm"><button class="btn btn-soft-warning" data-genre="{{ json_encode($genre->only(['id','name','code','description','is_active'])) }}" onclick="openGenreEdit(this)"><i class="ri-pencil-line"></i></button><form method="POST" action="{{ route('user.perpustakaan.genres.destroy',['userId'=>$userId,'id'=>$genre->id]) }}" onsubmit="return confirm('Hapus genre ini?')">@csrf @method('DELETE')<button class="btn btn-soft-danger"><i class="ri-delete-bin-line"></i></button></form></div></td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted py-5">Belum ada genre.</td></tr>@endforelse
        </tbody></table></div>
    </div>
    <div class="modal fade" id="genreModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" id="genreForm" action="{{ route('user.perpustakaan.genres.store',['userId'=>$userId]) }}">@csrf<input type="hidden" name="_method" id="genreMethod" value="POST"><div class="modal-header bg-primary-subtle"><h5 class="modal-title" id="genreTitle">Tambah Genre</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-7"><label class="form-label">Nama Genre</label><input name="name" id="genreName" class="form-control" required></div><div class="col-md-5"><label class="form-label">Kode</label><input name="code" id="genreCode" class="form-control" maxlength="30"></div><div class="col-12"><label class="form-label">Deskripsi</label><textarea name="description" id="genreDescription" rows="2" class="form-control"></textarea></div><div class="col-12"><label class="form-label">Status</label><select name="is_active" id="genreActive" class="form-select"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div></form></div></div></div>
    @include('perpustakaan._filter-modal')
@endsection
@section('script')
<script>
const genreStore=@json(route('user.perpustakaan.genres.store',['userId'=>$userId]));
const genreUpdate=@json(route('user.perpustakaan.genres.update',['userId'=>$userId,'id'=>'__ID__']));
function openGenreCreate(){document.getElementById('genreTitle').textContent='Tambah Genre';document.getElementById('genreForm').action=genreStore;document.getElementById('genreMethod').value='POST';['genreName','genreCode','genreDescription'].forEach(id=>document.getElementById(id).value='');document.getElementById('genreActive').value='1';}
function openGenreEdit(btn){const d=JSON.parse(btn.dataset.genre);document.getElementById('genreTitle').textContent='Edit Genre';document.getElementById('genreForm').action=genreUpdate.replace('__ID__',d.id);document.getElementById('genreMethod').value='PUT';document.getElementById('genreName').value=d.name||'';document.getElementById('genreCode').value=d.code||'';document.getElementById('genreDescription').value=d.description||'';document.getElementById('genreActive').value=d.is_active?'1':'0';new bootstrap.Modal(document.getElementById('genreModal')).show();}
</script>
@endsection
