@extends('layouts.master')
@section('title') Approval Center — {{ $asrama->name ?? 'Asrama' }} @endsection
@php $userId = $userId ?? request()->route('userId') ?? (function_exists('auth') && auth()->check() ? auth()->id() : null); @endphp

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('title') {{ $asrama->name ?? 'Asrama' }} — Approval Center @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> Terjadi kesalahan:
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- ============================================================
         INFO CARDS
    ============================================================ --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded">
                                <i class="ri-mail-unread-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Menunggu Persetujuan</p>
                            <h3 class="stat-value">{{ number_format($counts['total'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Total permohonan masuk</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded">
                                <i class="ri-home-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Izin Pulang</p>
                            <h3 class="stat-value">{{ number_format($counts['leave'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-home-4-line me-1"></i>Permohonan izin pulang</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded">
                                <i class="ri-user-heart-line text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Penjengukan</p>
                            <h3 class="stat-value">{{ number_format($counts['visit'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-user-follow-line me-1"></i>Permohonan kunjungan</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-hospital-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Izin Sakit</p>
                            <h3 class="stat-value">{{ number_format($counts['health'] ?? 0) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-first-aid-kit-line me-1"></i>Permohonan izin sakit</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         CARD UTAMA
    ============================================================ --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="approvalCard">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-mail-open-line me-1 text-primary"></i>Inbox Persetujuan — {{ $asrama->name ?? 'Asrama' }}</h5>
                            <p class="text-muted mb-0 small">
                                <span class="badge bg-primary-subtle text-primary">{{ $counts['total'] }} menunggu</span>
                                <span class="ms-2" id="visibleCount"></span>
                            </p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <input type="text" id="globalSearch" class="form-control form-control-sm"
                                       placeholder="Cari nama, pengaju, detail..." style="max-width:260px;">
                                <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}"
                                   class="btn btn-outline-secondary btn-sm" title="Kembali ke Asrama">
                                    <i class="ri-arrow-left-line me-1"></i> Kembali
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================
                     QUICK FILTER BAR (client-side)
                ============================================================ --}}
                <div class="card-header py-2 bg-light border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="text-muted me-2"><i class="ri-flashlight-line"></i> Quick Filter:</span>

                        <button type="button" class="filter-badge js-filter-type" data-type="">
                            <i class="ri-apps-2-line"></i> Semua
                        </button>
                        <button type="button" class="filter-badge js-filter-type" data-type="leave">
                            <i class="ri-home-line"></i> Izin Pulang
                        </button>
                        <button type="button" class="filter-badge js-filter-type" data-type="visit">
                            <i class="ri-user-heart-line"></i> Penjengukan
                        </button>
                        <button type="button" class="filter-badge js-filter-type" data-type="health">
                            <i class="ri-hospital-line"></i> Izin Sakit
                        </button>

                        <span class="mx-2 text-muted">|</span>

                        <button type="button" class="filter-badge js-sort" data-sort="newest">
                            <i class="ri-time-line"></i> Pengajuan Terbaru
                        </button>
                        <button type="button" class="filter-badge js-sort" data-sort="oldest">
                            <i class="ri-history-line"></i> Pengajuan Terlama
                        </button>

                        <button type="button" class="btn btn-sm btn-link text-danger ms-auto" id="clearFilter" style="display:none">
                            <i class="ri-close-circle-line me-1"></i> Hapus Filter
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-container">
                        <table class="table table-hover align-middle" id="approvalTable">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:50px;">No.</th>
                                    <th>Santri</th>
                                    <th>Jenis Permohonan</th>
                                    <th>Diajukan Oleh</th>
                                    <th>Detail / Jadwal</th>
                                    <th style="width:140px;">Waktu</th>
                                    <th class="text-center" style="width:110px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $i => $item)
                                    <tr
                                        data-type="{{ $item['type'] }}"
                                        data-type-label="{{ $item['type_label'] }}"
                                        data-date="{{ $item['started_at'] }}"
                                        data-search="{{ strtolower(($item['student_name'] ?? '') . ' ' . ($item['requester_name'] ?? '') . ' ' . ($item['detail'] ?? '')) }}">
                                        <td class="text-center text-muted">{{ $i + 1 }}</td>
                                        <td>
                                            <strong>{{ $item['student_name'] ?? '—' }}</strong>
                                        </td>
                                        <td>
                                            @if($item['type'] === 'leave')
                                                <span class="badge bg-warning-subtle text-warning">
                                                    <i class="ri-home-line me-1"></i>{{ $item['type_label'] }}
                                                </span>
                                            @elseif($item['type'] === 'visit')
                                                <span class="badge bg-info-subtle text-info">
                                                    <i class="ri-user-heart-line me-1"></i>{{ $item['type_label'] }}
                                                </span>
                                            @elseif($item['type'] === 'health')
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="ri-hospital-line me-1"></i>{{ $item['type_label'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $item['requester_name'] ?? '—' }}</td>
                                        <td>
                                            <div style="max-width:280px">
                                                <small class="text-muted d-block">{{ $item['detail'] ?? '—' }}</small>
                                                @if(!empty($item['extra']['return_at']))
                                                    <small class="text-muted">Kembali: <strong>{{ \Carbon\Carbon::parse($item['extra']['return_at'])->isoFormat('D MMM YYYY') }}</strong></small>
                                                @elseif(!empty($item['extra']['visit_from']))
                                                    <small class="text-muted">Mulai: <strong>{{ \Carbon\Carbon::parse($item['extra']['visit_from'])->isoFormat('D MMM HH:mm') }}</strong></small>
                                                    &rarr; <strong>{{ \Carbon\Carbon::parse($item['extra']['visit_to'])->isoFormat('D MMM HH:mm') }}</strong>
                                                @elseif(!empty($item['extra']['start_date']))
                                                    <small class="text-muted">{{ \Carbon\Carbon::parse($item['extra']['start_date'])->isoFormat('D MMM YYYY') }} → {{ \Carbon\Carbon::parse($item['extra']['end_date'])->isoFormat('D MMM YYYY') }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <small title="{{ $item['started_at'] }}">{{ \Carbon\Carbon::parse($item['started_at'])->diffForHumans() }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button" class="btn btn-outline-success btn-sm approve-trigger"
                                                        data-id="{{ $item['id'] }}"
                                                        data-type="{{ $item['type'] }}"
                                                        data-title="{{ $item['title'] }}"
                                                        data-requester="{{ $item['requester_name'] }}"
                                                        title="Setujui">
                                                    <i class="ri-check-line"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm reject-trigger"
                                                        data-id="{{ $item['id'] }}"
                                                        data-type="{{ $item['type'] }}"
                                                        data-title="{{ $item['title'] }}"
                                                        title="Tolak">
                                                    <i class="ri-close-circle-line"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="7" title="Tidak ada permohonan yang menunggu"
                                        message="Semua permohonan telah diproses.">
                                        <!-- <i class="ri-checkbox-circle-line fs-1 d-block mb-2 text-success"></i> -->
                                    </x-empty-state>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL: Setujui
    ============================================================ --}}
    <div class="modal fade zoomIn" id="approveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-checkbox-circle-line me-2 text-success"></i>Setujui Permohonan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form id="approveForm" action="{{ route('user.asrama.approval-center.approve', [$userId, $asramaUuid]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" id="approveType">
                    <input type="hidden" name="id" id="approveId">
                    <div class="modal-body">
                        <div class="text-center">
                            <div class="avatar-lg mx-auto mb-3">
                                <span class="avatar-title bg-success-subtle rounded-circle fs-1 text-success">
                                    <i class="ri-check-line"></i>
                                </span>
                            </div>
                            <h5>Setujui Permohonan?</h5>
                            <p class="text-muted mb-0" id="approveTitle">Permohonan akan disetujui.</p>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-center gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-check-line me-1"></i>Setujui
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL: Tolak
    ============================================================ --}}
    <div class="modal fade zoomIn" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-close-circle-line me-2 text-danger"></i>Tolak Permohonan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form id="rejectForm" action="{{ route('user.asrama.approval-center.reject', [$userId, $asramaUuid]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" id="rejectType">
                    <input type="hidden" name="id" id="rejectId">
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <div class="avatar-lg mx-auto mb-3">
                                <span class="avatar-title bg-danger-subtle rounded-circle fs-1 text-danger">
                                    <i class="ri-close-line"></i>
                                </span>
                            </div>
                            <h5>Tolak Permohonan?</h5>
                            <p class="text-muted mb-0" id="rejectTitle">Permohonan akan ditolak.</p>
                        </div>
                        <div class="mb-2">
                            <label for="rejectReason" class="form-label fs-12 text-muted mb-1">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea name="reason" id="rejectReason" class="form-control" rows="3" required maxlength="500"
                                placeholder="Contoh: Kuota tidak mencukupi..."></textarea>
                            <div class="text-end">
                                <small class="text-muted"><span id="rejectReasonCount">0</span>/500</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-center gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="ri-close-circle-line me-1"></i>Tolak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    var tbody      = document.querySelector('#approvalTable tbody');
    var rows       = tbody ? Array.from(tbody.querySelectorAll('tr[data-type]')) : [];
    var allRows    = rows.slice();
    var currentType = '';
    var currentSort = 'newest';
    var currentSearch = '';
    var visibleCount = document.getElementById('visibleCount');
    var clearBtn   = document.getElementById('clearFilter');

    function applyFilters() {
        var filtered = allRows.filter(function (row) {
            var matchType = !currentType || row.dataset.type === currentType;
            var matchSearch = !currentSearch || row.dataset.search.indexOf(currentSearch) !== -1;
            return matchType && matchSearch;
        });

        filtered.sort(function (a, b) {
            var da = new Date(a.dataset.date).getTime();
            var db = new Date(b.dataset.date).getTime();
            return currentSort === 'newest' ? db - da : da - db;
        });

        rows.forEach(function (r) { r.style.display = 'none'; });
        filtered.forEach(function (r, idx) {
            r.style.display = '';
            var firstTd = r.querySelector('td');
            if (firstTd) firstTd.textContent = idx + 1;
            tbody.appendChild(r);
        });

        if (visibleCount) {
            visibleCount.textContent = filtered.length === allRows.length
                ? ''
                : '· ' + filtered.length + ' tampil dari ' + allRows.length;
        }

        if (clearBtn) {
            var hasActive = currentType || currentSort !== 'newest' || currentSearch;
            clearBtn.style.display = hasActive ? '' : 'none';
        }
    }

    document.querySelectorAll('.js-filter-type').forEach(function (btn) {
        if (btn.dataset.type === '' && !currentType) btn.classList.add('active');
        btn.addEventListener('click', function () {
            currentType = this.dataset.type;
            document.querySelectorAll('.js-filter-type').forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            applyFilters();
        });
    });

    document.querySelectorAll('.js-sort').forEach(function (btn) {
        if (btn.dataset.sort === 'newest') btn.classList.add('active');
        btn.addEventListener('click', function () {
            currentSort = this.dataset.sort;
            document.querySelectorAll('.js-sort').forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            applyFilters();
        });
    });

    var searchInput = document.getElementById('globalSearch');
    if (searchInput) {
        var searchTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                currentSearch = this.value.trim().toLowerCase();
                applyFilters();
            }.bind(this), 200);
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            currentType = '';
            currentSort = 'newest';
            currentSearch = '';
            if (searchInput) searchInput.value = '';
            document.querySelectorAll('.filter-badge').forEach(function (b) { b.classList.remove('active'); });
            document.querySelector('.js-filter-type[data-type=""]').classList.add('active');
            document.querySelector('.js-sort[data-sort="newest"]').classList.add('active');
            applyFilters();
        });
    }

    applyFilters();

    var approveEl = document.getElementById('approveModal');
    if (approveEl) {
        var approveTitle = document.getElementById('approveTitle');
        var approveType  = document.getElementById('approveType');
        var approveId    = document.getElementById('approveId');

        document.querySelectorAll('.approve-trigger').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var requester = this.dataset.requester || '';
                var title = this.dataset.title || 'Permohonan akan disetujui.';
                var peribahasa = requester ? ' atas nama ' + requester : '';
                approveTitle.textContent = title + peribahasa + '.';
                approveType.value = this.dataset.type || '';
                approveId.value   = this.dataset.id || '';
                new bootstrap.Modal(approveEl).show();
            });
        });
    }

    var rejectEl = document.getElementById('rejectModal');
    if (rejectEl) {
        var rejectTitle = document.getElementById('rejectTitle');
        var rejectType  = document.getElementById('rejectType');
        var rejectId    = document.getElementById('rejectId');
        var rejectReason = document.getElementById('rejectReason');
        var rejectCount = document.getElementById('rejectReasonCount');

        function updateRejectCount() {
            rejectCount.textContent = rejectReason.value.length;
        }
        rejectReason.addEventListener('input', updateRejectCount);

        document.querySelectorAll('.reject-trigger').forEach(function (btn) {
            btn.addEventListener('click', function () {
                rejectTitle.textContent = this.dataset.title || 'Permohonan akan ditolak.';
                rejectType.value = this.dataset.type || '';
                rejectId.value   = this.dataset.id || '';
                rejectReason.value = '';
                updateRejectCount();
                new bootstrap.Modal(rejectEl).show();
            });
        });
    }
});
</script>
@endsection