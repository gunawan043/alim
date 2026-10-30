@extends('layouts.master')

@section('title', 'Review Nama Arab GTK')

@php
    $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null);
@endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.gtk.index', ['userId' => $userId]) }}">Data GTK</a> @endslot
        @slot('li_2') Review Nama Arab @endslot
        @slot('title') Review &amp; Approval Nama Arab GTK @endslot
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
                            <span class="avatar-title {{ ($stats['duplicate_nupy'] ?? 0) > 0 ? 'bg-danger-subtle' : 'bg-secondary-subtle' }} rounded fs-2">
                                <i class="ri-error-warning-line {{ ($stats['duplicate_nupy'] ?? 0) > 0 ? 'text-danger' : 'text-secondary' }}"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0 stat-label">NUPY Ganda</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['duplicate_nupy'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0 stat-label small"><i class="ri-information-line me-1"></i>Perlu perbaikan NUPY</p>
                </div>
            </div>
        </div>
    </div>

    {{-- CARD UTAMA --}}
    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0"><i class="ri-check-double-line text-success me-1"></i>Review &amp; Approval Nama Arab GTK</h5>
                    <p class="text-muted mb-0 small">Nama berstatus <strong>Disetujui</strong> dipakai pada dokumen resmi (rapor). Impor/generator menghasilkan status <em>menunggu tinjauan</em>.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="toolbar-actions">
                        <a href="{{ route('user.gtk.import-nama-arab-form', ['userId' => $userId]) }}" class="btn btn-outline-dark btn-sm">
                            <i class="ri-upload-2-line me-1"></i> Import / Generate
                        </a>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#approveAllGtkModal">
                            <i class="ri-check-double-line me-1"></i> Setujui Semua ({{ number_format($stats['pending'] ?? 0) }})
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- FILTER CHIP --}}
        <div class="card-header py-2 bg-light border-bottom">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small fw-semibold"><i class="ri-filter-3-line me-1"></i>Filter:</span>
                @foreach([
                    'pending' => 'Menunggu',
                    'missing' => 'Belum Ada',
                    'approved' => 'Disetujui',
                    'all' => 'Semua',
                ] as $key => $label)
                    <a href="{{ route('user.gtk.review-nama-arab', ['userId' => $userId, 'status' => $key, 'search' => request('search')]) }}"
                       class="filter-badge {{ $status === $key ? 'active' : '' }}">{{ $label }}</a>
                @endforeach

                <form method="GET" class="d-flex gap-2 ms-auto">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="text" name="search" class="form-control" style="width:200px"
                           placeholder="Cari nama / NUPY..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary"><i class="ri-search-line"></i></button>
                </form>
            </div>
        </div>

        <div class="card-body">
            {{-- COUNTER --}}
            <div class="d-flex align-items-center gap-2 mb-2">
                <small class="text-muted">
                    <i class="ri-file-list-line me-1"></i> Menampilkan
                    <strong>{{ $employees->count() }}</strong> dari
                    <strong>{{ $employees->total() }}</strong> GTK
                </small>
            </div>

            {{-- TABEL --}}
            <div class="table-container">
                <table class="table table-hover align-middle table-freeze mb-0" style="font-size:13px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:50px;">No</th>
                            <th style="width:150px;">NUPY</th>
                            <th>Nama Latin</th>
                            <th style="width:280px;">Nama Arab</th>
                            <th class="text-center" style="width:150px;">Status</th>
                            <th class="text-center" style="width:80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $i => $employee)
                            <tr>
                                <td class="text-center text-muted">{{ $employees->firstItem() + $i }}</td>
                                <td class="small text-muted">{{ $employee->employment?->nupy ?: '—' }}</td>
                                <td class="fw-medium">{{ $employee->name }}</td>
                                <td>
                                    <form method="POST"
                                          action="{{ route('user.gtk.review-nama-arab-approve', ['userId' => $userId, 'gtkUserId' => $employee->id]) }}"
                                          class="d-flex gap-2">
                                        @csrf
                                        <input type="text" name="name_ar" class="form-control" dir="rtl"
                                               value="{{ $employee->name_ar }}" placeholder="الاسم بالعربية" maxlength="255" required>
                                        <button type="submit" class="btn btn-outline-success flex-shrink-0" title="Simpan & setujui">
                                            <i class="ri-check-line"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-center">
                                    @if(! $employee->name_ar)
                                        <span class="badge bg-secondary-subtle text-secondary">Belum ada</span>
                                    @elseif($employee->name_ar_status === 'approved')
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="ri-checkbox-circle-line me-1"></i>Disetujui
                                        </span>
                                        @if($employee->name_ar_reviewed_at)
                                            <div class="text-muted small">{{ $employee->name_ar_reviewed_at->format('d M Y H:i') }}</div>
                                        @endif
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">
                                            <i class="ri-time-line me-1"></i>Menunggu
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="{{ route('user.gtk.edit', ['userId' => $userId, 'uuid' => $employee->id]) }}"
                                           class="btn btn-outline-secondary" title="Edit data GTK">
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

            @if($employees->hasPages())
                @include('shared._pagination', ['paginator' => $employees->withQueryString()])
            @endif
        </div>
    </div>
@endsection

@section('modal')
    <form id="approveAllGtkForm" method="POST"
          action="{{ route('user.gtk.review-nama-arab-approve-all', ['userId' => $userId]) }}">
        @csrf
    </form>

    <x-modal-confirm id="approveAllGtkModal"
        icon="https://cdn.lordicon.com/lupuorrc.json"
        title="Setujui Semua Nama Arab GTK?"
        message="Seluruh nama Arab GTK berstatus <strong>menunggu tinjauan</strong> akan disetujui dan dipakai pada dokumen resmi."
        submit-label="Ya, Setujui Semua"
        submit-icon="ri-check-double-line"
        submit-class="btn-success"
        form-id="approveAllGtkForm" />
@endsection