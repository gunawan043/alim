@extends('layouts.master')
@section('title', 'Petugas Santri Perpustakaan')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('perpustakaan._styles')
    @component('components.breadcrumb') @slot('li_1') Perpustakaan @endslot @slot('li_2') Pengaturan @endslot @slot('title') Petugas Santri @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('operator_credentials'))
        @php $credential = session('operator_credentials'); @endphp
        <div class="alert alert-warning">
            <div class="fw-semibold mb-1"><i class="ri-key-2-line me-1"></i>Akun petugas berhasil dibuat</div>
            <div class="small">Nama: <strong>{{ $credential['name'] }}</strong> · Unit: {{ $credential['unit'] }}</div>
            <div class="small">Username/NIS: <code>{{ $credential['username'] }}</code> · Password awal: <code>{{ $credential['password'] }}</code></div>
            <div class="small mt-1">Santri wajib mengganti password saat login pertama.</div>
        </div>
    @endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-2 mb-3 library-stats">
        @foreach([['Total Petugas', $stats['total'], 'ri-user-star-line', 'primary', 'Petugas santri unit '.$unit->name], ['Aktif', $stats['active'], 'ri-checkbox-circle-line', 'success', 'Dapat login dan bertugas'], ['Nonaktif', $stats['inactive'], 'ri-close-circle-line', 'secondary', 'Akses operasional dicabut']] as [$label, $value, $icon, $color, $hint])
            @include('perpustakaan._stat-card', compact('label', 'value', 'icon', 'color', 'hint'))
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-user-star-line text-primary me-1"></i>Petugas Santri {{ $unit->name }}</h5><p class="text-muted mb-0 small">Hanya dapat mencatat kunjungan, peminjaman, dan pengembalian. Tidak dapat approval, edit, hapus, atau AI.</p></div>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#operatorModal" onclick="openOperatorCreate()"><i class="ri-user-add-line me-1"></i>Tambah Petugas</button>
        </div>
        @include('perpustakaan._filter-panel', [
            'filterAction' => route('user.perpustakaan.operators.index'),
            'filterHidden' => ['unit_id' => $unit->id],
            'filterControls' => [
                ['type' => 'text', 'name' => 'search', 'label' => 'Pencarian', 'value' => request('search'), 'placeholder' => 'Nama atau NIS petugas'],
            ],
            'filterShowModal' => false,
        ])
        <div class="table-container"><table class="table table-hover library-table table-freeze align-middle mb-0"><thead class="table-light"><tr><th>Nama Santri</th><th>NIS</th><th>Username Login</th><th>Terakhir Login</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
        @forelse($assignments as $assignment)
            @php $search = strtolower((string) request('search')); $match = $search === '' || str_contains(strtolower($assignment->student?->name ?? ''), $search) || str_contains(strtolower($assignment->student?->nis ?? ''), $search); @endphp
            @if($match)
            <tr><td><div class="fw-medium">{{ $assignment->student?->name ?? '—' }}</div><div class="text-muted small">{{ $assignment->unit?->name }}</div></td><td><code>{{ $assignment->student?->nis ?? '—' }}</code></td><td><code>{{ $assignment->user?->username ?? '—' }}</code></td><td>{{ $assignment->user?->last_login_at?->format('d/m/Y H:i') ?? 'Belum pernah' }}</td><td><span class="badge {{ $assignment->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $assignment->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="text-end"><div class="btn-group btn-group-sm"><form method="POST" action="{{ route('user.perpustakaan.operators.reset-password',['userId'=>$userId,'id'=>$assignment->id]) }}" onsubmit="return confirm('Reset password petugas ke NIS/NISN?')">@csrf<button class="btn btn-soft-warning" title="Reset password"><i class="ri-key-2-line"></i></button></form><form method="POST" action="{{ route('user.perpustakaan.operators.toggle',['userId'=>$userId,'id'=>$assignment->id]) }}" onsubmit="return confirm('Ubah status petugas?')">@csrf<button class="btn {{ $assignment->is_active ? 'btn-soft-danger' : 'btn-soft-success' }}" title="{{ $assignment->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"><i class="{{ $assignment->is_active ? 'ri-user-unfollow-line' : 'ri-user-follow-line' }}"></i></button></form></div></td></tr>
            @endif
        @empty
            <tr><td colspan="6" class="text-center text-muted py-5">Belum ada petugas santri pada unit ini.</td></tr>
        @endforelse
        </tbody></table></div>
    </div>

    <div class="modal fade library-modal" id="operatorModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('user.perpustakaan.operators.store',['userId'=>$userId]) }}">@csrf<input type="hidden" name="library_unit_id" value="{{ $unit->id }}"><div class="modal-header bg-primary-subtle"><h5 class="modal-title">Tambah Petugas Santri</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-7"><label class="form-label">Santri</label><select name="student_id" id="operatorStudent" class="form-select" required><option value=""></option></select><div class="form-text">Hanya santri sesuai gender dan jenjang unit.</div></div><div class="col-md-5"><label class="form-label">Unit</label><input class="form-control" value="{{ $unit->name }}" disabled></div><div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" rows="2" class="form-control" placeholder="Contoh: petugas piket siang"></textarea></div><div class="col-12"><div class="alert alert-info py-2 mb-0 small"><i class="ri-information-line me-1"></i>Akun dibuat otomatis. Username memakai NIS, password awal NIS, dan wajib diganti saat login pertama.</div></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Buat Akun Petugas</button></div></form></div></div></div>
@endsection
@section('script')
<script>
    const operatorSearchUrl=@json(route('user.perpustakaan.members.students.search',['userId'=>$userId]));
    const operatorUnitId=@json($unit->id);
    function initOperatorSearch(){
        if (!window.jQuery || !$.fn.select2) return;
        $('#operatorStudent').select2({
            width:'100%',
            dropdownParent:$('#operatorModal'),
            placeholder:'Cari nama, NIS, atau NISN...',
            allowClear:true,
            minimumInputLength:1,
            ajax:{
                url:operatorSearchUrl,
                dataType:'json',
                delay:250,
                data:function(params){return {q:params.term || '', unit_id:operatorUnitId};},
                processResults:function(data){return data;},
                cache:true
            }
        });
    }
    function openOperatorCreate(){
        if(window.jQuery && $.fn.select2){$('#operatorStudent').val(null).trigger('change');}
    }
    document.addEventListener('DOMContentLoaded', initOperatorSearch);
</script>
@endsection
