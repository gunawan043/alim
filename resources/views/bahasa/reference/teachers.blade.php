@extends('layouts.master')

@section('title', 'Data Guru Bahasa')

@section('content')
    @include('bahasa._styles')

    <style>
        .teacher-table-container { position: relative; width: 100%; overflow-x: auto; }
        .teacher-table { table-layout: auto; min-width: max-content; width: 100%; margin-bottom: 0; }
        .teacher-table th, .teacher-table td { white-space: normal; vertical-align: middle; padding: 12px 16px; word-break: break-word; }
        .teacher-table thead th { position: sticky; top: 0; z-index: 20; font-weight: 600; border-bottom: 2px solid #dee2e6; }
        .teacher-table tbody td:nth-child(1) { position: sticky; left: 0; z-index: 10; background: #fff; min-width: 180px; box-shadow: 2px 0 5px rgba(0,0,0,.07); }
        [data-bs-theme="dark"] .teacher-table tbody td:nth-child(1) { background: #102631; }
        .teacher-filter-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 30px; font-size: 12px; transition: .2s; cursor: pointer; }
        .teacher-filter-badge:hover, .teacher-filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }
        .teacher-column-menu { max-height: 380px; overflow-y: auto; padding: 12px; width: 240px; }
    </style>

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') Operasional Harian @endslot
        @slot('title') Data Guru Bahasa @endslot
    @endcomponent

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2 mb-2"><div class="avatar-sm"><span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-team-line text-primary"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Guru Bahasa</p><h3 class="fw-bold mb-0">{{ $stats['total'] }}</h3></div></div><div class="text-muted" style="font-size:11px;">Hanya guru bahasa</div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2 mb-2"><div class="avatar-sm"><span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-translate text-success"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Bahasa Arab</p><h3 class="fw-bold mb-0">{{ $stats['arab'] }}</h3></div></div><div class="text-muted" style="font-size:11px;">Guru mapel Arab</div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2 mb-2"><div class="avatar-sm"><span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-english-input text-info"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Bahasa Inggris</p><h3 class="fw-bold mb-0">{{ $stats['inggris'] }}</h3></div></div><div class="text-muted" style="font-size:11px;">Guru mapel Inggris</div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2 mb-2"><div class="avatar-sm"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-building-line text-warning"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Satuan Pendidikan</p><h3 class="fw-bold mb-0">{{ $stats['schools'] }}</h3></div></div><div class="text-muted" style="font-size:11px;">Sekolah dengan guru bahasa</div></div></div></div>
    </div>

    <div class="card" id="teacherListCard">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0"><i class="ri-user-star-line text-primary me-1"></i>Daftar Guru Bahasa</h5>
                    <p class="text-muted mb-0 small">Hanya guru Bahasa Arab dan Bahasa Inggris. Import/tambah GTK tetap khusus Humas Personalia dan Super Admin.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-sm-end">
                        <form method="GET" action="{{ route('user.bahasa.reference.teachers', ['userId' => $userId]) }}" class="d-flex gap-2">
                            <input type="hidden" name="school_id" value="{{ $schoolId }}">
                            <input type="hidden" name="grade_level_id" value="{{ $gradeLevelId }}">
                            <input type="hidden" name="study_group_id" value="{{ $studyGroupId }}">
                            <input type="hidden" name="language" value="{{ $language }}">
                            <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nama guru..." style="width:220px">
                            <button class="btn btn-primary"><i class="ri-search-line"></i></button>
                        </form>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#teacherFilterModal"><i class="ri-filter-3-line me-1"></i>Filter</button>
                        <div class="dropdown">
                            <button class="btn btn-soft-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="ri-table-line me-1"></i>Kolom</button>
                            <div class="dropdown-menu dropdown-menu-end teacher-column-menu">
                                <div class="form-check mb-2"><input class="form-check-input teacher-column" type="checkbox" data-column="email" id="teacherColEmail" checked><label class="form-check-label" for="teacherColEmail">Email</label></div>
                                <div class="form-check mb-2"><input class="form-check-input teacher-column" type="checkbox" data-column="nupy" id="teacherColNupy"><label class="form-check-label" for="teacherColNupy">NUPY</label></div>
                                <div class="form-check mb-2"><input class="form-check-input teacher-column" type="checkbox" data-column="jabatan" id="teacherColJabatan" checked><label class="form-check-label" for="teacherColJabatan">Jabatan</label></div>
                                <div class="form-check mb-2"><input class="form-check-input teacher-column" type="checkbox" data-column="school" id="teacherColSchool" checked><label class="form-check-label" for="teacherColSchool">Satuan Pendidikan</label></div>
                                <div class="form-check mb-2"><input class="form-check-input teacher-column" type="checkbox" data-column="mapel" id="teacherColMapel" checked><label class="form-check-label" for="teacherColMapel">Mapel</label></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-header py-2 bg-light border-bottom">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted"><i class="ri-flashlight-line"></i> Quick Filter:</span>
                <a href="{{ route('user.bahasa.reference.teachers', ['userId' => $userId, 'language' => 'arab']) }}" class="teacher-filter-badge {{ $language === 'arab' ? 'active' : '' }}"><i class="ri-translate"></i> Bahasa Arab</a>
                <a href="{{ route('user.bahasa.reference.teachers', ['userId' => $userId, 'language' => 'inggris']) }}" class="teacher-filter-badge {{ $language === 'inggris' ? 'active' : '' }}"><i class="ri-english-input"></i> Bahasa Inggris</a>
                <a href="{{ route('user.bahasa.reference.teachers', ['userId' => $userId]) }}" class="teacher-filter-badge {{ ! $language ? 'active' : '' }}"><i class="ri-list-check"></i> Semua</a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="teacher-table-container">
                <table class="table table-hover align-middle teacher-table">
                    <thead class="table-light">
                        <tr>
                            <th data-column="nama">Nama Guru</th>
                            <th data-column="email">Email</th>
                            <th data-column="nupy" class="d-none">NUPY</th>
                            <th data-column="jabatan">Jabatan</th>
                            <th data-column="school">Satuan Pendidikan</th>
                            <th data-column="mapel">Mapel yang Diampu</th>
                            <th data-column="status">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teachers as $teacher)
                            @php
                                $subjects = ($assignments->get($teacher->user_id) ?? collect())->pluck('subject.name')->filter()->unique()->implode(', ');
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $teacher->user?->name ?? '-' }}</td>
                                <td data-column="email" class="small">{{ $teacher->user?->email ?? '-' }}</td>
                                <td data-column="nupy" class="small d-none">{{ $teacher->nupy ?: '-' }}</td>
                                <td data-column="jabatan">{{ $teacher->jabatan ?: '-' }}</td>
                                <td data-column="school">{{ $teacher->school?->name ?? '-' }}</td>
                                <td data-column="mapel" class="small">{{ $subjects ?: '-' }}</td>
                                <td data-column="status"><span class="badge {{ $teacher->user?->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $teacher->user?->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-soft-primary teacher-detail"
                                        data-name="{{ $teacher->user?->name }}"
                                        data-email="{{ $teacher->user?->email }}"
                                        data-nupy="{{ $teacher->nupy }}"
                                        data-jabatan="{{ $teacher->jabatan }}"
                                        data-school="{{ $teacher->school?->name }}"
                                        data-mapel="{{ $subjects }}"
                                        data-status="{{ $teacher->user?->is_active ? 'Aktif' : 'Nonaktif' }}">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-5"><i class="ri-user-search-line fs-2 d-block mb-2"></i>Tidak ada guru bahasa pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="teacherFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form class="modal-content" method="GET" action="{{ route('user.bahasa.reference.teachers', ['userId' => $userId]) }}">
                <div class="modal-header"><h5 class="modal-title"><i class="ri-filter-3-line me-1 text-info"></i>Filter Guru Bahasa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <x-bahasa-filter-fields
                            :schools="$schools"
                            :grade-levels="$gradeLevels"
                            :study-groups="$studyGroups"
                            :show-school="$showSchool"
                            :school-id="$schoolId"
                            :grade-level-id="$gradeLevelId"
                            :study-group-id="$studyGroupId"
                            :auto-submit="false"
                        />
                        <div class="col-md-3"><label class="form-label">Bahasa</label><select name="language" class="form-select"><option value="">Semua bahasa</option><option value="arab" @selected($language === 'arab')>Bahasa Arab</option><option value="inggris" @selected($language === 'inggris')>Bahasa Inggris</option></select></div>
                        <div class="col-md-6"><label class="form-label">Cari Nama</label><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Nama guru"></div>
                    </div>
                </div>
                <div class="modal-footer"><a href="{{ route('user.bahasa.reference.teachers', ['userId' => $userId]) }}" class="btn btn-light">Reset</a><button class="btn btn-primary"><i class="ri-filter-3-line me-1"></i>Terapkan Filter</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="teacherDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="ri-user-star-line me-1 text-primary"></i>Detail Guru Bahasa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="text-muted small">Nama</div><div class="fw-semibold" id="detailTeacherName">-</div></div>
                        <div class="col-md-6"><div class="text-muted small">Email</div><div id="detailTeacherEmail">-</div></div>
                        <div class="col-md-6"><div class="text-muted small">NUPY</div><div id="detailTeacherNupy">-</div></div>
                        <div class="col-md-6"><div class="text-muted small">Jabatan</div><div id="detailTeacherJabatan">-</div></div>
                        <div class="col-md-6"><div class="text-muted small">Satuan Pendidikan</div><div id="detailTeacherSchool">-</div></div>
                        <div class="col-md-6"><div class="text-muted small">Status</div><div id="detailTeacherStatus">-</div></div>
                        <div class="col-12"><div class="text-muted small">Mapel yang Diampu</div><div id="detailTeacherMapel">-</div></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button></div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
document.querySelectorAll('.teacher-column').forEach(function (checkbox) {
    checkbox.addEventListener('change', function () {
        document.querySelectorAll('.teacher-table [data-column="' + checkbox.dataset.column + '"]').forEach(function (cell) {
            cell.classList.toggle('d-none', !checkbox.checked);
        });
    });
});

document.querySelectorAll('.teacher-detail').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('detailTeacherName').textContent = button.dataset.name || '-';
        document.getElementById('detailTeacherEmail').textContent = button.dataset.email || '-';
        document.getElementById('detailTeacherNupy').textContent = button.dataset.nupy || '-';
        document.getElementById('detailTeacherJabatan').textContent = button.dataset.jabatan || '-';
        document.getElementById('detailTeacherSchool').textContent = button.dataset.school || '-';
        document.getElementById('detailTeacherStatus').textContent = button.dataset.status || '-';
        document.getElementById('detailTeacherMapel').textContent = button.dataset.mapel || '-';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('teacherDetailModal')).show();
    });
});
</script>
@endsection
