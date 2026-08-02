@extends('layouts.master')

@section('title', 'Konfigurasi Kuota Izin')

@section('content')
<div class="container-fluid">

    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Perizinan</a> @endslot
        @slot('title') Konfigurasi Kuota @endslot
    @endcomponent

    {{-- Flash message --}}
    @if (session('success_message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success_message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="flex-fill">
                        <h4 class="card-title mb-0">Konfigurasi Kuota Izin — {{ $dormitory->name }}</h4>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <form class="d-flex flex-grow-1 flex-md-grow-0" id="policySearchForm" onsubmit="event.preventDefault(); applyFilters();">
                            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Cari jenis izin..." value="">
                            <button type="submit" class="btn btn-sm btn-outline-secondary ms-2">
                                <i class="ri-search-line"></i>
                            </button>
                        </form>
                        <button type="button" class="btn btn-primary btn-sm js-add-permit">
                            <i class="ri-add-line me-1"></i> Tambah
                        </button>
                        <form method="POST" action="{{ route('user.asrama.leave-policies.apply-defaults', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light" onclick="return confirm('Terapkan pengaturan default ke semua jenis izin?')">
                                <i class="ri-download-2-line me-1"></i> Default
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">Atur jenis izin yang aktif, kuota per periode, approval, dan pengaturan darurat untuk setiap jenis izin.</p>
                    <div id="policyFilterInfo" class="d-flex justify-content-between align-items-center mb-3 text-sm"></div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0 table-responsive" id="policyTable">
                            <style>
                                .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
                                #policyTable thead th { position: sticky; top: 0; z-index: 2; background: #fff; border-top: none; }
                                #policyTable tbody tr:hover { background-color: #f8f9fa; transition: background-color 0.15s ease; }
                                #policyTable .avatar-xs { width: 32px; height: 32px; font-size: 0.875rem; }
                            </style>
                            <thead class="table-light">
                                <tr>
                                    <th style="width:50px;" class="text-center">No</th>
                                    <th>Jenis Izin</th>
                                    <th class="text-center">Kategori</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Kuota / Bulan</th>
                                    <th class="text-center">Info Kuota Tambahan</th>
                                    <th class="text-center">Approval</th>
                                    <th class="text-center">Darurat Bypass</th>
                                    <th class="text-center" style="width:140px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="policyTableBody">
                                @php
                                    // Bangun registry dari PermitType (DB), gabung dengan policy per-asrama.
                                    // Type yang non-aktif ditampilkan dengan badge "Nonaktif" (jangan disembunyiakan
                                    // supaya user bisa lihat & aktifkan kembali).
                                    $permitsList = \App\Models\PermitType::ordered()->get();
                                    $no = 1;
                                    $categoryLabels = [
                                        'default'   => ['Default',   'secondary'],
                                        'special'   => ['Khusus',    'info'],
                                        'emergency' => ['Darurat',   'danger'],
                                        'custom'    => ['Kustom',    'primary'],
                                    ];
                                @endphp

                                @forelse ($permitsList as $permitType)
                                    @php
                                        /** @var \App\Models\PermitType $permitType */
                                        /** @var \App\Models\DormitoryLeavePolicy|null $policy */
                                        $policy = $policies->get($permitType->code);
                                        $icon = $permitType->icon ?: 'ri-file-list-3-line';
                                        $color = $permitType->color ?: 'primary';
                                        $catLabel = $categoryLabels[$permitType->category] ?? ['-', 'secondary'];
                                    @endphp
                                    @php
                                        $searchText = strtolower($permitType->label . ' ' . $permitType->code . ' ' . $catLabel[0]);
                                        $statusBadge = '';
                                        if (! $permitType->is_active) {
                                            $statusBadge = 'inactive';
                                        } elseif (! $policy || ! $policy->is_enabled) {
                                            $statusBadge = 'disabled';
                                        } else {
                                            $statusBadge = 'active';
                                        }
                                        $rowClass = $permitType->is_active ? '' : 'table-secondary';
                                    @endphp
                                    <tr class="{{ $rowClass }}" data-search-text="{{ $searchText }}" data-category="{{ $permitType->category }}" data-status="{{ $statusBadge }}" data-id="{{ $permitType->id }}">
                                        <td class="text-center"><span class="row-number">{{ $no++ }}</span></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="avatar-xs rounded-circle bg-{{ $color }}-subtle d-flex align-items-center justify-content-center">
                                                    <i class="{{ $icon }} text-{{ $color }}"></i>
                                                </span>
                                                <div>
                                                    <span class="fw-semibold d-block">{{ $permitType->label }}</span>
                                                    <small class="text-muted">{{ $permitType->code }}</small>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Kategori --}}
                                        <td class="text-center">
                                            <span class="badge bg-{{ $catLabel[1] }}-subtle text-{{ $catLabel[1] }}">{{ $catLabel[0] }}</span>
                                        </td>

                                        {{-- Status --}}
                                        <td class="text-center">
                                            @if (! $permitType->is_active)
                                                <span class="badge bg-danger-subtle text-danger">Tidak Aktif</span>
                                            @elseif (! $policy || ! $policy->is_enabled)
                                                <span class="badge bg-warning-subtle text-warning">Off (Asrama)</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                            @endif
                                        </td>

                                        {{-- Quota --}}
                                        <td class="text-center">
                                            @if ($policy && $policy->quota_per_month)
                                                <span class="badge bg-primary-subtle text-primary">{{ $policy->quota_per_month }}x</span>
                                            @elseif ($policy && $policy->quota_per_year)
                                                <span class="badge bg-primary-subtle text-primary">{{ floor($policy->quota_per_year / 12) ?? '-' }}x</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Tanpa batas</span>
                                            @endif
                                        </td>

                                        {{-- Info Kuota Tambahan --}}
                                        <td class="text-center">
                                            @if ($permitType->code === 'pulang' && $policy && $policy->pulang_quota)
                                                <span class="badge bg-primary-subtle text-primary">
                                                    {{ $policy->pulang_quota }}x
                                                    @php
                                                        $periodLabels = ['monthly' => 'bulan', 'quarterly' => 'quarter', 'semester' => 'semester', 'yearly' => 'tahun'];
                                                        $p = $policy->pulang_quota_period ?? '';
                                                    @endphp
                                                    / {{ $periodLabels[$p] ?? $p }}
                                                </span>
                                            @elseif (in_array($permitType->code, ['keluar_kota','berobat','sakit','keperluan_keluarga','lainnya'], true) && $policy)
                                                @php
                                                    $mode = $policy->special_quota_mode ?? 'none';
                                                    $modeLabels = [
                                                        'none' => ['Tanpa batas', 'secondary'],
                                                        'shared_with_pulang' => ['Ikut Pulang', 'info'],
                                                        'own_quota' => ['Kuota Sendiri', 'primary'],
                                                    ];
                                                    $info = $modeLabels[$mode] ?? $modeLabels['none'];
                                                @endphp
                                                <span class="badge bg-{{ $info[1] }}-subtle text-{{ $info[1] }}">{{ $info[0] }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        {{-- Approval --}}
                                        <td class="text-center">
                                            @if (! $policy || $policy->requires_approval)
                                                <span class="badge bg-warning-subtle text-warning">Perlu approval</span>
                                            @else
                                                <span class="badge bg-info-subtle text-info">Auto-approve</span>
                                            @endif
                                        </td>

                                        {{-- Emergency bypass --}}
                                        <td class="text-center">
                                            @if ($permitType->code === 'darurat' && $policy)
                                                @if ($policy->emergency_bypass_quota)
                                                    <span class="badge bg-primary-subtle text-primary"><i class="ri-check-line me-1"></i>Ya</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">Tidak</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        {{-- Actions --}}
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                @if ($permitType->is_active)
                                                    <a href="{{ route('user.asrama.leave-policies.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitType' => $permitType->code]) }}"
                                                       class="btn btn-sm btn-outline-primary" title="Edit Konfigurasi">
                                                        <i class="ri-pencil-line"></i>
                                                    </a>
                                                @endif

                                                {{-- Toggle aktif/nonaktif --}}
                                                <button type="button" class="btn btn-sm btn-outline-success js-toggle-active" {{ !$permitType->is_active ? 'data-active="0"' : 'data-active="1"'}} data-id="{{ $permitType->id }}" title="{{ $permitType->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                    <i class="ri-{{ $permitType->is_active ? 'toggle-fill' : 'toggle-line' }}"></i>
                                                </button>

                                                {{-- Edit master --}}
                                                <button type="button" class="btn btn-sm btn-outline-info js-edit-master" data-id="{{ $permitType->id }}" title="Edit Master Jenis Izin">
                                                    <i class="ri-database-2-line"></i>
                                                </button>

                                                {{-- Hapus --}}
                                                <button type="button" class="btn btn-sm btn-outline-danger js-delete-permit" data-id="{{ $permitType->id }}" data-label="{{ addslashes($permitType->label) }}" title="Hapus Jenis Izin">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            Belum ada jenis izin. Klik <strong>Tambah Jenis Izin</strong> untuk membuat.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('policyTableBody');
    const filterInfo = document.getElementById('policyFilterInfo');
    if (!searchInput || !tableBody) return;

    const allRows = Array.from(tableBody.querySelectorAll('tr[data-search-text]'));

    function renumberVisible() {
        let n = 1;
        allRows.forEach(function(r) {
            if (r.style.display !== 'none') {
                const numEl = r.querySelector('.row-number');
                if (numEl) numEl.textContent = n++;
            }
        });
    }

    function updateFilterInfo() {
        const visible = allRows.filter(function(r) { return r.style.display !== 'none'; }).length;
        if (filterInfo) {
            filterInfo.innerHTML = '<span class="text-muted">Menampilkan <strong>' + visible + '</strong> dari ' + allRows.length + ' jenis izin</span>';
        }
    }

    function applyFilters() {
        const q = (searchInput.value || '').trim().toLowerCase();
        allRows.forEach(function(r) {
            const text = r.getAttribute('data-search-text') || '';
            const visible = !q || text.indexOf(q) !== -1;
            r.style.display = visible ? '' : 'none';
        });
        renumberVisible();
        updateFilterInfo();
    }

    searchInput.addEventListener('input', applyFilters);
    // initialize
    renumberVisible();
    updateFilterInfo();

    window.applyFilters = applyFilters;
})();
</script>
@endpush

{{-- Include modals --}}
@include('dormitory.leave-policies.permit-type-modal') {{-- Modal tambah/edit --}}
@include('dormitory.leave-policies.permit-type-delete-modal') {{-- Modal hapus --}}
@include('dormitory.leave-policies.permit-type-success-modal') {{-- Modal sukses --}}
@include('dormitory.leave-policies.permit-type-scripts') {{-- Scripts --}}

@endsection
