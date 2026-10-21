@extends('layouts.master')
@section('title', 'Petugas Santri Asrama')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('perpustakaan._styles')
    @component('components.breadcrumb') @slot('li_1') Asrama @endslot @slot('li_2') Pengaturan @endslot @slot('title') Petugas Santri @endslot @endcomponent
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('operator_credentials'))
        @php $credential = session('operator_credentials'); @endphp
        <div class="alert alert-warning"><div class="fw-semibold mb-1"><i class="ri-key-2-line me-1"></i>Akun petugas asrama dibuat</div><div class="small">Nama: <strong>{{ $credential['name'] }}</strong> · Asrama: {{ $credential['unit'] }}</div><div class="small">Username/NIS: <code>{{ $credential['username'] }}</code> · Password awal: <code>{{ $credential['password'] }}</code></div><div class="small mt-1">Santri wajib mengganti password saat login pertama.</div></div>
    @endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card mb-3"><div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h5 class="card-title mb-0"><i class="ri-hotel-line text-primary me-1"></i>Petugas Santri Asrama</h5><p class="text-muted mb-0 small">Petugas hanya dapat mencatat kunjungan dan check-in. Tidak ada approval, edit, atau hapus.</p></div><form method="GET" class="d-flex gap-2"><select name="dormitory_id" class="form-select form-select-sm" onchange="this.form.submit()">@foreach($dormitories as $item)<option value="{{ $item->id }}" @selected($dormitory->id === $item->id)>{{ $item->name }}</option>@endforeach</select><button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#operatorModal" onclick="openOperatorCreate()"><i class="ri-user-add-line me-1"></i>Tambah Petugas</button></form></div></div>

    <div class="row g-2 mb-3 library-stats">
        @foreach([['Total Petugas', $stats['total'], 'ri-user-star-line', 'primary', 'Petugas asrama '.$dormitory->name], ['Aktif', $stats['active'], 'ri-checkbox-circle-line', 'success', 'Dapat login dan bertugas'], ['Nonaktif', $stats['inactive'], 'ri-close-circle-line', 'secondary', 'Akses dicabut']] as [$label, $value, $icon, $color, $hint])
            @include('perpustakaan._stat-card', compact('label', 'value', 'icon', 'color', 'hint'))
        @endforeach
    </div>

    <div class="card"><div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-team-line text-primary me-1"></i>Daftar Petugas {{ $dormitory->name }}</h5></div><div class="table-container"><table class="table table-hover library-table table-freeze align-middle mb-0"><thead class="table-light"><tr><th>Nama Santri</th><th>NIS</th><th>Username</th><th>Terakhir Login</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
        @forelse($assignments as $assignment)
            <tr><td class="fw-medium">{{ $assignment->student?->name ?? '—' }}</td><td><code>{{ $assignment->student?->nis ?? '—' }}</code></td><td><code>{{ $assignment->user?->username ?? '—' }}</code></td><td>{{ $assignment->user?->last_login_at?->format('d/m/Y H:i') ?? 'Belum pernah' }}</td><td><span class="badge {{ $assignment->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $assignment->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="text-end"><div class="btn-group btn-group-sm"><form method="POST" action="{{ route('user.asrama.operators.reset-password',['userId'=>$userId,'id'=>$assignment->id]) }}" onsubmit="return confirm('Reset password ke NIS/NISN?')">@csrf<button class="btn btn-soft-warning" title="Reset password"><i class="ri-key-2-line"></i></button></form><form method="POST" action="{{ route('user.asrama.operators.toggle',['userId'=>$userId,'id'=>$assignment->id]) }}" onsubmit="return confirm('Ubah status petugas?')">@csrf<button class="btn {{ $assignment->is_active ? 'btn-soft-danger' : 'btn-soft-success' }}"><i class="{{ $assignment->is_active ? 'ri-user-unfollow-line' : 'ri-user-follow-line' }}"></i></button></form></div></td></tr>
        @empty<tr><td colspan="6" class="text-center text-muted py-5">Belum ada petugas santri di asrama ini.</td></tr>@endforelse
        </tbody></table></div></div>

    <div class="modal fade library-modal" id="operatorModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('user.asrama.operators.store',['userId'=>$userId]) }}">@csrf<input type="hidden" name="dormitory_id" value="{{ $dormitory->id }}"><div class="modal-header bg-primary-subtle"><h5 class="modal-title">Tambah Petugas Asrama</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-7"><label class="form-label">Santri Penghuni</label><select name="student_id" id="dormOperatorStudent" class="form-select" required><option value=""></option></select><div class="form-text">Hanya penghuni aktif asrama ini.</div></div><div class="col-md-5"><label class="form-label">Asrama</label><input class="form-control" value="{{ $dormitory->name }}" disabled></div><div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" rows="2" class="form-control"></textarea></div><div class="col-12"><div class="alert alert-info py-2 mb-0 small"><i class="ri-information-line me-1"></i>Username memakai NIS, password awal NIS, wajib diganti saat login.</div></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Buat Akun Petugas</button></div></form></div></div></div>
@endsection
@section('script')
<script>
    const dormOperatorSearch=@json(route('user.asrama.operators.students',['userId'=>$userId]));
    const dormOperatorId=@json($dormitory->id);
    function initDormOperatorSearch(){
        if(!window.jQuery || !$.fn.select2) return;
        $('#dormOperatorStudent').select2({width:'100%',dropdownParent:$('#operatorModal'),placeholder:'Cari nama, NIS, atau NISN...',allowClear:true,minimumInputLength:1,ajax:{url:dormOperatorSearch,dataType:'json',delay:250,data:function(params){return {q:params.term||'',dormitory_id:dormOperatorId};},processResults:function(data){return data;},cache:true}});
    }
    function openOperatorCreate(){if(window.jQuery && $.fn.select2){$('#dormOperatorStudent').val(null).trigger('change');}}
    document.addEventListener('DOMContentLoaded', initDormOperatorSearch);
</script>
@endsection
