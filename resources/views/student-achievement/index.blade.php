@extends('layouts.master')
@section('title') Data Prestasi — {{ $typeLabel }} @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    @include('kurikulum._styles')
@endsection

@section('content')
@php
$tabs = [
    'akademik' => 'Prestasi Akademik',
    'quran'    => 'Hafalan Qur\'an',
    'hadits'   => 'Hafalan Hadits',
];
$typeIcons = [
    'akademik' => 'ri-medal-line',
    'quran'    => 'ri-book-mark-line',
    'hadits'   => 'ri-heart-line',
];
$userId = $userId ?? auth()->id();
$activeTypeKey = match ($achievementType) {
    'hafalan_quran' => 'quran',
    'hafalan_hadits' => 'hadits',
    default => $achievementType,
};
$levelLabels = [
    'internal' => 'Internal',
    'kecamatan' => 'Kecamatan',
    'kabupaten_kota' => 'Kabupaten/Kota',
    'provinsi' => 'Provinsi',
    'nasional' => 'Nasional',
    'internasional' => 'Internasional',
];
$totalPrestasi = $stats['total'] ?? $achievements->total();
$juara1 = $stats['juara_1'] ?? 0;
$juara2 = $stats['juara_2'] ?? 0;
$juara3 = $stats['juara_3'] ?? 0;
$mumtaz = $stats['mumtaz'] ?? 0;
$tahunIni = $stats['tahun_ini'] ?? 0;
$jenisLomba = $stats['jenis'] ?? 0;
$terverifikasi = $stats['terverifikasi'] ?? 0;
$levelStats = $stats['levels'] ?? [];
$pctTahunIni = $totalPrestasi > 0 ? round($tahunIni / $totalPrestasi * 100) : 0;
$pctTerverifikasi = $totalPrestasi > 0 ? round($terverifikasi / $totalPrestasi * 100) : 0;
@endphp

@component('components.breadcrumb')
    @slot('li_1') Akademik @endslot
    @slot('title') {{ $typeLabel }} @endslot
@endcomponent

{{-- Session alerts --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@php $importErrors = session('import_errors', []); @endphp
@if(count($importErrors) > 0)
    <div class="alert alert-warning alert-dismissible fade show">
        <strong>{{ count($importErrors) }} baris tidak bisa diimport:</strong>
        <ul class="mb-0 mt-1">
            @foreach($importErrors as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- TABS --}}
<ul class="nav nav-tabs mb-3" id="achievementTabs" role="tablist">
    @foreach($tabs as $key => $label)
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $activeTypeKey === $key ? 'active' : '' }}"
               href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => $key]) }}"
               role="tab">
                <i class="{{ $typeIcons[$key] }} me-1"></i> {{ $label }}
            </a>
        </li>
    @endforeach
</ul>

{{-- STATISTIK --}}
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-2"><i class="{{ $typeIcons[$activeTypeKey] ?? 'ri-medal-line' }} text-primary"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Total Prestasi</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($totalPrestasi) }}</h3>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-success-subtle text-success stat-label">
                        <i class="ri-verified-badge-line me-1"></i>{{ number_format($terverifikasi) }} terverifikasi ({{ $pctTerverifikasi }}%)
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-trophy-line text-warning"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Juara 1 / 2 / 3</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($juara1 + $juara2 + $juara3) }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">juara</small></h3>
                    </div>
                </div>
                <div class="d-flex gap-1 flex-wrap">
                    <span class="badge bg-warning-subtle text-warning stat-label">J1 {{ number_format($juara1) }}</span>
                    <span class="badge bg-secondary-subtle text-secondary stat-label">J2 {{ number_format($juara2) }}</span>
                    <span class="badge bg-danger-subtle text-danger stat-label">J3 {{ number_format($juara3) }}</span>
                    @if($mumtaz > 0)
                        <span class="badge bg-info-subtle text-info stat-label">Mumtaz {{ number_format($mumtaz) }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-calendar-check-line text-success"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Tahun Ini</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($tahunIni) }}</h3>
                    </div>
                </div>
                <div class="progress mt-1" style="height:6px;">
                    <div class="progress-bar bg-success" style="width:{{ $pctTahunIni }}%"></div>
                </div>
                <p class="text-muted mb-0 stat-label mt-1"><i class="ri-information-line me-1"></i>{{ $pctTahunIni }}% dari total · {{ now()->year }}</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-90">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-stack-line text-info"></i></span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 stat-label">Jenis Lomba</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ number_format($jenisLomba) }}<small class="fw-normal text-muted ms-1" style="font-size:12px;">jenis</small></h3>
                    </div>
                </div>
                <div class="d-flex gap-1 flex-wrap">
                    @forelse(array_slice($levelStats, 0, 3, true) as $lvl => $cnt)
                        <span class="badge bg-light text-dark stat-label">{{ $levelLabels[$lvl] ?? ucfirst($lvl) }} {{ number_format($cnt) }}</span>
                    @empty
                        <span class="text-muted stat-label">Belum ada tingkat tercatat</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- FILTERS & ACTIONS --}}
<div class="card">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted">Cari</label>
                <input type="search" class="form-control" placeholder="Nama siswa, lomba, penyelenggara..."
                       value="{{ request('search') }}"
                       onchange="applyFilter('search', this.value)" id="searchInput">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Tahun Ajaran</label>
                <select class="form-select" onchange="applyFilter('academic_year_id', this.value)" id="academicYearFilter">
                    <option value="">Semua</option>
                    @foreach($academicYears as $ay)
                        <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>
                            {{ $ay->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Tingkat</label>
                <select class="form-select" onchange="applyFilter('level', this.value)" id="levelFilter">
                    <option value="">Semua</option>
                    @foreach(['internal','kecamatan','kabupaten_kota','provinsi','nasional','internasional'] as $lvl)
                        <option value="{{ $lvl }}" {{ request('level') == $lvl ? 'selected' : '' }}>
                            {{ match($lvl){'internal'=>'Internal','kecamatan'=>'Kecamatan','kabupaten_kota'=>'Kabupaten/Kota','provinsi'=>'Provinsi','nasional'=>'Nasional','internasional'=>'Internasional'} }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Kelas</label>
                <select class="form-select" onchange="applyFilter('study_group_id', this.value)" id="studyGroupFilter">
                    <option value="">Semua</option>
                    @foreach($studyGroups as $sg)
                        <option value="{{ $sg->id }}" {{ request('study_group_id') == $sg->id ? 'selected' : '' }}>
                            {{ $sg->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-outline-secondary flex-grow-1" onclick="clearFilters()">
                    <i class="ri-filter-off-line me-1"></i> Reset
                </button>
            </div>
        </div>
    </div>
</div>

{{-- TABLE CARD --}}
<div class="card">
    <div class="card-header border-bottom-dashed">
        <div class="row g-3 align-items-center">
            <div class="col-sm">
                <h5 class="card-title mb-0">Daftar {{ $typeLabel }}</h5>
                <p class="text-muted mb-0">
                    <span class="badge bg-primary-subtle text-primary">{{ number_format($totalPrestasi) }} data</span>
                    <span class="badge bg-secondary-subtle text-secondary ms-1">{{ number_format($jenisLomba) }} jenis lomba</span>
                </p>
            </div>
            <div class="col-sm-auto">
                <div class="d-flex gap-2">
                    <a href="{{ route('user.student-achievement.import-form', ['userId' => $userId, 'type' => $activeTypeKey]) }}"
                       class="btn btn-outline-primary btn-sm">
                        <i class="ri-upload-cloud-line me-1"></i> Import Massal
                    </a>
                    <a href="{{ route('user.student-achievement.create', ['userId' => $userId, 'type' => $activeTypeKey]) }}"
                       class="btn btn-primary btn-sm">
                        <i class="ri-add-line me-1"></i> Tambah
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card-header py-2 bg-light border-bottom">
        <div class="d-flex flex-wrap align-items-center">
            <span class="text-muted small fw-semibold me-2"><i class="ri-filter-3-line me-1"></i>Filter Cepat:</span>
            @foreach(['akademik' => 'Akademik', 'quran' => 'Qur\'an', 'hadits' => 'Hadits'] as $key => $label)
                <a href="{{ request()->fullUrlWithQuery(['type' => $key, 'page' => null]) }}"
                   class="filter-badge {{ $activeTypeKey === $key ? 'active' : '' }}">
                    <i class="{{ $typeIcons[$key] }}"></i> {{ $label }}
                </a>
            @endforeach
            <span class="text-muted small ms-2 me-2">·</span>
            @foreach($academicYears->take(4) as $ay)
                <a href="{{ request()->fullUrlWithQuery(['academic_year_id' => $ay->id, 'page' => null]) }}"
                   class="filter-badge {{ request('academic_year_id') == $ay->id ? 'active' : '' }}">
                    {{ $ay->name }}
                </a>
            @endforeach
            <span class="text-muted small ms-2 me-2">·</span>
            @foreach($levelLabels as $lvl => $label)
                <a href="{{ request()->fullUrlWithQuery(['level' => $lvl, 'page' => null]) }}"
                   class="filter-badge {{ request('level') == $lvl ? 'active' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
            <a href="{{ request()->fullUrlWithQuery(['academic_year_id' => null, 'level' => null, 'page' => null]) }}"
               class="btn btn-sm btn-link text-muted ms-auto">
                <i class="ri-refresh-line me-1"></i>Reset Cepat
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle table-freeze mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:35px">No</th>
                    <th>Siswa</th>
                    <th>Kompetisi / Lomba</th>
                    <th>Penyelenggara</th>
                    <th>Tanggal</th>
                    <th>Tingkat</th>
                    <th>Peringkat</th>
                    <th style="width:60px">Piagam</th>
                    <th style="width:100px">Aksi</th>
                </tr>
            </thead>
            <tbody class="small">
                @forelse($achievements as $i => $ach)
                    <tr>
                        <td class="text-muted">{{ $achievements->firstItem() + $i }}</td>
                        <td>
                            <div class="fw-medium">{{ $ach->student->name ?? '-' }}</div>
                            <div class="text-muted small">NISN: {{ $ach->student->nisn ?? '-' }}</div>
                            <div class="text-muted small">{{ $ach->student->classHistories->firstWhere('is_active', true)?->studyGroup?->full_name ?? '' }}</div>
                        </td>
                        <td>
                            <div class="fw-medium">{{ $ach->event_name }}</div>
                            @if($ach->academicYear)
                                <div class="text-muted small">{{ $ach->academicYear->name }}</div>
                            @endif
                        </td>
                        <td>{{ $ach->organizer ?: '-' }}</td>
                        <td>{{ $ach->event_date?->format('d M Y') ?: '-' }}</td>
                        <td>
                            @php $levelColors = ['internal'=>'secondary','kecamatan'=>'info','kabupaten_kota'=>'primary','provinsi'=>'warning','nasional'=>'danger','internasional'=>'dark']; @endphp
                            <span class="badge bg-{{ $levelColors[$ach->level] ?? 'secondary' }}-subtle">
                                {{ $ach->level_label }}
                            </span>
                        </td>
                        <td>
                            @php $posColors = ['juara_1'=>'warning','juara_2'=>'secondary','juara_3'=>'danger','harapan_1'=>'info','harapan_2'=>'info','harapan_3'=>'info','peserta'=>'secondary','lainnya'=>'dark']; @endphp
                            <span class="badge bg-{{ $posColors[$ach->position] ?? 'secondary' }}-subtle">
                                {{ $ach->position_label }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if($ach->certificate_url)
                                <a href="{{ $ach->certificate_url }}" target="_blank"
                                   class="btn btn-sm btn-outline-success rounded-pill px-2"
                                   title="Lihat piagam">
                                    <i class="ri-image-line"></i>
                                </a>
                            @else
                                <span class="text-muted"><i class="ri-image-off-line"></i></span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('user.student-achievement.show', ['userId' => $userId, 'id' => $ach->id, 'type' => $activeTypeKey]) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Detail">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <a href="{{ route('user.student-achievement.edit', ['userId' => $userId, 'id' => $ach->id, 'type' => $activeTypeKey]) }}"
                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </a>
                                <form method="POST"
                                      action="{{ route('user.student-achievement.destroy', ['userId' => $userId, 'id' => $ach->id, 'type' => $activeTypeKey]) }}"
                                      onsubmit="return confirm('Yakin hapus data prestasi ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-inbox-2-line fs-1 d-block mb-2"></i>
                                Belum ada data prestasi {{ strtolower($typeLabel) }} pada filter ini.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-body pt-0">
        @include('shared._pagination', ['paginator' => $achievements])
    </div>
</div>
@endsection

@section('script')
<script>
function applyFilter(key, value) {
    const url = new URL(window.location.href);
    if (value) {
        url.searchParams.set(key, value);
    } else {
        url.searchParams.delete(key);
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function clearFilters() {
    const url = new URL(window.location.href);
    ['search','academic_year_id','level','study_group_id'].forEach(k => url.searchParams.delete(k));
    url.searchParams.delete('page');
    window.location.href = url.toString();
}
</script>
@endsection
