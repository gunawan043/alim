@extends('layouts.master')
@section('title')
    @lang('Data Santri & Kesehatan')
@endsection
@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <style>
        .table-container { position: relative; width: 100%; overflow-x: auto; }
        .table-freeze { table-layout: auto; min-width: max-content; margin-bottom: 0; width: 100%; }
        .table-freeze th, .table-freeze td { white-space: normal; overflow: visible; text-overflow: clip; vertical-align: middle; padding: 12px 16px; word-break: break-word; }
        .table-freeze th:first-child, .table-freeze td:first-child { position: sticky; left: 0; z-index: 100; min-width: 150px; max-width: 200px; box-shadow: 2px 0 5px rgba(0,0,0,0.1); white-space: normal; word-wrap: break-word; }
        .table-freeze thead th { position: sticky; top: 0; z-index: 20; font-weight: 600; border-bottom: 2px solid #dee2e6; }
        .col-hidden { display: none !important; }
        .card-animate { transition: all 0.3s ease; }
        .card-animate:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .filter-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 30px; font-size: 13px; transition: all 0.2s; margin: 4px; cursor: pointer; }
        .filter-badge:hover { background: #405189; border-color: #94a3b8; color: #fff; }
        .filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }
        .select2-container--default .select2-selection--single { height: 38px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 5px 12px; }
        .column-visibility-dropdown { max-height: 400px; overflow-y: auto; padding: 12px; width: 260px; }
        .filter-group { background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
        .filter-group-title { font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .filter-group-title i { color: #0a5f9e; font-size: 18px; }
    </style>
@endsection

@section('content')
    @php
        $userId = request()->route('userId') ?? Auth::id();
        // Fallback: ensure list variable always exists to avoid undefined errors
        $students = $students ?? collect();
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') Data Santri & Kesehatan @endslot
        @slot('title') Santri & Kesehatan @endslot
    @endcomponent

    <!-- STATISTICS CARDS -->
    @php
        $statistics = $statistics ?? [];
        $total     = $statistics['total'] ?? 0;
        $putra     = $statistics['putra'] ?? 0;
        $putri     = $statistics['putri'] ?? 0;
        $totalGender = $putra + $putri;
        $pctL      = $totalGender > 0 ? round($putra / $totalGender * 100) : 0;
        $pctP      = $totalGender > 0 ? round($putri / $totalGender * 100) : 0;

        $bloodTypeCounts = $statistics['blood_type_counts'] ?? [];
        $totalWithBlood = $statistics['total_with_blood_type'] ?? 0;
        $pctBlood      = $total > 0 ? round($totalWithBlood / $total * 100) : 0;
    @endphp

    <div class="row g-3 mb-3">

        <!-- Total Santri -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-2">
                                <i class="bx bx-group text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Santri</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ number_format($total) }}</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-2" style="font-size:11px;">
                        <i class="ri-information-line me-1"></i>Semua Santri
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-info-subtle text-info" style="font-size:10px;">
                            L {{ number_format($putra) }} ({{ $pctL }}%)
                        </span>
                        <span class="badge bg-danger-subtle text-danger" style="font-size:10px;">
                            P {{ number_format($putri) }} ({{ $pctP }}%)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Golongan Darah -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-2">
                                <i class="ri-drop-line text-danger"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Golongan Darah Terdata</p>
                            <h3 class="ff-secondary mb-0" style="color:var(--bs-danger)">{{ number_format($totalWithBlood) }}</h3>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach (['A', 'B', 'AB', 'O'] as $bt)
                            @php $cnt = $bloodTypeCounts[$bt] ?? 0; @endphp
                            @if ($cnt > 0)
                                <span class="badge bg-danger-subtle text-danger" style="font-size:10px;">
                                    {{ $bt }} {{ number_format($cnt) }}
                                </span>
                            @endif
                        @endforeach
                        @if (empty(array_filter($bloodTypeCounts)))
                            <span class="text-muted" style="font-size:11px;">Belum ada data golongan darah</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Kelengkapan Data -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-2">
                                <i class="ri-pulse-line text-success"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Kelengkapan Data</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $pctBlood }}%</h3>
                        </div>
                    </div>
                    <div class="progress" style="height:5px;">
                        <div class="progress-bar bg-success" style="width:{{ $pctBlood }}%"></div>
                        <div class="progress-bar bg-light" style="width:{{ 100 - $pctBlood }}%"></div>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size:11px;">
                        <i class="ri-checkbox-circle-fill text-success me-1"></i>{{ $pctBlood }}% sudah isi golongan darah
                        <span class="mx-1">&middot;</span>
                        <i class="ri-close-circle-fill text-muted me-1"></i>{{ 100 - $pctBlood }}% belum
                    </small>
                </div>
            </div>
        </div>

        <!-- Aksi Cepat -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate h-100">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-2">
                                <i classri-edit-line text-warning"></i>
                            </span>
                        </div>
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Santri Belum Lengkap</p>
                            <h3 class="fw-bold ff-secondary mb-0">{{ $total - $totalWithBlood }}</h3>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">Santri tanpa data golongan darah atau jenis kelamin</p>
                    <a href="{{ route('user.uks.student-health.index', ['userId' => $userId, 'require_health_data' => 1]) }}" class="btn btn-sm btn-warning mt-3 w-100">
                        <i classri-edit-line me-1"></i> Tampilkan Langsung
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- FILTER + SEARCH BAR -->
    <div class="card mb-3">
        <div class="card-header border-bottom">
            <div class="row g-4 align-items-center">
                <div class="col-sm">
                    <h5 class="mb-0">
                        @if (isset($requireHealthFilter)) Daftar Santri yang Belum Lengkap @else Data Santri & Kesehatan @endif
                    </h5>
                    <p class="text-muted mb-0">Tabel seperti Data Santri dengan kolom kesehatan tambahan</p>
                </div>
                <div class="col-sm-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <input type="text" class="form-control" id="globalSearch"
                            placeholder="Cari Nama / NIS / Email..." value="{{ request('search') }}" style="min-width: 220px;">
                        <button type="button" class="btn btn-primary" onclick="performSearch()">
                            <i class="ri-search-line"></i> Cari
                        </button>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="bx bx-filter-alt align-bottom me-1"></i> Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACTIVE FILTERS BADGES ROW -->
        <div class="card-header py-2 bg-light border-bottom" id="activeFiltersRow" style="display: none;">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="fw-semibold me-2"><i class="ri-filter-3-line"></i> Filter Aktif:</span>
                <div id="activeFilterBadges" class="d-flex flex-wrap gap-2"></div>
                <button class="btn btn-sm btn-link text-danger ms-auto" onclick="clearAllFilters()">
                    <i class="ri-close-circle-line me-1"></i> Hapus Semua
                </button>
            </div>
        </div>

        <!-- QUICK FILTER -->
        <div class="card-header py-2 bg-light border-bottom">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted me-2"><i class="ri-flashlight-line"></i> Quick Filter:</span>
                <button class="filter-badge" onclick="quickFilter('gender', 'L')">
                    <i class="ri-men-line"></i> Putra
                </button>
                <button class="filter-badge" onclick="quickFilter('gender', 'P')">
                    <i class="ri-women-line"></i> Putri
                </button>
                <button class="filter-badge" onclick="quickFilter('golongan_darah', 'A')">Gol. A</button>
                <button class="filter-badge" onclick="quickFilter('golongan_darah', 'B')">Gol. B</button>
                <button class="filter-badge" onclick="quickFilter('golongan_darah', 'AB')">Gol. AB</button>
                <button class="filter-badge" onclick="quickFilter('golongan_darah', 'O')">Gol. O</button>
                <button class="filter-badge" onclick="quickFilter('has_health_data', '1')">
                    <i class="ri-check-circle-line me-1"></i> Sudah Lengkap
                </button>
                <button class="filter-badge" onclick="quickFilter('has_health_data', '0')">
                    <i class="ri-alert-circle-line me-1"></i> Belum Lengkap
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover align-middle table-freeze" id="studentHealthTable">
                    <thead class="table-light">
                        <tr>
                            <!-- DEFAULT KOLOM (7) -->
                            <th data-column="nama">Nama Santri</th>
                            <th data-column="nis">NIS</th>
                            <th data-column="gender">JK</th>
                            <th data-column="satuan_kerja">Kelas/Asrama</th>

                            <!-- NON-DEFAULT: Data Pribadi -->
                            <th data-column="tempat_lahir" class="col-hidden">Tempat Lahir</th>
                            <th data-column="tanggal_lahir" class="col-hidden">Tanggal Lahir</th>
                            <!-- Kolom Kesehatan: Golongan Darah -->
                            <th data-column="golongan_darah" class="col-hidden">Gol. Darah</th>
                            <th data-column="status_kesehatan" class="col-hidden">Status Kesehatan</th>
                            <th data-column="alergi" class="col-hidden">Alergi</th>
                            <th data-column="contact_darurat" class="col-hidden">Kontak Darurat</th>

                            <!-- NON-DEFAULT: Akademik -->
                            <th data-column="kelas" class="col-hidden">Kelas</th>
                            <th data-column="asrama" class="col-hidden">Asrama</th>

                            <!-- Action Kolom Selalu Visible -->
                            <th data-column="action">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="list">
                        @forelse($students as $student)
                            @php
                                $profile = $student->gtkProfile ?? null;
                                $health = $student->healthRecord ?? null;
                                $hasBloodType = !empty($health->blood_type) && $health->blood_type != 'tidak_diketahui';
                                $hasJK = !empty($student->gender);
                                $hasHealthData = $hasBloodType || $hasJK;
                            @endphp
                            <tr>
                                <!-- Nama Santri -->
                                <td data-column="nama">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-xs">
                                                <div class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                                    {{ $student->nama ? strtoupper(substr($student->nama, 0, 1)) : 'S' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <a href="{{ route('user.uks.student-health.profile', ['userId' => $userId, 'studentUuid' => $student->id]) }}" class="text-reset fw-semibold">
                                                {{ $student->nama ?? $student->name ?? '?' }}
                                            </a>
                                        </div>
                                    </div>
                                </td>

                                <!-- NIS -->
                                <td data-column="nis">{{ $student->nis ?? '-' }}</td>

                                <!-- Jenis Kelamin -->
                                <td data-column="gender">
                                    @if($student->gender)
                                        <span class="badge bg-{{ $student->gender == 'L' ? 'primary' : 'danger' }}-subtle text-{{ $student->gender == 'L' ? 'primary' : 'danger' }}">
                                            {{ $student->gender == 'L' ? '♂ Putra' : '♀ Putri' }}
                                        </span>
                                    @else - @endif
                                </td>

                                <!-- Kelas/Asrama -->
                                <td data-column="satuan_kerja">
                                    @if($student->currentClassHistory && $student->currentClassHistory->studyGroup)
                                        {{ $student->currentClassHistory->studyGroup->full_name ?? '-' }}
                                    @elseif($student->dormitoryResident && $student->dormitoryResident->dormitory)
                                        {{ $student->dormitoryResident->dormitory->name ?? '-' }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <!-- Tempat Lahir -->
                                <td data-column="tempat_lahir" class="col-hidden">{{ $student->tempat_lahir ?? '-' }}</td>

                                <!-- Tanggal Lahir -->
                                <td data-column="tanggal_lahir" class="col-hidden">
                                    {{ $student->tanggal_lahir ? \Carbon\Carbon::parse($student->tanggal_lahir)->format('d/m/Y') : '-' }}
                                </td>

                                <!-- Golongan Darah (Kesehatan) -->
                                <td data-column="golongan_darah" class="col-hidden">
                                    @if($health && $health->blood_type && $health->blood_type != 'tidak_diketahui')
                                        <span class="badge bg-danger-subtle text-danger">
                                            <i class="ri-drop-line me-1"></i>{{ $health->blood_type }}+
                                        </span>
                                    @elseif($profile && $profile->golongan_darah && $profile->golongan_darah != 'tidak_diketahui')
                                        <span class="badge bg-danger-subtle text-danger">
                                            <i class="ri-drop-line me-1"></i>{{ $profile->golongan_darah }}+
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- Status Kesehatan -->
                                <td data-column="status_kesehatan" class="col-hidden">
                                    @if($hasHealthData)
                                        <span class="badge bg-success-subtle text-success">Ada data</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">Belum ada</span>
                                    @endif
                                </td>

                                <!-- Alergi -->
                                <td data-column="alergi" class="col-hidden">
                                    {{ ($health && $health->allergies) ? $health->allergies : ($profile && $profile->alergi ? $profile->alergi : '-') }}
                                </td>

                                <!-- Kontak Darurat -->
                                <td data-column="contact_darurat" class="col-hidden">
                                    {{ ($health && $health->emergency_contact_name) ? $health->emergency_contact_name . ' (' . $health->emergency_contact_phone . ')' : ($profile && $profile->kontak_darurat_nama ? $profile->kontak_darurat_nama : '-') }}
                                </td>

                                <!-- Kelas -->
                                <td data-column="kelas" class="col-hidden">
                                    {{ $student->currentClassHistory?->studyGroup?->full_name ?? '-' }}
                                </td>

                                <!-- Asrama -->
                                <td data-column="asrama" class="col-hidden">
                                    {{ $student->dormitoryResident?->dormitory?->name ?? '-' }}
                                </td>

                                <!-- Aksi -->
                                <td data-column="action">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-soft-secondary" type="button" data-bs-toggle="dropdown">
                                            <i class="ri-more-2-fill"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.uks.student-health.profile', ['userId' => $userId, 'studentUuid' => $student->id]) }}">
                                                    <i class="ri-eye-fill text-info me-2"></i> Lihat Profil Kesehatan
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('user.uks.patients.create', ['userId' => $userId, 'student_id' => $student->id]) }}">
                                                    <i class="ri-plus-circle-line text-primary me-2"></i> Catat Kunjungan Baru
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                @unless($isDormitoryUser ?? false)
                                                    <a class="dropdown-item" href="{{ route('user.students.edit', ['userId' => $userId, 'santriUuid' => $student->id]) }}">
                                                        <i class="ri-pencil-fill text-warning me-1"></i> Edit Data Santri
                                                    </a>
                                                @endunless
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-5">
                                    <div class="avatar-sm mx-auto mb-3">
                                        <span class="avatar-title bg-info-subtle rounded fs-3">
                                            <i class="ri-user-line text-info"></i>
                                        </span>
                                    </div>
                                    <p class="text-muted mb-0">{{ isset($requireHealthFilter) ? 'Tidak ada santri yang memenuhi kriteria' : 'Belum ada data santri' }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($students instanceof \Illuminate\Pagination\LengthAwarePaginator && $students->hasPages())
                @include('shared._pagination', ['paginator' => $students])
            @endif
        </div>
    </div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
let currentDeleteUuid = null;
let activeFilters = {};
const DEFAULT_COLUMNS = ['nama', 'nis', 'email', 'no_hp', 'gender', 'jabatan', 'satuan_kerja'];

document.addEventListener('DOMContentLoaded', function () {
    localStorage.removeItem('student_health_column_visibility');
    syncCheckboxWithDOM();
    loadActiveFiltersFromUrl();
    setupEventListeners();
});

function syncCheckboxWithDOM() {
    document.querySelectorAll('.column-toggle').forEach(cb => {
        const column = cb.value;
        const th = document.querySelector(`th[data-column="${column}"]`);
        if (th) cb.checked = !th.classList.contains('col-hidden');
    });
}

function toggleColumnVisibility(checkbox) {
    const column = checkbox.value;
    const isChecked = checkbox.checked;
    document.querySelectorAll(`th[data-column="${column}"], td[data-column="${column}"]`).forEach(el => {
        isChecked ? el.classList.remove('col-hidden') : el.classList.add('col-hidden');
    });
}

function resetColumnVisibility() {
    document.querySelectorAll('.column-toggle').forEach(cb => {
        const column = cb.value;
        const isDefault = DEFAULT_COLUMNS.includes(column);
        cb.checked = isDefault;
        document.querySelectorAll(`th[data-column="${column}"], td[data-column="${column}"]`).forEach(el => {
            isDefault ? el.classList.remove('col-hidden') : el.classList.add('col-hidden');
        });
    });
    Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Kolomi direset ke default', timer: 1500, showConfirmButton: false });
}

function performSearch() {
    const searchValue = document.getElementById('globalSearch').value;
    let params = new URLSearchParams(window.location.search);
    if (searchValue) params.set('search', searchValue); else params.delete('search');
    params.delete('page');
    window.location.href = '{{ route('user.uks.student-health.index', ['userId' => $userId]) }}?' + params.toString();
}

function loadActiveFiltersFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    let filterCount = 0;
    urlParams.forEach((value, key) => {
        if (value && key !== 'page' && key !== 'sort' && key !== 'direction') {
            activeFilters[key] = value;
            filterCount++;
        }
    });
    const countEl = document.getElementById('activeFilterCount');
    const rowEl = document.getElementById('activeFiltersRow');
    if (filterCount > 0) {
        if (countEl) { countEl.style.display = 'inline'; countEl.innerText = filterCount; }
        if (rowEl) rowEl.style.display = 'block';
        renderActiveFilterBadges();
    } else {
        if (countEl) countEl.style.display = 'none';
        if (rowEl) rowEl.style.display = 'none';
    }
}

function applyFilters() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    let params = new URLSearchParams();
    for (let [key, value] of formData) {
        if (value && value.trim() !== '') params.append(key, value.trim());
    }
    const searchValue = document.getElementById('globalSearch').value;
    if (searchValue) params.set('search', searchValue);
    window.location.href = '{{ route('user.uks.student-health.index', ['userId' => $userId]) }}?' + params.toString();
}

function resetFilters() {
    document.getElementById('filterForm').reset();
    document.getElementById('globalSearch').value = '';
    window.location.href = '{{ route('user.uks.student-health.index', ['userId' => $userId]) }}';
}

function quickFilter(field, value) {
    let params = new URLSearchParams(window.location.search);
    if (params.get(field) === value) params.delete(field); else params.set(field, value);
    params.delete('page');
    window.location.href = '{{ route('user.uks.student-health.index', ['userId' => $userId]) }}?' + params.toString();
}

function clearAllFilters() {
    window.location.href = '{{ route('user.uks.student-health.index', ['userId' => $userId]) }}';
}

function removeFilter(key) {
    let params = new URLSearchParams(window.location.search);
    params.delete(key);
    window.location.href = '{{ route('user.uks.student-health.index', ['userId' => $userId]) }}?' + params.toString();
}

function renderActiveFilterBadges() {
    const container = document.getElementById('activeFilterBadges');
    if (!container) return;
    container.innerHTML = '';
    Object.keys(activeFilters).forEach(key => {
        if (!activeFilters[key] || key === 'page' || key === 'sort' || key === 'direction') return;
        let displayValue = activeFilters[key];
        if (key === 'gender') displayValue = displayValue === 'L' ? 'Putra' : 'Putri';
        if (key === 'golongan_darah') displayValue = 'Gol. ' + displayValue;
        if (key === 'has_health_data') displayValue = displayValue === '1' ? 'Sudah' : 'Belum';
        const badge = document.createElement('span');
        badge.className = 'filter-badge active';
        badge.innerHTML = `<i class="ri-filter-3-line me-1"></i>${getFilterLabel(key)}: ${displayValue}
            <span class="remove-filter" onclick="removeFilter('${key}')"><i class="ri-close-line"></i></span>`;
        container.appendChild(badge);
    });
}

function getFilterLabel(key) {
    const labels = { search:'Pencarian', gender:'JK', golongan_darah:'Gol. Darah', has_health_data:'Kelengkapan' };
    return labels[key] || key;
}

function setupEventListeners() {
    document.querySelectorAll('.column-toggle').forEach(cb => cb.addEventListener('change', function () { toggleColumnVisibility(this); }));
}
</script>
@endsection