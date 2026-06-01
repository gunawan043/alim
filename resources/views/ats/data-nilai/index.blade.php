@extends('layouts.master')
@section('title') Data Nilai Pelamar @endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .card-animate { transition: all 0.3s ease; }
    .card-animate:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 5px 12px;
    }
    .filter-group { background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
    .filter-group-title { font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
    .filter-group-title i { color: #0a5f9e; font-size: 18px; }
    .table-nilai th, .table-nilai td { vertical-align: middle; padding: 10px 12px; }
    .table-nilai thead th {
        background: #f8fafc;
        font-weight: 600;
        border-bottom: 2px solid #e2e8f0;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .table-nilai tbody tr:hover { background: #f1f5f9; }
    .nilai-cell { font-weight: 600; }
    .nilai-high { color: #0a8c5a; }
    .nilai-mid  { color: #b45309; }
    .nilai-low  { color: #b91c1c; }
    @media print { .no-print { display: none !important; } }
</style>
@endsection

@section('content')
@php
    $userId = request()->route('userId') ?? auth()->id();
    $activeTab = request('recruitment_status', 'aktif');

    // Hitung rasio kelulusan untuk progress bar
    $totalDinilai = ($stats['sudah_dinilai'] ?? 0) + ($stats['belum_dinilai'] ?? 0);
    $pctSudahDinilai = $totalDinilai > 0 ? round($stats['sudah_dinilai'] / $totalDinilai * 100) : 0;
    $pctLulus = $stats['sudah_dinilai'] > 0 ? round($stats['lulus_seleksi'] / $stats['sudah_dinilai'] * 100) : 0;
@endphp

@component('components.breadcrumb')
    @slot('li_1') Personalia @endslot
    @slot('title') Data Nilai Pelamar @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-check-line me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ===================== STATS CARDS ===================== --}}
<div class="row g-3 mb-3">

    {{-- 1. Total Pelamar --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-2">
                            <i class="ri-group-line text-primary"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Pelamar</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['total_pelamar']) }}</h3>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <small class="text-muted"><i class="ri-play-circle-fill text-success me-1"></i>{{ number_format($stats['pelamar_aktif']) }} Aktif</small>
                    <small class="text-muted"><i class="ri-archive-line text-secondary me-1"></i>{{ number_format($stats['pelamar_arsip']) }} Arsip</small>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Progress Penilaian --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-2">
                            <i class="ri-checkbox-circle-line text-warning"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Progress Penilaian</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['sudah_dinilai']) }} <small class="fw-normal text-muted">/ {{ $totalDinilai }}</small></h3>
                    </div>
                </div>
                <div class="progress" style="height:6px;">
                    <div class="progress-bar bg-warning" style="width:{{ $pctSudahDinilai }}%"></div>
                </div>
                <small class="text-muted">{{ $pctSudahDinilai }}% sudah dinilai &middot; {{ $stats['belum_dinilai'] }} belum</small>
            </div>
        </div>
    </div>

    {{-- 3. Lulus Seleksi --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-2">
                            <i class="ri-trophy-line text-success"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Lulus Seleksi</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($stats['lulus_seleksi']) }}</h3>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:6px;">
                        <div class="progress-bar bg-success" style="width:{{ $pctLulus }}%"></div>
                    </div>
                    <span class="badge bg-success-subtle text-success" style="font-size:10px;">{{ $pctLulus }}%</span>
                </div>
                <small class="text-muted">dari {{ $stats['sudah_dinilai'] }} yang sudah dinilai</small>
            </div>
        </div>
    </div>

    {{-- 4. Ringkasan Tab --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded fs-2">
                            <i class="ri-file-chart-line text-info"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Recruitment</p>
                        <h3 class="fw-bold ff-secondary mb-0" style="font-size:18px;">{{ ucfirst($activeTab) }}</h3>
                    </div>
                </div>
                <small class="text-muted d-block">
                    <i class="ri-information-line me-1"></i>
                    @if($activeTab == 'aktif')
                        Menampilkan pelamar dari recruitment yang sedang berjalan
                    @elseif($activeTab == 'arsip')
                        Menampilkan pelamar dari recruitment yang sudah ditutup
                    @else
                        Menampilkan seluruh pelamar dari semua recruitment
                    @endif
                </small>
            </div>
        </div>
    </div>
</div>

{{-- ===================== TAB FILTER AKTIF / ARSIP / SEMUA ===================== --}}
<ul class="nav nav-pills nav-sm gap-2 mb-3 no-print" role="tablist">
    <li class="nav-item">
        <a href="{{ route('user.ats.data-nilai.index', $userId) }}"
           class="nav-link {{ $activeTab == 'aktif' ? 'active' : '' }} bg-success-subtle text-success"
           style="{{ $activeTab == 'aktif' ? '' : 'opacity:0.7' }}">
            <i class="ri-play-circle-line"></i> Recruitment Aktif
            <span class="badge bg-success text-white ms-1">{{ $stats['pelamar_aktif'] }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('user.ats.data-nilai.index', $userId) }}?recruitment_status=arsip{{ request('job_id') ? '&job_id='.request('job_id') : '' }}{{ request('q') ? '&q='.urlencode(request('q')) : '' }}"
           class="nav-link {{ $activeTab == 'arsip' ? 'active' : '' }} bg-secondary-subtle text-secondary"
           style="{{ $activeTab == 'arsip' ? '' : 'opacity:0.7' }}">
            <i class="ri-archive-line"></i> Recruitment Arsip
            <span class="badge bg-secondary text-white ms-1">{{ $stats['pelamar_arsip'] }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="{{ route('user.ats.data-nilai.index', $userId) }}?recruitment_status=semua{{ request('job_id') ? '&job_id='.request('job_id') : '' }}{{ request('q') ? '&q='.urlencode(request('q')) : '' }}"
           class="nav-link {{ $activeTab == 'semua' ? 'active' : '' }} bg-primary-subtle text-primary"
           style="{{ $activeTab == 'semua' ? '' : 'opacity:0.7' }}">
            <i class="ri-list-unordered"></i> Semua
            <span class="badge bg-primary text-white ms-1">{{ $stats['total_pelamar'] }}</span>
        </a>
    </li>
</ul>

{{-- ===================== TABLE ===================== --}}
<div class="row">
    <div class="col-lg-12">
        <div class="card" id="nilaiList">
            <div class="card-header border-bottom-dashed">
                <div class="row g-3 align-items-center">
                    <div class="col-sm">
                        <h5 class="card-title mb-0">
                            <i class="ri-file-chart-line text-primary me-1"></i> Rekap Nilai Pelamar
                        </h5>
                        <p class="text-muted mb-0" style="font-size:0.82rem">
                            Rekap nilai administrasi, tes, wawancara, praktikum &amp; ranking
                        </p>
                    </div>
                    <div class="col-sm-auto">
                        <div class="d-flex flex-wrap align-items-start gap-2">
                            <a href="{{ route('user.ats.data-nilai.export', ['userId' => $userId] + request()->all()) }}"
                               class="btn btn-soft-success">
                                <i class="ri-file-excel-2-line align-bottom me-1"></i> Export CSV
                            </a>
                            <a href="{{ route('user.ats.jobs.index', ['userId' => $userId]) }}" class="btn btn-primary">
                                <i class="ri-briefcase-line align-bottom me-1"></i> Lowongan
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                {{-- Filter group --}}
                <div class="filter-group">
                    <div class="filter-group-title">
                        <i class="ri-filter-3-line"></i> Filter Data
                    </div>
                    <form method="GET" id="filterForm">
                        <input type="hidden" name="recruitment_status" value="{{ $activeTab }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-2 col-md-4">
                                <label class="form-label mb-1" style="font-size:0.78rem">Posisi</label>
                                <select name="job_id" class="form-select form-select-sm select2">
                                    <option value="">Semua Posisi</option>
                                    @foreach($jobs as $j)
                                        <option value="{{ $j->id }}" {{ request('job_id') == $j->id ? 'selected' : '' }}>
                                            {{ $j->judul }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <label class="form-label mb-1" style="font-size:0.78rem">Tahap</label>
                                <select name="stage_id" class="form-select form-select-sm select2">
                                    <option value="">Semua Tahap</option>
                                    @foreach($stages as $s)
                                        <option value="{{ $s->id }}" {{ request('stage_id') == $s->id ? 'selected' : '' }}>
                                            {{ $s->nama_tahapan }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <label class="form-label mb-1" style="font-size:0.78rem">Status Akhir</label>
                                <select name="status_akhir" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    <option value="lulus"       {{ request('status_akhir') == 'lulus' ? 'selected' : '' }}>Lulus</option>
                                    <option value="tidak_lulus" {{ request('status_akhir') == 'tidak_lulus' ? 'selected' : '' }}>Tidak Lulus</option>
                                    <option value="cadangan"    {{ request('status_akhir') == 'cadangan' ? 'selected' : '' }}>Cadangan</option>
                                </select>
                            </div>
                            <div class="col-lg-1 col-md-3">
                                <label class="form-label mb-1" style="font-size:0.78rem">Nilai ≥</label>
                                <input type="number" step="0.01" min="0" max="100" name="nilai_min"
                                       value="{{ request('nilai_min') }}" class="form-control form-control-sm" placeholder="0">
                            </div>
                            <div class="col-lg-1 col-md-3">
                                <label class="form-label mb-1" style="font-size:0.78rem">Nilai ≤</label>
                                <input type="number" step="0.01" min="0" max="100" name="nilai_max"
                                       value="{{ request('nilai_max') }}" class="form-control form-control-sm" placeholder="100">
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label mb-1" style="font-size:0.78rem">Cari</label>
                                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                                       placeholder="Nama / No lamaran...">
                            </div>
                            <div class="col-lg-2 col-md-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                        <i class="ri-filter-line me-1"></i> Terapkan
                                    </button>
                                    <a href="{{ route('user.ats.data-nilai.index', $userId) }}?recruitment_status={{ $activeTab }}"
                                       class="btn btn-light btn-sm" title="Reset filter">
                                        <i class="ri-restart-line"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Tabel --}}
                <div class="table-responsive">
                    <table id="table-nilai" class="table table-hover align-middle table-nilai mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:50px">#</th>
                                <th>Pelamar</th>
                                <th>Posisi</th>
                                <th class="text-center">Skor Adm</th>
                                <th class="text-center">Tes</th>
                                <th class="text-center">Wawancara</th>
                                <th class="text-center">Praktikum</th>
                                <th class="text-center">Nilai Akhir</th>
                                <th class="text-center">Ranking</th>
                                <th class="text-center">Status</th>
                                <th class="text-center no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    $('.select2').select2({ width: '100%', placeholder: 'Pilih...', allowClear: true });

    const dt = $('#table-nilai').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('user.ats.data-nilai.datatable', $userId) }}",
            data: function (d) {
                d.job_id       = $('[name=job_id]').val();
                d.stage_id     = $('[name=stage_id]').val();
                d.status_akhir = $('[name=status_akhir]').val();
                d.nilai_min    = $('[name=nilai_min]').val();
                d.nilai_max    = $('[name=nilai_max]').val();
                d.q            = $('[name=q]').val();
                d.recruitment_status = @json($activeTab);
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'pelamar',     name: 'pelamar' },
            { data: 'posisi',      name: 'posisi' },
            { data: 'skor_administrasi', name: 'skor_administrasi', className: 'text-center' },
            { data: 'nilai_tes',        name: 'nilai_tes',        className: 'text-center' },
            { data: 'nilai_wawancara',  name: 'nilai_wawancara',  className: 'text-center' },
            { data: 'nilai_praktikum',  name: 'nilai_praktikum',  className: 'text-center' },
            { data: 'nilai_akhir',      name: 'nilai_akhir',      className: 'text-center' },
            { data: 'ranking',          name: 'ranking',          className: 'text-center' },
            { data: 'status_akhir_badge', name: 'status_akhir',   className: 'text-center' },
            { data: 'aksi', orderable: false, searchable: false, className: 'text-center no-print' },
        ],
        order: [[7, 'desc']],
        pageLength: 25,
        language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json" },
        drawCallback: function () {
            // Highlight nilai cells berdasarkan threshold
            $('#table-nilai tbody td.nilai-cell').each(function () {
                const val = parseFloat($(this).data('nilai'));
                if (!isNaN(val)) {
                    if (val >= 80) $(this).addClass('nilai-high');
                    else if (val >= 60) $(this).addClass('nilai-mid');
                    else $(this).addClass('nilai-low');
                }
            });
        }
    });

    @if(session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('success')), timer: 2500, showConfirmButton: false });
    @endif
    @if(session('error'))
        Swal.fire({ icon: 'error', title: 'Gagal', text: @json(session('error')) });
    @endif
});
</script>
@endsection
