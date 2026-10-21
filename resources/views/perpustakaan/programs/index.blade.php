@extends('layouts.master')
@section('title', $meta['label'])
@section('content')
    @php
        $userId = $userId ?? auth()->id();
        $statusLabels = ['draft' => 'Draft', 'submitted' => 'Diajukan', 'approved' => 'Disetujui', 'ordered' => 'Dipesan', 'active' => 'Berjalan', 'completed' => 'Selesai', 'rejected' => 'Ditolak', 'cancelled' => 'Batal'];
        $quickBase = ['userId' => $userId, 'unit_id' => $unit->id];
        if (request('category')) { $quickBase['category'] = request('category'); }
        $filterAction = route('user.perpustakaan.programs.index', ['userId' => $userId, 'type' => $type]);
        $filterHidden = ['unit_id' => $unit->id];
        $filterFields = [
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['' => 'Semua status'] + $statusLabels],
            ['name' => 'category', 'label' => $meta['category'], 'type' => 'text', 'placeholder' => 'Ketik kategori'],
        ];
        $filterControls = [
            ['type' => 'select', 'name' => 'status', 'label' => 'Status', 'options' => ['' => 'Semua status'] + $statusLabels],
            ['type' => 'text', 'name' => 'category', 'label' => $meta['category'], 'value' => request('category'), 'placeholder' => 'Ketik kategori'],
        ];
        $quickFilters = [['label' => 'Semua', 'url' => route('user.perpustakaan.programs.index', array_merge($quickBase, ['type' => $type])), 'icon' => 'ri-list-check', 'active' => ! request('status')]];
        foreach ($statusLabels as $key => $label) {
            $quickFilters[] = ['label' => $label, 'url' => route('user.perpustakaan.programs.index', array_merge($quickBase, ['type' => $type, 'status' => $key])), 'icon' => 'ri-filter-line', 'active' => request('status') === $key];
        }
    @endphp
    @include('perpustakaan._styles')
    @component('components.breadcrumb') @slot('li_1') Perpustakaan @endslot @slot('li_2') {{ $type === 'literacy' ? 'Literasi' : 'Inventaris' }} @endslot @slot('title') {{ $meta['label'] }} @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-2 mb-3 library-stats">
        @foreach([['Total',$stats['total'],'ri-list-check-2','primary'],['Draft',$stats['draft'],'ri-draft-line','secondary'],['Berjalan',$stats['active'],'ri-time-line','warning'],['Selesai',$stats['done'],'ri-checkbox-circle-line','success']] as [$label,$value,$icon,$color])
            <div class="col-md-3"><div class="card"><div class="card-body py-3"><div class="d-flex gap-2 align-items-center"><span class="avatar-sm"><span class="avatar-title bg-{{ $color }}-subtle rounded"><i class="{{ $icon }} text-{{ $color }}"></i></span></span><div><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ $value }}</div></div></div></div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="{{ $meta['icon'] }} text-primary me-1"></i>{{ $meta['label'] }} {{ $unit->name }}</h5><p class="text-muted mb-0 small">{{ $year?->name ?? 'Tahun ajaran aktif' }} · pencatatan tahap awal, stok fisik tetap diverifikasi pada penerimaan.</p></div>
            <div class="d-flex flex-wrap gap-2">
                
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#programModal" onclick="openProgramCreate()"><i class="ri-add-line me-1"></i>Tambah</button>
            </div>
        </div>
        @include('perpustakaan._filter-panel')
        @include('perpustakaan._quick-filters')
        <div class="table-container"><table class="table table-hover library-table align-middle mb-0 table-freeze">
            <thead class="table-light"><tr><th>Judul / Kegiatan</th><th>{{ $meta['category'] }}</th><th>Tanggal</th><th>Jumlah</th><th>Nilai</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($programs as $program)
                @php
                    $edit = ['id'=>$program->id,'title'=>$program->title,'category'=>$program->category,'status'=>$program->status,'event_date'=>$program->event_date?->toDateString(),'quantity'=>$program->quantity,'amount'=>$program->amount,'description'=>$program->description];
                @endphp
                <tr>
                    <td><div class="fw-medium">{{ $program->title }}</div><div class="text-muted small">{{ Str::limit($program->description, 90) ?: '—' }}</div></td>
                    <td>{{ $program->category ?: '—' }}</td>
                    <td>{{ $program->event_date?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $program->quantity !== null ? number_format($program->quantity, 0, ',', '.') : '—' }}</td>
                    <td>{{ $program->amount !== null ? 'Rp '.number_format((float) $program->amount, 0, ',', '.') : '—' }}</td>
                    <td><span class="badge bg-{{ $program->status === 'completed' ? 'success' : ($program->status === 'draft' ? 'secondary' : 'warning') }}-subtle text-{{ $program->status === 'completed' ? 'success' : ($program->status === 'draft' ? 'secondary' : 'warning') }}">{{ $statusLabels[$program->status] ?? ucfirst($program->status) }}</span></td>
                    <td class="text-end"><div class="btn-group btn-group-sm"><button class="btn btn-soft-warning" data-program="{{ json_encode($edit, JSON_HEX_APOS|JSON_HEX_QUOT) }}" onclick="openProgramEdit(this)"><i class="ri-pencil-line"></i></button><form method="POST" action="{{ route('user.perpustakaan.programs.destroy',['userId'=>$userId,'id'=>$program->id]) }}" onsubmit="return confirm('Hapus data ini dari daftar aktif?')">@csrf @method('DELETE')<button class="btn btn-soft-danger"><i class="ri-delete-bin-line"></i></button></form></div></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-5">Belum ada data {{ strtolower($meta['label']) }}.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="modal fade library-modal" id="programModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <form method="POST" id="programForm" action="{{ route('user.perpustakaan.programs.store',['userId'=>$userId,'type'=>$type]) }}">
            @csrf @method('POST')
            <input type="hidden" name="library_unit_id" value="{{ $unit->id }}">
            <div class="modal-header bg-primary-subtle"><h5 class="modal-title" id="programModalTitle">Tambah {{ $meta['label'] }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><div class="row g-3">
                <div class="col-md-8"><label class="form-label">Judul / Nama Kegiatan</label><input name="title" id="programTitle" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">{{ $meta['category'] }}</label><input name="category" id="programCategory" class="form-control" list="programCategoryList"><datalist id="programCategoryList">@foreach($type === 'literacy' ? ['Pojok Baca','Resensi Buku','Program Literasi','Lomba Literasi'] : ($type === 'weeding' ? ['Rusak berat','Kedaluwarsa','Tidak relevan','Duplikat'] : ($type === 'donation' ? ['Perorangan','Instansi','Alumni','Yayasan'] : ['Fiksi','Nonfiksi','Pelajaran','Referensi','Kitab'])) as $option)<option value="{{ $option }}">@endforeach</datalist></div>
                <div class="col-md-4"><label class="form-label">Status</label><select name="status" id="programStatus" class="form-select">@foreach($statusLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Tanggal</label><input type="date" name="event_date" id="programDate" class="form-control" value="{{ today()->toDateString() }}"></div>
                <div class="col-md-4"><label class="form-label">Jumlah</label><input type="number" name="quantity" id="programQuantity" class="form-control" min="0"></div>
                <div class="col-md-4"><label class="form-label">Nilai (Rp)</label><input type="number" name="amount" id="programAmount" class="form-control" min="0" step="0.01"></div>
                <div class="col-12"><label class="form-label">Keterangan</label><textarea name="description" id="programDescription" rows="3" class="form-control"></textarea></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div></div></div>
    @include('perpustakaan._filter-modal')
@endsection
@section('script')
<script>
const programStore = @json(route('user.perpustakaan.programs.store', ['userId' => $userId, 'type' => $type]));
const programUpdate = @json(route('user.perpustakaan.programs.update', ['userId' => $userId, 'id' => '__ID__']));
function openProgramCreate() {
    document.getElementById('programModalTitle').textContent = 'Tambah {{ $meta['label'] }}';
    document.getElementById('programForm').action = programStore;
    document.getElementById('programForm').querySelector('input[name="_method"]').value = 'POST';
    ['programTitle','programCategory','programQuantity','programAmount','programDescription'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('programStatus').value = 'draft';
    document.getElementById('programDate').value = '{{ today()->toDateString() }}';
}
function openProgramEdit(btn) {
    const d = JSON.parse(btn.dataset.program);
    document.getElementById('programModalTitle').textContent = 'Edit {{ $meta['label'] }}';
    document.getElementById('programForm').action = programUpdate.replace('__ID__', d.id);
    document.getElementById('programForm').querySelector('input[name="_method"]').value = 'PUT';
    document.getElementById('programTitle').value = d.title || '';
    document.getElementById('programCategory').value = d.category || '';
    document.getElementById('programStatus').value = d.status || 'draft';
    document.getElementById('programDate').value = d.event_date || '';
    document.getElementById('programQuantity').value = d.quantity ?? '';
    document.getElementById('programAmount').value = d.amount ?? '';
    document.getElementById('programDescription').value = d.description || '';
    new bootstrap.Modal(document.getElementById('programModal')).show();
}
</script>
@endsection
