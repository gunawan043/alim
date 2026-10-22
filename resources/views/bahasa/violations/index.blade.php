@extends('layouts.master')

@section('title', 'Pelanggaran Bahasa')

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') Pelanggaran Bahasa @endslot
        @slot('title') Pencatatan Pelanggaran @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><div class="avatar-sm"><span class="avatar-title bg-danger-subtle rounded fs-2"><i class="ri-error-warning-line text-danger"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Pelanggaran</p><h3 class="fw-bold mb-0">{{ number_format($stats['total']) }}</h3></div></div><div class="mt-2 text-muted" style="font-size:11px;">Seluruh catatan</div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><div class="avatar-sm"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-calendar-event-line text-warning"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Bulan Ini</p><h3 class="fw-bold mb-0">{{ number_format($stats['month']) }}</h3></div></div><div class="mt-2"><span class="badge bg-warning-subtle text-warning">{{ number_format($stats['points']) }} poin</span></div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><div class="avatar-sm"><span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-shield-check-line text-success"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Sudah Dibina</p><h3 class="fw-bold mb-0">{{ number_format($stats['coached']) }}</h3></div></div><div class="mt-2 text-muted" style="font-size:11px;">Ditindaklanjuti</div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2"><div class="avatar-sm"><span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-scales-3-line text-primary"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Ambang Wakil</p><h3 class="fw-bold mb-0">100<small class="fw-normal text-muted fs-6 ms-1">poin</small></h3></div></div><div class="mt-2 text-muted" style="font-size:11px;">Pembinaan Wakil Departemen</div></div></div></div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0"><i class="ri-error-warning-line text-danger me-1"></i>Daftar Pelanggaran Bahasa</h5>
                    <p class="text-muted mb-0 small">Pencatatan pelanggaran wajib berbahasa Arab dan tindak lanjut pembinaan.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-sm-end">
                        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
                            <x-bahasa-filter-fields
                                :schools="$schools"
                                :grade-levels="$gradeLevels"
                                :study-groups="$studyGroups"
                                :show-school="$showSchool"
                                :school-id="$schoolId"
                                :grade-level-id="$gradeLevelId"
                                :study-group-id="$studyGroupId"
                            />
                            <div><label class="form-label small mb-1">Bulan</label><input type="month" name="month" value="{{ request('month') }}" class="form-control" style="width:150px"></div>
                            <div><label class="form-label small mb-1">Status</label><select name="status" class="form-select" style="width:140px"><option value="">Semua status</option><option value="recorded" @selected(request('status') === 'recorded')>Tercatat</option><option value="coached" @selected(request('status') === 'coached')>Sudah Dibina</option><option value="closed" @selected(request('status') === 'closed')>Selesai</option></select></div>
                            <div><label class="form-label small mb-1">Cari</label><input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Nama santri..." style="width:180px"></div>
                            <div><button class="btn btn-soft-primary"><i class="ri-filter-3-line me-1"></i>Filter</button></div>
                        </form>
                        <a href="{{ route('user.bahasa.violations.report', ['userId' => $userId]) }}" class="btn btn-soft-primary"><i class="ri-bar-chart-2-line me-1"></i>Rekap</a>
                        @if($canRecord)
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createViolationModal"><i class="ri-add-line me-1"></i>Catat</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Tanggal</th><th>Santri</th><th>Kelas / Asrama</th><th>Jenis Pelanggaran</th><th>Tempat</th><th class="text-center">Poin</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        @forelse($violations as $violation)
                            <tr>
                                <td class="text-muted small">{{ $violation->violation_date?->format('d/m/Y') }}</td>
                                <td><div class="fw-semibold">{{ $violation->student?->name ?? '-' }}</div><div class="text-muted small">{{ $violation->reporter?->name ? 'Dicatat: '.$violation->reporter->name : '' }}</div></td>
                                <td class="small">{{ $violation->class_name ?: '-' }}<div class="text-muted">{{ $violation->dormitory?->name ?? '' }}</div></td>
                                <td>{{ $violation->violation_type }}</td>
                                <td class="text-muted small">{{ $violation->place ?: '-' }}</td>
                                <td class="text-center"><span class="badge bg-warning-subtle text-warning">{{ $violation->points }}</span></td>
                                <td><span class="badge {{ $violation->status === 'recorded' ? 'bg-secondary-subtle text-secondary' : 'bg-success-subtle text-success' }}">{{ $violation->status_label }}</span></td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-soft-secondary" data-bs-toggle="dropdown"><i class="ri-more-2-fill"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route('user.bahasa.violations.show', ['userId' => $userId, 'violation' => $violation->id]) }}"><i class="ri-eye-line me-2 text-info"></i>Detail</a></li>
                                            @if($canManage)
                                                <li><a class="dropdown-item" href="{{ route('user.bahasa.violations.edit', ['userId' => $userId, 'violation' => $violation->id]) }}"><i class="ri-pencil-line me-2 text-warning"></i>Edit</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><button type="button" class="dropdown-item text-danger delete-violation" data-action="{{ route('user.bahasa.violations.destroy', ['userId' => $userId, 'violation' => $violation->id]) }}" data-title="{{ $violation->student?->name }}"><i class="ri-delete-bin-line me-2"></i>Hapus</button></li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-5">Belum ada pelanggaran pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($violations->hasPages())<div class="card-footer">{{ $violations->links() }}</div>@endif
    </div>

    @if($canRecord)
        <div class="modal fade" id="createViolationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('user.bahasa.violations.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $schoolId }}">
                    <div class="modal-header"><h5 class="modal-title"><i class="ri-add-circle-line me-1 text-danger"></i>Catat Pelanggaran Bahasa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-7"><label class="form-label">Santri <span class="text-danger">*</span></label><select name="student_id" class="form-select" required><option value="">— Pilih santri —</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->name }} — {{ $student->currentClassHistory?->studyGroup?->name ?? 'Tanpa kelas' }}</option>@endforeach</select></div>
                            <div class="col-md-5"><label class="form-label">Tanggal <span class="text-danger">*</span></label><input type="date" name="violation_date" value="{{ now()->toDateString() }}" class="form-control" required></div>
                            <div class="col-md-8"><label class="form-label">Jenis Pelanggaran <span class="text-danger">*</span></label><input name="violation_type" class="form-control" placeholder="Contoh: berbicara selain bahasa Arab" required></div>
                            <div class="col-md-4"><label class="form-label">Poin <span class="text-danger">*</span></label><input type="number" name="points" min="1" max="1000" value="5" class="form-control" required></div>
                            <div class="col-md-4"><label class="form-label">Kelas</label><input name="class_name" class="form-control" placeholder="7A"></div>
                            <div class="col-md-4"><label class="form-label">Asrama</label><select name="dormitory_id" class="form-select"><option value="">— Pilih —</option>@foreach($dormitories as $dormitory)<option value="{{ $dormitory->id }}">{{ $dormitory->name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Tempat</label><input name="place" class="form-control" placeholder="Asrama / kelas"></div>
                            <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="description" rows="2" class="form-control"></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger"><i class="ri-save-line me-1"></i>Simpan Pelanggaran</button></div>
                </form>
            </div>
        </div>
    @endif

    @if($canManage)
        <div class="modal fade" id="deleteViolationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="POST" id="deleteViolationForm" action="#">@csrf @method('DELETE')<div class="modal-body text-center p-4"><div class="avatar-lg mx-auto mb-3"><span class="avatar-title bg-danger-subtle rounded-circle fs-1 text-danger"><i class="ri-delete-bin-6-line"></i></span></div><h5>Hapus Pelanggaran?</h5><p class="text-muted mb-0">Catatan pelanggaran <strong id="deleteViolationTitle">ini</strong> akan dihapus permanen.</p></div><div class="modal-footer justify-content-center"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger">Hapus</button></div></form></div>
        </div>
    @endif
@endsection

@section('script')
<script>
document.querySelectorAll('.delete-violation').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('deleteViolationForm').action = button.dataset.action;
        document.getElementById('deleteViolationTitle').textContent = button.dataset.title || 'ini';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteViolationModal')).show();
    });
});
</script>
@endsection
