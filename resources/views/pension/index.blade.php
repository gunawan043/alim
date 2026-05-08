@extends('layouts.master')
@section('title')
    Daftar Pensiun GTK
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .table-container { position: relative; width: 100%; overflow-x: auto; }
        .table-freeze { table-layout: auto; min-width: max-content; margin-bottom: 0; width: 100%; }
        .table-freeze th, .table-freeze td {
            white-space: normal; overflow: visible; text-overflow: clip;
            vertical-align: middle; padding: 12px 16px; word-break: break-word;
        }
        .table-freeze th:first-child, .table-freeze td:first-child {
            position: sticky; left: 0; z-index: 100; min-width: 180px;
            max-width: 220px; box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            white-space: normal; word-wrap: break-word;
        }
        .table-freeze thead th {
            position: sticky; top: 0; z-index: 20;
            font-weight: 600; border-bottom: 2px solid #dee2e6;
        }
        .col-hidden { display: none !important; }
        .card-animate { transition: all 0.3s ease; }
        .card-animate:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .filter-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 30px;
            font-size: 13px; transition: all 0.2s; margin: 4px; cursor: pointer;
        }
        .filter-badge:hover { background: #405189; border-color: #94a3b8; color: #fff; }
        .filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }
    </style>
@endsection

@section('content')
@php $userId = $userId ?? request()->route('userId') ?? auth()->id(); @endphp
@component('components.breadcrumb')
    @slot('li_1') GTK @endslot
    @slot('title') Daftar Pensiun @endslot
@endcomponent

{{-- STATISTICS CARDS --}}
@php
    $countAktif       = $gtkList->where('status', 'active')->count();
    $countApproaching = $gtkList->where('status', 'approaching')->count();
    $countDue         = $gtkList->where('status', 'due')->count();
    $countCompleted   = $gtkList->where('pension.pension_status', 'completed')->count();
    $countPending      = $gtkList->where('pension.pension_status', 'pending')->count();
    $countApproved     = $gtkList->where('pension.pension_status', 'approved')->count();
@endphp

<div class="row g-3 mb-3">
    <div class="col-xl-4 col-md-4">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-2">
                            <i class="bx bx-group text-primary"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total GTK</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($gtkList->count()) }}</h3>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <small class="text-muted"><i class="ri-checkbox-circle-fill text-success me-1"></i>{{ $countAktif }} Aktif</small>
                    <small class="text-muted"><i class="ri-alert-fill text-warning me-1"></i>{{ $countApproaching }} Mendekati</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-4">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle rounded fs-2">
                            <i class="bx bx-alarm-exclamation text-danger"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Sudah BUP</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($countDue) }}</h3>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <small class="text-muted"><i class="ri-checkbox-circle-fill text-warning me-1"></i>{{ $countCompleted }} Pensiun</small>
                    <small class="text-muted"><i class="ri-time-line text-info me-1"></i>{{ $countPending }} Pending</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-4">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-2">
                            <i class="bx bx-check-circle text-success"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Approved & Selesai</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $countApproved }} <small class="fw-normal text-muted">/</small> {{ $countCompleted }}</h3>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <small class="text-muted"><i class="ri-settings-3-line text-muted me-1"></i>BUP {{ $settings['bup_age'] ?? 58 }} th</small>
                    <small class="text-muted"><i class="ri-notification-3-line text-muted me-1"></i>Notif {{ $settings['notification_months'] ?? 6 }} bln</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card" id="pensionList">
            <div class="card-header border-bottom-dashed">
                <div class="row g-4 align-items-center">
                    <div class="col-sm">
                        <div>
                            <h5 class="card-title mb-0">Daftar Pensiun GTK</h5>
                            <p class="text-muted mb-0">
                                <span class="badge bg-info-subtle text-info">BUP {{ $settings['bup_age'] ?? 58 }} tahun</span>
                                <span class="badge bg-secondary-subtle text-secondary ms-1">Notifikasi {{ $settings['notification_months'] ?? 6 }} bulan</span>
                            </p>
                        </div>
                    </div>
                    <div class="col-sm-auto">
                        <div class="d-flex flex-wrap align-items-start gap-2">
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control" id="globalSearch"
                                    placeholder="Cari Nama, Jabatan..." style="width: 220px;">
                                <button type="button" class="btn btn-primary" onclick="performSearch()">
                                    <i class="ri-search-line"></i>
                                </button>
                            </div>

                            {{-- COLUMN VISIBILITY --}}
                            <div class="dropdown">
                                <button type="button" class="btn btn-soft-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-table-line align-bottom me-1"></i> Kolom
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" style="width:220px;">
                                    <h6 class="dropdown-header">Tampilkan Kolom</h6>
                                    <div class="px-3 py-2">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input column-toggle" type="checkbox" value="email" id="colEmail" checked>
                                            <label class="form-check-label" for="colEmail">Email</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input column-toggle" type="checkbox" value="no_hp" id="colNoHp">
                                            <label class="form-check-label" for="colNoHp">No HP</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input column-toggle" type="checkbox" value="tmt" id="colTmt" checked>
                                            <label class="form-check-label" for="colTmt">TMT Pensiun</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input column-toggle" type="checkbox" value="benefit" id="colBenefit">
                                            <label class="form-check-label" for="colBenefit">Dana Pensiun</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input column-toggle" type="checkbox" value="sk_no" id="colSkNo">
                                            <label class="form-check-label" for="colSkNo">No SK</label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input column-toggle" type="checkbox" value="sk_date" id="colSkDate">
                                            <label class="form-check-label" for="colSkDate">Tanggal SK</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <a href="{{ route('user.pension.settings', ['userId' => $userId]) }}"
                               class="btn btn-soft-info">
                                <i class="ri-settings-3-line align-bottom me-1"></i> Pengaturan
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- QUICK FILTER --}}
            <div class="card-header py-2 bg-light border-bottom">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="text-muted me-2"><i class="ri-flashlight-line"></i> Filter:</span>
                    <button class="filter-badge active" onclick="quickFilterPension('all')">
                        <i class="ri-checkbox-circle-line"></i> Semua
                    </button>
                    <button class="filter-badge" onclick="quickFilterPension('active')">
                        <i class="ri-user-follow-line"></i> Aktif
                    </button>
                    <button class="filter-badge" onclick="quickFilterPension('approaching')">
                        <i class="ri-time-line"></i> Mendekati
                    </button>
                    <button class="filter-badge" onclick="quickFilterPension('due')">
                        <i class="ri-alert-line"></i> Sudah BUP
                    </button>
                    <button class="filter-badge" onclick="quickFilterPension('pending')">
                        <i class="ri-time-zone-line"></i> Pending
                    </button>
                    <button class="filter-badge" onclick="quickFilterPension('completed')">
                        <i class="ri-checkbox-circle-line"></i> Selesai
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="table-container">
                    <table class="table table-hover align-middle table-freeze" id="pensionTable">
                        <thead class="table-light">
                            <tr>
                                <th data-column="nama">Nama GTK</th>
                                <th data-column="email">Email</th>
                                <th data-column="no_hp" class="col-hidden">No HP</th>
                                <th data-column="jabatan">Jabatan</th>
                                <th data-column="satuan_kerja">Satuan Kerja</th>
                                <th data-column="usia">Usia</th>
                                <th data-column="bup">BUP</th>
                                <th data-column="tmt" class="col-hidden">TMT Pensiun</th>
                                <th data-column="sisa">Sisa</th>
                                <th data-column="status_gtk">Status GTK</th>
                                <th data-column="jenis_pensiun">Jenis</th>
                                <th data-column="benefit" class="col-hidden">Dana Pensiun</th>
                                <th data-column="sk_no" class="col-hidden">No SK</th>
                                <th data-column="sk_date" class="col-hidden">Tgl SK</th>
                                <th data-column="action">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="list">
                            @forelse($gtkList as $item)
                            @php
                                $gtk = $item->user;
                                $pension = $item->pension;
                                $countdownClass = match(true) {
                                    $item->months_until_pension !== null && $item->months_until_pension <= 0 => 'text-danger fw-bold',
                                    $item->months_until_pension !== null && $item->months_until_pension <= 12 => 'text-danger',
                                    $item->months_until_pension !== null && $item->months_until_pension <= 24 => 'text-warning',
                                    default => ''
                                };
                                $sisaLabel = match(true) {
                                    $item->months_until_pension !== null && $item->months_until_pension <= 0 => 'Sudah BUP',
                                    $item->months_until_pension !== null => (int) $item->months_until_pension . ' bulan',
                                    default => '–'
                                };
                                $statusGtkClass = match($item->status) {
                                    'approaching' => 'bg-warning-subtle text-warning',
                                    'due' => 'bg-danger-subtle text-danger',
                                    'pending' => 'bg-info-subtle text-info',
                                    'approved' => 'bg-success-subtle text-success',
                                    'completed' => 'bg-secondary-subtle text-secondary',
                                    'cancelled' => 'bg-danger-subtle text-danger',
                                    default => 'bg-primary-subtle text-primary'
                                };
                                $statusGtkLabel = match($item->status) {
                                    'approaching' => 'Mendekati',
                                    'due' => 'Sudah BUP',
                                    'pending' => 'Pending',
                                    'approved' => 'Disetujui',
                                    'completed' => 'Selesai',
                                    'cancelled' => 'Batal',
                                    default => 'Aktif'
                                };
                                $jenisLabel = match($pension?->pension_type) {
                                    'normal' => 'Normal',
                                    'dini' => 'Dini',
                                    'cacat' => 'Cacat',
                                    'janda' => 'Janda/Duda',
                                    default => '–'
                                };
                                $benefitLabel = $pension?->benefit_amount
                                    ? 'Rp ' . number_format((float) $pension->benefit_amount, 0, ',', '.')
                                    : '–';
                            @endphp
                            <tr data-status="{{ $item->status }}">
                                <td data-column="nama">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-xs">
                                                <div class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                                    {{ strtoupper(substr($gtk->name, 0, 1)) }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <a href="{{ route('user.gtk.show', ['userId' => $userId, 'uuid' => $gtk->id]) }}"
                                               class="text-reset fw-semibold">{{ $gtk->name }}</a>
                                            @if($item->status === 'due')
                                                <span class="badge bg-danger-subtle text-danger ms-1" style="font-size:10px;">BUP</span>
                                            @elseif($item->status === 'approaching')
                                                <span class="badge bg-warning-subtle text-warning ms-1" style="font-size:10px;">MENDEKATI</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td data-column="email">{{ $gtk->email ?? '–' }}</td>
                                <td data-column="no_hp" class="col-hidden">{{ $gtk->gtkContact?->no_hp ?? '–' }}</td>
                                <td data-column="jabatan">{{ $gtk->employment?->jabatan ?? '–' }}</td>
                                <td data-column="satuan_kerja">{{ $gtk->workUnits?->first()?->workUnit?->name ?? '–' }}</td>
                                <td data-column="usia">{{ $item->age ? $item->age . ' th' : '–' }}</td>
                                <td data-column="bup">{{ $item->bup_age }} th</td>
                                <td data-column="tmt" class="col-hidden">
                                    {{ $item->planned_pension_date ? \Carbon\Carbon::parse($item->planned_pension_date)->format('d/m/Y') : '–' }}
                                </td>
                                <td data-column="sisa">
                                    <span class="{{ $countdownClass }}">{{ $sisaLabel }}</span>
                                </td>
                                <td data-column="status_gtk">
                                    <span class="badge {{ $statusGtkClass }}">{{ $statusGtkLabel }}</span>
                                </td>
                                <td data-column="jenis_pensiun">
                                    {{ $jenisLabel }}
                                    @if($pension && $pension->pension_status && $pension->pension_status !== 'draft')
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size:10px;">
                                            {{ match($pension->pension_status) {
                                                'pending' => 'Pending',
                                                'approved' => 'Diset.',
                                                'completed' => 'Selesai',
                                                'cancelled' => 'Batal',
                                                default => ''
                                            } }}
                                        </span>
                                    @endif
                                </td>
                                <td data-column="benefit" class="col-hidden">{{ $benefitLabel }}</td>
                                <td data-column="sk_no" class="col-hidden">{{ $pension?->pension_letter_no ?? '–' }}</td>
                                <td data-column="sk_date" class="col-hidden">
                                    {{ $pension?->pension_letter_date ? \Carbon\Carbon::parse($pension->pension_letter_date)->format('d/m/Y') : '–' }}
                                </td>
                                <td data-column="action">
                                    <a href="{{ route('user.pension.edit', ['userId' => $userId, 'uuid' => $gtk->id]) }}"
                                       class="btn btn-sm btn-soft-primary">
                                        <i class="ri-edit-2-line"></i>
                                    </a>
                                    <a href="{{ route('user.profile.cv', ['userId' => $userId, 'uuid' => $gtk->id]) }}"
                                       target="_blank"
                                       class="btn btn-sm btn-soft-secondary">
                                        <i class="ri-file-pdf-2-line"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="15" class="text-center text-muted py-4">
                                    <i class="ri-user-search-line" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                                    Tidak ada data GTK.
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
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Column visibility
    document.querySelectorAll('.column-toggle').forEach(function(cb) {
        cb.addEventListener('change', function() {
            const col = this.value;
            const show = this.checked;
            document.querySelectorAll('#pensionTable [data-column="' + col + '"]').forEach(function(el) {
                if (show) {
                    el.classList.remove('col-hidden');
                } else {
                    el.classList.add('col-hidden');
                }
            });
        });
    });

    // Global search
    window.performSearch = function() {
        const q = document.getElementById('globalSearch').value.toLowerCase();
        document.querySelectorAll('#pensionTable tbody tr').forEach(function(row) {
            const text = row.textContent.toLowerCase();
            row.style.display = (q === '' || text.includes(q)) ? '' : 'none';
        });
    };
    document.getElementById('globalSearch').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') performSearch();
    });

    // Quick filter
    window.quickFilterPension = function(status) {
        document.querySelectorAll('.filter-badge').forEach(function(b) { b.classList.remove('active'); });
        event.target.closest('.filter-badge').classList.add('active');
        document.querySelectorAll('#pensionTable tbody tr').forEach(function(row) {
            row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
        });
    };
});
</script>
@endsection
