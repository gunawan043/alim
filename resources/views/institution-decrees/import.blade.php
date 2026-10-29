@extends('layouts.master')
@section('title') Import SK & Matriks @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Administrasi @endslot
        @slot('li_2') <a href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}">Surat Keputusan</a> @endslot
        @slot('title') Import SK & Matriks @endslot
    @endcomponent

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

    @php
        $importErrors = session('import_errors', []);
        $importWarnings = session('import_warnings', []);
    @endphp

    @if(count($importErrors) > 0)
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>{{ count($importErrors) }} baris tidak dapat diimport:</strong>
            <ul class="mb-0 mt-2" style="max-height:280px; overflow:auto;">
                @foreach($importErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(count($importWarnings) > 0)
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <strong>{{ count($importWarnings) }} catatan:</strong>
            <ul class="mb-0 mt-2" style="max-height:220px; overflow:auto;">
                @foreach($importWarnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-upload-cloud-2-line me-1 text-primary"></i>Unggah File Import</h5>
                    <p class="text-muted mb-0 small">Format .xlsx, .xls, atau .csv. Gunakan template agar nama kolom sesuai.</p>
                </div>
                <form method="POST" action="{{ route('user.institution-decrees.import-process', ['userId' => $userId]) }}"
                      enctype="multipart/form-data" id="importForm">
                    @csrf
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row g-3">
                            @if($isGlobal)
                                <div class="col-md-6">
                                    <label class="form-label">Satuan Pendidikan <span class="text-danger">*</span></label>
                                    <select name="school_id" class="form-select" required>
                                        <option value="">— Pilih Sekolah —</option>
                                        @foreach($schools as $school)
                                            <option value="{{ $school->id }}" {{ old('school_id', $selectedSchoolId) === $school->id ? 'selected' : '' }}>
                                                {{ $school->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <input type="hidden" name="school_id" value="{{ $selectedSchoolId }}">
                                <div class="col-md-6">
                                    <label class="form-label">Satuan Pendidikan</label>
                                    <input type="text" class="form-control" value="{{ $schools->first()?->name ?? '—' }}" readonly>
                                </div>
                            @endif

                            <div class="col-md-6">
                                <label class="form-label">Tahun Ajaran Default</label>
                                <select name="academic_year_id" class="form-select">
                                    <option value="">Tahun ajaran aktif</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ old('academic_year_id', $selectedAyId) === $ay->id ? 'selected' : '' }}>
                                            {{ $ay->name }} ({{ $ay->semester_text }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Dipakai bila kolom Tahun Ajaran pada sheet SK kosong.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">File Excel <span class="text-danger">*</span></label>
                                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                <div class="form-text">Maksimal 10 MB. Sheet wajib: <code>SK</code>; opsional: <code>MATRIKS</code>, <code>TUGAS</code>.</div>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="update_existing" value="1"
                                           id="updateExisting" {{ old('update_existing') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="updateExisting">
                                        Perbarui SK yang sudah ada (nomor SK sama)
                                    </label>
                                    <div class="form-text">Jika tidak dicentang, nomor SK yang sudah terdaftar akan ditolak.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex flex-wrap gap-2 justify-content-between">
                        <a href="{{ route('user.institution-decrees.import-template', ['userId' => $userId, 'school_id' => $selectedSchoolId, 'academic_year_id' => $selectedAyId]) }}"
                           id="templateLink" class="btn btn-soft-success">
                            <i class="ri-download-2-line me-1"></i> Unduh Template
                        </a>
                        <div class="d-flex gap-2">
                            <a href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-upload-2-line me-1"></i> Mulai Import
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-information-line me-1 text-info"></i>Struktur Template</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="badge bg-primary-subtle text-primary mb-1">Sheet SK</span>
                        <p class="small text-muted mb-1">Satu baris = satu SK. Wajib: Nomor SK, Judul, Tanggal Ditetapkan.</p>
                        <code class="small">Nomor SK · Jenis SK · Judul · Deskripsi · Tahun Ajaran · Tanggal Ditetapkan · Tanggal Efektif · Tanggal Berakhir · Penandatangan NUPY/Nama · Jabatan · Status</code>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-success-subtle text-success mb-1">Sheet MATRIKS</span>
                        <p class="small text-muted mb-1">Satu baris = guru + mapel + rombel + JP.</p>
                        <code class="small">Nomor SK · NUPY · Nama Guru · Kode Mapel · Mapel · Kelas · Rombel · JP · Koordinator</code>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-warning-subtle text-warning mb-1">Sheet TUGAS</span>
                        <p class="small text-muted mb-1">Opsional. Satu baris = satu tugas tambahan.</p>
                        <code class="small">Nomor SK · NUPY · Nama Guru · Nama Tugas · Kode Tugas · Rombel · JP</code>
                    </div>
                    <hr>
                    <ul class="small text-muted mb-0 ps-3">
                        <li>Guru, mapel, dan rombel harus sudah ada di master.</li>
                        <li>NUPY lebih aman daripada nama (nama bisa sama).</li>
                        <li>Satu mapel satu guru per rombel; pengecualian Tahfidz/Hadits.</li>
                        <li>Baris yang gagal dilaporkan, baris lain tetap diproses.</li>
                        <li>Isi matriks dibekukan sebagai snapshot setelah import.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const school = document.querySelector('[name="school_id"]');
    const year = document.querySelector('[name="academic_year_id"]');
    const link = document.getElementById('templateLink');

    if (!link) return;

    function syncTemplateLink() {
        const url = new URL(link.href, window.location.origin);
        if (school && school.value) url.searchParams.set('school_id', school.value);
        if (year && year.value) url.searchParams.set('academic_year_id', year.value);
        link.href = url.toString();
    }

    school?.addEventListener('change', syncTemplateLink);
    year?.addEventListener('change', syncTemplateLink);
});
</script>
@endpush
