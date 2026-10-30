@extends('layouts.master')

@section('title', 'Pelanggaran Bahasa')
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') Pelanggaran Bahasa @endslot
        @slot('title') Pencatatan Pelanggaran @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- ============================================================
         STATS CARDS
    ============================================================ --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2">
                                <i class="ri-error-warning-line text-danger"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Pelanggaran</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Seluruh catatan</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2">
                                <i class="ri-calendar-event-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Bulan Ini</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['month'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small">
                        <span class="badge bg-warning-subtle text-warning">{{ number_format($stats['points'] ?? 0) }} poin</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="ri-shield-check-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Sudah Dibina</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['coached'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Ditindaklanjuti</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2">
                                <i class="ri-scales-3-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Ambang Wakil</p>
                            <h3 class="fw-bold ff-secondary mb-0">100 <span class="fs-16 text-muted">poin</span></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Pembinaan Wakil Departemen</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         CARD UTAMA
    ============================================================ --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-error-warning-line text-danger me-1"></i>Daftar Pelanggaran Bahasa</h5>
                            <p class="text-muted mb-0 small">Pencatatan pelanggaran wajib berbahasa Arab dan tindak lanjut pembinaan.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <a href="{{ route('user.bahasa.violations.report', ['userId' => $userId]) }}"
                                   class="btn btn-outline-secondary" title="Rekap Pelanggaran">
                                    <i class="ri-bar-chart-2-line me-1"></i> Rekap
                                </a>
                                @if($canRecord)
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createViolationModal">
                                        <i class="ri-add-line me-1"></i> Catat
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- FILTER CEPAT --}}
                    <form method="GET" class="mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-6 col-lg-4">
                                <x-bahasa-filter-fields
                                    :schools="$schools"
                                    :grade-levels="$gradeLevels"
                                    :study-groups="$studyGroups"
                                    :show-school="$showSchool"
                                    :school-id="$schoolId"
                                    :grade-level-id="$gradeLevelId"
                                    :study-group-id="$studyGroupId"
                                />
                            </div>
                            <div class="col-12 col-md-3 col-lg-2">
                                <label class="form-label small mb-1">Bulan</label>
                                <input type="month" name="month" value="{{ request('month') }}" class="form-control">
                            </div>
                            <div class="col-12 col-md-3 col-lg-2">
                                <label class="form-label small mb-1">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">Semua status</option>
                                    <option value="recorded" @selected(request('status') === 'recorded')>Tercatat</option>
                                    <option value="coached" @selected(request('status') === 'coached')>Sudah Dibina</option>
                                    <option value="closed" @selected(request('status') === 'closed')>Selesai</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 col-lg-2">
                                <label class="form-label small mb-1">Cari</label>
                                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Nama santri...">
                            </div>
                            <div class="col-12 col-md-auto d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-shrink-0">
                                    <i class="ri-filter-3-line me-1"></i> Terapkan
                                </button>
                                <a href="{{ route('user.bahasa.violations.index', ['userId' => $userId]) }}"
                                   class="btn btn-light flex-shrink-0" title="Reset filter">
                                    <i class="ri-refresh-line"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- COUNTER --}}
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $violations->count() }}</strong> dari
                            <strong>{{ $violations->total() }}</strong> pelanggaran
                        </small>
                    </div>

                    {{-- TABEL --}}
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze" style="font-size:13px;">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Tanggal</th>
                                    <th>Santri</th>
                                    <th>Kelas / Asrama</th>
                                    <th>Jenis Pelanggaran</th>
                                    <th>Tempat</th>
                                    <th class="text-center">Poin</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width:80px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($violations ?? [] as $i => $violation)
                                    <tr>
                                        <td class="text-center text-muted">{{ $violations->firstItem() + $i }}</td>
                                        <td class="text-muted small">{{ $violation->violation_date?->format('d/m/Y') }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $violation->student?->name ?? '—' }}</div>
                                            <div class="text-muted small">
                                                {{ $violation->reporter?->name ? 'Dicatat: '.$violation->reporter->name : '' }}
                                            </div>
                                        </td>
                                        <td class="small">
                                            {{ $violation->class_name ?: '—' }}
                                            <div class="text-muted">{{ $violation->dormitory?->name ?? '' }}</div>
                                        </td>
                                        <td>{{ $violation->violation_type }}</td>
                                        <td class="text-muted small">{{ $violation->place ?: '—' }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-warning-subtle text-warning">{{ $violation->points }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $violation->status === 'recorded' ? 'bg-secondary-subtle text-secondary' : 'bg-success-subtle text-success' }}">
                                                {{ $violation->status_label }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('user.bahasa.violations.show', ['userId' => $userId, 'violation' => $violation->id]) }}"
                                                   class="btn btn-outline-primary" title="Detail">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                @if($canManage)
                                                    <div class="dropdown">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="dropdown" title="Aksi lain">
                                                            <i class="ri-more-2-fill"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('user.bahasa.violations.edit', ['userId' => $userId, 'violation' => $violation->id]) }}">
                                                                    <i class="ri-pencil-line me-2 text-warning"></i>Edit
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <button type="button" class="dropdown-item text-danger delete-violation"
                                                                        data-action="{{ route('user.bahasa.violations.destroy', ['userId' => $userId, 'violation' => $violation->id]) }}"
                                                                        data-title="{{ $violation->student?->name }}">
                                                                    <i class="ri-delete-bin-line me-2"></i>Hapus
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="9" title="Belum ada pelanggaran"
                                        message="Belum ada pelanggaran yang tercatat pada filter ini." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($violations->hasPages())
                        @include('shared._pagination', ['paginator' => $violations->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: CATAT PELANGGARAN --}}
    @if($canRecord)
        <div class="modal fade" id="createViolationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('user.bahasa.violations.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $schoolId }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-add-circle-line me-2 text-danger"></i>Catat Pelanggaran Bahasa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label small mb-1">Santri <span class="text-danger">*</span></label>
                                <select name="student_id" class="form-select" required>
                                    <option value="">— Pilih santri —</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}">
                                            {{ $student->name }} — {{ $student->currentClassHistory?->studyGroup?->name ?? 'Tanpa kelas' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small mb-1">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="violation_date" value="{{ now()->toDateString() }}" class="form-control" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small mb-1">Jenis Pelanggaran <span class="text-danger">*</span></label>
                                <input name="violation_type" class="form-control" placeholder="Contoh: berbicara selain bahasa Arab" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Poin <span class="text-danger">*</span></label>
                                <input type="number" name="points" min="1" max="1000" value="5" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Kelas</label>
                                <input name="class_name" class="form-control" placeholder="7A">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Asrama</label>
                                <select name="dormitory_id" class="form-select">
                                    <option value="">— Pilih —</option>
                                    @foreach($dormitories as $dormitory)
                                        <option value="{{ $dormitory->id }}">{{ $dormitory->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Tempat</label>
                                <input name="place" class="form-control" placeholder="Asrama / kelas">
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">Deskripsi</label>
                                <textarea name="description" rows="2" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-3-line me-1"></i> Simpan Pelanggaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL: HAPUS PELANGGARAN --}}
    @if($canManage)
        <div class="modal fade" id="deleteViolationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" id="deleteViolationForm" action="#">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body text-center p-4">
                        <div class="avatar-lg mx-auto mb-3">
                            <span class="avatar-title bg-danger-subtle rounded-circle fs-1 text-danger">
                                <i class="ri-delete-bin-6-line"></i>
                            </span>
                        </div>
                        <h5>Hapus Pelanggaran?</h5>
                        <p class="text-muted mb-0">Catatan pelanggaran <strong id="deleteViolationTitle">ini</strong> akan dihapus permanen.</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="ri-delete-bin-line me-1"></i> Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
document.querySelectorAll('.delete-violation').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('deleteViolationForm').action = button.dataset.action;
        document.getElementById('deleteViolationTitle').textContent = button.dataset.title || 'ini';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteViolationModal')).show();
    });
});
</script>
@endpush