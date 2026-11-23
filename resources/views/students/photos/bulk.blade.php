@extends('layouts.master')

@section('title', 'Upload Foto Masal Santri')

@section('css')
<style>
    .photo-drop-zone {
        border: 2px dashed #adb5bd;
        border-radius: 12px;
        padding: 2rem 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: all .2s;
        background: #f8fafc;
    }
    .photo-drop-zone:hover,
    .photo-drop-zone.dragover {
        border-color: #0d6efd;
        background: #e7f1ff;
    }
    .photo-drop-zone .icon { font-size: 2.25rem; color: #adb5bd; }
    .photo-drop-zone.dragover .icon { color: #0d6efd; }
    .photo-file-item {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .55rem .75rem;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        background: #f8fafc;
        margin-bottom: .4rem;
        font-size: .85rem;
    }
    .photo-file-item .file-name { flex: 1; word-break: break-all; }
    .guide-table th, .guide-table td { font-size: .8125rem; }
    .guide-table thead th { background: #1e3a5f; color: #fff; white-space: nowrap; }
</style>
@endsection

@section('content')
@php
    $bulkResult = session('photo_bulk_result');
@endphp

@component('components.breadcrumb')
    @slot('li_1') Data Santri @endslot
    @slot('li_2')
        <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Daftar Santri</a>
    @endslot
    @slot('title') Upload Foto Masal @endslot
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

@if($bulkResult)
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">Total File</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($bulkResult['total']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">Berhasil</p>
                    <h4 class="mb-0 fw-bold text-success">{{ number_format($bulkResult['success']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body py-3">
                    <p class="text-muted text-uppercase fw-semibold mb-1" style="font-size:.66rem;letter-spacing:.5px">Gagal / Tidak Cocok</p>
                    <h4 class="mb-0 fw-bold text-danger">{{ number_format($bulkResult['failed']) }}</h4>
                    <small class="text-muted">{{ number_format($bulkResult['unmatched']) }} file tidak menemukan NIS/NISN</small>
                </div>
            </div>
        </div>
    </div>

    @if(! empty($bulkResult['failures']))
        <div class="card mb-3 border-warning">
            <div class="card-header bg-warning-subtle border-bottom-dashed">
                <h6 class="mb-0"><i class="ri-error-warning-line me-1"></i>File yang Tidak Diproses</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Nama File</th><th>Alasan</th></tr>
                        </thead>
                        <tbody>
                            @foreach($bulkResult['failures'] as $failure)
                                <tr>
                                    <td><code>{{ $failure['file'] }}</code></td>
                                    <td class="text-muted small">{{ $failure['reason'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($bulkResult['failed'] > count($bulkResult['failures']))
                    <div class="p-2 small text-muted">Menampilkan 100 kegagalan pertama dari {{ number_format($bulkResult['failed']) }} kegagalan.</div>
                @endif
            </div>
        </div>
    @endif
@endif

<div class="row g-3">
    <div class="col-xl-8">
        <form method="POST" action="{{ route('user.students.photos.bulk-process', ['userId' => $userId]) }}" enctype="multipart/form-data" id="bulkPhotoForm">
            @csrf

            <div class="card mb-3">
                <div class="card-body py-2">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="flex-grow-1">
                            @if($schools->isNotEmpty())
                                <label class="form-label small text-muted mb-1">Satuan Pendidikan</label>
                                <select name="school_id" class="form-select form-select-sm" required>
                                    <option value="">Pilih sekolah...</option>
                                    @foreach($schools as $selectableSchool)
                                        <option value="{{ $selectableSchool->id }}" {{ (string) old('school_id', $schoolId) === (string) $selectableSchool->id ? 'selected' : '' }}>
                                            {{ $selectableSchool->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="text-muted small mt-1">Pilih sekolah agar foto dicocokkan ke santri yang benar.</div>
                            @else
                                <div class="fw-semibold">{{ $school?->name ?? 'Satuan Pendidikan' }}</div>
                                <div class="text-muted small">{{ number_format($studentCount) }} santri aktif tersedia</div>
                            @endif
                        </div>
                        <span class="badge bg-primary-subtle text-primary">Nama file = NIS / NISN</span>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="ri-image-add-line me-1"></i>Pilih Banyak Foto</h6>
                    <small class="text-muted">Bisa pilih ratusan file sekaligus. Nama file harus sama dengan NIS atau NISN santri.</small>
                </div>
                <div class="card-body">
                    <div class="photo-drop-zone" id="photoDropZone" onclick="document.getElementById('photoFiles').click()">
                        <div class="icon mb-2"><i class="ri-upload-cloud-2-line"></i></div>
                        <div class="fw-semibold text-dark">Drop banyak foto di sini</div>
                        <div class="text-muted small mb-2">atau klik untuk memilih file</div>
                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <span class="badge bg-light text-dark">.jpg</span>
                            <span class="badge bg-light text-dark">.jpeg</span>
                            <span class="badge bg-light text-dark">.png</span>
                            <span class="badge bg-light text-dark">.webp</span>
                            <span class="badge bg-light text-dark">maks 2 MB/foto</span>
                        </div>
                        <div id="photoFileCount" class="mt-2 text-success fw-medium small" style="display:none"></div>
                    </div>
                    <input type="file" name="photos[]" id="photoFiles" class="d-none" accept=".jpg,.jpeg,.png,.webp" multiple onchange="renderPhotoList(this.files)">
                    @error('photos')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    @error('photos.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                    <div id="photoPreviewWrap" class="mt-3" style="display:none">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small fw-semibold text-muted" id="photoPreviewTitle"></span>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearPhotoFiles()">
                                <i class="ri-delete-bin-line me-1"></i>Hapus Semua
                            </button>
                        </div>
                        <div id="photoPreviewList" style="max-height:280px;overflow:auto"></div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="ri-file-zip-line me-1"></i>Atau Upload ZIP</h6>
                    <small class="text-muted">Cocok untuk banyak foto. Nama file di dalam ZIP tetap NIS/NISN.</small>
                </div>
                <div class="card-body">
                    <input type="file" name="archive" id="archiveFile" class="form-control" accept=".zip">
                    @error('archive')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    <div class="form-text">Maksimal 100 MB, maksimal 1.000 file gambar di dalam ZIP.</div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-4">
                <a href="{{ route('user.students.index', ['userId' => $userId]) }}" class="btn btn-light">
                    <i class="ri-arrow-left-line me-1"></i>Batal
                </a>
                <button type="submit" class="btn btn-success" id="btnUploadPhotos">
                    <i class="ri-upload-cloud-line me-1"></i>Proses Upload Foto
                </button>
            </div>
        </form>
    </div>

    <div class="col-xl-4">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="ri-information-line me-1"></i>Aturan Pencocokan</h6></div>
            <div class="card-body small text-muted">
                <ol class="ps-3 mb-0">
                    <li class="mb-2">Nama file harus sama dengan <strong>NIS</strong> atau <strong>NISN</strong> santri.</li>
                    <li class="mb-2">Contoh: NISN <code>0012345678</code> → file <code>0012345678.jpg</code>.</li>
                    <li class="mb-2">Ekstensi yang didukung: JPG, JPEG, PNG, WEBP.</li>
                    <li class="mb-2">Ukuran maksimal 2 MB per foto.</li>
                    <li class="mb-2">Foto lama akan diganti dan file lama dihapus.</li>
                    <li>File yang tidak cocok tidak akan mengubah data santri dan akan ditampilkan di laporan.</li>
                </ol>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h6 class="mb-0">Contoh Nama File</h6></div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0 guide-table">
                    <thead><tr><th>Nama File</th><th>Hasil</th></tr></thead>
                    <tbody>
                        <tr><td><code>0012345678.jpg</code></td><td><span class="badge bg-success-subtle text-success">Cocok NISN</span></td></tr>
                        <tr><td><code>2024001.png</code></td><td><span class="badge bg-success-subtle text-success">Cocok NIS</span></td></tr>
                        <tr><td><code>foto-santri.jpg</code></td><td><span class="badge bg-danger-subtle text-danger">Tidak cocok</span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    const photoInput = document.getElementById('photoFiles');
    const photoDrop = document.getElementById('photoDropZone');
    const photoPreviewWrap = document.getElementById('photoPreviewWrap');
    const photoPreviewList = document.getElementById('photoPreviewList');
    const photoPreviewTitle = document.getElementById('photoPreviewTitle');
    const photoFileCount = document.getElementById('photoFileCount');

    ['dragenter', 'dragover'].forEach(function (evt) {
        photoDrop.addEventListener(evt, function (e) {
            e.preventDefault();
            photoDrop.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(function (evt) {
        photoDrop.addEventListener(evt, function (e) {
            e.preventDefault();
            photoDrop.classList.remove('dragover');
        });
    });

    photoDrop.addEventListener('drop', function (e) {
        const files = Array.prototype.slice.call(e.dataTransfer.files).filter(function (file) {
            return ['image/jpeg', 'image/png', 'image/webp'].includes(file.type);
        });
        if (! files.length) return;
        const dt = new DataTransfer();
        files.forEach(function (file) { dt.items.add(file); });
        photoInput.files = dt.files;
        renderPhotoList(photoInput.files);
    });

    function renderPhotoList(files) {
        const list = Array.prototype.slice.call(files);
        if (! list.length) {
            photoPreviewWrap.style.display = 'none';
            photoFileCount.style.display = 'none';
            return;
        }

        photoPreviewWrap.style.display = 'block';
        photoFileCount.style.display = 'block';
        photoFileCount.textContent = list.length + ' foto dipilih';
        photoPreviewTitle.textContent = list.length + ' foto akan diproses';

        photoPreviewList.innerHTML = list.map(function (file) {
            return '<div class="photo-file-item">' +
                '<i class="ri-image-line text-success"></i>' +
                '<span class="file-name">' + escapeHtml(file.name) + '</span>' +
                '<span class="text-muted small">' + (file.size / 1024).toFixed(1) + ' KB</span>' +
                '</div>';
        }).join('');
    }

    function clearPhotoFiles() {
        photoInput.value = '';
        renderPhotoList([]);
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
        });
    }

    document.getElementById('bulkPhotoForm').addEventListener('submit', function () {
        const button = document.getElementById('btnUploadPhotos');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';
    });
</script>
@endsection
