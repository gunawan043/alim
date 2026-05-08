@extends('layouts.master')
@section('title') Rekap Poin Pelanggaran @endsection

@section('content')
@php
$sevThresholds = [
    ['min' => 25, 'bg' => 'dark',     'subtle' => 'dark-subtle',     'txt' => 'dark',     'icon' => 'ri-error-warning-fill',     'label' => 'Sangat Berat',  'row' => 'table-danger'],
    ['min' => 16, 'bg' => 'danger',  'subtle' => 'danger-subtle',   'txt' => 'danger',   'icon' => 'ri-close-circle-fill',     'label' => 'Berat',         'row' => 'table-warning'],
    ['min' => 6,  'bg' => 'warning', 'subtle' => 'warning-subtle',  'txt' => 'warning',  'icon' => 'ri-alert-fill',             'label' => 'Sedang',        'row' => 'table-active'],
    ['min' => 1,  'bg' => 'info',    'subtle' => 'info-subtle',     'txt' => 'info',     'icon' => 'ri-information-fill',       'label' => 'Ringan',        'row' => ''],
];
@endphp

@component('components.breadcrumb')
    @slot('li_1') GTK & Peserta Didik @endslot
    @slot('li_2') <a href="{{ route('user.violation-points.index', ['userId' => $userId]) }}">Poin Pelanggaran</a> @endslot
    @slot('title') Rekap Poin Per Siswa @endslot
@endcomponent

{{-- Summary Stats --}}
<div class="row mb-4">
    <div class="col-xxl-3 col-sm-6">
        <div class="card card-animate card-shadow-hover border-0">
            <div class="card-body position-relative overflow-hidden">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-11">Total Siswa Aktif</p>
                        <h2 class="mb-2 fs-2 fw-bold counter-value" data-target="{{ $summary['total_students'] }}">{{ number_format($summary['total_students']) }}</h2>
                        <span class="badge bg-light text-success mb-0"><i class="ri-arrow-up-s-line align-bottom"></i> Aktif</span>
                    </div>
                    <div class="avatar-md bg-primary-subtle rounded flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle text-primary rounded fs-2"><i class="ri-user-3-line"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <div class="card card-animate card-shadow-hover border-0">
            <div class="card-body position-relative overflow-hidden">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-11">Total Pelanggaran</p>
                        <h2 class="mb-2 fs-2 fw-bold counter-value" data-target="{{ $summary['total_violations'] }}">{{ number_format($summary['total_violations']) }}</h2>
                        <span class="badge bg-light text-danger mb-0"><i class="ri-arrow-up-s-line align-bottom"></i> Tercatat</span>
                    </div>
                    <div class="avatar-md bg-danger-subtle rounded flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle text-danger rounded fs-2"><i class="ri-file-list-3-line"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <div class="card card-animate card-shadow-hover border-0">
            <div class="card-body position-relative overflow-hidden">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-11">Total Poin Keseluruhan</p>
                        <h2 class="mb-2 fs-2 fw-bold counter-value" data-target="{{ $summary['total_points'] }}">{{ number_format($summary['total_points']) }}</h2>
                        <span class="badge bg-light text-warning mb-0"><i class="ri-error-warning-line align-bottom"></i> Akumulasi</span>
                    </div>
                    <div class="avatar-md bg-warning-subtle rounded flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle text-warning rounded fs-2"><i class="ri-alert-line"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <div class="card card-animate card-shadow-hover border-0">
            <div class="card-body position-relative overflow-hidden">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-1 fs-11">Siswa Melanggar</p>
                        <h2 class="mb-2 fs-2 fw-bold counter-value" data-target="{{ $summary['students_with_violations'] }}">{{ number_format($summary['students_with_violations']) }}</h2>
                        @if($summary['total_students'] > 0)
                            <span class="badge bg-light text-info mb-0">{{ round(($summary['students_with_violations'] / $summary['total_students']) * 100, 1) }}% dari total siswa</span>
                        @else
                            <span class="badge bg-light text-info mb-0">0% dari total siswa</span>
                        @endif
                    </div>
                    <div class="avatar-md bg-success-subtle rounded flex-shrink-0">
                        <span class="avatar-title bg-success-subtle text-success rounded fs-2"><i class="ri-group-line"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Legend --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-light bg-gradient border-0 mb-0 py-2 px-3 d-flex align-items-center gap-4 flex-wrap" role="alert">
            <span class="fw-semibold text-dark fs-11 text-uppercase">Legenda Tingkat:</span>
            <span class="badge bg-dark-subtle text-dark"><i class="ri-error-warning-fill me-1"></i> >= 25 Sangat Berat</span>
            <span class="badge bg-danger-subtle text-danger"><i class="ri-close-circle-fill me-1"></i> 16-24 Berat</span>
            <span class="badge bg-warning-subtle text-warning"><i class="ri-alert-fill me-1"></i> 6-15 Sedang</span>
            <span class="badge bg-info-subtle text-info"><i class="ri-information-fill me-1"></i> 1-5 Ringan</span>
            <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-fill me-1"></i> 0 Tidak Ada</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="row g-4 align-items-center">
                    <div class="col-sm-6">
                        <h4 class="card-title mb-1 text-dark fw-bold"><i class="ri-file-list-3-line text-primary me-2"></i>Rekap Poin Pelanggaran per Siswa</h4>
                        <p class="text-muted mb-0 fs-13">Daftar akumulasi poin pelanggaran setiap peserta didik.</p>
                    </div>
                    <div class="col-sm-auto">
                        <a href="{{ route('user.violation-points.dashboard', ['userId' => $userId]) }}" class="btn btn-secondary btn-label me-2">
                            <i class="ri-dashboard-line label-icon align-middle me-1"></i> Dashboard
                        </a>
                        <a href="{{ route('user.violation-points.export-pdf', ['userId' => $userId]) }}" class="btn btn-danger btn-label">
                            <i class="ri-file-pdf-line label-icon align-middle me-1"></i> Export PDF
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body bg-light-subtle border-bottom">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label text-dark fw-medium fs-13"><i class="ri-search-line me-1 text-primary"></i> Cari Siswa</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Nama atau NISN..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-dark fw-medium fs-13"><i class="ri-team-line me-1 text-primary"></i> Rombongan Belajar</label>
                        <select name="study_group_id" class="form-control form-control-sm">
                            <option value="">-- Semua Rombel --</option>
                            @foreach($studyGroups as $sg)
                                <option value="{{ $sg->id }}" {{ request('study_group_id') == $sg->id ? 'selected' : '' }}>{{ $sg->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-dark fw-medium fs-13"><i class="ri-error-warning-line me-1 text-primary"></i> Min. Poin</label>
                        <input type="number" name="min_points" class="form-control form-control-sm" placeholder="0" value="{{ request('min_points') }}" min="0">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="ri-search-line me-1"></i> Tampilkan</button>
                        <a href="{{ route('user.violation-points.recap', ['userId' => $userId]) }}" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="ri-refresh-line"></i></a>
                    </div>
                </form>
                @if(request()->hasAny(['search', 'study_group_id', 'min_points']))
                    <div class="mt-2"><span class="badge bg-info-subtle text-info me-1"><i class="ri-filter-2-line me-1"></i> Filter aktif</span> <small class="text-muted">Hasil pencarian dengan filter yang diterapkan.</small></div>
                @endif
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead class="table-secondary text-dark">
                            <tr>
                                <th class="text-center" style="width:50px;">#</th>
                                <th>Peserta Didik</th>
                                <th style="width:180px;">Rombongan Belajar</th>
                                <th class="text-center" style="width:100px;">Jml. Pel.</th>
                                <th class="text-center" style="width:140px;">Total Poin</th>
                                <th class="text-center" style="width:180px;">Tingkat</th>
                                <th class="text-center" style="width:110px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
@php $sevRowClass = ''; $sevBadgeBg = ''; $sevBadgeSubtle = ''; $sevBadgeTxt = ''; $sevIcon = ''; $sevLabel = ''; @endphp
@forelse($recaps as $i => $s)
@php
$pts = $s->violation_points_sum_points ?? 0;
$rombel = $s->studyGroups->first()?->studyGroup?->full_name ?? '-';
$initials = collect(explode(' ', $s->name))->take(2)->map(fn($w) => strtoupper($w[0] ?? ''))->implode('');
$sevRowClass = '';
foreach ($sevThresholds as $t) {
    if ($pts >= $t['min']) {
        $sevBadgeBg = $t['bg'];
        $sevBadgeSubtle = $t['subtle'];
        $sevBadgeTxt = $t['txt'];
        $sevIcon = $t['icon'];
        $sevLabel = $t['label'];
        $sevRowClass = $t['row'];
        break;
    }
}
if ($pts == 0) {
    $sevBadgeBg = 'success'; $sevBadgeSubtle = 'success-subtle'; $sevBadgeTxt = 'success'; $sevIcon = 'ri-checkbox-circle-fill'; $sevLabel = 'Tidak Ada'; $sevRowClass = '';
}
@endphp
                            <tr class="{{ $sevRowClass }}">
                                <td class="text-center text-muted fw-medium"><span class="badge bg-light text-dark">{{ $recaps->firstItem() + $i }}</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-xs flex-shrink-0">
                                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-11 fw-bold">{{ $initials }}</span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <span class="fw-semibold text-dark">{{ $s->name }}</span>
                                            @if($s->nisn)<br><small class="text-muted">NISN: {{ $s->nisn }}</small>@endif
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark"><i class="ri-government-line me-1"></i>{{ $rombel }}</span></td>
                                <td class="text-center"><span class="badge bg-secondary-subtle text-secondary fw-medium">{{ $s->violation_points_count ?? 0 }}x</span></td>
                                <td class="text-center">
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <span class="badge bg-{{ $sevBadgeSubtle }} text-{{ $sevBadgeTxt }} fs-6 fw-bold px-2 py-1">{{ $pts }}</span>
                                        @if($pts > 0)
                                            <div class="progress rounded-pill" style="height:4px;width:80px;">
                                                <div class="progress-bar bg-{{ $sevBadgeBg }} rounded-pill" style="width:{{ min(100, $pts) }}%"></div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <span class="badge bg-{{ $sevBadgeSubtle }} text-{{ $sevBadgeTxt }} px-2 py-1"><i class="{{ $sevIcon }} me-1"></i>{{ $sevLabel }}</span>
                                        @if($pts > 0)<small class="text-muted">Poin Pelanggaran</small>@endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('user.violation-points.recap.detail', ['userId' => $userId, 'studentUuid' => $s->id]) }}" class="btn btn-soft-primary btn-sm w-100"><i class="ri-eye-line me-1"></i> Detail</a>
                                </td>
                            </tr>
@empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <div class="avatar-md bg-secondary-subtle rounded-circle d-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                                            <i class="ri-inbox-2-line text-secondary fs-2"></i>
                                        </div>
                                        <div>
                                            <h6 class="text-muted mb-1">Tidak Ada Data Ditemukan</h6>
                                            <p class="text-muted mb-0 fs-13">Coba ubah kata kunci pencarian atau filter yang digunakan.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
@endforelse
                        </tbody>
                    </table>
                </div>
                @php $paginator = $recaps; @endphp
                @include('shared._pagination', ['paginator' => $paginator])
            </div>
        </div>
    </div>
</div>
@endsection

@push('page_scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var counters = document.querySelectorAll('.counter-value');
    counters.forEach(function(counter) {
        var target = parseInt(counter.getAttribute('data-target') || counter.textContent.replace(/\D/g, ''));
        if (!target) return;
        var current = 0;
        var increment = Math.ceil(target / 60);
        var timer = setInterval(function() {
            current += increment;
            if (current >= target) { current = target; clearInterval(timer); }
            counter.textContent = current.toLocaleString('id-ID');
        }, 20);
    });
});
</script>
@endpush
