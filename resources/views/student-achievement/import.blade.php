@extends('layouts.master')
@section('title') Import Massal — {{ $typeLabel }} @endsection

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .drop-zone {
        border: 2px dashed #adb5bd;
        border-radius: 12px;
        padding: 2.5rem 2rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        background: #f8fafc;
    }
    .drop-zone:hover, .drop-zone.dragover {
        border-color: #0d6efd;
        background: #e7f1ff;
    }
    .drop-zone .icon { font-size: 2.5rem; color: #adb5bd; }
    .drop-zone.dragover .icon { color: #0d6efd; }

    .file-list-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.6rem 0.75rem;
        border-radius: 8px;
        margin-bottom: 0.4rem;
        background: #f8fafc;
        border: 1px solid #e9ecef;
    }
    .file-list-item .file-icon { font-size: 1.3rem; }
    .file-list-item .file-name { flex: 1; font-size: 0.875rem; }
    .file-list-item .remove-btn {
        background: none; border: none; color: #adb5bd; cursor: pointer;
        padding: 0; line-height: 1;
    }
    .file-list-item .remove-btn:hover { color: #dc3545; }

    .file-badge { font-size: 0.75rem; }
    .matched-badge { background: #d1fae5; color: #065f46; }
    .unmatched-badge { background: #fef9c3; color: #854d0e; }

    .guide-table th, .guide-table td { font-size: 0.8125rem; }
    .guide-table thead th {
        background: #1e3a5f;
        color: white;
        white-space: nowrap;
    }
</style>
@endsection

@section('content')
@php
$tabs = ['akademik'=>'Prestasi Akademik','quran'=>'Hafalan Qur\'an','hadits'=>'Hafalan Hadits'];
$typeParam = $achievementType;
@endphp

@component('components.breadcrumb')
    @slot('li_1') Akademik @endslot
    @slot('li_2')
        <a href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => $typeParam]) }}">
            {{ $typeLabel }}
        </a>
    @endslot
    @slot('title') Import Massal @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
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

{{-- TYPE TABS --}}
<ul class="nav nav-tabs mb-3" role="tablist">
    @foreach($tabs as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $typeParam === $key ? 'active' : '' }}"
               href="{{ route('user.student-achievement.import-form', ['userId' => $userId, 'type' => $key]) }}">
                {{ $label }}
            </a>
        </li>
    @endforeach
</ul>

<div class="row">
    {{-- LEFT: Upload Form --}}
    <div class="col-xl-8">
        <form method="POST"
              action="{{ route('user.student-achievement.import-process', ['userId' => $userId, 'type' => $typeParam]) }}"
              enctype="multipart/form-data" id="importForm">
            @csrf
            <input type="hidden" name="type" value="{{ $typeParam }}">

            {{-- Academic Year --}}
            <div class="card mb-3">
                <div class="card-body py-2">
                    <div class="row align-items-end">
                        <div class="col-md-8">
                            <label class="form-label small text-muted mb-0">Tahun Ajaran</label>
                            <select name="academic_year_id" class="form-select form-select-sm">
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ $activeYear && $ay->id === $activeYear->id ? 'selected' : '' }}>
                                        {{ $ay->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small">{{ $studentCount }} siswa aktif tersedia</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Excel Drop Zone --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="ri-file-excel-2-line me-1"></i> File Excel (.xlsx, .xls, .csv)</h6>
                </div>
                <div class="card-body">
                    <div class="drop-zone" id="excelDropZone" onclick="document.getElementById('excelFile').click()">
                        <div class="icon mb-2">
                            <i class="ri-upload-cloud-2-line"></i>
                        </div>
                        <div class="fw-semibold text-dark">Drop file Excel di sini</div>
                        <div class="text-muted small mb-2">atau klik untuk browse</div>
                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <span class="badge bg-light text-dark">.xlsx</span>
                            <span class="badge bg-light text-dark">.xls</span>
                            <span class="badge bg-light text-dark">.csv</span>
                        </div>
                        <div id="excelFileName" class="mt-2 text-primary fw-medium small" style="display:none"></div>
                    </div>
                    <input type="file" name="file" id="excelFile" class="d-none"
                           accept=".xlsx,.xls,.csv" required onchange="showExcelFileName(this)">
                    @error('file')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Images Drop Zone --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="ri-image-add-line me-1"></i> Gambar Piagam / Sertifikat
                    </h6>
                    <div class="text-muted small mt-1">
                        <i class="ri-information-line"></i>
                        Nama file = NISN siswa. Contoh: <code>0012345678.jpg</code>
                    </div>
                </div>
                <div class="card-body">
                    <div class="drop-zone" id="imageDropZone" onclick="document.getElementById('imageFiles').click()">
                        <div class="icon mb-2"><i class="ri-image-line"></i></div>
                        <div class="fw-semibold text-dark">Drop file gambar piagam di sini</div>
                        <div class="text-muted small mb-2">Multiple file: JPG, PNG, PDF</div>
                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <span class="badge bg-light text-dark">.jpg</span>
                            <span class="badge bg-light text-dark">.jpeg</span>
                            <span class="badge bg-light text-dark">.png</span>
                            <span class="badge bg-light text-dark">.pdf</span>
                        </div>
                        <div id="imageCount" class="mt-2 text-success fw-medium small" style="display:none"></div>
                    </div>
                    <input type="file" name="images[]" id="imageFiles" class="d-none"
                           accept=".jpg,.jpeg,.png,.pdf" multiple onchange="showImageFiles(this)">

                    {{-- Image Preview List --}}
                    <div id="imagePreviewList" class="mt-3" style="display:none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold text-muted" id="imageListTitle"></span>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="clearImages()">
                                <i class="ri-delete-bin-line me-1"></i> Hapus Semua
                            </button>
                        </div>
                        <div id="imageList"></div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-4">
                <a href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => $typeParam]) }}"
                   class="btn btn-light">
                    <i class="ri-arrow-left-line me-1"></i> Batal
                </a>
                <button type="submit" class="btn btn-success" id="btnImport">
                    <i class="ri-upload-cloud-line me-1"></i> Proses Import
                </button>
            </div>
        </form>
    </div>

    {{-- RIGHT: Guide --}}
    <div class="col-xl-4">
        {{-- Download Template --}}
        <div class="card mb-3">
            <div class="card-header bg-primary-subtle">
                <h6 class="mb-0 text-primary">
                    <i class="ri-download-line me-1"></i> Template Import
                </h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">
                    Unduh template Excel yang sudah disesuaikan dengan kolom untuk
                    <strong>{{ $typeLabel }}</strong>.
                </p>
                <a href="{{ route('user.student-achievement.template', ['userId' => $userId, 'type' => $typeParam]) }}"
                   class="btn btn-primary w-100 btn-sm">
                    <i class="ri-download-line me-1"></i> Download Template
                </a>
            </div>
        </div>

        {{-- Column Guide --}}
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Petunjuk Kolom</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0 guide-table">
                        <thead>
                            <tr>
                                <th>Kolom</th>
                                <th>Wajib</th>
                                <th>Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>nisn</code></td>
                                <td><span class="badge bg-danger">Ya</span></td>
                                <td>NISN siswa yang mengikuti lomba</td>
                            </tr>
                            <tr>
                                <td><code>nama_lomba</code></td>
                                <td><span class="badge bg-danger">Ya</span></td>
                                <td>Nama lomba/kompetisi/kejuaraan</td>
                            </tr>
                            <tr>
                                <td><code>penyelenggara</code></td>
                                <td><span class="badge bg-secondary">Tidak</span></td>
                                <td>Instansi penyeleggara</td>
                            </tr>
                            <tr>
                                <td><code>tingkat</code></td>
                                <td><span class="badge bg-secondary">Tidak</span></td>
                                <td>Internal / Kecamatan / Kabupaten/Kota / Provinsi / Nasional / Internasional</td>
                            </tr>
                            <tr>
                                <td><code>peringkat</code></td>
                                <td><span class="badge bg-secondary">Tidak</span></td>
                                <td>Juara 1 / Juara 2 / Juara 3 / Harapan 1 / Peserta / dll</td>
                            </tr>
                            <tr>
                                <td><code>tanggal</code></td>
                                <td><span class="badge bg-secondary">Tidak</span></td>
                                <td>Format DD/MM/YYYY (contoh: 15/03/2024)</td>
                            </tr>
                            <tr>
                                <td><code>lokasi</code></td>
                                <td><span class="badge bg-secondary">Tidak</span></td>
                                <td>Tempat kegiatan</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- File Matching Info --}}
        <div class="card">
            <div class="card-header"><h6 class="mb-0">⚙️ Cara Pencocokan File</h6></div>
            <div class="card-body small">
                <ol class="mb-0 ps-3 text-muted">
                    <li class="mb-2">
                        User upload file Excel + banyak file gambar piagam sekaligus.
                    </li>
                    <li class="mb-2">
                        Sistem mencari siswa berdasarkan kolom <code>nisn</code> di Excel.
                    </li>
                    <li class="mb-2">
                        File gambar yang namanya = NISN siswa akan langsung disimpan
                        sebagai piagam (case-insensitive, extens fleksibel).
                    </li>
                    <li class="mb-2">
                        Contoh: file <code>0012345678.jpg</code> otomatis cocok dengan
                        siswa NISN <code>0012345678</code>.
                    </li>
                    <li>
                        Baris yang NISN-nya tidak ditemukan akan tetap diimport TANPA piagam.
                    </li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    // ── Excel drag/drop ─────────────────────────────────────────────────
    const excelDrop = document.getElementById('excelDropZone');
    ['dragenter','dragover'].forEach(function(evt) {
        excelDrop.addEventListener(evt, function(e) { e.preventDefault(); excelDrop.classList.add('dragover'); });
    });
    ['dragleave','drop'].forEach(function(evt) {
        excelDrop.addEventListener(evt, function(e) { e.preventDefault(); excelDrop.classList.remove('dragover'); });
    });
    excelDrop.addEventListener('drop', function(e) {
        var f = e.dataTransfer.files[0];
        if (f) {
            var dt = new DataTransfer();
            dt.items.add(f);
            document.getElementById('excelFile').files = dt.files;
            showExcelFileName(document.getElementById('excelFile'));
        }
    });

    // ── Image drag/drop ──────────────────────────────────────────────────
    var imgDrop = document.getElementById('imageDropZone');
    ['dragenter','dragover'].forEach(function(evt) {
        imgDrop.addEventListener(evt, function(e) { e.preventDefault(); imgDrop.classList.add('dragover'); });
    });
    ['dragleave','drop'].forEach(function(evt) {
        imgDrop.addEventListener(evt, function(e) { e.preventDefault(); imgDrop.classList.remove('dragover'); });
    });
    imgDrop.addEventListener('drop', function(e) {
        var files = Array.prototype.slice.call(e.dataTransfer.files).filter(function(f) {
            return ['image/jpeg','image/png','application/pdf'].indexOf(f.type) !== -1;
        });
        if (files.length) {
            var dt = new DataTransfer();
            files.forEach(function(f) { dt.items.add(f); });
            document.getElementById('imageFiles').files = dt.files;
            showImageFiles(document.getElementById('imageFiles'));
        }
    });

    function showExcelFileName(input) {
        var f = input.files[0];
        var el = document.getElementById('excelFileName');
        if (f) {
            el.textContent = '\u2713 ' + f.name + ' (' + (f.size/1024).toFixed(1) + ' KB)';
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    }

    function showImageFiles(input) {
        var files = Array.prototype.slice.call(input.files);
        var container = document.getElementById('imagePreviewList');
        var listEl = document.getElementById('imageList');
        var countEl = document.getElementById('imageCount');
        var titleEl = document.getElementById('imageListTitle');

        if (files.length === 0) { container.style.display = 'none'; return; }

        countEl.textContent = files.length + ' file dipilih';
        countEl.style.display = 'block';
        titleEl.textContent = files.length + ' file piagam';
        container.style.display = 'block';

        var html = files.map(function(f) {
            var icon = f.type.indexOf('pdf') !== -1 ? 'ri-file-pdf-2-line text-danger' : 'ri-image-line text-success';
            return '<div class="file-list-item">' +
                '<i class="file-icon ' + icon + '"></i>' +
                '<span class="file-name">' + f.name + '</span>' +
                '<span class="text-muted small">' + (f.size/1024).toFixed(0) + ' KB</span>' +
            '</div>';
        }).join('');
        listEl.innerHTML = html;
    }

    function clearImages() {
        document.getElementById('imageFiles').value = '';
        document.getElementById('imagePreviewList').style.display = 'none';
        document.getElementById('imageCount').style.display = 'none';
    }

    // Submit loading state
    var form = document.getElementById('importForm');
    if (form) {
        form.addEventListener('submit', function() {
            var btn = document.getElementById('btnImport');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="ri-loader-4-line me-1"></i> Memproses...';
            }
        });
    }
</script>
@endsection