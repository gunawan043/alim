@extends('layouts.master')

@section('title', 'Sertifikasi Bahasa')

@php
    $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null);
@endphp

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') Ujian & Sertifikasi @endslot
        @slot('title') Sertifikasi Bahasa @endslot
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
                    <h5 class="card-title mb-0">Sertifikasi Bahasa</h5>
                    <p class="text-muted mb-0 small">Pencatatan sertifikat kemampuan Bahasa Arab dan Bahasa Inggris santri.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="toolbar-actions">
                        @if($canManage)
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCertificationModal">
                                <i class="ri-add-line me-1"></i> Tambah Sertifikasi
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
                                <i class="ri-award-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Sertifikasi</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Seluruh sertifikasi</p>
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
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Terbit</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['issued'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Sertifikat telah terbit</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2">
                                <i class="ri-time-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Menunggu</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['pending'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Menunggu proses</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-2">
                                <i class="ri-translate-2 text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Bahasa Arab</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['arab'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Sertifikasi Bahasa Arab</p>
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
                    <div class="col-12 col-md-2">
                        <label class="form-label small mb-1">Cari Santri</label>
                        <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Nama santri">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label small mb-1">Bahasa</label>
                        <select name="language" class="form-select">
                            <option value="">Semua</option>
                            <option value="arab" @selected(request('language') === 'arab')>Bahasa Arab</option>
                            <option value="inggris" @selected(request('language') === 'inggris')>Bahasa Inggris</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua</option>
                            <option value="issued" @selected(request('status') === 'issued')>Terbit</option>
                            <option value="pending" @selected(request('status') === 'pending')>Menunggu</option>
                            <option value="revoked" @selected(request('status') === 'revoked')>Dicabut</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="ri-filter-3-line me-1"></i> Terapkan
                        </button>
                        <a href="{{ route('user.bahasa.certifications.index', ['userId' => $userId]) }}"
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
                    <strong>{{ $certifications->count() }}</strong> dari
                    <strong>{{ $certifications->total() }}</strong> sertifikasi
                </small>
            </div>

            {{-- TABEL --}}
            <div class="table-container">
                <table class="table table-hover align-middle table-freeze mb-0" style="font-size:13px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:50px;">No</th>
                            <th>Tanggal</th>
                            <th>Santri</th>
                            <th>Sertifikasi</th>
                            <th>Bahasa</th>
                            <th>Level</th>
                            <th class="text-center">Nilai</th>
                            <th>No. Sertifikat</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width:100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($certifications as $i => $item)
                            <tr>
                                <td class="text-center text-muted">{{ $certifications->firstItem() + $i }}</td>
                                <td class="text-muted small">{{ $item->issued_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="fw-semibold">{{ $item->student?->name ?? '—' }}</td>
                                <td>{{ $item->certification_name }}</td>
                                <td>{{ $item->language_label }}</td>
                                <td>{{ $item->level ?: '—' }}</td>
                                <td class="text-center">{{ $item->score !== null ? number_format((float) $item->score, 1, ',', '.') : '—' }}</td>
                                <td class="small">{{ $item->certificate_number ?: '—' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $item->status === 'issued' ? 'bg-success-subtle text-success' : ($item->status === 'pending' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger') }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="{{ route('user.bahasa.certifications.print', ['userId' => $userId, 'certification' => $item->id]) }}"
                                           target="_blank" class="btn btn-outline-primary" title="Cetak">
                                            <i class="ri-printer-line"></i>
                                        </a>
                                        @if($canManage)
                                            <button type="button" class="btn btn-outline-danger delete-certification"
                                                    data-action="{{ route('user.bahasa.certifications.destroy', ['userId' => $userId, 'certification' => $item->id]) }}"
                                                    data-title="{{ $item->student?->name }}" title="Hapus">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <lord-icon
                                        src="https://cdn.lordicon.com/msoeawqm.json"
                                        trigger="loop"
                                        colors="primary:#121331,secondary:#08a88a"
                                        style="width:75px;height:75px">
                                    </lord-icon>
                                    <h6 class="text-muted mb-1 mt-3">Belum ada sertifikasi</h6>
                                    <p class="text-muted mb-0 small">Belum ada sertifikasi bahasa.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($certifications->hasPages())
                @include('shared._pagination', ['paginator' => $certifications->withQueryString()])
            @endif
        </div>
    </div>

    {{-- MODAL: TAMBAH SERTIFIKASI --}}
    @if($canManage)
        <div class="modal fade" id="createCertificationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('user.bahasa.certifications.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $schoolId }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-award-line me-2 text-primary"></i>Tambah Sertifikasi Bahasa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label small mb-1">Santri <span class="text-danger">*</span></label>
                                <select name="student_id" class="form-select" required>
                                    <option value="">— Pilih santri —</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}">{{ $student->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small mb-1">Nama Sertifikasi <span class="text-danger">*</span></label>
                                <input name="certification_name" class="form-control" placeholder="Sertifikasi Bahasa Arab" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Bahasa <span class="text-danger">*</span></label>
                                <select name="language" class="form-select">
                                    <option value="arab">Bahasa Arab</option>
                                    <option value="inggris">Bahasa Inggris</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Level</label>
                                <input name="level" class="form-control" placeholder="A1 / Mutawassith">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Nilai</label>
                                <input type="number" min="0" max="100" step="0.01" name="score" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Tanggal Terbit</label>
                                <input type="date" name="issued_date" value="{{ now()->toDateString() }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">No. Sertifikat</label>
                                <input name="certificate_number" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Status</label>
                                <select name="status" class="form-select">
                                    <option value="issued">Terbit</option>
                                    <option value="pending">Menunggu</option>
                                    <option value="revoked">Dicabut</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small mb-1">Catatan</label>
                                <textarea name="notes" rows="2" class="form-control"></textarea>
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

        {{-- MODAL: HAPUS SERTIFIKASI --}}
        <div class="modal fade" id="deleteCertificationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" id="deleteCertificationForm" action="#">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body text-center p-4">
                        <div class="avatar-lg mx-auto mb-3">
                            <span class="avatar-title bg-danger-subtle rounded-circle fs-1 text-danger">
                                <i class="ri-delete-bin-6-line"></i>
                            </span>
                        </div>
                        <h5>Hapus Sertifikasi?</h5>
                        <p class="text-muted mb-0"><strong id="deleteCertificationTitle">Sertifikasi ini</strong> akan dihapus permanen.</p>
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
document.querySelectorAll('.delete-certification').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('deleteCertificationForm').action = button.dataset.action;
        document.getElementById('deleteCertificationTitle').textContent = button.dataset.title || 'Sertifikasi ini';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteCertificationModal')).show();
    });
});
</script>
@endpush