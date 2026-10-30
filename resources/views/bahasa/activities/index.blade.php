@extends('layouts.master')

@section('title', 'AM Nasyath Syahri')

@php
    $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null);
@endphp

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') AM Nasyath Syahri @endslot
        @slot('title') Kegiatan Bahasa Bulanan @endslot
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

    {{-- HEADER HALAMAN --}}
    <div class="card mb-3">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0">AM Nasyath Syahri</h5>
                    <p class="text-muted mb-0 small">Lomba pidato, permainan kata, film, drama, dan kegiatan bahasa lainnya.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="toolbar-actions">
                        @if($canManage)
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createActivityModal">
                                <i class="ri-add-line me-1"></i> Buat Kegiatan
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Kegiatan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Seluruh kegiatan</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2">
                                <i class="ri-calendar-event-line text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Direncanakan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['planned'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Belum terlaksana</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="ri-check-double-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Selesai</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['completed'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Sudah terlaksana</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2">
                                <i class="ri-group-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Partisipasi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['participants'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Total peserta tercatat</p>
                </div>
            </div>
        </div>
    </div>

    {{-- CARD UTAMA --}}
    <div class="card">
        <div class="card-body">
            {{-- FILTER CEPAT --}}
            <form method="GET" class="mb-3">
                <div class="row g-2 align-items-end">
                    <x-bahasa-filter-fields
                        :schools="$schools"
                        :grade-levels="$gradeLevels"
                        :study-groups="$studyGroups"
                        :show-school="$showSchool"
                        :school-id="$schoolId"
                        :grade-level-id="$gradeLevelId"
                        :study-group-id="$studyGroupId"
                    />
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label small mb-1">Bulan</label>
                        <input type="month" name="month" value="{{ request('month') }}" class="form-control">
                    </div>
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua</option>
                            <option value="planned" @selected(request('status') === 'planned')>Direncanakan</option>
                            <option value="completed" @selected(request('status') === 'completed')>Selesai</option>
                            <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="ri-filter-3-line me-1"></i> Terapkan
                        </button>
                        <a href="{{ route('user.bahasa.activities.index', ['userId' => $userId]) }}"
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
                    <strong>{{ $activities->count() }}</strong> dari
                    <strong>{{ $activities->total() }}</strong> kegiatan
                </small>
            </div>

            {{-- TABEL --}}
            <div class="table-container">
                <table class="table table-hover align-middle table-freeze mb-0" style="font-size:13px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:50px;">No</th>
                            <th>Bulan</th>
                            <th>Kegiatan</th>
                            <th>Jenis</th>
                            <th>Tanggal</th>
                            <th>Pelaksana</th>
                            <th class="text-center">Peserta</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width:120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $i => $activity)
                            <tr>
                                <td class="text-center text-muted">{{ $activities->firstItem() + $i }}</td>
                                <td>{{ $activity->period_month?->format('m/Y') }}</td>
                                <td>
                                    <a href="{{ route('user.bahasa.activities.show', ['userId' => $userId, 'activity' => $activity->id]) }}" class="fw-semibold text-reset">{{ $activity->title }}</a>
                                    <div class="text-muted small">{{ $activity->theme ?: '—' }}</div>
                                </td>
                                <td>{{ $types[$activity->activity_type] ?? $activity->activity_type }}</td>
                                <td class="text-muted small">
                                    {{ $activity->activity_date?->format('d/m/Y') ?? '—' }}
                                    <div>{{ $activity->time_start ? substr($activity->time_start, 0, 5) : '' }}{{ $activity->time_end ? ' - '.substr($activity->time_end, 0, 5) : '' }}</div>
                                </td>
                                <td>{{ ucfirst($activity->executor_type) }}{{ $activity->executor_name ? ' · '.$activity->executor_name : '' }}</td>
                                <td class="text-center">{{ $activity->participants_count }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $activity->status === 'completed' ? 'bg-success-subtle text-success' : ($activity->status === 'cancelled' ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info') }}">
                                        {{ ucfirst($activity->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="{{ route('user.bahasa.activities.show', ['userId' => $userId, 'activity' => $activity->id]) }}"
                                           class="btn btn-outline-primary" title="Lihat">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                        @if($canManage)
                                            <a href="{{ route('user.bahasa.activities.edit', ['userId' => $userId, 'activity' => $activity->id]) }}"
                                               class="btn btn-outline-warning" title="Edit">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger delete-activity"
                                                    data-action="{{ route('user.bahasa.activities.destroy', ['userId' => $userId, 'activity' => $activity->id]) }}"
                                                    data-title="{{ $activity->title }}" title="Hapus">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <lord-icon
                                        src="https://cdn.lordicon.com/msoeawqm.json"
                                        trigger="loop"
                                        colors="primary:#121331,secondary:#08a88a"
                                        style="width:75px;height:75px">
                                    </lord-icon>
                                    <h6 class="text-muted mb-1 mt-3">Belum ada kegiatan</h6>
                                    <p class="text-muted mb-0 small">Belum ada kegiatan pada filter ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($activities->hasPages())
                @include('shared._pagination', ['paginator' => $activities->withQueryString()])
            @endif
        </div>
    </div>

    {{-- MODAL: BUAT KEGIATAN --}}
    @if($canManage)
        <div class="modal fade" id="createActivityModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('user.bahasa.activities.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $schoolId }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-add-circle-line me-2 text-primary"></i>Buat Kegiatan Bahasa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Bulan <span class="text-danger">*</span></label>
                                <input type="month" name="period_month" value="{{ now()->format('Y-m') }}" class="form-control" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small mb-1">Nama Kegiatan <span class="text-danger">*</span></label>
                                <input name="title" class="form-control" placeholder="Lomba Pidato Bahasa Arab" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Jenis <span class="text-danger">*</span></label>
                                <select name="activity_type" class="form-select" required>
                                    @foreach($types as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Tingkat</label>
                                <select name="grade_level_id" class="form-select">
                                    <option value="">Semua tingkat</option>
                                    @foreach($gradeLevels as $grade)
                                        <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Kelas</label>
                                <select name="study_group_id" class="form-select">
                                    <option value="">Semua kelas</option>
                                    @foreach($studyGroups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Tanggal</label>
                                <input type="date" name="activity_date" value="{{ now()->toDateString() }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Tempat</label>
                                <input name="place" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Tema</label>
                                <input name="theme" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Pelaksana <span class="text-danger">*</span></label>
                                <select name="executor_type" class="form-select">
                                    @foreach(['santri' => 'Santri', 'guru' => 'Guru Bahasa', 'departemen' => 'Departemen', 'asrama' => 'Asrama'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Nama Pelaksana</label>
                                <input name="executor_name" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Waktu Mulai</label>
                                <input type="time" name="time_start" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Waktu Selesai</label>
                                <input type="time" name="time_end" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Status</label>
                                <select name="status" class="form-select">
                                    <option value="planned">Direncanakan</option>
                                    <option value="completed">Selesai</option>
                                    <option value="cancelled">Dibatalkan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">MC</label>
                                <input name="mc_name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Jumlah Peserta</label>
                                <input type="number" min="0" name="participants_count" value="0" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">Panitia</label>
                                <textarea name="committee" rows="2" class="form-control"></textarea>
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

        {{-- MODAL: HAPUS KEGIATAN --}}
        <div class="modal fade" id="deleteActivityModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" id="deleteActivityForm" action="#">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body text-center p-4">
                        <div class="avatar-lg mx-auto mb-3">
                            <span class="avatar-title bg-danger-subtle rounded-circle fs-1 text-danger">
                                <i class="ri-delete-bin-6-line"></i>
                            </span>
                        </div>
                        <h5>Hapus Kegiatan?</h5>
                        <p class="text-muted mb-0">Kegiatan <strong id="deleteActivityTitle">ini</strong> akan dihapus permanen.</p>
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
document.querySelectorAll('.delete-activity').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('deleteActivityForm').action = button.dataset.action;
        document.getElementById('deleteActivityTitle').textContent = button.dataset.title || 'ini';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteActivityModal')).show();
    });
});
</script>
@endpush