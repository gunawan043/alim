@extends('layouts.master')
@section('title') Data Prestasi — {{ $typeLabel }} @endsection

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
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
            <a class="nav-link {{ $achievementType === $key ? 'active' : '' }}"
               href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => $key]) }}"
               role="tab">
                <i class="{{ $typeIcons[$key] }} me-1"></i> {{ $label }}
            </a>
        </li>
    @endforeach
</ul>

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

{{-- ACTION BAR --}}
<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="text-muted small">
        Menampilkan {{ $achievements->count() }} dari {{ $achievements->total() }} data
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('user.student-achievement.import-form', ['userId' => $userId, 'type' => $achievementType]) }}"
           class="btn btn-outline-primary btn-sm">
            <i class="ri-upload-cloud-line me-1"></i> Import Massal
        </a>
        <a href="{{ route('user.student-achievement.create', ['userId' => $userId, 'type' => $achievementType]) }}"
           class="btn btn-primary btn-sm">
            <i class="ri-add-line me-1"></i> Tambah
        </a>
    </div>
</div>

{{-- TABLE --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
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
                                <a href="{{ route('user.student-achievement.show', ['userId' => $userId, 'id' => $ach->id, 'type' => $achievementType]) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Detail">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <a href="{{ route('user.student-achievement.edit', ['userId' => $userId, 'id' => $ach->id, 'type' => $achievementType]) }}"
                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </a>
                                <form method="POST"
                                      action="{{ route('user.student-achievement.destroy', ['userId' => $userId, 'id' => $ach->id, 'type' => $achievementType]) }}"
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
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="ri-inbox-2-line fs-2 d-block mb-2"></i>
                            Belum ada data prestasi {{ strtolower($typeLabel) }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($achievements->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Halaman {{ $achievements->currentPage() }} dari {{ $achievements->lastPage() }}
            </div>
            {{ $achievements->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    @endif
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