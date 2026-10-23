@extends('layouts.master')
@section('title') Penugasan GTK dari Pondok @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Personalia @endslot
        @slot('title') Penugasan GTK dari Pondok @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">Total Penugasan</p>
                            <h3 class="mb-0">{{ $stats['total'] }}</h3>
                        </div>
                        <div class="avatar-sm"><span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3"><i class="ri-building-2-line"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">Aktif</p>
                            <h3 class="mb-0">{{ $stats['active'] }}</h3>
                        </div>
                        <div class="avatar-sm"><span class="avatar-title bg-success-subtle text-success rounded-circle fs-3"><i class="ri-checkbox-circle-line"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">Dicabut</p>
                            <h3 class="mb-0">{{ $stats['inactive'] }}</h3>
                        </div>
                        <div class="avatar-sm"><span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-3"><i class="ri-close-circle-line"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1">Satuan Pendidikan</p>
                            <h3 class="mb-0">{{ $stats['schools'] }}</h3>
                        </div>
                        <div class="avatar-sm"><span class="avatar-title bg-info-subtle text-info rounded-circle fs-3"><i class="ri-school-line"></i></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-1">Penugasan GTK Lintas Unit</h5>
                <p class="text-muted mb-0">Personalia menetapkan GTK dan satuan pendidikan tujuan. Mapel, rombel, dan JP diatur satuan pendidikan melalui SK.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPenugasanModal">
                <i class="ri-add-line me-1"></i> Tambah Penugasan
            </button>
        </div>

        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-4">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Cari GTK</label>
                    <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama GTK...">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Satuan Pendidikan</label>
                    <select name="school_id" class="form-select">
                        <option value="">Semua satuan pendidikan</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}" {{ request('school_id') === $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Dicabut</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <button class="btn btn-primary w-100" type="submit"><i class="ri-search-line me-1"></i> Filter</button>
                </div>
                <div class="col-lg-2 col-md-6">
                    <a href="{{ route('user.cross-unit-assignments.index', ['userId' => $userId]) }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>GTK</th>
                            <th>Work Unit Asal</th>
                            <th>Ditugaskan di</th>
                            <th>Tahun Ajaran</th>
                            <th>Mapel Rekomendasi</th>
                            <th>SK / Masa Tugas</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs me-2">
                                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                                {{ strtoupper(substr($assignment->user?->name ?? 'G', 0, 1)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $assignment->user?->name ?? '-' }}</div>
                                            <small class="text-muted">GTK Penugasan Pondok</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $assignment->homeWorkUnit?->name ?? '-' }}</td>
                                <td>
                                    <div class="fw-medium">{{ $assignment->school?->name ?? '-' }}</div>
                                    <small class="text-muted">Bukan anggota work unit sekolah</small>
                                </td>
                                <td>{{ $assignment->academicYear?->name ?? '-' }}</td>
                                <td>{{ $assignment->subject?->name ?? 'Ditentukan satuan pendidikan' }}</td>
                                <td>
                                    <div>{{ $assignment->decree_number ?: '-' }}</div>
                                    <small class="text-muted">
                                        {{ $assignment->start_date?->format('d M Y') ?? '-' }}
                                        @if($assignment->end_date) — {{ $assignment->end_date->format('d M Y') }} @endif
                                    </small>
                                </td>
                                <td>
                                    @if($assignment->status === 'active')
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Dicabut</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button"
                                            class="btn btn-sm btn-soft-primary me-1 btn-edit-penugasan"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editPenugasanModal"
                                            data-id="{{ $assignment->id }}"
                                            data-user-id="{{ $assignment->user_id }}"
                                            data-home-work-unit-id="{{ $assignment->home_work_unit_id }}"
                                            data-school-id="{{ $assignment->school_id }}"
                                            data-academic-year-id="{{ $assignment->academic_year_id }}"
                                            data-subject-id="{{ $assignment->subject_id }}"
                                            data-decree-number="{{ $assignment->decree_number }}"
                                            data-start-date="{{ $assignment->start_date?->format('Y-m-d') }}"
                                            data-end-date="{{ $assignment->end_date?->format('Y-m-d') }}"
                                            data-notes="{{ $assignment->notes }}"
                                            title="Edit penugasan">
                                        <i class="ri-pencil-line"></i>
                                    </button>
                                    <form method="POST" action="{{ route('user.cross-unit-assignments.toggle', ['userId' => $userId, 'id' => $assignment->id]) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $assignment->status === 'active' ? 'btn-soft-danger' : 'btn-soft-success' }}">
                                            {{ $assignment->status === 'active' ? 'Cabut' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="ri-building-2-line fs-1 d-block mb-2"></i>
                                    Belum ada penugasan lintas unit.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $assignments->links() }}</div>
        </div>
    </div>

    <div class="modal fade" id="addPenugasanModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.cross-unit-assignments.store', ['userId' => $userId]) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-add-line me-1"></i>Tambah Penugasan Pondok</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2">
                            Personalia hanya menetapkan GTK dan satuan pendidikan tujuan. Mapel, rombel, dan JP ditentukan satuan pendidikan di halaman SK.
                        </div>
                        @include('personalia.cross-unit-assignments._form', ['assignment' => null, 'activeAcademicYearId' => $activeAcademicYearId])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Penugasan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editPenugasanModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" id="editPenugasanForm" action="">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-pencil-line me-1"></i>Edit Penugasan Pondok</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2">
                            Perubahan Personalia tidak mengubah mapel, rombel, atau JP yang sudah disusun satuan pendidikan.
                        </div>
                        @include('personalia.cross-unit-assignments._form', ['assignment' => null, 'activeAcademicYearId' => $activeAcademicYearId])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Perbarui Penugasan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editForm = document.getElementById('editPenugasanForm');
    document.querySelectorAll('.btn-edit-penugasan').forEach(function (button) {
        button.addEventListener('click', function () {
            const data = button.dataset;
            editForm.action = '{{ route('user.cross-unit-assignments.update', ['userId' => $userId, 'id' => '__ID__']) }}'.replace('__ID__', data.id);
            editForm.querySelector('[name="user_id"]').value = data.userId || '';
            editForm.querySelector('[name="home_work_unit_id"]').value = data.homeWorkUnitId || '';
            editForm.querySelector('[name="school_id"]').value = data.schoolId || '';
            editForm.querySelector('[name="academic_year_id"]').value = data.academicYearId || '';
            editForm.querySelector('[name="subject_id"]').value = data.subjectId || '';
            editForm.querySelector('[name="decree_number"]').value = data.decreeNumber || '';
            editForm.querySelector('[name="start_date"]').value = data.startDate || '';
            editForm.querySelector('[name="end_date"]').value = data.endDate || '';
            editForm.querySelector('[name="notes"]').value = data.notes || '';
        });
    });
});
</script>
@endsection
