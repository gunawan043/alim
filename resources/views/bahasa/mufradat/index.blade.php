@extends('layouts.master')

@section('title', 'Mufradat')

@php
    $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null);
@endphp

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') Hafalan Mufradat @endslot
        @slot('title') Periode Mufradat @endslot
    @endcomponent

    {{-- FLASH --}}
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

    {{-- STATS CARDS --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2">
                                <i class="ri-calendar-2-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Periode</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($periods->count()) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label">
                        <span class="badge bg-success-subtle text-success">{{ $periods->where('is_active', true)->count() }} aktif</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2">
                                <i class="ri-book-open-line text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Kosakata</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($periods->sum('vocabularies_count')) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Seluruh periode</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2">
                                <i class="ri-check-double-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Setoran</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($periods->sum('setorans_count')) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Catatan setoran</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="ri-graduation-cap-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Target Pekanan</p>
                            <h3 class="fw-bold ff-secondary mb-0">10 <span class="fs-16 text-muted">kata</span></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label"><i class="ri-information-line me-1"></i>Standar minimal</p>
                </div>
            </div>
        </div>
    </div>

    {{-- CARD UTAMA --}}
    <div class="card" id="mufradatListCard">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0"><i class="ri-book-2-line text-primary me-1"></i>Daftar Periode Mufradat</h5>
                    <p class="text-muted mb-0 small">Kelola daftar kosakata, jadwal setoran, mustami, dan evaluasi pekanan.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="toolbar-actions">
                        @if($canManage)
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createPeriodModal">
                                <i class="ri-add-line me-1"></i> Buat Periode
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
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">Sekolah</label>
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
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-1">Cari</label>
                        <input type="search" id="mufradatSearch" class="form-control form-control-sm" placeholder="Cari periode...">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label small mb-1">Status</label>
                        <select id="mufradatStatus" class="form-select form-select-sm">
                            <option value="">Semua status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                            <i class="ri-filter-3-line me-1"></i> Filter
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="ri-table-line me-1"></i> Kolom
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-3" style="width:220px">
                                <div class="form-check">
                                    <input class="form-check-input mufradat-column" type="checkbox" data-column="rentang" id="colRentang" checked>
                                    <label class="form-check-label" for="colRentang">Rentang</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input mufradat-column" type="checkbox" data-column="mading" id="colMading" checked>
                                    <label class="form-check-label" for="colMading">Mading</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input mufradat-column" type="checkbox" data-column="kosakata" id="colKosakata" checked>
                                    <label class="form-check-label" for="colKosakata">Kosakata</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input mufradat-column" type="checkbox" data-column="setoran" id="colSetoran" checked>
                                    <label class="form-check-label" for="colSetoran">Setoran</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            {{-- COUNTER --}}
            <div class="d-flex align-items-center gap-2 mb-2">
                <small class="text-muted">
                    <i class="ri-file-list-line me-1"></i> Menampilkan
                    <strong>{{ $periods->count() }}</strong> periode
                </small>
            </div>

            {{-- TABEL --}}
            <div class="table-container">
                <table class="table table-hover align-middle table-freeze mb-0" id="mufradatTable" style="font-size:13px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:50px;">No</th>
                            <th>Periode</th>
                            <th data-column="rentang">Rentang</th>
                            <th data-column="mading">Mading</th>
                            <th class="text-center" data-column="kosakata">Kosakata</th>
                            <th class="text-center" data-column="setoran">Setoran</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width:80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($periods as $i => $period)
                            <tr data-status="{{ $period->is_active ? '1' : '0' }}">
                                <td class="text-center text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <a class="fw-semibold text-reset" href="{{ route('user.bahasa.mufradat.show', ['userId' => $userId, 'period' => $period->id]) }}">{{ $period->title }}</a>
                                    <div class="text-muted small">{{ $period->academicYear?->name ?? 'Tanpa tahun ajaran' }} · {{ $period->min_words }} kata/pekan</div>
                                </td>
                                <td data-column="rentang" class="text-muted small">{{ $period->period_start?->format('d/m/Y') ?? '—' }} — {{ $period->period_end?->format('d/m/Y') ?? '—' }}</td>
                                <td data-column="mading" class="small">
                                    <div><i class="ri-school-line me-1 text-muted"></i>{{ $period->location_school ?: '—' }}</div>
                                    <div><i class="ri-home-heart-line me-1 text-muted"></i>{{ $period->location_dorm ?: '—' }}</div>
                                </td>
                                <td data-column="kosakata" class="text-center">
                                    <span class="badge bg-info-subtle text-info">{{ $period->vocabularies_count }}</span>
                                </td>
                                <td data-column="setoran" class="text-center">
                                    <span class="badge bg-warning-subtle text-warning">{{ $period->setorans_count }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $period->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $period->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown" title="Aksi lain">
                                            <i class="ri-more-2-fill"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route('user.bahasa.mufradat.show', ['userId' => $userId, 'period' => $period->id]) }}"><i class="ri-eye-line me-2 text-info"></i>Buka</a></li>
                                            @if($canRecord ?? false)
                                                <li><a class="dropdown-item" href="{{ route('user.bahasa.mufradat.attendance', ['userId' => $userId, 'period' => $period->id]) }}"><i class="ri-check-double-line me-2 text-success"></i>Input Setoran</a></li>
                                            @endif
                                            <li><a class="dropdown-item" href="{{ route('user.bahasa.mufradat.report', ['userId' => $userId, 'period' => $period->id]) }}"><i class="ri-file-chart-line me-2 text-warning"></i>Laporan</a></li>
                                            @if($canManage)
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="{{ route('user.bahasa.mufradat.edit', ['userId' => $userId, 'period' => $period->id]) }}"><i class="ri-pencil-line me-2 text-warning"></i>Edit</a></li>
                                                <li><button type="button" class="dropdown-item text-danger delete-period" data-action="{{ route('user.bahasa.mufradat.destroy', ['userId' => $userId, 'period' => $period->id]) }}" data-title="{{ $period->title }}"><i class="ri-delete-bin-line me-2"></i>Hapus</button></li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <lord-icon
                                        src="https://cdn.lordicon.com/msoeawqm.json"
                                        trigger="loop"
                                        colors="primary:#121331,secondary:#08a88a"
                                        style="width:75px;height:75px">
                                    </lord-icon>
                                    <h6 class="text-muted mb-1 mt-3">Belum ada periode mufradat</h6>
                                    <p class="text-muted mb-0 small">Buat periode pertama untuk memulai.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div id="mufradatEmpty" class="text-center text-muted py-4 d-none">
                <i class="ri-search-line fs-3 d-block mb-2"></i>
                <p class="mb-0 small">Tidak ada periode yang cocok dengan filter.</p>
            </div>
        </div>
    </div>

    {{-- MODAL: BUAT PERIODE --}}
    @if($canManage)
        <div class="modal fade" id="createPeriodModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('user.bahasa.mufradat.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $schoolId }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-add-circle-line me-2 text-primary"></i>Buat Periode Mufradat</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small mb-1">Nama Periode <span class="text-danger">*</span></label>
                                <input name="title" class="form-control" placeholder="Pekan 1 — Oktober 2026" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Minimal Kosakata</label>
                                <input type="number" name="min_words" min="1" max="100" value="10" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Tingkat</label>
                                <select name="grade_level_id" class="form-select">
                                    <option value="">Semua tingkat</option>
                                    @foreach($gradeLevels as $grade)
                                        <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Kelas</label>
                                <select name="study_group_id" class="form-select">
                                    <option value="">Semua kelas</option>
                                    @foreach($studyGroups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Tanggal Mulai</label>
                                <input type="date" name="period_start" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Tanggal Selesai</label>
                                <input type="date" name="period_end" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Mading Sekolah</label>
                                <input name="location_school" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Mading Asrama</label>
                                <input name="location_dorm" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">Catatan</label>
                                <textarea name="notes" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="newPeriodActive">
                                    <label class="form-check-label" for="newPeriodActive">Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-3-line me-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL: HAPUS PERIODE --}}
        <div class="modal fade" id="deletePeriodModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" id="deletePeriodForm" action="#">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body text-center p-4">
                        <div class="avatar-lg mx-auto mb-3">
                            <span class="avatar-title bg-danger-subtle rounded-circle fs-1 text-danger">
                                <i class="ri-delete-bin-6-line"></i>
                            </span>
                        </div>
                        <h5>Hapus Periode?</h5>
                        <p class="text-muted mb-0">Periode <strong id="deletePeriodTitle">ini</strong> beserta mufradat, jadwal, dan setorannya akan dihapus.</p>
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
(function () {
    const search = document.getElementById('mufradatSearch');
    const status = document.getElementById('mufradatStatus');
    const rows = Array.from(document.querySelectorAll('#mufradatTable tbody tr[data-status]'));
    const empty = document.getElementById('mufradatEmpty');

    function filterRows() {
        const term = (search?.value || '').toLowerCase();
        const value = status?.value || '';
        let visible = 0;
        rows.forEach(function (row) {
            const text = row.textContent.toLowerCase();
            const show = (!term || text.includes(term)) && (!value || row.dataset.status === value);
            row.classList.toggle('d-none', !show);
            if (show) visible++;
        });
        empty?.classList.toggle('d-none', visible > 0);
    }

    search?.addEventListener('input', filterRows);
    status?.addEventListener('change', filterRows);

    document.querySelectorAll('.mufradat-column').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            document.querySelectorAll('#mufradatTable [data-column="' + checkbox.dataset.column + '"]').forEach(function (cell) {
                cell.classList.toggle('d-none', !checkbox.checked);
            });
        });
    });

    document.querySelectorAll('.delete-period').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('deletePeriodForm').action = button.dataset.action;
            document.getElementById('deletePeriodTitle').textContent = button.dataset.title;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deletePeriodModal')).show();
        });
    });
})();
</script>
@endpush