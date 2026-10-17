@extends('layouts.master')
@section('title') Scan Izin @endsection

@section('css')
<style>
    #qr-reader { width: 100%; max-width: 400px; margin: 0 auto; }
    #qr-reader video { border-radius: 8px; width: 100% !important; height: auto !important; object-fit: cover; }
    #qr-reader img { max-width: 100%; height: auto; }
    @media (max-width: 575.98px) {
        #qr-reader { max-width: 100%; }
    }
    .scan-result-card { display: none; }
    .scan-result-card.show { display: block; }
    /* Filter chips */
    .filter-badge {
        display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid #e2e8f0;
        border-radius: 30px; font-size: 13px; transition: all 0.2s; margin: 0; cursor: pointer; background: #fff;
    }
    .filter-badge:hover { background: #405189; border-color: #94a3b8; color: #fff; }
    .filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }
    /* Nav tabs custom */
    .nav-tabs-custom { border-bottom: 2px solid #e2e8f0; }
    .nav-tabs-custom .nav-link {
        border: none; border-bottom: 3px solid transparent; color: #64748b; font-weight: 500;
        padding: 12px 18px; display: flex; align-items: center; gap: 6px; transition: all 0.2s;
    }
    .nav-tabs-custom .nav-link:hover { color: #0a5f9e; border-bottom-color: #cbd5e1; }
    .nav-tabs-custom .nav-link.active { color: #0a5f9e; border-bottom-color: #0a5f9e; background: transparent; }
    .nav-tabs-custom .step-number {
        display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px;
        border-radius: 50%; background: #e2e8f0; color: #64748b; font-size: 12px; font-weight: 700; margin-right: 4px;
    }
    .nav-tabs-custom .nav-link.active .step-number { background: #0a5f9e; color: #fff; }
    /* Tabel perizinan: scroll horizontal di layar sempit agar kolom tidak terpotong */
    .permits-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border: 1px solid #e9ecef;
        border-radius: 6px;
    }
    .permits-table-wrap table { min-width: 760px; margin-bottom: 0; }
    .permits-table-wrap thead th {
        position: sticky; top: 0; background: #f8f9fa; z-index: 1;
        white-space: nowrap;
    }
    .permits-table-wrap tbody td { white-space: nowrap; vertical-align: middle; }
    .permits-table-wrap tbody td.cell-santri,
    .permits-table-wrap tbody td.cell-penjemput {
        white-space: normal; min-width: 180px;
    }
    @media (max-width: 575.98px) {
        .permits-table-wrap table { min-width: 640px; }
        .permits-table-wrap tbody td.cell-penjemput { min-width: 160px; }
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Perizinan</a> @endslot
        @slot('li_4') Scan QR @endslot
        @slot('title') Scan QR Izin Santri @endslot
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

    {{-- Scan Result (legacy fallback) --}}
    <div id="scan-result" class="alert alert-dismissible fade show" style="display:none;" role="alert">
        <span id="scan-result-text"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            {{-- Scanner Card --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-qr-scan-2-line me-2 text-primary"></i>Scan QR Izin Santri</h5>
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
                            <form id="manual-form" method="POST" action="{{ route('user.asrama.permits.scan.store', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Token QR / URL Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="token" id="manual_token" class="form-control @error('token') is-invalid @enderror"
                                           placeholder="Tempel hasil scan QR atau masukkan token langsung" required>
                                    @error('token')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Scan QR untuk catat penjemputan (pertama) atau kepulangan (kedua). Data penjemput otomatis diambil dari pengajuan izin.</small>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ri-check-line me-1"></i> Verifikasi
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter Bar (mirip visits) --}}
            <div class="card mb-3">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <div>
                                <h5 class="card-title mb-0"><i class="ri-filter-3-line me-2 text-primary"></i>Daftar Izin</h5>
                                <p class="text-muted mb-0 mt-1" style="font-size: 13px;">Menunjukkan izin sesuai filter periode & pencarian.</p>
                            </div>
                        </div>
                        <div class="col-sm-auto">
                            <form method="GET" action="{{ route('user.asrama.permits.scan', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}" id="scanFilterForm">
                                <div class="d-flex flex-wrap align-items-start gap-2">
                                    <div class="d-flex gap-2">
                                        <input type="text" name="search" value="{{ $search }}"
                                               class="form-control form-control-sm" placeholder="Cari nama santri…" style="width: 260px;">
                                        <button type="submit" class="btn btn-sm btn-primary"><i class="ri-search-line"></i> Cari</button>
                                    </div>

                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#filterModal">
                                        <i class="bx bx-filter-alt align-bottom me-1"></i> Filter Lanjutan
                                        @if($activeFilterCount > 0)
                                            <span class="badge bg-light text-dark ms-1">{{ $activeFilterCount }}</span>
                                        @endif
                                    </button>

                                    @if($activeFilterCount > 0)
                                    <a href="{{ route('user.asrama.permits.scan', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'search' => $search, 'period' => $period]) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Reset filter"><i class="ri-close-line"></i> Reset</a>
                                    @endif
                                </div>

                                {{-- Period chips --}}
                                <div class="mt-2 d-flex flex-wrap gap-1">
                                    @php $periods = ['today'=>'Hari Ini','week'=>'7 Hari','month'=>'30 Hari','all'=>'Semua']; @endphp
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
                        <span class="filter-badge active">Pencarian: "{{ $search }}"</span>
                        @endif
                        @if($period !== 'today')
                        <span class="filter-badge active">Periode: {{ $periods[$period] }}</span>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            {{-- Modal Filter Lanjutan --}}
            <div class="modal fade" id="filterModal" tabindex="-1" id="filterModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="GET" action="{{ route('user.asrama.permits.scan', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                            <div class="modal-header">
                                <h5 class="modal-title" id="filterModalLabel"><i class="bx bx-filter-alt me-2"></i>Filter Lanjutan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="search" value="{{ $search }}">
                                <div class="d-flex gap-2">
                                    <div>
                                        <label class="form-label small">Dari Tanggal</label>
                                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                                    </div>
                                    <div>
                                        <label class="form-label small">Sampai Tanggal</label>
                                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2" style="font-size: 11px;">
                                    <i class="ri-information-line"></i> Jika diisi, akan mengabaikan filter periode.
                                </small>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary"><i class="ri-check-line me-1"></i> Terapkan Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Wizard Tabs: 1) Menunggu Penjemputan → 2) Sudah Dijemput → 3) Riwayat Kepulangan --}}
            <div class="card">
                <div class="card-header border-bottom-dashed pb-0">
                    <ul class="nav nav-tabs nav-tabs-custom" id="visitStepper" role="tablist" style="border-bottom: none;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="step-pending-tab" data-bs-toggle="tab"
                                    data-bs-target="#step-pending" type="button" role="tab">
                                <span class="step-number">1</span><i class="ri-time-line me-1"></i>Menunggu Penjemputan
                                <span class="badge bg-warning-subtle text-warning ms-2">{{ $awaitingScan->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="step-picked-tab" data-bs-toggle="tab"
                                    data-bs-target="#step-picked" type="button" role="tab">
                                <span class="step-number">2</span><i class="ri-walk-line me-1"></i>Sudah Dijemput
                                <span class="badge bg-info-subtle text-info ms-2">{{ $sudahDijemput->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="step-completed-tab" data-bs-toggle="tab"
                                    data-bs-target="#step-completed" type="button" role="tab">
                                <span class="step-number">3</span><i class="ri-home-heart-line me-1"></i>Riwayat Kepulangan
                                <span class="badge bg-success-subtle text-success ms-2">{{ $riwayatKepulangan->count() }}</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-3">
                    <div class="tab-content" id="visitStepperTabContent">
                         {{-- Tab Menunggu Penjemputan --}}
                        <div class="tab-pane fade show active" id="step-pending" role="tabpanel">
                            @if($awaitingScan->isEmpty())
                            <div class="text-center py-4 text-muted">Tidak ada izin yang menunggu penjemputan.</div>
                            @else
                            <div class="permits-table-wrap">
                            <table class="table table-hover">
                                <thead><tr><th>#</th><th>Santri</th><th>Jenis</th><th>Penjemput (rencana)</th><th>Status</th><th>Aksi</th></tr></thead>
                                <tbody>
                                @php $n=1; @endphp
                                @foreach($awaitingScan as $permit)
                                <tr>
                                    <td class="text-center">{{ $n++ }}</td>
                                    <td class="cell-santri">
                                        <div class="fw-semibold">{{ $permit->student?->name ?? '—' }}</div>
                                        @if($permit->room)
                                            <div class="text-muted small">Kamar: {{ $permit->room->name }}</div>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-info-subtle text-info">{{ $permit->permit_type_text }}</span></td>
                                    <!-- <td>{{ $permit->departure_datetime?->format('d/m/Y H:i') ?? '—' }}</td> -->
                                    <td class="cell-penjemput">
                                        @if($permit->companion_name)
                                            <div style="font-size:13px;">{{ $permit->companion_name }}</div>
                                            @if($permit->companion_relation)
                                                <small class="text-muted">{{ $permit->companion_relation }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center"><span class="badge {{ $permit->status_badge }}">{{ $permit->status_text }}</span></td>
                                    <td class="text-center">
                                        {{-- Lihat Detail --}}
                                        <a href="{{ route('user.asrama.permits.show', ['userId'=>$userId,'asramaUuid'=>$dormitory->id,'permitUuid'=>$permit->id]) }}"
                                           class="btn btn-sm btn-outline-secondary me-1"
                                           data-bs-toggle="modal"
                                           data-bs-target="#detailModal"
                                           data-permit-id="{{ $permit->id }}"
                                           data-student-name="{{ $permit->student?->name ?? '—' }}"
                                           data-permit-type="{{ $permit->permit_type }}"
                                           data-departure="{{ $permit->departure_datetime?->format('d/m/Y H:i') ?? '' }}"
                                           data-status="{{ $permit->status }}"
                                           data-room="{{ $permit->room?->name ?? '—' }}">
                                            <i class="ri-eye-line me-1"></i>Detail
                                        </a>

                                        {{-- Update Status (Aksi Cepat) --}}
                                        <button type="button" class="btn btn-sm {{ in_array($permit->status, ['approved','overdue']) ? 'btn-outline-info' : 'btn-outline-success' }}" data-bs-toggle="modal" data-bs-target="#updateStatusModal"
                                                data-permit-id="{{ $permit->id }}"
                                                data-status="{{ $permit->status }}"
                                                data-student-name="{{ $permit->student?->name ?? '—' }}">
                                            @if(in_array($permit->status, ['approved','overdue']))
                                                <i class="ri-walk-line me-1"></i>Sudah Dijemput
                                            @elseif($permit->status === 'picked_up')
                                                <i class="ri-home-heart-line me-1"></i>Sudah Kembali
                                            @else
                                                <i class="ri-edit-line me-1"></i>Aksi Cepat
                                            @endif
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                                </tbody>
                            </table>
                            </div>
                            @endif
                        </div>

                         {{-- Tab Sudah Dijemput (sudah meninggalkan asrama, menunggu kepulangan) --}}
                        <div class="tab-pane fade" id="step-picked" role="tabpanel">
                            @if($sudahDijemput->isEmpty())
                            <div class="text-center py-4 text-muted">Tidak ada izin yang sudah dijemput.</div>
                            @else
                            <div class="permits-table-wrap">
                            <table class="table table-hover">
                                <thead><tr><th>#</th><th>Santri</th><th>Jenis</th><th>Waktu Dijemput</th><th>Penjemput</th><th>Status</th><th>Aksi</th></tr></thead>
                                <tbody>
                                @php $n=1; @endphp
                                @foreach($sudahDijemput as $permit)
                                @php
                                    $pickerName = $permit->pickup_details['picker_name'] ?? $permit->companion_name;
                                    $pickerRelation = $permit->pickup_details['picker_relation'] ?? $permit->companion_relation;
                                @endphp
                                <tr class="table-info-subtle">
                                    <td class="text-center">{{ $n++ }}</td>
                                    <td class="cell-santri">
                                        <div class="fw-semibold">{{ $permit->student?->name ?? '—' }}</div>
                                        @if($permit->room)
                                            <div class="text-muted small">Kamar: {{ $permit->room->name }}</div>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-info-subtle text-info">{{ $permit->permit_type_text }}</span></td>
                                    <td>{{ $permit->pickup_scanned_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="cell-penjemput">
                                        @if($pickerName)
                                            <div style="font-size:13px;" class="fw-semibold">
                                                <i class="ri-user-star-line text-success me-1"></i>{{ $pickerName }}
                                            </div>
                                            @if($pickerRelation)
                                                <small class="text-muted">{{ ucfirst($pickerRelation) }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted small" title="Belum ada data penjemput">(belum diisi)</span>
                                        @endif
                                    </td>
                                    <td class="text-center"><span class="badge {{ $permit->status_badge }}">{{ $permit->status_text }}</span></td>
                                    <td class="text-center">
                                        <a href="{{ route('user.asrama.permits.show', ['userId'=>$userId,'asramaUuid'=>$dormitory->id,'permitUuid'=>$permit->id]) }}"
                                           class="btn btn-sm btn-outline-secondary me-1">
                                            <i class="ri-eye-line me-1"></i>Detail
                                        </a>
                                        <button type="button" class="btn btn-sm {{ in_array($permit->status, ['approved','overdue']) ? 'btn-outline-info' : (in_array($permit->status, ['returned','rejected']) ? 'btn-outline-secondary' : 'btn-outline-success') }}" data-bs-toggle="modal" data-bs-target="#updateStatusModal"
                                                data-permit-id="{{ $permit->id }}"
                                                data-status="{{ $permit->status }}"
                                                data-student-name="{{ $permit->student?->name ?? '—' }}">
                                            @if(in_array($permit->status, ['approved','overdue']))
                                                <i class="ri-walk-line me-1"></i>Sudah Dijemput
                                            @elseif($permit->status === 'picked_up')
                                                <i class="ri-home-heart-line me-1"></i>Sudah Kembali
                                            @else
                                                <i class="ri-information-line me-1"></i>Lihat Detail
                                            @endif
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                                </tbody>
                            </table>
                            </div>
                            @endif
                        </div>

                         {{-- Tab Riwayat Kepulangan (sudah kembali ke asrama) --}}
                        <div class="tab-pane fade" id="step-completed" role="tabpanel">
                            @if($riwayatKepulangan->isEmpty())
                            <div class="text-center py-4 text-muted">Tidak ada riwayat kepulangan.</div>
                            @else
                            <div class="permits-table-wrap">
                            <table class="table table-hover">
                                <thead><tr><th>#</th><th>Santri</th><th>Jenis</th><th>Waktu Pulang</th><th>Penjemput</th><th>Status</th><th>Aksi</th></tr></thead>
                                <tbody>
                                @php $n=1; @endphp
                                @foreach($riwayatKepulangan as $permit)
                                @php
                                    $pickerName = $permit->pickup_details['picker_name'] ?? $permit->companion_name;
                                    $pickerRelation = $permit->pickup_details['picker_relation'] ?? $permit->companion_relation;
                                @endphp
                                <tr class="table-success-subtle">
                                    <td class="text-center">{{ $n++ }}</td>
                                    <td class="cell-santri">
                                        <div class="fw-semibold">{{ $permit->student?->name ?? '—' }}</div>
                                        @if($permit->room)
                                            <div class="text-muted small">Kamar: {{ $permit->room->name }}</div>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-info-subtle text-info">{{ $permit->permit_type_text }}</span></td>
                                    <td>{{ $permit->actual_return_datetime?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="cell-penjemput">
                                        @if($pickerName)
                                            <div style="font-size:13px;" class="fw-semibold">
                                                <i class="ri-user-star-line text-success me-1"></i>{{ $pickerName }}
                                            </div>
                                            @if($pickerRelation)
                                                <small class="text-muted">{{ ucfirst($pickerRelation) }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted small">(tidak tercatat)</span>
                                        @endif
                                    </td>
                                    <td class="text-center"><span class="badge {{ $permit->status_badge }}">{{ $permit->status_text }}</span></td>
                                    <td class="text-center">
                                        <a href="{{ route('user.asrama.permits.show', ['userId'=>$userId,'asramaUuid'=>$dormitory->id,'permitUuid'=>$permit->id]) }}"
                                           class="btn btn-sm btn-outline-secondary me-1">
                                            <i class="ri-eye-line me-1"></i>Lihat Detail
                                        </a>
                                        <button type="button" class="btn btn-sm {{ in_array($permit->status, ['approved','overdue']) ? 'btn-outline-info' : (in_array($permit->status, ['returned','rejected']) ? 'btn-outline-secondary' : 'btn-outline-success') }}" data-bs-toggle="modal" data-bs-target="#updateStatusModal"
                                                data-permit-id="{{ $permit->id }}"
                                                data-status="{{ $permit->status }}"
                                                data-student-name="{{ $permit->student?->name ?? '—' }}">
                                            @if(in_array($permit->status, ['approved','overdue']))
                                                <i class="ri-walk-line me-1"></i>Tandai Sudah Dijemput
                                            @elseif($permit->status === 'picked_up')
                                                <i class="ri-home-heart-line me-1"></i>Tandai Sudah Kembali
                                            @else
                                                <i class="ri-information-line me-1"></i>Lihat Detail
                                            @endif
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                                </tbody>
                            </table>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Detail Modal --}}
                <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Detail Izin</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <small class="text-muted">Santri</small>
                                        <div class="fw-semibold" id="detail-student-name"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Kamar</small>
                                        <div id="detail-room"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Jenis Izin</small>
                                        <div><span class="badge bg-info-subtle text-info" id="detail-permit-type"></span></div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Status Saat Ini</small>
                                        <div id="detail-status"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Waktu Berangkat</small>
                                        <div id="detail-departure"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Waktu Pulang</small>
                                        <div id="detail-return"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>

{{-- Update Status Modal: 1 tombol konfirmasi sesuai fase --}}
                <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form id="updateStatusForm" method="POST" action="">
                                @csrf
                                <input type="hidden" id="update-permit-id" name="permit_id">
                                <input type="hidden" id="update-action" value="pickup">
                                <div class="modal-body text-center p-5">
                                    <lord-icon id="update-lord-icon" src="https://cdn.lordicon.com/lupuorrc.json"
                                               trigger="loop" colors="primary:#08a88a,secondary:#08a88a"
                                               style="width:110px;height:110px"></lord-icon>
                                    <div class="mt-3">
                                        <h4 class="mb-1" id="update-title">Konfirmasi Penjemputan</h4>
                                        <p class="text-muted mb-1">
                                            Santri: <span class="fw-semibold text-body" id="update-student-name">—</span>
                                        </p>
                                        <div class="mb-3"><span id="update-current-status"></span></div>
                                    </div>

                                    <div class="text-start mb-4">
                                        <label for="update-action-note" class="form-label small">Catatan (opsional)</label>
                                        <textarea name="note" id="update-action-note" class="form-control form-control-sm" rows="2"
                                                  placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        <i class="ri-information-line me-1"></i>Data penjemput otomatis diambil dari pengajuan izin yang diisi wali.
                                    </p>
                                    <div class="hstack gap-2 justify-content-center">
                                        <button type="button" class="btn btn-light fw-medium material-shadow-none" data-bs-dismiss="modal">
                                            <i class="ri-close-line me-1 align-middle"></i> Batal
                                        </button>
                                        <button type="button" class="btn btn-success" id="btn-update-status">
                                            <i class="ri-check-double-line me-1"></i>
                                            <span id="btn-update-label">Sudah Dijemput</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const jsonHeaders = {
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded',
    };

    // QR Camera Scanner
    let html5QrCode = null;
    const qrReader = document.getElementById('qr-reader');
    const statusEl = document.getElementById('qr-reader--status');
    const manualForm = document.getElementById('manual-form');
    const scanResult = document.getElementById('scan-result');
    const scanUrlInput = document.getElementById('manual_token');

    // Modal-result dinamis (lord-icon template) — dipanggil dari JS
    function buildResultModalHtml(id, ok, message) {
        const icon = ok
            ? 'https://cdn.lordicon.com/lupuorrc.json'
            : 'https://cdn.lordicon.com/tdrtiskw.json';
        const primary = '#121331';
        const secondary = ok ? '#08a88a' : '#dc3545';
        const title = ok ? 'Berhasil!' : 'Gagal!';
        const primaryLabel = ok ? 'Selesai' : 'Tutup';
        const safeMessage = (message || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return `
<div class="modal fade" id="${id}" tabindex="-1" aria-labelledby="${id}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-5">
                <lord-icon src="${icon}" trigger="loop" colors="primary:${primary},secondary:${secondary}" style="width:120px;height:120px"></lord-icon>
                <div class="mt-4">
                    <h4 class="mb-3">${title}</h4>
                    <p class="text-muted mb-4">${safeMessage}</p>
                    <div class="hstack gap-2 justify-content-center">
                        <a href="javascript:void(0);" class="btn btn-link link-${ok ? 'success' : 'danger'} fw-medium material-shadow-none" data-bs-dismiss="modal"><i class="ri-close-line me-1 align-middle"></i> Tutup</a>
                        <button type="button" class="btn btn-${ok ? 'success' : 'danger'}" data-bs-dismiss="modal">${primaryLabel}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>`;
    }

    function showResultModal(ok, message, opts = {}) {
        const id = opts.id || ('jsResultModal_' + Date.now());
        // Hapus instance sebelumnya (kalau ada) untuk mencegah duplikat
        const existing = document.getElementById(id);
        if (existing) existing.remove();
        const wrap = document.createElement('div');
        wrap.innerHTML = buildResultModalHtml(id, ok, message);
        document.body.appendChild(wrap.firstElementChild);
        const el = document.getElementById(id);
        const m = new bootstrap.Modal(el, { backdrop: 'static', keyboard: false });
        el.addEventListener('hidden.bs.modal', () => el.remove());
        m.show();
        return m;
    }

    function showResult(ok, message) {
        // Kompatibel: fallback ke alert jika modal gagal inisialisasi
        try {
            showResultModal(ok, message);
        } catch (e) {
            if (!scanResult) return;
            scanResult.style.display = 'block';
            scanResult.innerHTML = `<strong>${message}</strong>`;
            scanResult.className = `alert alert-${ok ? 'success' : 'danger'} alert-dismissible fade show`;
        }
    }

    // AUTO-PROCESS: kirim fetch ke scanStore segera setelah QR terdeteksi
    async function processScan(scanUrl) {
        if (!scanUrl) return;
        showResult(true, '⏳ QR terdeteksi, sedang memproses...');
        try {
            const formBody = new URLSearchParams();
            formBody.append('scan_url', scanUrl);
            const response = await fetch(manualForm.action, {
                method: 'POST',
                headers: jsonHeaders,
                body: formBody.toString(),
            });
            let data = {};
            try { data = await response.json(); } catch (e) { console.warn('Parse JSON gagal', e); }
            if (response.ok && data.success) {
                const modal = showResult(true, data.message || 'Berhasil.');
                if (modal && modal._element) {
                    modal._element.addEventListener('hidden.bs.modal', () => window.location.reload(), { once: true });
                } else {
                    setTimeout(() => window.location.reload(), 1500);
                }
            } else {
                const errorMsg = (data && data.message) || response.statusText || 'Scan gagal.';
                console.error('Scan error response:', { status: response.status, data });
                const modal = showResult(false, errorMsg);
                if (modal && modal._element) {
                    modal._element.addEventListener('hidden.bs.modal', () => startScanner(), { once: true });
                } else {
                    setTimeout(() => startScanner(), 2000);
                }
            }
        } catch (err) {
            const modal = showResult(false, 'Terjadi kesalahan jaringan: ' + err.message);
            if (modal && modal._element) {
                modal._element.addEventListener('hidden.bs.modal', () => startScanner(), { once: true });
            } else {
                setTimeout(() => startScanner(), 2000);
            }
        }
    }

    function onScanSuccess(decodedText) {
        const url = decodedText.trim();
        if (html5QrCode) html5QrCode.stop().catch(() => {});
        if (scanUrlInput) scanUrlInput.value = url;
        processScan(url);
    }

    function startScanner() {
        if (!qrReader) return;
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop().catch(() => {});
            return;
        }
        html5QrCode = new Html5Qrcode("qr-reader");
        const config = {
            fps: 10,
            qrbox: function(viewfinderWidth, viewfinderHeight) {
                // Ikuti lebar viewfinder agar pas di mobile & desktop.
                // Minimal 200px supaya QR tetap terbaca, maksimal 320px (desktop).
                const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                const size = Math.max(200, Math.min(320, Math.floor(minEdge * 0.8)));
                return { width: size, height: size };
            },
            aspectRatio: 1.0,
        };
        html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess, () => {}).then(() => {
            if (statusEl) {
                statusEl.textContent = '📷 Kamera aktif. Arahkan ke QR Code Izin.';
                statusEl.className = 'text-center text-muted small';
            }
        }).catch(() => {
            if (statusEl) {
                statusEl.textContent = '⚠️ Kamera tidak tersedia. Gunakan tab Manual atau tombol aksi di tabel.';
                statusEl.className = 'text-center text-warning small';
            }
        });
    }
    startScanner();

    // Restart kamera saat user pindah tab kembali ke Kamera (mis. habis pakai Manual),
    // dan saat window di-resize / device di-rotate agar viewfinder pas ulang.
    const cameraTabBtn = document.getElementById('camera-tab');
    if (cameraTabBtn) {
        cameraTabBtn.addEventListener('shown.bs.tab', function () {
            if (html5QrCode && !html5QrCode.isScanning) {
                startScanner();
            }
        });
    }
    let resizeTimer = null;
    window.addEventListener('resize', function () {
        if (resizeTimer) clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => startScanner()).catch(() => {});
            }
        }, 350);
    });
    window.addEventListener('orientationchange', function () {
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop().then(() => setTimeout(startScanner, 400)).catch(() => {});
        }
    });

    // Handle manual submit (tab Manual) — sama-sama pakai processScan
    if (manualForm) {
        manualForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const url = scanUrlInput?.value?.trim();
            if (!url) {
                showResult(false, 'Tempelkan URL QR atau token terlebih dahulu.');
                return;
            }
            await processScan(url);
        });
    }

    // Period filter helper
    window.setPeriod = function(period) {
        document.getElementById('periodInput').value = period;
        document.getElementById('scanFilterForm')?.submit();
    };

    // Modal Detail
    const detailModal = document.getElementById('detailModal');
    if (detailModal) {
        detailModal.addEventListener('show.bs.modal', function(event) {
            const btn = event.relatedTarget;
            if (!btn) return;
            document.getElementById('detail-student-name').textContent = btn.dataset.studentName || '—';
            document.getElementById('detail-room').textContent = btn.dataset.room || '—';
            document.getElementById('detail-permit-type').textContent = btn.dataset.permitType || '—';
            document.getElementById('detail-departure').textContent = btn.dataset.departure || '—';
            const status = btn.dataset.status || 'pending';
            const statusBadge = {
                'pending': '<span class="badge bg-warning text-dark">Menunggu</span>',
                'approved': '<span class="badge bg-primary">Disetujui</span>',
                'overdue': '<span class="badge bg-danger">Telat</span>',
                'picked_up': '<span class="badge bg-info">Sudah Dijemput</span>',
                'returned': '<span class="badge bg-success">Pulang</span>',
                'rejected': '<span class="badge bg-secondary">Ditolak</span>',
            };
            document.getElementById('detail-status').innerHTML = statusBadge[status] || `<span class="badge bg-light text-dark">${status}</span>`;
            document.getElementById('detail-return').textContent = 'Lihat halaman detail untuk informasi lengkap.';
        });
    }

    // Modal Update Status (1 tombol konfirmasi — sesuai fase izin)
    const updateStatusModal = document.getElementById('updateStatusModal');
    const updateStatusForm = document.getElementById('updateStatusForm');
    const pickupUrlTemplate = '{!! route('user.asrama.permits.pickup', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => '__PERMIT__'], false) !!}';
    const updateStatusUrlTemplate = '{!! route('user.asrama.permits.update-status', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitUuid' => '__PERMIT__'], false) !!}';

    const statusBadgeMap = {
        'pending': '<span class="badge bg-warning text-dark">Menunggu</span>',
        'approved': '<span class="badge bg-primary">Disetujui</span>',
        'overdue': '<span class="badge bg-danger">Telat</span>',
        'picked_up': '<span class="badge bg-info">Sudah Dijemput</span>',
        'returned': '<span class="badge bg-success">Pulang</span>',
        'rejected': '<span class="badge bg-secondary">Ditolak</span>',
    };

    // Phase configuration: tentukan label, ikon, warna, dan endpoint berdasarkan fase saat ini
    const phaseConfig = {
        approved: {
            action: 'pickup',
            title: 'Konfirmasi Penjemputan',
            buttonLabel: 'Tandai Sudah Dijemput',
            busyLabel: 'Mencatat Penjemputan...',
            fallbackMessage: 'Penjemputan berhasil dicatat.',
            btnClass: 'btn-info',
            iconSrc: 'https://cdn.lordicon.com/lupuorrc.json',
            iconColor: '#08a88a',
            buttonIcon: 'ri-walk-line',
        },
        overdue: {
            action: 'pickup',
            title: 'Konfirmasi Penjemputan (Terlambat)',
            buttonLabel: 'Tandai Sudah Dijemput',
            busyLabel: 'Mencatat Penjemputan...',
            fallbackMessage: 'Penjemputan berhasil dicatat.',
            btnClass: 'btn-info',
            iconSrc: 'https://cdn.lordicon.com/lupuorrc.json',
            iconColor: '#08a88a',
            buttonIcon: 'ri-walk-line',
        },
        picked_up: {
            action: 'return',
            title: 'Konfirmasi Kepulangan',
            buttonLabel: 'Sudah Kembali ke Asrama',
            busyLabel: 'Mencatat Kepulangan...',
            fallbackMessage: 'Kepulangan berhasil dicatat.',
            btnClass: 'btn-success',
            iconSrc: 'https://cdn.lordicon.com/lupuorrc.json',
            iconColor: '#08a88a',
            buttonIcon: 'ri-home-heart-line',
        },
    };

    function buildActionUrl(template, permitId) {
        return template.replace('__PERMIT__', permitId || '');
    }

    if (updateStatusModal && updateStatusForm) {
        let currentPermitId = null;
        let currentConfig = null;

        updateStatusModal.addEventListener('show.bs.modal', function(event) {
            const btn = event.relatedTarget;
            if (!btn) return;
            currentPermitId = btn.dataset.permitId;
            const status = btn.dataset.status || '';
            currentConfig = phaseConfig[status];

            document.getElementById('update-permit-id').value = currentPermitId || '';
            document.getElementById('update-student-name').textContent = btn.dataset.studentName || '—';
            document.getElementById('update-current-status').innerHTML = statusBadgeMap[status] || '<span class="badge bg-light text-dark">—</span>';
            document.getElementById('update-action-note').value = '';
            document.getElementById('update-action').value = '';

            const confirmBtn = document.getElementById('btn-update-status');
            const confirmLabel = document.getElementById('btn-update-label');
            const titleEl = document.getElementById('update-title');
            const lordIcon = document.getElementById('update-lord-icon');

            if (!currentConfig) {
                // Fase yang tidak punya aksi (returned/rejected/pending) — sembunyikan tombol
                titleEl.textContent = 'Tidak Ada Aksi Tersedia';
                confirmLabel.textContent = 'Tidak Ada Aksi';
                confirmBtn.disabled = true;
                confirmBtn.classList.remove('btn-info', 'btn-success');
                confirmBtn.classList.add('btn-secondary');
                lordIcon.src = 'https://cdn.lordicon.com/tdrtiskw.json';
                lordIcon.setAttribute('colors', 'primary:#6c757d,secondary:#adb5bd');
                return;
            }

            confirmBtn.disabled = false;
            // Reset class lalu pasang yang sesuai fase
            confirmBtn.classList.remove('btn-info', 'btn-success', 'btn-secondary');
            confirmBtn.classList.add(currentConfig.btnClass);
            // Ikon tombol
            const iconEl = confirmBtn.querySelector('i');
            if (iconEl) iconEl.className = currentConfig.buttonIcon + ' me-1';
            confirmLabel.textContent = currentConfig.buttonLabel;
            titleEl.textContent = currentConfig.title;
            document.getElementById('update-action').value = currentConfig.action;
            // Lord-icon: ganti src + warna
            lordIcon.src = currentConfig.iconSrc;
            lordIcon.setAttribute('colors', 'primary:' + currentConfig.iconColor + ',secondary:' + currentConfig.iconColor);

            // Form penjemput otomatis dari data pengajuan izin (tidak perlu input lagi di sini)
        });

        // Reset form saat modal ditutup
        updateStatusModal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('update-action-note').value = '';
            currentPermitId = null;
            currentConfig = null;
        });

        const confirmBtn = document.getElementById('btn-update-status');
        confirmBtn.addEventListener('click', async function() {
            if (!currentPermitId || !currentConfig) return;
            const note = document.getElementById('update-action-note').value.trim();
            const confirmLabel = document.getElementById('btn-update-label');

            let url, body;
            if (currentConfig.action === 'pickup') {
                url = buildActionUrl(pickupUrlTemplate, currentPermitId);
                body = 'note=' + encodeURIComponent(note);
            } else if (currentConfig.action === 'return') {
                url = buildActionUrl(updateStatusUrlTemplate, currentPermitId);
                body = 'status=returned&note=' + encodeURIComponent(note);
            } else return;

            confirmBtn.disabled = true;
            const originalIcon = confirmBtn.querySelector('i').className;
            confirmBtn.querySelector('i').className = '';
            confirmLabel.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + currentConfig.busyLabel;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: jsonHeaders,
                    body,
                });
                const data = await response.json().catch(() => ({}));

                const modalInstance = bootstrap.Modal.getInstance(updateStatusModal);
                if (modalInstance) modalInstance.hide();

                if (response.ok && data.success) {
                    const resultModal = showResult(true, data.message || currentConfig.fallbackMessage);
                    if (resultModal && resultModal._element) {
                        resultModal._element.addEventListener('hidden.bs.modal', () => window.location.reload(), { once: true });
                    } else {
                        setTimeout(() => window.location.reload(), 1200);
                    }
                } else {
                    showResult(false, data.message || 'Gagal memproses aksi.');
                }
            } catch (err) {
                showResult(false, 'Terjadi kesalahan jaringan: ' + err.message);
            } finally {
                confirmBtn.disabled = false;
                const iconEl = confirmBtn.querySelector('i');
                if (iconEl) iconEl.className = originalIcon;
                confirmLabel.textContent = currentConfig.buttonLabel;
            }
        });
    }

    window.addEventListener('beforeunload', () => { if(html5QrCode) html5QrCode.stop().catch(()=>{}); });
})();
</script>
@endsection
