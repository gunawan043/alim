@extends('layouts.master')
@section('title') Scan QR Kunjungan @endsection

@section('css')
<style>
    #qr-reader { width: 100%; max-width: 400px; margin: 0 auto; }
    #qr-reader video { border-radius: 8px; }
    #scan-result { display: none; }
    .status-dot { display:inline-block; width:.65rem; height:.65rem; border-radius:50%; margin-right:.35rem; vertical-align:middle; }

    /* Filter chips (pola Data GTK) */
    .filter-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 30px;
        font-size: 13px;
        transition: all 0.2s;
        margin: 0;
        cursor: pointer;
        background: #fff;
    }
    .filter-badge:hover { background: #405189; border-color: #94a3b8; color: #fff; }
    .filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }
    .filter-badge .remove-filter { cursor: pointer; margin-left: 4px; opacity: 0.85; text-decoration: none; color: inherit; }
    .filter-badge .remove-filter:hover { opacity: 1; }
    .filter-group { background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
    .filter-group-title { font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
    .filter-group-title i { color: #0a5f9e; font-size: 18px; }

    /* Stepper tabs (pola nav-tabs-custom ala Velzon) */
    .nav-tabs-custom { border-bottom: 2px solid #e2e8f0; }
    .nav-tabs-custom .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        color: #64748b;
        font-weight: 500;
        padding: 12px 18px;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .nav-tabs-custom .nav-link:hover { color: #0a5f9e; border-bottom-color: #cbd5e1; }
    .nav-tabs-custom .nav-link.active {
        color: #0a5f9e;
        border-bottom-color: #0a5f9e;
        background: transparent;
    }
    .nav-tabs-custom .step-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        margin-right: 4px;
    }
    .nav-tabs-custom .nav-link.active .step-number {
        background: #0a5f9e;
        color: #fff;
    }

    /* Soft alert backgrounds for tab info strips */
    .bg-soft-info { background: #f0f9ff; }
    .bg-soft-warning { background: #fffbeb; }
    .bg-soft-success { background: #f0fdf4; }

        .table-container {
        position: relative;
        width: 100%;
        overflow-x: auto;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Kunjungan</a> @endslot
        @slot('li_4') Scan QR @endslot
        @slot('title') Scan QR Kunjungan Tamu @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div id="scan-result" class="alert alert-dismissible fade show" role="alert">
        <i id="scan-result-icon" class="me-2"></i>
        <span id="scan-result-text"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            {{-- Scanner Card --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-qr-scan-2-line me-2 text-primary"></i>Scan QR Kunjungan Tamu</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-pills mb-3" id="scanTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="camera-tab" data-bs-toggle="tab"
                                    data-bs-target="#camera-panel" type="button" role="tab">
                                <i class="ri-camera-line me-1"></i> Kamera
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="manual-tab" data-bs-toggle="tab"
                                    data-bs-target="#manual-panel" type="button" role="tab">
                                <i class="ri-keyboard-line me-1"></i> Manual
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="scanTabContent">
                        <div class="tab-pane fade show active" id="camera-panel" role="tabpanel">
                            <div id="qr-reader" class="mb-3"></div>
                            <div id="qr-reader--status" class="text-center text-muted small"></div>
                        </div>

                        <div class="tab-pane fade" id="manual-panel" role="tabpanel">
                            <form id="manual-form" method="POST"
                                  action="{{ route('user.asrama.visits.scan.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">URL QR Kunjungan <span class="text-danger">*</span></label>
                                    <textarea name="scan_url" id="manual_url" rows="3"
                                              class="form-control @error('scan_url') is-invalid @enderror"
                                              placeholder="Tempel URL lengkap hasil scan QR (termasuk signature)" required>{{ old('scan_url') }}</textarea>
                                    @error('scan_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Aksi otomatis: <strong>check-in</strong> jika kunjungan disetujui, <strong>check-out</strong> jika tamu sudah berada di asrama.</small>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ri-check-line me-1"></i> Verifikasi
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter Bar (pola Data GTK) --}}
            <div class="card mb-3">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0">
                                    <i class="ri-filter-3-line me-2 text-primary"></i>Daftar Kunjungan
                                </h5>
                                <p class="text-muted mb-0 mt-1" style="font-size: 13px;">
                                    Hanya menampilkan kunjungan yang <strong>mengajukan</strong> (approved / arrived / checked_out) sesuai filter.
                                    @if($activeFilterCount > 0)
                                        <span class="badge bg-primary-subtle text-primary ms-1">{{ $activeFilterCount }} filter aktif</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-sm-auto">
                            <form method="GET" action="{{ route('user.asrama.visits.scan', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" id="scanFilterForm">
                                <div class="d-flex flex-wrap align-items-start gap-2">
                                    <div class="d-flex gap-2">
                                        <input type="text" name="search" value="{{ $search }}"
                                               class="form-control form-control-sm" id="globalSearch"
                                               placeholder="Cari nama tamu / nama siswa…" style="width: 260px;">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="ri-search-line"></i> Cari
                                        </button>
                                    </div>

                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#filterModal">
                                        <i class="bx bx-filter-alt align-bottom me-1"></i> Filter Lanjutan
                                        @if($activeFilterCount > 0)
                                            <span class="badge bg-light text-dark ms-1">{{ $activeFilterCount }}</span>
                                        @endif
                                    </button>

                                    @if($activeFilterCount > 0)
                                        <a href="{{ route('user.asrama.visits.scan', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                           class="btn btn-sm btn-outline-secondary" title="Reset filter">
                                            <i class="ri-close-line"></i> Reset
                                        </a>
                                    @endif
                                </div>

                                {{-- Period chips --}}
                                <div class="mt-2 d-flex flex-wrap gap-1">
                                    @php
                                        $periods = [
                                            'today' => 'Hari Ini',
                                            'week'  => '7 Hari',
                                            'month' => '30 Hari',
                                            'all'   => 'Semua',
                                        ];
                                    @endphp
                                    @foreach($periods as $key => $label)
                                        <button type="button" class="filter-badge {{ $period === $key ? 'active' : '' }}"
                                                onclick="setPeriod('{{ $key }}')">
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>

                                <input type="hidden" name="period" id="periodInput" value="{{ $period }}">
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Active filter badges --}}
                @if($activeFilterCount > 0)
                    <div class="card-body border-bottom" style="padding: 12px 20px;">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <small class="text-muted fw-semibold">Filter Aktif:</small>
                            @if($search !== '')
                                <span class="filter-badge active">
                                    Pencarian: "{{ $search }}"
                                    <a href="{{ route('user.asrama.visits.scan', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'period' => $period, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                                       class="remove-filter text-white" title="Hapus filter ini">×</a>
                                </span>
                            @endif
                            @if($period !== 'today')
                                <span class="filter-badge active">
                                    Periode: {{ $periods[$period] ?? $period }}
                                    <a href="{{ route('user.asrama.visits.scan', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'search' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                                       class="remove-filter text-white" title="Hapus filter ini">×</a>
                                </span>
                            @endif
                            @if($dateFrom)
                                <span class="filter-badge active">
                                    Dari: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}
                                    <a href="{{ route('user.asrama.visits.scan', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'search' => $search, 'period' => $period, 'date_to' => $dateTo]) }}"
                                       class="remove-filter text-white" title="Hapus filter ini">×</a>
                                </span>
                            @endif
                            @if($dateTo)
                                <span class="filter-badge active">
                                    Sampai: {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                                    <a href="{{ route('user.asrama.visits.scan', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'search' => $search, 'period' => $period, 'date_from' => $dateFrom]) }}"
                                       class="remove-filter text-white" title="Hapus filter ini">×</a>
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Modal Filter Lanjutan --}}
            <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="GET" action="{{ route('user.asrama.visits.scan', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                            <div class="modal-header">
                                <h5 class="modal-title" id="filterModalLabel">
                                    <i class="bx bx-filter-alt me-2"></i>Filter Lanjutan
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="search" value="{{ $search }}">

                                <div class="filter-group">
                                    <div class="filter-group-title"><i class="ri-calendar-line"></i> Rentang Tanggal Manual</div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small">Dari Tanggal</label>
                                            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Sampai Tanggal</label>
                                            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-2" style="font-size: 11px;">
                                        <i class="ri-information-line"></i> Jika diisi, akan mengabaikan filter periode.
                                    </small>
                                </div>

                                <div class="alert alert-light border mb-0" style="font-size: 12px;">
                                    <i class="ri-information-line me-1 text-primary"></i>
                                    Halaman ini hanya menampilkan kunjungan yang telah <strong>mengajukan</strong> (status: approved, arrived, checked_out).
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-check-line me-1"></i> Terapkan Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Wizard Tabs: 1) Wali belum datang → 2) Wali di pos, santri keluar → 3) Santri sudah kembali --}}
            <div class="card">
                <div class="card-header border-bottom-dashed pb-0">
                    <ul class="nav nav-tabs nav-tabs-custom" id="visitStepper" role="tablist" style="border-bottom: none;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="step-pending-tab" data-bs-toggle="tab"
                                    data-bs-target="#step-pending" type="button" role="tab">
                                <span class="step-number">1</span>
                                <i class="ri-user-add-line me-1"></i>
                                <span>Santri Belum Keluar</span>
                                <span class="badge bg-info-subtle text-info ms-2">{{ $approvedAwaiting->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="step-arrived-tab" data-bs-toggle="tab"
                                    data-bs-target="#step-arrived" type="button" role="tab">
                                <span class="step-number">2</span>
                                <i class="ri-user-unfollow-line me-1"></i>
                                <span>Santri Sedang Keluar</span>
                                <span class="badge bg-warning-subtle text-warning ms-2">{{ $arrivedAwaiting->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="step-done-tab" data-bs-toggle="tab"
                                    data-bs-target="#step-done" type="button" role="tab">
                                <span class="step-number">3</span>
                                <i class="ri-home-heart-line me-1"></i>
                                <span>Santri Sudah Kembali</span>
                                <span class="badge bg-success-subtle text-success ms-2">{{ $returnedHome->count() }}</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content" id="visitStepperContent">
                    {{-- STEP 1: Wali belum datang (approved, belum scan/QR) --}}
                    <div class="tab-pane fade show active" id="step-pending" role="tabpanel">
                        <div class="card-header bg-soft-info py-2">
                            <small class="text-muted">
                                <i class="ri-information-line me-1"></i>
                                Kunjungan yang menunggu wali datang ke pos asrama.
                                <span class="text-primary fw-semibold">Check-in = catat waktu wali tiba (santri keluar ke pos)</span>.
                                Gunakan tombol <strong>Catat Wali Tiba</strong> jika wali tidak dapat memindai QR.
                            </small>
                        </div>
                        <div class="table-container">
                            @if($approvedAwaiting->isEmpty())
                                <div class="text-center text-muted py-4">
                                    <i class="ri-information-line d-block fs-4 mb-1"></i>
                                    Tidak ada wali yang menunggu untuk dijemput santrinya.
                                </div>
                            @else
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width:40px;">No</th>
                                            <th>Nama Wali</th>
                                            <th>Hubungan</th>
                                            <th>Nama Santri</th>
                                            <th>Kamar</th>
                                            <th>Waktu Rencana</th>
                                            <th>Keperluan</th>
                                            <th>Status</th>
                                            <th class="text-center" style="width:220px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $n = 1; @endphp
                                        @foreach($approvedAwaiting as $visit)
                                        <tr>
                                            <td data-label="No" class="text-center"><span class="d-md-none text-muted small me-1">#</span>{{ $n++ }}</td>
                                            <td data-label="Nama Wali">
                                                <div class="fw-semibold">{{ $visit->visitor_name }}</div>
                                                @if($visit->visitor_phone)
                                                    <div class="text-muted small"><i class="ri-phone-line"></i> {{ $visit->visitor_phone }}</div>
                                                @endif
                                            </td>
                                            <td data-label="Hubungan"><span class="text-muted">{{ $visit->visitor_relationship ?? '—' }}</span></td>
                                            <td data-label="Nama Santri">{{ $visit->student?->name ?? '—' }}</td>
                                            <td data-label="Kamar">{{ $visit->room?->name ?? '—' }}</td>
                                            <td data-label="Waktu Rencana">{{ $visit->expected_arrival_datetime?->format('d/m/Y H:i') ?? '—' }}</td>
                                            <td data-label="Keperluan">
                                                <span class="badge bg-info-subtle text-info">{{ $visit->purpose_text }}</span>
                                            </td>
                                            <td data-label="Status">
                                                <span class="badge bg-info-subtle text-info">
                                                    <span class="status-dot bg-info"></span> Menunggu Penjengukan
                                                </span>
                                            </td>
                                            <td data-label="Aksi" class="text-center">
                                                <div class="d-inline-flex gap-1">
                                                    <button type="button" class="btn btn-sm btn-success"
                                                            data-bs-toggle="modal" data-bs-target="#manualActionModal"
                                                            data-action-url="{{ route('user.asrama.visits.check-in', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'visitUuid' => $visit->id]) }}"
                                                            data-visit-name="{{ $visit->visitor_name }}"
                                                            data-student-name="{{ $visit->student?->name ?? '—' }}"
                                                            data-action="check-in"
                                                            data-action-label="Wali Tiba (Check-in)"
                                                            title="Catat wali tiba di pos asrama tanpa QR">
                                                        <i class="ri-login-box-line me-1"></i> Catat Wali Tiba
                                                    </button>
                                                    <a href="{{ route('user.asrama.visits.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'visitUuid' => $visit->id]) }}"
                                                       class="btn btn-sm btn-outline-primary" title="Detail">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>

                    {{-- STEP 2: Wali tiba & sedang menunggu, sementara SANTRI yang keluar asrama --}}
                    <div class="tab-pane fade" id="step-arrived" role="tabpanel">
                        <div class="card-header bg-soft-warning py-2">
                            <small class="text-muted">
                                <i class="ri-information-line me-1"></i>
                                Wali tiba di pos asrama; <strong>Santri sedang keluar</strong> untuk menemui wali.
                                <span class="text-primary fw-semibold">Check-out = catat waktu wali pulang / kunjungan selesai</span> (Santri kembali ke asrama).
                                Gunakan tombol <strong>Santri Kembali</strong> setelah kunjungan selesai.
                            </small>
                        </div>
                        <div class="table-container">
                            @if($arrivedAwaiting->isEmpty())
                                <div class="text-center text-muted py-4">
                                    <i class="ri-information-line d-block fs-4 mb-1"></i>
                                    Tidak ada wali yang sedang menunggu di pos kunjungan.
                                </div>
                            @else
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width:40px;">No</th>
                                            <th>Nama Wali</th>
                                            <th>Hubungan</th>
                                            <th>Nama Santri</th>
                                            <th>Kamar</th>
                                            <th>Durasi</th>
                                            <th>Status</th>
                                            <th class="text-center" style="width:220px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $m = 1; @endphp
                                        @foreach($arrivedAwaiting as $visit)
                                            @php
                                                $checkedInAt = $visit->check_in_at;
                                                $duration = $checkedInAt ? $checkedInAt->diffForHumans(null, true) : '—';
                                            @endphp
                                        <tr>
                                            <td data-label="No" class="text-center"><span class="d-md-none text-muted small me-1">#</span>{{ $m++ }}</td>
                                            <td data-label="Nama Wali">
                                                <div class="fw-semibold">{{ $visit->visitor_name }}</div>
                                                @if($visit->visitor_phone)
                                                    <div class="text-muted small"><i class="ri-phone-line"></i> {{ $visit->visitor_phone }}</div>
                                                @endif
                                            </td>
                                            <td data-label="Hubungan"><span class="text-muted">{{ $visit->visitor_relationship ?? '—' }}</span></td>
                                            <td data-label="Nama Santri">{{ $visit->student?->name ?? '—' }}</td>
                                            <td data-label="Kamar">{{ $visit->room?->name ?? '—' }}</td>
                                            <td data-label="Durasi"><span class="text-muted">{{ $duration }}</span></td>
                                            <td data-label="Status">
                                                <span class="badge bg-warning-subtle text-warning">
                                                    <span class="status-dot bg-warning"></span> Santri Sedang Keluar
                                                </span>
                                            </td>
                                            <td data-label="Aksi" class="text-center">
                                                <div class="d-inline-flex gap-1">
                                                    <button type="button" class="btn btn-sm btn-primary"
                                                            data-bs-toggle="modal" data-bs-target="#manualActionModal"
                                                            data-action-url="{{ route('user.asrama.visits.check-out', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'visitUuid' => $visit->id]) }}"
                                                            data-visit-name="{{ $visit->visitor_name }}"
                                                            data-student-name="{{ $visit->student?->name ?? '—' }}"
                                                            data-action="check-out"
                                                            data-action-label="Santri Kembali (Check-out)"
                                                            title="Catat kunjungan selesai & santri kembali ke asrama">
                                                        <i class="ri-logout-box-line me-1"></i> Santri Kembali
                                                    </button>
                                                    <a href="{{ route('user.asrama.visits.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'visitUuid' => $visit->id]) }}"
                                                       class="btn btn-sm btn-outline-primary" title="Detail">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>

                    {{-- STEP 3: Santri sudah kembali (checked_out) --}}
                    <div class="tab-pane fade" id="step-done" role="tabpanel">
                        <div class="card-header bg-soft-success py-2">
                            <small class="text-muted">
                                <i class="ri-information-line me-1"></i>
                                Riwayat kunjungan yang telah selesai — Wali pulang & Santri sudah kembali ke asrama. {{ $recentScans->count() > 0 ? "Menampilkan juga " . $recentScans->count() . " scan terbaru." : '' }}
                            </small>
                        </div>
                        <div class="table-container">
                            @if($returnedHome->isEmpty() && $recentScans->isEmpty())
                                <div class="text-center text-muted py-4">
                                    <i class="ri-information-line d-block fs-4 mb-1"></i>
                                    Belum ada kunjungan yang selesai.
                                </div>
                            @else
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width:40px;">No</th>
                                            <th>Nama Wali</th>
                                            <th>Hubungan</th>
                                            <th>Nama Santri</th>
                                            <th>Kamar</th>
                                            <th>Santri Kembali (Check-out)</th>
                                            <th>Durasi</th>
                                            <th>Status</th>
                                            <th class="text-center" style="width:80px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $r = 1; @endphp
                                        @foreach($returnedHome as $visit)
                                            @php
                                                $dur = ($visit->check_in_at && $visit->check_out_at)
                                                    ? $visit->check_in_at->diffForHumans($visit->check_out_at, true)
                                                    : '—';
                                            @endphp
                                        <tr>
                                            <td data-label="No" class="text-center"><span class="d-md-none text-muted small me-1">#</span>{{ $r++ }}</td>
                                            <td data-label="Nama Wali">
                                                <div class="fw-semibold">{{ $visit->visitor_name }}</div>
                                            </td>
                                            <td data-label="Hubungan"><span class="text-muted">{{ $visit->visitor_relationship ?? '—' }}</span></td>
                                            <td data-label="Nama Santri">{{ $visit->student?->name ?? '—' }}</td>
                                            <td data-label="Kamar">{{ $visit->room?->name ?? '—' }}</td>
                                            <td data-label="Santri Kembali (Check-out)">{{ $visit->check_out_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                            <td data-label="Durasi"><span class="text-muted">{{ $dur }}</span></td>
                                            <td data-label="Status">
                                                <span class="badge bg-success-subtle text-success">
                                                    <span class="status-dot bg-success"></span> Santri Sudah Kembali
                                                </span>
                                            </td>
                                            <td data-label="Aksi" class="text-center">
                                                <a href="{{ route('user.asrama.visits.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'visitUuid' => $visit->id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Detail">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                        @foreach($recentScans as $visit)
                                            @if(!$returnedHome->contains('id', $visit->id))
                                                <tr class="table-light">
                                                    <td data-label="No" class="text-center"><span class="d-md-none text-muted small me-1">#</span>{{ $r++ }}</td>
                                                    <td data-label="Nama Wali">
                                                        <div class="fw-semibold">{{ $visit->visitor_name }}</div>
                                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size:10px;">Scan</span>
                                                    </td>
                                                    <td data-label="Hubungan"><span class="text-muted">{{ $visit->visitor_relationship ?? '—' }}</span></td>
                                                    <td data-label="Nama Santri">{{ $visit->student?->name ?? '—' }}</td>
                                                    <td data-label="Kamar">{{ $visit->room?->name ?? '—' }}</td>
                                                    <td data-label="Santri Kembali (Check-out)" class="text-muted">—</td>
                                                    <td data-label="Durasi" class="text-muted">—</td>
                                                    <td data-label="Status">
                                                        <span class="badge bg-secondary-subtle text-secondary">Riwayat Scan</span>
                                                    </td>
                                                    <td data-label="Aksi" class="text-center">
                                                        <a href="{{ route('user.asrama.visits.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'visitUuid' => $visit->id]) }}"
                                                           class="btn btn-sm btn-outline-primary" title="Detail">
                                                            <i class="ri-eye-line"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal konfirmasi untuk aksi manual (check-in / check-out tanpa QR) --}}
            <div class="modal fade" id="manualActionModal" tabindex="-1" aria-labelledby="manualActionModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form id="manualActionForm" method="POST" action="">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title" id="manualActionModalLabel">
                                    <i class="ri-shield-check-line me-2"></i><span id="manual-action-label">Aksi Manual</span>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-light border mb-3" style="font-size: 13px;">
                                    <table class="table table-sm table-borderless mb-0" style="font-size: 13px;">
                                        <tr>
                                            <td class="text-muted" style="width: 130px;">Nama Wali</td>
                                            <td class="fw-semibold" id="modal-visit-name">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Santri yang dijenguk</td>
                                            <td class="fw-semibold" id="modal-student-name">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Aksi</td>
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary" id="modal-action-name">—</span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-semibold">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
                                    <textarea name="note" id="manual-action-note" rows="2"
                                              class="form-control"
                                              placeholder="Mis. 'Penjenguk tidak bisa scan QR', 'Verifikasi KTP manual', dll."></textarea>
                                    <small class="text-muted">Catatan akan dicatat di timeline kunjungan untuk jejak audit.</small>
                                </div>

                                {{-- Hidden QR Reader untuk modal --}}
                                <div id="qr-reader-modal" style="display:none;"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="button" id="scanQRInModalBtn" class="btn btn-outline-primary" title="Scan QR kunjungan dari kamera">
                                    <i class="ri-qr-scan-2-line me-1"></i> Scan QR
                                </button>
                                <button type="submit" class="btn btn-success" id="manual-action-submit">
                                    <i class="ri-check-line me-1"></i> <span>Konfirmasi</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function() {
    'use strict';

    let html5QrCode = null;
    const qrReader = document.getElementById('qr-reader');
    const statusEl = document.getElementById('qr-reader--status');
    const scanForm = document.getElementById('manual-form');
    const scanUrlInput = document.getElementById('manual_url');
    const resultBox = document.getElementById('scan-result');
    const resultIcon = document.getElementById('scan-result-icon');
    const resultText = document.getElementById('scan-result-text');

    function showResult(success, message) {
        resultBox.className = 'alert alert-dismissible fade show ' + (success ? 'alert-success' : 'alert-danger');
        resultIcon.className = success ? 'ri-check-line me-2' : 'ri-error-warning-line me-2';
        resultText.textContent = message;
        resultBox.style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /**
     * Submit signed URL via fetch, then redirect to scan page with flash message.
     */
    async function processScan(scanUrl) {
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content
                || scanForm.querySelector('input[name="_token"]').value;

            const response = await fetch(scanUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'scan_url=' + encodeURIComponent(scanUrl),
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok && data.success) {
                showResult(true, data.message || 'Berhasil.');
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showResult(false, data.message || 'Gagal memproses QR.');
            }
        } catch (err) {
            console.error('Scan error:', err);
            showResult(false, 'Tidak dapat memproses URL: ' + err.message);
        }
    }

    function onScanSuccess(decodedText) {
        const url = decodedText.trim();
        if (html5QrCode) {
            html5QrCode.stop().catch(() => {});
        }
        scanUrlInput.value = url;
        processScan(url);
    }

    function startScanner() {
        if (!qrReader) return;
        html5QrCode = new Html5Qrcode("qr-reader");
        const config = { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 };

        html5QrCode.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            () => {}
        ).then(() => {
            if (statusEl) {
                statusEl.textContent = '📷 Kamera aktif. Arahkan ke QR Code kunjungan.';
                statusEl.className = 'text-center text-muted small';
            }
        }).catch(() => {
            if (statusEl) {
                statusEl.textContent = '⚠️ Kamera tidak tersedia. Gunakan tab manual.';
                statusEl.className = 'text-center text-warning small';
            }
        });
    }

    startScanner();

    // Periode chip: submit form dengan field period di-update
    window.setPeriod = function (value) {
        const input = document.getElementById('periodInput');
        const form = document.getElementById('scanFilterForm');
        if (input && form) {
            input.value = value;
            form.submit();
        }
    };

    scanForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        const url = scanUrlInput.value.trim();
        if (url) processScan(url);
    });

    window.addEventListener('beforeunload', () => {
        if (html5QrCode) html5QrCode.stop().catch(() => {});
    });

    // Modal konfirmasi untuk aksi manual (check-in / check-out tanpa QR)
    const manualActionModal = document.getElementById('manualActionModal');
    if (manualActionModal) {
        manualActionModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            const form = document.getElementById('manualActionForm');
            form.action = btn.dataset.actionUrl || '';
            document.getElementById('manual-action-label').textContent = btn.dataset.actionLabel || 'Aksi Manual';
            document.getElementById('modal-visit-name').textContent = btn.dataset.visitName || '—';
            document.getElementById('modal-student-name').textContent = btn.dataset.studentName || '—';
            document.getElementById('modal-action-name').textContent = btn.dataset.actionLabel || '—';

            const isCheckIn = btn.dataset.action === 'check-in';
            const submitBtn = document.getElementById('manual-action-submit');
            submitBtn.classList.toggle('btn-success', isCheckIn);
            submitBtn.classList.toggle('btn-primary', !isCheckIn);
            submitBtn.querySelector('i').className = isCheckIn ? 'ri-login-box-line me-1' : 'ri-logout-box-line me-1';

            document.getElementById('manual-action-note').value = '';
        });
    }

        // Scan QR dari modal manual action
    const scanQrModalBtn = document.getElementById('scanQRInModalBtn');
    if (scanQrModalBtn) {
        let modalScanner = null;
        const scanRouteUrl = "{{ route('user.asrama.visits.scan.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}";
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        scanQrModalBtn.addEventListener('click', function() {
            if (modalScanner) {
                modalScanner.stop().catch(() => {});
                modalScanner = null;
            }
            const qrReader = document.getElementById('qr-reader-modal');
            if (!qrReader) return;
            modalScanner = new Html5Qrcode("qr-reader-modal");
            const config = { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 };
            modalScanner.start(
                { facingMode: "environment" },
                config,
                function(decodedText) {
                    const url = decodedText.trim();
                    if (modalScanner) {
                        modalScanner.stop().catch(() => {});
                        modalScanner = null;
                    }
                    fetch(scanRouteUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'scan_url=' + encodeURIComponent(url),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Scan berhasil! Kunjungan diproses.');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            alert('Gagal: ' + (data.message || 'Terjadi error'));
                        }
                    })
                    .catch(err => {
                        alert('Error: ' + err.message);
                    });
                },
                () => {}
            ).catch(err => {
                alert('Tidak bisa memulai kamera: ' + err.message);
            });
        });
    }

    // Auto-switch tab berdasarkan anchor #tab di URL (untuk test langsung)
    if (window.location.hash) {
        const hashTab = document.querySelector(`button[data-bs-target="${window.location.hash}"]`);
        if (hashTab) {
            const tab = new bootstrap.Tab(hashTab);
            tab.show();
        }
    }
})();
</script>
@endsection