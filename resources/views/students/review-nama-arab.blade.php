@extends('layouts.master')

@section('title', 'Review Nama Arab Santri')

@php
    $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null);

    // Fallback: kalau controller tidak kirim $status, ambil dari request
    $status = $status ?? request('status', 'pending');

    $statusBadges = [
        'pending' => ['label' => 'Menunggu Tinjauan', 'class' => 'bg-warning-subtle text-warning'],
        'approved' => ['label' => 'Disetujui', 'class' => 'bg-success-subtle text-success'],
    ];
@endphp

@section('css')
    <style>
        /* Fallback CSS .filter-badge — jika belum ada di bahasa._styles */
        .filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border: 1px solid var(--bs-border-color);
            border-radius: 30px;
            font-size: 13px;
            background: transparent;
            color: var(--bs-body-color);
            transition: all .2s;
            text-decoration: none;
            cursor: pointer;
        }
        .filter-badge:hover {
            background: var(--bs-tertiary-bg);
            color: var(--bs-body-color);
            border-color: var(--bs-border-color);
        }
        .filter-badge.active {
            background: var(--bs-primary);
            border-color: var(--bs-primary);
            color: #fff;
        }
        .filter-badge.active i {
            color: #fff;
        }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
        @slot('li_2') Review Nama Arab @endslot
        @slot('title') Review &amp; Approval Nama Arab @endslot
    @endcomponent

    {{-- DEBUG — hapus setelah fix --}}
    <!-- @if(config('app.debug'))
        <div class="alert alert-light border small mb-3">
            <strong>Debug:</strong>
            <code>$status = {{ var_export($status, true) }}</code> ·
            <code>request('status') = {{ var_export(request('status'), true) }}</code> ·
            <code>URL = {{ request()->fullUrl() }}</code>
        </div>
    @endif -->

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
                            <span class="avatar-title bg-secondary-subtle rounded fs-2">
                                <i class="ri-user-unfollow-line text-secondary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Belum Ada Nama Arab</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['missing'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Belum ada nama Arab</p>
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
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Menunggu Tinjauan</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['pending'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Perlu ditinjau</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="ri-checkbox-circle-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Disetujui</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['approved'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Sudah disetujui</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title {{ ($stats['duplicate_nis'] ?? 0) > 0 ? 'bg-danger-subtle' : 'bg-secondary-subtle' }} rounded fs-2">
                                <i class="ri-error-warning-line {{ ($stats['duplicate_nis'] ?? 0) > 0 ? 'text-danger' : 'text-secondary' }}"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">NIS Ganda</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['duplicate_nis'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Perlu perbaikan NIS</p>
                </div>
            </div>
        </div>
    </div>

    {{-- CARD UTAMA --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0"><i class="ri-check-double-line text-success me-1"></i>Review &amp; Approval Nama Arab Santri</h5>
                    <p class="text-muted mb-0 small">Nama berstatus <strong>Disetujui</strong> yang dipakai pada dokumen resmi (rapor). Impor/generator menghasilkan status <em>menunggu tinjauan</em>.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="toolbar-actions">
                        <a href="{{ route('user.students.import-nama-arab-form', ['userId' => $userId]) }}" class="btn btn-outline-dark btn-sm">
                            <i class="ri-upload-2-line me-1"></i> Import / Generate
                        </a>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#approveAllStudentsModal">
                            <i class="ri-check-double-line me-1"></i> Setujui Semua ({{ number_format($stats['pending'] ?? 0) }})
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- FILTER CEPAT (chip) — pola absensi --}}
        <div class="card-header py-2 bg-light border-bottom">
            <div class="d-flex flex-wrap align-items-center">
                <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'pending', 'page' => null]) }}"
                   class="filter-badge {{ $status === 'pending' ? 'active' : '' }}">
                    <i class="ri-time-line"></i> Menunggu
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'missing', 'page' => null]) }}"
                   class="filter-badge {{ $status === 'missing' ? 'active' : '' }}">
                    <i class="ri-user-unfollow-line"></i> Belum Ada
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'approved', 'page' => null]) }}"
                   class="filter-badge {{ $status === 'approved' ? 'active' : '' }}">
                    <i class="ri-checkbox-circle-line"></i> Disetujui
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'all', 'page' => null]) }}"
                   class="filter-badge {{ $status === 'all' ? 'active' : '' }}">
                    <i class="ri-list-unordered"></i> Semua
                </a>
            </div>
        </div>

        <div class="card-body">
            {{-- FORM FILTER --}}
            <form method="GET" class="mb-3">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Cari</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="Cari nama / NIS / NISN..." value="{{ request('search') }}">
                    </div>
                    <div class="col-12 col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-shrink-0">
                            <i class="ri-search-line me-1"></i> Tampilkan
                        </button>
                        <a href="{{ route('user.students.review-nama-arab', ['userId' => $userId]) }}"
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
                    <strong>{{ $students->count() }}</strong> dari
                    <strong>{{ $students->total() }}</strong> santri
                </small>
            </div>

            {{-- TABEL --}}
            <div class="table-container">
                <table class="table table-hover align-middle table-freeze mb-0" style="font-size:13px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:50px;">No</th>
                            <th style="width:130px;">NIS / NISN</th>
                            <th>Nama Latin</th>
                            <th style="width:280px;">Nama Arab</th>
                            <th class="text-center" style="width:150px;">Status</th>
                            <th class="text-center" style="width:80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $i => $student)
                            <tr>
                                <td class="text-center text-muted">{{ $students->firstItem() + $i }}</td>
                                <td class="small text-muted">{{ $student->nis ?: '—' }} / {{ $student->nisn ?: '—' }}</td>
                                <td class="fw-medium">{{ $student->name }}</td>
                                <td>
                                    <form method="POST"
                                          action="{{ route('user.students.review-nama-arab-approve', ['userId' => $userId, 'studentId' => $student->id]) }}"
                                          class="d-flex gap-2">
                                        @csrf
                                        <input type="text" name="name_ar" class="form-control" dir="rtl"
                                               value="{{ $student->name_ar }}" placeholder="الاسم بالعربية" maxlength="255" required>
                                        <button type="submit" class="btn btn-outline-success flex-shrink-0" title="Simpan & setujui">
                                            <i class="ri-check-line"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-center">
                                    @if(! $student->name_ar)
                                        <span class="badge bg-secondary-subtle text-secondary">Belum ada</span>
                                    @elseif($student->name_ar_status === 'approved')
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="ri-checkbox-circle-line me-1"></i>Disetujui
                                        </span>
                                        @if($student->name_ar_reviewed_at)
                                            <div class="text-muted small">{{ $student->name_ar_reviewed_at->format('d M Y H:i') }}</div>
                                        @endif
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">
                                            <i class="ri-time-line me-1"></i>Menunggu
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="{{ route('user.students.edit', ['userId' => $userId, 'santriUuid' => $student->id]) }}"
                                           class="btn btn-outline-secondary" title="Edit data santri">
                                            <i class="ri-pencil-line"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <lord-icon
                                        src="https://cdn.lordicon.com/msoeawqm.json"
                                        trigger="loop"
                                        colors="primary:#121331,secondary:#08a88a"
                                        style="width:75px;height:75px">
                                    </lord-icon>
                                    <h6 class="text-muted mb-1 mt-3">Tidak ada data</h6>
                                    <p class="text-muted mb-0 small">Tidak ada data pada filter ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($students->hasPages())
                @include('shared._pagination', ['paginator' => $students->withQueryString()])
            @endif
        </div>
    </div>
@endsection

@section('modal')
    <form id="approveAllStudentsForm" method="POST"
          action="{{ route('user.students.review-nama-arab-approve-all', ['userId' => $userId]) }}">
        @csrf
    </form>

    <x-modal-confirm id="approveAllStudentsModal"
        icon="https://cdn.lordicon.com/lupuorrc.json"
        title="Setujui Semua Nama Arab?"
        message="Seluruh nama Arab santri berstatus <strong>menunggu tinjauan</strong> akan disetujui dan langsung dipakai pada dokumen resmi (rapor bahasa Arab)."
        submit-label="Ya, Setujui Semua"
        submit-icon="ri-check-double-line"
        submit-class="btn-success"
        form-id="approveAllStudentsForm" />
@endsection