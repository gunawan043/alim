@extends('layouts.master')

@section('title', 'Mata Pelajaran Bahasa')

@section('content')
    @include('bahasa._styles')

    <style>
        .subject-table-container { position: relative; width: 100%; overflow-x: auto; }
        .subject-table { table-layout: auto; min-width: max-content; width: 100%; margin-bottom: 0; }
        .subject-table th, .subject-table td { vertical-align: middle; padding: 12px 16px; white-space: normal; word-break: break-word; }
        .subject-table thead th { position: sticky; top: 0; z-index: 20; font-weight: 600; border-bottom: 2px solid #dee2e6; }
        .subject-table tbody td:nth-child(1) { position: sticky; left: 0; z-index: 10; background: #fff; min-width: 210px; box-shadow: 2px 0 5px rgba(0,0,0,.07); }
        [data-bs-theme="dark"] .subject-table tbody td:nth-child(1) { background: #102631; }
        .subject-filter-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 30px; font-size: 12px; transition: .2s; cursor: pointer; }
        .subject-filter-badge:hover, .subject-filter-badge.active { background: #0a5f9e; border-color: #0a5f9e; color: #fff; }
        .subject-column-menu { max-height: 380px; overflow-y: auto; padding: 12px; width: 240px; }
    </style>

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') Master Bahasa @endslot
        @slot('title') Mata Pelajaran Bahasa @endslot
    @endcomponent

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2 mb-2"><div class="avatar-sm"><span class="avatar-title bg-primary-subtle rounded fs-2"><i class="ri-book-mark-line text-primary"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Mapel Bahasa</p><h3 class="fw-bold mb-0">{{ $stats['total'] }}</h3></div></div><div class="text-muted" style="font-size:11px;">Tanpa mapel umum</div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2 mb-2"><div class="avatar-sm"><span class="avatar-title bg-success-subtle rounded fs-2"><i class="ri-translate text-success"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Bahasa Arab</p><h3 class="fw-bold mb-0">{{ $stats['arab'] }}</h3></div></div><div class="text-muted" style="font-size:11px;">Mapel Arab</div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2 mb-2"><div class="avatar-sm"><span class="avatar-title bg-info-subtle rounded fs-2"><i class="ri-english-input text-info"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Bahasa Inggris</p><h3 class="fw-bold mb-0">{{ $stats['inggris'] }}</h3></div></div><div class="text-muted" style="font-size:11px;">Mapel Inggris</div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><div class="d-flex align-items-center gap-2 mb-2"><div class="avatar-sm"><span class="avatar-title bg-warning-subtle rounded fs-2"><i class="ri-time-line text-warning"></i></span></div><div><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total JP</p><h3 class="fw-bold mb-0">{{ $stats['jp'] }}</h3></div></div><div class="text-muted" style="font-size:11px;">Jam pelajaran</div></div></div></div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0"><i class="ri-book-mark-line text-primary me-1"></i>Daftar Mata Pelajaran Bahasa</h5>
                    <p class="text-muted mb-0 small">Hanya Bahasa Arab, Bahasa Inggris, dan English. Mapel umum tidak ditampilkan.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-sm-end">
                        <form method="GET" action="{{ route('user.bahasa.reference.subjects', ['userId' => $userId]) }}" class="d-flex gap-2">
                            <input type="hidden" name="school_id" value="{{ $schoolId }}">
                            <input type="hidden" name="grade_level_id" value="{{ $gradeLevelId }}">
                            <input type="hidden" name="study_group_id" value="{{ $studyGroupId }}">
                            <input type="hidden" name="language" value="{{ $language }}">
                            <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nama mapel..." style="width:220px">
                            <button class="btn btn-primary"><i class="ri-search-line"></i></button>
                        </form>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#subjectFilterModal"><i class="ri-filter-3-line me-1"></i>Filter</button>
                        <div class="dropdown">
                            <button class="btn btn-soft-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="ri-table-line me-1"></i>Kolom</button>
                            <div class="dropdown-menu dropdown-menu-end subject-column-menu">
                                <div class="form-check mb-2"><input class="form-check-input subject-column" type="checkbox" data-column="code" id="subjectColCode" checked><label class="form-check-label" for="subjectColCode">Kode</label></div>
                                <div class="form-check mb-2"><input class="form-check-input subject-column" type="checkbox" data-column="school" id="subjectColSchool" checked><label class="form-check-label" for="subjectColSchool">Satuan Pendidikan</label></div>
                                <div class="form-check mb-2"><input class="form-check-input subject-column" type="checkbox" data-column="jp" id="subjectColJp" checked><label class="form-check-label" for="subjectColJp">Jam Pelajaran</label></div>
                                <div class="form-check mb-2"><input class="form-check-input subject-column" type="checkbox" data-column="books" id="subjectColBooks" checked><label class="form-check-label" for="subjectColBooks">Buku Nilai</label></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-header py-2 bg-light border-bottom">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted"><i class="ri-flashlight-line"></i> Quick Filter:</span>
                <a href="{{ route('user.bahasa.reference.subjects', ['userId' => $userId, 'language' => 'arab']) }}" class="subject-filter-badge {{ $language === 'arab' ? 'active' : '' }}"><i class="ri-translate"></i> Bahasa Arab</a>
                <a href="{{ route('user.bahasa.reference.subjects', ['userId' => $userId, 'language' => 'inggris']) }}" class="subject-filter-badge {{ $language === 'inggris' ? 'active' : '' }}"><i class="ri-english-input"></i> Bahasa Inggris</a>
                <a href="{{ route('user.bahasa.reference.subjects', ['userId' => $userId]) }}" class="subject-filter-badge {{ ! $language ? 'active' : '' }}"><i class="ri-list-check"></i> Semua</a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="subject-table-container">
                <table class="table table-hover align-middle subject-table">
                    <thead class="table-light"><tr><th>Nama Mapel</th><th data-column="code">Kode</th><th data-column="school">Satuan Pendidikan</th><th class="text-center" data-column="jp">Jam Pelajaran</th><th class="text-center" data-column="books">Buku Nilai</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        @forelse($subjects as $subject)
                            <tr>
                                <td class="fw-semibold">{{ $subject->name }}</td>
                                <td data-column="code">{{ $subject->code }}</td>
                                <td data-column="school">{{ $subject->school?->name ?? '-' }}</td>
                                <td data-column="jp" class="text-center">{{ $subject->credit_hours }}</td>
                                <td data-column="books" class="text-center">{{ $subject->admin_books_count ?? 0 }}</td>
                                <td><span class="badge {{ $subject->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $subject->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="text-end"><button type="button" class="btn btn-sm btn-soft-primary subject-detail" data-name="{{ $subject->name }}" data-code="{{ $subject->code }}" data-school="{{ $subject->school?->name }}" data-jp="{{ $subject->credit_hours }}" data-books="{{ $subject->admin_books_count ?? 0 }}" data-status="{{ $subject->is_active ? 'Aktif' : 'Nonaktif' }}"><i class="ri-eye-line"></i></button></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">Tidak ada mata pelajaran bahasa pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="subjectFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form class="modal-content" method="GET" action="{{ route('user.bahasa.reference.subjects', ['userId' => $userId]) }}">
                <div class="modal-header"><h5 class="modal-title"><i class="ri-filter-3-line me-1 text-info"></i>Filter Mata Pelajaran Bahasa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><div class="row g-3">
                    <x-bahasa-filter-fields :schools="$schools" :grade-levels="$gradeLevels" :study-groups="$studyGroups" :show-school="$showSchool" :school-id="$schoolId" :grade-level-id="$gradeLevelId" :study-group-id="$studyGroupId" :auto-submit="false" />
                    <div class="col-md-3"><label class="form-label">Bahasa</label><select name="language" class="form-select"><option value="">Semua bahasa</option><option value="arab" @selected($language === 'arab')>Bahasa Arab</option><option value="inggris" @selected($language === 'inggris')>Bahasa Inggris</option></select></div>
                    <div class="col-md-6"><label class="form-label">Cari Mapel</label><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Nama mapel"></div>
                </div></div>
                <div class="modal-footer"><a href="{{ route('user.bahasa.reference.subjects', ['userId' => $userId]) }}" class="btn btn-light">Reset</a><button class="btn btn-primary"><i class="ri-filter-3-line me-1"></i>Terapkan Filter</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="subjectDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="ri-book-mark-line me-1 text-primary"></i>Detail Mata Pelajaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-6"><div class="text-muted small">Nama Mapel</div><div class="fw-semibold" id="detailSubjectName">-</div></div><div class="col-md-6"><div class="text-muted small">Kode</div><div id="detailSubjectCode">-</div></div><div class="col-md-6"><div class="text-muted small">Satuan Pendidikan</div><div id="detailSubjectSchool">-</div></div><div class="col-md-3"><div class="text-muted small">Jam Pelajaran</div><div id="detailSubjectJp">-</div></div><div class="col-md-3"><div class="text-muted small">Buku Nilai</div><div id="detailSubjectBooks">-</div></div><div class="col-12"><div class="text-muted small">Status</div><div id="detailSubjectStatus">-</div></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button></div></div></div>
    </div>
@endsection

@section('script')
<script>
document.querySelectorAll('.subject-column').forEach(function (checkbox) {
    checkbox.addEventListener('change', function () {
        document.querySelectorAll('.subject-table [data-column="' + checkbox.dataset.column + '"]').forEach(function (cell) {
            cell.classList.toggle('d-none', !checkbox.checked);
        });
    });
});
document.querySelectorAll('.subject-detail').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('detailSubjectName').textContent = button.dataset.name || '-';
        document.getElementById('detailSubjectCode').textContent = button.dataset.code || '-';
        document.getElementById('detailSubjectSchool').textContent = button.dataset.school || '-';
        document.getElementById('detailSubjectJp').textContent = button.dataset.jp || '-';
        document.getElementById('detailSubjectBooks').textContent = button.dataset.books || '-';
        document.getElementById('detailSubjectStatus').textContent = button.dataset.status || '-';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('subjectDetailModal')).show();
    });
});
</script>
@endsection
