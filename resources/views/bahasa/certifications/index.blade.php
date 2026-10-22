@extends('layouts.master')

@section('title', 'Sertifikasi Bahasa')

@section('content')
    @include('bahasa._styles')

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.bahasa.dashboard', ['userId' => $userId]) }}">Departemen Bahasa</a> @endslot
        @slot('li_2') Ujian & Sertifikasi @endslot
        @slot('title') Sertifikasi Bahasa @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h4 class="mb-1">Sertifikasi Bahasa</h4><p class="text-muted mb-0">Pencatatan sertifikat kemampuan Bahasa Arab dan Bahasa Inggris santri.</p></div>
        @if($canManage)<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createCertificationModal"><i class="ri-add-line me-1"></i>Tambah Sertifikasi</button>@endif
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Total Sertifikasi</p><h3 class="fw-bold mb-0">{{ $stats['total'] }}</h3></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Terbit</p><h3 class="fw-bold mb-0">{{ $stats['issued'] }}</h3></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Menunggu</p><h3 class="fw-bold mb-0">{{ $stats['pending'] }}</h3></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card card-animate h-100"><div class="card-body py-3"><p class="text-uppercase fw-medium text-muted mb-0" style="font-size:11px;">Bahasa Arab</p><h3 class="fw-bold mb-0">{{ $stats['arab'] }}</h3></div></div></div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <form method="GET" class="row g-2 align-items-end">
                <x-bahasa-filter-fields
                    :schools="$schools"
                    :grade-levels="$gradeLevels"
                    :study-groups="$studyGroups"
                    :show-school="$showSchool"
                    :school-id="$schoolId"
                    :grade-level-id="$gradeLevelId"
                    :study-group-id="$studyGroupId"
                />
                <div class="col-md-2"><label class="form-label small mb-1">Cari Santri</label><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Nama santri"></div>
                <div class="col-md-2"><label class="form-label small mb-1">Bahasa</label><select name="language" class="form-select"><option value="">Semua</option><option value="arab" @selected(request('language') === 'arab')>Bahasa Arab</option><option value="inggris" @selected(request('language') === 'inggris')>Bahasa Inggris</option></select></div>
                <div class="col-md-2"><label class="form-label small mb-1">Status</label><select name="status" class="form-select"><option value="">Semua</option><option value="issued" @selected(request('status') === 'issued')>Terbit</option><option value="pending" @selected(request('status') === 'pending')>Menunggu</option><option value="revoked" @selected(request('status') === 'revoked')>Dicabut</option></select></div>
                <div class="col-md-2"><button class="btn btn-soft-primary w-100"><i class="ri-filter-3-line me-1"></i>Filter</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Tanggal</th><th>Santri</th><th>Sertifikasi</th><th>Bahasa</th><th>Level</th><th class="text-center">Nilai</th><th>No. Sertifikat</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($certifications as $item)
                        <tr>
                            <td class="text-muted small">{{ $item->issued_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="fw-semibold">{{ $item->student?->name ?? '-' }}</td>
                            <td>{{ $item->certification_name }}</td>
                            <td>{{ $item->language_label }}</td>
                            <td>{{ $item->level ?: '-' }}</td>
                            <td class="text-center">{{ $item->score !== null ? number_format((float) $item->score, 1, ',', '.') : '-' }}</td>
                            <td class="small">{{ $item->certificate_number ?: '-' }}</td>
                            <td><span class="badge {{ $item->status === 'issued' ? 'bg-success-subtle text-success' : ($item->status === 'pending' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger') }}">{{ ucfirst($item->status) }}</span></td>
                            <td class="text-end">@if($canManage)<a href="{{ route('user.bahasa.certifications.print', ['userId' => $userId, 'certification' => $item->id]) }}" target="_blank" class="btn btn-sm btn-soft-primary"><i class="ri-printer-line"></i></a> <button class="btn btn-sm btn-soft-danger delete-certification" data-action="{{ route('user.bahasa.certifications.destroy', ['userId' => $userId, 'certification' => $item->id]) }}" data-title="{{ $item->student?->name }}"><i class="ri-delete-bin-line"></i></button>@else<a href="{{ route('user.bahasa.certifications.print', ['userId' => $userId, 'certification' => $item->id]) }}" target="_blank" class="btn btn-sm btn-soft-primary"><i class="ri-printer-line"></i></a>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-5">Belum ada sertifikasi bahasa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($certifications->hasPages())<div class="card-footer">{{ $certifications->links() }}</div>@endif
    </div>

    @if($canManage)
        <div class="modal fade" id="createCertificationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('user.bahasa.certifications.store', ['userId' => $userId]) }}">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $schoolId }}">
                    <div class="modal-header"><h5 class="modal-title"><i class="ri-award-line me-1 text-success"></i>Tambah Sertifikasi Bahasa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-7"><label class="form-label">Santri <span class="text-danger">*</span></label><select name="student_id" class="form-select" required><option value="">— Pilih santri —</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->name }}</option>@endforeach</select></div>
                            <div class="col-md-5"><label class="form-label">Nama Sertifikasi <span class="text-danger">*</span></label><input name="certification_name" class="form-control" placeholder="Sertifikasi Bahasa Arab" required></div>
                            <div class="col-md-4"><label class="form-label">Bahasa <span class="text-danger">*</span></label><select name="language" class="form-select"><option value="arab">Bahasa Arab</option><option value="inggris">Bahasa Inggris</option></select></div>
                            <div class="col-md-4"><label class="form-label">Level</label><input name="level" class="form-control" placeholder="A1 / Mutawassith"></div>
                            <div class="col-md-4"><label class="form-label">Nilai</label><input type="number" min="0" max="100" step="0.01" name="score" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Tanggal Terbit</label><input type="date" name="issued_date" value="{{ now()->toDateString() }}" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">No. Sertifikat</label><input name="certificate_number" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="issued">Terbit</option><option value="pending">Menunggu</option><option value="revoked">Dicabut</option></select></div>
                            <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" rows="2" class="form-control"></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-success"><i class="ri-save-line me-1"></i>Simpan</button></div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="deleteCertificationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="POST" id="deleteCertificationForm" action="#">@csrf @method('DELETE')<div class="modal-body text-center p-4"><div class="avatar-lg mx-auto mb-3"><span class="avatar-title bg-danger-subtle rounded-circle fs-1 text-danger"><i class="ri-delete-bin-6-line"></i></span></div><h5>Hapus Sertifikasi?</h5><p class="text-muted mb-0"><strong id="deleteCertificationTitle">Sertifikasi ini</strong> akan dihapus permanen.</p></div><div class="modal-footer justify-content-center"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger">Hapus</button></div></form></div>
        </div>
    @endif
@endsection

@section('script')
<script>
document.querySelectorAll('.delete-certification').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('deleteCertificationForm').action = button.dataset.action;
        document.getElementById('deleteCertificationTitle').textContent = button.dataset.title || 'Sertifikasi ini';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteCertificationModal')).show();
    });
});
</script>
@endsection
