@extends('layouts.master')

@section('title', 'Konfigurasi Izin & Kunjungan')

@section('content')

    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('li_3') <a href="{{ route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">Perizinan</a> @endslot
        @slot('title') Pengaturan Perizinan @endslot
    @endcomponent

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if (session('success_message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i> {{ session('success_message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>
            <strong>Periksa kembali pengaturan:</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @php
        $permitTypes = \App\Models\PermitType::ordered()->get();
        $activeTypeCount = $permitTypes->where('is_active', true)->count();
        $enabledCount = 0;
        $approvalCount = 0;
        $limitedCount = 0;

        foreach ($permitTypes as $type) {
            $policy = $policies->get($type->code);
            if (! $type->is_active || ! $policy || ! $policy->is_enabled) {
                continue;
            }
            $enabledCount++;
            if ($policy->requires_approval || in_array($type->category, ['special', 'emergency'], true)) {
                $approvalCount++;
            }
            if ($policy->quotaSummary()['quota'] !== null) {
                $limitedCount++;
            }
        }

        $visitStrategy = $visitPolicy->visit_strategy ?? 'unrestricted';
        $visitStrategyLabel = match ($visitStrategy) {
            'quota' => 'Berbasis kuota',
            'banned' => 'Dinonaktifkan',
            default => 'Tidak terbatas',
        };
        $visitBadge = match ($visitStrategy) {
            'quota' => 'success',
            'banned' => 'danger',
            default => 'info',
        };
    @endphp

    {{-- Ringkasan kebijakan asrama (gaya kartu GTK) --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded">
                                <i class="ri-file-list-3-line text-primary"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Jenis Izin</p>
                            <h3 class="stat-value">{{ number_format($permitTypes->count()) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>{{ $activeTypeCount }} aktif di master</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded">
                                <i class="ri-checkbox-circle-line text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Aktif di Asrama</p>
                            <h3 class="stat-value">{{ number_format($enabledCount) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-home-4-line me-1"></i>{{ $dormitory->name }}</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded">
                                <i class="ri-shield-check-line text-warning"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Perlu Approval</p>
                            <h3 class="stat-value">{{ number_format($approvalCount) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-bar-chart-line me-1"></i>{{ $limitedCount }} jenis berkuota</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded">
                                <i class="ri-group-line text-info"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Kebijakan Kunjungan</p>
                            <h3 class="stat-value" style="font-size:1.35rem;">{{ $visitStrategyLabel }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0">
                        <span class="badge bg-{{ $visitBadge }}-subtle text-{{ $visitBadge }}">
                            {{ $visitPolicy->visit_quota ?? '∞' }} / {{ $visitPolicy->visit_quota_period ?? 'tanpa periode' }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-3 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">Konfigurasi Perizinan</h5>
                            <p class="text-muted mb-0 small">Atur jenis izin, kuota, approval, dan kebijakan kunjungan asrama.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="toolbar-actions">
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control form-control-sm" id="searchInput"
                                           placeholder="Cari jenis izin..." value="{{ request('search') }}" style="width:220px;">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="applyFilters()">
                                        <i class="ri-search-line"></i> Cari
                                    </button>
                                </div>

                                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#policyFilterModal">
                                    <i class="ri-filter-3-line me-1"></i> Filter
                                    <span id="policyFilterCount" class="badge bg-light text-dark ms-1" style="display:none;">0</span>
                                </button>

                                @if ($canManageQuota)
                                    <button type="button" class="btn btn-warning btn-sm js-add-permit">
                                        <i class="ri-add-line me-1"></i> Tambah Jenis
                                    </button>
                                    <form method="POST" action="{{ route('user.asrama.leave-policies.apply-defaults', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-light btn-sm" onclick="return confirm('Terapkan pengaturan default ke semua jenis izin?')">
                                            <i class="ri-download-2-line me-1"></i> Default
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div id="policyActiveFilters" class="mb-3"></div>
                    <div id="policyFilterInfo" class="d-flex justify-content-between align-items-center mb-3 small text-muted"></div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-freeze mb-0" id="policyTable">
                            <thead>
                                <tr>
                                    <th style="width:52px;" class="text-center">No</th>
                                    <th>Jenis Izin</th>
                                    <th class="text-center">Kategori</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Kuota Efektif</th>
                                    <th class="text-center">Approval</th>
                                    <th class="text-center" style="width:150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="policyTableBody">
                                @forelse ($permitTypes as $permitType)
                                    @php
                                        /** @var \App\Models\PermitType $permitType */
                                        /** @var \App\Models\DormitoryLeavePolicy|null $policy */
                                        $policy = $policies->get($permitType->code);
                                        $icon = $permitType->icon ?: 'ri-file-list-3-line';
                                        $color = $permitType->color ?: 'primary';
                                        $categoryLabels = [
                                            'default' => ['Default', 'secondary'],
                                            'special' => ['Khusus', 'info'],
                                            'emergency' => ['Darurat', 'danger'],
                                            'custom' => ['Kustom', 'primary'],
                                        ];
                                        $catLabel = $categoryLabels[$permitType->category] ?? ['-', 'secondary'];
                                        $statusBadge = ! $permitType->is_active ? 'inactive' : ((! $policy || ! $policy->is_enabled) ? 'disabled' : 'active');
                                        $quotaSummary = $policy?->quotaSummary();
                                        $isShared = $policy && $policy->special_quota_mode === 'shared_with_pulang';
                                        $requiresApproval = $policy?->requires_approval ?? true;
                                        $quotaValue = $isShared ? 'shared' : (($quotaSummary['quota'] ?? null) !== null ? 'limited' : 'unlimited');
                                        $searchText = strtolower($permitType->label.' '.$permitType->code.' '.$catLabel[0].' '.$statusBadge.' '.($quotaSummary['label'] ?? 'tanpa batas'));
                                    @endphp
                                    <tr data-search-text="{{ $searchText }}"
                                        data-category="{{ $permitType->category }}"
                                        data-status="{{ $statusBadge }}"
                                        data-quota="{{ $quotaValue }}"
                                        data-approval="{{ $requiresApproval ? 'required' : 'auto' }}">
                                        <td class="text-center"><span class="row-number text-muted">{{ $loop->iteration }}</span></td>
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
                                        <td class="text-center">
                                            <span class="badge bg-{{ $catLabel[1] }}-subtle text-{{ $catLabel[1] }}">{{ $catLabel[0] }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if ($statusBadge === 'inactive')
                                                <span class="badge bg-danger-subtle text-danger">Nonaktif Master</span>
                                            @elseif ($statusBadge === 'disabled')
                                                <span class="badge bg-warning-subtle text-warning">Off di Asrama</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($isShared)
                                                <span class="badge bg-info-subtle text-info">Ikut Kuota Pulang</span>
                                            @elseif (($quotaSummary['quota'] ?? null) !== null)
                                                <span class="badge bg-primary-subtle text-primary">{{ $quotaSummary['label'] }}</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Tanpa batas</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($requiresApproval)
                                                <span class="badge bg-warning-subtle text-warning">Perlu approval</span>
                                            @else
                                                <span class="badge bg-info-subtle text-info">Auto-approve</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($canManageQuota)
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if ($permitType->is_active)
                                                        <a href="{{ route('user.asrama.leave-policies.show', ['userId' => $userId, 'asramaUuid' => $dormitory->id, 'permitType' => $permitType->code]) }}"
                                                           class="btn btn-sm btn-outline-primary" title="Edit Konfigurasi">
                                                            <i class="ri-pencil-line"></i>
                                                        </a>
                                                    @endif
                                                    <button type="button" class="btn btn-sm btn-outline-success js-toggle-active"
                                                            data-active="{{ $permitType->is_active ? '1' : '0' }}"
                                                            data-id="{{ $permitType->id }}"
                                                            title="{{ $permitType->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                        <i class="ri-{{ $permitType->is_active ? 'toggle-fill' : 'toggle-line' }}"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info js-edit-master"
                                                            data-id="{{ $permitType->id }}" title="Edit Master Jenis Izin">
                                                        <i class="ri-database-2-line"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger js-delete-permit"
                                                            data-id="{{ $permitType->id }}" data-label="{{ $permitType->label }}" title="Hapus Jenis Izin">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-muted small">Hanya lihat</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            Belum ada jenis izin. Klik <strong>Tambah Jenis</strong> untuk membuat.
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

    {{-- Kebijakan kunjungan per asrama --}}
    <div class="card mt-3">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="mb-0"><i class="ri-group-line me-2 text-primary"></i>Kebijakan Kunjungan Keluarga</h6>
                <small class="text-muted">Status kunjungan mengikuti posisi santri: belum keluar → sedang dalam kunjungan → kunjungan selesai.</small>
            </div>
            <span class="badge bg-{{ $visitBadge }}-subtle text-{{ $visitBadge }}">{{ $visitStrategyLabel }}</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('user.asrama.leave-policies.visit', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Strategi</label>
                        <select name="visit_strategy" class="form-select" {{ $canManageQuota ? '' : 'disabled' }}>
                            @foreach(['unrestricted' => 'Tidak Terbatas', 'quota' => 'Berbasis Kuota', 'banned' => 'Dinonaktifkan'] as $value => $label)
                                <option value="{{ $value }}" {{ old('visit_strategy', $visitStrategy) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Kuota</label>
                        <input type="number" name="visit_quota" min="1" class="form-control" value="{{ old('visit_quota', $visitPolicy->visit_quota ?? '') }}" placeholder="mis. 2" {{ $canManageQuota ? '' : 'disabled' }}>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Periode Kuota</label>
                        <select name="visit_quota_period" class="form-select" {{ $canManageQuota ? '' : 'disabled' }}>
                            @foreach(['weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'semester' => 'Semester', 'yearly' => 'Tahunan'] as $value => $label)
                                <option value="{{ $value }}" {{ old('visit_quota_period', $visitPolicy->visit_quota_period ?? 'monthly') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Maks. Pengunjung</label>
                        <input type="number" name="max_visitors_per_visit" min="1" max="100" class="form-control" value="{{ old('max_visitors_per_visit', $visitPolicy->max_visitors_per_visit ?? '') }}" placeholder="mis. 5" {{ $canManageQuota ? '' : 'disabled' }}>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        @if ($canManageQuota)
                            <button class="btn btn-primary w-100"><i class="ri-save-line me-1"></i>Simpan</button>
                        @else
                            <span class="text-muted small">Hanya Kepala/Wakil Asrama yang dapat mengubah.</span>
                        @endif
                    </div>
                </div>
                <div class="form-text mt-2">
                    Berlaku untuk pengajuan kunjungan baru. Kuota dihitung satu kali per kunjungan, bukan per scan.
                </div>
            </form>
        </div>
    </div>

    {{-- Consent PDP wali per asrama --}}
    <div class="card mt-3">
        <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="mb-0"><i class="ri-shield-user-line me-2 text-primary"></i>Persetujuan Data Wali (PDP)</h6>
                <small class="text-muted">Atur apakah consent wali wajib dicatat sebelum kunjungan/izin diajukan.</small>
            </div>
            <span class="badge bg-{{ $effectiveConsentRequired ? 'warning' : 'success' }}-subtle text-{{ $effectiveConsentRequired ? 'warning' : 'success' }}">
                {{ $effectiveConsentRequired ? 'Wajib consent' : 'Tanpa gate consent' }}
            </span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('user.asrama.leave-policies.consent', ['userId' => $userId, 'asramaUuid' => $dormitory->id]) }}">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label">Kebijakan consent untuk asrama ini</label>
                        <select name="guardian_consent_required" class="form-select" {{ $canManageQuota ? '' : 'disabled' }}>
                            <option value="" {{ old('guardian_consent_required', $consentOverride === null ? '' : (string) (int) $consentOverride) === '' ? 'selected' : '' }}>
                                Ikuti pengaturan sistem ({{ \App\Support\AsramaConfig::consentRequired() ? 'Wajib' : 'Tidak wajib' }})
                            </option>
                            <option value="1" {{ old('guardian_consent_required', $consentOverride === null ? '' : (string) (int) $consentOverride) === '1' ? 'selected' : '' }}>Wajibkan consent</option>
                            <option value="0" {{ old('guardian_consent_required', $consentOverride === null ? '' : (string) (int) $consentOverride) === '0' ? 'selected' : '' }}>Tidak wajibkan consent</option>
                        </select>
                        <div class="form-text">
                            Pilih <strong>Tidak wajibkan consent</strong> untuk sementara bila pencatatan consent
                            belum siap. Data consent yang sudah ada tetap tersimpan.
                        </div>
                    </div>
                    <div class="col-md-4">
                        @if ($canManageQuota)
                            <button class="btn btn-primary w-100"><i class="ri-save-line me-1"></i>Simpan Pengaturan</button>
                        @else
                            <span class="text-muted small">Hanya Kepala/Wakil Asrama yang dapat mengubah.</span>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

{{-- Filter modal ala GTK --}}
<div class="modal fade" id="policyFilterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-filter-3-line me-2 text-primary"></i>Filter Jenis Izin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="filter-group mb-0">
                            <div class="filter-group-title"><i class="ri-price-tag-3-line"></i>Kategori</div>
                            <select id="filterCategory" class="form-select">
                                <option value="">Semua kategori</option>
                                <option value="default">Default</option>
                                <option value="special">Khusus</option>
                                <option value="emergency">Darurat</option>
                                <option value="custom">Kustom</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-group mb-0">
                            <div class="filter-group-title"><i class="ri-toggle-line"></i>Status</div>
                            <select id="filterStatus" class="form-select">
                                <option value="">Semua status</option>
                                <option value="active">Aktif di asrama</option>
                                <option value="disabled">Off di asrama</option>
                                <option value="inactive">Nonaktif master</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-group mb-0">
                            <div class="filter-group-title"><i class="ri-bar-chart-line"></i>Kuota</div>
                            <select id="filterQuota" class="form-select">
                                <option value="">Semua kuota</option>
                                <option value="limited">Berkuota</option>
                                <option value="unlimited">Tanpa batas</option>
                                <option value="shared">Ikut kuota pulang</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-group mb-0">
                            <div class="filter-group-title"><i class="ri-shield-check-line"></i>Approval</div>
                            <select id="filterApproval" class="form-select">
                                <option value="">Semua</option>
                                <option value="required">Perlu approval</option>
                                <option value="auto">Auto-approve</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" onclick="resetPolicyFilters()"><i class="ri-refresh-line me-1"></i>Reset</button>
                <button type="button" class="btn btn-primary" onclick="applyFilters()"><i class="ri-filter-3-line me-1"></i>Terapkan</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('policyTableBody');
    const filterInfo = document.getElementById('policyFilterInfo');
    const filterCount = document.getElementById('policyFilterCount');
    const activeFilterBox = document.getElementById('policyActiveFilters');
    if (!searchInput || !tableBody) return;

    const allRows = Array.from(tableBody.querySelectorAll('tr[data-search-text]'));

    const filterLabels = {
        filterCategory: {
            default: 'Kategori: Default',
            special: 'Kategori: Khusus',
            emergency: 'Kategori: Darurat',
            custom: 'Kategori: Kustom',
        },
        filterStatus: {
            active: 'Status: Aktif',
            disabled: 'Status: Off di asrama',
            inactive: 'Status: Nonaktif master',
        },
        filterQuota: {
            limited: 'Kuota: Berkuota',
            unlimited: 'Kuota: Tanpa batas',
            shared: 'Kuota: Ikut pulang',
        },
        filterApproval: {
            required: 'Approval: Perlu approval',
            auto: 'Approval: Auto-approve',
        },
    };

    function renderActiveFilterBadges() {
        if (!activeFilterBox) return;
        activeFilterBox.innerHTML = '';

        Object.keys(filterLabels).forEach(function (id) {
            const element = document.getElementById(id);
            const value = element ? element.value : '';
            if (!value || !filterLabels[id][value]) return;

            const badge = document.createElement('span');
            badge.className = 'filter-badge active';
            badge.innerHTML = filterLabels[id][value] + ' <span class="remove-filter" aria-hidden="true">&times;</span>';
            badge.querySelector('.remove-filter').addEventListener('click', function () {
                element.value = '';
                window.applyFilters();
            });
            activeFilterBox.appendChild(badge);
        });

        if ((searchInput.value || '').trim() !== '') {
            const searchBadge = document.createElement('span');
            searchBadge.className = 'filter-badge active';
            searchBadge.innerHTML = 'Pencarian: "' + searchInput.value + '" <span class="remove-filter" aria-hidden="true">&times;</span>';
            searchBadge.querySelector('.remove-filter').addEventListener('click', function () {
                searchInput.value = '';
                window.applyFilters();
            });
            activeFilterBox.appendChild(searchBadge);
        }
    }

    function renumberVisible() {
        let n = 1;
        allRows.forEach(function (row) {
            if (row.style.display !== 'none') {
                const number = row.querySelector('.row-number');
                if (number) number.textContent = n++;
            }
        });
    }

    function updateFilterInfo() {
        const visible = allRows.filter(function (row) { return row.style.display !== 'none'; }).length;
        if (filterInfo) {
            filterInfo.innerHTML = '<span>Menampilkan <strong>' + visible + '</strong> dari ' + allRows.length + ' jenis izin</span>';
        }
        const filters = [
            document.getElementById('filterCategory')?.value,
            document.getElementById('filterStatus')?.value,
            document.getElementById('filterQuota')?.value,
            document.getElementById('filterApproval')?.value,
        ].filter(Boolean).length;
        if (filterCount) {
            filterCount.textContent = filters;
            filterCount.style.display = filters || (searchInput.value || '').trim() ? '' : 'none';
        }

        renderActiveFilterBadges();
    }

    window.applyFilters = function () {
        const query = (searchInput.value || '').trim().toLowerCase();
        const category = document.getElementById('filterCategory')?.value || '';
        const status = document.getElementById('filterStatus')?.value || '';
        const quota = document.getElementById('filterQuota')?.value || '';
        const approval = document.getElementById('filterApproval')?.value || '';

        allRows.forEach(function (row) {
            const matchSearch = !query || (row.dataset.searchText || '').includes(query);
            const matchCategory = !category || row.dataset.category === category;
            const matchStatus = !status || row.dataset.status === status;
            const matchQuota = !quota || row.dataset.quota === quota;
            const matchApproval = !approval || row.dataset.approval === approval;
            row.style.display = (matchSearch && matchCategory && matchStatus && matchQuota && matchApproval) ? '' : 'none';
        });

        renumberVisible();
        updateFilterInfo();

        const modal = bootstrap.Modal.getInstance(document.getElementById('policyFilterModal'));
        if (modal) modal.hide();
    };

    window.resetPolicyFilters = function () {
        ['filterCategory', 'filterStatus', 'filterQuota', 'filterApproval'].forEach(function (id) {
            const element = document.getElementById(id);
            if (element) element.value = '';
        });
        searchInput.value = '';
        window.applyFilters();
    };

    searchInput.addEventListener('input', window.applyFilters);
    updateFilterInfo();
    renderActiveFilterBadges();
})();
</script>
@endpush

@include('dormitory.leave-policies.permit-type-modal')
@include('dormitory.leave-policies.permit-type-delete-modal')
@include('dormitory.leave-policies.permit-type-success-modal')
@include('dormitory.leave-policies.permit-type-scripts')
@endsection
