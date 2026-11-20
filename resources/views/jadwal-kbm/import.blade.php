@extends('layouts.master')

@section('title', 'Import Jadwal Pelajaran')

@push('css')
@include('kurikulum._styles')
<style>
    .import-ref-table { font-size: .82rem; }
    .import-ref-table th { white-space: nowrap; }
    .import-drop { border: 1px dashed #cbd5e1; border-radius: .75rem; padding: 1.25rem; background: #f8fafc; }
</style>
@endpush

@section('content')
@php $userId = $userId ?? auth()->id(); @endphp

@component('components.breadcrumb')
    @slot('li_1') <a href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}">Jadwal KBM</a> @endslot
    @slot('li_2') Import @endslot
    @slot('title') Import Jadwal Pelajaran @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('jadwal_import_errors') && count(session('jadwal_import_errors')))
    <div class="alert alert-warning">
        <h6 class="fw-semibold mb-2"><i class="ri-error-warning-line me-1"></i>Baris yang tidak diproses</h6>
        <ul class="mb-0 small">
            @foreach(session('jadwal_import_errors') as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(session('jadwal_import_warnings') && count(session('jadwal_import_warnings')))
    <div class="alert alert-info">
        <h6 class="fw-semibold mb-2"><i class="ri-information-line me-1"></i>Catatan import</h6>
        <ul class="mb-0 small">
            @foreach(session('jadwal_import_warnings') as $warning)
                <li>{{ $warning }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-upload-2-line text-primary me-1"></i>Import Jadwal</h5>
                    <p class="text-muted mb-0 small">Satu baris = satu pertemuan jadwal.</p>
                </div>
                <a href="{{ route('user.jadwal-kbm.import.template', ['userId' => $userId]) }}"
                   class="btn btn-sm btn-soft-success">
                    <i class="ri-download-2-line me-1"></i>Unduh Template
                </a>
            </div>
            <div class="card-body">
                <div class="alert alert-info small">
                    <div class="fw-semibold mb-1"><i class="ri-information-line me-1"></i>Konteks import</div>
                    <div>{{ $school->name }} · {{ $activeAy->name }} ({{ ucfirst($activeAy->semester ?? '-') }})</div>
                    <div class="mt-1">
                        {{ $studyGroups->count() }} rombel · {{ $subjects->count() }} mapel · {{ $teachers->count() }} guru.
                    </div>
                </div>

                <form method="POST" action="{{ route('user.jadwal-kbm.import.store', ['userId' => $userId]) }}"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="import-drop mb-3">
                        <label class="form-label fw-semibold">File jadwal <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror"
                               accept=".xlsx,.xls,.csv" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Format .xlsx, .xls, atau .csv. Maksimal 10 MB. Disarankan memakai template yang disediakan.</div>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="update_existing"
                               name="update_existing" value="1" checked>
                        <label class="form-check-label" for="update_existing">
                            Perbarui jadwal yang sudah ada pada rombel + hari + jam yang sama
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="default_active"
                               name="default_active" value="1" checked>
                        <label class="form-check-label" for="default_active">
                            Jadwal baru langsung aktif (jika kolom Aktif kosong)
                        </label>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-upload-cloud-2-line me-1"></i>Proses Import
                        </button>
                        <a href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}" class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i>Kembali ke Jadwal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header border-bottom-dashed">
                <h5 class="card-title mb-0"><i class="ri-book-open-line text-primary me-1"></i>Petunjuk Singkat</h5>
            </div>
            <div class="card-body">
                <ol class="small text-muted ps-3 mb-3">
                    <li>Unduh template, lalu isi sheet <strong>JADWAL</strong>.</li>
                    <li>Gunakan nama/kode dari sheet <strong>REF_ROMBEL</strong>, <strong>REF_MAPEL</strong>, dan <strong>REF_GURU</strong>.</li>
                    <li>Hari boleh angka 1–6 atau nama hari. Jam Ke mengikuti <strong>REF_JAM</strong>.</li>
                    <li>Jam istirahat tidak boleh dipakai. Baris yang salah akan dilaporkan dan dilewati.</li>
                    <li>Jika kolom <strong>Aktif</strong> kosong, jadwal baru mengikuti pilihan default di samping.</li>
                </ol>
                <div class="border rounded p-2 bg-light small">
                    <div class="fw-semibold mb-1">Kolom wajib</div>
                    <code>Rombel</code>, <code>Hari</code>, <code>Jam Ke</code>, dan salah satu dari <code>Kode Mapel</code> / <code>Mapel</code>.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header border-bottom-dashed">
        <h5 class="card-title mb-0"><i class="ri-table-line text-primary me-1"></i>Referensi Data</h5>
        <p class="text-muted mb-0 small">Gunakan data ini saat mengisi template. Data lengkap juga tersedia di sheet referensi pada file template.</p>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#ref-rombel" type="button">Rombel</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ref-mapel" type="button">Mapel</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ref-guru" type="button">Guru</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="ref-rombel">
                <div class="table-responsive" style="max-height:320px;overflow-y:auto;">
                    <table class="table table-sm table-bordered import-ref-table mb-0">
                        <thead class="table-light"><tr><th>Rombel</th><th>Kode</th><th>Ruang</th></tr></thead>
                        <tbody>
                            @forelse($studyGroups as $group)
                                <tr><td>{{ $group->name }}</td><td>{{ $group->code ?: '—' }}</td><td>{{ $group->room ?: '—' }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="text-muted text-center">Belum ada rombel aktif.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="ref-mapel">
                <div class="table-responsive" style="max-height:320px;overflow-y:auto;">
                    <table class="table table-sm table-bordered import-ref-table mb-0">
                        <thead class="table-light"><tr><th>Kode</th><th>Mapel</th></tr></thead>
                        <tbody>
                            @forelse($subjects as $subject)
                                <tr><td>{{ $subject->code ?: '—' }}</td><td>{{ $subject->name }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-muted text-center">Belum ada mapel aktif.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="ref-guru">
                <div class="table-responsive" style="max-height:320px;overflow-y:auto;">
                    <table class="table table-sm table-bordered import-ref-table mb-0">
                        <thead class="table-light"><tr><th>Nama</th><th>Email / Username</th></tr></thead>
                        <tbody>
                            @forelse($teachers as $teacher)
                                <tr>
                                    <td>{{ $teacher->name }}</td>
                                    <td class="text-muted">{{ $teacher->email ?: $teacher->username ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted text-center">Belum ada data guru.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
