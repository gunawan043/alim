@extends('layouts.master')
@section('title') Approval Center — {{ $asrama->name ?? 'Asrama' }} @endsection

@section('breadcrumb')
    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('title') {{ $asrama->name ?? 'Asrama' }} — Approval Center @endslot
    @endcomponent
@endsection

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .table-freeze {
        table-layout: auto;
        width: 100%;
        margin-bottom: 0;
    }
    .table-freeze th,
    .table-freeze td {
        white-space: normal;
        vertical-align: middle;
        padding: 12px 16px;
        word-break: break-word;
    }
    .table-freeze thead th {
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        background: #f8fafc;
    }
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
        background: #fff;
    }
    .filter-badge:hover { background: #405189; border-color: #94a3b8; color: #fff; }
    .filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }
    .info-card-label {
        font-size: 11px;
        letter-spacing: .5px;
        text-transform: uppercase;
        color: #6c757d;
        font-weight: 600;
    }
    .info-card-value {
        font-size: 15px;
        font-weight: 600;
        color: #212529;
    }
</style>
@endsection

@section('content')

<div class="row mt-4 mb-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="mb-1"><i class="ri-mail-open-line me-2"></i>Inbox Persetujuan</h4>
                <p class="text-muted mb-0">Semua permohonan menunggu persetujuan — satu tempat</p>
            </div>
            <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="ri-arrow-left-line me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

{{-- INFO CARDS --}}
<div class="row g-3 mb-2">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90 border-start border-primary">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-mail-unread-line fs-24 text-primary"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Menunggu Persetujuan</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $counts['total'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90 border-start border-warning">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-home-line fs-24 text-warning"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Izin Pulang</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $counts['leave'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90 border-start border-info">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-user-heart-line fs-24 text-info"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Penjengukan</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $counts['visit'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90 border-start border-success">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-hospital-line fs-24 text-success"></i></span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Izin Sakit</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $counts['health'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="ri-check-line me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-12">
        <div class="card" id="approvalCard">
            <div class="card-header border-bottom-dashed">
                <div class="row g-4 align-items-center">
                    <div class="col-sm">
                        <div>
                            <h5 class="card-title mb-0">Daftar Permohonan — {{ $asrama->name ?? 'Asrama' }}</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-primary-subtle text-primary">{{ $counts['total'] }} menunggu</span>
                                <span class="ms-2" id="visibleCount" class="text-muted"></span>
                            </p>
                        </div>
                    </div>
                    <div class="col-sm-auto">
                        <div class="d-flex flex-wrap align-items-start gap-2">
                            <input type="text" class="form-control" id="globalSearch"
                                placeholder="Cari nama, pengaju, detail..." style="width: 260px;">
                        </div>
                    </div>
                </div>
            </div>

            {{-- QUICK FILTER BAR --}}
            <div class="card-header py-2 bg-light border-bottom">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="text-muted me-2"><i class="ri-flashlight-line"></i> Quick Filter:</span>

                    {{-- Jenis permohonan --}}
                    <button class="filter-badge js-filter-type" data-type="">
                        <i class="ri-apps-2-line"></i> Semua
                    </button>
                    <button class="filter-badge js-filter-type" data-type="leave">
                        <i class="ri-home-line"></i> Izin Pulang
                    </button>
                    <button class="filter-badge js-filter-type" data-type="visit">
                        <i class="ri-user-heart-line"></i> Penjengukan
                    </button>
                    <button class="filter-badge js-filter-type" data-type="health">
                        <i class="ri-hospital-line"></i> Izin Sakit
                    </button>

                    <span class="mx-2 text-muted">|</span>

                    {{-- Urutan --}}
                    <button class="filter-badge js-sort" data-sort="newest">
                        <i class="ri-time-line"></i> Pengajuan Terbaru
                    </button>
                    <button class="filter-badge js-sort" data-sort="oldest">
                        <i class="ri-history-line"></i> Pengajuan Terlama
                    </button>

                    <button class="btn btn-sm btn-link text-danger ms-auto" id="clearFilter" style="display:none">
                        <i class="ri-close-circle-line me-1"></i> Hapus Filter
                    </button>
                </div>
            </div>

            <div class="card-body">
                @if(empty($items))
                    <div class="text-center py-5">
                        <i class="ri-checkbox-circle-line text-success" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 text-muted">Tidak ada permohonan yang menunggu</h5>
                        <p class="text-muted">Semua permohonan telah diproses.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-freeze" id="approvalTable">
                            <thead>
                                <tr>
                                    <th width="40">No.</th>
                                    <th>Santri</th>
                                    <th>Jenis Permohonan</th>
                                    <th>Diajukan Oleh</th>
                                    <th>Detail / Jadwal</th>
                                    <th width="140">Waktu</th>
                                    <th width="140">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $i => $item)
                                    <tr
                                        data-type="{{ $item['type'] }}"
                                        data-type-label="{{ $item['type_label'] }}"
                                        data-date="{{ $item['started_at'] }}"
                                        data-search="{{ strtolower(($item['student_name'] ?? '') . ' ' . ($item['requester_name'] ?? '') . ' ' . ($item['detail'] ?? '')) }}">
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            <strong>{{ $item['student_name'] ?? '—' }}</strong>
                                        </td>
                                        <td>
                                            @if($item['type'] === 'leave')
                                                <span class="badge bg-warning-subtle text-warning"><i class="ri-home-line me-1"></i>{{ $item['type_label'] }}</span>
                                            @elseif($item['type'] === 'visit')
                                                <span class="badge bg-info-subtle text-info"><i class="ri-user-heart-line me-1"></i>{{ $item['type_label'] }}</span>
                                            @elseif($item['type'] === 'health')
                                                <span class="badge bg-success-subtle text-success"><i class="ri-hospital-line me-1"></i>{{ $item['type_label'] }}</span>
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
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-soft-success btn-sm approve-trigger"
                                                        data-id="{{ $item['id'] }}"
                                                        data-type="{{ $item['type'] }}"
                                                        data-title="{{ $item['title'] }}"
                                                        data-requester="{{ $item['requester_name'] }}"
                                                        title="Setujui">
                                                    <i class="ri-check-line"></i>
                                                </button>
                                                <button class="btn btn-soft-danger btn-sm reject-trigger"
                                                        data-id="{{ $item['id'] }}"
                                                        data-type="{{ $item['type'] }}"
                                                        data-title="{{ $item['title'] }}"
                                                        title="Tolak">
                                                    <i class="ri-close-circle-line"></i>
                                                </button>
                                            </div>
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
</div>

{{-- Approve Modal --}}
<div class="modal fade zoomIn" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approveForm" action="{{ route('user.asrama.approval-center.approve', [$userId, $asramaUuid]) }}" method="POST">
                @csrf
                <input type="hidden" name="type" id="approveType">
                <input type="hidden" name="id" id="approveId">
                <div class="modal-body">
                    <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/lupuorrc.json" trigger="loop"
                            colors="primary:#059a6c,secondary:#45ca95" style="width:100px;height:100px"></lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Setujui Permohonan?</h4>
                            <p class="text-muted mx-4 mb-0" id="approveTitle">Permohonan akan disetujui.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="ri-check-line me-1"></i>Setujui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade zoomIn" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectForm" action="{{ route('user.asrama.approval-center.reject', [$userId, $asramaUuid]) }}" method="POST">
                @csrf
                <input type="hidden" name="type" id="rejectType">
                <input type="hidden" name="id" id="rejectId">
                <div class="modal-body">
                    <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/nkmsrxys.json" trigger="loop"
                            colors="primary:#f06548,secondary:#f7b84b" style="width:100px;height:100px"></lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Tolak Permohonan?</h4>
                            <p class="text-muted mx-4 mb-0" id="rejectTitle">Permohonan akan ditolak.</p>
                        </div>
                    </div>
                    <div class="mb-3 mt-4 mx-4 mx-sm-5">
                        <label for="rejectReason" class="form-label small text-muted">Alasan Penolakan <span class="text-danger">*</span></label>
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
    var rows       = tbody ? Array.from(tbody.querySelectorAll('tr')) : [];
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

        // sort by date
        filtered.sort(function (a, b) {
            var da = new Date(a.dataset.date).getTime();
            var db = new Date(b.dataset.date).getTime();
            return currentSort === 'newest' ? db - da : da - db;
        });

        // re-render
        rows.forEach(function (r) { r.style.display = 'none'; });
        filtered.forEach(function (r, idx) {
            r.style.display = '';
            // re-number
            var firstTd = r.querySelector('td');
            if (firstTd) firstTd.textContent = idx + 1;
            tbody.appendChild(r); // reorder
        });

        if (visibleCount) {
            visibleCount.textContent = filtered.length === allRows.length
                ? ''
                : '· ' + filtered.length + ' tampil dari ' + allRows.length;
        }

        // toggle clear button
        if (clearBtn) {
            var hasActive = currentType || currentSort !== 'newest' || currentSearch;
            clearBtn.style.display = hasActive ? '' : 'none';
        }
    }

    // ── Quick Filter: Jenis -- type chips
    document.querySelectorAll('.js-filter-type').forEach(function (btn) {
        if (btn.dataset.type === '' && !currentType) btn.classList.add('active');
        btn.addEventListener('click', function () {
            currentType = this.dataset.type;
            document.querySelectorAll('.js-filter-type').forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            applyFilters();
        });
    });

    // ── Quick Filter: Sort -- newest/oldest
    document.querySelectorAll('.js-sort').forEach(function (btn) {
        if (btn.dataset.sort === 'newest') btn.classList.add('active');
        btn.addEventListener('click', function () {
            currentSort = this.dataset.sort;
            document.querySelectorAll('.js-sort').forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            applyFilters();
        });
    });

    // ── Global search
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

    // ── Clear all filters
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

    // initial render (sort by newest by default)
    applyFilters();

    // ── Approve modal binding
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

    // ── Reject modal binding
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
