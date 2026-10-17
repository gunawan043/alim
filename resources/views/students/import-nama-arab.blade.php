@extends('layouts.master')
@section('title', 'Import Nama Arab Santri')

@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
        @slot('li_2') Import Nama Arab @endslot
        @slot('title') Import Nama Arab Santri @endslot
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

    @if(session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <strong>Hasil import:</strong>
            {{ $result['updated'] }} diperbarui · {{ $result['skipped'] }} dilewati (nama Arab kosong)
            @if(! empty($result['not_found']))
                · <span class="text-danger">{{ count($result['not_found']) }} NIS/NISN tidak ditemukan</span>
            @endif
            @if(! empty($result['invalid']))
                · <span class="text-danger">{{ count($result['invalid']) }} baris tidak valid</span>
            @endif
            @if(! empty($result['not_found']))
                <div class="small mt-1">Tidak ditemukan: <code>{{ implode(', ', array_slice($result['not_found'], 0, 30)) }}</code>@if(count($result['not_found']) > 30) …@endif</div>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-1 stat-label">Total Santri</p>
                    <h3 class="fw-bold mb-0">{{ number_format($stats['total']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-1 stat-label">Sudah Ada Nama Arab</p>
                    <h3 class="fw-bold text-success mb-0">{{ number_format($stats['filled']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-animate h-90">
                <div class="card-body py-3">
                    <p class="text-uppercase fw-medium text-muted mb-1 stat-label">Belum Ada Nama Arab</p>
                    <h3 class="fw-bold text-warning mb-0">{{ number_format($stats['missing']) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-file-excel-2-line text-success me-1"></i>Import dari Excel</h5>
                    <p class="text-muted mb-0 small">Pencocokan memakai <strong>NIS</strong> (fallback <strong>NISN</strong>) pada satuan pendidikan ini.</p>
                </div>
                <div class="card-body">
                    <ol class="small text-muted mb-3">
                        <li>Unduh template — kolom <code>nis</code>, <code>nisn</code>, <code>nama</code>, dan <code>nama_arab</code> sudah terisi data santri.</li>
                        <li>Isi kolom <code>nama_arab</code> (huruf Arab). Baris yang kosong akan dilewati.</li>
                        <li>Unggah kembali file tersebut.</li>
                    </ol>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a href="{{ route('user.students.import-nama-arab-template', ['userId' => $userId]) }}"
                           class="btn btn-outline-success">
                            <i class="ri-download-2-line me-1"></i>Unduh Template Excel
                        </a>
                    </div>

                    <form method="POST" action="{{ route('user.students.import-nama-arab-process', ['userId' => $userId]) }}"
                          enctype="multipart/form-data" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">File Excel (.xlsx/.xls)</label>
                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-upload-2-line me-1"></i>Import Nama Arab
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-magic-line text-primary me-1"></i>Isi Otomatis (Generator)
                        @if(config('arabic_names.driver') === 'ai')
                            <span class="badge bg-primary-subtle text-primary ms-1" style="font-size:10px;">AI</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:10px;">Kaidah Transliterasi</span>
                        @endif
                    </h5>
                    <p class="text-muted mb-0 small">Membuat nama Arab dari nama Latin mengikuti kaidah transliterasi Arab (fonetis, ejaan baku, pemisahan unsur nama). Hasil bersifat best-effort — mohon ditinjau.</p>
                </div>
                <div class="card-body">
                    <form id="generateStudentsForm" method="POST"
                          action="{{ route('user.students.import-nama-arab-generate', ['userId' => $userId]) }}">
                        @csrf
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="overwrite" value="1" id="overwriteStudents">
                            <label class="form-check-label small" for="overwriteStudents">Timpa nama Arab yang sudah ada</label>
                        </div>
                        <button type="button" class="btn btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#generateStudentsModal">
                            <i class="ri-magic-line me-1"></i>Generate untuk {{ number_format($stats['missing']) }} Santri
                        </button>
                    </form>

                    @if($missingStudents->isNotEmpty())
                        <hr>
                        <p class="small text-muted mb-2">Contoh yang belum terisi:</p>
                        <ul class="small mb-0">
                            @foreach($missingStudents as $student)
                                <li>{{ $student->name }} <span class="text-muted">({{ $student->nis ?: $student->nisn }})</span></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modal')
    <x-modal-confirm id="generateStudentsModal"
        icon="https://cdn.lordicon.com/lupuorrc.json"
        title="Buat Nama Arab Otomatis?"
        message="Sistem akan membuat nama Arab dari nama Latin memakai kaidah transliterasi Arab (fonetis, ejaan baku, pemisahan unsur nama). Hasil bersifat best-effort — mohon ditinjau di Data Santri."
        submit-label="Ya, Generate"
        submit-icon="ri-magic-line"
        submit-class="btn-primary"
        form-id="generateStudentsForm" />
@endsection
