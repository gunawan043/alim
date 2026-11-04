@extends('layouts.master')
@section('title', 'Import Kurikulum')
@section('css')
    @include('kurikulum._styles')
@endsection

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.kurikulum.index', ['userId' => $userId]) }}">Kurikulum</a> @endslot
        @slot('title') Import Kurikulum @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $importErrors = session('kurikulum_import_errors', []);
        $importWarnings = session('kurikulum_import_warnings', []);
        $stats = session('kurikulum_import_stats', []);
    @endphp

    @if(! empty($stats))
        <div class="row g-2 mb-3">
            @foreach([
                'cp_created' => 'CP Baru', 'cp_updated' => 'CP Diperbarui',
                'tp_created' => 'TP Baru', 'tp_updated' => 'TP Diperbarui',
                'atp_created' => 'ATP Baru', 'atp_updated' => 'ATP Diperbarui',
                'perangkat_created' => 'Perangkat Baru', 'perangkat_updated' => 'Perangkat Diperbarui',
                'prota_created' => 'PROTA Baru', 'prota_updated' => 'PROTA Diperbarui',
                'prosem_created' => 'PROSEM Baru', 'prosem_updated' => 'PROSEM Diperbarui',
            ] as $key => $label)
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="card card-animate h-100">
                        <div class="card-body py-2">
                            <div class="text-muted stat-label text-uppercase">{{ $label }}</div>
                            <h5 class="mb-0 fw-bold">{{ (int) ($stats[$key] ?? 0) }}</h5>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if(count($importErrors))
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

    @if(count($importWarnings))
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
                    <h5 class="card-title mb-0"><i class="ri-upload-cloud-2-line me-1 text-primary"></i>Unggah File Import Kurikulum</h5>
                    <p class="text-muted mb-0 small">CP → TP → ATP → Perangkat → Prota → Prosem dalam satu workbook.</p>
                </div>
                <form method="POST" action="{{ route('user.kurikulum.import.store', ['userId' => $userId]) }}" enctype="multipart/form-data">
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
                                            <option value="{{ $school->id }}" {{ old('school_id', $selectedSchoolId) === $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
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

                            <div class="col-md-3">
                                <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-select" required>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ old('academic_year_id', $selectedAyId) === $ay->id ? 'selected' : '' }}>{{ $ay->name }} ({{ $ay->semester_text }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <select name="semester" class="form-select" required>
                                    <option value="ganjil" {{ old('semester', $selectedSemester) === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                                    <option value="genap" {{ old('semester', $selectedSemester) === 'genap' ? 'selected' : '' }}>Genap</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">File Excel <span class="text-danger">*</span></label>
                                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                <div class="form-text">Maksimal 10 MB. Sheet: CP, TP, ATP, ATP_ITEM, PERANGKAT, PROTA, PROSEM.</div>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="update_existing" value="1" id="updateExisting" {{ old('update_existing', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="updateExisting">Perbarui data lama jika kunci data sudah ada</label>
                                    <div class="form-text">Jika tidak dicentang, data duplikat dilaporkan sebagai error.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex flex-wrap justify-content-between gap-2">
                        <a href="{{ route('user.kurikulum.import.template', ['userId' => $userId, 'school_id' => $selectedSchoolId, 'academic_year_id' => $selectedAyId, 'semester' => $selectedSemester]) }}" class="btn btn-soft-success">
                            <i class="ri-download-2-line me-1"></i>Unduh Template
                        </a>
                        <div class="d-flex gap-2">
                            <a href="{{ route('user.kurikulum.index', ['userId' => $userId]) }}" class="btn btn-light">Batal</a>
                            <button type="submit" class="btn btn-primary"><i class="ri-upload-2-line me-1"></i>Mulai Import</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-information-line me-1 text-info"></i>Urutan &amp; Kunci Data</h5>
                </div>
                <div class="card-body small">
                    <ol class="ps-3 mb-3">
                        <li class="mb-1"><strong>CP</strong> — mapel + fase + elemen + TA.</li>
                        <li class="mb-1"><strong>TP</strong> — mapel + kelas + kode TP + TA + semester; terhubung ke CP.</li>
                        <li class="mb-1"><strong>ATP</strong> — mapel + kelas + TA + semester.</li>
                        <li class="mb-1"><strong>ATP_ITEM</strong> — kode TP + urutan + JP; item ATP diganti bila baris tersedia.</li>
                        <li class="mb-1"><strong>PERANGKAT</strong> — satu dokumen current per mapel + kelas + TA + semester.</li>
                        <li class="mb-1"><strong>PROTA</strong> — dari ATP; item disinkronkan dari ATP.</li>
                        <li><strong>PROSEM</strong> — dari PROTA; distribusi mengikuti Pekan Efektif.</li>
                    </ol>
                    <div class="alert alert-warning py-2 mb-2">
                        <i class="ri-alert-line me-1"></i>
                        Guru, mapel, dan kelas harus sudah ada di master. Import tidak membuat master baru.
                    </div>
                    <div class="alert alert-info py-2 mb-0">
                        <i class="ri-lightbulb-line me-1"></i>
                        Gunakan sheet <code>REF_MAPEL</code>, <code>REF_KELAS</code>, <code>REF_GURU</code>, <code>REF_CP</code>, <code>REF_TP</code>, dan <code>REF_ATP</code> sebagai acuan pengisian.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
