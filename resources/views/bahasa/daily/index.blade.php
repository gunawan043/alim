@extends('layouts.master')

@section('title', $type === 'jasus' ? 'Monitoring Jasus' : 'Percakapan Harian')
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') Operasional Harian @endslot
        @slot('title') {{ $type === 'jasus' ? 'Monitoring Jasus' : 'Percakapan Harian' }} @endslot
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
                                <i class="ri-chat-3-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Percakapan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['percakapan'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Catatan percakapan harian</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2">
                                <i class="ri-spy-line text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Monitoring Jasus</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['jasus'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Catatan monitoring jasus</p>
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
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Catatan bulan berjalan</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2">
                                <i class="ri-alert-line text-danger"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Ada Temuan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['findings'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Perlu tindak lanjut</p>
                </div>
            </div>
        </div>
    </div>

    {{-- CARD UTAMA --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center mb-2">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">{{ $type === 'jasus' ? 'Monitoring Jasus' : 'Percakapan Harian' }}</h5>
                            <p class="text-muted mb-0 small">Catatan pelaksanaan bahasa oleh guru bahasa dan pengurus asrama; Departemen Bahasa sebagai pengawas.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                @if($canRecord)
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDailyModal">
                                        <i class="ri-add-line me-1"></i> Tambah Catatan
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Tab nav-pills --}}
                    <ul class="nav nav-pills gap-1">
                        <li class="nav-item">
                            <a class="nav-link {{ $type === 'percakapan' ? 'active' : '' }}"
                               href="{{ route('user.bahasa.daily.index', ['userId' => $userId, 'type' => 'percakapan']) }}">
                                <i class="ri-chat-3-line me-1"></i>Percakapan Harian
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $type === 'jasus' ? 'active' : '' }}"
                               href="{{ route('user.bahasa.daily.index', ['userId' => $userId, 'type' => 'jasus']) }}">
                                <i class="ri-spy-line me-1"></i>Monitoring Jasus
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    {{-- FILTER --}}
                    <form method="GET" class="mb-3">
                        <input type="hidden" name="type" value="{{ $type }}">

                        <x-bahasa-filter-fields
                            :schools="$schools"
                            :grade-levels="$gradeLevels"
                            :study-groups="$studyGroups"
                            :show-school="$showSchool"
                            :school-id="$schoolId"
                            :grade-level-id="$gradeLevelId"
                            :study-group-id="$studyGroupId"
                        />

                        <div class="row g-2 align-items-end mt-2">
                            <div class="col-12 col-md-6 col-lg-3">
                                <label class="form-label small mb-1">Bulan</label>
                                <input type="month" name="month" value="{{ request('month') }}" class="form-control">
                            </div>

                            <div class="col-12 col-md-6 col-lg-6">
                                <label class="form-label small mb-1">Cari</label>
                                <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Tema, temuan, atau kelas">
                            </div>

                            <div class="col-12 col-md-auto d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-shrink-0">
                                    <i class="ri-filter-3-line me-1"></i> Terapkan
                                </button>
                                <a href="{{ route('user.bahasa.daily.index', ['userId' => $userId, 'type' => $type]) }}"
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
                            <strong>{{ $logs->count() }}</strong> dari
                            <strong>{{ $logs->total() }}</strong> catatan
                        </small>
                    </div>

                    {{-- TABEL --}}
                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze" style="font-size:13px;">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:50px;">No</th>
                                    <th>Tanggal</th>
                                    <th>Tema / Temuan</th>
                                    <th>Kelas / Lokasi</th>
                                    <th>Mustami</th>
                                    <th class="text-center">Peserta</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width:80px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs ?? [] as $i => $log)
                                    <tr>
                                        <td class="text-center text-muted">{{ $logs->firstItem() + $i }}</td>
                                        <td class="text-muted small">{{ $log->log_date?->format('d/m/Y') }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $log->theme ?: $log->type_label }}</div>
                                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit((string) $log->findings, 90) }}</div>
                                        </td>
                                        <td class="small">
                                            {{ $log->class_name ?: '—' }}
                                            <div class="text-muted">{{ $log->location ?: '' }}</div>
                                        </td>
                                        <td>{{ $log->mustami?->name ?? '—' }}</td>
                                        <td class="text-center">{{ number_format($log->participants_count) }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $log->status === 'perlu_tindak_lanjut' ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }}">
                                                <i class="ri-{{ $log->status === 'perlu_tindak_lanjut' ? 'alert-line' : 'checkbox-circle-line' }} me-1"></i>
                                                {{ $log->status === 'perlu_tindak_lanjut' ? 'Perlu Tindak Lanjut' : 'Selesai' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                @if($canManage)
                                                    <button type="button" class="btn btn-outline-danger delete-daily"
                                                            data-action="{{ route('user.bahasa.daily.destroy', ['userId' => $userId, 'log' => $log->id]) }}"
                                                            data-title="{{ $log->theme ?: $log->type_label }}"
                                                            title="Hapus">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="8" title="Belum ada catatan"
                                        message="Belum ada catatan operasional harian." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($logs->hasPages())
                        @include('shared._pagination', ['paginator' => $logs->withQueryString()])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: CATAT HARIAN --}}
    @if($canRecord)
        <div class="modal fade" id="createDailyModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('user.bahasa.daily.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $schoolId }}">
                    <input type="hidden" name="log_type" value="{{ $type }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-add-circle-line me-2 text-primary"></i>{{ $type === 'jasus' ? 'Catat Monitoring Jasus' : 'Catat Percakapan Harian' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="log_date" value="{{ now()->toDateString() }}" class="form-control" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small mb-1">Tema / Judul</label>
                                <input name="theme" class="form-control" placeholder="{{ $type === 'jasus' ? 'Temuan penggunaan bahasa' : 'Tema percakapan' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Kelas</label>
                                <input name="class_name" class="form-control" placeholder="7A">
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
                                <label class="form-label small mb-1">Kelas (filter)</label>
                                <select name="study_group_id" class="form-select">
                                    <option value="">Semua kelas</option>
                                    @foreach($studyGroups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Lokasi</label>
                                <input name="location" class="form-control" placeholder="Asrama / kelas">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Mustami / Petugas</label>
                                <select name="mustami_id" class="form-select">
                                    <option value="">— Pilih —</option>
                                    @foreach($mustamis as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Jumlah Peserta</label>
                                <input type="number" min="0" name="participants_count" value="0" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Status</label>
                                <select name="status" class="form-select">
                                    <option value="selesai">Selesai</option>
                                    <option value="perlu_tindak_lanjut">Perlu Tindak Lanjut</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">{{ $type === 'jasus' ? 'Temuan' : 'Catatan Percakapan' }}</label>
                                <textarea name="findings" rows="3" class="form-control"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">Tindak Lanjut</label>
                                <textarea name="follow_up" rows="2" class="form-control"></textarea>
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
    @endif

    {{-- MODAL: HAPUS CATATAN --}}
    @if($canManage)
        <div class="modal fade" id="deleteDailyModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" id="deleteDailyForm" action="#">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body text-center p-4">
                        <div class="avatar-lg mx-auto mb-3">
                            <span class="avatar-title bg-danger-subtle rounded-circle fs-1 text-danger">
                                <i class="ri-delete-bin-6-line"></i>
                            </span>
                        </div>
                        <h5>Hapus Catatan?</h5>
                        <p class="text-muted mb-0"><strong id="deleteDailyTitle">Catatan ini</strong> akan dihapus permanen.</p>
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
document.querySelectorAll('.delete-daily').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('deleteDailyForm').action = button.dataset.action;
        document.getElementById('deleteDailyTitle').textContent = button.dataset.title || 'Catatan ini';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteDailyModal')).show();
    });
});
</script>
@endpush