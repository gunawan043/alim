@extends('layouts.master')
@section('title', $meta['title'])
@section('css')
    <style>
        .import-hero {
            background: linear-gradient(135deg, #eef5ff 0%, #f4fbff 55%, #eefaf4 100%);
            border: 1px solid #dbeafe;
            border-radius: 20px;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        .import-hero::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            right: -80px;
            top: -100px;
            border-radius: 50%;
            background: rgba(13, 110, 253, .08);
        }
        .import-hero-icon {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: #fff;
            font-size: 1.8rem;
            box-shadow: 0 10px 24px rgba(13, 110, 253, .25);
            flex-shrink: 0;
        }
        .import-dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            background: #fbfdff;
            padding: 2rem 1.25rem;
            text-align: center;
            cursor: pointer;
            transition: all .25s ease;
        }
        .import-dropzone:hover,
        .import-dropzone.dragover {
            border-color: #0d6efd;
            background: #f0f7ff;
            transform: translateY(-2px);
        }
        .import-dropzone .drop-icon {
            width: 62px;
            height: 62px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            background: #e7f1ff;
            color: #0d6efd;
            font-size: 1.7rem;
        }
        .step-card {
            border: 1px solid #e9ecef;
            border-radius: 14px;
            padding: .9rem 1rem;
            display: flex;
            gap: .85rem;
            align-items: flex-start;
            background: #fff;
            height: 100%;
        }
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e7f1ff;
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }
        .import-meta-card {
            border: 1px solid #e9ecef;
            border-radius: 16px;
            background: #fff;
        }
        .import-meta-card .list-group-item {
            border-color: #f1f3f5;
            padding-left: 0;
            padding-right: 0;
        }
        .import-file-chip {
            display: flex;
            align-items: center;
            gap: .65rem;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            border-radius: 12px;
            padding: .75rem .9rem;
        }
        .import-result-card {
            border: 1px solid #e9ecef;
            border-radius: 16px;
            overflow: hidden;
        }
        .import-result-card .card-header {
            background: #f8fafc;
        }
        .import-progress-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(15, 23, 42, .55);
            align-items: center;
            justify-content: center;
        }
        .import-progress-overlay.show { display: flex; }
        .import-progress-card {
            width: min(420px, 90vw);
            background: #fff;
            border-radius: 18px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
        }
        @media (max-width: 767.98px) {
            .import-hero { padding: 1.25rem; }
            .import-hero-icon { width: 52px; height: 52px; font-size: 1.4rem; }
        }
    </style>
@endsection
@section('content')
    @php $userId = $userId ?? auth()->id(); @endphp
    @include('perpustakaan._styles')
    @component('components.breadcrumb') @slot('li_1') Perpustakaan @endslot @slot('li_2') Import Data @endslot @slot('title') {{ $meta['title'] }} @endslot @endcomponent

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show"><i class="ri-error-warning-line me-1"></i>{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="import-hero mb-4">
        <div class="d-flex flex-wrap align-items-center gap-3 position-relative" style="z-index:1;">
            <div class="import-hero-icon"><i class="{{ $meta['icon'] }}"></i></div>
            <div class="flex-grow-1">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h4 class="mb-0 fw-bold">{{ $meta['title'] }}</h4>
                    <span class="badge bg-primary-subtle text-primary">Excel / CSV</span>
                    <span class="badge bg-success-subtle text-success">Template tersedia</span>
                </div>
                <p class="text-muted mb-0">{{ $meta['description'] }}</p>
            </div>
            <a href="{{ route($meta['template'], ['userId' => $userId]) }}" class="btn btn-success">
                <i class="ri-download-2-line me-1"></i>Unduh Template
            </a>
        </div>
    </div>

    @if(session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="row g-3 mb-4 library-stats">
            @foreach([['Baris Dibaca', $result['total'], 'ri-file-list-3-line', 'primary', session('import_unit') ?? 'File import'], ['Berhasil', $result['success'], 'ri-checkbox-circle-line', 'success', 'Data masuk ke database'], ['Gagal', count($result['failed']), 'ri-error-warning-line', 'danger', 'Baris dilewati, data lain tetap diproses']] as [$label, $value, $icon, $color, $hint])
                @include('perpustakaan._stat-card', compact('label', 'value', 'icon', 'color', 'hint'))
            @endforeach
        </div>
        @if(count($result['failed']))
            <div class="card import-result-card mb-4 border-danger-subtle">
                <div class="card-header border-bottom-dashed d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0 text-danger"><i class="ri-error-warning-line me-1"></i>Baris yang Gagal</h5>
                    <span class="badge bg-danger-subtle text-danger">{{ count($result['failed']) }} baris</span>
                </div>
                <div class="table-container">
                    <table class="table table-sm align-middle mb-0 table-freeze">
                        <thead class="table-light"><tr><th style="width:90px">Baris</th><th>Alasan</th></tr></thead>
                        <tbody>
                        @foreach($result['failed'] as $failure)
                            <tr><td><span class="badge bg-light text-dark">#{{ $failure['row'] }}</span></td><td class="text-danger small">{{ $failure['reason'] }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    <form method="POST" action="{{ route($meta['action'], ['userId' => $userId]) }}" enctype="multipart/form-data" id="libraryImportForm">
        @csrf
        <div class="card mb-4">
            <div class="card-header border-bottom-dashed d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-0"><i class="ri-upload-cloud-2-line text-primary me-1"></i>Unggah File Import</h5>
                    <p class="text-muted mb-0 small">Pilih unit, unduh template, lalu unggah file yang sudah diisi.</p>
                </div>
                <a href="{{ url()->previous() }}" class="btn btn-light btn-sm"><i class="ri-arrow-left-line me-1"></i>Kembali</a>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Unit Perpustakaan <span class="text-danger">*</span></label>
                            <select name="library_unit_id" class="form-select" required>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" @selected(old('library_unit_id', request('unit_id')) === $unit->id)>{{ $unit->name }} ({{ $unit->code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="import-dropzone" id="importDropzone" onclick="document.getElementById('importFile').click()">
                            <input type="file" id="importFile" name="file" class="d-none" accept=".xlsx,.xls,.csv,.txt" required>
                            <div class="drop-icon"><i class="ri-file-upload-line"></i></div>
                            <h6 class="mb-1">Klik atau seret file ke sini</h6>
                            <p class="text-muted small mb-0">Format .xlsx, .xls, .csv, atau .txt · Maksimal 10 MB</p>
                            <p class="mt-2 mb-0 fw-semibold" id="importFileName" style="display:none;"></p>
                        </div>

                        <div id="importFileInfo" class="import-file-chip mt-3" style="display:none;">
                            <i class="ri-file-excel-2-fill fs-4 text-success"></i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold" id="importFileLabel">—</div>
                                <div class="text-muted small" id="importFileSize">—</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="resetImportFile()"><i class="ri-close-line"></i></button>
                        </div>

                        <div class="alert alert-info py-2 px-3 mt-3 mb-0 small">
                            <i class="ri-information-line me-1"></i>Baris yang gagal tidak menghentikan baris lain. Hasil per baris akan ditampilkan setelah proses selesai.
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="step-card mb-3">
                            <div class="step-number">1</div>
                            <div><div class="fw-semibold">Pilih unit</div><div class="text-muted small">Data akan masuk ke unit yang dipilih.</div></div>
                        </div>
                        <div class="step-card mb-3">
                            <div class="step-number">2</div>
                            <div><div class="fw-semibold">Isi template</div><div class="text-muted small">Jangan mengubah nama kolom.</div></div>
                        </div>
                        <div class="step-card mb-3">
                            <div class="step-number">3</div>
                            <div><div class="fw-semibold">Upload &amp; import</div><div class="text-muted small">Periksa ringkasan hasil setelah proses.</div></div>
                        </div>
                        <a href="{{ route($meta['template'], ['userId' => $userId]) }}" class="btn btn-outline-success w-100"><i class="ri-file-download-line me-1"></i>Template Excel</a>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="text-muted small"><i class="ri-shield-check-line me-1"></i>Format file divalidasi sebelum diproses.</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light" onclick="resetImportFile()">Reset</button>
                    <button type="submit" class="btn btn-primary" id="importSubmit" disabled><i class="ri-upload-cloud-2-line me-1"></i>Mulai Import</button>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card import-meta-card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-table-line text-primary me-1"></i>Panduan Kolom</h5></div>
                <div class="table-container">
                    <table class="table table-sm align-middle mb-0 table-freeze">
                        <thead class="table-light"><tr><th>Kolom</th><th>Keterangan</th><th>Contoh</th></tr></thead>
                        <tbody>
                        @foreach($meta['columns'] as $column)
                            <tr><td><code>{{ $column[0] }}</code></td><td>{{ $column[1] }}</td><td class="text-muted small">{{ $column[2] }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card import-meta-card h-100">
                <div class="card-header border-bottom-dashed"><h5 class="card-title mb-0"><i class="ri-lightbulb-line text-warning me-1"></i>Tips Import</h5></div>
                <div class="card-body">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item"><i class="ri-check-line text-success me-1"></i>Gunakan template resmi agar nama kolom sesuai.</li>
                        <li class="list-group-item"><i class="ri-check-line text-success me-1"></i>Baris kosong akan dilewati otomatis.</li>
                        <li class="list-group-item"><i class="ri-check-line text-success me-1"></i>Data duplikat dilaporkan tanpa menghentikan baris lain.</li>
                        <li class="list-group-item"><i class="ri-check-line text-success me-1"></i>Periksa kembali unit sebelum menekan Mulai Import.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="import-progress-overlay" id="importProgressOverlay">
        <div class="import-progress-card">
            <div class="spinner-border text-primary" style="width:3rem;height:3rem;" role="status"></div>
            <h5 class="mt-3 mb-1">Sedang mengimport...</h5>
            <p class="text-muted small mb-0">Mohon tunggu, data sedang diproses per baris.</p>
        </div>
    </div>
@endsection
@section('script')
<script>
    const importInput = document.getElementById('importFile');
    const importDropzone = document.getElementById('importDropzone');
    const importFileInfo = document.getElementById('importFileInfo');
    const importFileName = document.getElementById('importFileName');
    const importFileLabel = document.getElementById('importFileLabel');
    const importFileSize = document.getElementById('importFileSize');
    const importSubmit = document.getElementById('importSubmit');

    function formatBytes(bytes) {
        if (!bytes) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(i ? 1 : 0) + ' ' + units[i];
    }
    function showImportFile(file) {
        if (!file) return;
        importFileName.textContent = file.name;
        importFileName.style.display = 'block';
        importFileLabel.textContent = file.name;
        importFileSize.textContent = formatBytes(file.size);
        importFileInfo.style.display = 'flex';
        importSubmit.disabled = false;
    }
    function resetImportFile() {
        if (importInput) importInput.value = '';
        importFileName.textContent = '';
        importFileName.style.display = 'none';
        importFileInfo.style.display = 'none';
        importSubmit.disabled = true;
    }
    importInput?.addEventListener('change', function () { showImportFile(this.files[0]); });
    ['dragenter', 'dragover'].forEach(function (eventName) {
        importDropzone?.addEventListener(eventName, function (event) {
            event.preventDefault();
            importDropzone.classList.add('dragover');
        });
    });
    ['dragleave', 'drop'].forEach(function (eventName) {
        importDropzone?.addEventListener(eventName, function (event) {
            event.preventDefault();
            importDropzone.classList.remove('dragover');
        });
    });
    importDropzone?.addEventListener('drop', function (event) {
        if (event.dataTransfer.files[0]) {
            importInput.files = event.dataTransfer.files;
            showImportFile(event.dataTransfer.files[0]);
        }
    });
    document.getElementById('libraryImportForm')?.addEventListener('submit', function () {
        if (importInput.files.length) {
            document.getElementById('importProgressOverlay').classList.add('show');
        }
    });
</script>
@endsection
