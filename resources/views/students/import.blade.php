@extends('layouts.master')
@section('title') Import Santri via Excel @endsection
@php $userId = request()->route('userId') ?? Auth::id(); @endphp

@section('css')
<style>
.import-hero {
    background: linear-gradient(135deg, #0d6efd08 0%, #19875408 100%);
    border: 2px dashed #dee2e6;
    border-radius: 16px;
    transition: all 0.25s ease;
    cursor: pointer;
}
.import-hero:hover,
.import-hero.dragover {
    border-color: #0d6efd;
    background: linear-gradient(135deg, #0d6efd15 0%, #19875415 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(13,110,253,0.12);
}
.import-hero .upload-icon {
    width: 80px; height: 80px;
    background: linear-gradient(135deg, #0d6efd20, #19875420);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1rem;
    font-size: 2rem; color: #0d6efd;
}
.step-card {
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1.25rem;
    transition: box-shadow 0.2s;
}
.step-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
.step-number {
    width: 36px; height: 36px;
    background: #0d6efd; color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 15px; flex-shrink: 0;
}
.rombel-badge {
    display: inline-flex; align-items: center; gap: 4px;
    background: #f0f9ff; border: 1px solid #bfdbfe;
    color: #1d4ed8; padding: 2px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
}
.duplicate-table { font-size: 12px; }
.duplicate-table thead th { background: #f8f9fa; white-space: nowrap; }
.dup-highlight { background: #fff8e1; }
.info-box {
    background: #f8f9fa;
    border-left: 4px solid #0d6efd;
    border-radius: 8px;
    padding: 1rem 1.25rem;
}
.info-box strong { color: #0d6efd; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Akademik @endslot
        @slot('li_2') <a href="{{ route('user.students.index', ['userId' => $userId]) }}">Data Santri</a> @endslot
        @slot('title') Import Santri @endslot
    @endcomponent

    {{-- ══════════════════════════════════════════════════════
         FEEDBACK AREA — Hasil Import Session
    ══════════════════════════════════════════════════════ --}}
    @if(session('import_result'))
        @php
            $result    = session('import_result');
            $totalDup  = count($result['duplicates'] ?? []);
            $totalErr  = count($result['errors']    ?? []);
            $created   = $result['created'] ?? 0;
            $allErrors = $result['errors']    ?? [];
        @endphp

        {{-- Ringkasan dalam cards --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-success shadow-sm">
                    <div class="card-body py-2 text-center">
                        <div class="text-success fw-bold fs-1">{{ $created }}</div>
                        <div class="text-muted small">Berhasil Diimport</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning shadow-sm">
                    <div class="card-body py-2 text-center">
                        <div class="text-warning fw-bold fs-1">{{ $totalDup }}</div>
                        <div class="text-muted small">Duplikat (Dilewati)</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-danger shadow-sm">
                    <div class="card-body py-2 text-center">
                        <div class="text-danger fw-bold fs-1">{{ $totalErr }}</div>
                        <div class="text-muted small">Error</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Error list — diperluas agar mudah dibaca --}}
        @if($totalErr)
            <div class="card border-danger mb-4">
                <div class="card-header bg-danger text-white py-2">
                    <h6 class="mb-0"><i class="ri-error-line me-1"></i> {{ $totalErr }} Error — Perbaiki lalu import ulang</h6>
                </div>
                <div class="card-body p-0" style="max-height:300px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Pesan Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allErrors as $i => $err)
                                <tr>
                                    <td class="text-center text-muted">{{ $i + 1 }}</td>
                                    <td class="text-danger small">{{ $err }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Duplikat list --}}
        @if($totalDup)
            <div class="card border-warning mb-4">
                <div class="card-header bg-warning text-dark py-2">
                    <h6 class="mb-0"><i class="ri-alert-line me-1"></i> {{ $totalDup }} Data Duplikat — dilewati, tidak ditimpa</h6>
                </div>
                <div class="card-body p-0" style="max-height:300px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px">#</th>
                                <th>NISN</th>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Sekolah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($result['duplicates'] as $dup)
                                <tr>
                                    <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                    <td><code>{{ $dup['nisn'] }}</code></td>
                                    <td><code>{{ $dup['nik'] }}</code></td>
                                    <td class="fw-semibold">{{ $dup['nama'] }}</td>
                                    <td class="text-muted small">{{ $dup['sekolah'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    {{-- Dev Error Banner — dari exception catch --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-line me-1"></i>
            <strong>Error:</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Validation errors dari Laravel --}}
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-line me-1"></i>
            <strong>Validasi gagal:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">

        {{-- ══════════════════════════════════════════════════
             KOLOM KIRI: Info Sekolah + Rombel + Panduan
        ══════════════════════════════════════════════════ --}}
        <div class="col-lg-5">

            {{-- Langkah-langkah --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-guide-line me-1 text-primary"></i>Cara Import
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-3">
                        <div class="step-card">
                            <div class="d-flex align-items-start gap-3">
                                <div class="step-number">1</div>
                                <div>
                                    <div class="fw-semibold mb-1">Unduh Template</div>
                                    <p class="text-muted small mb-2">Download template Excel berisi 55 kolom sesuai format Dapodik/EMIS.</p>
                                    <a href="{{ route('user.students.template', ['userId' => $userId]) }}"
                                       class="btn btn-sm btn-outline-success">
                                        <i class="ri-download-2-line me-1"></i> Download Template
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="step-card">
                            <div class="d-flex align-items-start gap-3">
                                <div class="step-number">2</div>
                                <div>
                                    <div class="fw-semibold mb-1">Isi Data</div>
                                    <p class="text-muted small mb-0">
                                        Baris 1-3 = info sekolah.<br>
                                        Baris 4-5 = header kolom.<br>
                                        Baris 6+ = data santrimu.<br>
                                        <strong>Nama</strong> dan <strong>JK (L/P)</strong> wajib.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="step-card">
                            <div class="d-flex align-items-start gap-3">
                                <div class="step-number">3</div>
                                <div>
                                    <div class="fw-semibold mb-1">Pilih Rombel</div>
                                    <p class="text-muted small mb-0">
                                        Tentukan rombel tujuan, maka setiap<br>
                                        Santri yang diimport langsung masuk rombel itu.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="step-card">
                            <div class="d-flex align-items-start gap-3">
                                <div class="step-number">4</div>
                                <div>
                                    <div class="fw-semibold mb-1">Upload</div>
                                    <p class="text-muted small mb-0">
                                        Drag file atau klik area upload.<br>
                                        NISN yang sudah ada akan dilaporkan & dilewati.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════
             KOLOM KANAN: Form Upload
        ══════════════════════════════════════════════════ --}}
        <div class="col-lg-7">


            {{-- Info Sekolah --}}
            @if($schools->count() === 1)
                <div class="info-box mb-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="ri-school-line fs-4 text-primary"></i>
                        <div>
                            <strong>{{ $schools->first()->name }}</strong>
                            <div class="text-muted small">{{ $schools->first()->npsn ?? '' }}</div>
                        </div>
                    </div>
                    <div class="text-muted small">
                        Semua data import akan masuk ke sekolah ini.
                    </div>
                </div>
            @endif
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ri-upload-2-line me-1 text-primary"></i>Unggah File Excel
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('user.students.import-process', ['userId' => $userId]) }}"
                          method="POST" enctype="multipart/form-data" id="importForm">
                        @csrf

                        {{-- Dropzone --}}
                        <div class="import-hero p-4 text-center mb-4" id="dropZone"
                             onclick="document.getElementById('excelFile').click()">
                            <input type="file" id="excelFile" name="file" accept=".xlsx,.xls"
                                   class="d-none" onchange="handleFileSelect(this)">
                            <div class="upload-icon">
                                <i class="ri-file-upload-line"></i>
                            </div>
                            <p class="mb-1 fw-semibold">Klik atau seret file Excel ke sini</p>
                            <p class="text-muted small mb-0">Format: .xlsx / .xls — Maksimal 10 MB</p>
                            <p id="fileNameDisplay" class="mt-2 fw-semibold mb-0" style="display:none"></p>
                        </div>

                        @error('file')
                            <div class="text-danger small mb-3">{{ $message }}</div>
                        @enderror

                        {{-- Hidden school if single --}}
                        @if($schools->count() === 1)
                            <input type="hidden" name="school_id" value="{{ $schools->first()->id }}">
                        @elseif($schools->count() > 1)
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Sekolah Tujuan</label>
                                <select name="school_id" class="form-select @error('school_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Sekolah --</option>
                                    @foreach($schools as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Rombel Tujuan --}}
                        @if($studyGroups->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Rombel Tujuan <span class="text-muted">(opsional)</span></label>
                                <select name="study_group_id" class="form-select @error('study_group_id') is-invalid @enderror">
                                    <option value="">Tanpa rombel — simpan dulu saja</option>
                                    @foreach($studyGroups as $sg)
                                        <option value="{{ $sg->id }}"
                                            {{ $studyGroupId == $sg->id ? 'selected' : '' }}>
                                            {{ $sg->gradeLevel?->name ?? '' }} — {{ $sg->name }}
                                            ({{ $sg->capacity - ($sg->studentCount ?? 0) }} slot tersisa)
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Santri yang diimport langsung masuk rombel yang dipilih.</div>

                                {{-- Warning: kapasitas rombel --}}
                                <div class="alert alert-warning py-2 px-3 small mt-2 mb-0" id="rombelCapacityWarning" style="display:none">
                                    <i class="ri-error-warning-line me-1"></i>
                                    <span id="capacityWarningText"></span>
                                </div>
                            </div>
                        @endif

                        {{-- Tombol aksi --}}
                        <div class="d-flex gap-2 align-items-center">
                            <a href="{{ route('user.students.index', ['userId' => $userId]) }}"
                               class="btn btn-light">
                                <i class="ri-arrow-left-line me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                <i class="ri-upload-2-line me-1"></i> Import Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            
            {{-- Info Penting --}}
            <div class="alert alert-warning py-2 px-3 small mb-2">
                <i class="ri-error-warning-line me-1"></i>
                <strong>Catatan:</strong> NISN/NIK yang sudah terdaftar akan dilewati dan dilaporkan. Semua kolom selain Nama & JK boleh kosong.
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
function handleFileSelect(input) {
    var file = input.files[0];
    if (!file) return;

    var maxSize = 10 * 1024 * 1024;
    if (file.size > maxSize) {
        alert('Ukuran file maksimal 10MB.');
        input.value = '';
        return;
    }

    if (!file.name.match(/\.xlsx?$/i)) {
        alert('Format file harus .xlsx atau .xls');
        input.value = '';
        return;
    }

    var display = document.getElementById('fileNameDisplay');
    var dropZone = document.getElementById('dropZone');
    var submitBtn = document.getElementById('submitBtn');

    display.textContent = '✓ ' + file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
    display.style.color = '#198754';
    display.style.display = '';
    dropZone.style.borderColor = '#198754';
    submitBtn.disabled = false;
}

// Rombel capacity warning
(function() {
    var select = document.querySelector('select[name="study_group_id"]');
    var warnBox = document.getElementById('rombelCapacityWarning');
    var warnText = document.getElementById('capacityWarningText');

    if (!select || !warnBox) return;

    function checkCapacity() {
        var opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) {
            warnBox.style.display = 'none';
            return;
        }
        // Format: "X — Name (Y slot tersisa)"
        var match = opt.textContent.match(/\((\d+) slot/);
        if (!match) {
            warnBox.style.display = 'none';
            return;
        }
        var remaining = parseInt(match[1], 10);
        var text = opt.textContent.split('(')[0].trim();
        if (remaining === 0) {
            warnText.textContent = '⚠️ Kapasitas penuh — pilih rombel lain.';
            warnBox.style.display = '';
            warnBox.className = 'alert alert-danger py-2 px-3 small mt-2 mb-0';
            submitBtn.disabled = true;
        } else if (remaining <= 5) {
            warnText.textContent = '⚠️ Hanya ' + remaining + ' slot tersisa untuk rombel ini.';
            warnBox.style.display = '';
            warnBox.className = 'alert alert-warning py-2 px-3 small mt-2 mb-0';
        } else {
            warnBox.style.display = 'none';
        }
    }

    select.addEventListener('change', checkCapacity);
    checkCapacity();
})();

// Drag & drop
var dropZone = document.getElementById('dropZone');
['dragenter', 'dragover'].forEach(function(evt) {
    dropZone.addEventListener(evt, function(e) {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });
});
['dragleave', 'drop'].forEach(function(evt) {
    dropZone.addEventListener(evt, function(e) {
        e.preventDefault();
        dropZone.classList.remove('dragover');
    });
});
dropZone.addEventListener('drop', function(e) {
    var file = e.dataTransfer.files[0];
    if (file) {
        var input = document.getElementById('excelFile');
        var dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        handleFileSelect(input);
    }
});
</script>
@endsection