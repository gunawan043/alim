@extends('layouts.master')
@section('title', 'Tasmi’ & Syahadah')
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('tahfidz._styles')
    @component('components.breadcrumb') @slot('li_1') Tahfidz @endslot @slot('li_2') Ujian @endslot @slot('title') Tasmi’ & Syahadah @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row tahfidz-stat">
        @foreach([
            ['Sesi Tasmi’', $stats['total'], 'ri-award-line', 'primary', 'Sesi pada filter ini'],
            ['Terjadwal / Berlangsung', $stats['scheduled'], 'ri-calendar-todo-line', 'warning', 'Perlu dipantau'],
            ['Peserta', $stats['participants'], 'ri-user-star-line', 'info', 'Total peserta'],
            ['Sudah Dinilai', $stats['scored'], 'ri-check-double-line', 'success', 'Memiliki nilai'],
        ] as [$label, $value, $icon, $color, $note])
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0"><span class="avatar-title bg-{{ $color }}-subtle rounded fs-2"><i class="{{ $icon }} text-{{ $color }}"></i></span></div>
                    <div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0 stat-label">{{ $label }}</p><h3 class="fw-bold ff-secondary mb-0">{{ number_format($value) }}</h3></div>
                </div>
                <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>{{ $note }}</p>
            </div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="card-title mb-0"><i class="ri-award-line text-primary me-1"></i>Sesi Tasmi’</h5><p class="text-muted mb-0 small">Ujian hafalan formal, penilaian mustami’, dan penerbitan syahadah.</p></div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.tahfidz.tasmi.flow', ['userId'=>$userId]) }}" class="btn btn-soft-primary"><i class="ri-route-line me-1"></i>Alur Tasmi’</a>
                <a href="{{ route('user.tahfidz.tasmi.certificates', ['userId'=>$userId]) }}" class="btn btn-soft-info"><i class="ri-certificate-line me-1"></i>Syahadah</a>
                @if($canManage)<button type="button" class="btn btn-success" onclick="openTasmiModal()"><i class="ri-add-line me-1"></i>Buat Sesi</button>@endif
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small">Tahun Ajaran</label><select name="academic_year_id" class="form-select" onchange="this.form.submit()">@foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected($academicYearId === $ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>@endforeach</select></div>
                @if($schools->isNotEmpty())<div class="col-md-4"><label class="form-label small">Sekolah</label><select name="school_id" class="form-select" onchange="this.form.submit()"><option value="">Semua</option>@foreach($schools as $school)<option value="{{ $school->id }}" @selected(request('school_id')===$school->id)>{{ $school->name }}</option>@endforeach</select></div>@endif
                <div class="col-md-3"><label class="form-label small">Status</label><select name="status" class="form-select" onchange="this.form.submit()"><option value="">Semua</option>@foreach(['draft'=>'Draft','terjadwal'=>'Terjadwal','berlangsung'=>'Berlangsung','selesai'=>'Selesai'] as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tahfidz-table">
                <thead class="table-light"><tr><th>Sesi</th><th>Tanggal</th><th>Sekolah</th><th class="text-center">Peserta</th><th class="text-center">Dinilai</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($sessions as $session)
                        @php $editPayload = ['id'=>$session->id,'school_id'=>$session->school_id,'academic_year_id'=>$session->academic_year_id,'session_name'=>$session->session_name,'session_date'=>$session->session_date?->format('Y-m-d'),'session_time_start'=>$session->session_time_start,'session_time_end'=>$session->session_time_end,'location'=>$session->location,'session_type'=>$session->session_type,'status'=>$session->status]; @endphp
                        <tr>
                            <td><div class="fw-medium">{{ $session->session_name }}</div><div class="text-muted small">{{ $session->session_type_label }} · {{ $session->location ?? '—' }}</div></td>
                            <td>{{ $session->session_date?->format('d M Y') }}<div class="text-muted small">{{ $session->session_time_start ? substr($session->session_time_start,0,5) : '' }}{{ $session->session_time_end ? '–'.substr($session->session_time_end,0,5) : '' }}</div></td>
                            <td>{{ $session->school?->name ?? 'Departemen / semua sekolah' }}</td>
                            <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $session->participants_count }}</span></td>
                            <td class="text-center"><span class="badge bg-success-subtle text-success">{{ $session->scores_count }}</span></td>
                            <td><span class="badge {{ $session->status==='selesai' ? 'bg-success-subtle text-success' : ($session->status==='berlangsung' ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary') }}">{{ $session->status_label }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('user.tahfidz.tasmi.show', ['userId'=>$userId,'id'=>$session->id]) }}" class="btn btn-sm btn-outline-primary" title="Detail"><i class="ri-eye-line"></i></a>
                                @if($canManage)
                                    <button type="button" class="btn btn-sm btn-outline-warning edit-tasmi" title="Edit" data-session="{{ json_encode($editPayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}" onclick="openTasmiEditModal(JSON.parse(this.dataset.session))"><i class="ri-edit-line"></i></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-tasmi" title="Hapus" data-action="{{ route('user.tahfidz.tasmi.destroy', ['userId'=>$userId,'id'=>$session->id]) }}" data-name="{{ $session->session_name }}"><i class="ri-delete-bin-line"></i></button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="ri-award-line fs-3 d-block mb-2"></i>Belum ada sesi tasmi’.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canManage)
        <div class="modal fade" id="tasmiModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('user.tahfidz.tasmi.store', ['userId'=>$userId]) }}" id="tasmiForm">
                        @csrf
                        <input type="hidden" name="_method" value="PUT" id="tasmiMethod" disabled>
                        <div class="modal-header bg-primary-subtle"><h5 class="modal-title" id="tasmiModalTitle"><i class="ri-award-line me-1"></i>Buat Sesi Tasmi’</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body"><div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Nama Sesi <span class="text-danger">*</span></label><input name="session_name" id="tasmi_session_name" class="form-control" required maxlength="191" placeholder="Mis. Tasmi’ Juz 30 Semester Ganjil"></div>
                            <div class="col-md-3"><label class="form-label">Tanggal <span class="text-danger">*</span></label><input type="date" name="session_date" id="tasmi_session_date" class="form-control" required></div>
                            <div class="col-md-3"><label class="form-label">Jenis <span class="text-danger">*</span></label><select name="session_type" id="tasmi_session_type" class="form-select" required><option value="reguler">Reguler</option><option value="kenaikan_juz">Kenaikan Juz</option><option value="khatam">Khatam</option><option value="khusus">Khusus</option></select></div>
                            <div class="col-md-6"><label class="form-label">Sekolah</label><select name="school_id" id="tasmi_school_id" class="form-select"><option value="">Departemen / semua sekolah</option>@foreach($schools as $school)<option value="{{ $school->id }}">{{ $school->name }}</option>@endforeach</select></div>
                            <div class="col-md-6"><label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label><select name="academic_year_id" id="tasmi_academic_year_id" class="form-select" required>@foreach($academicYears as $ay)<option value="{{ $ay->id }}" @selected($academicYearId===$ay->id)>{{ $ay->name }} — {{ ucfirst($ay->semester) }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label">Mulai</label><input type="time" name="session_time_start" id="tasmi_session_time_start" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">Selesai</label><input type="time" name="session_time_end" id="tasmi_session_time_end" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">Status <span class="text-danger">*</span></label><select name="status" id="tasmi_status" class="form-select" required><option value="draft">Draft</option><option value="terjadwal">Terjadwal</option><option value="berlangsung">Berlangsung</option><option value="selesai">Selesai</option></select></div>
                            <div class="col-md-3"><label class="form-label">Tempat</label><input name="location" id="tasmi_location" class="form-control" maxlength="191" placeholder="Aula / Ruang Tahfidz"></div>
                        </div></div>
                        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success" id="tasmiSubmit"><i class="ri-save-line me-1"></i>Simpan Sesi</button></div>
                    </form>
                </div>
            </div>
        </div>

        <form id="deleteTasmiForm" method="POST" class="d-none">@csrf @method('DELETE')</form>
        <x-modal-confirm id="deleteTasmiModal" title="Hapus Sesi Tasmi’?" message="Sesi <strong id='deleteTasmiName'>ini</strong> akan dihapus. Sesi yang memiliki peserta/nilai tidak dapat dihapus." submit-label="Ya, Hapus" submit-icon="ri-delete-bin-line" submit-class="btn-danger" form-id="deleteTasmiForm" />
    @endif
@endsection

@push('scripts')
<script>
    @php
        $tasmiStoreUrl = route('user.tahfidz.tasmi.store', ['userId'=>$userId]);
        $tasmiUpdateUrl = route('user.tahfidz.tasmi.update', ['userId'=>$userId,'id'=>'__ID__']);
    @endphp
    const tasmiStoreUrl = @json($tasmiStoreUrl);
    const tasmiUpdateUrl = @json($tasmiUpdateUrl);
    function setTasmiField(key, value) {
        const field = document.getElementById('tasmi_' + key);
        if (field) field.value = value === null || value === undefined ? '' : value;
    }
    function openTasmiModal() {
        const form = document.getElementById('tasmiForm');
        if (! form || ! window.bootstrap) return;
        form.reset();
        form.action = tasmiStoreUrl;
        document.getElementById('tasmiMethod').disabled = true;
        document.getElementById('tasmiModalTitle').innerHTML = '<i class="ri-award-line me-1"></i>Buat Sesi Tasmi’';
        document.getElementById('tasmiSubmit').innerHTML = '<i class="ri-save-line me-1"></i>Simpan Sesi';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('tasmiModal')).show();
    }
    function openTasmiEditModal(data) {
        const form = document.getElementById('tasmiForm');
        if (! form || ! data || ! window.bootstrap) return;
        form.action = tasmiUpdateUrl.replace('__ID__', data.id);
        document.getElementById('tasmiMethod').disabled = false;
        document.getElementById('tasmiModalTitle').innerHTML = '<i class="ri-edit-line me-1"></i>Edit Sesi Tasmi’';
        document.getElementById('tasmiSubmit').innerHTML = '<i class="ri-save-line me-1"></i>Simpan Perubahan';
        ['school_id','academic_year_id','session_name','session_date','session_time_start','session_time_end','location','session_type','status'].forEach(function (key) {
            let value = data[key];
            if ((key === 'session_time_start' || key === 'session_time_end') && value) value = value.slice(0, 5);
            setTasmiField(key, value);
        });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('tasmiModal')).show();
    }
    document.addEventListener('DOMContentLoaded', function () {
        @if($errors->any() && $canManage)
            openTasmiModal();
        @endif
        document.querySelectorAll('.edit-tasmi').forEach(function (button) {
            button.addEventListener('click', function () { openTasmiEditModal(JSON.parse(this.dataset.session)); });
        });
        document.querySelectorAll('.delete-tasmi').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('deleteTasmiForm').action = this.dataset.action;
                document.getElementById('deleteTasmiName').textContent = this.dataset.name;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteTasmiModal')).show();
            });
        });
    });
</script>
@endpush
