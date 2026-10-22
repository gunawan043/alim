@extends('layouts.master')
@section('title') Perizinan Asrama @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .table-container {
            position: relative;
            width: 100%;
            overflow-x: auto;
        }
        .table-freeze {
            table-layout: auto;
            min-width: max-content;
            margin-bottom: 0;
            width: 100%;
        }
        .table-freeze th,
        .table-freeze td {
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            vertical-align: middle;
            padding: 12px 16px;
            word-break: break-word;
        }
        .table-freeze th:first-child,
        .table-freeze td:first-child {
            position: sticky;
            left: 0;
            z-index: 100;
            min-width: 150px;
            max-width: 200px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            white-space: normal;
            word-wrap: break-word;
        }
        .table-freeze thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        .card-animate { transition: all 0.3s ease; }
        .card-animate:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            font-size: 13px;
            transition: all 0.2s;
            margin: 4px;
            cursor: pointer;
        }
        .filter-badge:hover { background: #405189; border-color: #94a3b8; color: #fff; }
        .filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }
        .filter-badge .remove-filter { cursor: pointer; margin-left: 4px; opacity: 0.7; }
        .filter-badge .remove-filter:hover { opacity: 1; }
        .filter-group { background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
        .filter-group-title { font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .filter-group-title i { color: #0a5f9e; font-size: 18px; }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Perizinan</a> @endslot
        @slot('title') Perizinan @endslot
    @endcomponent

    @if(session('success'))
        <x-modal-result
            id="permitResultModal"
            title="Berhasil!"
            :message="session('success')"
            primary-action-label="Selesai"
        />
    @elseif(session('error'))
        <x-modal-result
            id="permitResultModal"
            icon="https://cdn.lordicon.com/tdrtiskw.json"
            primary-color="#ffffff"
            secondary-color="#dc3545"
            title="Gagal!"
            :message="session('error')"
            primary-action-label="Tutup"
        />
    @endif
    @if(session('token_error'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="ri-alert-line me-2"></i>{{ session('token_error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('token_found'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="ri-qr-code-line me-2"></i>Izin ditemukan!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @php
        $totalApproved = ($stats['approved'] ?? 0) + ($stats['picked_up'] ?? 0);
        $totalActive = ($stats['pending'] ?? 0) + ($stats['approved'] ?? 0) + ($stats['picked_up'] ?? 0) + ($stats['overdue'] ?? 0);
        $totalAll = ($stats['pending'] ?? 0) + ($stats['approved'] ?? 0) + ($stats['picked_up'] ?? 0) + ($stats['returned'] ?? 0) + ($stats['overdue'] ?? 0) + ($stats['rejected'] ?? 0);
    @endphp

    {{-- STATISTICS CARDS (gaya GTK) --}}
    <div class="row">
        {{-- 1. Total Izin --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2">
                                <i class="bx bx-receipt text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Izin</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalAll) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-2" style="font-size:11px;">
                        <i class="ri-information-line me-1"></i>Tahun Ajaran {{ $activeYear->name ?? '—' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- 2. Menunggu Persetujuan --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2">
                                <i class="bx bx-time text-warning"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Menunggu</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['pending'] ?? 0) }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">izin</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0" style="font-size:11px;">
                        <i class="ri-information-line me-1"></i>Perlu tindakan admin
                    </p>
                </div>
            </div>
        </div>

        {{-- 3. Izin Aktif (Approved + Picked-up + Overdue) --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="bx bx-check-circle text-success"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Izin Aktif</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalActive) }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">total</small></h3>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-info-subtle text-info" style="font-size:10px;">
                            <i class="ri-checkbox-circle-fill me-1"></i>{{ number_format($stats['approved'] ?? 0) }} Disetujui
                        </span>
                        <span class="badge bg-primary-subtle text-primary" style="font-size:10px;">
                            <i class="ri-arrow-right-circle-fill me-1"></i>{{ number_format($stats['picked_up'] ?? 0) }} Sedang di Luar
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Terlambat --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2">
                                <i class="bx bx-error-circle text-danger"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Terlambat</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['overdue'] ?? 0) }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">izin</small></h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0" style="font-size:11px;">
                        <i class="ri-alert-line me-1"></i>Perlu tindak lanjut
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Kuota Izin Section (lebih sederhana) --}}
    @if($quotaInfo)
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between bg-light">
            <h6 class="card-title mb-0">
                <i class="ri-bar-chart-line me-1 text-primary"></i>Kuota Izin
            </h6>
            <form method="GET" id="quotaForm" class="m-0">
                <select id="quotaStudentFilter" name="quota_student" class="form-select form-select-sm" style="width:200px;" onchange="this.form.submit()">
                    <option value="">Semua Santri</option>
                    @foreach($residents as $resident)
                        <option value="{{ $resident->student_id }}" {{ request('quota_student') == $resident->student_id ? 'selected' : '' }}>
                            {{ $resident->student?->name ?? 'Unknown' }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="card-body py-3">
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <span class="filter-badge">
                    <i class="ri-shield-check-line text-primary"></i>
                    Strategi: <strong>{{ ucfirst($quotaInfo['strategy'] ?? 'N/A') }}</strong>
                </span>
                @if($quotaInfo['is_unrestricted'])
                    <span class="filter-badge active">
                        <i class="ri-infinity-line"></i> Tidak Terbatas
                    </span>
                @else
                    <span class="filter-badge active">
                        <i class="ri-numbers-line"></i> {{ $quotaInfo['quota'] ?? 0 }} per {{ ucfirst($quotaInfo['period'] ?? 'bulan') }}
                    </span>
                @endif
                <span class="filter-badge">
                    <i class="ri-time-line text-info"></i> Izin bulan ini: <strong>{{ number_format($stats['approved'] + $stats['overdue'] ?? 0) }}</strong>
                </span>
                @if(!empty($visitQuotaInfo))
                    <span class="filter-badge">
                        <i class="ri-group-line text-success"></i> Kunjungan {{ $visitQuotaInfo['period_label_id'] }}:
                        <strong>{{ $visitQuotaInfo['used'] }}/{{ $visitQuotaInfo['quota'] ?? '∞' }}</strong>
                        @if($visitQuotaInfo['remaining'] !== null) · sisa {{ $visitQuotaInfo['remaining'] }} @endif
                    </span>
                @endif
                @if(request('quota_student'))
                    @php
                        $studentId = request('quota_student');
                        $rangeStart = now()->startOfMonth();
                        $rangeEnd = now()->endOfMonth();
                        $usageCount = \DB::table('dormitory_permits')
                            ->where('student_id', $studentId)
                            ->whereIn('status', ['pending', 'approved', 'picked_up', 'returned'])
                            ->where('academic_year_id', optional($activeYear)->id)
                            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                            ->count();
                        $quotaLimit = $quotaInfo['is_unrestricted'] ? 999999 : ($quotaInfo['quota'] ?? 4);
                        $remaining = max(0, $quotaLimit - $usageCount);
                        $usedPercent = $quotaLimit > 99999 ? 100 : round(($usageCount / $quotaLimit) * 100, 1);
                        $selectedStudentName = optional(optional(\App\Models\Student::find($studentId))->name) ?: '—';
                    @endphp
                    <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                       class="filter-badge active text-decoration-none">
                        <i class="ri-user-line"></i> {{ $selectedStudentName }}
                        <span class="remove-filter">×</span>
                    </a>
                @endif
            </div>

            @if(request('quota_student'))
                <div class="mt-3">
                    <div class="d-flex align-items-center gap-3">
                        <div style="flex:1;">
                            <div class="progress" style="height:22px; border-radius:5px;">
                                <div class="progress-bar {{ $usedPercent >= 100 ? 'bg-danger' : ($usedPercent >= 75 ? 'bg-warning' : 'bg-success') }}"
                                     role="progressbar"
                                     style="width: {{ min(100, $usedPercent) }}%;"
                                     aria-valuenow="{{ $usageCount }}" aria-valuemin="0" aria-valuemax="{{ $quotaLimit }}">
                                    {{ $usageCount }} / {{ $quotaLimit }}
                                </div>
                            </div>
                        </div>
                        <div>
                            <span class="badge {{ $remaining > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                {{ $remaining > 0 ? "Sisa {$remaining}" : 'Kuota Habis' }}
                            </span>
                        </div>
                    </div>
                    <small class="text-muted mt-1 d-block" style="font-size:11px;">
                        <i class="ri-calendar-line me-1"></i>
                        Periode: {{ $rangeStart->format('d/m/Y') }} – {{ $rangeEnd->format('d/m/Y') }}
                    </small>
                </div>
            @endif
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed bg-light">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary-subtle rounded fs-3">
                                        <i class="bx bx-receipt text-primary"></i>
                                    </span>
                                </div>
                                <div>
                                    <h5 class="card-title mb-0">Daftar Perizinan Santri</h5>
                                    <p class="text-muted mb-0" style="font-size:11px;">
                                        <i class="ri-home-4-line me-1"></i>{{ $dormitory->name }}
                                        <span class="mx-1">·</span>
                                        <i class="ri-calendar-line me-1"></i>{{ $activeYear->name ?? '—' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-auto d-flex gap-2 flex-wrap">
                            <a href="{{ route('user.asrama.permits.scan', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-outline-info btn-sm">
                                <i class="ri-qr-scan-2-line me-1"></i> Scan QR
                            </a>
                            <a href="{{ route('user.asrama.leave-policies.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-outline-warning btn-sm" title="Pengaturan kuota dan jenis izin">
                                <i class="ri-settings-3-line me-1"></i> Pengaturan
                            </a>
                            <a href="{{ route('user.asrama.permits.bulk-card', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-outline-success btn-sm" title="Cetak Kartu Izin Massal">
                                <i class="ri-printer-line me-1"></i> Cetak Kartu
                            </a>
                            <a href="{{ route('user.asrama.permits.create', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-primary btn-sm">
                                <i class="ri-add-line align-bottom me-1"></i> Ajukan Izin
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Toolbar pencarian + filter modal (pola GTK) --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width:520px;">
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Cari nama santri, NISN, tujuan..."
                                   value="{{ request('search') }}">
                            @foreach(['status', 'permit_type', 'start_date', 'end_date'] as $filterKey)
                                @if(request($filterKey))
                                    <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                                @endif
                            @endforeach
                            <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                                <i class="ri-search-line"></i> Cari
                            </button>
                        </form>

                        <div class="toolbar-actions">
                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#permitFilterModal">
                                <i class="ri-filter-3-line me-1"></i> Filter Lanjutan
                                @if(request()->hasAny(['status', 'permit_type', 'start_date', 'end_date']))
                                    <span class="badge bg-light text-dark ms-1">Aktif</span>
                                @endif
                            </button>
                            <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                               class="btn btn-light btn-sm" title="Reset filter">
                                <i class="ri-refresh-line"></i>
                            </a>
                        </div>
                    </div>

                    @if(request()->hasAny(['search','status','permit_type','start_date','end_date']))
                        <div class="mb-3">
                            <span class="text-muted small">Filter aktif:</span>
                            @if(request('search'))
                                <span class="filter-badge active"><i class="ri-search-line"></i> "{{ request('search') }}"</span>
                            @endif
                            @if(request('status'))
                                <span class="filter-badge active"><i class="ri-flag-line"></i> {{ ucfirst(str_replace('_', ' ', request('status'))) }}</span>
                            @endif
                            @if(request('permit_type'))
                                <span class="filter-badge active"><i class="ri-bookmark-line"></i> {{ ucfirst(str_replace('_', ' ', request('permit_type'))) }}</span>
                            @endif
                            @if(request('start_date'))
                                <span class="filter-badge active"><i class="ri-calendar-line"></i> ≥ {{ \Carbon\Carbon::parse(request('start_date'))->format('d/m/Y') }}</span>
                            @endif
                            @if(request('end_date'))
                                <span class="filter-badge active"><i class="ri-calendar-line"></i> ≤ {{ \Carbon\Carbon::parse(request('end_date'))->format('d/m/Y') }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="d-flex align-items-center gap-2 mb-2">
                        <small class="text-muted">
                            <i class="ri-file-list-line me-1"></i> Menampilkan
                            <strong>{{ $permits->count() }}</strong> dari
                            <strong>{{ $permits->total() }}</strong> izin
                        </small>
                    </div>

                    <div class="table-container">
                        <table class="table table-hover align-middle table-freeze">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>Santri</th>
                                    <th>Jenis</th>
                                    <th>Pulang</th>
                                    <th>Kembali</th>
                                    <th>Penjemput</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width:140px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="list">
                                @forelse($permits as $i => $permit)
                                    <tr class="{{ $permit->isOverdue ? 'table-danger' : '' }}">
                                        <td class="text-center">
                                            <span class="text-muted" style="font-size:12px;">{{ $permits->firstItem() + $i }}</span>
                                        </td>

                                        {{-- Santri --}}
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="flex-shrink-0">
                                                    <div class="avatar-xs">
                                                        <span class="avatar-title rounded-circle bg-primary-subtle text-primary fs-6">
                                                            {{ strtoupper(substr($permit->student?->name ?? '?', 0, 1)) }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0 fs-14">{{ $permit->student?->name ?? '—' }}</h6>
                                                    <small class="text-muted">
                                                        <i class="ri-home-4-line me-1"></i>{{ $permit->room?->name ?? 'Tanpa Kamar' }}
                                                        @if($permit->student?->nisn)
                                                            · {{ $permit->student->nisn }}
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Jenis --}}
                                        <td>
                                            <span class="status-pill bg-info-subtle text-info">
                                                <i class="ri-bookmark-line"></i>
                                                {{ $permit->permit_type_text }}
                                            </span>
                                        </td>

                                        {{-- Pulang --}}
                                        <td>
                                            <div class="fw-medium" style="font-size:13px;">
                                                {{ $permit->departure_datetime ? $permit->departure_datetime->format('d/m/Y') : '—' }}
                                            </div>
                                            @if($permit->departure_datetime)
                                                <small class="text-muted">{{ $permit->departure_datetime->format('H:i') }} WIB</small>
                                            @endif
                                        </td>

                                        {{-- Kembali --}}
                                        <td>
                                            <div class="fw-medium" style="font-size:13px;">
                                                {{ $permit->expected_return_datetime ? $permit->expected_return_datetime->format('d/m/Y') : '—' }}
                                            </div>
                                            @if($permit->expected_return_datetime)
                                                <small class="text-muted">{{ $permit->expected_return_datetime->format('H:i') }} WIB</small>
                                            @endif
                                        </td>

                                        {{-- Penjemput --}}
                                        <td>
                                            @php
                                                $pickerName = $permit->pickup_details['picker_name'] ?? $permit->companion_name;
                                                $pickerRelation = $permit->pickup_details['picker_relation'] ?? $permit->companion_relation;
                                                $isScanned = !is_null($permit->pickup_scanned_at);
                                            @endphp

                                            @if($isScanned && $pickerName)
                                                <div style="font-size:13px;" class="fw-semibold">
                                                    <i class="ri-user-star-line text-success me-1"></i>{{ $pickerName }}
                                                </div>
                                                @if($pickerRelation)
                                                    <small class="text-muted">{{ ucfirst($pickerRelation) }}</small>
                                                @endif
                                                <small class="d-block text-muted" style="font-size:10px;">
                                                    <i class="ri-checkbox-circle-line"></i> Saat penjemputan
                                                </small>
                                            @else
                                                <div style="font-size:13px;">{{ $permit->companion_name ?: '—' }}</div>
                                                @if($permit->companion_relation)
                                                    <small class="text-muted">{{ $permit->companion_relation }}</small>
                                                @endif
                                                @if($permit->companion_name)
                                                    <small class="d-block text-muted" style="font-size:10px;">
                                                        <i class="ri-file-text-line"></i> Rencana
                                                    </small>
                                                @endif
                                                @if($permit->pickup_authorization_status === 'pending')
                                                    <span class="badge bg-warning-subtle text-warning mt-1" style="font-size:9px;">
                                                        <i class="ri-shield-user-line me-1"></i>Perlu verifikasi
                                                    </span>
                                                @elseif($permit->pickup_authorization_status === 'rejected')
                                                    <span class="badge bg-danger-subtle text-danger mt-1" style="font-size:9px;">
                                                        <i class="ri-close-line me-1"></i>Penjemput ditolak
                                                    </span>
                                                @endif
                                            @endif
                                        </td>

                                        {{-- Status --}}
                                        <td>
                                            @if($permit->status === 'pending')
                                                <span class="status-pill bg-warning-subtle text-warning">
                                                    <i class="ri-time-line"></i> Menunggu
                                                </span>
                                            @elseif($permit->status === 'approved')
                                                <span class="status-pill bg-info-subtle text-info">
                                                    <i class="ri-logout-box-r-line"></i> Belum Keluar
                                                </span>
                                            @elseif($permit->status === 'picked_up')
                                                <span class="status-pill bg-warning-subtle text-warning">
                                                    <i class="ri-walk-line"></i> Sedang di Luar
                                                </span>
                                            @elseif($permit->status === 'returned')
                                                <span class="status-pill bg-success-subtle text-success">
                                                    <i class="ri-home-heart-line"></i> Sudah Kembali
                                                </span>
                                            @elseif($permit->status === 'rejected')
                                                <span class="status-pill bg-dark-subtle text-dark">
                                                    <i class="ri-close-line"></i> Ditolak
                                                </span>
                                            @elseif($permit->isOverdue)
                                                <span class="status-pill bg-danger-subtle text-danger">
                                                    <i class="ri-alarm-warning-line"></i> Terlambat
                                                </span>
                                            @else
                                                <span class="status-pill bg-light text-dark">
                                                    {{ $permit->status_text }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Aksi --}}
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('user.asrama.permits.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}"
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Lihat Detail">
                                                    <i class="ri-eye-line"></i>
                                                </a>

                                                <a href="{{ route('user.asrama.permits.card', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}"
                                                   target="_blank"
                                                   class="btn btn-sm btn-outline-secondary"
                                                   title="Cetak Kartu Izin">
                                                    <i class="ri-printer-line"></i>
                                                </a>

                                                @if($permit->status === 'pending')
                                                    <button type="button" class="btn btn-sm btn-outline-success"
                                                            data-bs-toggle="modal" data-bs-target="#approveModal-{{ $permit->id }}"
                                                            title="Setujui">
                                                        <i class="ri-check-line"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                            data-bs-toggle="modal" data-bs-target="#rejectModal-{{ $permit->id }}"
                                                            title="Tolak">
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                @elseif(in_array($permit->status, ['approved', 'picked_up', 'overdue']) && is_null($permit->actual_return_datetime))
                                                    <button type="button" class="btn btn-sm btn-success"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#returnModal"
                                                            data-permit-id="{{ $permit->id }}"
                                                            data-permit-name="{{ $permit->student?->name }}"
                                                            data-permit-expected="{{ $permit->expected_return_datetime?->format('d/m/Y H:i') ?? '—' }}"
                                                            data-record-url="{{ route('user.asrama.permits.return', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => '__ID__']) }}"
                                                            title="Ubah Status / Catat Kedatangan">
                                                        <i class="ri-edit-2-line"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Modal: Setujui Izin --}}
                                    @if($permit->status === 'pending')
                                        <x-modal-confirm
                                            id="approveModal-{{ $permit->id }}"
                                            icon="https://cdn.lordicon.com/tdrtiskw.json"
                                            title="Setujui Izin Santri?"
                                            submit-label="Ya, Setujui"
                                            submit-icon="ri-check-line"
                                            submit-class="btn-success approveSubmitBtn"
                                            form-id="approveForm-{{ $permit->id }}">
                                            <form method="POST" id="approveForm-{{ $permit->id }}" class="approveForm"
                                                  data-quota-check-url="{{ route('user.asrama.permits.quota.check', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}"
                                                  action="{{ route('user.asrama.permits.approve', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}">
                                                @csrf
                                                <input type="hidden" name="asrama_uuid" value="{{ $dormitory->id }}">
                                                <input type="hidden" name="student_id" value="{{ $permit->student_id }}">
                                                <input type="hidden" name="permit_type_original" value="{{ $permit->permit_type }}">
                                                <input type="hidden" name="departure_datetime" value="{{ optional($permit->departure_datetime)->format('Y-m-d\TH:i') }}">

                                                <div class="mb-2">
                                                    <div class="fw-semibold">Santri: {{ $permit->student?->name }}</div>
                                                    <small class="text-muted">
                                                        Tujuan: {{ $permit->destination ?? '—' }} <br>
                                                        {{ $permit->departure_datetime?->format('d/m/Y H:i') }} →
                                                        {{ $permit->expected_return_datetime?->format('d/m/Y H:i') }}
                                                    </small>
                                                </div>

                                                @if($permit->isOverdue)
                                                    <div class="alert alert-warning py-2 small mb-2">
                                                        <i class="ri-alarm-warning-line"></i>
                                                        Izin ini sudah lewat dari waktu kepulangan yang ditentukan. Kuota kemungkinan sudah penuh atau ada keterlambatan.
                                                    </div>
                                                @endif
                                            </form>
                                        </x-modal-confirm>

                                        {{-- Modal: Tolak Izin --}}
                                        <x-modal-confirm
                                            id="rejectModal-{{ $permit->id }}"
                                            icon="https://cdn.lordicon.com/tdrtiskw.json"
                                            secondary-color="#dc3545"
                                            title="Tolak Izin Santri?"
                                            submit-label="Ya, Tolak"
                                            submit-icon="ri-close-line"
                                            submit-class="btn-danger"
                                            form-id="rejectForm-{{ $permit->id }}"
                                        >
                                            <form method="POST" id="rejectForm-{{ $permit->id }}"
                                                  action="{{ route('user.asrama.permits.reject', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => $permit->id]) }}">
                                                @csrf
                                                <div class="mb-2">
                                                    <div class="fw-semibold">Santri: {{ $permit->student?->name }}</div>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label" for="rejection_reason-{{ $permit->id }}">
                                                        Alasan Penolakan <span class="text-danger">*</span>
                                                    </label>
                                                    <textarea name="rejection_reason"
                                                              id="rejection_reason-{{ $permit->id }}"
                                                              class="form-control"
                                                              rows="3"
                                                              placeholder="Jelaskan alasan penolakan..."
                                                              required></textarea>
                                                </div>
                                            </form>
                                        </x-modal-confirm>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5"></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <x-pagination :paginator="$permits" />
                </div>
            </div>
        </div>
    </div>

    {{-- Modal filter lanjutan --}}
    <div class="modal fade" id="permitFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-filter-3-line me-2 text-primary"></i>Filter Perizinan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-search-line"></i>Pencarian</div>
                                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama santri, NISN, tujuan...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-flag-line"></i>Status</div>
                                    <select name="status" class="form-select">
                                        <option value="">Semua status</option>
                                        @foreach([
                                            'pending' => 'Menunggu Persetujuan',
                                            'approved' => 'Belum Keluar',
                                            'picked_up' => 'Sedang di Luar Asrama',
                                            'returned' => 'Sudah Kembali',
                                            'overdue' => 'Melewati Jadwal',
                                            'rejected' => 'Ditolak',
                                        ] as $value => $label)
                                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-bookmark-line"></i>Jenis Izin</div>
                                    <select name="permit_type" class="form-select">
                                        <option value="">Semua jenis</option>
                                        @foreach(($permitTypes ?? collect()) as $permitType)
                                            <option value="{{ $permitType->code }}" {{ request('permit_type') === $permitType->code ? 'selected' : '' }}>{{ $permitType->label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-calendar-line"></i>Dari</div>
                                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-calendar-line"></i>Sampai</div>
                                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" class="btn btn-light">
                            <i class="ri-refresh-line me-1"></i> Reset
                        </a>
                        <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line me-1"></i> Terapkan Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Catat Kedatangan --}}
    <x-modal-confirm
        id="returnModal"
        icon="https://cdn.lordicon.com/lupuorrc.json"
        secondary-color="#0dcaf0"
        title="Catat Kedatangan Santri?"
        submit-label="Simpan"
        submit-icon="ri-check-line"
        submit-class="btn-info"
        form-id="returnModalForm"
    >
        <form method="POST" id="returnModalForm">
            @csrf
            <div class="fw-semibold mb-2" id="returnModalStudent"></div>
            <small class="text-muted d-block mb-2" id="returnModalExpected"></small>
            <label class="form-label fw-semibold">Waktu Kedatangan Santri <span class="text-danger">*</span></label>
            <input type="datetime-local" name="actual_return_datetime" class="form-control"
                   value="{{ now()->format('Y-m-d\TH:i') }}" required>
        </form>
    </x-modal-confirm>

    {{-- Modal Global: Peringatan Kuota / Overdue --}}
    <div class="modal fade" id="quotaWarningModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning-subtle">
                    <h5 class="modal-title">
                        <i class="ri-alarm-warning-line text-warning me-1"></i> Peringatan Kuota / Izin Terlambat
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="quotaWarningContent">
                    {{-- konten dinamis via JS --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Mengerti</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
<script>
// Auto-show result modal (success/error) on page load if present
(function () {
    const resultModalEl = document.getElementById('permitResultModal');
    if (resultModalEl) {
        const resultModal = new bootstrap.Modal(resultModalEl, { backdrop: 'static', keyboard: true });
        resultModal.show();
        resultModalEl.addEventListener('hidden.bs.modal', function () {
            resultModalEl.remove();
        });
    }
})();

// Return modal: dynamic form action
document.getElementById('returnModal')?.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    if (!button || !button.dataset.permitId) return;

    document.getElementById('returnModalStudent').textContent = button.dataset.permitName || '';
    document.getElementById('returnModalExpected').textContent = 'Estimasi kedatangan: ' + (button.dataset.permitExpected || '—');
    const urlTpl = button.dataset.recordUrl;
    document.getElementById('returnModalForm').action = urlTpl ? urlTpl.replace('__ID__', button.dataset.permitId) : '';
});

// Token search auto-submit
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('token') && !urlParams.get('token')) {
    document.querySelector('#tokenSearchForm input[name="token"]')?.focus();
}

// AJAX scan handler — avoid page reload, return result in modal
document.getElementById('tokenSearchForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const form = this;
    const tokenInput = form.querySelector('input[name="token"]');
    const result = document.getElementById('tokenResult');
    if (!tokenInput || !tokenInput.value.trim()) return;
    if (result) result.innerHTML = '';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: new FormData(form),
    })
    .then(async (response) => {
        const data = await response.json().catch(() => ({}));
        const success = response.ok && data.success;
        const message = data.message || (success ? 'Scan berhasil.' : 'Scan gagal.');
        if (result) {
            result.innerHTML = `<div class="alert alert-${success ? 'success' : 'danger'}">${message}</div>`;
        }
        if (success) {
            setTimeout(() => window.location.reload(), 1200);
        }
    })
    .catch(() => {
        if (result) {
            result.innerHTML = '<div class="alert alert-danger">Terjadi kesalahan saat scan.</div>';
        }
    });
});
</script>

<!-- Handler Approve Forms (custom modals di index) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('quotaWarningModal');
    if (!modal) return;

    const warningContent = document.getElementById('quotaWarningContent');

    document.querySelectorAll('.approveForm').forEach(function (form) {
        const approveBtn = document.querySelector(`button.approveSubmitBtn[form="${form.id}"]`);
        const studentId = form.querySelector('[name="student_id"]').value;
        const originalPermitType = form.querySelector('[name="permit_type_original"]').value;
        const departureDatetime = form.querySelector('[name="departure_datetime"]').value;
        const baseUrl = form.getAttribute('data-quota-check-url');

        let isSubmitting = false;

        form.addEventListener('submit', async function (e) {
            if (isSubmitting) return;
            e.preventDefault();
            isSubmitting = true;
            const originalHTML = approveBtn.innerHTML;
            approveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
            approveBtn.disabled = true;

            try {
                {
                    const response = await fetch(baseUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            student_id: studentId,
                            permit_type: originalPermitType,
                            departure_datetime: departureDatetime
                        })
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.over) {
                            const periodLabel = data.period_label_id || 'periode ini';
                            const used = data.used ?? 0;
                            const quota = data.quota ?? 0;
                            const remaining = data.remaining !== null ? data.remaining : 'tak terbatas';

                            let html = '<strong>Jenis:</strong> ' + originalPermitType + '<br>';
                            html += 'Telah terpakai: <strong>' + used + '/' + quota + '</strong> pada ' + periodLabel + '.<br>';
                            if (remaining !== 'tak terbatas' && remaining !== null && remaining !== undefined) {
                                html += '<br><strong>Hanya tersisa ' + remaining + ' slot.</strong>';
                            } else {
                                html += '<br>Sisa kuota tidak tersedia.';
                            }
                            html += '<br><br>Kuota tidak dapat dilampaui tanpa izin darurat/khusus sesuai prosedur asrama.';

                            warningContent.innerHTML = html;
                            new bootstrap.Modal(modal).show();
                            approveBtn.innerHTML = originalHTML;
                            approveBtn.disabled = false;
                            isSubmitting = false;
                            return;
                        }
                    } else {
                        console.warn('Quota check skipped:', response.status);
                    }
                }
                form.submit();
            } catch (err) {
                console.error('Quota check error:', err);
                alert('Terjadi kesalahan.');
                approveBtn.innerHTML = originalHTML;
                approveBtn.disabled = false;
                isSubmitting = false;
            }
        });
    });

});
</script>
@endsection
